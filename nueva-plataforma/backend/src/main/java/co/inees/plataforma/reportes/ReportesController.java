package co.inees.plataforma.reportes;

import static co.inees.plataforma.comun.Html.esc;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.comun.Descarga;
import co.inees.plataforma.comun.Excel;
import co.inees.plataforma.comun.Filas;
import co.inees.plataforma.comun.Html;
import co.inees.plataforma.comun.Pdf;
import co.inees.plataforma.config.NegocioException;

/** Reportes de gestión (permiso REPORTES). Cada reporte se ve en pantalla y se descarga en Excel o PDF. */
@RestController
@RequestMapping("/api/reportes")
public class ReportesController {

	public record Tabla(List<String> columnas, List<Map<String, Object>> filas) {
	}

	public record Inventario(Map<String, Object> kpis, List<Map<String, Object>> consolidado, List<Map<String, Object>> porTecnico) {
	}

	private final JdbcClient jdbc;
	private final ReporteEjecutivo ejecutivo;
	private final ReporteEjecutivoPdf ejecutivoPdf;
	private final ReporteTecnico tecnico;

	public ReportesController(JdbcClient jdbc, ReporteEjecutivo ejecutivo, ReporteEjecutivoPdf ejecutivoPdf, ReporteTecnico tecnico) {
		this.jdbc = jdbc;
		this.ejecutivo = ejecutivo;
		this.ejecutivoPdf = ejecutivoPdf;
		this.tecnico = tecnico;
	}

	// ================================================================== ejecutivo
	@GetMapping("/ejecutivo")
	public ReporteEjecutivo.Tablero ejecutivo(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		rango(desde, hasta, 400);
		return ejecutivo.generar(desde, hasta);
	}

