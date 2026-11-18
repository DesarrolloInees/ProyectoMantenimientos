package co.inees.plataforma.importar;

import java.io.IOException;
import java.io.InputStream;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;

import org.apache.poi.ss.usermodel.Cell;
import org.apache.poi.ss.usermodel.DataFormatter;
import org.apache.poi.ss.usermodel.Row;
import org.apache.poi.ss.usermodel.Sheet;
import org.apache.poi.ss.usermodel.Workbook;
import org.apache.poi.ss.usermodel.WorkbookFactory;
import org.apache.poi.ss.util.CellReference;
import org.springframework.http.MediaType;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RequestPart;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.config.NegocioException;

/**
 * Importaciones desde Excel (permiso ADMINISTRACION). Todas tienen modo simulación: primero se ve qué va a cambiar
 * y después se confirma.
 */
@RestController
@RequestMapping("/api/importar")
public class ImportarController {

	public record FilaBase(int fila, String deviceId, String cliente, String punto, String accion, String clienteAnterior,
		String puntoAnterior, String tipoMaquina, String delegacion) {
	}

	public record ResultadoBase(boolean simulacion, int filas, int nuevas, int actualizadas, int errores, List<FilaBase> detalle,
		List<FilaBase> fantasmas, int maquinasDesactivadas, int puntosDesactivados, List<String> mensajes) {
	}

	public record FilaEstado(String deviceId, String cliente, String punto, String tipoMaquina, String zona, String accion) {
	}

	public record ResultadoEstado(boolean simulacion, List<FilaEstado> detalle, int marcadas) {
	}

	public record FilaZona(String deviceId, String punto, String zonaActual, String zonaNueva, String accion) {
	}

	public record ResultadoZonas(boolean simulacion, List<FilaZona> detalle, int actualizados) {
	}

	public record Maquina(int idMaquina, String deviceId, String cliente, String punto, String zona, String direccion,
		String tipoMaquina) {
	}

	/** Correcciones de nombres de tipo de máquina que llegan escritos de varias formas en el Excel de Prosegur. */
	private static final Map<String, String> TIPOS = Map.ofEntries(Map.entry("MACH 6", "MATCH-6"), Map.entry("MACH6", "MATCH-6"),
		Map.entry("SDM 500", "SDM-500"), Map.entry("SDM500", "SDM-500"), Map.entry("SDM 100", "SDM-100"), Map.entry("SDM100", "SDM-100"),
		Map.entry("SDM 10", "SDM-10"), Map.entry("SDM10", "SDM-10"), Map.entry("SDM 10 XS", "SDM-10-XS"), Map.entry("SDM 10XS", "SDM-10-XS"),
		Map.entry("SDM10 XS", "SDM-10-XS"), Map.entry("SDM-10XS", "SDM-10-XS"), Map.entry("SDM- 10X", "SDM-10-XS"),
		Map.entry("SDM 10-XS", "SDM-10-XS"), Map.entry("SDM-10 XS", "SDM-10-XS"), Map.entry("KD-30 SDM 500", "KD30-SDM500"),
		Map.entry("KD30 SDM 500", "KD30-SDM500"), Map.entry("KD30-500", "KD30-SDM500"), Map.entry("KD-30-SDM500", "KD30-SDM500"),
		Map.entry("KD30-SDM 100", "KD30-SDM100"), Map.entry("KD30 SDM 100", "KD30-SDM100"), Map.entry("KD30-100", "KD30-SDM100"),
		Map.entry("JH 600", "JH-600"), Map.entry("JH600", "JH-600"), Map.entry("JH 6000", "JH-6000"), Map.entry("JH6000", "JH-6000"),
		Map.entry("MINIMEI", "MINI MEI"), Map.entry("PS3000", "PS-3000"), Map.entry("SNBC", "PRO-EFECTIVO-X"),
		Map.entry("SNBC-SDM500", "PRO-EFECTIVO-X"), Map.entry("PRO EFECTIVO X", "PRO-EFECTIVO-X"),
		Map.entry("PROEFECTIVO X", "PRO-EFECTIVO-X"), Map.entry("DF 4", "DF4"), Map.entry("LOGY SAFE", "LOGY-SAFE"));

