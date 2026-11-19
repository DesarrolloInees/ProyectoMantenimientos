package co.inees.mantenimientos.modulos.orden;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Service;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/** Port de app/models/orden/ordenDetalleModelo.php */
@Service
public class OrdenDetalleModelo {

    private static final Logger log = LoggerFactory.getLogger(OrdenDetalleModelo.class);
    private static final Pattern P_HORA = Pattern.compile("^(\\d{1,2}):(\\d{2})");

    private final Db db;

    public OrdenDetalleModelo(Db db) {
        this.db = db;
    }

    private static String selectServicios(String repuestosTexto) {
        return """
                SELECT
                    o.id_ordenes_servicio,
                    o.numero_remision,
                    o.fecha_visita,
                    o.hora_entrada,
                    o.hora_salida,
                    o.tiempo_servicio,
                    o.valor_servicio,
                    o.valor_viaticos,
                    o.dias_viaticos,
                    o.es_fuera_delegacion,
                    o.actividades_realizadas as que_se_hizo,
                    o.tiene_novedad,
                    o.detalle_novedad,
                    o.repuestos_tecnico,
                    IFNULL(
                        (SELECT GROUP_CONCAT(tn.nombre_novedad SEPARATOR ', ')
                        FROM orden_servicio_novedad osn
                        JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                        WHERE osn.id_orden_servicio = o.id_ordenes_servicio)
                    , '') as nombres_novedades,
                    IFNULL(
                        (SELECT GROUP_CONCAT(osn.id_tipo_novedad SEPARATOR ',')
                        FROM orden_servicio_novedad osn
                        WHERE osn.id_orden_servicio = o.id_ordenes_servicio)
                    , '') as ids_novedades,
                    o.id_maquina,
                    m.device_id,
                    tm.nombre_tipo_maquina,
                    tm.id_tipo_maquina,
                    COALESCE(o.id_cliente, c_maq.id_cliente) as id_cliente,
                    COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente) as nombre_cliente,
                    COALESCE(o.id_punto, p_maq.id_punto) as id_punto,
                    COALESCE(p_directo.nombre_punto, p_maq.nombre_punto) as nombre_punto,
                    COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion) as delegacion,
                    COALESCE(p_directo.id_delegacion, p_maq.id_delegacion) as id_delegacion,
                    COALESCE(o.id_modalidad, 1) as id_modalidad,
                    CASE
                        WHEN o.id_modalidad = 1 THEN 'URBANO'
                        WHEN o.id_modalidad = 2 THEN 'INTERURBANO'
                        ELSE 'NO DEFINIDO'
                    END as tipo_zona,
                    o.id_tecnico, t.nombre_tecnico,
                    o.id_tipo_mantenimiento as id_manto, tman.nombre_completo as tipo_servicio,
                    o.id_estado_maquina as id_estado, em.nombre_estado as estado_maquina,
                    o.id_calificacion as id_calif, cal.nombre_calificacion,
                """ + repuestosTexto + """
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
                """;
    }

    private static final String REP_TEXTO_FECHA = """
                    IFNULL(
                        (SELECT GROUP_CONCAT(
                        CONCAT(
                            r.nombre_repuesto,
                            CASE
                                WHEN osr.origen = 'PROSEGUR' THEN ' (PROSEGUR)'
                                WHEN osr.origen = 'INEES' THEN ' (INEES)'
                                ELSE CONCAT(' (', osr.origen, ')')
                            END,
                            CASE
                                WHEN osr.cantidad > 1 THEN CONCAT(' (x', osr.cantidad, ')')
                                ELSE ''
                            END
                        )
                        ORDER BY r.nombre_repuesto
                        SEPARATOR ', ')
                        FROM orden_servicio_repuesto osr
                        JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                        WHERE osr.id_orden_servicio = o.id_ordenes_servicio)
                    , '') as repuestos_texto
            """;

    private static final String REP_TEXTO_BUSQUEDA = """
                    IFNULL(
                        (SELECT GROUP_CONCAT(
                            CONCAT(r.nombre_repuesto, ' (', osr.origen, ')', IF(osr.cantidad>1, CONCAT(' x', osr.cantidad), ''))
                            SEPARATOR ', ')
                        FROM orden_servicio_repuesto osr
                        JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                        WHERE osr.id_orden_servicio = o.id_ordenes_servicio)
                    , '') as repuestos_texto
            """;

