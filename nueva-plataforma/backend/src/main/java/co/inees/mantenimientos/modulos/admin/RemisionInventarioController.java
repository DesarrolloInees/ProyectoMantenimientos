package co.inees.mantenimientos.modulos.admin;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;
import co.inees.mantenimientos.core.Sesion;

/**
 * Port de controlRemision{Ver,Crear,Editar,Eliminar}, inventarioTecnico{Ver,Crear,Editar,Eliminar}
 * y gestionDevolucion.
 */
@RestController
@RequestMapping("/api")
public class RemisionInventarioController {

    private static final Logger log = LoggerFactory.getLogger(RemisionInventarioController.class);
    private final Db db;

    public RemisionInventarioController(Db db) {
        this.db = db;
    }

    private List<Map<String, Object>> tecnicosActivos() {
        return db.list("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC");
    }

    // ================================================================ CONTROL REMISIONES

    private static final String FROM_REMISIONES = """
            FROM control_remisiones cr
            INNER JOIN tecnico t ON cr.id_tecnico = t.id_tecnico
            INNER JOIN estados_remision e ON cr.id_estado = e.id_estado
            WHERE e.nombre_estado != 'ELIMINADO'""";

    private static final String FILTRO_REMISIONES = """
             AND (cr.numero_remision LIKE :busqueda1
                OR t.nombre_tecnico LIKE :busqueda2
                OR e.nombre_estado LIKE :busqueda3)""";

    @GetMapping(value = "/controlRemisionVer", params = "accion=obtenerDatosDatatable")
    public Map<String, Object> remisionesDatatable(Req req) {
        int draw = req.intv("draw", 1);
        int start = req.intv("start", 0);
        int length = req.intv("length", 100);
        String busqueda = Conv.str(req.en("search", "value")).trim();
        Object colObj = req.en("order", "0", "column");
        int col = colObj == null ? 0 : Conv.toInt(colObj);
        String dir = Conv.str(req.en("order", "0", "dir"), "desc");

        Map<String, Object> pCount = new LinkedHashMap<>();
        String sqlCount = "SELECT COUNT(*) as total " + FROM_REMISIONES;
        long total = db.scalarLong(sqlCount);
        long filtrados = total;
        if (!busqueda.isEmpty()) {
            String like = "%" + busqueda + "%";
            pCount.put("busqueda1", like);
            pCount.put("busqueda2", like);
            pCount.put("busqueda3", like);
            filtrados = db.scalarLong(sqlCount + FILTRO_REMISIONES, pCount);
        }
        String[] columnas = {"numero_remision", "nombre_tecnico", "nombre_estado", "fecha_asignacion", "fecha_uso"};
        String orden = "cr.id_control DESC";
        if (col >= 0 && col < columnas.length) orden = columnas[col] + " " + ("desc".equals(dir) ? "DESC" : "ASC");
        String sql = """
                SELECT
                    cr.id_control,
                    cr.numero_remision,
                    e.nombre_estado,
                    cr.fecha_asignacion,
                    cr.fecha_uso,
                    t.nombre_tecnico
                """ + FROM_REMISIONES + (busqueda.isEmpty() ? "" : FILTRO_REMISIONES) + " ORDER BY " + orden + " LIMIT :limite OFFSET :offset";
        Map<String, Object> p = new LinkedHashMap<>(pCount);
        p.put("limite", length);
        p.put("offset", start);
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("draw", draw);
        r.put("recordsTotal", total);
        r.put("recordsFiltered", filtrados);
        r.put("data", db.list(sql, p));
        return r;
    }

    @GetMapping(value = "/controlRemisionCrear", params = "accion=index")
    public Map<String, Object> remisionCrearForm() {
        return Map.of("listaTecnicos", tecnicosActivos());
    }

    @GetMapping(value = "/controlRemisionCrear", params = "accion=getUltima")
    public Map<String, Object> ultimaRemision(Req req) {
        Object ultima;
        try {
            ultima = db.scalar("""
                    SELECT numero_remision
                    FROM control_remisiones
                    WHERE id_tecnico = :id_tecnico
                    ORDER BY CAST(numero_remision AS UNSIGNED) DESC
                    LIMIT 1""", "id_tecnico", req.intv("id_tecnico"));
            if (ultima == null) ultima = 0;
        } catch (Exception e) {
            ultima = 0;
        }
        String s = Conv.str(ultima);
        Object siguiente = s.matches("^\\d+(\\.\\d+)?$") ? (Object) (Conv.toLong(s) + 1) : "";
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("ultima", ultima);
        r.put("siguiente", siguiente);
        return r;
    }

