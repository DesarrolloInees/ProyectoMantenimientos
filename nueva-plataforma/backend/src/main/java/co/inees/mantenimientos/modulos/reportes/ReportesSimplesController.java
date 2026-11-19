package co.inees.mantenimientos.modulos.reportes;

import java.time.LocalDate;
import java.time.YearMonth;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/**
 * Reportes pequeños:
 * remisionesPendientesControlador.php (+ controlRemisionVerModelo::obtenerSalteadasSandwich),
 * reporteDevolucionControlador.php, reporteMaquinasControlador.php, reporteTarifasControlador.php
 */
@RestController
public class ReportesSimplesController {

    private static final Logger log = LoggerFactory.getLogger(ReportesSimplesController.class);
    private final Db db;

    public ReportesSimplesController(Db db) {
        this.db = db;
    }

    // ================================================================ REMISIONES SALTEADAS

    @GetMapping(value = "/api/remisionesPendientes", params = "accion=index")
    public Map<String, Object> remisionesPendientes() {
        return Map.of("titulo", "Remisiones Salteadas", "pendientes", salteadasSandwich());
    }

    @RequestMapping(value = "/api/remisionesPendientes", params = "accion=cambiarEstado", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> cambiarEstado(Req req) {
        if (req.has("id") && req.has("estado")) {
            String estado = req.str("estado");
            if (List.of("ANULADA", "USADA", "FALTANTE", "ELIMINADO", "DISPONIBLE").contains(estado)) {
                try {
                    db.update("UPDATE control_remisiones SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = :estado LIMIT 1) WHERE id_control = :id",
                            "estado", estado, "id", req.str("id"));
                } catch (Exception e) {
                    log.error("actualizarEstadoRapido: {}", e.getMessage());
                }
            }
        }
        return Map.of("status", true, "redirect", "remisionesPendientes");
    }

    private List<Map<String, Object>> salteadasSandwich() {
        List<Map<String, Object>> todas = db.list("""
                SELECT
                    cr.id_control,
                    cr.numero_remision,
                    e.nombre_estado,
                    cr.fecha_asignacion,
                    cr.id_tecnico,
                    t.nombre_tecnico
                FROM control_remisiones cr
                INNER JOIN tecnico t ON cr.id_tecnico = t.id_tecnico
                INNER JOIN estados_remision e ON cr.id_estado = e.id_estado
                WHERE e.nombre_estado IN ('USADA', 'DISPONIBLE')
                ORDER BY cr.id_tecnico ASC, CAST(cr.numero_remision AS UNSIGNED) ASC""");
        Map<String, List<Map<String, Object>>> porTec = new LinkedHashMap<>();
        todas.forEach(r -> porTec.computeIfAbsent(Conv.str(r.get("id_tecnico")), k -> new ArrayList<>()).add(r));
        List<Map<String, Object>> det = new ArrayList<>();
        for (var rems : porTec.values()) {
            int total = rems.size();
            if (total < 3) continue;
            for (int i = 0; i < total - 2; i++) {
                var r0 = rems.get(i);
                var r1 = rems.get(i + 1);
                var r2 = rems.get(i + 2);
                if (est(r0, "USADA") && est(r1, "DISPONIBLE") && est(r2, "USADA")) {
                    long n0 = num(r0), n1 = num(r1), n2 = num(r2);
                    if (n1 == n0 + 1 && n2 == n1 + 1) det.add(formato(r1, r0.get("numero_remision"), r2.get("numero_remision")));
                }
                if (i < total - 3) {
                    var r3 = rems.get(i + 3);
                    if (est(r0, "USADA") && est(r1, "DISPONIBLE") && est(r2, "DISPONIBLE") && est(r3, "USADA")) {
                        long n0 = num(r0), n1 = num(r1), n2 = num(r2), n3 = num(r3);
                        if (n1 == n0 + 1 && n2 == n1 + 1 && n3 == n2 + 1) {
                            det.add(formato(r1, r0.get("numero_remision"), r3.get("numero_remision")));
                            det.add(formato(r2, r0.get("numero_remision"), r3.get("numero_remision")));
                        }
                    }
                }
            }
        }
        return det;
    }

    private static boolean est(Map<String, Object> r, String e) {
        return e.equals(r.get("nombre_estado"));
    }

    /** intval() de PHP: dígitos iniciales */
    private static long num(Map<String, Object> r) {
        String s = Conv.str(r.get("numero_remision")).trim();
        int i = 0;
        if (i < s.length() && (s.charAt(i) == '-' || s.charAt(i) == '+')) i++;
        int ini = i;
        while (i < s.length() && Character.isDigit(s.charAt(i))) i++;
        if (i == ini) return 0;
        try {
            return Long.parseLong(s.substring(0, i));
        } catch (Exception e) {
            return 0;
        }
    }

    private static Map<String, Object> formato(Map<String, Object> a, Object anterior, Object siguiente) {
        Map<String, Object> m = new LinkedHashMap<>();
        m.put("id_control", a.get("id_control"));
        m.put("numero_remision", a.get("numero_remision"));
        m.put("nombre_estado", a.get("nombre_estado"));
        m.put("fecha_asignacion", a.get("fecha_asignacion"));
        m.put("nombre_tecnico", a.get("nombre_tecnico"));
        m.put("anterior", anterior);
        m.put("siguiente", siguiente);
        return m;
    }

    // ================================================================ DEVOLUCIONES

    @RequestMapping(value = "/api/reporteDevolucion", params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> reporteDevolucion(Req req) {
        Map<String, Object> filtros = new LinkedHashMap<>();
        filtros.put("id_tecnico", "");
        filtros.put("fecha_inicio", F.hoy().substring(0, 8) + "01");
        filtros.put("fecha_fin", F.hoy());
        List<Map<String, Object>> datos = List.of();
        if (req.isPost()) {
            filtros.put("id_tecnico", req.str("id_tecnico", ""));
            filtros.put("fecha_inicio", req.str("fecha_inicio", ""));
            filtros.put("fecha_fin", req.str("fecha_fin", ""));
            Map<String, Object> p = new LinkedHashMap<>();
            String sql = """
                    SELECT
                        t.nombre_tecnico,
                        r.nombre_repuesto,
                        r.codigo_referencia,
                        osr.cantidad,
                        os.fecha_visita,
                        os.numero_remision,
                        c.nombre_cliente,
                        p.nombre_punto,
                        d.nombre_delegacion
                    FROM orden_servicio_repuesto osr
                    INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE r.requiere_devolucion = 1""";
            if (!Conv.empty(filtros.get("id_tecnico"))) {
                sql += " AND os.id_tecnico = :id_tecnico";
                p.put("id_tecnico", filtros.get("id_tecnico"));
            }
            if (!Conv.empty(filtros.get("fecha_inicio")) && !Conv.empty(filtros.get("fecha_fin"))) {
                sql += " AND os.fecha_visita BETWEEN :inicio AND :fin";
                p.put("inicio", filtros.get("fecha_inicio"));
                p.put("fin", filtros.get("fecha_fin"));
            }
            sql += " ORDER BY os.fecha_visita DESC, t.nombre_tecnico ASC";
            try {
                datos = db.list(sql, p);
            } catch (Exception e) {
                log.error("Error en ReporteDevolucionModelo: {}", e.getMessage());
            }
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Control de Devolución de Repuestos");
        r.put("tecnicos", db.list("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
        r.put("filtros", filtros);
        r.put("datosDevoluciones", datos);
        return r;
    }

    // ================================================================ MÁQUINAS POR TIPO

    @RequestMapping(value = "/api/reporteMaquinas", params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> reporteMaquinas(Req req) {
        String idTipo = "";
        List<Map<String, Object>> datos = List.of();
        if (req.isPost()) {
            idTipo = req.str("id_tipo_maquina", "");
            if (!idTipo.isEmpty()) {
                try {
                    datos = db.list("""
                            SELECT
                                d.nombre_delegacion,
                                m.device_id,
                                tm.nombre_tipo_maquina,
                                p.nombre_punto,
                                p.direccion,
                                p.fecha_ultima_visita
                            FROM maquina m
                            INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                            INNER JOIN punto p ON m.id_punto = p.id_punto
                            LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                            WHERE m.id_tipo_maquina = :id_tipo
                            ORDER BY d.nombre_delegacion ASC, p.nombre_punto ASC""", "id_tipo", idTipo);
                } catch (Exception e) {
                    log.error("Error Reporte Maquinas: {}", e.getMessage());
                }
            }
        }
        List<Map<String, Object>> tipos;
        try {
            tipos = db.list("SELECT id_tipo_maquina, nombre_tipo_maquina FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC");
        } catch (Exception e) {
            tipos = List.of();
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Reporte por Tipo de Máquina");
        r.put("listaTipos", tipos);
        r.put("idTipoSeleccionado", idTipo);
        r.put("datosMaquinas", datos);
        return r;
    }

    // ================================================================ TARIFAS (PORCENTAJES)

    @GetMapping(value = "/api/reporteTarifas", params = "accion=index")
    public Map<String, Object> reporteTarifas() {
        return Map.of("titulo", "Reporte de Tarifas Mensuales");
    }

    @RequestMapping(value = "/api/reporteTarifas", params = "accion=descargarReportePorcentajes", method = { RequestMethod.GET, RequestMethod.POST })
    public Object descargarReportePorcentajes(Req req) {
        if (!req.sesion().has("usuario_id")) return Map.of("error", "Acceso denegado");
        LocalDate hoy = LocalDate.now();
        String ini = !Conv.empty(req.get("fecha_inicio")) ? req.str("fecha_inicio") : hoy.withDayOfMonth(1).toString();
        String fin = !Conv.empty(req.get("fecha_fin")) ? req.str("fecha_fin") : YearMonth.from(hoy).atEndOfMonth().toString();
        try {
            return db.list("""
                    SELECT
                        CASE
                            WHEN tmq.id_tipo_maquina IN (9, 15, 45) THEN 'Grandes'
                            ELSE 'Pequeñas'
                        END AS categoria_tamano,
                        tmq.nombre_tipo_maquina,
                        tm.nombre_completo AS tipo_mantenimiento,
                        COUNT(os.id_ordenes_servicio) AS cantidad_servicios,
                        SUM(os.valor_servicio) AS precio_total
                    FROM ordenes_servicio os
                    INNER JOIN maquina m ON os.id_maquina = m.id_maquina
                    INNER JOIN tipo_maquina tmq ON m.id_tipo_maquina = tmq.id_tipo_maquina
                    INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.estado = 1
                        AND tm.nombre_completo NOT LIKE '%Instalacion%'
                        AND tm.nombre_completo NOT LIKE '%Garantia%'
                        AND os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY categoria_tamano, tmq.id_tipo_maquina, tm.id_tipo_mantenimiento
                    ORDER BY categoria_tamano ASC, tmq.nombre_tipo_maquina ASC, precio_total DESC""", "inicio", ini, "fin", fin);
        } catch (Exception e) {
            log.error("Error en exportación de porcentajes: {}", e.getMessage());
            return List.of();
        }
    }
}
