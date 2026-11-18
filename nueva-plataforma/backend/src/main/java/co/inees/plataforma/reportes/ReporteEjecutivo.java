package co.inees.plataforma.reportes;

import java.math.BigDecimal;
import java.time.DayOfWeek;
import java.time.LocalDate;
import java.time.temporal.ChronoUnit;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.stereotype.Component;

import co.inees.plataforma.comun.Filas;
import co.inees.plataforma.comun.Parametros;

/**
 * Tablero ejecutivo (reporteEjecutivo y generarReporte de PHP). Las secciones son filas genéricas
 * (columna → valor) para que la pantalla las grafique y el Excel las exporte sin más código.
 */
@Component
public class ReporteEjecutivo {

	public record Meta(int esperados, int diasLaborables, int tecnicosDias, int esperadosNormal, int esperadosNuevaArquitectura) {
	}

	public record Kpis(int totalServicios, int tecnicos, double mediaPorTecnico, double mediaDiaria, double cumplimientoMeta,
		Meta meta, int fallidos, int conNovedad, int sinNovedad, BigDecimal ingresosServicios, BigDecimal ingresosRepuestos,
		BigDecimal viaticos, BigDecimal costosOperativos, BigDecimal gastosGenerales) {
	}

	public record Tablero(LocalDate desde, LocalDate hasta, Kpis kpis, Map<String, List<Map<String, Object>>> secciones) {
	}

	private final JdbcClient jdbc;
	private final Parametros parametros;

	public ReporteEjecutivo(JdbcClient jdbc, Parametros parametros) {
		this.jdbc = jdbc;
		this.parametros = parametros;
	}

