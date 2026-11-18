package co.inees.plataforma.seguimiento;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.util.List;

import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.comun.Parametros;

/** Avance de las rutas del día y alertas para el Supervisor. */
@RestController
@RequestMapping("/api/seguimiento")
public class SeguimientoController {

	public record AvanceRuta(int idRuta, int idTecnico, String tecnico, String estado, LocalTime inicioJornada,
		int total, int hechas, int noIngreso, int pendientes, Integer ordenActual, String puntoActual, String etapaActual,
		LocalDateTime ultimoEvento, Double ultimaLatitud, Double ultimaLongitud) {
	}

	public record Alerta(String tipo, String tecnico, Integer idOrden, String punto, String detalle, LocalDateTime desde) {
	}

	public record Tablero(LocalDate fecha, List<AvanceRuta> rutas, List<Alerta> alertas) {
	}

	private final JdbcClient jdbc;
	private final Parametros parametros;

	public SeguimientoController(JdbcClient jdbc, Parametros parametros) {
		this.jdbc = jdbc;
		this.parametros = parametros;
	}

	@GetMapping
	public Tablero tablero(@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate fecha,
		@RequestParam int idDelegacion) {
		LocalDate dia = fecha == null ? LocalDate.now() : fecha;
		List<AvanceRuta> rutas = jdbc.sql("""
			SELECT r.id_ruta, r.id_tecnico, t.nombre_tecnico AS tecnico, r.estado,
			       (SELECT tt.hora_entrada FROM turno_tecnico tt WHERE tt.id_tecnico = r.id_tecnico AND tt.fecha = r.fecha LIMIT 1) AS inicio_jornada,
			       COUNT(rp.id_parada) AS total,
			       SUM(rp.estado = 'HECHA') AS hechas, SUM(rp.estado = 'NO_INGRESO') AS no_ingreso,
			       SUM(rp.estado = 'PENDIENTE') AS pendientes,
			       act.id_ordenes_servicio AS orden_actual, pa.nombre_punto AS punto_actual, act.etapa_actual,
			       ev.fecha_hora AS ultimo_evento, ev.latitud AS ultima_latitud, ev.longitud AS ultima_longitud
			FROM ruta_diaria r
			JOIN tecnico t ON t.id_tecnico = r.id_tecnico
			LEFT JOIN ruta_parada rp ON rp.id_ruta = r.id_ruta
			LEFT JOIN ordenes_servicio act ON act.id_tecnico = r.id_tecnico AND act.estado IN (4, 5, 6)
			LEFT JOIN punto pa ON pa.id_punto = act.id_punto
			LEFT JOIN orden_servicio_evento ev ON ev.id_evento = (
			     SELECT e.id_evento FROM orden_servicio_evento e JOIN ordenes_servicio o ON o.id_ordenes_servicio = e.id_orden_servicio
			      WHERE o.id_tecnico = r.id_tecnico AND DATE(e.fecha_hora) = r.fecha ORDER BY e.fecha_hora DESC LIMIT 1)
			WHERE r.fecha = :fecha AND t.id_delegacion = :del AND r.estado <> 'BORRADOR'
			GROUP BY r.id_ruta ORDER BY t.nombre_tecnico""")
			.param("fecha", dia).param("del", idDelegacion).query(AvanceRuta.class).list();

		int espera = parametros.entero(Parametros.ESPERA_MAX, 45);
		List<Alerta> alertas = jdbc.sql("""
			SELECT 'ESPERA' AS tipo, t.nombre_tecnico AS tecnico, os.id_ordenes_servicio AS id_orden, p.nombre_punto AS punto,
			       CONCAT('Esperando ingreso hace ', TIMESTAMPDIFF(MINUTE, os.hora_llegada, NOW()), ' min') AS detalle,
			       os.hora_llegada AS desde
			FROM ordenes_servicio os JOIN tecnico t ON t.id_tecnico = os.id_tecnico JOIN punto p ON p.id_punto = os.id_punto
			WHERE os.estado = 5 AND t.id_delegacion = :del AND TIMESTAMPDIFF(MINUTE, os.hora_llegada, NOW()) >= :espera
			UNION ALL
			SELECT 'SERIALES', t.nombre_tecnico, os.id_ordenes_servicio, p.nombre_punto, c.detalle_diferencia_serial, os.hora_ingreso
			FROM ordenes_servicio os JOIN tecnico t ON t.id_tecnico = os.id_tecnico JOIN punto p ON p.id_punto = os.id_punto
			JOIN ordenes_servicio_complemento c ON c.id_orden_servicio = os.id_ordenes_servicio
			WHERE c.seriales_coinciden = 0 AND os.fecha_visita = :fecha AND t.id_delegacion = :del
			UNION ALL
			SELECT 'LEJOS_DEL_PUNTO', t.nombre_tecnico, os.id_ordenes_servicio, p.nombre_punto,
			       JSON_UNQUOTE(JSON_EXTRACT(e.datos, '$.avisos[0]')), e.fecha_hora
			FROM orden_servicio_evento e JOIN ordenes_servicio os ON os.id_ordenes_servicio = e.id_orden_servicio
			JOIN tecnico t ON t.id_tecnico = os.id_tecnico JOIN punto p ON p.id_punto = os.id_punto
			WHERE e.etapa = 'LLEGADA' AND DATE(e.fecha_hora) = :fecha AND t.id_delegacion = :del
			  AND JSON_EXTRACT(e.datos, '$.avisos[0]') IS NOT NULL
			UNION ALL
			SELECT 'SIN_REPORTE', t.nombre_tecnico, os.id_ordenes_servicio, p.nombre_punto,
			       CONCAT('Salió hacia el punto hace ', TIMESTAMPDIFF(MINUTE, os.hora_inicio_trayecto, NOW()), ' min y no ha reportado llegada'),
			       os.hora_inicio_trayecto
			FROM ordenes_servicio os JOIN tecnico t ON t.id_tecnico = os.id_tecnico JOIN punto p ON p.id_punto = os.id_punto
			WHERE os.estado = 4 AND t.id_delegacion = :del AND TIMESTAMPDIFF(MINUTE, os.hora_inicio_trayecto, NOW()) > 90
			ORDER BY desde DESC""")
			.param("del", idDelegacion).param("fecha", dia).param("espera", espera).query(Alerta.class).list();
		return new Tablero(dia, rutas, alertas);
	}
}