	private final JdbcClient jdbc;

	public ImportarController(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	// ================================================================== base de máquinas de Prosegur
	/**
	 * Excel de la base de Prosegur: A código cliente, B cliente, C device id, D código 1, E código 2, F punto,
	 * I delegación, K tipo de máquina, AK dirección. Al confirmar se crean o actualizan clientes, puntos y máquinas, y
	 * se desactivan las máquinas y puntos activos que ya no vienen en el archivo.
	 */
	@PostMapping(value = "/base-prosegur", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	@Transactional
	public ResultadoBase basePro(@RequestPart("archivo") MultipartFile archivo, @RequestParam(defaultValue = "true") boolean simular) {
		List<Map<String, String>> filas = leer(archivo, List.of("A", "B", "C", "D", "E", "F", "I", "K", "AK"));
		LocalDateTime inicio = LocalDateTime.now().withNano(0).minusSeconds(1);
		List<FilaBase> detalle = new ArrayList<>();
		List<String> mensajes = new ArrayList<>();
		Set<String> devices = new LinkedHashSet<>();
		int nuevas = 0, actualizadas = 0, errores = 0, n = 1;
		for (Map<String, String> f : filas) {
			n++;
			String device = f.get("C");
			if (device.isEmpty()) {
				continue;
			}
			devices.add(device);
			record Actual(String cliente, String punto) {
			}
			Actual actual = jdbc.sql("""
				SELECT c.nombre_cliente AS cliente, p.nombre_punto AS punto FROM maquina m JOIN punto p ON p.id_punto = m.id_punto
				JOIN cliente c ON c.id_cliente = p.id_cliente WHERE m.device_id = :d""").param("d", device).query(Actual.class)
				.optional().orElse(null);
			String accion = actual == null ? "NUEVO" : "ACTUALIZAR";
			if (actual == null) {
				nuevas++;
			}
			else {
				actualizadas++;
			}
			detalle.add(new FilaBase(n, device, f.get("B"), f.get("F"), accion, actual == null ? null : actual.cliente(),
				actual == null ? null : actual.punto(), f.get("K"), f.get("I")));
			if (simular) {
				continue;
			}
			try {
				int idCliente = cliente(f.get("B"), f.get("A"));
				Integer idDelegacion = jdbc.sql("SELECT id_delegacion FROM delegacion WHERE nombre_delegacion LIKE :n LIMIT 1")
					.param("n", f.get("I").toUpperCase(Locale.ROOT)).query(Integer.class).optional().orElse(null);
				int idPunto = punto(f.get("F"), idCliente, f.get("D"), f.get("E"), idDelegacion, f.get("AK"));
				int idTipo = tipoMaquina(f.get("K"));
				int existe = jdbc.sql("UPDATE maquina SET id_punto = :p, id_tipo_maquina = :t, fecha_actualizacion = NOW(), estado = 1 WHERE device_id = :d")
					.param("p", idPunto).param("t", idTipo).param("d", device).update();
				if (existe == 0) {
					jdbc.sql("INSERT INTO maquina (device_id, id_punto, id_tipo_maquina, estado, activo_operativo, fecha_actualizacion) VALUES (:d, :p, :t, 1, 1, NOW())")
						.param("d", device).param("p", idPunto).param("t", idTipo).update();
				}
			}
			catch (RuntimeException e) {
				errores++;
				mensajes.add("Fila " + n + " (" + device + "): " + e.getMessage());
			}
		}
		List<FilaBase> fantasmas = new ArrayList<>();
		int maqBaja = 0, ptoBaja = 0;
		if (simular) {
			Set<String> enArchivo = new HashSet<>(devices);
			jdbc.sql("""
				SELECT m.device_id, c.nombre_cliente, p.nombre_punto FROM maquina m JOIN punto p ON p.id_punto = m.id_punto
				JOIN cliente c ON c.id_cliente = p.id_cliente WHERE m.estado = 1""")
				.query((rs, i) -> {
					if (!enArchivo.contains(rs.getString(1))) {
						fantasmas.add(new FilaBase(0, rs.getString(1), rs.getString(2), rs.getString(3), "DESACTIVAR", null, null, null, null));
					}
					return null;
				}).list();
		}
		else if (!devices.isEmpty()) {
			maqBaja = jdbc.sql("UPDATE maquina SET estado = 0 WHERE estado = 1 AND (fecha_actualizacion < :f OR fecha_actualizacion IS NULL)")
				.param("f", inicio).update();
			ptoBaja = jdbc.sql("UPDATE punto SET estado = 0 WHERE estado = 1 AND (fecha_actualizacion < :f OR fecha_actualizacion IS NULL)")
				.param("f", inicio).update();
		}
		return new ResultadoBase(simular, detalle.size(), nuevas, actualizadas, errores, detalle, fantasmas, maqBaja, ptoBaja, mensajes);
	}

	private int cliente(String nombre, String codigo) {
		if (!codigo.isEmpty()) {
			Integer id = jdbc.sql("SELECT id_cliente FROM cliente WHERE codigo_cliente = :c LIMIT 1").param("c", codigo)
				.query(Integer.class).optional().orElse(null);
			if (id != null) {
				return id;
			}
		}
		Integer id = jdbc.sql("SELECT id_cliente FROM cliente WHERE nombre_cliente = :n LIMIT 1").param("n", nombre)
			.query(Integer.class).optional().orElse(null);
		if (id != null) {
			return id;
		}
		var llave = new GeneratedKeyHolder();
		jdbc.sql("INSERT INTO cliente (nombre_cliente, codigo_cliente, estado) VALUES (:n, :c, 1)")
			.param("n", nombre).param("c", codigo.isEmpty() ? null : codigo).update(llave);
		return llave.getKey().intValue();
	}

	private int punto(String nombre, int idCliente, String cod1, String cod2, Integer idDelegacion, String direccion) {
		if (!cod1.isEmpty()) {
			Integer id = jdbc.sql("SELECT id_punto FROM punto WHERE codigo_1 = :c AND id_cliente = :cli LIMIT 1")
				.param("c", cod1).param("cli", idCliente).query(Integer.class).optional().orElse(null);
			if (id != null) {
				jdbc.sql("UPDATE punto SET nombre_punto = :n, id_delegacion = :d, estado = 1, fecha_actualizacion = NOW() WHERE id_punto = :id")
					.param("n", nombre).param("d", idDelegacion).param("id", id).update();
				return id;
			}
		}
		Integer id = jdbc.sql("SELECT id_punto FROM punto WHERE nombre_punto = :n AND id_cliente = :cli LIMIT 1")
			.param("n", nombre).param("cli", idCliente).query(Integer.class).optional().orElse(null);
		if (id != null) {
			jdbc.sql("UPDATE punto SET estado = 1, fecha_actualizacion = NOW() WHERE id_punto = :id").param("id", id).update();
			return id;
		}
		// Municipio: el primero de la delegación (en PHP quedaba siempre el municipio 1)
		Integer municipio = idDelegacion == null ? null : jdbc.sql("SELECT MIN(id_municipio) FROM municipio WHERE id_delegacion = :d")
			.param("d", idDelegacion).query(Integer.class).optional().orElse(null);
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO punto (nombre_punto, codigo_1, codigo_2, id_municipio, id_delegacion, id_cliente, direccion, estado, id_modalidad,
			                   fecha_actualizacion)
			VALUES (:n, :c1, :c2, :mu, :d, :cli, :dir, 1, 1, NOW())""")
			.param("n", nombre).param("c1", cod1).param("c2", cod2).param("mu", municipio == null ? 1 : municipio)
			.param("d", idDelegacion).param("cli", idCliente).param("dir", direccion).update(llave);
		return llave.getKey().intValue();
	}

	private int tipoMaquina(String nombre) {
		String n = nombre.trim().toUpperCase(Locale.ROOT);
		n = TIPOS.getOrDefault(n, n);
		Integer id = jdbc.sql("SELECT id_tipo_maquina FROM tipo_maquina WHERE UPPER(nombre_tipo_maquina) = :n LIMIT 1").param("n", n)
			.query(Integer.class).optional().orElse(null);
		if (id != null) {
			return id;
		}
		if (n.isEmpty()) {
			throw new NegocioException("La fila no trae tipo de máquina.");
		}
		var llave = new GeneratedKeyHolder();
		jdbc.sql("INSERT INTO tipo_maquina (nombre_tipo_maquina, estado) VALUES (:n, 1)").param("n", n).update(llave);
		return llave.getKey().intValue();
	}

	// ================================================================== máquinas fuera de servicio
	/** Excel con los device id (columna A) de las máquinas que Prosegur reporta fuera de servicio. */
	@PostMapping(value = "/estado-maquinas", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	@Transactional
	public ResultadoEstado estadoMaquinas(@RequestPart("archivo") MultipartFile archivo, @RequestParam(defaultValue = "true") boolean simular,
		@RequestParam(required = false) List<String> aprobados) {
		List<FilaEstado> detalle = new ArrayList<>();
		Set<String> vistos = new HashSet<>();
		int marcadas = 0;
		for (Map<String, String> f : leer(archivo, List.of("A"))) {
			String device = f.get("A");
			if (device.isEmpty() || !vistos.add(device)) {
				continue;
			}
			record M(String cliente, String punto, String tipo, String zona, boolean operativa) {
			}
			M m = jdbc.sql("""
				SELECT c.nombre_cliente AS cliente, p.nombre_punto AS punto, tm.nombre_tipo_maquina AS tipo, p.zona, m.activo_operativo = 1 AS operativa
				FROM maquina m JOIN punto p ON p.id_punto = m.id_punto JOIN cliente c ON c.id_cliente = p.id_cliente
				JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina WHERE m.device_id = :d AND m.estado = 1""")
				.param("d", device).query(M.class).optional().orElse(null);
			String accion = m == null ? "NO_ENCONTRADA" : m.operativa() ? "MARCAR_FUERA_DE_SERVICIO" : "YA_INACTIVA";
			detalle.add(new FilaEstado(device, m == null ? null : m.cliente(), m == null ? null : m.punto(), m == null ? null : m.tipo(),
				m == null ? null : m.zona(), accion));
			if (!simular && m != null && m.operativa() && (aprobados == null || aprobados.contains(device))) {
				marcadas += jdbc.sql("UPDATE maquina SET activo_operativo = 0, fecha_actualizacion = NOW() WHERE device_id = :d")
					.param("d", device).update();
			}
		}
		return new ResultadoEstado(simular, detalle, marcadas);
	}

	@GetMapping("/fuera-de-servicio")
	public List<Maquina> fueraDeServicio() {
		return jdbc.sql("""
			SELECT m.id_maquina, m.device_id, c.nombre_cliente AS cliente, p.nombre_punto AS punto, p.zona, p.direccion,
			       tm.nombre_tipo_maquina AS tipo_maquina
			FROM maquina m JOIN punto p ON p.id_punto = m.id_punto JOIN cliente c ON c.id_cliente = p.id_cliente
			JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina
			WHERE m.activo_operativo = 0 AND m.estado = 1 AND p.estado = 1 ORDER BY p.zona, c.nombre_cliente""").query(Maquina.class).list();
	}

	@PostMapping("/fuera-de-servicio/{id}/restaurar")
	public void restaurar(@PathVariable int id) {
		jdbc.sql("UPDATE maquina SET activo_operativo = 1, fecha_actualizacion = NOW() WHERE id_maquina = :id").param("id", id).update();
	}

	@PostMapping("/fuera-de-servicio/restaurar")
	public int restaurarVarias(@RequestBody List<Integer> ids) {
		if (ids.isEmpty()) {
			return 0;
		}
		return jdbc.sql("UPDATE maquina SET activo_operativo = 1, fecha_actualizacion = NOW() WHERE id_maquina IN (:ids)")
			.param("ids", ids).update();
	}

	// ================================================================== zonas de los puntos
	/** Excel de la programación: C device id, F zona. Actualiza la zona del punto de cada máquina. */
	@PostMapping(value = "/zonas", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	@Transactional
	public ResultadoZonas zonas(@RequestPart("archivo") MultipartFile archivo, @RequestParam(defaultValue = "true") boolean simular) {
		List<FilaZona> detalle = new ArrayList<>();
		int actualizados = 0;
		Set<Integer> puntos = new HashSet<>();
		for (Map<String, String> f : leer(archivo, List.of("C", "F"))) {
			String device = f.get("C");
			if (device.isEmpty()) {
				continue;
			}
			String zona = f.get("F").isEmpty() ? null : f.get("F").toUpperCase(Locale.ROOT);
			record P(int idPunto, String punto, String zona) {
			}
			P p = jdbc.sql("SELECT p.id_punto, p.nombre_punto AS punto, p.zona FROM maquina m JOIN punto p ON p.id_punto = m.id_punto WHERE m.device_id = :d")
				.param("d", device).query(P.class).optional().orElse(null);
			if (p == null) {
				detalle.add(new FilaZona(device, null, null, zona, "NO_ENCONTRADA"));
				continue;
			}
			boolean igual = java.util.Objects.equals(p.zona(), zona);
			detalle.add(new FilaZona(device, p.punto(), p.zona(), zona, igual ? "SIN_CAMBIO" : "ACTUALIZAR"));
			if (!simular && !igual && puntos.add(p.idPunto())) {
				actualizados += jdbc.sql("UPDATE punto SET zona = :z, fecha_actualizacion = NOW() WHERE id_punto = :id")
					.param("z", zona).param("id", p.idPunto()).update();
			}
		}
		return new ResultadoZonas(simular, detalle, actualizados);
	}

	// ------------------------------------------------------------------ lectura de Excel por letra de columna
	/** Lee la primera hoja desde la fila 2; cada fila es columna → texto (sin espacios sobrantes). */
	static List<Map<String, String>> leer(MultipartFile archivo, List<String> columnas) {
		if (archivo == null || archivo.isEmpty()) {
			throw new NegocioException("Adjunte el archivo de Excel.");
		}
		try (InputStream in = archivo.getInputStream(); Workbook libro = WorkbookFactory.create(in)) {
			Sheet hoja = libro.getSheetAt(0);
			DataFormatter formato = new DataFormatter(Locale.ROOT);
			List<Map<String, String>> filas = new ArrayList<>();
			for (Row r : hoja) {
				if (r.getRowNum() == 0) {
					continue;
				}
				Map<String, String> f = new java.util.HashMap<>();
				boolean vacia = true;
				for (String col : columnas) {
					Cell c = r.getCell(CellReference.convertColStringToIndex(col));
					String v = c == null ? "" : formato.formatCellValue(c).replace(' ', ' ').trim();
					f.put(col, v);
					vacia &= v.isEmpty();
				}
				if (!vacia) {
					filas.add(f);
				}
			}
			return filas;
		}
		catch (IOException | RuntimeException e) {
			if (e instanceof NegocioException ne) {
				throw ne;
			}
			throw new NegocioException("No se pudo leer el archivo. Use un Excel .xlsx o .xls.");
		}
	}
}