	@GetMapping("/ejecutivo/excel")
	public ResponseEntity<byte[]> ejecutivoExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		rango(desde, hasta, 400);
		var t = ejecutivo.generar(desde, hasta);
		Excel x = new Excel();
		var k = t.kpis();
		x.hoja("Resumen", "Tablero ejecutivo del " + desde + " al " + hasta, List.of("Indicador", "Valor"), List.of(
			List.of("Servicios", k.totalServicios()), List.of("Técnicos", k.tecnicos()),
			List.of("Promedio por técnico", k.mediaPorTecnico()), List.of("Promedio diario por técnico", k.mediaDiaria()),
			List.of("Servicios esperados (meta)", k.meta().esperados()), List.of("Cumplimiento de la meta (%)", k.cumplimientoMeta()),
			List.of("Fallidos", k.fallidos()), List.of("Con novedad", k.conNovedad()),
			List.of("Ingresos por servicios", new Excel.Pesos(k.ingresosServicios())),
			List.of("Repuestos INEES", new Excel.Pesos(k.ingresosRepuestos())), List.of("Viáticos", new Excel.Pesos(k.viaticos())),
			List.of("Costos operativos", new Excel.Pesos(k.costosOperativos())), List.of("Gastos generales", new Excel.Pesos(k.gastosGenerales()))));
		Map<String, String> nombres = new LinkedHashMap<>();
		nombres.put("puntosMasVisitados", "Puntos más visitados");
		nombres.put("novedadesPorDelegacion", "Novedades por delegación");
		nombres.put("fallidosPorDelegacion", "Fallidos por delegación");
		nombres.put("clientesConNovedades", "Clientes con novedades");
		nombres.put("delegaciones", "Delegaciones");
		nombres.put("productividad", "Productividad");
		nombres.put("tipos", "Tipos de servicio");
		nombres.put("puntosConFallidos", "Puntos con fallidos");
		nombres.put("repuestosPorDelegacion", "Repuestos por delegación");
		nombres.put("costosOperativos", "Costos operativos");
		nombres.put("gastosGenerales", "Gastos generales");
		nombres.forEach((clave, titulo) -> hojaGenerica(x, titulo, t.secciones().get(clave)));
		return Descarga.excel("Reporte_ejecutivo_" + desde + "_" + hasta, x.bytes());
	}

	/** Secciones que se pueden elegir para el PDF (las de «Configurar reporte» de PHP). */
	@GetMapping("/ejecutivo/secciones")
	public List<ReporteEjecutivoPdf.Seccion> ejecutivoSecciones() {
		return ReporteEjecutivoPdf.SECCIONES;
	}

	/** PDF ejecutivo: A4 horizontal, una sección por página. Sin {@code secciones} van todas. */
	@GetMapping("/ejecutivo/pdf")
	public ResponseEntity<byte[]> ejecutivoPdf(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) List<String> secciones,
		@RequestParam(defaultValue = "2") int minVisitas) {
		rango(desde, hasta, 400);
		var elegidas = new java.util.HashSet<>(secciones == null ? List.<String>of() : secciones);
		return Descarga.pdf("Reporte_Ejecutivo_" + Html.fecha(desde).replace('/', '-') + "_al_" + Html.fecha(hasta).replace('/', '-'),
			ejecutivoPdf.generar(desde, hasta, elegidas, minVisitas));
	}

	// ================================================================== técnico
	@GetMapping("/tecnico")
	public List<ReporteTecnico.Dia> reporteTecnico(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		rango(desde, hasta, 62);
		return tecnico.generar(desde, hasta, idTecnico);
	}

	@GetMapping("/tecnico/pdf")
	public ResponseEntity<byte[]> reporteTecnicoPdf(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		rango(desde, hasta, 62);
		return Descarga.pdf("Reporte_tecnico_" + desde + "_" + hasta, Pdf.generar(tecnico.html(desde, hasta, tecnico.generar(desde, hasta, idTecnico))));
	}

	// ================================================================== repuestos usados
	@GetMapping("/repuestos-usados")
	public Tabla repuestosUsados(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) String origen) {
		return new Tabla(List.of("codigo", "repuesto", "origen", "cantidad", "servicios", "valor"), filas("""
			SELECT r.codigo_referencia AS codigo, r.nombre_repuesto AS repuesto, osr.origen, SUM(osr.cantidad) AS cantidad,
			       COUNT(DISTINCT o.id_ordenes_servicio) AS servicios, SUM(osr.cantidad * osr.valor_unitario) AS valor
			FROM orden_servicio_repuesto osr JOIN ordenes_servicio o ON o.id_ordenes_servicio = osr.id_orden_servicio
			JOIN repuesto r ON r.id_repuesto = osr.id_repuesto
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h AND (:o IS NULL OR osr.origen = :o)
			GROUP BY r.id_repuesto, osr.origen ORDER BY cantidad DESC""", Map.of("d", desde, "h", hasta), "o", vacio(origen)));
	}

	@GetMapping("/repuestos-usados/excel")
	public ResponseEntity<byte[]> repuestosUsadosExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) String origen) {
		Excel x = new Excel();
		hojaGenerica(x, "Consolidado", repuestosUsados(desde, hasta, origen).filas());
		hojaGenerica(x, "Detalle INEES", filas("""
			SELECT o.fecha_visita AS fecha, o.numero_remision AS remision, c.nombre_cliente AS cliente, p.nombre_punto AS punto,
			       d.nombre_delegacion AS delegacion, m.device_id, tm.nombre_tipo_maquina AS tipo_maquina,
			       r.codigo_referencia AS codigo, r.nombre_repuesto AS repuesto, osr.cantidad, o.actividades_realizadas AS observacion
			FROM orden_servicio_repuesto osr JOIN ordenes_servicio o ON o.id_ordenes_servicio = osr.id_orden_servicio
			JOIN repuesto r ON r.id_repuesto = osr.id_repuesto JOIN punto p ON p.id_punto = o.id_punto
			JOIN cliente c ON c.id_cliente = p.id_cliente JOIN maquina m ON m.id_maquina = o.id_maquina
			LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion LEFT JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina
			WHERE o.estado = 1 AND osr.origen = 'INEES' AND o.fecha_visita BETWEEN :d AND :h
			ORDER BY o.fecha_visita DESC, c.nombre_cliente""", Map.of("d", desde, "h", hasta), null, null));
		return Descarga.excel("Repuestos_" + desde + "_" + hasta, x.bytes());
	}

	// ================================================================== inventario de los técnicos
	@GetMapping("/inventario")
	public Inventario inventario() {
		Map<String, Object> kpis = Filas.normalizar(jdbc.sql("""
			SELECT COALESCE(SUM(cantidad_actual), 0) AS total_piezas, COUNT(DISTINCT IF(cantidad_actual > 0, id_tecnico, NULL)) AS tecnicos_con_stock,
			       COUNT(DISTINCT IF(cantidad_actual > 0, id_repuesto, NULL)) AS referencias, SUM(cantidad_actual = 0) AS agotados
			FROM inventario_tecnico WHERE estado = 1""").query().singleRow());
		var consolidado = filas("""
			SELECT r.nombre_repuesto AS repuesto, r.codigo_referencia AS codigo, COUNT(DISTINCT IF(i.cantidad_actual > 0, i.id_tecnico, NULL)) AS tecnicos,
			       SUM(i.cantidad_actual) AS total
			FROM inventario_tecnico i JOIN repuesto r ON r.id_repuesto = i.id_repuesto WHERE i.estado = 1
			GROUP BY r.id_repuesto HAVING total > 0 ORDER BY total DESC, r.nombre_repuesto""", Map.of(), null, null);
		var porTecnico = filas("""
			SELECT t.nombre_tecnico AS tecnico, r.nombre_repuesto AS repuesto, r.codigo_referencia AS codigo, i.cantidad_actual AS cantidad
			FROM inventario_tecnico i JOIN tecnico t ON t.id_tecnico = i.id_tecnico JOIN repuesto r ON r.id_repuesto = i.id_repuesto
			WHERE i.estado = 1 AND i.cantidad_actual > 0 ORDER BY t.nombre_tecnico, r.nombre_repuesto""", Map.of(), null, null);
		return new Inventario(kpis, consolidado, porTecnico);
	}

	@GetMapping("/inventario/pdf")
	public ResponseEntity<byte[]> inventarioPdf() {
		Inventario inv = inventario();
		StringBuilder b = new StringBuilder(Pdf.encabezado("Inventario de repuestos de los técnicos", "Corte al " + Html.fecha(LocalDate.now())));
		b.append("<table><tr><th>Piezas en poder de técnicos</th><th>Técnicos con stock</th><th>Referencias</th><th>Referencias agotadas</th></tr><tr>")
			.append("<td class=\"cen\">").append(inv.kpis().get("total_piezas")).append("</td><td class=\"cen\">")
			.append(inv.kpis().get("tecnicos_con_stock")).append("</td><td class=\"cen\">").append(inv.kpis().get("referencias"))
			.append("</td><td class=\"cen\">").append(inv.kpis().get("agotados")).append("</td></tr></table>");
		b.append("<h2>Consolidado por repuesto</h2>").append(Html.tabla(List.of("Repuesto", "Código", "Técnicos", "Total"), inv.consolidado(),
			r -> List.of(esc(r.get("repuesto")), esc(r.get("codigo")), esc(r.get("tecnicos")), esc(r.get("total")))));
		Map<Object, List<Map<String, Object>>> porTec = new LinkedHashMap<>();
		inv.porTecnico().forEach(r -> porTec.computeIfAbsent(r.get("tecnico"), k -> new ArrayList<>()).add(r));
		porTec.forEach((tec, lista) -> b.append("<h3>").append(esc(tec)).append("</h3>").append(Html.tabla(List.of("Repuesto", "Código", "Cantidad"),
			lista, r -> List.of(esc(r.get("repuesto")), esc(r.get("codigo")), esc(r.get("cantidad"))))));
		return Descarga.pdf("Inventario_tecnicos_" + LocalDate.now(), Pdf.generar(Pdf.documento("Inventario", null, b.toString())));
	}

	// ================================================================== máquinas por tipo
	@GetMapping("/maquinas")
	public Tabla maquinas(@RequestParam(required = false) Integer idTipoMaquina, @RequestParam(required = false) Integer idDelegacion) {
		return new Tabla(List.of("delegacion", "device_id", "tipo_maquina", "cliente", "punto", "direccion", "ultima_visita", "operativa", "vip"),
			filas("""
				SELECT d.nombre_delegacion AS delegacion, m.device_id, tm.nombre_tipo_maquina AS tipo_maquina, c.nombre_cliente AS cliente,
				       p.nombre_punto AS punto, p.direccion, m.ultima_visita, m.activo_operativo = 1 AS operativa, m.es_vip = 1 AS vip
				FROM maquina m JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina JOIN punto p ON p.id_punto = m.id_punto
				JOIN cliente c ON c.id_cliente = p.id_cliente LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion
				WHERE m.estado = 1 AND p.estado = 1 AND (:t IS NULL OR m.id_tipo_maquina = :t) AND (:del IS NULL OR p.id_delegacion = :del)
				ORDER BY d.nombre_delegacion, p.nombre_punto""", Map.of(), "t", idTipoMaquina, "del", idDelegacion));
	}

	@GetMapping("/maquinas/resumen")
	public List<Map<String, Object>> maquinasResumen() {
		return filas("""
			SELECT d.nombre_delegacion AS fila, tm.nombre_tipo_maquina AS columna, COUNT(*) AS valor
			FROM maquina m JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina JOIN punto p ON p.id_punto = m.id_punto
			LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion WHERE m.estado = 1 AND p.estado = 1 GROUP BY 1, 2""",
			Map.of(), null, null);
	}

	@GetMapping("/maquinas/excel")
	public ResponseEntity<byte[]> maquinasExcel(@RequestParam(required = false) Integer idTipoMaquina,
		@RequestParam(required = false) Integer idDelegacion) {
		Excel x = new Excel();
		hojaGenerica(x, "Máquinas", maquinas(idTipoMaquina, idDelegacion).filas());
		return Descarga.excel("Maquinas_" + LocalDate.now(), x.bytes());
	}

	// ================================================================== historial y cumplimiento
	@GetMapping("/historial")
	public Map<String, List<Map<String, Object>>> historial(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		rango(desde, hasta, 800);
		Map<String, Object> p = Map.of("d", desde, "h", hasta);
		Map<String, List<Map<String, Object>>> r = new LinkedHashMap<>();
		r.put("mantenimientos", filas("""
			SELECT o.fecha_visita AS fecha, c.nombre_cliente AS cliente, p.nombre_punto AS punto, d.nombre_delegacion AS delegacion,
			       m.device_id, tmq.nombre_tipo_maquina AS tipo_maquina, tm.nombre_completo AS tipo_mantenimiento
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto JOIN cliente c ON c.id_cliente = p.id_cliente
			LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion JOIN maquina m ON m.id_maquina = o.id_maquina
			LEFT JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = m.id_tipo_maquina
			LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h AND COALESCE(o.id_tipo_mantenimiento, 0) <> 4 AND p.estado = 1
			ORDER BY o.fecha_visita DESC""", p, null, null));
		// Cumplimiento: cuántos mantenimientos tuvo cada máquina y cuántos preventivos le faltaron según su frecuencia
		r.put("cumplimiento", filas("""
			SELECT v.device_id, tmq.nombre_tipo_maquina AS tipo_maquina, c.nombre_cliente AS cliente, p.nombre_punto AS punto,
			       d.nombre_delegacion AS delegacion, v.frecuencia_dias AS frecuencia, v.ultima_visita, v.fecha_vencimiento,
			       COUNT(IF(COALESCE(o.id_tipo_mantenimiento, 0) <> 4, o.id_ordenes_servicio, NULL)) AS mantenimientos,
			       SUM(o.id_tipo_mantenimiento IN (1, 2)) AS preventivos,
			       GREATEST(FLOOR((DATEDIFF(:h, :d) + 1) / v.frecuencia_dias) - COALESCE(SUM(o.id_tipo_mantenimiento IN (1, 2)), 0), 0) AS preventivos_faltantes
			FROM v_mantenimiento_pendiente v JOIN punto p ON p.id_punto = v.id_punto JOIN cliente c ON c.id_cliente = p.id_cliente
			LEFT JOIN delegacion d ON d.id_delegacion = v.id_delegacion JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = v.id_tipo_maquina
			LEFT JOIN ordenes_servicio o ON o.id_maquina = v.id_maquina AND o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h
			GROUP BY v.id_maquina ORDER BY c.nombre_cliente, p.nombre_punto""", p, null, null));
		return r;
	}

	@GetMapping("/historial/excel")
	public ResponseEntity<byte[]> historialExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		var h = historial(desde, hasta);
		Excel x = new Excel();
		hojaGenerica(x, "Mantenimientos", h.get("mantenimientos"));
		hojaGenerica(x, "Cumplimiento", h.get("cumplimiento"));
		return Descarga.excel("Historial_" + desde + "_" + hasta, x.bytes());
	}

	// ================================================================== trazabilidad de repuestos
	@GetMapping("/trazabilidad")
	public Map<String, List<Map<String, Object>>> trazabilidad(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		Map<String, Object> p = Map.of("d", desde, "h", hasta);
		Map<String, List<Map<String, Object>>> r = new LinkedHashMap<>();
		r.put("porPunto", filas("""
			SELECT c.nombre_cliente AS cliente, p.nombre_punto AS punto, tm.nombre_tipo_maquina AS tipo_maquina, m.device_id,
			       r.nombre_repuesto AS repuesto, osr.origen, osr.cantidad, o.fecha_visita AS fecha_cambio
			FROM orden_servicio_repuesto osr JOIN ordenes_servicio o ON o.id_ordenes_servicio = osr.id_orden_servicio
			JOIN repuesto r ON r.id_repuesto = osr.id_repuesto JOIN maquina m ON m.id_maquina = o.id_maquina
			JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina JOIN punto p ON p.id_punto = o.id_punto
			JOIN cliente c ON c.id_cliente = p.id_cliente
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h ORDER BY p.nombre_punto, o.fecha_visita DESC""", p, null, null));
		r.put("porTipoMaquina", filas("""
			SELECT tm.nombre_tipo_maquina AS tipo_maquina, r.nombre_repuesto AS repuesto, osr.origen, SUM(osr.cantidad) AS total
			FROM orden_servicio_repuesto osr JOIN ordenes_servicio o ON o.id_ordenes_servicio = osr.id_orden_servicio
			JOIN repuesto r ON r.id_repuesto = osr.id_repuesto JOIN maquina m ON m.id_maquina = o.id_maquina
			JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h
			GROUP BY tm.nombre_tipo_maquina, r.nombre_repuesto, osr.origen ORDER BY tm.nombre_tipo_maquina, total DESC""", p, null, null));
		return r;
	}

	@GetMapping("/trazabilidad/excel")
	public ResponseEntity<byte[]> trazabilidadExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		var t = trazabilidad(desde, hasta);
		Excel x = new Excel();
		hojaGenerica(x, "Trazabilidad por punto", t.get("porPunto"));
		hojaGenerica(x, "Top por tipo de máquina", t.get("porTipoMaquina"));
		return Descarga.excel("Trazabilidad_repuestos_" + desde + "_" + hasta, x.bytes());
	}

	// ================================================================== tarifas: participación de cada tipo de servicio
	@GetMapping("/tarifas")
	public Tabla tarifas(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		List<Map<String, Object>> filas = filas("""
			SELECT IF(tmq.es_grande = 1, 'Grandes', 'Pequeñas') AS categoria, tmq.nombre_tipo_maquina AS tipo_maquina,
			       tm.nombre_completo AS tipo_mantenimiento, COUNT(*) AS servicios, SUM(o.valor_servicio) AS valor
			FROM ordenes_servicio o JOIN maquina m ON m.id_maquina = o.id_maquina JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = m.id_tipo_maquina
			JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 1 AND tm.nombre_completo NOT LIKE '%Instalaci%' AND tm.nombre_completo NOT LIKE '%Garant%'
			  AND o.fecha_visita BETWEEN :d AND :h
			GROUP BY categoria, tmq.id_tipo_maquina, tm.id_tipo_mantenimiento ORDER BY categoria, tmq.nombre_tipo_maquina, valor DESC""",
			Map.of("d", desde, "h", hasta), null, null);
		// Porcentaje de cada tipo de servicio dentro de su tipo de máquina y dentro de la categoría
		Map<Object, Double> porMaquina = new LinkedHashMap<>(), porCategoria = new LinkedHashMap<>();
		filas.forEach(f -> {
			double v = ReporteEjecutivo.num(f.get("valor")).doubleValue();
			porMaquina.merge(f.get("tipo_maquina"), v, Double::sum);
			porCategoria.merge(f.get("categoria"), v, Double::sum);
		});
		List<Map<String, Object>> r = new ArrayList<>();
		for (var f : filas) {
			Map<String, Object> m = new LinkedHashMap<>(f);
			double v = ReporteEjecutivo.num(f.get("valor")).doubleValue();
			double tm = porMaquina.get(f.get("tipo_maquina")), tc = porCategoria.get(f.get("categoria"));
			m.put("pct_maquina", tm == 0 ? 0 : Math.round(v * 1000 / tm) / 10.0);
			m.put("pct_categoria", tc == 0 ? 0 : Math.round(v * 1000 / tc) / 10.0);
			r.add(m);
		}
		return new Tabla(List.of("categoria", "tipo_maquina", "tipo_mantenimiento", "servicios", "valor", "pct_maquina", "pct_categoria"), r);
	}

	@GetMapping("/tarifas/excel")
	public ResponseEntity<byte[]> tarifasExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		Excel x = new Excel();
		hojaGenerica(x, "Porcentajes", tarifas(desde, hasta).filas());
		return Descarga.excel("Tarifas_porcentajes_" + desde + "_" + hasta, x.bytes());
	}

	// ================================================================== exportación de puntos (exportarExcel)
	@GetMapping("/puntos/excel")
	public ResponseEntity<byte[]> puntosExcel() {
		Excel x = new Excel();
		hojaGenerica(x, "Puntos", filas("""
			SELECT c.codigo_cliente, c.nombre_cliente AS cliente, p.nombre_punto AS punto, p.direccion, mu.nombre_municipio AS municipio,
			       p.zona, d.nombre_delegacion AS delegacion, m.device_id, tmq.nombre_tipo_maquina AS tipo_maquina,
			       v.ultima_visita, tm.nombre_completo AS ultimo_mantenimiento, v.frecuencia_dias, v.fecha_vencimiento,
			       m.latitud, m.longitud
			FROM punto p JOIN maquina m ON m.id_punto = p.id_punto AND m.estado = 1 JOIN cliente c ON c.id_cliente = p.id_cliente
			JOIN municipio mu ON mu.id_municipio = p.id_municipio JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = m.id_tipo_maquina
			LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = p.id_ultimo_tipo_mantenimiento
			LEFT JOIN v_mantenimiento_pendiente v ON v.id_maquina = m.id_maquina
			WHERE p.estado = 1 ORDER BY c.nombre_cliente, p.nombre_punto""", Map.of(), null, null));
		return Descarga.excel("Puntos_" + LocalDate.now(), x.bytes());
	}

	// ------------------------------------------------------------------ apoyo
	private List<Map<String, Object>> filas(String sql, Map<String, Object> params, Object... extra) {
		var spec = jdbc.sql(sql).params(params);
		for (int i = 0; i + 1 < extra.length; i += 2) {
			if (extra[i] != null) {
				spec = spec.param((String) extra[i], extra[i + 1]);
			}
		}
		return spec.query().listOfRows().stream().map(Filas::normalizar).toList();
	}

	/** Hoja con las columnas tal como vienen de la consulta (encabezados legibles). */
	private static void hojaGenerica(Excel x, String titulo, List<Map<String, Object>> filas) {
		if (filas == null) {
			return;
		}
		List<String> columnas = filas.isEmpty() ? List.of("Sin datos") : new ArrayList<>(filas.get(0).keySet());
		List<? extends List<?>> datos = filas.stream().map(f -> (List<?>) new ArrayList<>(f.values())).toList();
		x.hoja(titulo, columnas.stream().map(ReportesController::legible).toList(), datos);
	}

	static String legible(String columna) {
		String s = columna.replace('_', ' ');
		return Character.toUpperCase(s.charAt(0)) + s.substring(1);
	}

	private static void rango(LocalDate desde, LocalDate hasta, int maxDias) {
		if (hasta.isBefore(desde)) {
			throw new NegocioException("La fecha final no puede ser anterior a la inicial.");
		}
		if (java.time.temporal.ChronoUnit.DAYS.between(desde, hasta) > maxDias) {
			throw new NegocioException("El rango máximo para este reporte es de " + maxDias + " días.");
		}
	}

	private static String vacio(String s) {
		return s == null || s.isBlank() ? null : s.trim();
	}
}
