package co.inees.plataforma.reportes;

import static co.inees.plataforma.comun.Html.esc;

import java.text.Normalizer;
import java.time.DayOfWeek;
import java.time.LocalDate;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;
import java.util.TreeMap;
import java.util.TreeSet;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.stereotype.Component;

import co.inees.plataforma.comun.Html;
import co.inees.plataforma.comun.Parametros;
import co.inees.plataforma.comun.Pdf;

/**
 * Reporte técnico diario: por cada día, servicios de cada técnico por tipo, cumplimiento de la meta (semáforo),
 * jornada, desplazamientos entre servicios (descontando el almuerzo) y fallidos por cliente.
 * Incluye las remisiones usadas en instalaciones/desinstalaciones sin servicio asociado, como en PHP.
 */
@Component
public class ReporteTecnico {

	public record Item(int id, String remision, LocalDate fecha, LocalTime horaEntrada, LocalTime horaSalida,
		Integer minutosServicio, String tecnico, String cliente, String punto, String delegacion, String deviceId,
		String tipo, String novedades, String actividades) {
	}

	public record FilaTecnico(String tecnico, Map<String, Integer> porTipo, int servicios, int fallidos, int sinFallidos,
		int meta, int metaAmarillo, boolean nuevaArquitectura, String semaforo, double porcentaje, LocalTime entrada,
		LocalTime salida, int minutosJornada, int minutosDesplazamiento, List<Integer> tramos) {
	}

	public record Fallido(String delegacion, String cliente, String puntos, int total, String novedades) {
	}

	public record Dia(LocalDate fecha, boolean noLaboral, List<String> tipos, List<FilaTecnico> tecnicos,
		Map<String, Integer> totales, int total, List<Fallido> fallidos, List<Item> servicios) {
	}

	private static final Set<String> ESTADOS_REMISION = Set.of("KISAN", "DESINSTALACION", "CAMBIO DE MAQUINA",
		"INSTALACION FALLIDA", "DESANCLAJE", "ANCLAJE", "INSTALACION MAS CAPACITACION", "INSTALACION SIN CAPACITACION",
		"INSTALACION");

	private final JdbcClient jdbc;
	private final Parametros parametros;

	public ReporteTecnico(JdbcClient jdbc, Parametros parametros) {
		this.jdbc = jdbc;
		this.parametros = parametros;
	}

	public List<Dia> generar(LocalDate desde, LocalDate hasta, Integer idTecnico) {
		List<Item> items = new ArrayList<>(jdbc.sql("""
			SELECT o.id_ordenes_servicio AS id, o.numero_remision AS remision, o.fecha_visita AS fecha, o.hora_entrada,
			       o.hora_salida, o.minutos_servicio, t.nombre_tecnico AS tecnico, c.nombre_cliente AS cliente,
			       p.nombre_punto AS punto, d.nombre_delegacion AS delegacion, m.device_id, tm.nombre_completo AS tipo,
			       (SELECT GROUP_CONCAT(tn.nombre_novedad SEPARATOR ', ') FROM orden_servicio_novedad osn
			          JOIN tipo_novedad tn ON tn.id_tipo_novedad = osn.id_tipo_novedad WHERE osn.id_orden_servicio = o.id_ordenes_servicio) AS novedades,
			       o.actividades_realizadas AS actividades
			FROM ordenes_servicio o JOIN tecnico t ON t.id_tecnico = o.id_tecnico JOIN punto p ON p.id_punto = o.id_punto
			JOIN cliente c ON c.id_cliente = p.id_cliente LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			JOIN maquina m ON m.id_maquina = o.id_maquina LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h AND (:t IS NULL OR o.id_tecnico = :t)
			  AND UPPER(COALESCE(tm.nombre_completo, '')) NOT LIKE '%INSTALACI%'""")
			.param("d", desde).param("h", hasta).param("t", idTecnico).query(Item.class).list());
		jdbc.sql("""
			SELECT cr.id_control, cr.numero_remision, DATE(cr.fecha_uso), TIME(cr.fecha_uso), t.nombre_tecnico, e.nombre_estado
			FROM control_remisiones cr JOIN tecnico t ON t.id_tecnico = cr.id_tecnico JOIN estados_remision e ON e.id_estado = cr.id_estado
			WHERE cr.fecha_uso IS NOT NULL AND DATE(cr.fecha_uso) BETWEEN :d AND :h AND cr.id_orden_servicio IS NULL
			  AND (:t IS NULL OR cr.id_tecnico = :t)""")
			.param("d", desde).param("h", hasta).param("t", idTecnico)
			.query((rs, i) -> {
				String estado = rs.getString(6);
				if (ESTADOS_REMISION.contains(normalizar(estado))) {
					LocalTime h = rs.getObject(4, LocalTime.class);
					items.add(new Item(-rs.getInt(1), rs.getString(2), rs.getObject(3, LocalDate.class), h, h, 0, rs.getString(5),
						"SIN CLIENTE (REMISIÓN)", "N/A", "N/A", "N/A", estado, null, estado));
				}
				return null;
			}).list();

		Set<LocalDate> festivos = new HashSet<>(jdbc.sql("SELECT fecha FROM dias_festivos WHERE fecha BETWEEN :d AND :h")
			.param("d", desde).param("h", hasta).query(LocalDate.class).list());
		Map<LocalDate, List<Item>> porDia = new TreeMap<>(Comparator.reverseOrder());
		items.forEach(i -> porDia.computeIfAbsent(i.fecha(), k -> new ArrayList<>()).add(i));
		List<Dia> dias = new ArrayList<>();
		porDia.forEach((fecha, lista) -> dias.add(dia(fecha, lista, festivos.contains(fecha))));
		return dias;
	}

