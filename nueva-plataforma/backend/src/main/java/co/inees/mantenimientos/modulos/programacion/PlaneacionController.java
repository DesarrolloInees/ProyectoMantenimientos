package co.inees.mantenimientos.modulos.programacion;

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
import co.inees.mantenimientos.core.Req;

/** Port de planeacionControlador.php (clase planeacionVerControlador) + planeacionModelo.php */
@RestController
@RequestMapping("/api/planeacionVer")
public class PlaneacionController {

    private static final Logger log = LoggerFactory.getLogger(PlaneacionController.class);
    private final Db db;

    public PlaneacionController(Db db) {
        this.db = db;
    }

    private List<Map<String, Object>> estrategias() {
        try {
            return db.list("""
                    SELECT e.id_estrategia, e.nombre_estrategia, e.fecha_inicial, e.fecha_final,
                           e.avance_pct, ep.nombre_estado, u.nombre AS nombre_responsable
                    FROM estrategias e
                    LEFT JOIN estados_planeacion ep ON ep.id_estado = e.id_estado
                    LEFT JOIN usuarios u ON u.usuario_id = e.id_responsable
                    WHERE e.estado_registro = 1
                    ORDER BY e.id_estrategia ASC""");
        } catch (Exception e) {
            log.error("Error obtenerEstrategiasActivas: {}", e.getMessage());
            return List.of();
        }
    }