    private void adjuntarRepuestosJson(List<Map<String, Object>> resultados) {
        List<Object> ids = new ArrayList<>();
        for (Map<String, Object> r : resultados) ids.add(r.get("id_ordenes_servicio"));
        Map<String, List<Map<String, Object>>> agrupados = new LinkedHashMap<>();
        if (!ids.isEmpty()) {
            Map<String, Object> p = new LinkedHashMap<>();
            String in = Db.in("o", ids, p);
            List<Map<String, Object>> todos = db.list("""
                    SELECT osr.id_orden_servicio, r.id_repuesto as id, r.nombre_repuesto as nombre, osr.origen, osr.cantidad
                    FROM orden_servicio_repuesto osr
                    JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    WHERE osr.id_orden_servicio IN (""" + in + ")", p);
            for (Map<String, Object> rep : todos) {
                String idO = Conv.str(rep.remove("id_orden_servicio"));
                agrupados.computeIfAbsent(idO, k -> new ArrayList<>()).add(rep);
            }
        }
        for (Map<String, Object> row : resultados) {
            List<Map<String, Object>> l = agrupados.get(Conv.str(row.get("id_ordenes_servicio")));
            row.put("repuestos_json", l == null ? "[]" : Req.jsonEncode(l));
        }
    }

    public List<Map<String, Object>> obtenerServiciosPorFecha(String fecha) {
        List<Map<String, Object>> r = db.list(selectServicios(REP_TEXTO_FECHA) + """
                WHERE o.fecha_visita = :fecha
                AND o.estado = 1
                ORDER BY o.id_tecnico ASC, o.hora_entrada ASC""", "fecha", fecha);
        adjuntarRepuestosJson(r);
        return r;
    }

    public List<Map<String, Object>> buscarOrdenesFiltros(Map<String, String> f) {
        StringBuilder sql = new StringBuilder(selectServicios(REP_TEXTO_BUSQUEDA)).append(" WHERE 1=1 AND o.estado = 1 ");
        Map<String, Object> p = new LinkedHashMap<>();
        if (!Conv.empty(f.get("remision"))) {
            sql.append(" AND o.numero_remision LIKE :rem");
            p.put("rem", "%" + f.get("remision") + "%");
        }
        if (!Conv.empty(f.get("id_cliente"))) {
            sql.append(" AND (o.id_cliente = :cli1 OR c_maq.id_cliente = :cli2)");
            p.put("cli1", f.get("id_cliente"));
            p.put("cli2", f.get("id_cliente"));
        }
        if (!Conv.empty(f.get("id_punto"))) {
            sql.append(" AND (o.id_punto = :pun1 OR m.id_punto = :pun2)");
            p.put("pun1", f.get("id_punto"));
            p.put("pun2", f.get("id_punto"));
        }
        if (!Conv.empty(f.get("fecha_inicio"))) {
            sql.append(" AND o.fecha_visita >= :fi");
            p.put("fi", f.get("fecha_inicio"));
        }
        if (!Conv.empty(f.get("fecha_fin"))) {
            sql.append(" AND o.fecha_visita <= :ff");
            p.put("ff", f.get("fecha_fin") + " 23:59:59");
        }
        if (!Conv.empty(f.get("id_delegacion"))) {
            sql.append(" AND (p_directo.id_delegacion = :del1 OR p_maq.id_delegacion = :del2)");
            p.put("del1", f.get("id_delegacion"));
            p.put("del2", f.get("id_delegacion"));
        }
        sql.append(" ORDER BY o.fecha_visita DESC, o.id_ordenes_servicio DESC LIMIT 100");
        List<Map<String, Object>> r = db.list(sql.toString(), p);
        adjuntarRepuestosJson(r);
        return r;
    }

    // ---------------------------------------------------------------- catálogos

    public List<Map<String, Object>> obtenerTodosLosClientes() {
        return db.list("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC");
    }

    public List<Map<String, Object>> obtenerPuntosPorCliente(Object id) {
        return db.list("SELECT id_punto, nombre_punto FROM punto WHERE id_cliente = :id AND estado = 1 ORDER BY nombre_punto ASC", "id", id);
    }

    public List<Map<String, Object>> obtenerMaquinasPorPunto(Object id) {
        return db.list("""
                SELECT m.id_maquina, m.device_id, tm.nombre_tipo_maquina, tm.id_tipo_maquina
                FROM maquina m
                JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE m.id_punto = :id AND m.estado = 1
                ORDER BY m.device_id ASC""", "id", id);
    }