	private Dia dia(LocalDate fecha, List<Item> lista, boolean festivo) {
		boolean sabado = fecha.getDayOfWeek() == DayOfWeek.SATURDAY;
		boolean noLaboral = festivo || fecha.getDayOfWeek() == DayOfWeek.SUNDAY;
		int metaVerde = sabado ? parametros.entero(Parametros.META_SABADO, 3) : parametros.entero(Parametros.META_DIA, 6);
		int metaNa = sabado ? parametros.entero(Parametros.META_NA_SABADO, 2) : parametros.entero(Parametros.META_NA_DIA, 3);
		int almuerzo = parametros.entero(Parametros.MINUTOS_ALMUERZO, 60);

		Set<String> tipos = new TreeSet<>();
		lista.forEach(i -> tipos.add(i.tipo() == null ? "SIN ESPECIFICAR" : i.tipo()));
		Map<String, List<Item>> porTecnico = new TreeMap<>();
		lista.forEach(i -> porTecnico.computeIfAbsent(i.tecnico(), k -> new ArrayList<>()).add(i));

		List<FilaTecnico> filas = new ArrayList<>();
		Map<String, Integer> totales = new LinkedHashMap<>();
		tipos.forEach(t -> totales.put(t, 0));
		for (var e : porTecnico.entrySet()) {
			Map<String, Integer> porTipo = new LinkedHashMap<>();
			tipos.forEach(t -> porTipo.put(t, 0));
			int fallidos = 0;
			boolean na = false;
			LocalTime entrada = null, salida = null;
			int minutos = 0;
			for (Item i : e.getValue()) {
				String t = i.tipo() == null ? "SIN ESPECIFICAR" : i.tipo();
				porTipo.merge(t, 1, Integer::sum);
				totales.merge(t, 1, Integer::sum);
				String n = normalizar(t);
				if (n.contains("FALLID")) {
					fallidos++;
				}
				if (n.contains("NUEVA ARQUITECTURA")) {
					na = true;
				}
				if (i.horaEntrada() != null && (entrada == null || i.horaEntrada().isBefore(entrada))) {
					entrada = i.horaEntrada();
				}
				if (i.horaSalida() != null && (salida == null || i.horaSalida().isAfter(salida))) {
					salida = i.horaSalida();
				}
				minutos += i.minutosServicio() == null ? 0 : i.minutosServicio();
			}
			if (minutos == 0 && entrada != null && salida != null && salida.isAfter(entrada)) {
				minutos = (int) java.time.Duration.between(entrada, salida).toMinutes();
			}
			// La jornada descuenta una hora de almuerzo (como el reporte PHP)
			minutos = minutos > almuerzo ? minutos - almuerzo : 0;
			int servicios = e.getValue().size();
			int sinFallidos = Math.max(0, servicios - fallidos);
			int meta = noLaboral ? 0 : na ? metaNa : metaVerde;
			int metaAmarillo = (int) Math.ceil(meta * 2 / 3.0);
			String semaforo = meta <= 0 ? "SIN_META" : sinFallidos >= meta ? "CUMPLE" : sinFallidos >= metaAmarillo ? "POR_DEBAJO" : "BAJO";
			List<Integer> tramos = desplazamientos(e.getValue(), almuerzo);
			filas.add(new FilaTecnico(e.getKey(), porTipo, servicios, fallidos, sinFallidos, meta, metaAmarillo, na, semaforo,
				meta > 0 ? Math.round(sinFallidos * 1000.0 / meta) / 10.0 : 0, entrada, salida, minutos,
				tramos.stream().mapToInt(Integer::intValue).sum(), tramos));
		}

		// Fallidos agrupados por delegación y cliente
		Map<String, Map<String, List<Item>>> fallidos = new TreeMap<>();
		lista.stream().filter(i -> normalizar(i.tipo()).contains("FALLID")).forEach(i -> fallidos
			.computeIfAbsent(i.delegacion() == null ? "SIN DELEGACIÓN" : i.delegacion(), k -> new LinkedHashMap<>())
			.computeIfAbsent(i.cliente(), k -> new ArrayList<>()).add(i));
		List<Fallido> listaFallidos = new ArrayList<>();
		fallidos.forEach((del, clientes) -> clientes.entrySet().stream()
			.sorted((a, b) -> b.getValue().size() - a.getValue().size())
			.forEach(c -> listaFallidos.add(new Fallido(del, c.getKey(),
				String.join(", ", c.getValue().stream().map(Item::punto).distinct().toList()), c.getValue().size(),
				String.join(", ", c.getValue().stream().filter(i -> i.novedades() != null)
					.flatMap(i -> java.util.Arrays.stream(i.novedades().split(",\\s*"))).distinct().toList())))));

		List<Item> ordenados = lista.stream().sorted(Comparator.comparing(Item::tecnico)
			.thenComparing(i -> i.horaEntrada() == null ? LocalTime.MAX : i.horaEntrada())).toList();
		return new Dia(fecha, noLaboral, List.copyOf(tipos), filas, totales, lista.size(), listaFallidos, ordenados);
	}

