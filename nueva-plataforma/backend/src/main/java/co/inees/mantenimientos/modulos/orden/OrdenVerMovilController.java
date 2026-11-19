package co.inees.mantenimientos.modulos.orden;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;
import co.inees.mantenimientos.core.Sesion;

/** Port de ordenVerControlador/ordenVerModelo y ordenMovilControlador/ordenMovilModelo. */
@RestController
@RequestMapping("/api")
public class OrdenVerMovilController {

    private final Db db;

    public OrdenVerMovilController(Db db) {
        this.db = db;
    }

    // ================================================================ ordenVer

    @GetMapping(value = "/ordenVer", params = "accion=index")
    public Map<String, Object> ordenVer(Sesion s) {
        return Map.of("titulo", "Historial por Días", "rolUsuario", s.nivel());
    }

    @PostMapping(value = "/ordenVer", params = "accion=ajaxListar")
    public Map<String, Object> ajaxListar(Sesion s) {
        List<Map<String, Object>> filas;
        try {
            filas = db.list("""
                    SELECT
                        o.fecha_visita,
                        COALESCE(d.nombre_delegacion, 'SIN ASIGNAR') as nombre_delegacion,
                        COUNT(o.id_ordenes_servicio) as cantidad,
                        IFNULL(SUM(o.valor_servicio), 0) as valor,
                        COUNT(DISTINCT o.id_tecnico) as cantidad_tecnicos
                    FROM ordenes_servicio o
                    LEFT JOIN punto p ON o.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE o.estado = 1
                    GROUP BY o.fecha_visita, d.nombre_delegacion
                    ORDER BY o.fecha_visita DESC, d.nombre_delegacion ASC""");
        } catch (Exception e) {
            filas = List.of();
        }
        Map<String, Map<String, Object>> porDia = new LinkedHashMap<>();
        for (Map<String, Object> row : filas) {
            String fecha = Conv.str(row.get("fecha_visita"));
            Map<String, Object> dia = porDia.computeIfAbsent(fecha, f -> {
                Map<String, Object> m = new LinkedHashMap<>();
                m.put("fecha_visita", f);
                m.put("cantidad_total", 0L);
                m.put("valor_total_dia", 0.0);
                m.put("detalles_delegacion", new ArrayList<Map<String, Object>>());
                return m;
            });
            dia.put("cantidad_total", Conv.toLong(dia.get("cantidad_total")) + Conv.toLong(row.get("cantidad")));
            dia.put("valor_total_dia", Conv.toDouble(dia.get("valor_total_dia")) + Conv.toDouble(row.get("valor")));
            Map<String, Object> det = new LinkedHashMap<>();
            det.put("nombre", row.get("nombre_delegacion"));
            det.put("cant", row.get("cantidad"));
            det.put("valor", row.get("valor"));
            det.put("num_tecnicos", row.get("cantidad_tecnicos"));
            @SuppressWarnings("unchecked")
            List<Map<String, Object>> l = (List<Map<String, Object>>) dia.get("detalles_delegacion");
            l.add(det);
        }
        List<Map<String, Object>> datos = new ArrayList<>(porDia.values());
        if (s.nivel() == 5) {
            for (Map<String, Object> dia : datos) {
                dia.put("valor_total_dia", 0);
                @SuppressWarnings("unchecked")
                List<Map<String, Object>> l = (List<Map<String, Object>>) dia.get("detalles_delegacion");
                for (Map<String, Object> d : l) d.put("valor", 0);
            }
        }
        return Map.of("data", datos);
    }

    // ================================================================ ordenMovil

    @GetMapping(value = "/ordenMovil", params = "accion=index")
    public Map<String, Object> ordenMovil() {
        return Map.of("listaClientes", db.list("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC"));
    }

    @PostMapping(value = "/ordenMovil", params = "accion=cargarPuntos")
    public List<Map<String, Object>> cargarPuntos(Req req) {
        return db.list("SELECT id_punto, nombre_punto FROM punto WHERE id_cliente = :c AND estado = 1 ORDER BY nombre_punto ASC",
                "c", req.intv("id_cliente"));
    }

    @PostMapping(value = "/ordenMovil", params = "accion=buscarHistorial")
    public List<Map<String, Object>> buscarHistorial(Req req) {
        int idCliente = req.intv("id_cliente");
        int idPunto = req.intv("id_punto");
        List<Map<String, Object>> r = db.list("""
                SELECT
                    o.id_ordenes_servicio,
                    o.fecha_visita,
                    o.actividades_realizadas as que_se_hizo,
                    t.nombre_tecnico,
                    tm.nombre_completo as tipo_servicio,
                    (
                        SELECT GROUP_CONCAT(
                            CONCAT(r.nombre_repuesto, ' (x', osr.cantidad, ')')
                            SEPARATOR ', '
                        )
                        FROM orden_servicio_repuesto osr
                        JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                        WHERE osr.id_orden_servicio = o.id_ordenes_servicio
                    ) as repuestos
                FROM ordenes_servicio o
                LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                LEFT JOIN tipo_mantenimiento tm ON o.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                WHERE (o.id_punto = :p1 OR m.id_punto = :p2)
                AND (o.id_cliente = :c)
                AND o.id_tipo_mantenimiento NOT IN (4, 5)
                ORDER BY o.fecha_visita DESC
                LIMIT 20""", "p1", idPunto, "p2", idPunto, "c", idCliente);
        for (Map<String, Object> row : r) {
            String tipo = Conv.str(row.get("tipo_servicio")).toUpperCase();
            String borde = "border-blue-500";
            if (tipo.contains("CORRECTIVO")) borde = "border-orange-500";
            if (tipo.contains("PREVENTIVO")) borde = "border-green-500";
            if (tipo.contains("FALLIDO")) borde = "border-red-500";
            row.put("color_borde", borde);
        }
        return r;
    }
}
