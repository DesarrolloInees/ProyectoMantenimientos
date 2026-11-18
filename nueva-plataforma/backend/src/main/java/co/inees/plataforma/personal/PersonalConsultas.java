package co.inees.plataforma.personal;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.util.Arrays;
import java.util.List;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.stereotype.Component;

import co.inees.plataforma.comun.Filtro;
import co.inees.plataforma.comun.Parametros;

/** Consultas de horas extra, parqueaderos, jornadas y tiempos de servicio (las usan el técnico y la oficina). */
@Component
public class PersonalConsultas {

	public record Foto(int id, String ruta) {
	}

	public record HoraExtra(int id, LocalDate fecha, LocalTime horaInicio, LocalTime horaFin, BigDecimal totalHoras,
		String justificacion, String observacionSupervisor, int idEstado, String estado, int idTecnico, String tecnico,
		Integer idCliente, String cliente, Integer idPunto, String punto, LocalDateTime registrado, String fotosTexto,
		List<Foto> fotos) {

		HoraExtra conFotos() {
			List<Foto> f = fotosTexto == null || fotosTexto.isBlank() ? List.of()
				: Arrays.stream(fotosTexto.split("\\|\\|")).map(x -> x.split("::", 2))
					.map(p -> new Foto(Integer.parseInt(p[0]), p[1])).toList();
			return new HoraExtra(id, fecha, horaInicio, horaFin, totalHoras, justificacion, observacionSupervisor, idEstado,
				estado, idTecnico, tecnico, idCliente, cliente, idPunto, punto, registrado, null, f);
		}
	}

	public record Parqueadero(int id, LocalDate fecha, LocalTime horaInicio, LocalTime horaFin, String numeroFactura,
		BigDecimal valor, String rutaFoto, int idTecnico, String tecnico, int idPunto, String punto, LocalDateTime registrado) {
	}

	public record Jornada(int id, LocalDate fecha, LocalTime horaEntrada, LocalTime horaSalida, String novedad,
		Double latitud, Double longitud, LocalDateTime registrado, int idTecnico, String tecnico, String codigoRuta,
		Integer servicios) {
	}

	/** Duración real de cada servicio: los del flujo nuevo (ingreso → cierre) y los del cronómetro anterior. */
	public record Tiempo(String fuente, int id, LocalDate fecha, String tecnico, String cliente, String punto,
		String tipoMantenimiento, Integer idTipoMantenimiento, LocalDateTime inicio, LocalDateTime fin, Integer minutos,
		Integer estimado, String estado, String justificacion) {
	}

	private final JdbcClient jdbc;
	private final Parametros parametros;

	public PersonalConsultas(JdbcClient jdbc, Parametros parametros) {
		this.jdbc = jdbc;
		this.parametros = parametros;
	}

	public List<HoraExtra> horasExtra(LocalDate desde, LocalDate hasta, Integer idTecnico, Integer idEstado) {
		Filtro f = new Filtro().y("h.fecha_reporte >= :d", "d", desde).y("h.fecha_reporte <= :h", "h", hasta)
			.y("h.id_tecnico = :t", "t", idTecnico).y("h.id_estado_aprobacion = :e", "e", idEstado);
		return jdbc.sql("""
			SELECT h.id_registro_he AS id, h.fecha_reporte AS fecha, h.hora_inicio, h.hora_fin, h.total_horas,
			       h.justificacion_tecnico AS justificacion, h.observacion_supervisor, h.id_estado_aprobacion AS id_estado,
			       e.nombre_estado AS estado, h.id_tecnico, t.nombre_tecnico AS tecnico, h.id_cliente, c.nombre_cliente AS cliente,
			       h.id_punto, p.nombre_punto AS punto, h.fecha_registro AS registrado,
			       (SELECT GROUP_CONCAT(CONCAT(x.id_evidencia, '::', x.ruta_archivo) SEPARATOR '||') FROM evidencia_horas_extra x
			         WHERE x.id_registro_he = h.id_registro_he) AS fotos_texto, NULL AS fotos
			FROM registro_horas_extra h JOIN tecnico t ON t.id_tecnico = h.id_tecnico
			JOIN estados_aprobacion_he e ON e.id_estado = h.id_estado_aprobacion
			LEFT JOIN cliente c ON c.id_cliente = h.id_cliente LEFT JOIN punto p ON p.id_punto = h.id_punto
			WHERE """ + " " + f.where() + " ORDER BY h.fecha_reporte DESC, h.id_registro_he DESC").params(f.params())
			.query(HoraExtra.class).list().stream().map(HoraExtra::conFotos).toList();
	}

	public List<Parqueadero> parqueaderos(LocalDate desde, LocalDate hasta, Integer idTecnico) {
		Filtro f = new Filtro().y("fp.estado = 1").y("fp.fecha_servicio >= :d", "d", desde)
			.y("fp.fecha_servicio <= :h", "h", hasta).y("fp.id_tecnico = :t", "t", idTecnico);
		return jdbc.sql("""
			SELECT fp.id_factura_parqueadero AS id, fp.fecha_servicio AS fecha, fp.hora_inicio, fp.hora_fin, fp.numero_factura,
			       fp.valor_factura AS valor, NULLIF(fp.ruta_foto, '') AS ruta_foto, fp.id_tecnico, t.nombre_tecnico AS tecnico,
			       fp.id_punto, p.nombre_punto AS punto, fp.fecha_registro AS registrado
			FROM facturas_parqueadero fp JOIN tecnico t ON t.id_tecnico = fp.id_tecnico JOIN punto p ON p.id_punto = fp.id_punto
			WHERE """ + " " + f.where() + " ORDER BY fp.fecha_servicio DESC, fp.id_factura_parqueadero DESC").params(f.params())
			.query(Parqueadero.class).list();
	}