	/** Tiempo entre la salida de un servicio y la entrada del siguiente; al tramo del mediodía se le resta el almuerzo. */
	static List<Integer> desplazamientos(List<Item> servicios, int almuerzo) {
		List<Item> orden = servicios.stream().sorted(Comparator.comparing(i -> i.horaEntrada() == null ? LocalTime.MAX : i.horaEntrada()))
			.toList();
		List<int[]> tramos = new ArrayList<>();
		LocalTime anterior = null;
		for (Item s : orden) {
			if (anterior != null && s.horaEntrada() != null) {
				int ini = anterior.toSecondOfDay() / 60, fin = s.horaEntrada().toSecondOfDay() / 60;
				if (fin > ini) {
					tramos.add(new int[] { fin - ini, ini, fin });
				}
			}
			anterior = s.horaSalida();
		}
		int objetivo = -1, mejor = 0;
		for (int i = 0; i < tramos.size(); i++) {
			int solape = Math.min(tramos.get(i)[2], 14 * 60) - Math.max(tramos.get(i)[1], 12 * 60);
			if (solape > mejor) {
				mejor = solape;
				objetivo = i;
			}
		}
		if (objetivo < 0) {
			int mayor = almuerzo;
			for (int i = 0; i < tramos.size(); i++) {
				if (tramos.get(i)[0] > mayor) {
					mayor = tramos.get(i)[0];
					objetivo = i;
				}
			}
		}
		if (objetivo >= 0) {
			tramos.get(objetivo)[0] = Math.max(0, tramos.get(objetivo)[0] - almuerzo);
		}
		return tramos.stream().map(t -> t[0]).filter(m -> m > 0).toList();
	}

