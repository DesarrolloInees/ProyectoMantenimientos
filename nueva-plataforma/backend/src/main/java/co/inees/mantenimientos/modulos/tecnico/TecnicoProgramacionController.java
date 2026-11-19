package co.inees.mantenimientos.modulos.tecnico;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/** Port de tecnicoProgramacionControlador.php + tecnicoProgramacionModelo.php */
@RestController
@RequestMapping("/api/tecnicoProgramacion")
public class TecnicoProgramacionController {

    private static final Logger log = LoggerFactory.getLogger(TecnicoProgramacionController.class);
    private final Db db;

    public TecnicoProgramacionController(Db db) {
        this.db = db;
    }

    private static int usuario(Req req) {
        return req.sesion().has("usuario_id") ? req.sesion().usuarioId() : 0;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> index() {
        return Map.of("titulo", "Mi Programación Diaria");
    }

    @PostMapping(params = "accion=ajaxObtenerProgramacion")
    public Map<String, Object> ajaxObtenerProgramacion(Req req) {
        try {
            int uid = usuario(req);
            if (uid == 0) return Map.of("data", List.of(), "error", "No hay sesión de usuario activa.");
            String fecha = !Conv.empty(req.get("fecha")) ? req.str("fecha") : F.hoy();
            String estado = req.has("estado") ? req.str("estado") : "pendientes";
            if (!fecha.matches("\\d{4}-\\d{2}-\\d{2}")) return Map.of("data", List.of(), "error", "Formato de fecha inválido.");
            int estadoBD = "pendientes".equals(estado) ? 2 : 1;
            List<Map<String, Object>> datos;
            try {
                datos = db.list("""
                        SELECT
                            os.id_ordenes_servicio,
                            os.fecha_visita,
                            os.estado,
                            c.nombre_cliente,
                            p.nombre_punto,
                            p.direccion        AS direccion_punto,
                            m.device_id,
                            tm.nombre_tipo_maquina,
                            tmt.nombre_completo AS tipo_mantenimiento,
                            t.nombre_tecnico,
                            os.hora_entrada,
                            os.hora_salida,
                            os.tiempo_servicio,
                            os.actividades_realizadas
                        FROM ordenes_servicio os
                        INNER JOIN tecnico t
                            ON os.id_tecnico = t.id_tecnico
                            AND t.usuario_id  = :id_usuario
                            AND t.estado      = 1
                        LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                        LEFT JOIN punto p ON os.id_punto = p.id_punto
                        LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                        LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                        LEFT JOIN tipo_mantenimiento tmt ON os.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                        WHERE os.fecha_visita = :fecha
                        AND   os.estado       = :estado
                        ORDER BY p.nombre_punto ASC, os.id_ordenes_servicio ASC""", "id_usuario", uid, "fecha", fecha, "estado", estadoBD);
            } catch (Exception e) {
                log.error("[tecnicoProgramacionModelo] Error en obtenerServiciosProgramadosTecnico: {}", e.getMessage());
                datos = List.of();
            }
            Map<String, Object> r = new LinkedHashMap<>();
            r.put("data", datos);
            r.put("debug_id", uid);
            r.put("debug_fecha", fecha);
            r.put("estado", estado);
            return r;
        } catch (Exception e) {
            return Map.of("data", List.of(), "error", "Error interno del servidor.");
        }
    }

    @PostMapping(params = "accion=ajaxObtenerDetalleServicio")
    public Map<String, Object> ajaxObtenerDetalleServicio(Req req) {
        int uid = usuario(req);
        int idOrden = req.intv("id_orden", 0);
        if (uid == 0 || idOrden == 0) return Map.of("success", false, "msj", "Sesión expirada o orden inválida");
        Map<String, Object> d;
        try {
            d = db.one("""
                    SELECT
                        os.id_ordenes_servicio,
                        os.fecha_visita,
                        os.hora_entrada,
                        os.hora_salida,
                        os.tiempo_servicio,
                        os.actividades_realizadas,
                        os.valor_servicio,
                        c.nombre_cliente,
                        p.nombre_punto,
                        p.direccion,
                        m.device_id,
                        tm.nombre_tipo_maquina,
                        tmt.nombre_completo AS tipo_mantenimiento,
                        em.nombre_estado AS estado_final,
                        cs.nombre_calificacion AS calificacion,
                        os.soporte_remoto,
                        os.detalle_novedad
                    FROM ordenes_servicio os
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico AND t.usuario_id = :id_usuario
                    LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                    LEFT JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                    LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN tipo_mantenimiento tmt ON os.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                    LEFT JOIN estado_maquina em ON os.id_estado_maquina = em.id_estado
                    LEFT JOIN calificacion_servicio cs ON os.id_calificacion = cs.id_calificacion
                    WHERE os.id_ordenes_servicio = :id_orden""", "id_usuario", uid, "id_orden", idOrden);
        } catch (Exception e) {
            d = null;
        }
        if (d != null) return Map.of("success", true, "data", d);
        return Map.of("success", false, "msj", "No se encontró el servicio");
    }

    @PostMapping(params = "accion=ajaxEliminarServicio")
    public Map<String, Object> ajaxEliminarServicio(Req req) {
        int uid = usuario(req);
        int idOrden = req.intv("id_orden", 0);
        if (uid == 0 || idOrden == 0) return Map.of("success", false, "msj", "Datos inválidos o sesión expirada.");
        boolean ok;
        try {
            Map<String, Object> t = db.one("SELECT id_tecnico FROM tecnico WHERE usuario_id = :uid", "uid", uid);
            if (t == null) ok = false;
            else {
                // PDOStatement::execute() devuelve true aunque no borre filas
                db.update("DELETE FROM ordenes_servicio WHERE id_ordenes_servicio = :id_orden AND id_tecnico = :id_tecnico AND estado = 2",
                        "id_orden", idOrden, "id_tecnico", t.get("id_tecnico"));
                ok = true;
            }
        } catch (Exception e) {
            log.error("[tecnicoProgramacionModelo] Error eliminando orden: {}", e.getMessage());
            ok = false;
        }
        return ok ? Map.of("success", true, "msj", "Servicio eliminado correctamente.")
                : Map.of("success", false, "msj", "No se pudo eliminar. Verifique el estado del servicio.");
    }

    @PostMapping(params = "accion=ajaxObtenerClientes")
    public Object ajaxObtenerClientes() {
        try {
            return db.list("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    @PostMapping(params = "accion=ajaxObtenerPuntos")
    public Object ajaxObtenerPuntos(Req req) {
        try {
            return db.list("SELECT id_punto, nombre_punto, direccion FROM punto WHERE id_cliente = :id_cliente AND estado = 1 ORDER BY nombre_punto ASC",
                    "id_cliente", req.intv("id_cliente", 0));
        } catch (Exception e) {
            return List.of();
        }
    }

    @PostMapping(params = "accion=ajaxObtenerMaquinas")
    public Object ajaxObtenerMaquinas(Req req) {
        try {
            return db.list("""
                    SELECT m.id_maquina, m.device_id, tm.nombre_tipo_maquina
                    FROM maquina m
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE m.id_punto = :id_punto AND m.estado = 1""", "id_punto", req.intv("id_punto", 0));
        } catch (Exception e) {
            return List.of();
        }
    }

    @PostMapping(params = "accion=ajaxObtenerTiposMantenimiento")
    public Object ajaxObtenerTiposMantenimiento() {
        try {
            return db.list("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1");
        } catch (Exception e) {
            return List.of();
        }
    }

    @PostMapping(params = "accion=ajaxIniciarServicio")
    public Map<String, Object> ajaxIniciarServicio(Req req) {
        int uid = usuario(req);
        int idOrden = req.intv("id_orden", 0);
        String lat = req.str("latitud_inicio", "");
        String lon = req.str("longitud_inicio", "");
        if (uid == 0 || idOrden == 0 || lat.isEmpty() || "0".equals(lat) || lon.isEmpty() || "0".equals(lon)) {
            return Map.of("success", false, "msj", "Faltan permisos de GPS o la sesión expiró.");
        }
        try {
            db.update("""
                    INSERT INTO ordenes_servicio_complemento
                    (id_orden_servicio, latitud_inicio, longitud_inicio, estado)
                    VALUES (:id_orden, :lat, :lon, 1)
                    ON DUPLICATE KEY UPDATE
                    latitud_inicio = IFNULL(latitud_inicio, VALUES(latitud_inicio)),
                    longitud_inicio = IFNULL(longitud_inicio, VALUES(longitud_inicio))""", "id_orden", idOrden, "lat", lat, "lon", lon);
            db.update("UPDATE ordenes_servicio SET hora_entrada = IFNULL(hora_entrada, CURTIME()) WHERE id_ordenes_servicio = :id_orden", "id_orden", idOrden);
            return Map.of("success", true);
        } catch (Exception e) {
            log.error("Error iniciarServicioGPS: {}", e.getMessage());
            return Map.of("success", false, "msj", "Error al guardar ubicación de inicio en la base de datos.");
        }
    }

    @PostMapping(params = "accion=ajaxGuardarExtra")
    public Map<String, Object> ajaxGuardarExtra(Req req) {
        int uid = usuario(req);
        if (uid == 0 || Conv.empty(req.get("id_cliente")) || Conv.empty(req.get("id_punto")) || Conv.empty(req.get("id_maquina"))
                || Conv.empty(req.get("id_tipo_mantenimiento")) || Conv.empty(req.get("fecha_visita"))) {
            return Map.of("success", false, "msj", "Faltan datos o sesión expirada.");
        }
        try {
            Map<String, Object> t = db.one("SELECT id_tecnico FROM tecnico WHERE usuario_id = :uid", "uid", uid);
            if (t == null) return Map.of("success", false, "msj", "Error DB: No se encontró el perfil de técnico.");
            Map<String, Object> p = db.one("SELECT id_modalidad FROM punto WHERE id_punto = :id_punto", "id_punto", req.intv("id_punto"));
            Object modalidad = p != null && !Conv.empty(p.get("id_modalidad")) ? p.get("id_modalidad") : 1;
            db.update("""
                    INSERT INTO ordenes_servicio
                    (id_cliente, id_punto, id_modalidad, id_maquina, id_tecnico, id_tipo_mantenimiento, fecha_visita, estado)
                    VALUES
                    (:cliente, :punto, :modalidad, :maquina, :tecnico, :tipo_mantenimiento, :fecha, 2)""",
                    "cliente", req.intv("id_cliente"), "punto", req.intv("id_punto"), "modalidad", modalidad, "maquina", req.intv("id_maquina"),
                    "tecnico", t.get("id_tecnico"), "tipo_mantenimiento", req.intv("id_tipo_mantenimiento"), "fecha", req.str("fecha_visita"));
            return Map.of("success", true, "msj", "Servicio extra agendado con éxito.");
        } catch (Exception e) {
            log.error("Error guardando extra: {}", e.getMessage());
            return Map.of("success", false, "msj", "Error DB: " + e.getMessage());
        }
    }
}
