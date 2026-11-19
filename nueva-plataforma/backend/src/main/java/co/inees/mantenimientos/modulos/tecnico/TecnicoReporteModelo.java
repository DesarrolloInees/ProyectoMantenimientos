package co.inees.mantenimientos.modulos.tecnico;

import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Component;
import org.springframework.transaction.support.TransactionTemplate;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/** Port de app/models/tecnico/tecnicoReporteModelo.php (compartido por tecnicoReporte y tecnicoReporteEditar) */
@Component
public class TecnicoReporteModelo {

    private static final Logger log = LoggerFactory.getLogger(TecnicoReporteModelo.class);
    private final Db db;
    private final Archivos archivos;
    private final TransactionTemplate tx;

    public TecnicoReporteModelo(Db db, Archivos archivos, TransactionTemplate tx) {
        this.db = db;
        this.archivos = archivos;
        this.tx = tx;
    }

    public int obtenerIdTecnicoPorUsuario(int idUsuario) {
        try {
            Map<String, Object> r = db.one("SELECT id_tecnico FROM tecnico WHERE usuario_id = :id_usuario LIMIT 1", "id_usuario", idUsuario);
            return r == null ? 0 : Conv.toInt(r.get("id_tecnico"));
        } catch (Exception e) {
            return 0;
        }
    }

    public List<Map<String, Object>> obtenerModalidades() {
        return db.list("SELECT id_modalidad, nombre_modalidad FROM modalidad_operativa ORDER BY id_modalidad ASC");
    }

    public List<Map<String, Object>> obtenerEstadosMaquina() {
        return db.list("SELECT id_estado, nombre_estado FROM estado_maquina ORDER BY id_estado ASC");
    }

    public List<Map<String, Object>> obtenerCalificaciones() {
        return db.list("SELECT id_calificacion, nombre_calificacion FROM calificacion_servicio ORDER BY id_calificacion ASC");
    }

    public Map<String, Object> obtenerDetalleOrden(int idOrden, int idTecnico) {
        try {
            return db.one("""
                    SELECT
                        os.id_ordenes_servicio,
                        os.id_cliente,
                        os.id_punto,
                        os.fecha_visita,
                        os.hora_entrada,
                        os.hora_salida,
                        os.id_tipo_mantenimiento,
                        COALESCE(os.id_modalidad, p.id_modalidad, 1) AS id_modalidad,
                        c.nombre_cliente,
                        p.nombre_punto,
                        p.direccion,
                        m.device_id,
                        tm.nombre_tipo_maquina,
                        tmt.nombre_completo AS tipo_mantenimiento,
                        os.id_estado_maquina,
                        os.actividades_realizadas
                    FROM ordenes_servicio os
                    LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                    LEFT JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                    LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN tipo_mantenimiento tmt ON os.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                    WHERE os.id_ordenes_servicio = :id_orden
                    AND os.id_tecnico = :id_tecnico
                    AND os.estado = 2 LIMIT 1""", "id_orden", idOrden, "id_tecnico", idTecnico);
        } catch (Exception e) {
            log.error("Error obtenerDetalleOrden: {}", e.getMessage());
            return null;
        }
    }