	public String html(LocalDate desde, LocalDate hasta, List<Dia> dias) {
		StringBuilder b = new StringBuilder(Pdf.encabezado("Reporte técnico de servicios", Html.fecha(desde) + " al " + Html.fecha(hasta)));
		boolean primero = true;
		for (Dia d : dias) {
			if (!primero) {
				b.append("<div class=\"salto\"></div>");
			}
			primero = false;
			b.append("<h2>").append(Html.fechaLarga(d.fecha())).append(d.noLaboral() ? " (no laboral)" : "").append(" · ")
				.append(d.total()).append(" servicios</h2>");
			List<String> cab = new ArrayList<>(List.of("Técnico"));
			cab.addAll(d.tipos());
			cab.addAll(List.of("Total", "Meta", "Cumplimiento", "Jornada", "Desplazamiento"));
			b.append(Html.tabla(cab, d.tecnicos(), f -> {
				List<String> c = new ArrayList<>();
				c.add(esc(f.tecnico()) + (f.nuevaArquitectura() ? " <span class=\"muted\">(NA)</span>" : ""));
				d.tipos().forEach(t -> c.add("<div class=\"cen\">" + f.porTipo().getOrDefault(t, 0) + "</div>"));
				c.add("<div class=\"cen\"><b>" + f.servicios() + "</b></div>");
				c.add("<div class=\"cen\">" + (f.meta() == 0 ? "—" : f.meta()) + "</div>");
				String color = switch (f.semaforo()) {
					case "CUMPLE" -> "#17806f";
					case "POR_DEBAJO" -> "#b7791f";
					case "BAJO" -> "#d2493f";
					default -> "#6f7d8c";
				};
				c.add("<b style=\"color:" + color + "\">" + (f.meta() == 0 ? "Sin meta" : f.porcentaje() + "%") + "</b>"
					+ (f.fallidos() > 0 ? " <span class=\"muted\">(" + f.fallidos() + " fallidos)</span>" : ""));
				c.add(Html.hora(f.entrada()) + "–" + Html.hora(f.salida()) + "<br/><span class=\"muted\">" + Html.minutos(f.minutosJornada()) + " h</span>");
				c.add(Html.minutos(f.minutosDesplazamiento()) + " h");
				return c;
			}));
			if (!d.fallidos().isEmpty()) {
				b.append("<h3>Fallidos</h3>").append(Html.tabla(List.of("Delegación", "Cliente", "Puntos", "Total", "Novedades"),
					d.fallidos(), f -> List.of(esc(f.delegacion()), esc(f.cliente()), esc(f.puntos()), String.valueOf(f.total()), esc(f.novedades()))));
			}
			b.append("<h3>Detalle de servicios</h3>").append(Html.tabla(List.of("Técnico", "Remisión", "Entrada", "Salida", "Cliente / Punto",
				"Delegación", "Máquina", "Tipo", "Novedades"), d.servicios(), i -> List.of(esc(i.tecnico()), esc(i.remision()),
					Html.hora(i.horaEntrada()), Html.hora(i.horaSalida()), esc(i.cliente()) + "<br/><span class=\"muted\">" + esc(i.punto()) + "</span>",
					esc(i.delegacion()), esc(i.deviceId()), esc(i.tipo()), esc(i.novedades()))));
		}
		if (dias.isEmpty()) {
			b.append("<p class=\"muted\">No hay servicios en el rango.</p>");
		}
		return Pdf.documento("Reporte técnico", "@page { size: A4 landscape; margin: 9mm; }", b.toString());
	}

	static String normalizar(String s) {
		if (s == null) {
			return "";
		}
		return Normalizer.normalize(s.trim().toUpperCase(Locale.ROOT), Normalizer.Form.NFD).replaceAll("\\p{M}", "");
	}
}