    private List<Map<String, Object>> arbolTareas(Object idEstrategia) {
        try {
            return db.list("""
                    WITH RECURSIVE arbol AS (
                        SELECT t.*, 0 AS nivel
                        FROM tareas_estrategia t
                        WHERE t.id_estrategia = :id_estrategia
                          AND t.id_tarea_padre IS NULL
                          AND t.estado_registro = 1
                        UNION ALL
                        SELECT h.*, a.nivel + 1
                        FROM tareas_estrategia h
                        INNER JOIN arbol a ON h.id_tarea_padre = a.id_tarea
                        WHERE h.estado_registro = 1
                    )
                    SELECT arbol.*, ep.nombre_estado, u.nombre AS nombre_responsable
                    FROM arbol
                    LEFT JOIN estados_planeacion ep ON ep.id_estado = arbol.id_estado
                    LEFT JOIN usuarios u ON u.usuario_id = arbol.id_responsable
                    ORDER BY arbol.orden ASC, arbol.id_tarea ASC""", "id_estrategia", idEstrategia);
        } catch (Exception e) {
            log.error("Error obtenerArbolTareas: {}", e.getMessage());
            return List.of();
        }
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> index(Req req) {
        if (!req.sesion().has("usuario_id")) return Map.of("error", "Sesión expirada.");
        List<Map<String, Object>> est = new ArrayList<>();
        for (var e : estrategias()) {
            Map<String, Object> x = new LinkedHashMap<>(e);
            x.put("tareas", arbolTareas(e.get("id_estrategia")));
            est.add(x);
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Planeación Estratégica");
        List<Map<String, Object>> estados;
        List<Map<String, Object>> usuarios;
        try {
            estados = db.list("SELECT id_estado, nombre_estado FROM estados_planeacion ORDER BY id_estado ASC");
        } catch (Exception e) {
            estados = List.of();
        }
        try {
            usuarios = db.list("SELECT usuario_id, nombre FROM usuarios WHERE estado = 'activo' ORDER BY nombre ASC");
        } catch (Exception e) {
            usuarios = List.of();
        }
        r.put("estados", estados);
        r.put("usuarios", usuarios);
        r.put("estrategias", est);
        return r;
    }

    @RequestMapping(params = "accion=datosGantt", method = { RequestMethod.GET, RequestMethod.POST })
    public List<Map<String, Object>> datosGantt() {
        List<Map<String, Object>> filas = new ArrayList<>();
        for (var e : estrategias()) {
            String idE = "estrategia_" + e.get("id_estrategia");
            Map<String, Object> f = new LinkedHashMap<>();
            f.put("id", idE);
            f.put("tipo", "estrategia");
            f.put("name", e.get("nombre_estrategia"));
            f.put("start", e.get("fecha_inicial"));
            f.put("end", e.get("fecha_final"));
            f.put("progress", Conv.toInt(e.get("avance_pct")));
            f.put("estado", e.get("nombre_estado") != null ? e.get("nombre_estado") : "Sin estado");
            f.put("responsable", e.get("nombre_responsable") != null ? e.get("nombre_responsable") : "Sin asignar");
            filas.add(f);
            for (var t : arbolTareas(e.get("id_estrategia"))) {
                Map<String, Object> g = new LinkedHashMap<>();
                g.put("id", "tarea_" + t.get("id_tarea"));
                g.put("tipo", "tarea");
                g.put("name", t.get("nombre_tarea"));
                g.put("start", t.get("fecha_inicial"));
                g.put("end", t.get("fecha_final"));
                g.put("progress", Conv.toInt(t.get("avance_pct")));
                g.put("dependencies", !Conv.empty(t.get("id_tarea_padre")) ? "tarea_" + t.get("id_tarea_padre") : idE);
                g.put("estado", t.get("nombre_estado") != null ? t.get("nombre_estado") : "Sin estado");
                g.put("responsable", t.get("nombre_responsable") != null ? t.get("nombre_responsable") : "Sin asignar");
                filas.add(g);
            }
        }
        return filas;
    }

    private static Integer idOpt(Req req, String k) {
        return !Conv.empty(req.get(k)) ? req.intv(k) : null;
    }

    @PostMapping(params = "accion=crearEstrategia")
    public Map<String, Object> crearEstrategia(Req req) {
        String nombre = req.str("nombre_estrategia", "").trim();
        Integer resp = idOpt(req, "id_responsable");
        Integer estado = idOpt(req, "id_estado");
        String fi = req.str("fecha_inicial", ""), ff = req.str("fecha_final", "");
        if (nombre.isEmpty() || estado == null || estado == 0 || fi.isEmpty() || ff.isEmpty())
            return Map.of("status", "error", "message", "Faltan datos obligatorios.");
        try {
            long id = db.insert("""
                    INSERT INTO estrategias
                        (nombre_estrategia, id_responsable, id_estado, fecha_inicial, fecha_final)
                    VALUES
                        (:nombre, :id_responsable, :id_estado, :fecha_inicial, :fecha_final)""",
                    "nombre", nombre, "id_responsable", resp, "id_estado", estado, "fecha_inicial", fi, "fecha_final", ff);
            return Map.of("status", "success", "id_estrategia", id);
        } catch (Exception e) {
            log.error("Error crearEstrategia: {}", e.getMessage());
            return Map.of("status", "error", "message", "No se pudo crear la estrategia.");
        }
    }

    @PostMapping(params = "accion=crearTarea")
    public Map<String, Object> crearTarea(Req req) {
        Integer idE = idOpt(req, "id_estrategia");
        Integer padre = idOpt(req, "id_tarea_padre");
        String nombre = req.str("nombre_tarea", "").trim();
        Integer resp = idOpt(req, "id_responsable");
        Integer estado = idOpt(req, "id_estado");
        String fi = req.str("fecha_inicial", ""), ff = req.str("fecha_final", "");
        if (idE == null || idE == 0 || nombre.isEmpty() || estado == null || estado == 0 || fi.isEmpty() || ff.isEmpty())
            return Map.of("status", "error", "message", "Faltan datos obligatorios.");
        try {
            long id = db.insert("""
                    INSERT INTO tareas_estrategia
                        (id_estrategia, id_tarea_padre, nombre_tarea, id_responsable, id_estado,
                         fecha_inicial, fecha_final, orden)
                    VALUES
                        (:id_estrategia, :id_tarea_padre, :nombre_tarea, :id_responsable, :id_estado,
                         :fecha_inicial, :fecha_final, :orden)""",
                    "id_estrategia", idE, "id_tarea_padre", padre != null && padre != 0 ? padre : null, "nombre_tarea", nombre, "id_responsable", resp,
                    "id_estado", estado, "fecha_inicial", fi, "fecha_final", ff, "orden", 0);
            return Map.of("status", "success", "id_tarea", id);
        } catch (Exception e) {
            log.error("Error crearTarea: {}", e.getMessage());
            return Map.of("status", "error", "message", "No se pudo crear la tarea.");
        }
    }

    private static String[] tabla(String tipo) {
        return "tarea".equals(tipo) ? new String[] { "tareas_estrategia", "id_tarea" } : new String[] { "estrategias", "id_estrategia" };
    }

    private static boolean tipoValido(String t) {
        return "tarea".equals(t) || "estrategia".equals(t);
    }

    @PostMapping(params = "accion=actualizarFechas")
    public Map<String, Object> actualizarFechas(Req req) {
        String tipo = req.str("tipo", "");
        int id = req.intv("id", 0);
        String fi = req.str("fecha_inicial", ""), ff = req.str("fecha_final", "");
        if (id == 0 || fi.isEmpty() || ff.isEmpty() || !tipoValido(tipo)) return Map.of("status", "error", "message", "Datos incompletos.");
        String[] t = tabla(tipo);
        try {
            db.update("UPDATE " + t[0] + " SET fecha_inicial = :fecha_inicial, fecha_final = :fecha_final, updated_at = NOW() WHERE " + t[1] + " = :id",
                    "fecha_inicial", fi, "fecha_final", ff, "id", id);
            return Map.of("status", "success");
        } catch (Exception e) {
            log.error("Error actualizarFechas: {}", e.getMessage());
            return Map.of("status", "error");
        }
    }

    @PostMapping(params = "accion=actualizarAvance")
    public Map<String, Object> actualizarAvance(Req req) {
        String tipo = req.str("tipo", "");
        int id = req.intv("id", 0);
        int avance = req.has("avance_pct") ? Math.max(0, Math.min(100, req.intv("avance_pct", 0))) : 0;
        if (id == 0 || !tipoValido(tipo)) return Map.of("status", "error", "message", "Datos incompletos.");
        String[] t = tabla(tipo);
        try {
            db.update("UPDATE " + t[0] + " SET avance_pct = :avance, updated_at = NOW() WHERE " + t[1] + " = :id", "avance", avance, "id", id);
            return Map.of("status", "success");
        } catch (Exception e) {
            log.error("Error actualizarAvance: {}", e.getMessage());
            return Map.of("status", "error");
        }
    }

    @PostMapping(params = "accion=actualizarEstado")
    public Map<String, Object> actualizarEstado(Req req) {
        String tipo = req.str("tipo", "");
        int id = req.intv("id", 0);
        int estado = req.intv("id_estado", 0);
        if (id == 0 || estado == 0 || !tipoValido(tipo)) return Map.of("status", "error", "message", "Datos incompletos.");
        String[] t = tabla(tipo);
        try {
            db.update("UPDATE " + t[0] + " SET id_estado = :id_estado, updated_at = NOW() WHERE " + t[1] + " = :id", "id_estado", estado, "id", id);
            return Map.of("status", "success");
        } catch (Exception e) {
            log.error("Error actualizarEstado: {}", e.getMessage());
            return Map.of("status", "error");
        }
    }
}
