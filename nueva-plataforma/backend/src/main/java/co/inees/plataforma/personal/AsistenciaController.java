package co.inees.plataforma.personal;

import java.io.BufferedReader;
import java.io.IOException;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.text.Normalizer;
import java.time.DayOfWeek;
import java.time.LocalDate;
import java.time.LocalTime;
import java.time.format.DateTimeFormatter;
import java.time.format.TextStyle;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;
import java.util.TreeMap;

import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestPart;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.comun.Descarga;
import co.inees.plataforma.comun.Excel;
import co.inees.plataforma.config.NegocioException;

/**
 * Procesador de asistencias: cruza el CSV del huellero (empleado, fecha, hora) con los servicios de la plataforma
 * para obtener entrada y salida de cada día. El cálculo de horas extra y recargos se hace en la pantalla (editable)
 * y aquí solo se arma el Excel final (permiso PERSONAL).
 */
@RestController
@RequestMapping("/api/personal/asistencia")
public class AsistenciaController {

	public record Dia(String nombre, String cargo, LocalDate fecha, boolean domingoFestivo, String entrada, String salida,
		int servicios, String falta) {
	}

	public record Procesado(LocalDate desde, LocalDate hasta, List<Dia> dias, List<String> sinCoincidencia) {
	}

	/** Fila ya calculada en la pantalla (horas en minutos, valores en pesos). */
	public record FilaCalculada(String nombre, String cargo, LocalDate fecha, boolean domingoFestivo, String entrada,
		String salida, int servicios, int minutosTrabajados, int extrasDiurnas, int extrasNocturnas, int recargoNocturno,
		int recargoDomFest, int extrasDiurnasDomFest, int extrasNocturnasDomFest, int recargoNocturnoDomFest,
		double valorExtras, double valorRecargos, String novedades) {
	}

	public record Exportar(List<FilaCalculada> filas, Map<String, Double> salarios) {
	}

	private static final DateTimeFormatter[] FORMATOS = { DateTimeFormatter.ofPattern("d/M/yyyy"), DateTimeFormatter.ofPattern("d-M-yyyy"),
		DateTimeFormatter.ISO_LOCAL_DATE, DateTimeFormatter.ofPattern("yyyy/M/d"), DateTimeFormatter.ofPattern("M/d/yyyy") };

	private final JdbcClient jdbc;

