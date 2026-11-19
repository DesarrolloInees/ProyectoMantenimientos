package co.inees.mantenimientos.modulos.reportes;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Excel;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Pdf;
import co.inees.mantenimientos.core.Req;

/**
 * reporteHistorialControlador.php, reporteRepuestoControlador.php,
 * reporteRepuestosControlador.php (PDF de inventario) y reporteTrazabilidadControlador.php (+Excel)
 */
@RestController
public class ReportesInventarioController {

    private static final Logger log = LoggerFactory.getLogger(ReportesInventarioController.class);
    private final Db db;
    private final Pdf pdf;

    public ReportesInventarioController(Db db, Pdf pdf) {
        this.db = db;
        this.pdf = pdf;
    }

    private static String primeroMes() {
        return F.hoy().substring(0, 8) + "01";
    }

    // ================================================================ HISTORIAL / CUMPLIMIENTO

    @RequestMapping(value = "/api/reporteHistorial", params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> reporteHistorial(Req req) {
        Map<String, Object> filtros = new LinkedHashMap<>(Map.of("fecha_inicio", primeroMes(), "fecha_fin", F.hoy()));
        List<Map<String, Object>> datos = List.of(), excel = List.of();
        String mensaje = "";
        if (req.isPost()) {
            String ini = req.str("fecha_inicio", ""), fin = req.str("fecha_fin", "");
            filtros.put("fecha_inicio", ini);
            filtros.put("fecha_fin", fin);
            if (!ini.isEmpty() && !fin.isEmpty()) {
                try {
                    datos = db.list("""
                            SELECT
                            os.id_ordenes_servicio,
                            os.fecha_visita,
                            os.id_tipo_mantenimiento,
                            c.nombre_cliente AS cliente,
                            p.nombre_punto AS punto,
                            d.nombre_delegacion AS dele,
                            m.device_id,
                            tmaq.nombre_tipo_maquina AS tipo_maquina,
                            tm.nombre_completo AS tipo_mantenimiento
                            FROM ordenes_servicio os
                            INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                            INNER JOIN punto p ON os.id_punto = p.id_punto
                            LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                            INNER JOIN maquina m ON os.id_maquina = m.id_maquina
                            LEFT JOIN tipo_maquina tmaq ON m.id_tipo_maquina = tmaq.id_tipo_maquina
                            LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                            WHERE os.fecha_visita BETWEEN :inicio AND :fin
                            AND (os.id_tipo_mantenimiento != 4 OR os.id_tipo_mantenimiento IS NULL)
                            AND p.estado = 1
                            ORDER BY os.fecha_visita DESC""", "inicio", ini, "fin", fin);
                } catch (Exception e) {
                    log.error("Error en historial de puntos: {}", e.getMessage());
                }
                try {
                    excel = db.list("""
                            SELECT
                            m.device_id,
                            tmaq.nombre_tipo_maquina AS tipo_maquina,
                            c.nombre_cliente AS cliente,
                            p.nombre_punto AS punto,
                            d.nombre_delegacion AS dele,
                            p.frecuencia_mantenimiento_dias AS frecuencia,
                            p.fecha_ultima_visita AS fecha_ultima,
                            SUM(CASE WHEN os.id_ordenes_servicio IS NOT NULL AND (os.id_tipo_mantenimiento != 4 OR os.id_tipo_mantenimiento IS NULL) THEN 1 ELSE 0 END) AS total_mantenimientos,
                            SUM(CASE WHEN os.id_tipo_mantenimiento IN (1, 2) THEN 1 ELSE 0 END) AS total_preventivos
                            FROM punto p
                            INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                            LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                            LEFT JOIN maquina m ON p.id_punto = m.id_punto
                            LEFT JOIN tipo_maquina tmaq ON m.id_tipo_maquina = tmaq.id_tipo_maquina
                            LEFT JOIN ordenes_servicio os ON p.id_punto = os.id_punto
                                AND m.id_maquina = os.id_maquina
                                AND os.fecha_visita BETWEEN :inicio AND :fin
                            WHERE p.estado = 1
                            GROUP BY p.id_punto, m.id_maquina
                            ORDER BY c.nombre_cliente ASC, p.nombre_punto ASC""", "inicio", ini, "fin", fin);
                } catch (Exception e) {
                    log.error("Error en cumplimiento excel: {}", e.getMessage());
                }
                if (datos.isEmpty() && excel.isEmpty()) mensaje = "No se encontraron datos en la base de datos.";
            } else {
                mensaje = "Por favor selecciona el rango de fechas.";
            }
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Historial de Mantenimientos por Punto");
        r.put("filtros", filtros);
        r.put("datosReporte", datos);
        r.put("datosExcel", excel);
        r.put("mensaje", mensaje);
        return r;
    }

    // ================================================================ USO DE REPUESTOS

    @RequestMapping(value = "/api/reporteRepuesto", params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> reporteRepuesto(Req req) {
        Map<String, Object> filtros = new LinkedHashMap<>(Map.of("origen", "", "fecha_inicio", primeroMes(), "fecha_fin", F.hoy()));
        List<Map<String, Object>> datos = List.of(), inees = List.of();
        long total = 0;
        String mensaje = "";
        if (req.isPost()) {
            String origen = req.str("origen", ""), ini = req.str("fecha_inicio", ""), fin = req.str("fecha_fin", "");
            filtros.put("origen", origen);
            filtros.put("fecha_inicio", ini);
            filtros.put("fecha_fin", fin);
            if (!ini.isEmpty() && !fin.isEmpty()) {
                Map<String, Object> p = new LinkedHashMap<>(Map.of("inicio", ini, "fin", fin));
                String sql = """
                        SELECT
                            r.codigo_referencia,
                            r.nombre_repuesto,
                            osr.origen,
                            SUM(osr.cantidad) as total_cantidad,
                            COUNT(os.id_ordenes_servicio) as veces_usado
                        FROM orden_servicio_repuesto osr
                        INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                        INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                        WHERE os.fecha_visita BETWEEN :inicio AND :fin""";
                if (!origen.isEmpty()) {
                    sql += " AND osr.origen = :origen";
                    p.put("origen", origen);
                }
                sql += " GROUP BY r.id_repuesto, osr.origen ORDER BY total_cantidad DESC";
                try {
                    datos = db.list(sql, p);
                } catch (Exception e) {
                    log.error("Error reporte agrupado: {}", e.getMessage());
                }
                try {
                    inees = db.list("""
                            SELECT
                                c.nombre_cliente,
                                p.nombre_punto,
                                d.nombre_delegacion,
                                m.device_id,
                                tm.nombre_tipo_maquina,
                                os.numero_remision,
                                os.actividades_realizadas as observacion,
                                r.nombre_repuesto,
                                r.codigo_referencia,
                                osr.cantidad,
                                os.fecha_visita
                            FROM orden_servicio_repuesto osr
                            INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                            INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                            INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                            INNER JOIN punto p ON os.id_punto = p.id_punto
                            INNER JOIN maquina m ON os.id_maquina = m.id_maquina
                            LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                            LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                            WHERE osr.origen = 'INEES'
                            AND os.fecha_visita BETWEEN :inicio AND :fin
                            ORDER BY os.fecha_visita DESC, c.nombre_cliente ASC""", "inicio", ini, "fin", fin);
                } catch (Exception e) {
                    log.error("Error reporte detalle INEES: {}", e.getMessage());
                }
                for (var row : datos) total += Conv.toLong(row.get("total_cantidad"));
                if (datos.isEmpty()) mensaje = "No se encontraron repuestos usados en ese rango de fechas.";
            } else {
                mensaje = "Por favor selecciona el rango de fechas.";
            }
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Reporte de Uso de Repuestos");
        r.put("filtros", filtros);
        r.put("datosReporte", datos);
        r.put("datosInees", inees);
        r.put("totalPiezas", total);
        r.put("mensaje", mensaje);
        return r;
    }

    // ================================================================ INVENTARIO EN CALLE (PDF)

    @GetMapping(value = "/api/reporteRepuestos")
    public ResponseEntity<?> reporteRepuestos(Req req) {
        String desde = req.str("fecha_desde", "");
        String hasta = req.str("fecha_hasta", "");
        if (!desde.matches("\\d{4}-\\d{2}-\\d{2}")) desde = null;
        if (!hasta.matches("\\d{4}-\\d{2}-\\d{2}")) hasta = null;
        Map<String, Object> p = new LinkedHashMap<>();
        String filtro = "";
        if (desde != null) {
            filtro += " AND i.ultima_actualizacion >= :fecha_desde";
            p.put("fecha_desde", desde);
        }
        if (hasta != null) {
            filtro += " AND i.ultima_actualizacion <= :fecha_hasta";
            p.put("fecha_hasta", hasta);
        }
        try {
            Map<String, Object> kpis = db.one("""
                    SELECT
                        COALESCE(SUM(i.cantidad_actual), 0) as total_piezas,
                        COALESCE(COUNT(DISTINCT i.id_tecnico), 0) as tecnicos_con_stock,
                        COALESCE(COUNT(DISTINCT i.id_repuesto), 0) as referencias_distintas,
                        COALESCE(SUM(CASE WHEN i.cantidad_actual = 0 THEN 1 ELSE 0 END), 0) as repuestos_agotados
                    FROM inventario_tecnico i
                    WHERE i.estado = 1""" + filtro, p);
            List<Map<String, Object>> consolidado = db.list("""
                    SELECT
                        r.nombre_repuesto,
                        r.codigo_referencia,
                        COUNT(DISTINCT i.id_tecnico) as tecnicos_lo_tienen,
                        SUM(i.cantidad_actual) as total_asignado
                    FROM inventario_tecnico i
                    INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                    WHERE i.estado = 1""" + filtro + " GROUP BY r.id_repuesto ORDER BY total_asignado DESC, r.nombre_repuesto ASC", p);
            Map<String, List<Map<String, Object>>> porTecnico = new LinkedHashMap<>();
            db.list("""
                    SELECT
                        t.nombre_tecnico,
                        r.nombre_repuesto,
                        r.codigo_referencia,
                        i.cantidad_actual
                    FROM inventario_tecnico i
                    INNER JOIN tecnico t ON i.id_tecnico = t.id_tecnico
                    INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                    WHERE i.estado = 1""" + filtro + " ORDER BY t.nombre_tecnico ASC, r.nombre_repuesto ASC", p)
                    .forEach(f -> porTecnico.computeIfAbsent(Conv.str(f.get("nombre_tecnico")), k -> new ArrayList<>()).add(f));

            String fechaReporte = desde != null && hasta != null ? "Desde " + F.dmy(desde) + " hasta " + F.dmy(hasta)
                    : LocalDateTime.now().format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm"));
            String html = PlantillaRepuestosPdf.render(kpis == null ? Map.of() : kpis, consolidado, porTecnico, fechaReporte, pdf.dataUri("app/logos/logoInees.jpg"));
            byte[] contenido = pdf.generar(html);
            String nombre = "Reporte_Repuestos_" + LocalDateTime.now().format(DateTimeFormatter.ofPattern("dd-MM-yyyy_HH-mm")) + ".pdf";
            return Descarga.pdf(contenido, nombre, true);
        } catch (Exception e) {
            return Descarga.html("<h1>Error generando PDF de Repuestos</h1><p>" + Conv.h(e.getMessage()) + "</p>");
        }
    }

    // ================================================================ TRAZABILIDAD

    private static String validarFecha(String f, String fallback) {
        return f != null && f.matches("\\d{4}-\\d{2}-\\d{2}") ? f : fallback;
    }

    private List<Map<String, Object>> trazabilidad(String ini, String fin) {
        try {
            return db.list("""
                    SELECT
                        c.nombre_cliente       AS cliente,
                        p.nombre_punto         AS punto,
                        tm.nombre_tipo_maquina AS tipo_maquina,
                        m.device_id            AS device_id,
                        r.nombre_repuesto      AS repuesto,
                        osr.origen             AS origen,
                        osr.cantidad           AS cantidad,
                        os.fecha_visita        AS fecha_cambio
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os ON os.id_ordenes_servicio = osr.id_orden_servicio
                    INNER JOIN repuesto r         ON r.id_repuesto = osr.id_repuesto
                    INNER JOIN maquina m          ON m.id_maquina = os.id_maquina
                    INNER JOIN tipo_maquina tm    ON tm.id_tipo_maquina = m.id_tipo_maquina
                    INNER JOIN punto p            ON p.id_punto = os.id_punto
                    INNER JOIN cliente c          ON c.id_cliente = os.id_cliente
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    ORDER BY p.nombre_punto ASC, os.fecha_visita DESC""", "inicio", ini, "fin", fin);
        } catch (Exception e) {
            log.error("Error ReporteTrazabilidad Hoja1: {}", e.getMessage());
            return List.of();
        }
    }

    private List<Map<String, Object>> topPorTipo(String ini, String fin) {
        try {
            return db.list("""
                    SELECT
                        tm.nombre_tipo_maquina AS tipo_maquina,
                        r.nombre_repuesto      AS repuesto,
                        osr.origen             AS origen,
                        SUM(osr.cantidad)      AS total_cambiados
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os ON os.id_ordenes_servicio = osr.id_orden_servicio
                    INNER JOIN repuesto r         ON r.id_repuesto = osr.id_repuesto
                    INNER JOIN maquina m          ON m.id_maquina = os.id_maquina
                    INNER JOIN tipo_maquina tm    ON tm.id_tipo_maquina = m.id_tipo_maquina
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY tm.nombre_tipo_maquina, r.nombre_repuesto, osr.origen
                    ORDER BY tm.nombre_tipo_maquina ASC, total_cambiados DESC""", "inicio", ini, "fin", fin);
        } catch (Exception e) {
            log.error("Error ReporteTrazabilidad Hoja2: {}", e.getMessage());
            return List.of();
        }
    }

    @RequestMapping(value = "/api/reporteTrazabilidad", params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> reporteTrazabilidad(Req req) {
        Map<String, Object> filtros = new LinkedHashMap<>(Map.of("fecha_inicio", primeroMes(), "fecha_fin", F.hoy()));
        List<Map<String, Object>> det = List.of(), top = List.of();
        long total = 0;
        String mensaje = "";
        if (req.isPost()) {
            filtros.put("fecha_inicio", validarFecha(req.str("fecha_inicio", ""), Conv.str(filtros.get("fecha_inicio"))));
            filtros.put("fecha_fin", validarFecha(req.str("fecha_fin", ""), Conv.str(filtros.get("fecha_fin"))));
            det = trazabilidad(Conv.str(filtros.get("fecha_inicio")), Conv.str(filtros.get("fecha_fin")));
            top = topPorTipo(Conv.str(filtros.get("fecha_inicio")), Conv.str(filtros.get("fecha_fin")));
            for (var row : det) total += Conv.toInt(row.get("cantidad"));
            if (det.isEmpty() && top.isEmpty()) mensaje = "No se encontraron repuestos suministrados en ese rango de fechas.";
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Trazabilidad de Repuestos Suministrados");
        r.put("filtros", filtros);
        r.put("datosTrazabilidad", det);
        r.put("datosTop", top);
        r.put("totalPiezas", total);
        r.put("mensaje", mensaje);
        return r;
    }

    @GetMapping(value = "/api/reporteTrazabilidad", params = "accion=descargarExcel")
    public ResponseEntity<byte[]> descargarExcel(Req req) {
        String ini = validarFecha(req.str("fecha_inicio", ""), primeroMes());
        String fin = validarFecha(req.str("fecha_fin", ""), F.hoy());
        List<Map<String, Object>> det = trazabilidad(ini, fin), top = topPorTipo(ini, fin);
        Excel x = new Excel("Trazabilidad Punto");
        String[] h1 = { "Cliente", "Punto", "Tipo de Maquina", "Device ID", "Nombre del Repuesto", "Origen del Repuesto", "Cantidad", "Fecha de Cambio" };
        for (int i = 0; i < h1.length; i++) x.set(i + 1, 1, h1[i]);
        estiloHead(x, "A1:H1");
        int f = 2;
        for (var d : det) {
            x.set("A" + f, d.get("cliente")).set("B" + f, d.get("punto")).set("C" + f, d.get("tipo_maquina")).set("D" + f, d.get("device_id"))
                    .set("E" + f, d.get("repuesto")).set("F" + f, d.get("origen")).set("G" + f, Conv.toInt(d.get("cantidad"))).set("H" + f, d.get("fecha_cambio"));
            x.estilo("H" + f, Excel.E.of().formato("yyyy-mm-dd"));
            f++;
        }
        ajustes(x, "A1:H" + Math.max(f - 1, 1), f - 1, new String[] { "A", "B", "C", "D", "E", "F", "G", "H" }, new double[] { 30, 30, 24, 16, 35, 16, 12, 16 });
        x.nuevaHoja("Top Repuestos Tipo Maq");
        String[] h2 = { "Tipo de Maquina", "Nombre del Repuesto", "Origen del Repuesto", "Total Cambiados" };
        for (int i = 0; i < h2.length; i++) x.set(i + 1, 1, h2[i]);
        estiloHead(x, "A1:D1");
        int f2 = 2;
        for (var t : top) {
            x.set("A" + f2, t.get("tipo_maquina")).set("B" + f2, t.get("repuesto")).set("C" + f2, t.get("origen")).set("D" + f2, Conv.toInt(t.get("total_cambiados")));
            f2++;
        }
        ajustes(x, "A1:D" + Math.max(f2 - 1, 1), f2 - 1, new String[] { "A", "B", "C", "D" }, new double[] { 28, 40, 18, 17 });
        x.usarHoja(0);
        x.wb().setActiveSheet(0);
        return Descarga.excel(x.bytes(), "Trazabilidad_Repuestos_" + ini + "_al_" + fin + ".xlsx");
    }

    private static void estiloHead(Excel x, String rango) {
        x.estilo(rango, Excel.E.of().bold().color("FFFFFF").size(11).fill("1F4E78").center().wrap().borde("thin").bordeColor("B0B0B0"));
        x.alto(1, 30);
    }

    private static void ajustes(Excel x, String rango, int ultimaFila, String[] cols, double[] anchos) {
        for (int i = 0; i < cols.length; i++) x.ancho(cols[i], anchos[i]);
        if (ultimaFila > 1) x.autoFiltro(rango);
        x.congelar("A2");
        x.estilo(rango, Excel.E.of().borde("thin").bordeColor("D9D9D9").v("center"));
    }
}
