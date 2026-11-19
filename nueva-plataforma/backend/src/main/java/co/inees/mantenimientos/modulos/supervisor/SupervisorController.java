package co.inees.mantenimientos.modulos.supervisor;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
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
import co.inees.mantenimientos.core.Correo;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/**
 * Port de panelSupervisorControlador.php y notificacionesLogisticaControlador.php (con sus modelos).
 * (app/controllers/api/ajaxServiciosSupervisor.php no está en la tabla `rutas` y ninguna vista lo usa.)
 */
@RestController
public class SupervisorController {

    private static final Logger log = LoggerFactory.getLogger(SupervisorController.class);
    private static final List<String> SUPERVISORES = List.of("supervisorsat@inees.co", "operaciones@inees.co");
    private final Db db;
    private final Correo correo;

    public SupervisorController(Db db, Correo correo) {
        this.db = db;
        this.correo = correo;
    }

    private List<Map<String, Object>> lista(String sql, Object... kv) {
        try {
            return db.list(sql, kv);
        } catch (Exception e) {
            log.error("{}", e.getMessage());
            return List.of();
        }
    }

    // ================================================================ PANEL SUPERVISOR

    @GetMapping(value = "/api/panelSupervisor", params = "accion=index")
    public Map<String, Object> panel() {
        return Map.of("titulo", "Panel de Supervisor - Tiempo Real", "hoy", LocalDate.now().toString());
    }

