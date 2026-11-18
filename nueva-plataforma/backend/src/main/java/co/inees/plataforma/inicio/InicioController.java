package co.inees.plataforma.inicio;

import java.time.LocalDate;
import java.time.LocalTime;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.comun.Filas;
import co.inees.plataforma.seguridad.Sesion;

/** Inicio de cada usuario y la pantalla «Información de la base de datos». */
@RestController
@RequestMapping("/api")
public class InicioController {

	public record Oficina(int serviciosMes, int clientes, int tecnicos, int maquinas, int bolsaPendiente, int serviciosHoy,
		int horasExtraPendientes, int solicitudesPendientes, int devolucionesPendientes) {
	}

	public record Tecnico(LocalTime jornadaHoy, String novedadJornada, Map<String, Integer> serviciosMes, int totalMes,
		int pendientesHoy) {
	}

	/**
	 * Guía del día de la oficina: lo que hay que preparar para el próximo día hábil (Excel de Prosegur, pines, rutas,
	 * publicación) y cómo va hoy (rutas en la calle, servicios hechos y por revisar).
	 */
	public record Guia(LocalDate proximoDia, int cargasConfirmadas, int cargasEnRevision, int solicitudesProximo,
		int sinUbicacion, int bolsaProximo, int rutasBorrador, int rutasPublicadas, int motorizadosDisponibles,
		int rutasHoy, int paradasHoy, int paradasHechasHoy, int paradasSinIngresoHoy, int serviciosEnCurso,
		int cerradosHoy, int conNovedadHoy) {
	}

	public record Inicio(Oficina oficina, Tecnico tecnico, Guia guia) {
	}

	private final JdbcClient jdbc;

