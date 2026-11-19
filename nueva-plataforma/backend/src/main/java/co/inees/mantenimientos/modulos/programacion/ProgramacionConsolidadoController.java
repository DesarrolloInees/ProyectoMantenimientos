package co.inees.mantenimientos.modulos.programacion;

import java.time.DayOfWeek;
import java.time.LocalDate;
import java.time.temporal.TemporalAdjusters;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/** Port de programacionConsolidadoControlador.php + programacionConsolidadoModelo.php */
@RestController
@RequestMapping("/api/programacionConsolidado")
public class ProgramacionConsolidadoController {

    private final Db db;

    public ProgramacionConsolidadoController(Db db) {
        this.db = db;
    }

    private List<Map<String, Object>> modalidades() {
        return db.list("SELECT id_modalidad, nombre_modalidad FROM modalidad_operativa ORDER BY nombre_modalidad ASC");
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> index(Req req) {
        LocalDate hoy = LocalDate.now();
        String ini = req.has("fecha_inicio") ? req.str("fecha_inicio") : hoy.with(TemporalAdjusters.previousOrSame(DayOfWeek.MONDAY)).toString();
        String fin = req.has("fecha_fin") ? req.str("fecha_fin") : hoy.with(TemporalAdjusters.nextOrSame(DayOfWeek.SUNDAY)).toString();
        String del = req.str("delegacion", "");
        String tec = req.str("tecnico", "");
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Consolidado General de Programación");
        r.put("fecha_inicio", ini);
        r.put("fecha_fin", fin);
        r.put("delegacion", del);
        r.put("tecnico", tec);
        r.put("listaDelegaciones", db.list("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC"));
        r.put("listaTecnicos", db.list("SELECT id_tecnico, nombre_tecnico, COALESCE(codigo_ruta, 'S/R') AS codigo_ruta FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
        r.put("listaModalidades", modalidades());
        r.put("consolidado", consolidado(ini, fin, del, tec));
        return r;
    }

    private List<Map<String, Object>> consolidado(String ini, String fin, String del, String tec) {
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("inicio", ini);
        p.put("fin", fin);
        String sql = """
                SELECT os.id_ordenes_servicio AS id_orden, os.fecha_visita, os.estado,
                    t.id_tecnico, COALESCE(t.nombre_tecnico, 'Sin Técnico') AS nombre_tecnico,
                    COALESCE(t.codigo_ruta, 'S/R') AS codigo_ruta,
                    os.id_punto,
                    COALESCE(os.id_cliente, p.id_cliente) AS id_cliente,
                    p.id_cliente AS id_cliente_punto,
                    p.nombre_punto, p.direccion, p.codigo_1 AS codigo_1, p.zona, p.fecha_ultima_visita,
                    c.nombre_cliente, c.codigo_cliente,
                    m.nombre_municipio, d.nombre_delegacion,
                    COALESCE(os.id_maquina, mq.id_maquina,
                        (SELECT m1.id_maquina FROM maquina m1
                          WHERE m1.id_punto = p.id_punto AND m1.estado = 1
                          ORDER BY m1.id_maquina ASC LIMIT 1)) AS id_maquina,
                    COALESCE(os.id_modalidad, p.id_modalidad, 1) AS id_modalidad,
                    COALESCE(mq.device_id,
                        (SELECT m2.device_id FROM maquina m2
                          WHERE m2.id_punto = p.id_punto AND m2.estado = 1
                          ORDER BY m2.id_maquina ASC LIMIT 1), 'S/N') AS device_id,
                    COALESCE(mq.activo_operativo,
                        (SELECT m3.activo_operativo FROM maquina m3
                          WHERE m3.id_punto = p.id_punto AND m3.estado = 1
                          ORDER BY m3.id_maquina ASC LIMIT 1), 1) AS activo_operativo
                FROM ordenes_servicio os
                LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                LEFT JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                LEFT JOIN maquina mq ON os.id_maquina = mq.id_maquina
                WHERE os.estado = 2
                AND os.fecha_visita BETWEEN :inicio AND :fin""";
        if (!Conv.empty(del)) {
            sql += " AND p.id_delegacion = :delegacion";
            p.put("delegacion", del);
        }
        if (!Conv.empty(tec)) {
            sql += " AND os.id_tecnico = :tecnico";
            p.put("tecnico", tec);
        }
        sql += " ORDER BY os.fecha_visita ASC, t.codigo_ruta ASC, p.zona ASC";
        return db.list(sql, p);
    }

    /** En PHP sólo restaura las máquinas no programadas a Operativo (no devuelve contenido; el Excel se arma en el navegador). */
    @RequestMapping(params = "accion=exportarExcelConsolidado", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> exportarExcelConsolidado() {
        try {
            int n = db.update("""
                    UPDATE maquina m
                    SET m.activo_operativo = 1,
                        m.fecha_actualizacion = NOW()
                    WHERE m.activo_operativo = 0
                        AND m.estado = 1
                        AND NOT EXISTS (
                            SELECT 1
                            FROM ordenes_servicio os
                            WHERE os.id_punto = m.id_punto
                            AND os.estado = 2
                        )""");
            return Map.of("status", true, "filas_restauradas", n);
        } catch (Exception e) {
            return Map.of("status", false, "msg", String.valueOf(e.getMessage()));
        }
    }

    @PostMapping(params = "accion=eliminarOrden")
    public Map<String, Object> eliminarOrden(Req req) {
        int id = req.intv("id_orden", 0);
        if (id <= 0) return Map.of("status", false, "msg", "Identificador de orden inválido.");
        try {
            if (db.update("DELETE FROM ordenes_servicio WHERE id_ordenes_servicio = :id AND estado = 2", "id", id) > 0)
                return Map.of("status", true, "msg", "Orden eliminada de la programación.");
            return Map.of("status", false, "msg", "No se encontró una orden programada con ese identificador, o ya no está en estado programado.");
        } catch (Exception e) {
            return Map.of("status", false, "msg", "No se pudo eliminar la orden: " + e.getMessage());
        }
    }

    @PostMapping(params = "accion=actualizarOrden")
    public Map<String, Object> actualizarOrden(Req req) {
        String[] req1 = { "id_orden", "id_punto", "id_tecnico", "id_cliente", "fecha_visita" };
        for (String k : req1) if (Conv.empty(req.get(k))) return Map.of("status", false, "msg", "Faltan campos obligatorios.");
        int idOrden = req.intv("id_orden", 0), idPunto = req.intv("id_punto", 0), idTecnico = req.intv("id_tecnico", 0), idCliente = req.intv("id_cliente", 0);
        int idMaquina = req.str("id_maquina", "").isEmpty() ? 0 : req.intv("id_maquina", 0);
        int idModalidad = req.str("id_modalidad", "").isEmpty() ? 0 : req.intv("id_modalidad", 0);
        String fecha = req.str("fecha_visita", "").replaceAll("<[^>]*>", "").trim();
        try {
            if (idOrden <= 0) return Map.of("status", false, "msg", "Identificador de orden inválido.");
            if (idPunto <= 0 || idTecnico <= 0 || idCliente <= 0) return Map.of("status", false, "msg", "Debe seleccionar técnico, cliente y punto.");
            boolean fechaOk;
            try {
                fechaOk = fecha.matches("\\d{4}-\\d{2}-\\d{2}") && LocalDate.parse(fecha).toString().equals(fecha);
            } catch (Exception e) {
                fechaOk = false;
            }
            if (!fechaOk) return Map.of("status", false, "msg", "La fecha de visita no es válida.");
            Map<String, Object> actual = db.one("SELECT id_ordenes_servicio, id_punto FROM ordenes_servicio WHERE id_ordenes_servicio = :id AND estado = 2", "id", idOrden);
            if (actual == null) return Map.of("status", false, "msg", "No se encontró una orden programada válida para editar.");
            Map<String, Object> punto = db.one("""
                    SELECT p.id_punto, p.id_cliente, p.nombre_punto, p.zona, p.direccion,
                            COALESCE(p.id_modalidad, 1) AS id_modalidad
                    FROM punto p
                    WHERE p.id_punto = :id_punto AND p.estado = 1""", "id_punto", idPunto);
            if (punto == null) return Map.of("status", false, "msg", "El punto seleccionado no existe o está inactivo.");
            idCliente = Conv.toInt(punto.get("id_cliente"));
            if (Conv.toInt(actual.get("id_punto")) != idPunto) {
                long dup = db.scalarLong("SELECT COUNT(*) AS total FROM ordenes_servicio WHERE id_punto = :id_punto AND estado = 2 AND id_ordenes_servicio <> :id",
                        "id_punto", idPunto, "id", idOrden);
                if (dup > 0) return Map.of("status", false, "msg", "Ese punto ya tiene otra orden programada. Elimine o mueva la otra orden primero.");
            }
            if (idMaquina > 0 && db.one("SELECT id_maquina FROM maquina WHERE id_maquina = :id AND id_punto = :id_punto AND estado = 1", "id", idMaquina, "id_punto", idPunto) == null) {
                idMaquina = 0;
            }
            if (idMaquina <= 0) {
                Map<String, Object> mq = db.one("SELECT id_maquina FROM maquina WHERE id_punto = :id_punto AND estado = 1 ORDER BY id_maquina ASC LIMIT 1", "id_punto", idPunto);
                idMaquina = mq != null ? Conv.toInt(mq.get("id_maquina")) : 0;
            }
            if (idModalidad <= 0) idModalidad = Conv.toInt(punto.get("id_modalidad") != null ? punto.get("id_modalidad") : 1);
            db.update("""
                    UPDATE ordenes_servicio SET
                        id_punto = :id_punto,
                        id_tecnico = :id_tecnico,
                        id_cliente = :id_cliente,
                        id_maquina = :id_maquina,
                        id_modalidad = :id_modalidad,
                        fecha_visita = :fecha_visita
                    WHERE id_ordenes_servicio = :id
                      AND estado = 2""", "id_punto", idPunto, "id_tecnico", idTecnico, "id_cliente", idCliente, "id_maquina", idMaquina > 0 ? idMaquina : null,
                    "id_modalidad", idModalidad, "fecha_visita", fecha, "id", idOrden);
            Map<String, Object> r = new LinkedHashMap<>();
            r.put("status", true);
            r.put("msg", "Orden actualizada correctamente.");
            r.put("datos", ordenParaEdicion(idOrden));
            return r;
        } catch (Exception e) {
            return Map.of("status", false, "msg", "Error de base de datos: " + e.getMessage());
        }
    }

    private Map<String, Object> ordenParaEdicion(int idOrden) {
        return db.one("""
                SELECT os.id_ordenes_servicio AS id_orden, os.fecha_visita, os.estado,
                       os.id_punto,
                       COALESCE(os.id_cliente, p.id_cliente) AS id_cliente,
                       COALESCE(os.id_modalidad, p.id_modalidad, 1) AS id_modalidad,
                       COALESCE(os.id_maquina, mq.id_maquina,
                           (SELECT m1.id_maquina FROM maquina m1
                             WHERE m1.id_punto = p.id_punto AND m1.estado = 1
                             ORDER BY m1.id_maquina ASC LIMIT 1)) AS id_maquina,
                       t.id_tecnico, COALESCE(t.nombre_tecnico, 'Sin Técnico') AS nombre_tecnico,
                       COALESCE(t.codigo_ruta, 'S/R') AS codigo_ruta,
                       p.nombre_punto, p.direccion, p.codigo_1 AS codigo_1, p.zona, p.fecha_ultima_visita,
                       m.nombre_municipio, d.nombre_delegacion,
                       c.nombre_cliente, c.codigo_cliente,
                       COALESCE(mq.device_id,
                           (SELECT m2.device_id FROM maquina m2
                             WHERE m2.id_punto = p.id_punto AND m2.estado = 1
                             ORDER BY m2.id_maquina ASC LIMIT 1), 'S/N') AS device_id,
                       COALESCE(mq.activo_operativo,
                           (SELECT m3.activo_operativo FROM maquina m3
                             WHERE m3.id_punto = p.id_punto AND m3.estado = 1
                             ORDER BY m3.id_maquina ASC LIMIT 1), 1) AS activo_operativo
                FROM ordenes_servicio os
                LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                LEFT JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                LEFT JOIN cliente c ON COALESCE(os.id_cliente, p.id_cliente) = c.id_cliente
                LEFT JOIN maquina mq ON os.id_maquina = mq.id_maquina
                WHERE os.id_ordenes_servicio = :id""", "id", idOrden);
    }

    @PostMapping(params = "accion=obtenerClientesPorPunto")
    public Map<String, Object> obtenerClientesPorPunto(Req req) {
        int idPunto = req.intv("id_punto", 0);
        int excluir = req.intv("id_cliente_excluir", 0);
        if (idPunto <= 0) return Map.of("status", false, "msg", "Punto inválido.");
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("id_punto", idPunto);
        String sql = "SELECT DISTINCT c.id_cliente, c.nombre_cliente, c.codigo_cliente FROM cliente c INNER JOIN punto p ON c.id_cliente = p.id_cliente"
                + " WHERE p.id_punto = :id_punto AND c.estado = 1 AND p.estado = 1";
        if (excluir > 0) {
            sql += " AND c.id_cliente != :excluir_cliente";
            p.put("excluir_cliente", excluir);
        }
        sql += " ORDER BY c.nombre_cliente ASC";
        return Map.of("status", true, "datos", db.list(sql, p));
    }

    @RequestMapping(params = "accion=listarClientesEditar", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> listarClientesEditar() {
        return Map.of("status", true, "clientes", db.list("""
                SELECT DISTINCT c.id_cliente, c.nombre_cliente, c.codigo_cliente
                FROM cliente c
                INNER JOIN punto p ON p.id_cliente = c.id_cliente
                WHERE c.estado = 1 AND p.estado = 1
                ORDER BY c.nombre_cliente ASC"""));
    }

    @PostMapping(params = "accion=obtenerPuntosCliente")
    public Map<String, Object> obtenerPuntosCliente(Req req) {
        int idCliente = req.intv("id_cliente", 0);
        int puntoActual = req.has("id_punto_actual") ? req.intv("id_punto_actual", 0) : req.intv("id_punto_excluir", 0);
        int idOrden = req.intv("id_orden", 0);
        if (idCliente <= 0) return Map.of("status", false, "msg", "Cliente inválido.");
        List<Map<String, Object>> puntos;
        try {
            puntos = db.list("""
                    SELECT p.id_punto, p.nombre_punto, p.zona, p.direccion, p.fecha_ultima_visita,
                           COALESCE(p.id_modalidad, 1) AS id_modalidad,
                           COALESCE(mo.nombre_modalidad, 'Sin modalidad') AS nombre_modalidad,
                           (SELECT m1.id_maquina FROM maquina m1
                             WHERE m1.id_punto = p.id_punto AND m1.estado = 1
                             ORDER BY m1.id_maquina ASC LIMIT 1) AS id_maquina,
                           COALESCE((SELECT m2.device_id FROM maquina m2
                             WHERE m2.id_punto = p.id_punto AND m2.estado = 1
                             ORDER BY m2.id_maquina ASC LIMIT 1), 'S/N') AS device_id,
                           COALESCE((SELECT m3.activo_operativo FROM maquina m3
                             WHERE m3.id_punto = p.id_punto AND m3.estado = 1
                             ORDER BY m3.id_maquina ASC LIMIT 1), 1) AS activo_operativo,
                           CASE WHEN EXISTS (
                               SELECT 1 FROM ordenes_servicio os
                               WHERE os.id_punto = p.id_punto
                                 AND os.estado = 2
                                 AND os.id_ordenes_servicio <> :id_orden
                           ) THEN 1 ELSE 0 END AS ya_programado
                    FROM punto p
                    LEFT JOIN modalidad_operativa mo ON mo.id_modalidad = p.id_modalidad
                    WHERE p.id_cliente = :id_cliente AND p.estado = 1
                    ORDER BY p.nombre_punto ASC""", "id_orden", idOrden, "id_cliente", idCliente);
        } catch (Exception e) {
            puntos = List.of();
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("status", true);
        r.put("puntos", puntos);
        r.put("id_punto_actual", puntoActual);
        r.put("modalidades", modalidades());
        return r;
    }
}
