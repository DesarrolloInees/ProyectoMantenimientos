package co.inees.mantenimientos.modulos.orden;

import java.util.List;
import java.util.Map;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/** Port de ordenReporteControlador.php + ordenReporteModelo.php */
@RestController
@RequestMapping("/api/ordenReporte")
public class OrdenReporteController {

    private final Db db;

    public OrdenReporteController(Db db) {
        this.db = db;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> cargarVista() {
        return Map.of("titulo", "Generador de Reportes");
    }

    @PostMapping(params = "accion=ajaxDescargarServicios")
    public Map<String, Object> ajaxDescargarServicios(Req req) {
        String inicio = req.str("fecha_inicio", F.hoy());
        String fin = req.str("fecha_fin", F.hoy());
        String tipoMant = req.str("tipo_mantenimiento", "todos");
        if (inicio.compareTo(fin) > 0) return Map.of("status", "error", "msg", "Fechas incorrectas.");
        try {
            return Map.of("status", "ok", "datos", obtenerServiciosPorRango(inicio, fin, tipoMant));
        } catch (Exception e) {
            return Map.of("status", "error", "msg", "Error: " + e.getMessage());
        }
    }

    @PostMapping(params = "accion=ajaxDescargarNovedades")
    public Map<String, Object> ajaxDescargarNovedades(Req req) {
        String inicio = req.str("fecha_inicio", F.hoy());
        String fin = req.str("fecha_fin", F.hoy());
        if (inicio.compareTo(fin) > 0) return Map.of("status", "error", "msg", "Fechas incorrectas.");
        try {
            return Map.of("status", "ok", "datos", obtenerNovedadesPorRango(inicio, fin));
        } catch (Exception e) {
            return Map.of("status", "error", "msg", "Error: " + e.getMessage());
        }
    }

    /** ordenReporteModelo::obtenerServiciosPorRango (también lo usa reporteTecnico) */
    public List<Map<String, Object>> obtenerServiciosPorRango(String inicio, String fin, String tipoMant) {
        String filtro = switch (tipoMant) {
            case "basico" -> " AND (LOWER(tman.nombre_completo) LIKE '%basico%' OR LOWER(tman.nombre_completo) LIKE '%básico%' OR (LOWER(tman.nombre_completo) LIKE '%preventivo%' AND LOWER(tman.nombre_completo) NOT LIKE '%profundo%' AND LOWER(tman.nombre_completo) NOT LIKE '%completo%'))";
            case "profundo" -> " AND (LOWER(tman.nombre_completo) LIKE '%profundo%' OR LOWER(tman.nombre_completo) LIKE '%completo%')";
            case "correctivo" -> " AND (LOWER(tman.nombre_completo) LIKE '%correctivo%' OR LOWER(tman.nombre_completo) LIKE '%reparacion%')";
            case "fallido" -> " AND (LOWER(tman.nombre_completo) LIKE '%fallido%' OR LOWER(tman.nombre_completo) LIKE '%fallido%')";
            case "garantia" -> " AND (LOWER(tman.nombre_completo) LIKE '%garantia%' OR LOWER(tman.nombre_completo) LIKE '%garantia%')";
            default -> "";
        };
        String sql = """
                SELECT
                    o.id_ordenes_servicio, o.numero_remision, o.fecha_visita,
                    o.hora_entrada, o.hora_salida, o.tiempo_servicio, o.valor_servicio,
                    (SELECT MAX(o2.valor_viaticos)
                        FROM ordenes_servicio o2
                        WHERE o2.id_tecnico = o.id_tecnico
                        AND o2.fecha_visita = o.fecha_visita) as valor_viaticos,
                        o.actividades_realizadas as que_se_hizo,
                    tman.nombre_completo as txt_servicio,
                    m.device_id, tm.nombre_tipo_maquina,
                    COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente) as nombre_cliente,
                    COALESCE(p_directo.nombre_punto, p_maq.nombre_punto) as nombre_punto,
                    COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion) as delegacion,
                    CASE
                        WHEN o.id_modalidad = 1 THEN 'URBANO'
                        WHEN o.id_modalidad = 2 THEN 'INTERURBANO'
                        ELSE 'NO DEFINIDO'
                    END as tipo_zona,
                    t.nombre_tecnico,
                    tman.nombre_completo as tipo_servicio,
                    em.nombre_estado as estado_maquina, cal.nombre_calificacion,
                    IFNULL(
                        (SELECT GROUP_CONCAT(tn.nombre_novedad SEPARATOR ', ')
                        FROM orden_servicio_novedad osn
                        JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                        WHERE osn.id_orden_servicio = o.id_ordenes_servicio)
                    , '') as nombres_novedades,
                    IFNULL(
                        (SELECT GROUP_CONCAT(
                            CONCAT(r.nombre_repuesto, ' (', osr.origen, ')', IF(osr.cantidad>1, CONCAT(' x', osr.cantidad), ''))
                            SEPARATOR ', ')
                        FROM orden_servicio_repuesto osr
                        JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                        WHERE osr.id_orden_servicio = o.id_ordenes_servicio)
                    , '') as repuestos_texto
                FROM ordenes_servicio o
                    LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                    LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                    LEFT JOIN tipo_mantenimiento tman ON o.id_tipo_mantenimiento = tman.id_tipo_mantenimiento
                    LEFT JOIN estado_maquina em ON o.id_estado_maquina = em.id_estado
                    LEFT JOIN calificacion_servicio cal ON o.id_calificacion = cal.id_calificacion
                    LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
                    LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
                    LEFT JOIN delegacion d_maq ON p_maq.id_delegacion = d_maq.id_delegacion
                    LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
                    LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
                    LEFT JOIN delegacion d_directo ON p_directo.id_delegacion = d_directo.id_delegacion
                WHERE o.fecha_visita BETWEEN :ini AND :fin
                    AND o.estado = 1
                """ + filtro + """

                    ORDER BY t.nombre_tecnico ASC, o.fecha_visita ASC, o.hora_entrada ASC""";
        return db.list(sql, "ini", inicio, "fin", fin);
    }

    private Object obtenerNovedadesPorRango(String inicio, String fin) {
        return db.list("""
                SELECT
                    GROUP_CONCAT(tn.nombre_novedad SEPARATOR ', ') as nombre_novedad,
                    o.actividades_realizadas as observacion,
                    o.fecha_visita,
                    o.numero_remision,
                    m.device_id,
                    tm.nombre_tipo_maquina,
                    t.nombre_tecnico,
                    COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente) as nombre_cliente,
                    COALESCE(p_directo.nombre_punto, p_maq.nombre_punto) as nombre_punto,
                    COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion) as delegacion
                FROM ordenes_servicio o
                    INNER JOIN orden_servicio_novedad osn ON o.id_ordenes_servicio = osn.id_orden_servicio
                    INNER JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                    LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                    LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
                    LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
                    LEFT JOIN delegacion d_maq ON p_maq.id_delegacion = d_maq.id_delegacion
                    LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
                    LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
                    LEFT JOIN delegacion d_directo ON p_directo.id_delegacion = d_directo.id_delegacion
                WHERE o.fecha_visita BETWEEN :ini AND :fin
                    AND o.estado = 1
                    GROUP BY o.id_ordenes_servicio
                ORDER BY o.fecha_visita ASC, t.nombre_tecnico ASC""", "ini", inicio, "fin", fin);
    }
}
