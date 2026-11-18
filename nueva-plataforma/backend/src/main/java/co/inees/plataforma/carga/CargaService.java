package co.inees.plataforma.carga;

import java.io.IOException;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.comun.Historial;
import co.inees.plataforma.comun.Parametros;
import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.seguridad.Sesion;

@Service
public class CargaService {

	public record Resumen(int idCarga, String nombreArchivo, LocalDate fechaServicio, String usuario,
		LocalDateTime fechaCarga, int totalFilas, int filasOk, int filasError, String estado, int sinUbicacion) {
	}

	public record FilaSolicitud(int idSolicitud, int filaExcel, String deviceId, String referenciaExterna,
		String tipoServicioTexto, String descripcion, String estado, String mensajeError, Integer idMaquina,
		String punto, String cliente, String municipio, String tipoMantenimiento, Boolean ubicada,
		Integer idOrdenServicio) {
	}

	public record Detalle(Resumen resumen, Map<String, String> columnasReconocidas, List<FilaSolicitud> filas) {
	}

	private final JdbcClient jdbc;
	private final Parametros parametros;
	private final Historial historial;

	public CargaService(JdbcClient jdbc, Parametros parametros, Historial historial) {
		this.jdbc = jdbc;
		this.parametros = parametros;
		this.historial = historial;
	}