	public Tablero generar(LocalDate desde, LocalDate hasta) {
		Map<String, List<Map<String, Object>>> s = new LinkedHashMap<>();
		s.put("serviciosPorDia", filas("""
			SELECT o.fecha_visita AS fecha, COUNT(*) AS total, COUNT(DISTINCT o.id_tecnico) AS tecnicos
			FROM ordenes_servicio o WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY o.fecha_visita ORDER BY 1""", desde, hasta));
		s.put("serviciosPorSemana", filas("""
			SELECT YEARWEEK(o.fecha_visita, 1) AS semana, MIN(o.fecha_visita) AS inicio, MAX(o.fecha_visita) AS fin, COUNT(*) AS total
			FROM ordenes_servicio o WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h
			GROUP BY YEARWEEK(o.fecha_visita, 1) ORDER BY 2""", desde, hasta));
		s.put("delegaciones", filas("""
			SELECT d.nombre_delegacion AS delegacion, COUNT(*) AS servicios, COUNT(DISTINCT o.id_tecnico) AS tecnicos,
			       SUM(EXISTS (SELECT 1 FROM orden_servicio_novedad n WHERE n.id_orden_servicio = o.id_ordenes_servicio)) AS novedades,
			       COUNT(DISTINCT CONCAT(o.id_tecnico, '_', o.fecha_visita)) AS dias_efectivos,
			       COALESCE(SUM(o.valor_servicio), 0) AS ingresos
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY d.nombre_delegacion ORDER BY servicios DESC""", desde, hasta));
		s.put("tipos", filas("""
			SELECT tm.nombre_completo AS tipo, COUNT(*) AS total, COALESCE(SUM(o.valor_servicio), 0) AS ingresos
			FROM ordenes_servicio o JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY tm.id_tipo_mantenimiento, tm.nombre_completo
			ORDER BY tm.id_tipo_mantenimiento""", desde, hasta));
		s.put("productividad", filas("""
			SELECT t.nombre_tecnico AS tecnico,
			       SUM(DAYOFWEEK(o.fecha_visita) BETWEEN 2 AND 6) AS servicios_lv,
			       COUNT(DISTINCT IF(DAYOFWEEK(o.fecha_visita) BETWEEN 2 AND 6, o.fecha_visita, NULL)) AS dias_lv,
			       SUM(DAYOFWEEK(o.fecha_visita) = 7) AS servicios_sab,
			       COUNT(DISTINCT IF(DAYOFWEEK(o.fecha_visita) = 7, o.fecha_visita, NULL)) AS dias_sab,
			       COUNT(*) AS total, ROUND(COALESCE(SUM(o.minutos_servicio), 0) / 60, 1) AS horas
			FROM ordenes_servicio o JOIN tecnico t ON t.id_tecnico = o.id_tecnico
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY t.id_tecnico, t.nombre_tecnico ORDER BY total DESC""", desde, hasta));
		s.put("tecnicoPorTipo", filas("""
			SELECT t.nombre_tecnico AS fila, tm.nombre_completo AS columna, COUNT(*) AS valor
			FROM ordenes_servicio o JOIN tecnico t ON t.id_tecnico = o.id_tecnico
			JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY 1, 2""", desde, hasta));
		s.put("delegacionPorTipo", filas("""
			SELECT d.nombre_delegacion AS fila, tm.nombre_completo AS columna, COUNT(*) AS valor
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY 1, 2""", desde, hasta));
		s.put("delegacionPorTipoMaquina", filas("""
			SELECT d.nombre_delegacion AS fila, tmq.nombre_tipo_maquina AS columna, COUNT(DISTINCT o.id_punto) AS valor
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			JOIN maquina m ON m.id_maquina = o.id_maquina JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = m.id_tipo_maquina
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY 1, 2""", desde, hasta));
		s.put("estadosMaquina", filas("""
			SELECT em.nombre_estado AS estado, COUNT(*) AS total FROM ordenes_servicio o
			JOIN estado_maquina em ON em.id_estado = o.id_estado_maquina
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY em.nombre_estado ORDER BY total DESC""", desde, hasta));
		s.put("calificaciones", filas("""
			SELECT d.nombre_delegacion AS fila, cs.nombre_calificacion AS columna, COUNT(*) AS valor
			FROM ordenes_servicio o JOIN calificacion_servicio cs ON cs.id_calificacion = o.id_calificacion
			JOIN punto p ON p.id_punto = o.id_punto JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY 1, 2""", desde, hasta));
		s.put("repuestosOrigen", filas("""
			SELECT osr.origen, SUM(osr.cantidad) AS total, COALESCE(SUM(osr.cantidad * osr.valor_unitario), 0) AS valor
			FROM orden_servicio_repuesto osr JOIN ordenes_servicio o ON o.id_ordenes_servicio = osr.id_orden_servicio
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY osr.origen""", desde, hasta));
		s.put("repuestosPorDelegacion", filas("""
			SELECT d.nombre_delegacion AS delegacion, r.nombre_repuesto AS repuesto, SUM(osr.cantidad) AS cantidad
			FROM orden_servicio_repuesto osr JOIN ordenes_servicio o ON o.id_ordenes_servicio = osr.id_orden_servicio
			JOIN punto p ON p.id_punto = o.id_punto JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			JOIN repuesto r ON r.id_repuesto = osr.id_repuesto
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h AND osr.origen = 'INEES'
			GROUP BY d.nombre_delegacion, r.nombre_repuesto ORDER BY d.nombre_delegacion, cantidad DESC""", desde, hasta));
		s.put("novedadesPorDelegacion", top(filas(novedades(false), desde, hasta), "delegacion", 3));
		s.put("fallidosPorDelegacion", top(filas(novedades(true), desde, hasta), "delegacion", 6));
		s.put("topNovedades", filas("""
			SELECT tn.nombre_novedad AS novedad, COUNT(DISTINCT o.id_ordenes_servicio) AS total
			FROM ordenes_servicio o JOIN orden_servicio_novedad osn ON osn.id_orden_servicio = o.id_ordenes_servicio
			JOIN tipo_novedad tn ON tn.id_tipo_novedad = osn.id_tipo_novedad
			JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h AND UPPER(tm.nombre_completo) NOT LIKE '%FALLID%'
			GROUP BY tn.id_tipo_novedad, tn.nombre_novedad ORDER BY total DESC LIMIT 5""", desde, hasta));
		s.put("clientesConNovedades", top(filas("""
			SELECT COALESCE(d.nombre_delegacion, 'SIN DELEGACIÓN') AS delegacion, c.nombre_cliente AS cliente,
			       COUNT(DISTINCT o.id_ordenes_servicio) AS total,
			       (SELECT GROUP_CONCAT(CONCAT(x.nov, ' (', x.n, ')') ORDER BY x.n DESC, x.nov SEPARATOR '; ') FROM (
			           SELECT tn.nombre_novedad AS nov, COUNT(*) AS n, o2.id_cliente, p2.id_delegacion
			           FROM ordenes_servicio o2 JOIN punto p2 ON p2.id_punto = o2.id_punto
			           JOIN orden_servicio_novedad n2 ON n2.id_orden_servicio = o2.id_ordenes_servicio
			           JOIN tipo_novedad tn ON tn.id_tipo_novedad = n2.id_tipo_novedad
			           WHERE o2.estado = 1 AND o2.fecha_visita BETWEEN :d AND :h GROUP BY tn.id_tipo_novedad, o2.id_cliente, p2.id_delegacion) x
			         WHERE x.id_cliente = c.id_cliente AND x.id_delegacion <=> p.id_delegacion) AS novedades
			FROM ordenes_servicio o JOIN cliente c ON c.id_cliente = o.id_cliente JOIN punto p ON p.id_punto = o.id_punto
			LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			JOIN orden_servicio_novedad osn ON osn.id_orden_servicio = o.id_ordenes_servicio
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h
			GROUP BY d.nombre_delegacion, c.id_cliente, c.nombre_cliente, p.id_delegacion ORDER BY delegacion, total DESC""", desde, hasta),
			"delegacion", 3));
		s.put("puntosMasVisitados", filas("""
			SELECT p.id_punto, p.nombre_punto AS punto, d.nombre_delegacion AS delegacion, c.nombre_cliente AS cliente,
			       (SELECT GROUP_CONCAT(DISTINCT tm2.nombre_tipo_maquina SEPARATOR ', ') FROM maquina m2
			          JOIN tipo_maquina tm2 ON tm2.id_tipo_maquina = m2.id_tipo_maquina WHERE m2.id_punto = p.id_punto AND m2.estado = 1) AS tipos,
			       COUNT(DISTINCT o.id_ordenes_servicio) AS visitas,
			       GROUP_CONCAT(DISTINCT CONCAT(DATE_FORMAT(o.fecha_visita, '%d/%m'), ' ', COALESCE(tm.nombre_completo, ''))
			                    ORDER BY o.fecha_visita SEPARATOR ' | ') AS detalle
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto JOIN cliente c ON c.id_cliente = p.id_cliente
			LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h
			GROUP BY p.id_punto, p.nombre_punto, d.nombre_delegacion, c.nombre_cliente HAVING visitas >= 2
			ORDER BY visitas DESC LIMIT 50""", desde, hasta));
		s.put("puntosConFallidos", filas("""
			SELECT p.nombre_punto AS punto, d.nombre_delegacion AS delegacion, COUNT(*) AS fallidos
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h AND UPPER(tm.nombre_completo) LIKE '%FALLID%'
			GROUP BY p.id_punto, p.nombre_punto, d.nombre_delegacion HAVING fallidos >= 2 ORDER BY d.nombre_delegacion, fallidos DESC""",
			desde, hasta));
		LocalDate mesIni = desde.withDayOfMonth(1);
		LocalDate mesFin = hasta.withDayOfMonth(1);
		s.put("costosOperativos", jdbc.sql("""
			SELECT COALESCE(t.nombre_tecnico, u.nombre) AS beneficiario, COALESCE(u.cargo, 'Técnico motorizado') AS rol,
			       c.salario, c.horas_extra, c.bono_meta, c.gasolina, c.auxilio_comunicacion, c.auxilio_rodamiento,
			       c.salario + c.horas_extra + c.bono_meta + c.gasolina + c.auxilio_comunicacion + c.auxilio_rodamiento AS subtotal
			FROM costos_operativos c LEFT JOIN tecnico t ON t.id_tecnico = c.id_tecnico LEFT JOIN usuarios u ON u.usuario_id = c.id_usuario
			WHERE c.estado = 1 AND c.mes_reporte BETWEEN :mi AND :mf ORDER BY subtotal DESC""")
			.param("mi", mesIni).param("mf", mesFin).query().listOfRows().stream().map(Filas::normalizar).toList());
		s.put("gastosGenerales", jdbc.sql("""
			SELECT categoria, concepto, valor, mes_reporte AS mes FROM gastos_administrativos
			WHERE estado = 1 AND mes_reporte BETWEEN :mi AND :mf ORDER BY valor DESC""")
			.param("mi", mesIni).param("mf", mesFin).query().listOfRows().stream().map(Filas::normalizar).toList());

		return new Tablero(desde, hasta, kpis(desde, hasta, s), s);
	}