    @RequestMapping(value = "/api/panelSupervisor", params = "accion=ajaxObtenerServicios", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> servicios() {
        return Map.of("success", true, "data", lista("""
                SELECT
                    o.id_ordenes_servicio,
                    o.numero_remision,
                    o.fecha_visita,
                    o.id_estado_maquina,
                    COALESCE(t.nombre_tecnico, 'Sin Asignar') as nombre_tecnico,
                    COALESCE(c.nombre_cliente, 'SIN CLIENTE') as nombre_cliente,
                    COALESCE(p.nombre_punto, 'SIN PUNTO') as nombre_punto
                FROM ordenes_servicio o
                LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                LEFT JOIN cliente c ON o.id_cliente = c.id_cliente
                LEFT JOIN punto p ON o.id_punto = p.id_punto
                WHERE DATE(o.fecha_visita) = CURDATE()
                AND o.estado = 1
                ORDER BY o.id_ordenes_servicio DESC"""));
    }

    @PostMapping(value = "/api/panelSupervisor", params = "accion=ajaxCancelarServicio")
    public Map<String, Object> cancelar(Req req) {
        String id = req.str("id_orden", "");
        if (!id.isEmpty()) {
            try {
                db.update("DELETE FROM ordenes_servicio WHERE id_ordenes_servicio = :id", "id", id);
                return Map.of("success", true, "msj", "Servicio cancelado correctamente.");
            } catch (Exception e) {
                log.error("Error eliminando servicio: {}", e.getMessage());
            }
        }
        return Map.of("success", false, "msj", "Error al cancelar el servicio.");
    }

    @PostMapping(value = "/api/panelSupervisor", params = "accion=ajaxObtenerTecnicos")
    public Object tecnicos() {
        return lista("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC");
    }

    @PostMapping(value = "/api/panelSupervisor", params = "accion=ajaxObtenerClientes")
    public Object clientes() {
        return lista("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC");
    }

    @PostMapping(value = "/api/panelSupervisor", params = "accion=ajaxObtenerPuntos")
    public Object puntos(Req req) {
        return lista("SELECT id_punto, nombre_punto, direccion FROM punto WHERE id_cliente = :id_cliente AND estado = 1 ORDER BY nombre_punto ASC",
                "id_cliente", req.intv("id_cliente", 0));
    }

    @PostMapping(value = "/api/panelSupervisor", params = "accion=ajaxObtenerMaquinas")
    public Object maquinas(Req req) {
        return lista("""
                SELECT m.id_maquina, m.device_id, tm.nombre_tipo_maquina
                FROM maquina m
                INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE m.id_punto = :id_punto AND m.estado = 1""", "id_punto", req.intv("id_punto", 0));
    }

    @PostMapping(value = "/api/panelSupervisor", params = "accion=ajaxObtenerTiposMantenimiento")
    public Object tiposMantenimiento() {
        return lista("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1");
    }

    @PostMapping(value = "/api/panelSupervisor", params = "accion=ajaxGuardarServicio")
    public Map<String, Object> guardar(Req req) {
        for (String k : List.of("id_tecnico", "id_cliente", "id_punto", "id_maquina", "id_tipo_mantenimiento", "fecha_visita")) {
            if (Conv.empty(req.get(k))) return Map.of("success", false, "msj", "Faltan datos en el formulario.");
        }
        try {
            Object modalidad = null;
            var p = db.one("SELECT id_modalidad FROM punto WHERE id_punto = :id_punto", "id_punto", req.intv("id_punto", 0));
            if (p != null) modalidad = p.get("id_modalidad");
            db.update("""
                    INSERT INTO ordenes_servicio
                    (id_cliente, id_punto, id_modalidad, id_maquina, id_tecnico, id_tipo_mantenimiento, fecha_visita, estado)
                    VALUES
                    (:cliente, :punto, :modalidad, :maquina, :tecnico, :tipo_mantenimiento, :fecha, 2)""",
                    "cliente", req.intv("id_cliente", 0), "punto", req.intv("id_punto", 0), "modalidad", modalidad, "maquina", req.intv("id_maquina", 0),
                    "tecnico", req.intv("id_tecnico", 0), "tipo_mantenimiento", req.intv("id_tipo_mantenimiento", 0), "fecha", req.str("fecha_visita", ""));
            return Map.of("success", true, "msj", "Servicio agendado correctamente.");
        } catch (Exception e) {
            log.error("Error guardando servicio supervisor: {}", e.getMessage());
            return Map.of("success", false, "msj", "Error al guardar en la base de datos.");
        }
    }

    // ================================================================ NOTIFICACIONES LOGÍSTICA

    @GetMapping(value = "/api/notificacionesLogistica", params = "accion=index")
    public Map<String, Object> notificaciones() {
        return Map.of("titulo", "Alertas y Notificaciones Logísticas");
    }

    @PostMapping(value = "/api/notificacionesLogistica", params = "accion=procesarNotificaciones")
    public Map<String, Object> procesar() {
        try {
            var visitas = lista("""
                    SELECT
                        p.nombre_punto,
                        c.nombre_cliente,
                        COUNT(os.id_ordenes_servicio) AS total_visitas,
                        GROUP_CONCAT(os.fecha_visita ORDER BY os.fecha_visita ASC SEPARATOR ' | ') AS fechas_visitadas
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                    WHERE os.fecha_visita >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                        AND os.estado = 1
                    GROUP BY os.id_punto, p.nombre_punto, c.nombre_cliente
                    HAVING total_visitas > 2""");
            var desplaz = lista("""
                    WITH ViajesTecnicos AS (
                        SELECT
                            os.id_ordenes_servicio,
                            t.nombre_tecnico,
                            p.nombre_punto,
                            mo.nombre_modalidad,
                            os.fecha_visita,
                            os.hora_entrada,
                            LAG(os.hora_salida) OVER (PARTITION BY os.id_tecnico, os.fecha_visita ORDER BY os.hora_entrada) AS salida_servicio_anterior
                        FROM ordenes_servicio os
                        INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                        INNER JOIN punto p ON os.id_punto = p.id_punto
                        INNER JOIN modalidad_operativa mo ON os.id_modalidad = mo.id_modalidad
                        WHERE os.fecha_visita = CURDATE()
                            AND mo.nombre_modalidad LIKE '%Urbano%'
                            AND os.estado = 1
                    )
                    SELECT
                        nombre_tecnico,
                        nombre_punto AS destino,
                        salida_servicio_anterior AS inicio_desplazamiento,
                        hora_entrada AS llegada_destino,
                        TIMESTAMPDIFF(MINUTE, salida_servicio_anterior, hora_entrada) AS minutos_viaje
                    FROM ViajesTecnicos
                    WHERE salida_servicio_anterior IS NOT NULL
                        AND TIMESTAMPDIFF(MINUTE, salida_servicio_anterior, hora_entrada) > 40""");
            boolean enviado = false;
            StringBuilder html = new StringBuilder();
            if (!visitas.isEmpty() || !desplaz.isEmpty()) {
                html.append("<h2 style='color: #1F4E78;'>Reporte de Alertas Logísticas Diarias</h2>");
                if (!visitas.isEmpty()) {
                    html.append("<h3 style='color: #C55A11;'>1. Puntos visitados más de 2 veces (Últimos 7 días)</h3>")
                            .append("<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>")
                            .append("<thead style='background-color: #f2f2f2;'><tr><th>Punto</th><th>Cliente</th><th>Total Visitas</th><th>Fechas de Visita</th></tr></thead><tbody>");
                    for (var v : visitas) {
                        html.append("<tr><td>").append(Conv.h(v.get("nombre_punto"))).append("</td><td>").append(Conv.h(v.get("nombre_cliente")))
                                .append("</td><td style='text-align:center;'><b>").append(v.get("total_visitas")).append("</b></td><td style='font-size: 0.9em;'>")
                                .append(Conv.str(v.get("fechas_visitadas"))).append("</td></tr>");
                    }
                    html.append("</tbody></table><br>");
                }
                if (!desplaz.isEmpty()) {
                    html.append("<h3 style='color: #C55A11;'>2. Desplazamientos Urbanos prolongados (> 40 min) de hoy</h3>")
                            .append("<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>")
                            .append("<thead style='background-color: #f2f2f2;'><tr><th>Técnico</th><th>Punto Destino</th><th>Salida Anterior</th><th>Llegada Actual</th><th>Tiempo (Minutos)</th></tr></thead><tbody>");
                    for (var d : desplaz) {
                        html.append("<tr><td>").append(Conv.h(d.get("nombre_tecnico"))).append("</td><td>").append(Conv.h(d.get("destino")))
                                .append("</td><td style='text-align:center;'>").append(Conv.h(d.get("inicio_desplazamiento"))).append("</td><td style='text-align:center;'>")
                                .append(Conv.h(d.get("llegada_destino"))).append("</td><td style='text-align:center; color: red;'><b>").append(d.get("minutos_viaje")).append(" min</b></td></tr>");
                    }
                    html.append("</tbody></table><br>");
                }
                LocalDateTime ahora = LocalDateTime.now(ZoneId.of("America/Bogota"));
                try {
                    correo.enviar(SUPERVISORES, null, "[Alertas Operativas] Reporte Logístico - " + ahora.format(DateTimeFormatter.ofPattern("dd/MM/yyyy")),
                            html + "<br><br><hr><small>Mensaje automático generado el " + ahora.format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")) + "</small>", null, null);
                    enviado = true;
                } catch (Exception e) {
                    log.error("[Notificaciones Logística] Error enviando correo: {}", e.getMessage());
                }
            }
            Map<String, Object> r = new LinkedHashMap<>();
            r.put("exito", true);
            r.put("mensaje", "Proceso finalizado correctamente.");
            r.put("datos_visitas", visitas);
            r.put("datos_desplazamientos", desplaz);
            r.put("correo_enviado", enviado);
            r.put("html_generado", html.toString());
            return r;
        } catch (Exception e) {
            return Map.of("exito", false, "error", "Error: " + e.getMessage());
        }
    }
}
