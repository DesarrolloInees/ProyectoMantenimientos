package co.inees.plataforma.orden;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.stereotype.Component;

import co.inees.plataforma.comun.Filtro;

/**
 * Consulta única de servicios. En PHP cada pantalla repetía la misma consulta con COALESCE entre el punto de la
 * orden y el de la máquina; la base ya tiene punto y cliente en todas las órdenes, así que se usan joins directos.
 */
@Component
public class ServicioConsultas {

	public record Repuesto(int idRepuesto, String nombre, String codigo, String origen, int cantidad, BigDecimal valorUnitario) {
	}

	public record Servicio(int idOrden, String numeroRemision, LocalDate fechaVisita, LocalTime horaEntrada,
		LocalTime horaSalida, Integer minutosServicio, BigDecimal valorServicio, BigDecimal valorViaticos, Integer diasViaticos,
		boolean esFueraDelegacion, String actividades, boolean tieneNovedad, String detalleNovedad, String novedades,
		String idsNovedades, int idMaquina, String deviceId, Integer idTipoMaquina, String tipoMaquina, int idCliente,
		String cliente, int idPunto, String punto, Integer idDelegacion, String delegacion, Integer idModalidad,
		String modalidad, Integer idTecnico, String tecnico, Integer idTipoMantenimiento, String tipoMantenimiento,
		Integer idEstadoMaquina, String estadoMaquina, Integer idCalificacion, String calificacion, String repuestosTexto,
		int estado, String origen, String repuestosLegado, LocalDateTime creado, List<Repuesto> repuestos) {

		Servicio conRepuestos(List<Repuesto> r) {
			return new Servicio(idOrden, numeroRemision, fechaVisita, horaEntrada, horaSalida, minutosServicio,
				valorServicio, valorViaticos, diasViaticos, esFueraDelegacion, actividades, tieneNovedad, detalleNovedad,
				novedades, idsNovedades, idMaquina, deviceId, idTipoMaquina, tipoMaquina, idCliente, cliente, idPunto, punto,
				idDelegacion, delegacion, idModalidad, modalidad, idTecnico, tecnico, idTipoMantenimiento, tipoMantenimiento,
				idEstadoMaquina, estadoMaquina, idCalificacion, calificacion, repuestosTexto, estado, origen, repuestosLegado,
				creado, r);
		}
	}

	/** Filtros de búsqueda; los nulos no filtran. */
	public record Filtros(LocalDate desde, LocalDate hasta, Integer idCliente, Integer idPunto, Integer idTecnico,
		Integer idDelegacion, Integer idTipoMantenimiento, String remision, String deviceId, Boolean conNovedad,
		Integer estado) {

		public static Filtros dia(LocalDate fecha) {
			return new Filtros(fecha, fecha, null, null, null, null, null, null, null, null, 1);
		}
	}

	static final String SELECT = """
		SELECT o.id_ordenes_servicio AS id_orden, o.numero_remision, o.fecha_visita, o.hora_entrada, o.hora_salida,
		       o.minutos_servicio, o.valor_servicio, o.valor_viaticos, o.dias_viaticos, o.es_fuera_delegacion,
		       o.actividades_realizadas AS actividades, o.tiene_novedad, o.detalle_novedad,
		       (SELECT GROUP_CONCAT(tn.nombre_novedad ORDER BY tn.nombre_novedad SEPARATOR ', ')
		          FROM orden_servicio_novedad osn JOIN tipo_novedad tn ON tn.id_tipo_novedad = osn.id_tipo_novedad
		         WHERE osn.id_orden_servicio = o.id_ordenes_servicio) AS novedades,
		       (SELECT GROUP_CONCAT(osn.id_tipo_novedad) FROM orden_servicio_novedad osn
		         WHERE osn.id_orden_servicio = o.id_ordenes_servicio) AS ids_novedades,
		       o.id_maquina, m.device_id, m.id_tipo_maquina, tmq.nombre_tipo_maquina AS tipo_maquina,
		       p.id_cliente, c.nombre_cliente AS cliente, o.id_punto, p.nombre_punto AS punto,
		       d.id_delegacion, d.nombre_delegacion AS delegacion, o.id_modalidad, mo.nombre_modalidad AS modalidad,
		       o.id_tecnico, t.nombre_tecnico AS tecnico, o.id_tipo_mantenimiento, tm.nombre_completo AS tipo_mantenimiento,
		       o.id_estado_maquina, em.nombre_estado AS estado_maquina, o.id_calificacion, cal.nombre_calificacion AS calificacion,
		       (SELECT GROUP_CONCAT(CONCAT(r.nombre_repuesto, ' (', osr.origen, ')', IF(osr.cantidad > 1, CONCAT(' x', osr.cantidad), ''))
		               ORDER BY r.nombre_repuesto SEPARATOR ', ')
		          FROM orden_servicio_repuesto osr JOIN repuesto r ON r.id_repuesto = osr.id_repuesto
		         WHERE osr.id_orden_servicio = o.id_ordenes_servicio) AS repuestos_texto,
		       o.estado, o.origen, o.repuestos_tecnico AS repuestos_legado, o.created_at AS creado, NULL AS repuestos
		FROM ordenes_servicio o
		JOIN maquina m ON m.id_maquina = o.id_maquina
		JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = m.id_tipo_maquina
		JOIN punto p ON p.id_punto = o.id_punto
		JOIN cliente c ON c.id_cliente = p.id_cliente
		LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
		LEFT JOIN delegacion d ON d.id_delegacion = COALESCE(p.id_delegacion, mu.id_delegacion)
		LEFT JOIN modalidad_operativa mo ON mo.id_modalidad = o.id_modalidad
		LEFT JOIN tecnico t ON t.id_tecnico = o.id_tecnico
		LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
		LEFT JOIN estado_maquina em ON em.id_estado = o.id_estado_maquina
		LEFT JOIN calificacion_servicio cal ON cal.id_calificacion = o.id_calificacion
		""";