	@Transactional
	public Detalle cargar(MultipartFile archivo, LocalDate fechaServicio) {
		if (archivo == null || archivo.isEmpty()) {
			throw new NegocioException("Seleccione el archivo de Excel de Prosegur.");
		}
		if (fechaServicio == null || fechaServicio.isBefore(LocalDate.now())) {
			throw new NegocioException("La fecha de los servicios debe ser hoy o un día futuro.");
		}
		LectorExcel.Resultado leido;
		try {
			leido = new LectorExcel(parametros.texto(Parametros.COLUMNAS_EXCEL, "{}")).leer(archivo.getInputStream());
		}
		catch (IOException e) {
			throw new NegocioException("No se pudo leer el archivo.");
		}
		if (leido.filas().isEmpty()) {
			throw new NegocioException("El archivo no tiene filas con servicios.");
		}

		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO carga_solicitud (nombre_archivo, fecha_servicio, id_usuario, total_filas)
			VALUES (:nombre, :fecha, :usuario, :total)""")
			.param("nombre", archivo.getOriginalFilename() == null ? "archivo.xlsx" : archivo.getOriginalFilename())
			.param("fecha", fechaServicio).param("usuario", Sesion.actual().usuarioId())
			.param("total", leido.filas().size())
			.update(llave);
		int idCarga = llave.getKey().intValue();

		int tipoPorDefecto = parametros.entero(Parametros.TIPO_SOLICITUD, 3);
		Map<Integer, Integer> maquinasVistas = new HashMap<>();
		int ok = 0, errores = 0;
		for (LectorExcel.Fila f : leido.filas()) {
			Integer idMaquina = null;
			String error = null;
			if (f.deviceId() == null) {
				error = "La fila no tiene ID de máquina.";
			}
			else {
				List<Integer> ids = jdbc.sql("""
					SELECT id_maquina FROM maquina
					WHERE estado = 1 AND (device_id = :d OR TRIM(LEADING '0' FROM device_id) = TRIM(LEADING '0' FROM :d))
					LIMIT 2""").param("d", f.deviceId()).query(Integer.class).list();
				if (ids.isEmpty()) {
					error = "La máquina " + f.deviceId() + " no existe en la base. Importe primero la base de Prosegur.";
				}
				else if (ids.size() > 1) {
					error = "Hay más de una máquina con el ID " + f.deviceId() + ".";
				}
				else if (maquinasVistas.containsKey(ids.get(0))) {
					error = "La máquina ya viene en la fila " + maquinasVistas.get(ids.get(0)) + ".";
				}
				else {
					idMaquina = ids.get(0);
					maquinasVistas.put(idMaquina, f.numero());
				}
			}
			jdbc.sql("""
				INSERT INTO solicitud_servicio (id_carga, fila_excel, device_id, referencia_externa, tipo_servicio_texto,
				       descripcion, id_maquina, id_tipo_mantenimiento, estado, mensaje_error, datos_originales)
				VALUES (:carga, :fila, :device, :ref, :tipoTexto, :desc, :maquina, :tipo, :estado, :error, :json)""")
				.param("carga", idCarga).param("fila", f.numero()).param("device", recortar(f.deviceId(), 30))
				.param("ref", recortar(f.referencia(), 60)).param("tipoTexto", recortar(f.tipo(), 100))
				.param("desc", f.descripcion()).param("maquina", idMaquina)
				.param("tipo", idMaquina == null ? null : tipoMantenimiento(f.tipo(), f.descripcion(), tipoPorDefecto))
				.param("estado", error == null ? "VALIDA" : "ERROR").param("error", error)
				.param("json", LectorExcel.json(f.original()))
				.update();
			if (error == null) {
				ok++;
			}
			else {
				errores++;
			}
		}
		jdbc.sql("UPDATE carga_solicitud SET filas_ok = :ok, filas_error = :err WHERE id_carga = :id")
			.param("ok", ok).param("err", errores).param("id", idCarga).update();
		return detalle(idCarga, leido.columnasReconocidas());
	}

	public List<Resumen> listar() {
		return jdbc.sql(SELECT_RESUMEN + " ORDER BY c.fecha_carga DESC LIMIT 60").query(Resumen.class).list();
	}

	public Detalle detalle(int idCarga) {
		return detalle(idCarga, null);
	}

	/**
	 * Convierte las filas válidas en órdenes de la bolsa con prioridad 1. Exige que todas las máquinas tengan
	 * ubicación: las que no la tienen se pinean antes en el mapa.
	 */
	@Transactional
	public Detalle confirmar(int idCarga) {
		Resumen r = resumen(idCarga);
		if (!"EN_REVISION".equals(r.estado())) {
			throw new NegocioException("Esta carga ya fue " + r.estado().toLowerCase().replace('_', ' ') + ".");
		}
		if (r.sinUbicacion() > 0) {
			throw new NegocioException("Faltan " + r.sinUbicacion() + " máquinas por ubicar en el mapa antes de confirmar.");
		}
		int usuario = Sesion.actual().usuarioId();
		record Valida(int idSolicitud, int idMaquina, int idTipoMantenimiento, int idPunto, int idCliente, int idModalidad) {
		}
		List<Valida> validas = jdbc.sql("""
			SELECT s.id_solicitud, s.id_maquina, s.id_tipo_mantenimiento, p.id_punto, p.id_cliente, p.id_modalidad
			FROM solicitud_servicio s
			JOIN maquina m ON m.id_maquina = s.id_maquina
			JOIN punto p ON p.id_punto = m.id_punto
			WHERE s.id_carga = :carga AND s.estado = 'VALIDA'""").param("carga", idCarga).query(Valida.class).list();

		for (Valida v : validas) {
			record Abierta(int idOrdenesServicio, int estado, String origen, int prioridad, LocalDate fechaVisita) {
			}
			Abierta abierta = jdbc.sql("""
				SELECT id_ordenes_servicio, estado, origen, prioridad, fecha_visita FROM ordenes_servicio
				WHERE id_maquina = :m AND estado IN (2, 3, 4, 5, 6) ORDER BY id_ordenes_servicio DESC LIMIT 1""")
				.param("m", v.idMaquina()).query(Abierta.class).optional().orElse(null);
			int idOrden;
			if (abierta != null) {
				idOrden = abierta.idOrdenesServicio();
				boolean sinEmpezar = abierta.estado() == 2 || abierta.estado() == 3;
				jdbc.sql("""
					UPDATE ordenes_servicio SET origen = 'SOLICITUD_PROSEGUR', prioridad = 1, id_solicitud = :sol,
					       id_tipo_mantenimiento = IF(:sinEmpezar, :tipo, id_tipo_mantenimiento)
					WHERE id_ordenes_servicio = :id""")
					.param("sol", v.idSolicitud()).param("sinEmpezar", sinEmpezar).param("tipo", v.idTipoMantenimiento())
					.param("id", idOrden).update();
				historial.registrar(idOrden, "origen", abierta.origen(), "SOLICITUD_PROSEGUR",
					"La pidió Prosegur en la carga " + idCarga, usuario);
			}
			else {
				var llave = new GeneratedKeyHolder();
				jdbc.sql("""
					INSERT INTO ordenes_servicio (id_cliente, id_punto, id_modalidad, id_maquina, id_tipo_mantenimiento,
					       fecha_visita, estado, origen, prioridad, id_solicitud, tiene_novedad, created_at)
					VALUES (:cliente, :punto, :modalidad, :maquina, :tipo, :fecha, 3, 'SOLICITUD_PROSEGUR', 1, :sol, 0, NOW())""")
					.param("cliente", v.idCliente()).param("punto", v.idPunto()).param("modalidad", v.idModalidad())
					.param("maquina", v.idMaquina()).param("tipo", v.idTipoMantenimiento())
					.param("fecha", r.fechaServicio()).param("sol", v.idSolicitud())
					.update(llave);
				idOrden = llave.getKey().intValue();
				historial.registrar(idOrden, "estado", null, "PENDIENTE", "Creada desde la carga " + idCarga, usuario);
			}
			jdbc.sql("UPDATE solicitud_servicio SET estado = 'ORDEN_GENERADA', id_orden_servicio = :o WHERE id_solicitud = :s")
				.param("o", idOrden).param("s", v.idSolicitud()).update();
		}
		jdbc.sql("UPDATE carga_solicitud SET estado = 'CONFIRMADA', fecha_confirmacion = NOW() WHERE id_carga = :id")
			.param("id", idCarga).update();
		return detalle(idCarga);
	}

	@Transactional
	public Detalle anular(int idCarga) {
		Resumen r = resumen(idCarga);
		if (!"EN_REVISION".equals(r.estado())) {
			throw new NegocioException("Solo se puede anular una carga que no se ha confirmado.");
		}
		jdbc.sql("UPDATE carga_solicitud SET estado = 'ANULADA' WHERE id_carga = :id").param("id", idCarga).update();
		jdbc.sql("UPDATE solicitud_servicio SET estado = 'DESCARTADA' WHERE id_carga = :id AND estado = 'VALIDA'")
			.param("id", idCarga).update();
		return detalle(idCarga);
	}

	/**
	 * Traduce el texto del Excel al tipo de mantenimiento. Primero mira la columna de tipo; si no hay o no se
	 * reconoce, mira los comentarios (el Excel de indisponibles de Prosegur no trae columna de tipo y lo dice en
	 * «COMENTARIOS», p. ej. «INEES MANTENIMIENTO PROFUNDO PILOTO»). Si tampoco, usa el parámetro.
	 */
	static int tipoMantenimiento(String tipo, String comentarios, int porDefecto) {
		Integer t = reconocerTipo(tipo);
		if (t == null) {
			t = reconocerTipo(comentarios);
		}
		return t == null ? porDefecto : t;
	}

	private static Integer reconocerTipo(String texto) {
		String t = LectorExcel.normalizar(texto);
		if (t.contains("correctivo")) {
			return 3;
		}
		if (t.contains("profundo")) {
			return 2;
		}
		if (t.contains("garantia")) {
			return 5;
		}
		if (t.contains("arquitectura")) {
			return 6;
		}
		if (t.contains("preventivo")) {
			return 1;
		}
		return null;
	}

	private static final String SELECT_RESUMEN = """
		SELECT c.id_carga, c.nombre_archivo, c.fecha_servicio, u.nombre AS usuario, c.fecha_carga, c.total_filas,
		       c.filas_ok, c.filas_error, c.estado,
		       (SELECT COUNT(*) FROM solicitud_servicio s JOIN maquina m ON m.id_maquina = s.id_maquina
		         WHERE s.id_carga = c.id_carga AND s.estado = 'VALIDA' AND m.latitud IS NULL) AS sin_ubicacion
		FROM carga_solicitud c JOIN usuarios u ON u.usuario_id = c.id_usuario
		""";

	private Resumen resumen(int idCarga) {
		return jdbc.sql(SELECT_RESUMEN + " WHERE c.id_carga = :id").param("id", idCarga).query(Resumen.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("La carga"));
	}

	private Detalle detalle(int idCarga, Map<String, String> columnas) {
		List<FilaSolicitud> filas = jdbc.sql("""
			SELECT s.id_solicitud, s.fila_excel, s.device_id, s.referencia_externa, s.tipo_servicio_texto, s.descripcion,
			       s.estado, s.mensaje_error, s.id_maquina, p.nombre_punto AS punto, c.nombre_cliente AS cliente,
			       mu.nombre_municipio AS municipio, tm.nombre_completo AS tipo_mantenimiento,
			       IF(m.id_maquina IS NULL, NULL, m.latitud IS NOT NULL) AS ubicada, s.id_orden_servicio
			FROM solicitud_servicio s
			LEFT JOIN maquina m ON m.id_maquina = s.id_maquina
			LEFT JOIN punto p ON p.id_punto = m.id_punto
			LEFT JOIN cliente c ON c.id_cliente = p.id_cliente
			LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
			LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = s.id_tipo_mantenimiento
			WHERE s.id_carga = :id ORDER BY s.estado = 'ERROR' DESC, s.fila_excel""")
			.param("id", idCarga).query(FilaSolicitud.class).list();
		return new Detalle(resumen(idCarga), columnas, filas);
	}

	private static String recortar(String s, int max) {
		return s == null || s.length() <= max ? s : s.substring(0, max);
	}
}
