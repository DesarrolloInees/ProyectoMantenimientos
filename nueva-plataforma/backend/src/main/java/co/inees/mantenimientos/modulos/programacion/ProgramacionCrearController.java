package co.inees.mantenimientos.modulos.programacion;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.transaction.support.TransactionTemplate;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/** Port de programacionCrearControlador.php + programacionCrearModelo.php */
@RestController
@RequestMapping("/api/programacionCrear")
public class ProgramacionCrearController {

    private static final Logger log = LoggerFactory.getLogger(ProgramacionCrearController.class);
    private static final List<String> DIAS = List.of("lunes", "martes", "miercoles", "jueves", "viernes", "sabado");
    private final Db db;
    private final TransactionTemplate tx;

    public ProgramacionCrearController(Db db, TransactionTemplate tx) {
        this.db = db;
        this.tx = tx;
    }

    // ================================================================ VISTAS

    /** Datos base de la vista (index / previsualizar recargan lo mismo) */
    private Map<String, Object> base(String delegacion, List<Object> clientesSel) {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("listaDelegaciones", db.list("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC"));
        r.put("listaTecnicos", db.list("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
        r.put("listaMaquinasInactivas", maquinasFueraDeServicio());
        List<Map<String, Object>> clientes = List.of();
        List<Object> zonas = List.of();
        Map<String, Object> conteo = new LinkedHashMap<>();
        List<Object> sel = new ArrayList<>(clientesSel == null ? List.of() : clientesSel);
        if (!Conv.empty(delegacion)) {
            clientes = clientesPorDelegacion(delegacion);
            zonas = db.column("SELECT DISTINCT zona FROM punto WHERE id_delegacion = :id_delegacion AND zona IS NOT NULL AND zona != '' ORDER BY zona ASC",
                    "id_delegacion", delegacion);
            if (sel.isEmpty()) clientes.forEach(c -> sel.add(c.get("id_cliente")));
            conteo = contarPuntosPorZona(delegacion, sel);
        }
        r.put("delegacionSeleccionada", delegacion == null ? "" : delegacion);
        r.put("listaClientes", clientes);
        r.put("clientesSeleccionados", sel);
        r.put("listaZonas", zonas);
        r.put("conteoZonas", conteo);
        return r;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> index(Req req) {
        Map<String, Object> r = base(req.str("delegacion", ""), Req.asList(req.get("clientes")));
        r.put("titulo", "Programación de Rutas Diarias - Contingencias");
        r.put("errores", List.of());
        r.put("propuesta", List.of());
        return r;
    }

    @PostMapping(params = "accion=previsualizar_diario")
    public Map<String, Object> previsualizarDiario(Req req) {
        List<String> errores = new ArrayList<>();
        List<Map<String, Object>> propuesta = new ArrayList<>();
        List<Object> servicios = Req.asList(req.get("servicios_diarios"));
        if (!servicios.isEmpty()) {
            List<Integer> ids = servicios.stream().map(s -> Conv.toInt(Conv.map(s).get("id_punto"))).toList();
            Map<Integer, Map<String, Object>> info = infoPuntos(ids);
            for (Object o : servicios) {
                Map<String, Object> s = Conv.map(o);
                Map<String, Object> i = info.get(Conv.toInt(s.get("id_punto")));
                if (i != null) propuesta.add(item(s.get("id_punto"), s.get("id_tecnico"), s.get("fecha_visita"), i, null, true));
            }
        } else {
            errores.add("No se recibieron puntos para programar desde el modal.");
        }
        Map<String, Object> r = base("", List.of());
        r.put("titulo", "Previsualización de Programación Diaria");
        r.put("errores", errores);
        r.put("propuesta", propuesta);
        return r;
    }

    private static Map<String, Object> item(Object idPunto, Object idTecnico, Object fecha, Map<String, Object> info, Boolean sabadoFallido, boolean fueraServicio) {
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("id_punto", idPunto);
        p.put("id_tecnico", idTecnico);
        p.put("fecha_visita", fecha);
        p.put("zona", info.get("zona") != null ? info.get("zona") : "");
        p.put("nombre_punto", info.get("nombre_punto") != null ? info.get("nombre_punto") : "");
        p.put("nombre_cliente", info.get("nombre_cliente") != null ? info.get("nombre_cliente") : "");
        if (sabadoFallido != null) p.put("es_sabado_fallido", sabadoFallido);
        p.put("es_fuera_servicio", fueraServicio);
        return p;
    }

    @PostMapping(params = "accion=previsualizar")
    public Map<String, Object> previsualizar(Req req) {
        List<String> errores = new ArrayList<>();
        List<Map<String, Object>> propuesta = new ArrayList<>();
        String delegacion = req.str("delegacion", "");
        List<Object> clientes = Req.asList(req.get("clientes"));
        String fechaInicio = req.str("fecha_inicio", "");
        if (delegacion.isEmpty()) errores.add("Debe seleccionar una delegación.");
        if (clientes.isEmpty()) errores.add("Debe seleccionar al menos un cliente para programar.");
        if (fechaInicio.isEmpty()) errores.add("Debe ingresar la fecha de inicio.");
        if (Conv.empty(req.get("semanas")) || req.intv("semanas", 0) < 1) errores.add("Debe ingresar el número de semanas a programar.");
        boolean hayConfig = DIAS.stream().anyMatch(d -> !Conv.empty(req.get("tecnico_" + d)) && !Req.asList(req.get("zonas_" + d)).isEmpty());
        if (!hayConfig) errores.add("Debe configurar al menos un día de la semana con técnico y zonas.");

        if (errores.isEmpty()) {
            Map<String, Map<String, Object>> calendario = new LinkedHashMap<>();
            for (String d : DIAS) {
                Object tec = req.get("tecnico_" + d);
                List<Object> zonas = Req.asList(req.get("zonas_" + d));
                if (!Conv.empty(tec) && !zonas.isEmpty()) calendario.put(d, Map.of("id_tecnico", tec, "zonas", zonas));
            }
            Map<String, Object> puntosDia = Conv.map(req.get("puntos_dia"));
            boolean manual = DIAS.stream().anyMatch(d -> !Req.asList(puntosDia.get(d)).isEmpty());
            Map<String, Object> configuracion = null;
            if (manual) {
                LocalDate ini = LocalDate.parse(fechaInicio.substring(0, 10));
                LinkedHashSet<Integer> todos = new LinkedHashSet<>();
                for (String d : DIAS) for (Object pid : Req.asList(puntosDia.get(d))) if (Conv.toInt(pid) > 0) todos.add(Conv.toInt(pid));
                Map<Integer, Map<String, Object>> info = infoPuntos(new ArrayList<>(todos));
                for (int k = 0; k < DIAS.size(); k++) {
                    String d = DIAS.get(k);
                    Object tec = req.get("tecnico_" + d);
                    List<Object> pts = Req.asList(puntosDia.get(d));
                    if (Conv.empty(tec) || pts.isEmpty()) continue;
                    String fecha = ini.plusDays(k).toString();
                    for (Object pid : pts) {
                        int id = Conv.toInt(pid);
                        Map<String, Object> i = info.get(id);
                        if (i != null) propuesta.add(item(id, tec, fecha, i, false, Conv.toInt(i.get("es_fuera_servicio")) != 0));
                    }
                }
            } else {
                configuracion = new LinkedHashMap<>();
                configuracion.put("id_delegacion", delegacion);
                configuracion.put("clientes_ids", clientes);
                configuracion.put("calendario", calendario);
                configuracion.put("fecha_inicio", fechaInicio);
                configuracion.put("semanas", req.intv("semanas", 1));
                configuracion.put("max_servicios_dia", req.intv("max_servicios", 5));
                propuesta.addAll(generarProgramacionSemanal(configuracion));
            }

            List<Object> extra = Req.asList(req.get("puntos_aledanios_extra"));
            if (!extra.isEmpty()) {
                List<Integer> ids = new ArrayList<>(new LinkedHashSet<>(extra.stream().map(Conv::toInt).toList()));
                Map<Integer, Map<String, Object>> info = infoPuntos(ids);
                String primerDia = calendario.keySet().stream().findFirst().orElse(null);
                Object primerTec = primerDia != null ? calendario.get(primerDia).get("id_tecnico") : null;
                // En PHP $configuracion sólo existe en el modo automático; en modo manual se usa la fecha de hoy
                String fechaPrimer = configuracion != null && !Conv.empty(configuracion.get("fecha_inicio")) ? F.fechaValida(Conv.str(configuracion.get("fecha_inicio")), F.hoy()) : F.hoy();
                for (Integer id : ids) {
                    if (propuesta.stream().anyMatch(p -> Conv.toInt(p.get("id_punto")) == id)) continue;
                    Map<String, Object> i = info.get(id);
                    if (i != null) propuesta.add(item(id, primerTec, fechaPrimer, i, false, true));
                }
            }
            if (propuesta.isEmpty()) errores.add("No se pudo generar la programación. Verifique que haya puntos pendientes en las zonas y clientes seleccionados.");
        }
        Map<String, Object> r = base(delegacion, clientes);
        r.put("titulo", "Programación de Rutas Semanales");
        r.put("errores", errores);
        r.put("propuesta", propuesta);
        return r;
    }

    @PostMapping(params = "accion=guardar_definitivo")
    public Map<String, Object> guardarDefinitivo(Req req) {
        List<Object> lista = Req.asList(req.get("final"));
        if (lista.isEmpty()) return Map.of("status", false, "vacio", true);
        Map<String, Object> r = guardarProgramacionDefinitiva(lista);
        if (Boolean.TRUE.equals(r.get("status"))) {
            return Map.of("status", true, "exito", "¡Programación guardada exitosamente! Se crearon " + r.get("count") + " órdenes.");
        }
        return Map.of("status", false, "error", "Error al guardar: " + r.get("msg"));
    }

    // ================================================================ AJAX

    @RequestMapping(params = "accion=obtener_puntos_zonas", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> obtenerPuntosZonas(Req req) {
        String delegacion = req.str("delegacion", "");
        Object zonasRaw = req.has("zonas") ? req.get("zonas") : req.get("zona");
        if (zonasRaw == null || lista(zonasRaw).isEmpty()) return Map.of("error", "No se especificaron zonas");
        List<String> zonas = lista(zonasRaw);
        List<Integer> clientes = lista(req.get("clientes")).stream().map(Conv::toInt).toList();
        return Map.of("puntos", puntosPorMultiplesZonas(delegacion, zonas, clientes));
    }

    @RequestMapping(params = "accion=obtener_puntos_zona", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> obtenerPuntosZona(Req req) {
        String delegacion = req.str("delegacion", ""), zona = req.str("zona", "");
        if (delegacion.isEmpty() || zona.isEmpty()) return Map.of("error", "Parámetros incompletos");
        return Map.of("puntos", puntosPorZona(delegacion, zona, List.of()));
    }

    @RequestMapping(params = "accion=puntos_aledanios", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> puntosAledanios(Req req) {
        int idPunto = req.intv("id_punto", 0);
        String zona = req.str("zona", "").trim();
        if (zona.isEmpty()) return Map.of("error", "No se especificó ninguna zona.");
        return Map.of("puntos", puntosAledaniosQ(idPunto, lista(zona)));
    }

    @PostMapping(params = "accion=restaurarOperativo")
    public Map<String, Object> restaurarOperativo(Req req) {
        List<Integer> sel = Conv.list(Req.jsonDecode(req.str("puntos_seleccionados", "[]"))).stream().map(Conv::toInt).toList();
        try {
            int[] n = { 0 };
            tx.executeWithoutResult(s -> {
                for (var m : db.list("SELECT m.id_maquina, m.device_id, m.id_punto FROM maquina m WHERE m.activo_operativo = 0 AND m.estado = 1")) {
                    if (!sel.contains(Conv.toInt(m.get("id_punto")))) {
                        db.update("UPDATE maquina SET activo_operativo = 1, fecha_actualizacion = NOW() WHERE id_maquina = :id", "id", m.get("id_maquina"));
                        n[0]++;
                    }
                }
            });
            return Map.of("status", true, "restauradas", n[0]);
        } catch (Exception e) {
            return Map.of("status", false, "msg", String.valueOf(e.getMessage()));
        }
    }

    @PostMapping(params = "accion=restaurarMaquinaIndividual")
    public Map<String, Object> restaurarMaquinaIndividual(Req req) {
        String dev = req.str("device_id", "");
        if (dev.isEmpty()) return Map.of("status", false, "msg", "Device ID vacio");
        boolean ok;
        try {
            ok = db.update("UPDATE maquina SET activo_operativo = 1, fecha_actualizacion = NOW() WHERE device_id = :dev AND estado = 1", "dev", dev) > 0;
        } catch (Exception e) {
            ok = false;
        }
        return Map.of("status", ok, "msg", ok ? "Maquina restaurada a Operativo" : "No se pudo restaurar");
    }

    private static List<String> lista(Object v) {
        List<String> r = new ArrayList<>();
        if (v instanceof List<?> || v instanceof Map<?, ?>) Req.asList(v).forEach(x -> r.add(Conv.str(x).trim()));
        else if (!Conv.empty(v)) for (String s : Conv.str(v).split(",")) r.add(s.trim());
        r.removeIf(String::isEmpty);
        return r;
    }

    // ================================================================ MODELO

    private List<Map<String, Object>> clientesPorDelegacion(String del) {
        return db.list("""
                SELECT DISTINCT c.id_cliente, c.nombre_cliente, c.codigo_cliente,
                       COUNT(p.id_punto) as total_puntos,
                       SUM(CASE WHEN (p.fecha_ultima_visita IS NULL OR DATEDIFF(NOW(), p.fecha_ultima_visita) >= 30) THEN 1 ELSE 0 END) as puntos_pendientes
                FROM cliente c
                INNER JOIN punto p ON c.id_cliente = p.id_cliente
                WHERE p.id_delegacion = :id_delegacion
                  AND p.estado = 1
                  AND c.estado = 1
                GROUP BY c.id_cliente, c.nombre_cliente, c.codigo_cliente
                HAVING puntos_pendientes > 0
                ORDER BY c.nombre_cliente ASC""", "id_delegacion", del);
    }

    private static final String PENDIENTE_SIN_ORDEN = """
             AND NOT EXISTS (
                SELECT 1
                FROM ordenes_servicio os
                WHERE os.id_punto = p.id_punto
                AND os.estado = 2
            )""";

    private Map<String, Object> contarPuntosPorZona(String del, List<Object> clientes) {
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("delegacion", del);
        String sql = "SELECT p.zona, COUNT(*) as total FROM punto p WHERE p.estado = 1 AND p.id_delegacion = :delegacion"
                + " AND (p.fecha_ultima_visita IS NULL OR DATEDIFF(NOW(), p.fecha_ultima_visita) >= 30)" + PENDIENTE_SIN_ORDEN;
        if (!clientes.isEmpty()) {
            sql += " AND p.id_cliente IN (:clientes)";
            p.put("clientes", clientes);
        }
        sql += " GROUP BY p.zona ORDER BY p.zona ASC";
        Map<String, Object> r = new LinkedHashMap<>();
        db.list(sql, p).forEach(row -> r.put(Conv.str(row.get("zona")), row.get("total")));
        return r;
    }

    private List<Map<String, Object>> puntosPorZona(String del, String zona, List<Object> clientes) {
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("delegacion", del);
        p.put("zona", zona);
        String sql = """
                SELECT p.id_punto, p.nombre_punto, p.zona, p.fecha_ultima_visita,
                        c.nombre_cliente, m.nombre_municipio,
                        DATEDIFF(NOW(), p.fecha_ultima_visita) as dias_sin_visita
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
                WHERE p.estado = 1
                AND p.id_delegacion = :delegacion
                AND p.zona = :zona
                AND (p.fecha_ultima_visita IS NULL OR DATEDIFF(NOW(), p.fecha_ultima_visita) >= 30)""" + PENDIENTE_SIN_ORDEN;
        if (!clientes.isEmpty()) {
            sql += " AND p.id_cliente IN (:clientes)";
            p.put("clientes", clientes);
        }
        sql += " ORDER BY p.fecha_ultima_visita ASC, m.nombre_municipio ASC";
        return db.list(sql, p);
    }

    private static final String SELECT_DETALLE_PUNTO = """
            SELECT p.id_punto, p.nombre_punto, p.direccion, p.zona, p.fecha_ultima_visita,
                    c.nombre_cliente, c.codigo_cliente,
                    m.nombre_municipio,
                    (SELECT mq.device_id FROM maquina mq WHERE mq.id_punto = p.id_punto AND mq.estado = 1 ORDER BY mq.id_maquina ASC LIMIT 1) as device_id,
                    (SELECT tm.nombre_tipo_maquina FROM maquina mq2
                        INNER JOIN tipo_maquina tm ON mq2.id_tipo_maquina = tm.id_tipo_maquina
                        WHERE mq2.id_punto = p.id_punto AND mq2.estado = 1 ORDER BY mq2.id_maquina ASC LIMIT 1) as tipo_maquina,
                    CASE WHEN EXISTS (SELECT 1 FROM maquina mq3 WHERE mq3.id_punto = p.id_punto AND mq3.activo_operativo = 0 AND mq3.estado = 1) THEN 1 ELSE 0 END as fuera_de_servicio,
                    DATEDIFF(NOW(), p.fecha_ultima_visita) as dias_sin_visita
            FROM punto p
            INNER JOIN cliente c ON p.id_cliente = c.id_cliente
            LEFT JOIN municipio m ON p.id_municipio = m.id_municipio
            WHERE p.estado = 1
                AND p.zona IN (:zonas)
                AND (
                    EXISTS (SELECT 1 FROM maquina mq4 WHERE mq4.id_punto = p.id_punto AND mq4.activo_operativo = 0 AND mq4.estado = 1)
                    OR p.fecha_ultima_visita IS NULL
                    OR DATEDIFF(NOW(), p.fecha_ultima_visita) >= 30
                )""";

    private List<Map<String, Object>> puntosPorMultiplesZonas(String del, List<String> zonas, List<Integer> clientes) {
        if (zonas.isEmpty()) return List.of();
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("zonas", zonas);
        String sql = SELECT_DETALLE_PUNTO + PENDIENTE_SIN_ORDEN;
        if (!Conv.empty(del)) {
            sql += " AND p.id_delegacion = :delegacion";
            p.put("delegacion", del);
        }
        if (!clientes.isEmpty()) {
            sql += " AND p.id_cliente IN (:clientes)";
            p.put("clientes", clientes);
        }
        sql += " ORDER BY fuera_de_servicio DESC, p.zona ASC, p.fecha_ultima_visita ASC, p.nombre_punto ASC";
        return db.list(sql, p);
    }

    private List<Map<String, Object>> puntosAledaniosQ(int idPunto, List<String> zonas) {
        if (zonas.isEmpty()) return List.of();
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("zonas", zonas);
        String sql = SELECT_DETALLE_PUNTO + PENDIENTE_SIN_ORDEN;
        if (idPunto > 0) {
            sql += " AND p.id_punto != :id_punto";
            p.put("id_punto", idPunto);
        }
        sql += " ORDER BY fuera_de_servicio DESC, p.fecha_ultima_visita ASC, c.nombre_cliente ASC";
        return db.list(sql, p);
    }

    private Map<Integer, Map<String, Object>> infoPuntos(List<Integer> ids) {
        Map<Integer, Map<String, Object>> r = new LinkedHashMap<>();
        if (ids.isEmpty()) return r;
        db.list("""
                SELECT p.id_punto, p.nombre_punto, p.zona, c.nombre_cliente,
                       CASE WHEN EXISTS (SELECT 1 FROM maquina mq3 WHERE mq3.id_punto = p.id_punto AND mq3.activo_operativo = 0 AND mq3.estado = 1) THEN 1 ELSE 0 END as es_fuera_servicio
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                WHERE p.id_punto IN (:ids)""", "ids", ids).forEach(row -> r.put(Conv.toInt(row.get("id_punto")), row));
        return r;
    }

    private List<Map<String, Object>> maquinasFueraDeServicio() {
        return db.list("""
                SELECT m.id_maquina, m.device_id,
                        p.id_punto, p.nombre_punto, p.zona, p.direccion,
                        c.nombre_cliente, c.codigo_cliente,
                        tm.nombre_tipo_maquina
                FROM maquina m
                INNER JOIN punto p ON m.id_punto = p.id_punto
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE m.activo_operativo = 0 AND m.estado = 1 AND p.estado = 1
                AND NOT EXISTS (
                    SELECT 1
                    FROM ordenes_servicio os
                    WHERE os.id_punto = p.id_punto
                    AND os.estado = 2
                )
                ORDER BY p.zona ASC, c.nombre_cliente ASC""");
    }

    @SuppressWarnings("unchecked")
    private List<Map<String, Object>> generarProgramacionSemanal(Map<String, Object> cfg) {
        List<Map<String, Object>> propuesta = new ArrayList<>();
        List<Object> asignados = new ArrayList<>();
        LocalDate ini = LocalDate.parse(Conv.str(cfg.get("fecha_inicio")).substring(0, 10));
        Map<String, Map<String, Object>> cal = (Map<String, Map<String, Object>>) cfg.get("calendario");
        int semanas = Conv.toInt(cfg.get("semanas"));
        int max = Conv.toInt(cfg.get("max_servicios_dia"));
        List<Object> clientes = Conv.list(cfg.get("clientes_ids"));
        Map<String, List<Map<String, Object>>> porZona = new LinkedHashMap<>();
        for (var c : cal.values()) {
            for (Object z : Conv.list(c.get("zonas"))) {
                porZona.computeIfAbsent(Conv.str(z), k -> puntosPorZona(Conv.str(cfg.get("id_delegacion")), k, clientes));
            }
        }
        for (int s = 0; s < semanas; s++) {
            for (int n = 1; n <= 6; n++) {
                Map<String, Object> c = cal.get(DIAS.get(n - 1));
                if (c == null) continue;
                String fecha = ini.plusDays(s * 7L + (n - 1)).toString();
                List<Map<String, Object>> cand = new ArrayList<>();
                for (Object z : Conv.list(c.get("zonas"))) cand.addAll(porZona.getOrDefault(Conv.str(z), List.of()));
                int cuenta = 0;
                for (var p : cand) {
                    if (cuenta >= max) break;
                    Object id = p.get("id_punto");
                    if (asignados.stream().anyMatch(a -> Conv.looseEq(a, id))) continue;
                    Map<String, Object> it = new LinkedHashMap<>();
                    it.put("id_punto", id);
                    it.put("id_tecnico", c.get("id_tecnico"));
                    it.put("fecha_visita", fecha);
                    it.put("zona", p.get("zona"));
                    it.put("nombre_punto", p.get("nombre_punto"));
                    it.put("nombre_cliente", p.get("nombre_cliente"));
                    it.put("es_sabado_fallido", false);
                    propuesta.add(it);
                    asignados.add(id);
                    cuenta++;
                }
            }
        }
        return propuesta;
    }

    private Map<String, Object> guardarProgramacionDefinitiva(List<Object> servicios) {
        try {
            return tx.execute(st -> {
                int count = 0;
                List<String> errores = new ArrayList<>();
                for (Object o : servicios) {
                    Map<String, Object> s = Conv.map(o);
                    if (Conv.empty(s.get("id_punto")) || Conv.empty(s.get("id_tecnico")) || Conv.empty(s.get("fecha_visita"))) continue;
                    Map<String, Object> d = db.one("""
                            SELECT
                                p.id_cliente,
                                p.id_modalidad,
                                COALESCE(
                                    (SELECT m.id_maquina
                                        FROM maquina m
                                        WHERE m.id_punto = p.id_punto
                                        AND m.estado = 1
                                        ORDER BY m.id_maquina ASC
                                    LIMIT 1),
                                    0
                                ) as id_maquina
                            FROM punto p
                            WHERE p.id_punto = :id_punto""", "id_punto", s.get("id_punto"));
                    if (d == null) {
                        errores.add("Error con el punto ID " + s.get("id_punto") + ": no existe en la base de datos.");
                        continue;
                    }
                    try {
                        db.update("""
                                INSERT INTO ordenes_servicio (
                                    id_punto, id_tecnico, id_cliente, id_maquina, id_modalidad, fecha_visita, estado, created_at
                                ) VALUES (
                                    :id_punto, :id_tecnico, :id_cliente, :id_maquina, :id_modalidad, :fecha_visita, 2, NOW()
                                )""", "id_punto", s.get("id_punto"), "id_tecnico", s.get("id_tecnico"), "id_cliente", d.get("id_cliente"),
                                "id_maquina", Conv.empty(d.get("id_maquina")) ? null : d.get("id_maquina"),
                                "id_modalidad", Conv.empty(d.get("id_modalidad")) ? null : d.get("id_modalidad"), "fecha_visita", s.get("fecha_visita"));
                        count++;
                    } catch (Exception e) {
                        errores.add("Error guardando Punto " + s.get("id_punto") + ": " + e.getMessage());
                    }
                }
                if (count > 0) return Map.<String, Object>of("status", true, "count", count, "msg", "Se crearon " + count + " órdenes programadas.", "errores", errores);
                st.setRollbackOnly();
                return Map.<String, Object>of("status", false, "msg", "No se pudo insertar ninguna orden. " + String.join(" | ", errores));
            });
        } catch (Exception e) {
            log.error("Error guardando programación: {}", e.getMessage());
            return Map.of("status", false, "msg", "Error de BD: " + e.getMessage());
        }
    }
}