	private Kpis kpis(LocalDate desde, LocalDate hasta, Map<String, List<Map<String, Object>>> s) {
		int total = s.get("serviciosPorDia").stream().mapToInt(r -> num(r.get("total")).intValue()).sum();
		int tecnicos = s.get("productividad").size();
		long dias = ChronoUnit.DAYS.between(desde, hasta) + 1;
		Meta meta = meta(desde, hasta);
		record Totales(int fallidos, int conNovedad, BigDecimal ingresos, BigDecimal viaticos) {
		}
		Totales t = jdbc.sql("""
			SELECT SUM(UPPER(tm.nombre_completo) LIKE '%FALLID%') AS fallidos,
			       SUM(EXISTS (SELECT 1 FROM orden_servicio_novedad n WHERE n.id_orden_servicio = o.id_ordenes_servicio)) AS con_novedad,
			       COALESCE(SUM(o.valor_servicio), 0) AS ingresos, COALESCE(SUM(o.valor_viaticos), 0) AS viaticos
			FROM ordenes_servicio o LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h""").param("d", desde).param("h", hasta)
			.query((rs, i) -> new Totales(rs.getInt(1), rs.getInt(2), rs.getBigDecimal(3), rs.getBigDecimal(4))).single();
		BigDecimal repuestos = s.get("repuestosOrigen").stream().filter(r -> "INEES".equals(r.get("origen")))
			.map(r -> (BigDecimal) num(r.get("valor"))).findFirst().orElse(BigDecimal.ZERO);
		BigDecimal costos = s.get("costosOperativos").stream().map(r -> (BigDecimal) num(r.get("subtotal"))).reduce(BigDecimal.ZERO, BigDecimal::add);
		BigDecimal gastos = s.get("gastosGenerales").stream().map(r -> (BigDecimal) num(r.get("valor"))).reduce(BigDecimal.ZERO, BigDecimal::add);
		return new Kpis(total, tecnicos, tecnicos == 0 ? 0 : redondear(total / (double) tecnicos, 1),
			tecnicos == 0 ? 0 : redondear(total / (double) tecnicos / dias, 2),
			meta.esperados() == 0 ? 0 : redondear(total * 100.0 / meta.esperados(), 1), meta, t.fallidos(), t.conNovedad(),
			total - t.conNovedad(), t.ingresos(), repuestos, t.viaticos(), costos, gastos);
	}