    @PostMapping("/controlRemisionCrear")
    public Map<String, Object> remisionCrear(Req req) {
        String idTecnico = req.str("id_tecnico");
        String ini = req.trim("remision_inicio");
        String fin = req.trim("remision_fin");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(idTecnico)) errores.add("Seleccione un técnico.");
        if (Conv.empty(ini)) errores.add("Falta el número inicial.");
        if (errores.isEmpty()) {
            long inicio = Conv.toLong(ini);
            long fn = Conv.empty(fin) ? inicio : Conv.toLong(fin);
            if (fn < inicio) {
                errores.add("El final no puede ser menor al inicial.");
            } else {
                int creadas = 0, duplicadas = 0;
                for (long i = inicio; i <= fn; i++) {
                    try {
                        db.update("""
                                INSERT INTO control_remisiones (
                                    numero_remision, id_tecnico, id_estado, fecha_asignacion
                                ) VALUES (
                                    :numero, :id_tecnico,
                                    (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1),
                                    NOW()
                                )""", "numero", String.valueOf(i), "id_tecnico", idTecnico);
                        creadas++;
                    } catch (DataIntegrityViolationException e) {
                        duplicadas++;
                    } catch (Exception e) {
                        log.error("Error al crear remisión: {}", e.getMessage());
                    }
                }
                if (creadas > 0 && duplicadas == 0) return Map.of("success", true, "redirect", "controlRemisionVer");
                if (duplicadas > 0) {
                    errores.add("Se asignaron " + creadas + ". OJO: " + duplicadas + " números ya los tenía este técnico y se omitieron.");
                } else {
                    errores.add("No se guardó nada. Al parecer este técnico ya tiene asignados todos esos números.");
                }
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    private Map<String, Object> remisionPorId(Object id) {
        return db.one("""
                SELECT c.*, e.nombre_estado
                FROM control_remisiones c
                LEFT JOIN estados_remision e ON c.id_estado = e.id_estado
                WHERE c.id_control = :id""", "id", id);
    }

    @GetMapping("/controlRemisionEditar")
    public Map<String, Object> remisionEditarForm(Req req) {
        String id = req.str("id");
        if (id.isEmpty()) return Map.of("redirect", "controlRemisionVer");
        Map<String, Object> d = remisionPorId(id);
        if (d == null) return Map.of("redirect", "controlRemisionVer");
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("datos", d);
        r.put("listaTecnicos", tecnicosActivos());
        r.put("listaEstados", db.list("SELECT id_estado, nombre_estado FROM estados_remision WHERE activo = 1 ORDER BY nombre_estado ASC"));
        return r;
    }

    @PostMapping("/controlRemisionEditar")
    public Map<String, Object> remisionEditar(Req req) {
        String id = req.str("id");
        if (id.isEmpty() || remisionPorId(id) == null) return Map.of("success", false, "redirect", "controlRemisionVer");
        String numero = req.trim("numero_remision");
        String idTec = req.str("id_tecnico");
        String idEstado = req.str("id_estado");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(numero)) errores.add("El número es obligatorio.");
        if (Conv.empty(idTec)) errores.add("El técnico es obligatorio.");
        if (Conv.empty(idEstado)) errores.add("El estado es obligatorio.");
        if (errores.isEmpty()) {
            try {
                db.update("""
                        UPDATE control_remisiones SET
                            numero_remision = :numero,
                            id_tecnico = :id_tecnico,
                            id_estado = :id_estado
                        WHERE id_control = :id""", "numero", numero, "id_tecnico", idTec, "id_estado", idEstado, "id", id);
                return Map.of("success", true, "redirect", "controlRemisionVer");
            } catch (DataIntegrityViolationException e) {
                errores.add("Ese número de remisión ya existe en el sistema.");
            } catch (Exception e) {
                errores.add("Error al actualizar en la base de datos.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    @PostMapping("/controlRemisionEliminar")
    public Map<String, Object> remisionEliminar(Req req) {
        String id = req.str("id");
        if (!id.isEmpty()) {
            try {
                // Misma lógica que controlRemisionEliminarControlador.php
                Map<String, Object> d = db.one("SELECT estado FROM control_remisiones WHERE id_control = :id", "id", id);
                if (d != null && ("DISPONIBLE".equals(d.get("estado")) || "ANULADA".equals(d.get("estado")))) {
                    db.update("UPDATE control_remisiones SET estado = 'ELIMINADO' WHERE id_control = :id", "id", id);
                }
            } catch (Exception e) {
                log.warn("controlRemisionEliminar: {}", e.getMessage());
            }
        }
        return Map.of("success", true, "redirect", "controlRemisionVer");
    }

    // ================================================================ INVENTARIO TÉCNICO

    private static final String SQL_INVENTARIO = """
            SELECT
                i.id_inventario,
                i.cantidad_actual,
                i.ultima_actualizacion,
                t.nombre_tecnico,
                r.nombre_repuesto,
                r.codigo_referencia
            FROM inventario_tecnico i
            INNER JOIN tecnico t ON i.id_tecnico = t.id_tecnico
            INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
            WHERE i.estado = 1""";

    @GetMapping("/inventarioTecnicoVer")
    public Map<String, Object> inventarioVer(Sesion s) {
        Map<String, Object> r = new LinkedHashMap<>();
        boolean esTecnico = s.nivel() == 3;
        r.put("esTecnico", esTecnico);
        if (esTecnico) {
            List<Map<String, Object>> inv = new ArrayList<>();
            if (s.usuarioId() > 0) {
                Map<String, Object> tec = db.one("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE usuario_id = :uid LIMIT 1", "uid", s.usuarioId());
                if (tec != null && !Conv.empty(tec.get("id_tecnico"))) {
                    inv = db.list(SQL_INVENTARIO + " AND i.id_tecnico = :id_tecnico ORDER BY r.nombre_repuesto ASC", "id_tecnico", tec.get("id_tecnico"));
                }
            }
            r.put("titulo", "Mi Inventario");
            r.put("inventario", inv);
        } else {
            r.put("titulo", "Inventario por Técnico");
            r.put("inventario", db.list(SQL_INVENTARIO + " ORDER BY t.nombre_tecnico ASC, r.nombre_repuesto ASC"));
            r.put("listaTecnicos", db.column("""
                    SELECT DISTINCT t.nombre_tecnico FROM tecnico t
                    INNER JOIN inventario_tecnico i ON t.id_tecnico = i.id_tecnico
                    WHERE i.estado = 1 ORDER BY t.nombre_tecnico ASC"""));
        }
        return r;
    }

    @GetMapping("/inventarioTecnicoCrear")
    public Map<String, Object> inventarioCrearForm() {
        return Map.of("listaTecnicos", tecnicosActivos(),
                "listaRepuestos", db.list("SELECT id_repuesto, nombre_repuesto, codigo_referencia FROM repuesto WHERE estado = 1 ORDER BY nombre_repuesto ASC"));
    }

    @PostMapping("/inventarioTecnicoCrear")
    public Map<String, Object> inventarioCrear(Req req) {
        List<Object> ids = req.arr("ids_tecnicos");
        List<Object> repuestos = req.arr("repuestos");
        List<Object> cantidades = req.arr("cantidades");
        List<String> errores = new ArrayList<>();
        if (ids.isEmpty()) errores.add("Error crítico: No seleccionaste ningún técnico.");
        if (repuestos.isEmpty()) errores.add("No agregaste ningún repuesto a la lista.");
        if (!errores.isEmpty()) return Map.of("success", false, "errores", errores);
        int[] tot = {0, 0};
        try {
            db.txRun(() -> {
                for (Object idTec : ids) {
                    tot[1]++;
                    for (int i = 0; i < repuestos.size(); i++) {
                        Object idRep = repuestos.get(i);
                        int cant = i < cantidades.size() ? Conv.toInt(cantidades.get(i)) : 0;
                        if (!Conv.empty(idRep) && cant > 0) {
                            try {
                                db.update("""
                                        INSERT INTO inventario_tecnico (id_tecnico, id_repuesto, cantidad_actual, estado)
                                        VALUES (:id_tec, :id_rep, :cant_ini, 1)
                                        ON DUPLICATE KEY UPDATE
                                            cantidad_actual = cantidad_actual + :cant_sum,
                                            estado = 1""", "id_tec", Conv.toInt(idTec), "id_rep", Conv.toInt(idRep), "cant_ini", cant, "cant_sum", cant);
                            } catch (Exception e) {
                                throw new IllegalStateException("Error al asignar repuesto ID " + idRep + " al técnico " + idTec);
                            }
                            tot[0]++;
                        }
                    }
                }
            });
            return Map.of("success", true, "mensajeExito",
                    "¡Éxito! Se asignaron repuestos a <b>" + tot[1] + " técnicos</b> (Total movimientos: " + tot[0] + ").");
        } catch (Exception e) {
            errores.add("Error en el proceso: " + e.getMessage());
            return Map.of("success", false, "errores", errores, "tecnicosSeleccionados", ids);
        }
    }

    private Map<String, Object> inventarioPorId(Object id) {
        return db.one("""
                SELECT i.*, t.nombre_tecnico, r.nombre_repuesto
                FROM inventario_tecnico i
                INNER JOIN tecnico t ON i.id_tecnico = t.id_tecnico
                INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                WHERE i.id_inventario = :id AND i.estado = 1""", "id", id);
    }

    @GetMapping("/inventarioTecnicoEditar")
    public Map<String, Object> inventarioEditarForm(Req req) {
        Map<String, Object> d = inventarioPorId(req.str("id"));
        if (d == null) return Map.of("redirect", "inventarioTecnicoVer");
        return Map.of("datos", d);
    }

    @PostMapping("/inventarioTecnicoEditar")
    public Map<String, Object> inventarioEditar(Req req) {
        String id = req.str("id_inventario");
        int cantidad = req.intv("cantidad");
        List<String> errores = new ArrayList<>();
        if (cantidad < 0) errores.add("La cantidad no puede ser negativa.");
        if (errores.isEmpty()) {
            try {
                db.update("UPDATE inventario_tecnico SET cantidad_actual = :cant WHERE id_inventario = :id", "cant", cantidad, "id", id);
                return Map.of("success", true, "redirect", "inventarioTecnicoVer");
            } catch (Exception e) {
                errores.add("Error al actualizar la base de datos.");
            }
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("success", false);
        r.put("errores", errores);
        r.put("datos", inventarioPorId(id));
        return r;
    }

    @PostMapping("/inventarioTecnicoEliminar")
    public Map<String, Object> inventarioEliminar(Req req) {
        if (!req.str("id").isEmpty()) {
            try {
                db.update("UPDATE inventario_tecnico SET estado = 0 WHERE id_inventario = :id", "id", req.intv("id"));
            } catch (Exception e) {
                log.error("Error al eliminar inventario: {}", e.getMessage());
            }
        }
        return Map.of("success", true, "redirect", "inventarioTecnicoVer");
    }

    // ================================================================ GESTIÓN DEVOLUCIÓN

    @PostMapping(value = "/gestionDevolucion", params = "accion=index")
    public Map<String, Object> gestionDevolucion(Req req) {
        String filtro = req.str("id_tecnico");
        String sql = """
                SELECT
                    cdr.id_orden_servicio,
                    cdr.id_repuesto,
                    cdr.cantidad,
                    cdr.estado_devolucion,
                    r.nombre_repuesto,
                    r.codigo_referencia,
                    t.nombre_tecnico,
                    os.fecha_visita,
                    os.numero_remision,
                    c.nombre_cliente,
                    p.nombre_punto,
                    d.nombre_delegacion
                FROM control_devolucion_repuestos cdr
                INNER JOIN repuesto r ON cdr.id_repuesto = r.id_repuesto
                INNER JOIN tecnico t ON cdr.id_tecnico = t.id_tecnico
                INNER JOIN ordenes_servicio os ON cdr.id_orden_servicio = os.id_ordenes_servicio
                INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                INNER JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                WHERE cdr.estado_devolucion = 'Pendiente'""";
        Map<String, Object> p = new LinkedHashMap<>();
        if (!Conv.empty(filtro)) {
            sql += " AND cdr.id_tecnico = :id_tecnico";
            p.put("id_tecnico", Conv.toInt(filtro));
        }
        sql += " ORDER BY os.fecha_visita ASC";
        List<Map<String, Object>> pendientes;
        try {
            pendientes = db.list(sql, p);
        } catch (Exception e) {
            pendientes = List.of();
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("tecnicos", tecnicosActivos());
        r.put("filtroTecnico", filtro);
        r.put("datosPendientes", pendientes);
        return r;
    }

    @PostMapping(value = "/gestionDevolucion", params = "accion=ajaxProcesarDevolucion")
    public Map<String, Object> procesarDevolucion(Req req) {
        Object items = Req.jsonDecode(req.str("items", "[]"));
        List<Object> lista = items instanceof List<?> l ? new ArrayList<>(l) : List.of();
        if (lista.isEmpty()) return Map.of("status", "error", "msg", "No se seleccionaron repuestos.");
        int exitos = 0, errores = 0;
        for (Object o : lista) {
            Map<String, Object> it = Conv.map(o);
            try {
                db.update("""
                        UPDATE control_devolucion_repuestos
                        SET estado_devolucion = 'Devuelto'
                        WHERE id_orden_servicio = :o AND id_repuesto = :r""", "o", Conv.toInt(it.get("id_orden")), "r", Conv.toInt(it.get("id_repuesto")));
                exitos++;
            } catch (Exception e) {
                errores++;
            }
        }
        if (errores == 0) return Map.of("status", "ok", "msg", "✅ " + exitos + " repuestos marcados como devueltos correctamente.");
        return Map.of("status", "warning", "msg", "⚠️ Se procesaron " + exitos + ", pero hubo " + errores + " errores.");
    }
}