    public List<Map<String, Object>> obtenerTodosLosTecnicos() {
        return db.list("""
                SELECT
                    id_tecnico,
                    estado,
                    CASE
                        WHEN estado = 1 THEN nombre_tecnico
                        ELSE CONCAT(nombre_tecnico, ' (INACTIVO)')
                    END as nombre_tecnico
                FROM tecnico
                ORDER BY estado DESC, nombre_tecnico ASC""");
    }

    public List<Map<String, Object>> obtenerTiposMantenimiento() {
        return db.list("SELECT * FROM tipo_mantenimiento");
    }

    public List<Map<String, Object>> obtenerEstados() {
        return db.list("SELECT * FROM estado_maquina");
    }

    public List<Map<String, Object>> obtenerCalificaciones() {
        return db.list("SELECT * FROM calificacion_servicio");
    }

    public List<Map<String, Object>> obtenerModalidades() {
        return db.list("SELECT * FROM modalidad_operativa ORDER BY id_modalidad");
    }

    public List<Map<String, Object>> obtenerDelegaciones() {
        return db.list("SELECT id_delegacion, nombre_delegacion FROM delegacion ORDER BY nombre_delegacion ASC");
    }

    public List<Map<String, Object>> obtenerTiposNovedad() {
        return db.list("SELECT * FROM tipo_novedad WHERE estado = 1 ORDER BY nombre_novedad ASC");
    }