	/** Meta esperada: por técnico y día laborable, 6 servicios (3 el sábado); 3/2 si ese día hizo Nueva Arquitectura. */
	public Meta meta(LocalDate desde, LocalDate hasta) {
		Set<LocalDate> festivos = new HashSet<>(jdbc.sql("SELECT fecha FROM dias_festivos WHERE fecha BETWEEN :d AND :h")
			.param("d", desde).param("h", hasta).query(LocalDate.class).list());
		int metaDia = parametros.entero(Parametros.META_DIA, 6), metaSab = parametros.entero(Parametros.META_SABADO, 3);
		int naDia = parametros.entero(Parametros.META_NA_DIA, 3), naSab = parametros.entero(Parametros.META_NA_SABADO, 2);
		int esperados = 0, tecnicosDias = 0, normal = 0, na = 0;
		Set<LocalDate> laborables = new HashSet<>();
		for (var r : jdbc.sql("""
			SELECT o.fecha_visita, MAX(o.id_tipo_mantenimiento = 6 OR UPPER(tm.nombre_completo) LIKE '%NUEVA ARQUITECTURA%') AS na
			FROM ordenes_servicio o LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h AND o.id_tecnico IS NOT NULL
			GROUP BY o.fecha_visita, o.id_tecnico""").param("d", desde).param("h", hasta)
			.query((rs, i) -> new Object[] { rs.getObject(1, LocalDate.class), rs.getBoolean(2) }).list()) {
			LocalDate f = (LocalDate) r[0];
			if (f.getDayOfWeek() == DayOfWeek.SUNDAY || festivos.contains(f)) {
				continue;
			}
			boolean sabado = f.getDayOfWeek() == DayOfWeek.SATURDAY;
			int m;
			if ((Boolean) r[1]) {
				m = sabado ? naSab : naDia;
				na += m;
			}
			else {
				m = sabado ? metaSab : metaDia;
				normal += m;
			}
			esperados += m;
			tecnicosDias++;
			laborables.add(f);
		}
		return new Meta(esperados, laborables.size(), tecnicosDias, normal, na);
	}