	public List<Jornada> jornadas(LocalDate desde, LocalDate hasta, Integer idTecnico) {
		Filtro f = new Filtro().y("tt.estado = 1").y("tt.fecha >= :d", "d", desde).y("tt.fecha <= :h", "h", hasta)
			.y("tt.id_tecnico = :t", "t", idTecnico);
		return jdbc.sql("""
			SELECT tt.id_turno AS id, tt.fecha, tt.hora_entrada, tt.hora_salida, tt.novedad, tt.latitud, tt.longitud,
			       tt.fecha_registro AS registrado, tt.id_tecnico, t.nombre_tecnico AS tecnico, t.codigo_ruta,
			       (SELECT COUNT(*) FROM ordenes_servicio o WHERE o.id_tecnico = tt.id_tecnico AND o.fecha_visita = tt.fecha
			           AND o.estado = 1) AS servicios
			FROM turno_tecnico tt JOIN tecnico t ON t.id_tecnico = tt.id_tecnico
			WHERE """ + " " + f.where() + " ORDER BY tt.fecha DESC, t.nombre_tecnico").params(f.params()).query(Jornada.class).list();
	}

	public List<Tiempo> tiempos(LocalDate desde, LocalDate hasta, Integer idTecnico) {
		var estimados = parametros.tiemposMantenimiento();
		List<Tiempo> filas = jdbc.sql("""
			SELECT * FROM (
			  SELECT 'SERVICIO' AS fuente, o.id_ordenes_servicio AS id, o.fecha_visita AS fecha, t.nombre_tecnico AS tecnico,
			         c.nombre_cliente AS cliente, p.nombre_punto AS punto, tm.nombre_completo AS tipo_mantenimiento,
			         o.id_tipo_mantenimiento, COALESCE(o.hora_ingreso, TIMESTAMP(o.fecha_visita, o.hora_entrada)) AS inicio,
			         IF(o.estado = 1, TIMESTAMP(o.fecha_visita, o.hora_salida), NULL) AS fin,
			         IF(o.estado = 1, o.minutos_servicio, TIMESTAMPDIFF(MINUTE, o.hora_ingreso, NOW())) AS minutos,
			         NULL AS estimado, IF(o.estado = 1, 'Finalizado', 'En progreso') AS estado, NULL AS justificacion, o.id_tecnico
			  FROM ordenes_servicio o JOIN tecnico t ON t.id_tecnico = o.id_tecnico JOIN punto p ON p.id_punto = o.id_punto
			  JOIN cliente c ON c.id_cliente = p.id_cliente LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			  WHERE o.fecha_visita BETWEEN :d AND :h AND (o.estado = 1 AND o.hora_entrada IS NOT NULL OR o.estado = 6)
			  UNION ALL
			  SELECT 'CRONOMETRO', ml.id_monitoreo, ml.fecha_servicio, t.nombre_tecnico, c.nombre_cliente, p.nombre_punto,
			         tm.nombre_completo, ml.id_tipo_mantenimiento, ml.hora_inicio, ml.hora_fin,
			         COALESCE(ml.duracion_real_minutos, TIMESTAMPDIFF(MINUTE, ml.hora_inicio, COALESCE(ml.hora_fin, NOW()))),
			         NULL, ml.estado_actual, ml.justificacion_retraso, ml.id_tecnico
			  FROM monitoreo_motorizados_live ml JOIN tecnico t ON t.id_tecnico = ml.id_tecnico JOIN punto p ON p.id_punto = ml.id_punto
			  JOIN cliente c ON c.id_cliente = ml.id_cliente JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = ml.id_tipo_mantenimiento
			  WHERE ml.fecha_servicio BETWEEN :d AND :h
			) x WHERE (:t IS NULL OR x.id_tecnico = :t) ORDER BY x.fecha DESC, x.inicio DESC""")
			.param("d", desde).param("h", hasta).param("t", idTecnico)
			.query((rs, i) -> new Tiempo(rs.getString("fuente"), rs.getInt("id"), rs.getObject("fecha", LocalDate.class),
				rs.getString("tecnico"), rs.getString("cliente"), rs.getString("punto"), rs.getString("tipo_mantenimiento"),
				(Integer) rs.getObject("id_tipo_mantenimiento"), rs.getObject("inicio", LocalDateTime.class),
				rs.getObject("fin", LocalDateTime.class), rs.getObject("minutos") == null ? null : rs.getInt("minutos"), null,
				rs.getString("estado"), rs.getString("justificacion")))
			.list();
		return filas.stream().map(t -> new Tiempo(t.fuente(), t.id(), t.fecha(), t.tecnico(), t.cliente(), t.punto(),
			t.tipoMantenimiento(), t.idTipoMantenimiento(), t.inicio(), t.fin(), t.minutos(),
			t.idTipoMantenimiento() == null ? null : estimados.getOrDefault(t.idTipoMantenimiento(), 45), t.estado(),
			t.justificacion())).toList();
	}
}