    public List<Map<String, Object>> obtenerRemisionesTecnico(int idTecnico) {
        try {
            return db.list("""
                    SELECT numero_remision
                    FROM control_remisiones
                    WHERE id_tecnico = :id_tecnico
                    AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)
                    ORDER BY CAST(numero_remision AS UNSIGNED) ASC""", "id_tecnico", idTecnico);
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> obtenerTiposMantenimientoTecnico() {
        try {
            return db.list("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1 AND id_tipo_mantenimiento != 5");
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> obtenerInventarioTecnico(int idTecnico) {
        try {
            return db.list("""
                    SELECT i.id_repuesto, r.nombre_repuesto, i.cantidad_actual
                    FROM inventario_tecnico i
                    INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                    WHERE i.id_tecnico = :id_tec AND i.estado = 1 AND i.cantidad_actual > 0
                    ORDER BY r.nombre_repuesto ASC""", "id_tec", idTecnico);
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> obtenerTodosLosRepuestos() {
        try {
            return db.list("SELECT id_repuesto, nombre_repuesto, codigo_referencia FROM repuesto WHERE estado = 1 ORDER BY nombre_repuesto ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    private int obtenerIdDelegacionPunto(Object idPunto) {
        try {
            Map<String, Object> r = db.one("SELECT id_delegacion FROM punto WHERE id_punto = :id LIMIT 1", "id", idPunto);
            return r == null ? 0 : Conv.toInt(r.get("id_delegacion"));
        } catch (Exception e) {
            return 0;
        }
    }

    private double obtenerValorParametro(String clave) {
        try {
            Map<String, Object> r = db.one("SELECT valor FROM parametros WHERE clave = :clave LIMIT 1", "clave", clave);
            return r == null ? 0 : Conv.toDouble(r.get("valor"));
        } catch (Exception e) {
            return 0;
        }
    }

    private boolean tecnicoYaCobroViaticosHoy(Object idTecnico, Object fecha, Object idOrdenActual) {
        String sql = "SELECT COUNT(*) as total FROM ordenes_servicio WHERE id_tecnico = :id_tec AND fecha_visita = :fecha AND valor_viaticos > 0";
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("id_tec", idTecnico);
        p.put("fecha", fecha);
        if (idOrdenActual != null && Conv.toInt(idOrdenActual) != 0) {
            sql += " AND id_ordenes_servicio != :id_orden";
            p.put("id_orden", idOrdenActual);
        }
        Map<String, Object> r = db.one(sql, p);
        return r != null && Conv.toInt(r.get("total")) > 0;
    }

    // ================================================================ EVIDENCIAS

    public boolean guardarEvidenciaFoto(int idOrden, String tipo, String ruta) {
        try {
            db.update("INSERT INTO evidencia_servicio (id_orden_servicio, tipo_evidencia, ruta_archivo) VALUES (:id_orden, :tipo, :ruta)",
                    "id_orden", idOrden, "tipo", tipo, "ruta", ruta);
            return true;
        } catch (Exception e) {
            log.error("Error guardando foto: {}", e.getMessage());
            return false;
        }
    }

    public List<Map<String, Object>> obtenerEvidenciasPorOrden(int idOrden) {
        try {
            return db.list("SELECT id_evidencia, tipo_evidencia, ruta_archivo FROM evidencia_servicio WHERE id_orden_servicio = :id_orden", "id_orden", idOrden);
        } catch (Exception e) {
            return List.of();
        }
    }

    public Map<String, Object> eliminarEvidencia(int idEvidencia) {
        try {
            Map<String, Object> foto = db.one("SELECT ruta_archivo FROM evidencia_servicio WHERE id_evidencia = :id", "id", idEvidencia);
            if (foto != null) {
                db.update("DELETE FROM evidencia_servicio WHERE id_evidencia = :id", "id", idEvidencia);
                return Map.of("success", true, "ruta", Conv.str(foto.get("ruta_archivo")));
            }
            return Map.of("success", false, "msj", "No se encontró la foto en la BD con ese ID.");
        } catch (Exception e) {
            return Map.of("success", false, "msj", "Error SQL al borrar: " + e.getMessage());
        }
    }

    public Map<String, Object> eliminarTodasEvidenciasOrden(int idOrden) {
        try {
            db.update("DELETE FROM evidencia_servicio WHERE id_orden_servicio = :id_orden", "id_orden", idOrden);
            return Map.of("success", true);
        } catch (Exception e) {
            return Map.of("success", false, "msj", "Error SQL al vaciar: " + e.getMessage());
        }
    }

    public boolean eliminarEvidenciaPorTipo(int idOrden, String tipo) {
        try {
            for (var f : db.list("SELECT ruta_archivo FROM evidencia_servicio WHERE id_orden_servicio = :id_orden AND tipo_evidencia = :tipo", "id_orden", idOrden, "tipo", tipo)) {
                archivos.borrar("app/" + Conv.str(f.get("ruta_archivo")));
            }
            db.update("DELETE FROM evidencia_servicio WHERE id_orden_servicio = :id_orden AND tipo_evidencia = :tipo", "id_orden", idOrden, "tipo", tipo);
            return true;
        } catch (Exception e) {
            log.error("Error eliminarEvidenciaPorTipo: {}", e.getMessage());
            return false;
        }
    }

    // ================================================================ REPUESTOS

    public void limpiarRepuestosOrden(int idOrden) {
        try {
            db.update("DELETE FROM orden_servicio_repuesto WHERE id_orden_servicio = :id_orden", "id_orden", idOrden);
        } catch (Exception e) {
            log.error("Error limpiando repuestos: {}", e.getMessage());
        }
    }

    public void guardarRepuestoOrden(int idOrden, int idRepuesto, int cantidad, Object origen) {
        try {
            db.update("""
                    INSERT INTO orden_servicio_repuesto (id_orden_servicio, id_repuesto, origen, cantidad, valor_unitario)
                    SELECT :id_orden, :id_repuesto, :origen, :cantidad, valor_venta
                    FROM repuesto
                    WHERE id_repuesto = :id_repuesto""", "id_orden", idOrden, "id_repuesto", idRepuesto, "cantidad", cantidad, "origen", origen);
        } catch (Exception e) {
            log.error("Error guardando repuesto: {}", e.getMessage());
        }
    }

    public List<Map<String, Object>> obtenerRepuestosGuardados(int idOrden) {
        return db.list("""
                SELECT ro.id_repuesto as id, r.nombre_repuesto as nombre, ro.cantidad, ro.origen
                FROM orden_servicio_repuesto ro
                INNER JOIN repuesto r ON ro.id_repuesto = r.id_repuesto
                WHERE ro.id_orden_servicio = :id""", "id", idOrden);
    }

    /** Guarda repuestos del JSON [{id, cantidad, origen}] (sólo si hay al menos uno, como en PHP). */
    public void guardarRepuestosJson(int idOrden, String json) {
        if (Conv.empty(json)) return;
        List<Object> lista = Conv.list(Req.jsonDecode(json));
        if (lista.isEmpty()) return;
        limpiarRepuestosOrden(idOrden);
        for (Object o : lista) {
            Map<String, Object> r = Conv.map(o);
            guardarRepuestoOrden(idOrden, Conv.toInt(r.get("id")), Conv.toInt(r.get("cantidad")), r.get("origen"));
        }
    }

    // ================================================================ TARIFA / VIÁTICOS (común a guardar y actualizar)

    private Map<String, Object> calcularValores(Map<String, Object> d) {
        Map<String, Object> info = db.one("""
                SELECT os.fecha_visita, os.id_punto, os.id_modalidad AS orden_modalidad, p.id_modalidad AS punto_modalidad, m.id_tipo_maquina
                FROM ordenes_servicio os
                LEFT JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN maquina m ON os.id_maquina = m.id_maquina
                WHERE os.id_ordenes_servicio = :id_orden""", "id_orden", d.get("id_ordenes_servicio"));
        double valorServicio = 0, valorViaticos = 0;
        int fuera = 0, dias = 0;
        int modalidad = !Conv.empty(d.get("id_modalidad")) ? Conv.toInt(d.get("id_modalidad"))
                : info != null && !Conv.empty(info.get("orden_modalidad")) ? Conv.toInt(info.get("orden_modalidad"))
                        : info != null && !Conv.empty(info.get("punto_modalidad")) ? Conv.toInt(info.get("punto_modalidad")) : 1;
        if (info != null) {
            String fv = Conv.str(info.get("fecha_visita"));
            int anio = fv.length() >= 4 ? Conv.toInt(fv.substring(0, 4)) : Conv.toInt(F.hoy().substring(0, 4));
            Map<String, Object> t = db.one("""
                    SELECT precio FROM tarifa
                    WHERE id_tipo_maquina = :tipo_maq
                        AND id_tipo_mantenimiento = :tipo_manto
                        AND id_modalidad = :modalidad
                        AND año_vigencia = :anio
                    LIMIT 1""", "tipo_maq", info.get("id_tipo_maquina"), "tipo_manto", d.get("id_tipo_mantenimiento"), "modalidad", modalidad, "anio", anio);
            if (t != null && t.get("precio") != null) valorServicio = Conv.toDouble(t.get("precio"));
            int del = obtenerIdDelegacionPunto(info.get("id_punto"));
            if (modalidad == 2 && del > 0 && !List.of(1, 2, 3, 4, 5).contains(del)) {
                fuera = 1;
                if (!tecnicoYaCobroViaticosHoy(d.get("id_tecnico"), info.get("fecha_visita"), d.get("id_ordenes_servicio"))) {
                    dias = 1;
                    valorViaticos = dias * obtenerValorParametro("Recargo_Servicios_Interurbanos");
                }
            }
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("id_modalidad", modalidad);
        r.put("valor_servicio", valorServicio);
        r.put("es_fuera", fuera);
        r.put("dias_viaticos", dias);
        r.put("valor_viaticos", valorViaticos);
        return r;
    }

    public boolean guardarReporteTecnico(Map<String, Object> d) {
        try {
            Map<String, Object> v = calcularValores(d);
            Map<String, Object> p = new LinkedHashMap<>(v);
            p.put("id_cliente", d.get("id_cliente"));
            p.put("id_punto", d.get("id_punto"));
            p.put("remision", d.get("numero_remision"));
            p.put("id_tipo_manto", d.get("id_tipo_mantenimiento"));
            p.put("entrada", d.get("hora_entrada"));
            p.put("salida", d.get("hora_salida"));
            p.put("tiempo", d.get("tiempo_servicio") != null ? d.get("tiempo_servicio") : "00:00");
            p.put("actividades", d.get("actividades_realizadas"));
            p.put("id_estado", d.get("id_estado_maquina"));
            p.put("id_calif", d.get("id_calificacion"));
            p.put("tiene_novedad", d.get("tiene_novedad"));
            p.put("detalle_novedad", d.get("detalle_novedad"));
            p.put("soporte_remoto", d.get("soporte_remoto"));
            p.put("repuestos_tecnico", d.get("repuestos_tecnico"));
            p.put("id_orden", d.get("id_ordenes_servicio"));
            p.put("id_tecnico", d.get("id_tecnico"));
            db.update("""
                    UPDATE ordenes_servicio SET
                        id_cliente = :id_cliente,
                        id_punto = :id_punto,
                        id_modalidad = :id_modalidad,
                        numero_remision = :remision,
                        id_tipo_mantenimiento = :id_tipo_manto,
                        valor_servicio = :valor_servicio,
                        es_fuera_delegacion = :es_fuera,
                        dias_viaticos = :dias_viaticos,
                        valor_viaticos = :valor_viaticos,
                        hora_entrada = :entrada,
                        hora_salida = :salida,
                        tiempo_servicio = :tiempo,
                        actividades_realizadas = :actividades,
                        id_estado_maquina = :id_estado,
                        id_calificacion = :id_calif,
                        tiene_novedad = :tiene_novedad,
                        detalle_novedad = :detalle_novedad,
                        soporte_remoto = :soporte_remoto,
                        repuestos_tecnico = :repuestos_tecnico,
                        estado = 1
                    WHERE id_ordenes_servicio = :id_orden
                    AND id_tecnico = :id_tecnico""", p);
            return true;
        } catch (Exception e) {
            log.error("Error guardarReporteTecnico: {}", e.getMessage());
            return false;
        }
    }

    public boolean actualizarReporteTecnico(Map<String, Object> d) {
        Map<String, Object> p = new LinkedHashMap<>(calcularValores(d));
        for (String k : List.of("numero_remision", "hora_entrada", "hora_salida", "tiempo_servicio", "actividades_realizadas", "id_estado_maquina", "id_calificacion",
                "id_tipo_mantenimiento", "soporte_remoto", "tiene_novedad", "detalle_novedad", "repuestos_tecnico", "id_ordenes_servicio", "id_tecnico")) {
            p.put(k, d.get(k));
        }
        db.update("""
                UPDATE ordenes_servicio SET
                    id_modalidad = :id_modalidad,
                    valor_servicio = :valor_servicio,
                    es_fuera_delegacion = :es_fuera,
                    dias_viaticos = :dias_viaticos,
                    valor_viaticos = :valor_viaticos,
                    numero_remision = :numero_remision,
                    hora_entrada = :hora_entrada,
                    hora_salida = :hora_salida,
                    tiempo_servicio = :tiempo_servicio,
                    actividades_realizadas = :actividades_realizadas,
                    id_estado_maquina = :id_estado_maquina,
                    id_calificacion = :id_calificacion,
                    id_tipo_mantenimiento = :id_tipo_mantenimiento,
                    soporte_remoto = :soporte_remoto,
                    tiene_novedad = :tiene_novedad,
                    detalle_novedad = :detalle_novedad,
                    repuestos_tecnico = :repuestos_tecnico
                    WHERE id_ordenes_servicio = :id_ordenes_servicio AND id_tecnico = :id_tecnico""", p);
        return true;
    }

    // ================================================================ COMPLEMENTO

    private Boolean tieneColNovIni;

    private boolean columnaNovedadInicial() {
        if (tieneColNovIni == null) {
            try {
                Map<String, Object> r = db.one("SELECT COUNT(*) n FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c",
                        "t", "ordenes_servicio_complemento", "c", "novedad_estado_inicial");
                tieneColNovIni = r != null && Conv.toInt(r.get("n")) > 0;
            } catch (Exception e) {
                return true;
            }
        }
        return tieneColNovIni;
    }

    public boolean guardarDatosComplementarios(Map<String, Object> c) {
        boolean nueva = columnaNovedadInicial();
        String sql = "INSERT INTO ordenes_servicio_complemento ("
                + " id_orden_servicio, numero_maquina, serial_maquina, serial_router,"
                + " serial_ups, pendientes, administrador_punto, celular_encargado, id_estado_inicial," + (nueva ? " novedad_estado_inicial," : "")
                + " latitud_fin, longitud_fin, estado"
                + " ) VALUES ("
                + " :id_orden, :num_maq, :ser_maq, :ser_rout,"
                + " :ser_ups, :pendientes, :admin, :celular, :est_ini," + (nueva ? " :nov_ini," : "")
                + " :lat_fin, :lon_fin, 1"
                + " ) ON DUPLICATE KEY UPDATE"
                + " numero_maquina = VALUES(numero_maquina), serial_maquina = VALUES(serial_maquina), serial_router = VALUES(serial_router),"
                + " serial_ups = VALUES(serial_ups), pendientes = VALUES(pendientes), administrador_punto = VALUES(administrador_punto),"
                + " celular_encargado = VALUES(celular_encargado), id_estado_inicial = VALUES(id_estado_inicial),"
                + (nueva ? " novedad_estado_inicial = VALUES(novedad_estado_inicial)," : "")
                + " latitud_fin = VALUES(latitud_fin), longitud_fin = VALUES(longitud_fin)";
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("id_orden", c.get("id_orden_servicio"));
        p.put("num_maq", c.get("numero_maquina"));
        p.put("ser_maq", c.get("serial_maquina"));
        p.put("ser_rout", c.get("serial_router"));
        p.put("ser_ups", c.get("serial_ups"));
        p.put("pendientes", c.get("pendientes"));
        p.put("admin", c.get("administrador_punto"));
        p.put("celular", c.get("celular_encargado"));
        p.put("est_ini", c.get("id_estado_inicial"));
        if (nueva) p.put("nov_ini", c.get("novedad_estado_inicial"));
        p.put("lat_fin", c.get("latitud_fin"));
        p.put("lon_fin", c.get("longitud_fin"));
        try {
            db.update(sql, p);
            return true;
        } catch (Exception e) {
            log.error("Error guardarDatosComplementarios Excepcion: {}", e.getMessage());
            return false;
        }
    }

    public boolean actualizarDatosComplementarios(Map<String, Object> d) {
        boolean nueva = columnaNovedadInicial();
        Map<String, Object> p = new LinkedHashMap<>();
        for (String k : List.of("numero_maquina", "serial_maquina", "serial_router", "serial_ups", "pendientes", "administrador_punto", "celular_encargado",
                "id_estado_inicial", "id_orden_servicio")) {
            p.put(k, d.get(k));
        }
        if (nueva) p.put("novedad_estado_inicial", d.get("novedad_estado_inicial"));
        db.update("UPDATE ordenes_servicio_complemento SET"
                + " numero_maquina = :numero_maquina, serial_maquina = :serial_maquina, serial_router = :serial_router, serial_ups = :serial_ups,"
                + " pendientes = :pendientes, administrador_punto = :administrador_punto, celular_encargado = :celular_encargado,"
                + " id_estado_inicial = :id_estado_inicial" + (nueva ? ", novedad_estado_inicial = :novedad_estado_inicial" : "")
                + " WHERE id_orden_servicio = :id_orden_servicio", p);
        return true;
    }

    public Map<String, Object> obtenerReporteGuardado(int idOrden) {
        String extra = columnaNovedadInicial() ? ", c.novedad_estado_inicial" : "";
        return db.one("SELECT r.*, c.numero_maquina, c.serial_maquina, c.serial_router, c.serial_ups,"
                + " c.pendientes, c.administrador_punto, c.celular_encargado, c.id_estado_inicial" + extra
                + " FROM ordenes_servicio r LEFT JOIN ordenes_servicio_complemento c ON r.id_ordenes_servicio = c.id_orden_servicio"
                + " WHERE r.id_ordenes_servicio = :id", "id", idOrden);
    }

    public Map<String, Object> obtenerDetalleOrdenParaEdicion(int idOrden) {
        return db.one("""
                SELECT o.*, COALESCE(o.id_modalidad, p.id_modalidad, 1) AS id_modalidad, c.nombre_cliente, p.nombre_punto, p.direccion AS direccion_punto
                FROM ordenes_servicio o
                LEFT JOIN cliente c ON o.id_cliente = c.id_cliente
                LEFT JOIN punto p ON o.id_punto = p.id_punto
                WHERE o.id_ordenes_servicio = :id""", "id", idOrden);
    }

    // ================================================================ MODIFICACIONES (app/temp/modificaciones.json)

    private Path archivoModificaciones() throws Exception {
        Path p = archivos.resolver("app/temp/modificaciones.json");
        Files.createDirectories(p.getParent());
        return p;
    }

    public synchronized String obtenerUltimaModificacion(int idOrden) {
        try {
            Path f = archivoModificaciones();
            if (!Files.exists(f)) return null;
            Map<String, Object> m = Conv.map(Req.jsonDecode(Files.readString(f, StandardCharsets.UTF_8)));
            Object v = m.get(String.valueOf(idOrden));
            return v == null ? null : Conv.str(v);
        } catch (Exception e) {
            return null;
        }
    }

    public synchronized void actualizarFechaModificacion(int idOrden) {
        try {
            Path f = archivoModificaciones();
            Map<String, Object> m = new LinkedHashMap<>();
            if (Files.exists(f)) {
                Object o = Req.jsonDecode(Files.readString(f, StandardCharsets.UTF_8));
                if (o instanceof Map<?, ?>) m.putAll(Conv.map(o));
            }
            m.put(String.valueOf(idOrden), F.ahora());
            Files.writeString(f, Req.jsonEncode(m), StandardCharsets.UTF_8);
        } catch (Exception e) {
            log.warn("No se pudo actualizar modificaciones.json: {}", e.getMessage());
        }
    }

    /** strtotime(a) > strtotime(b) para 'Y-m-d H:i:s' */
    public static boolean posterior(String a, String b) {
        if (Conv.empty(a) || Conv.empty(b)) return false;
        return a.trim().compareTo(b.trim()) > 0;
    }

    // ================================================================ NOVEDADES

    public Map<String, Object> verificarNovedad(int idNov) {
        try {
            Map<String, Object> r = db.one("SELECT id_tipo_novedad, nombre_novedad, estado FROM tipo_novedad WHERE id_tipo_novedad = :id LIMIT 1", "id", idNov);
            if (r != null && Conv.toInt(r.get("estado")) == 1) return Map.of("valido", true, "nombre", Conv.str(r.get("nombre_novedad")));
        } catch (Exception ignored) {
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("valido", false);
        r.put("nombre", null);
        return r;
    }

    public List<Integer> filtrarNovedadesValidas(List<Integer> ids) {
        LinkedHashSet<Integer> v = new LinkedHashSet<>();
        for (Integer id : ids) {
            if (id == null || id <= 0) continue;
            if (Boolean.TRUE.equals(verificarNovedad(id).get("valido"))) v.add(id);
        }
        return new ArrayList<>(v);
    }

    public static List<Integer> normalizarIds(Object lista) {
        List<String> items = new ArrayList<>();
        if (lista instanceof List<?> || lista instanceof Map<?, ?>) Req.asList(lista).forEach(x -> items.add(Conv.str(x)));
        else for (String s : Conv.str(lista).split(",")) items.add(s);
        LinkedHashSet<Integer> r = new LinkedHashSet<>();
        for (String s : items) {
            String t = s.trim();
            if (t.isEmpty() || !Conv.esNumero(t)) continue;
            int id = (int) Conv.toDouble(t);
            if (id > 0) r.add(id);
        }
        return new ArrayList<>(r);
    }

    public boolean guardarNovedadesTecnico(int idOrden, List<Integer> novedades) {
        try {
            tx.executeWithoutResult(s -> {
                db.update("DELETE FROM orden_servicio_novedad WHERE id_orden_servicio = :id_ord", "id_ord", idOrden);
                List<Integer> validas = novedades == null ? List.of() : filtrarNovedadesValidas(novedades);
                for (Integer n : validas) {
                    db.update("INSERT INTO orden_servicio_novedad (id_orden_servicio, id_tipo_novedad) VALUES (:id_ord, :id_nov)", "id_ord", idOrden, "id_nov", n);
                }
                db.update("UPDATE ordenes_servicio SET tiene_novedad = :tiene_nov WHERE id_ordenes_servicio = :id_ord", "tiene_nov", validas.isEmpty() ? 0 : 1,
                        "id_ord", idOrden);
            });
            return true;
        } catch (Exception e) {
            log.error("Error guardarNovedadesTecnico: {}", e.getMessage());
            return false;
        }
    }

    public List<Integer> obtenerNovedadesOrden(int idOrden) {
        try {
            return db.list("SELECT id_tipo_novedad FROM orden_servicio_novedad WHERE id_orden_servicio = :id ORDER BY id_tipo_novedad ASC", "id", idOrden).stream()
                    .map(r -> Conv.toInt(r.get("id_tipo_novedad"))).toList();
        } catch (Exception e) {
            return List.of();
        }
    }

    public int resolverTieneNovedad(int idOrden, Object valorPost, boolean gestionada) {
        if (!obtenerNovedadesOrden(idOrden).isEmpty()) return 1;
        if (gestionada) return 0;
        try {
            Map<String, Object> r = db.one("SELECT tiene_novedad FROM ordenes_servicio WHERE id_ordenes_servicio = :id LIMIT 1", "id", idOrden);
            if (r != null && Conv.toInt(r.get("tiene_novedad")) == 1) return 1;
        } catch (Exception ignored) {
        }
        String v = Conv.str(valorPost);
        return !v.isEmpty() && !"0".equals(v) ? 1 : 0;
    }

    // ================================================================ PUNTO / REMISIÓN

    public void actualizarUltimaVisitaPunto(Object idPunto, Object fecha, Object idTipo) {
        try {
            db.update("UPDATE punto SET fecha_ultima_visita = :fecha, id_ultimo_tipo_mantenimiento = :tipo WHERE id_punto = :id_punto",
                    "fecha", fecha, "tipo", idTipo, "id_punto", idPunto);
        } catch (Exception e) {
            log.error("Error actualizarUltimaVisitaPunto: {}", e.getMessage());
        }
    }

    public void actualizarTipoMantenimientoPunto(Object idPunto, Object idTipo) {
        try {
            db.update("UPDATE punto SET id_ultimo_tipo_mantenimiento = :tipo WHERE id_punto = :id_punto", "tipo", idTipo, "id_punto", idPunto);
        } catch (Exception e) {
            log.error("Error actualizarTipoMantenimientoPunto: {}", e.getMessage());
        }
    }

    /** ordenCrearModelo::marcarRemisionComoUsada */
    public void marcarRemisionComoUsada(Object numero, int idOrden, int idTecnico) {
        db.update("""
                UPDATE control_remisiones
                SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1),
                    id_orden_servicio = :id_orden,
                    fecha_uso = NOW()
                WHERE numero_remision = :num AND id_tecnico = :tec""", "id_orden", idOrden, "num", numero, "tec", idTecnico);
    }
}