	static String novedades(boolean soloFallidos) {
		return """
			SELECT d.nombre_delegacion AS delegacion, tn.nombre_novedad AS novedad, COUNT(DISTINCT o.id_ordenes_servicio) AS total
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			JOIN orden_servicio_novedad osn ON osn.id_orden_servicio = o.id_ordenes_servicio
			JOIN tipo_novedad tn ON tn.id_tipo_novedad = osn.id_tipo_novedad
			JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h AND UPPER(tm.nombre_completo) """
			+ (soloFallidos ? "" : "NOT ") + """
			LIKE '%FALLID%'
			GROUP BY d.nombre_delegacion, tn.id_tipo_novedad, tn.nombre_novedad ORDER BY d.nombre_delegacion, total DESC""";
	}

	/** Deja las primeras n filas de cada grupo (las consultas ya vienen ordenadas). */
	static List<Map<String, Object>> top(List<Map<String, Object>> filas, String grupo, int n) {
		Map<Object, Integer> cuenta = new LinkedHashMap<>();
		List<Map<String, Object>> r = new ArrayList<>();
		for (var f : filas) {
			int c = cuenta.merge(f.get(grupo), 1, Integer::sum);
			if (c <= n) {
				r.add(f);
			}
		}
		return r;
	}

	List<Map<String, Object>> filas(String sql, LocalDate desde, LocalDate hasta) {
		return jdbc.sql(sql).param("d", desde).param("h", hasta).query().listOfRows().stream().map(Filas::normalizar).toList();
	}

	static Number num(Object o) {
		if (o instanceof BigDecimal b) {
			return b;
		}
		if (o instanceof Number n) {
			return new BigDecimal(n.toString());
		}
		return BigDecimal.ZERO;
	}

	private static double redondear(double v, int decimales) {
		double f = Math.pow(10, decimales);
		return Math.round(v * f) / f;
	}
}