	public InicioController(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	@GetMapping("/inicio")
	public Inicio inicio() {
		var u = Sesion.actual();
		LocalDate hoy = LocalDate.now();
		LocalDate mes = hoy.withDayOfMonth(1);
		Oficina oficina = null;
		Tecnico tecnico = null;
		Guia guia = null;
		if (u.idTecnico() == null) {
			oficina = jdbc.sql("""
				SELECT (SELECT COUNT(*) FROM ordenes_servicio WHERE estado = 1 AND fecha_visita BETWEEN :mes AND :hoy) AS servicios_mes,
				       (SELECT COUNT(*) FROM cliente WHERE estado = 1) AS clientes,
				       (SELECT COUNT(*) FROM tecnico WHERE estado = 1) AS tecnicos,
				       (SELECT COUNT(*) FROM maquina WHERE estado = 1) AS maquinas,
				       (SELECT COUNT(*) FROM ordenes_servicio WHERE estado = 3) AS bolsa_pendiente,
				       (SELECT COUNT(*) FROM ordenes_servicio WHERE fecha_visita = :hoy AND estado IN (1, 2, 4, 5, 6)) AS servicios_hoy,
				       (SELECT COUNT(*) FROM registro_horas_extra WHERE id_estado_aprobacion = 1) AS horas_extra_pendientes,
				       (SELECT COUNT(*) FROM solicitudes_inventario WHERE estado = 'Pendiente') AS solicitudes_pendientes,
				       (SELECT COUNT(*) FROM control_devolucion_repuestos WHERE estado_devolucion = 'Pendiente') AS devoluciones_pendientes""")
				.param("mes", mes).param("hoy", hoy).query(Oficina.class).single();
			guia = guia(hoy);
		}
		else {
			record Jornada(LocalTime horaEntrada, String novedad) {
			}
			Jornada j = jdbc.sql("SELECT hora_entrada, novedad FROM turno_tecnico WHERE id_tecnico = :t AND fecha = :hoy LIMIT 1")
				.param("t", u.idTecnico()).param("hoy", hoy).query(Jornada.class).optional().orElse(null);
			// Misma clasificación del reporte técnico: básico, profundo, correctivo, fallido, otros
			Map<String, Integer> porTipo = new LinkedHashMap<>();
			List.of("basico", "profundo", "correctivo", "fallido", "otros").forEach(k -> porTipo.put(k, 0));
			int[] total = { 0 };
			jdbc.sql("""
				SELECT tm.nombre_completo, COUNT(*) FROM ordenes_servicio o
				LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
				WHERE o.id_tecnico = :t AND o.estado = 1 AND o.fecha_visita BETWEEN :mes AND :hoy
				  AND UPPER(COALESCE(tm.nombre_completo, '')) NOT LIKE '%INSTALACI%' GROUP BY tm.nombre_completo""")
				.param("t", u.idTecnico()).param("mes", mes).param("hoy", hoy)
				.query((rs, i) -> {
					porTipo.merge(clasificar(rs.getString(1)), rs.getInt(2), Integer::sum);
					total[0] += rs.getInt(2);
					return null;
				}).list();
			int pendientes = jdbc.sql("""
				SELECT COUNT(*) FROM ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta
				WHERE r.id_tecnico = :t AND r.fecha = :hoy AND rp.estado = 'PENDIENTE' AND r.estado IN ('PUBLICADA','EN_CURSO')""")
				.param("t", u.idTecnico()).param("hoy", hoy).query(Integer.class).single();
			tecnico = new Tecnico(j == null ? null : j.horaEntrada(), j == null ? null : j.novedad(), porTipo, total[0], pendientes);
		}
		return new Inicio(oficina, tecnico, guia);
	}

	static String clasificar(String tipo) {
		String t = tipo == null ? "" : java.text.Normalizer.normalize(tipo.toUpperCase(), java.text.Normalizer.Form.NFD)
			.replaceAll("\\p{M}", "");
		if (t.contains("FALLID")) {
			return "fallido";
		}
		if (t.contains("PROFUNDO") || t.contains("COMPLETO")) {
			return "profundo";
		}
		if (t.contains("CORRECTIVO") || t.contains("REPARACION")) {
			return "correctivo";
		}
		if (t.contains("PREVENTIVO") || t.contains("BASICO")) {
			return "basico";
		}
		return "otros";
	}

	/** Resumen de la base: totales, máquinas por delegación y tipo, principales clientes y antigüedad de las visitas. */
	@GetMapping("/admin/informacion-base")
	public Map<String, Object> informacion() {
		Map<String, Object> r = new LinkedHashMap<>();
		r.put("resumen", Filas.normalizar(jdbc.sql("""
			SELECT (SELECT COUNT(*) FROM maquina) AS total_maquinas, (SELECT COUNT(*) FROM maquina WHERE estado = 1) AS maquinas_activas,
			       (SELECT COUNT(*) FROM maquina WHERE estado = 1 AND activo_operativo = 0) AS fuera_de_servicio,
			       (SELECT COUNT(*) FROM maquina WHERE estado = 1 AND latitud IS NULL) AS sin_ubicacion,
			       (SELECT COUNT(*) FROM punto) AS total_puntos, (SELECT COUNT(*) FROM punto WHERE estado = 1) AS puntos_activos,
			       (SELECT COUNT(*) FROM cliente WHERE estado = 1) AS clientes, (SELECT COUNT(*) FROM delegacion WHERE estado = 1) AS delegaciones,
			       (SELECT COUNT(*) FROM ordenes_servicio WHERE estado = 1) AS servicios_realizados""").query().singleRow()));
		r.put("porDelegacionYTipo", Filas.normalizar(jdbc.sql("""
			SELECT COALESCE(d.nombre_delegacion, 'SIN DELEGACIÓN') AS fila, tm.nombre_tipo_maquina AS columna, COUNT(*) AS valor
			FROM maquina m JOIN punto p ON p.id_punto = m.id_punto JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina
			LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion WHERE m.estado = 1 GROUP BY 1, 2""").query().listOfRows()));
		r.put("topClientes", Filas.normalizar(jdbc.sql("""
			SELECT c.nombre_cliente AS cliente, COUNT(*) AS maquinas FROM maquina m JOIN punto p ON p.id_punto = m.id_punto
			JOIN cliente c ON c.id_cliente = p.id_cliente WHERE m.estado = 1 GROUP BY c.id_cliente, c.nombre_cliente
			ORDER BY maquinas DESC LIMIT 10""").query().listOfRows()));
		r.put("antiguedadVisitas", Filas.normalizar(jdbc.sql("""
			SELECT CASE WHEN ultima_visita IS NULL THEN 'Sin visita registrada'
			            WHEN ultima_visita >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 'Al día (< 30 días)'
			            WHEN ultima_visita >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 'Hace 1 a 3 meses'
			            ELSE 'Más de 3 meses' END AS rango, COUNT(*) AS maquinas
			FROM maquina WHERE estado = 1 GROUP BY rango ORDER BY MIN(COALESCE(ultima_visita, '1900-01-01')) DESC""").query().listOfRows()));
		r.put("vencidas", Filas.normalizar(jdbc.sql("""
			SELECT COUNT(*) AS total, SUM(es_vip = 1) AS vip FROM v_mantenimiento_pendiente
			WHERE fecha_vencimiento IS NULL OR fecha_vencimiento <= CURDATE()""").query().singleRow()));
		return r;
	}

	/** Próximo día hábil: se salta los domingos y los festivos. */
	LocalDate proximoDiaHabil(LocalDate desde) {
		LocalDate d = desde.plusDays(1);
		for (int i = 0; i < 10; i++) {
			boolean festivo = jdbc.sql("SELECT COUNT(*) FROM dias_festivos WHERE fecha = :f").param("f", d).query(Integer.class).single() > 0;
			if (d.getDayOfWeek() != java.time.DayOfWeek.SUNDAY && !festivo) {
				return d;
			}
			d = d.plusDays(1);
		}
		return desde.plusDays(1);
	}

	private Guia guia(LocalDate hoy) {
		LocalDate proximo = proximoDiaHabil(hoy);
		return jdbc.sql("""
			SELECT CAST(:proximo AS DATE) AS proximo_dia,
			       (SELECT COUNT(*) FROM carga_solicitud WHERE fecha_servicio = :proximo AND estado = 'CONFIRMADA') AS cargas_confirmadas,
			       (SELECT COUNT(*) FROM carga_solicitud WHERE fecha_servicio >= :hoy AND estado = 'EN_REVISION') AS cargas_en_revision,
			       (SELECT COUNT(*) FROM solicitud_servicio s JOIN carga_solicitud c ON c.id_carga = s.id_carga
			         WHERE c.fecha_servicio = :proximo AND c.estado <> 'ANULADA' AND s.estado IN ('VALIDA', 'ORDEN_GENERADA')) AS solicitudes_proximo,
			       (SELECT COUNT(DISTINCT m.id_maquina) FROM maquina m
			         WHERE m.latitud IS NULL AND m.estado = 1
			           AND (EXISTS (SELECT 1 FROM solicitud_servicio s JOIN carga_solicitud c ON c.id_carga = s.id_carga
			                         WHERE s.id_maquina = m.id_maquina AND c.estado = 'EN_REVISION' AND s.estado = 'VALIDA')
			                OR EXISTS (SELECT 1 FROM ordenes_servicio o WHERE o.id_maquina = m.id_maquina AND o.estado = 3))) AS sin_ubicacion,
			       (SELECT COUNT(*) FROM ordenes_servicio WHERE estado = 3 AND fecha_visita <= :proximo) AS bolsa_proximo,
			       (SELECT COUNT(*) FROM ruta_diaria WHERE fecha = :proximo AND estado = 'BORRADOR') AS rutas_borrador,
			       (SELECT COUNT(*) FROM ruta_diaria WHERE fecha = :proximo AND estado IN ('PUBLICADA', 'EN_CURSO')) AS rutas_publicadas,
			       (SELECT COUNT(*) FROM tecnico WHERE estado = 1 AND id_delegacion IS NOT NULL) AS motorizados_disponibles,
			       (SELECT COUNT(*) FROM ruta_diaria WHERE fecha = :hoy AND estado <> 'BORRADOR') AS rutas_hoy,
			       (SELECT COUNT(*) FROM ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta
			         WHERE r.fecha = :hoy AND r.estado <> 'BORRADOR' AND rp.estado <> 'RETIRADA') AS paradas_hoy,
			       (SELECT COUNT(*) FROM ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta
			         WHERE r.fecha = :hoy AND r.estado <> 'BORRADOR' AND rp.estado = 'HECHA') AS paradas_hechas_hoy,
			       (SELECT COUNT(*) FROM ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta
			         WHERE r.fecha = :hoy AND r.estado <> 'BORRADOR' AND rp.estado = 'NO_INGRESO') AS paradas_sin_ingreso_hoy,
			       (SELECT COUNT(*) FROM ordenes_servicio WHERE estado IN (4, 5, 6)) AS servicios_en_curso,
			       (SELECT COUNT(*) FROM ordenes_servicio WHERE estado = 1 AND fecha_visita = :hoy) AS cerrados_hoy,
			       (SELECT COUNT(*) FROM ordenes_servicio WHERE estado = 1 AND fecha_visita = :hoy AND tiene_novedad = 1) AS con_novedad_hoy""")
			.param("proximo", proximo).param("hoy", hoy).query(Guia.class).single();
	}
}