    public List<Object> obtenerFestivos() {
        try {
            return db.column("SELECT fecha FROM dias_festivos ORDER BY fecha ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> obtenerListaRepuestos() {
        try {
            return db.list("SELECT id_repuesto, nombre_repuesto FROM repuesto WHERE estado = 1 ORDER BY nombre_repuesto ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    public String obtenerDelegacionPorPunto(Object idPunto) {
        Object v = db.scalar("""
                SELECT d.nombre_delegacion
                FROM punto p
                JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                WHERE p.id_punto = :id""", "id", idPunto);
        return Conv.empty(v) ? "Sin Asignar" : String.valueOf(v);
    }

    /** -1 si no existe tarifa. */
    public double obtenerPrecioTarifa(Object idTipoMaquina, Object idTipoManto, Object idModalidad, Object anio) {
        Object anioVigencia = Conv.empty(anio) ? F.hoy().substring(0, 4) : anio;
        Map<String, Object> fila = db.one("""
                SELECT precio
                FROM tarifa
                WHERE id_tipo_maquina = :maq
                AND id_tipo_mantenimiento = :man
                AND id_modalidad = :mod
                AND año_vigencia = :anio
                LIMIT 1""", "maq", idTipoMaquina, "man", idTipoManto, "mod", idModalidad, "anio", anioVigencia);
        if (fila == null) return -1;
        return Conv.toDouble(fila.get("precio"));
    }

    public List<Map<String, Object>> obtenerStockPorTecnico(Object idTecnico) {
        return db.list("""
                SELECT
                    i.id_repuesto,
                    r.nombre_repuesto,
                    i.cantidad_actual
                FROM inventario_tecnico i
                JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                WHERE i.id_tecnico = :t AND i.cantidad_actual > 0 AND i.estado = 1
                ORDER BY r.nombre_repuesto ASC""", "t", idTecnico);
    }

    public List<Map<String, Object>> obtenerRemisionesDisponiblesPorTecnico(int idTecnico, String remisionActual) {
        List<Map<String, Object>> disp = new ArrayList<>(db.list("""
                SELECT numero_remision
                FROM control_remisiones
                WHERE id_tecnico = :t
                AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)
                ORDER BY CAST(numero_remision AS UNSIGNED) ASC""", "t", idTecnico));
        if (!Conv.empty(remisionActual)) {
            boolean ya = disp.stream().anyMatch(r -> Conv.looseEq(r.get("numero_remision"), remisionActual));
            if (!ya) {
                Map<String, Object> m = new LinkedHashMap<>();
                m.put("numero_remision", remisionActual);
                disp.add(0, m);
            }
        }
        return disp;
    }

    public Map<String, List<Map<String, Object>>> obtenerTodasRemisionesDisponibles() {
        List<Map<String, Object>> r = db.list("""
                SELECT id_tecnico, numero_remision
                FROM control_remisiones
                WHERE id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)
                ORDER BY CAST(numero_remision AS UNSIGNED) ASC""");
        Map<String, List<Map<String, Object>>> ag = new LinkedHashMap<>();
        for (Map<String, Object> x : r) {
            Map<String, Object> m = new LinkedHashMap<>();
            m.put("numero_remision", x.get("numero_remision"));
            ag.computeIfAbsent(Conv.str(x.get("id_tecnico")), k -> new ArrayList<>()).add(m);
        }
        return ag;
    }

    private double obtenerValorParametro(String clave) {
        try {
            Object v = db.scalar("SELECT valor FROM parametros WHERE clave = :clave LIMIT 1", "clave", clave);
            return v == null ? 0 : Conv.toDouble(v);
        } catch (Exception e) {
            return 0;
        }
    }

    private int obtenerIdDelegacionPunto(Object idPunto) {
        try {
            Object v = db.scalar("SELECT id_delegacion FROM punto WHERE id_punto = :id LIMIT 1", "id", idPunto);
            return v == null ? 0 : Conv.toInt(v);
        } catch (Exception e) {
            return 0;
        }
    }

    /** normalizarHora() del modelo: "930" -> 09:30:00, "7:5" inválido -> según regla. */
    static String normalizarHora(Object valor) {
        String v = Conv.str(valor).trim();
        if (v.isEmpty()) return "00:00:00";
        Matcher m = P_HORA.matcher(v);
        if (m.find()) {
            int h = Math.min(23, Integer.parseInt(m.group(1)));
            int mi = Math.min(59, Integer.parseInt(m.group(2)));
            return String.format("%02d:%02d:00", h, mi);
        }
        String nums = v.replaceAll("\\D", "");
        if (nums.isEmpty()) return "00:00:00";
        nums = (nums + "0000").substring(0, Math.max(4, nums.length())).substring(0, 4);
        int h = Math.min(23, Integer.parseInt(nums.substring(0, 2)));
        int mi = Math.min(59, Integer.parseInt(nums.substring(2, 4)));
        return String.format("%02d:%02d:00", h, mi);
    }

    private static Object dv(Map<String, Object> d, String k, Object def) {
        Object v = d.get(k);
        return v == null ? def : v;
    }

    public boolean actualizarOrdenFull(Object id, Map<String, Object> datos, int rolUsuario) {
        try {
            db.txRun(() -> actualizarOrdenFullTx(id, datos, rolUsuario));
            return true;
        } catch (Exception e) {
            log.error("CRITICAL ERROR actualizarOrdenFull: {}", e.getMessage());
            return false;
        }
    }

    private void actualizarOrdenFullTx(Object id, Map<String, Object> datos, int rolUsuario) {
        datos.put("entrada", normalizarHora(datos.get("entrada")));
        datos.put("salida", normalizarHora(datos.get("salida")));

        Map<String, Object> actual = db.one("""
                SELECT numero_remision, id_tecnico, id_punto, fecha_visita, id_maquina, id_tipo_mantenimiento, valor_servicio, id_modalidad
                FROM ordenes_servicio WHERE id_ordenes_servicio = :id""", "id", id);
        if (actual == null) actual = new LinkedHashMap<>();

        Object nuevaRemision = dv(datos, "remision", "");
        Object nuevoTecnico = dv(datos, "id_tecnico", actual.get("id_tecnico"));
        Object nuevoPunto = dv(datos, "id_punto", actual.get("id_punto"));
        Object nuevaFecha = datos.get("fecha_individual") != null ? datos.get("fecha_individual")
                : (actual.get("fecha_visita") != null ? actual.get("fecha_visita") : F.hoy());
        String fechaUso = nuevaFecha + " " + dv(datos, "entrada", "00:00:00");

        if (!actual.isEmpty() && (!Conv.looseEq(actual.get("numero_remision"), nuevaRemision)
                || !Conv.looseEq(actual.get("id_tecnico"), nuevoTecnico))) {
            db.update("""
                    UPDATE control_remisiones
                    SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1),
                        id_orden_servicio = NULL,
                        fecha_uso = NULL
                    WHERE id_orden_servicio = :id""", "id", id);
            if (!Conv.empty(nuevaRemision)) {
                db.update("""
                        UPDATE control_remisiones
                        SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1),
                            id_orden_servicio = :id,
                            fecha_uso = :fu
                        WHERE numero_remision = :rem AND id_tecnico = :tec""",
                        "id", id, "fu", fechaUso, "rem", nuevaRemision, "tec", nuevoTecnico);
            }
        }

        int[] principales = {1, 2, 3, 4, 5};
        int idDelPunto = obtenerIdDelegacionPunto(nuevoPunto);
        int idModalidad = datos.get("id_modalidad") != null ? Conv.toInt(datos.get("id_modalidad"))
                : (actual.get("id_modalidad") != null ? Conv.toInt(actual.get("id_modalidad")) : 1);

        int esFuera = 0, diasViaticos = 0;
        double valorViaticos = 0;
        if (idModalidad == 2) {
            boolean esPrincipal = false;
            for (int p : principales) if (p == idDelPunto) esPrincipal = true;
            if (idDelPunto > 0 && !esPrincipal) {
                esFuera = 1;
                long yaCobroHoy = db.scalarLong("""
                        SELECT COUNT(*) as total
                        FROM ordenes_servicio
                        WHERE id_tecnico = :t
                            AND fecha_visita = :f
                            AND valor_viaticos > 0
                            AND id_ordenes_servicio != :id""", "t", nuevoTecnico, "f", nuevaFecha, "id", id);
                if (yaCobroHoy == 0) {
                    diasViaticos = datos.get("dias_viaticos") != null ? Conv.toInt(datos.get("dias_viaticos")) : 1;
                    valorViaticos = diasViaticos * obtenerValorParametro("Recargo_Servicios_Interurbanos");
                }
            }
        }

        Object valorServicio = dv(datos, "valor", actual.get("valor_servicio"));
        Object idMaqUso = dv(datos, "id_maquina", actual.get("id_maquina"));
        Object idMantoUso = dv(datos, "id_manto", actual.get("id_tipo_mantenimiento"));

        boolean cambioPunto = !Conv.looseEq(actual.get("id_punto"), nuevoPunto);
        boolean cambioFecha = !Conv.looseEq(actual.get("fecha_visita"), nuevaFecha);
        boolean cambioModalidad = !Conv.looseEq(actual.get("id_modalidad"), idModalidad);
        boolean cambioMaquina = !Conv.looseEq(actual.get("id_maquina"), idMaqUso);
        boolean cambioManto = !Conv.looseEq(actual.get("id_tipo_mantenimiento"), idMantoUso);

        if (cambioPunto || cambioFecha || cambioModalidad || cambioMaquina || cambioManto || rolUsuario == 5) {
            Object idTipoMaq = db.scalar("SELECT id_tipo_maquina FROM maquina WHERE id_maquina = :id", "id", idMaqUso);
            String anioUso = Conv.str(nuevaFecha).length() >= 4 ? Conv.str(nuevaFecha).substring(0, 4) : F.hoy().substring(0, 4);
            double precio = obtenerPrecioTarifa(idTipoMaq, idMantoUso, idModalidad, anioUso);
            if (precio != -1) {
                valorServicio = precio;
            } else if (rolUsuario == 5 && !cambioPunto && !cambioFecha && !cambioModalidad && !cambioMaquina && !cambioManto) {
                valorServicio = actual.get("valor_servicio");
            } else {
                valorServicio = precio;
            }
        }

        Map<String, Object> p = new LinkedHashMap<>();
        p.put("cli", Conv.nullIfEmpty(datos.get("id_cliente")));
        p.put("pun", nuevoPunto);
        p.put("maq", Conv.nullIfEmpty(datos.get("id_maquina")));
        p.put("mod", idModalidad);
        p.put("rem", nuevaRemision);
        p.put("tec", nuevoTecnico);
        p.put("man", Conv.nullIfEmpty(datos.get("id_manto")));
        p.put("est", Conv.nullIfEmpty(datos.get("id_estado")));
        p.put("cal", Conv.nullIfEmpty(datos.get("id_calif")));
        p.put("he", dv(datos, "entrada", "00:00"));
        p.put("hs", dv(datos, "salida", "00:00"));
        p.put("ts", dv(datos, "tiempo", "00:00"));
        p.put("val", valorServicio);
        p.put("obs", dv(datos, "obs", ""));
        p.put("nov", dv(datos, "tiene_novedad", 0));
        p.put("fue", esFuera);
        p.put("dv", diasViaticos);
        p.put("vv", valorViaticos);
        p.put("fv", nuevaFecha);
        p.put("id", id);
        db.update("""
                UPDATE ordenes_servicio SET
                    id_cliente = :cli, id_punto = :pun, id_maquina = :maq, id_modalidad = :mod,
                    numero_remision = :rem, id_tecnico = :tec, id_tipo_mantenimiento = :man,
                    id_estado_maquina = :est, id_calificacion = :cal, hora_entrada = :he,
                    hora_salida = :hs, tiempo_servicio = :ts, valor_servicio = :val,
                    actividades_realizadas = :obs, tiene_novedad = :nov,
                    es_fuera_delegacion = :fue, dias_viaticos = :dv, valor_viaticos = :vv,
                    fecha_visita = :fv
                WHERE id_ordenes_servicio = :id""", p);

        if (!Conv.empty(nuevoPunto)) actualizarInfoMantenimientoPunto(nuevoPunto);

        if (!Conv.looseEq(actual.get("id_tecnico"), nuevoTecnico)) {
            db.update("UPDATE control_devolucion_repuestos SET id_tecnico = :t WHERE id_orden_servicio = :id", "t", nuevoTecnico, "id", id);
        }
    }

    public boolean actualizarInfoMantenimientoPunto(Object idPunto) {
        try {
            db.update("""
                    UPDATE punto p
                    JOIN (
                        SELECT id_punto, fecha_visita, id_tipo_mantenimiento
                        FROM ordenes_servicio
                        WHERE id_punto = :id_punto_b
                          AND id_tipo_mantenimiento != 4
                        ORDER BY fecha_visita DESC, id_ordenes_servicio DESC
                        LIMIT 1
                    ) AS ultima_real ON p.id_punto = ultima_real.id_punto
                    SET p.fecha_ultima_visita = ultima_real.fecha_visita,
                        p.id_ultimo_tipo_mantenimiento = ultima_real.id_tipo_mantenimiento
                    WHERE p.id_punto = :id_punto_a""", "id_punto_b", idPunto, "id_punto_a", idPunto);
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    // ---------------------------------------------------------------- repuestos en tiempo real

    public Map<String, Object> agregarRepuestoRealTime(Object idOrden, Object idRepuesto, Object cantidadObj, String origen, Object idTecnico) {
        int cantidad = Conv.toInt(cantidadObj);
        try {
            return db.tx(() -> {
                long nuevoStock = 0;
                if ("INEES".equals(origen)) {
                    Object stock = db.scalar("""
                            SELECT cantidad_actual FROM inventario_tecnico
                            WHERE id_tecnico = :t AND id_repuesto = :r FOR UPDATE""", "t", idTecnico, "r", idRepuesto);
                    if (stock == null || Conv.toLong(stock) < cantidad) {
                        throw new StockInsuficiente();
                    }
                    db.update("""
                            UPDATE inventario_tecnico
                            SET cantidad_actual = cantidad_actual - :c
                            WHERE id_tecnico = :t AND id_repuesto = :r""", "c", cantidad, "t", idTecnico, "r", idRepuesto);
                    nuevoStock = Conv.toLong(stock) - cantidad;
                }
                Object cantOrden = db.scalar("""
                        SELECT cantidad FROM orden_servicio_repuesto
                        WHERE id_orden_servicio = :o AND id_repuesto = :r AND origen = :or""", "o", idOrden, "r", idRepuesto, "or", origen);
                if (cantOrden != null) {
                    db.update("""
                            UPDATE orden_servicio_repuesto
                            SET cantidad = cantidad + :c
                            WHERE id_orden_servicio = :o AND id_repuesto = :r AND origen = :or""", "c", cantidad, "o", idOrden, "r", idRepuesto, "or", origen);
                } else {
                    db.update("""
                            INSERT INTO orden_servicio_repuesto
                            (id_orden_servicio, id_repuesto, origen, cantidad)
                            VALUES (:o, :r, :or, :c)""", "o", idOrden, "r", idRepuesto, "or", origen, "c", cantidad);
                }
                Object reqDev = db.scalar("SELECT requiere_devolucion FROM repuesto WHERE id_repuesto = :r", "r", idRepuesto);
                if (Conv.toInt(reqDev) == 1) {
                    Object cantCD = db.scalar("SELECT cantidad FROM control_devolucion_repuestos WHERE id_orden_servicio = :o AND id_repuesto = :r",
                            "o", idOrden, "r", idRepuesto);
                    if (cantCD != null) {
                        db.update("UPDATE control_devolucion_repuestos SET cantidad = cantidad + :c WHERE id_orden_servicio = :o AND id_repuesto = :r",
                                "c", cantidad, "o", idOrden, "r", idRepuesto);
                    } else {
                        db.update("""
                                INSERT INTO control_devolucion_repuestos (id_tecnico, id_orden_servicio, id_repuesto, cantidad, estado_devolucion)
                                VALUES (:t, :o, :r, :c, 'Pendiente')""", "t", idTecnico, "o", idOrden, "r", idRepuesto, "c", cantidad);
                    }
                }
                Map<String, Object> r = new LinkedHashMap<>();
                r.put("status", "ok");
                r.put("msg", "Agregado correctamente");
                r.put("nuevo_stock", nuevoStock);
                return r;
            });
        } catch (StockInsuficiente e) {
            return Map.of("status", "error", "msg", "Stock insuficiente en la maleta del técnico.");
        } catch (Exception e) {
            return Map.of("status", "error", "msg", "Error BD: " + e.getMessage());
        }
    }

    static class StockInsuficiente extends RuntimeException {
        StockInsuficiente() {
            super("Stock insuficiente");
        }
    }

    static class NoExiste extends RuntimeException {
        NoExiste() {
            super("No existe");
        }
    }

    public Map<String, Object> eliminarRepuestoRealTime(Object idOrden, Object idRepuesto, String origen, Object idTecnico) {
        try {
            return db.tx(() -> {
                Object cantidad = db.scalar("""
                        SELECT cantidad FROM orden_servicio_repuesto
                        WHERE id_orden_servicio = :o AND id_repuesto = :r AND origen = :or""", "o", idOrden, "r", idRepuesto, "or", origen);
                if (Conv.empty(cantidad)) throw new NoExiste();
                if ("INEES".equals(origen)) {
                    db.update("""
                            INSERT INTO inventario_tecnico (id_tecnico, id_repuesto, cantidad_actual, estado)
                            VALUES (:t, :r, :c1, 1)
                            ON DUPLICATE KEY UPDATE cantidad_actual = cantidad_actual + :c2""",
                            "t", idTecnico, "r", idRepuesto, "c1", cantidad, "c2", cantidad);
                }
                db.update("DELETE FROM orden_servicio_repuesto WHERE id_orden_servicio = :o AND id_repuesto = :r AND origen = :or",
                        "o", idOrden, "r", idRepuesto, "or", origen);
                Object reqDev = db.scalar("SELECT requiere_devolucion FROM repuesto WHERE id_repuesto = :r", "r", idRepuesto);
                if (Conv.toInt(reqDev) == 1) {
                    db.update("UPDATE control_devolucion_repuestos SET cantidad = cantidad - :c WHERE id_orden_servicio = :o AND id_repuesto = :r",
                            "c", cantidad, "o", idOrden, "r", idRepuesto);
                    db.update("DELETE FROM control_devolucion_repuestos WHERE id_orden_servicio = :o AND id_repuesto = :r AND cantidad <= 0",
                            "o", idOrden, "r", idRepuesto);
                }
                return Map.<String, Object>of("status", "ok", "msg", "Repuesto eliminado de la orden");
            });
        } catch (NoExiste e) {
            return Map.of("status", "error", "msg", "El repuesto no existe en esta orden.");
        } catch (Exception e) {
            return Map.of("status", "error", "msg", "Error BD: " + e.getMessage());
        }
    }

    // ---------------------------------------------------------------- novedades

    public boolean guardarNovedadesOrden(Object idOrden, List<Object> novedades) {
        try {
            db.txRun(() -> {
                db.update("DELETE FROM orden_servicio_novedad WHERE id_orden_servicio = :o", "o", idOrden);
                for (Object n : novedades) {
                    db.update("INSERT INTO orden_servicio_novedad (id_orden_servicio, id_tipo_novedad) VALUES (:o, :n)", "o", idOrden, "n", n);
                }
                db.update("UPDATE ordenes_servicio SET tiene_novedad = :t WHERE id_ordenes_servicio = :o", "t", novedades.isEmpty() ? 0 : 1, "o", idOrden);
            });
            return true;
        } catch (Exception e) {
            return false;
        }
    }
}