	public AsistenciaController(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	@PostMapping(value = "/procesar", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	public Procesado procesar(@RequestPart("archivo") MultipartFile archivo) {
		// 1. Marcaciones del huellero: primera y última del día por empleado
		Map<String, Map<LocalDate, LocalTime[]>> marcas = new LinkedHashMap<>();
		Map<String, String> nombreOriginal = new LinkedHashMap<>();
		LocalDate min = null, max = null;
		for (String[] f : leerCsv(archivo)) {
			if (f.length < 3) {
				continue;
			}
			String nombre = f[0].trim(), fechaTxt = f[1].trim(), horaTxt = f[2].trim();
			if (nombre.isEmpty() || nombre.toLowerCase(Locale.ROOT).contains("empleado") || fechaTxt.toLowerCase(Locale.ROOT).contains("fecha")) {
				continue;
			}
			LocalDate fecha = fecha(fechaTxt);
			LocalTime hora = hora(horaTxt);
			if (fecha == null || hora == null) {
				continue;
			}
			String clave = normalizar(nombre);
			nombreOriginal.putIfAbsent(clave, nombre);
			LocalTime[] eh = marcas.computeIfAbsent(clave, k -> new TreeMap<>()).computeIfAbsent(fecha, k -> new LocalTime[] { hora, hora });
			if (hora.isBefore(eh[0])) {
				eh[0] = hora;
			}
			if (hora.isAfter(eh[1])) {
				eh[1] = hora;
			}
			min = min == null || fecha.isBefore(min) ? fecha : min;
			max = max == null || fecha.isAfter(max) ? fecha : max;
		}
		if (min == null) {
			throw new NegocioException("El archivo está vacío o las fechas no se reconocen (columnas: empleado, fecha, hora).");
		}

		// 2. Empleados de la plataforma: técnicos y usuarios activos
		Map<String, String[]> empleados = new LinkedHashMap<>();
		jdbc.sql("""
			SELECT t.nombre_tecnico, 'Técnico' FROM tecnico t LEFT JOIN usuarios u ON u.usuario_id = t.usuario_id
			WHERE t.estado = 1 AND (u.estado = 'activo' OR u.estado IS NULL)
			UNION SELECT nombre, cargo FROM usuarios WHERE estado = 'activo' AND id_rol <> 3""")
			.query((rs, i) -> empleados.putIfAbsent(normalizar(rs.getString(1)), new String[] { rs.getString(1), rs.getString(2) })).list();

		// 3. Cada nombre del huellero se asocia a un empleado (todas las palabras o 85 % de parecido)
		Map<String, String> asociado = new LinkedHashMap<>();
		List<String> sinCoincidencia = new ArrayList<>();
		for (String clave : marcas.keySet()) {
			String emp = buscar(clave, empleados.keySet());
			if (emp == null) {
				empleados.put(clave, new String[] { nombreOriginal.get(clave), "No registrado en la plataforma" });
				sinCoincidencia.add(nombreOriginal.get(clave));
				emp = clave;
			}
			asociado.put(clave, emp);
		}

		// 4. Servicios de la plataforma: amplían la jornada si empezaron antes o terminaron después
		Map<String, Map<LocalDate, Object[]>> servicios = new LinkedHashMap<>();
		jdbc.sql("""
			SELECT t.nombre_tecnico, o.fecha_visita, MIN(o.hora_entrada), MAX(o.hora_salida), COUNT(*) FROM ordenes_servicio o
			JOIN tecnico t ON t.id_tecnico = o.id_tecnico WHERE o.fecha_visita BETWEEN :d AND :h AND o.estado = 1
			GROUP BY t.id_tecnico, o.fecha_visita""").param("d", min).param("h", max)
			.query((rs, i) -> {
				String emp = buscar(normalizar(rs.getString(1)), empleados.keySet());
				if (emp != null) {
					servicios.computeIfAbsent(emp, k -> new LinkedHashMap<>()).put(rs.getObject(2, LocalDate.class),
						new Object[] { rs.getObject(3, LocalTime.class), rs.getObject(4, LocalTime.class), rs.getInt(5) });
				}
				return null;
			}).list();
		Set<LocalDate> festivos = new HashSet<>(jdbc.sql("SELECT fecha FROM dias_festivos WHERE fecha BETWEEN :d AND :h")
			.param("d", min).param("h", max).query(LocalDate.class).list());

		// 5. Una fila por empleado y día del rango
		Map<String, Map<LocalDate, LocalTime[]>> porEmpleado = new LinkedHashMap<>();
		marcas.forEach((clave, dias) -> porEmpleado.computeIfAbsent(asociado.get(clave), k -> new TreeMap<>()).putAll(dias));
		List<Dia> dias = new ArrayList<>();
		for (var e : empleados.entrySet()) {
			Map<LocalDate, LocalTime[]> m = porEmpleado.getOrDefault(e.getKey(), Map.of());
			Map<LocalDate, Object[]> s = servicios.getOrDefault(e.getKey(), Map.of());
			if (m.isEmpty() && s.isEmpty()) {
				continue;
			}
			for (LocalDate f = min; !f.isAfter(max); f = f.plusDays(1)) {
				LocalTime[] eh = m.get(f);
				Object[] sv = s.get(f);
				LocalTime entrada = eh == null ? null : eh[0], salida = eh == null || eh[0].equals(eh[1]) ? null : eh[1];
				int cant = 0;
				if (sv != null) {
					LocalTime se = (LocalTime) sv[0], ss = (LocalTime) sv[1];
					if (se != null && (entrada == null || se.isBefore(entrada))) {
						entrada = se;
					}
					if (ss != null && (salida == null || ss.isAfter(salida))) {
						salida = ss;
					}
					cant = (int) sv[2];
				}
				String falta = entrada == null ? "Falta entrada" : salida == null ? "Falta salida" : null;
				dias.add(new Dia(e.getValue()[0], e.getValue()[1], f, f.getDayOfWeek() == DayOfWeek.SUNDAY || festivos.contains(f),
					entrada == null ? null : entrada.toString().substring(0, 5), salida == null ? null : salida.toString().substring(0, 5),
					cant, falta));
			}
		}
		return new Procesado(min, max, dias, sinCoincidencia);
	}

	/** Excel final: una hoja por empleado con el detalle diario y una hoja de resumen. */
	@PostMapping("/excel")
	public ResponseEntity<byte[]> excel(@RequestBody Exportar datos) {
		if (datos.filas() == null || datos.filas().isEmpty()) {
			throw new NegocioException("No hay datos para exportar.");
		}
		Map<String, List<FilaCalculada>> porEmpleado = new LinkedHashMap<>();
		datos.filas().forEach(f -> porEmpleado.computeIfAbsent(f.nombre(), k -> new ArrayList<>()).add(f));
		Excel x = new Excel();
		List<List<?>> resumen = new ArrayList<>();
		for (var e : porEmpleado.entrySet()) {
			List<List<?>> filas = new ArrayList<>();
			int trab = 0, ext = 0, rec = 0, serv = 0;
			double vExt = 0, vRec = 0;
			for (FilaCalculada f : e.getValue()) {
				String dia = f.fecha().getDayOfWeek().getDisplayName(TextStyle.FULL, Locale.forLanguageTag("es-CO"));
				filas.add(java.util.Arrays.asList(f.fecha(), dia, f.domingoFestivo() ? "Sí" : "", f.entrada(), f.salida(), f.servicios(),
					h(f.minutosTrabajados()), h(f.extrasDiurnas()), h(f.extrasNocturnas()), h(f.recargoNocturno()), h(f.recargoDomFest()),
					h(f.extrasDiurnasDomFest()), h(f.extrasNocturnasDomFest()), h(f.recargoNocturnoDomFest()),
					new Excel.Pesos(f.valorExtras()), new Excel.Pesos(f.valorRecargos()),
					new Excel.Pesos(f.valorExtras() + f.valorRecargos()), f.novedades()));
				trab += f.minutosTrabajados();
				ext += f.extrasDiurnas() + f.extrasNocturnas() + f.extrasDiurnasDomFest() + f.extrasNocturnasDomFest();
				rec += f.recargoNocturno() + f.recargoDomFest() + f.recargoNocturnoDomFest();
				serv += f.servicios();
				vExt += f.valorExtras();
				vRec += f.valorRecargos();
			}
			Double salario = datos.salarios() == null ? null : datos.salarios().get(e.getKey());
			x.hoja(e.getKey(), e.getKey() + " · " + e.getValue().get(0).cargo() + (salario == null ? "" : " · salario $" + Math.round(salario)),
				List.of("Fecha", "Día", "Dom/fest", "Entrada", "Salida", "Servicios", "Trabajado (h)", "HE diurna", "HE nocturna",
					"Rec. nocturno", "Rec. dom/fest", "HE diurna dom/fest", "HE nocturna dom/fest", "Rec. noct. dom/fest", "Valor HE",
					"Valor recargos", "Total", "Novedades"), filas);
			resumen.add(java.util.Arrays.asList(e.getKey(), e.getValue().get(0).cargo(), serv, h(trab), h(ext), new Excel.Pesos(vExt), h(rec),
				new Excel.Pesos(vRec), new Excel.Pesos(vExt + vRec)));
		}
		x.hoja("Resumen", List.of("Empleado", "Cargo", "Servicios", "Horas trabajadas", "Horas extra", "Valor horas extra",
			"Horas de recargo", "Valor recargos", "Total a pagar"), resumen);
		return Descarga.excel("Asistencias_" + LocalDate.now(), x.bytes());
	}

	private static double h(int minutos) {
		return Math.round(minutos / 60.0 * 100) / 100.0;
	}

	private static String buscar(String nombre, Set<String> empleados) {
		if (empleados.contains(nombre)) {
			return nombre;
		}
		String[] partes = nombre.split("\\s+");
		String mejor = null;
		double mejorPct = 0;
		for (String e : empleados) {
			boolean todas = true;
			for (String p : partes) {
				if (!p.isEmpty() && !e.contains(p)) {
					todas = false;
					break;
				}
			}
			if (todas) {
				return e;
			}
			double pct = parecido(nombre, e);
			if (pct > mejorPct) {
				mejorPct = pct;
				mejor = e;
			}
		}
		return mejorPct >= 85 ? mejor : null;
	}

	/** Porcentaje de parecido entre dos textos (Levenshtein normalizado). */
	static double parecido(String a, String b) {
		int[] prev = new int[b.length() + 1], cur = new int[b.length() + 1];
		for (int j = 0; j <= b.length(); j++) {
			prev[j] = j;
		}
		for (int i = 1; i <= a.length(); i++) {
			cur[0] = i;
			for (int j = 1; j <= b.length(); j++) {
				cur[j] = Math.min(Math.min(cur[j - 1] + 1, prev[j] + 1), prev[j - 1] + (a.charAt(i - 1) == b.charAt(j - 1) ? 0 : 1));
			}
			int[] t = prev;
			prev = cur;
			cur = t;
		}
		int largo = Math.max(a.length(), b.length());
		return largo == 0 ? 100 : (1 - prev[b.length()] / (double) largo) * 100;
	}

	static String normalizar(String s) {
		return Normalizer.normalize(s.trim().toUpperCase(Locale.ROOT), Normalizer.Form.NFD).replaceAll("\\p{M}", "").replaceAll("\\s+", " ");
	}

	private static List<String[]> leerCsv(MultipartFile archivo) {
		if (archivo == null || archivo.isEmpty()) {
			throw new NegocioException("Adjunte el archivo CSV del huellero.");
		}
		try {
			byte[] bytes = archivo.getBytes();
			String texto = new String(bytes, StandardCharsets.UTF_8);
			if (texto.contains("�")) {
				texto = new String(bytes, Charset.forName("windows-1252"));
			}
			List<String[]> filas = new ArrayList<>();
			try (BufferedReader r = new BufferedReader(new java.io.StringReader(texto.replace("﻿", "")))) {
				String linea;
				while ((linea = r.readLine()) != null) {
					String sep = linea.contains(";") && !linea.contains(",") ? ";" : ",";
					filas.add(linea.replace("\"", "").split(sep, -1));
				}
			}
			return filas;
		}
		catch (IOException e) {
			throw new NegocioException("No se pudo leer el archivo.");
		}
	}

	private static LocalDate fecha(String s) {
		String t = s.trim().split(" ")[0];
		for (DateTimeFormatter f : FORMATOS) {
			try {
				return LocalDate.parse(t, f);
			}
			catch (RuntimeException e) {
				// siguiente formato
			}
		}
		return null;
	}

	private static LocalTime hora(String s) {
		String t = s.trim().toLowerCase(Locale.ROOT).replace(".", "");
		try {
			boolean pm = t.endsWith("pm"), am = t.endsWith("am");
			t = t.replace("am", "").replace("pm", "").trim();
			String[] p = t.split(":");
			int hh = Integer.parseInt(p[0]), mm = p.length > 1 ? Integer.parseInt(p[1]) : 0;
			if (pm && hh < 12) {
				hh += 12;
			}
			if (am && hh == 12) {
				hh = 0;
			}
			return LocalTime.of(hh, mm);
		}
		catch (RuntimeException e) {
			return null;
		}
	}
}