	private final JdbcClient jdbc;

	public ServicioConsultas(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	public List<Servicio> buscar(Filtros f, String orden, int limite) {
		Filtro w = new Filtro()
			// Por omisión solo servicios realizados; estado = -1 trae todos.
			.y("o.estado = :estado", "estado", f.estado() == null ? Integer.valueOf(1) : f.estado() < 0 ? null : f.estado())
			.y("o.fecha_visita >= :desde", "desde", f.desde())
			.y("o.fecha_visita <= :hasta", "hasta", f.hasta())
			.y("p.id_cliente = :cliente", "cliente", f.idCliente())
			.y("o.id_punto = :punto", "punto", f.idPunto())
			.y("o.id_tecnico = :tecnico", "tecnico", f.idTecnico())
			.y("d.id_delegacion = :delegacion", "delegacion", f.idDelegacion())
			.y("o.id_tipo_mantenimiento = :tipo", "tipo", f.idTipoMantenimiento())
			.texto("remision", f.remision(), "o.numero_remision")
			.texto("device", f.deviceId(), "m.device_id");
		if (Boolean.TRUE.equals(f.conNovedad())) {
			w.y("EXISTS (SELECT 1 FROM orden_servicio_novedad x WHERE x.id_orden_servicio = o.id_ordenes_servicio)");
		}
		List<Servicio> lista = jdbc.sql(SELECT + " WHERE " + w.where() + " ORDER BY " + orden + " LIMIT " + limite)
			.params(w.params()).query(Servicio.class).list();
		return conRepuestos(lista);
	}

	public Servicio uno(int idOrden) {
		List<Servicio> l = jdbc.sql(SELECT + " WHERE o.id_ordenes_servicio = :id").param("id", idOrden).query(Servicio.class).list();
		if (l.isEmpty()) {
			throw co.inees.plataforma.config.NegocioException.noEncontrado("El servicio");
		}
		return conRepuestos(l).get(0);
	}

	public List<Servicio> porIds(List<Integer> ids) {
		if (ids.isEmpty()) {
			return List.of();
		}
		return conRepuestos(jdbc.sql(SELECT + " WHERE o.id_ordenes_servicio IN (:ids) ORDER BY o.fecha_visita DESC, o.id_ordenes_servicio DESC")
			.param("ids", ids).query(Servicio.class).list());
	}

	/** Agrega los repuestos de cada servicio con una sola consulta (no una por fila). */
	private List<Servicio> conRepuestos(List<Servicio> lista) {
		if (lista.isEmpty()) {
			return lista;
		}
		List<Integer> ids = lista.stream().map(Servicio::idOrden).toList();
		Map<Integer, List<Repuesto>> porOrden = new HashMap<>();
		for (int i = 0; i < ids.size(); i += 1000) {
			jdbc.sql("""
				SELECT osr.id_orden_servicio, r.id_repuesto, r.nombre_repuesto, r.codigo_referencia, osr.origen, osr.cantidad,
				       osr.valor_unitario
				FROM orden_servicio_repuesto osr JOIN repuesto r ON r.id_repuesto = osr.id_repuesto
				WHERE osr.id_orden_servicio IN (:ids) ORDER BY r.nombre_repuesto""")
				.param("ids", ids.subList(i, Math.min(ids.size(), i + 1000)))
				.query((rs, n) -> {
					porOrden.computeIfAbsent(rs.getInt(1), k -> new ArrayList<>()).add(new Repuesto(rs.getInt(2), rs.getString(3),
						rs.getString(4), rs.getString(5), rs.getInt(6), rs.getBigDecimal(7)));
					return null;
				}).list();
		}
		return lista.stream().map(s -> s.conRepuestos(porOrden.getOrDefault(s.idOrden(), List.of()))).toList();
	}
}
