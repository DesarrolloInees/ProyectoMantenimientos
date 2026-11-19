package co.inees.mantenimientos.modulos.orden;

import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.Duration;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/** Port de ordenCrearControlador.php + ordenCrearModelo.php */
@RestController
@RequestMapping("/api/ordenCrear")
public class OrdenCrearController {

    private static final Logger log = LoggerFactory.getLogger(OrdenCrearController.class);
    private final Db db;
    private final Archivos archivos;
    private final OrdenDetalleModelo detalle;

    public OrdenCrearController(Db db, Archivos archivos, OrdenDetalleModelo detalle) {
        this.db = db;
        this.archivos = archivos;
        this.detalle = detalle;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> cargarVista() {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Reporte de Servicios");
        r.put("clientes", db.list("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC"));
        r.put("tiposManto", db.list("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1"));
        r.put("tecnicos", db.list("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
        r.put("estados", db.list("SELECT id_estado, nombre_estado FROM estado_maquina ORDER BY id_estado ASC"));
        r.put("califs", db.list("SELECT id_calificacion, nombre_calificacion FROM calificacion_servicio ORDER BY id_calificacion ASC"));
        r.put("listaRepuestos", db.list("""
                SELECT id_repuesto,
                        CONCAT(
                            '[',
                            COALESCE(NULLIF(codigo_referencia, ''), 'SIN CÓDIGO'),
                            '] ',
                            nombre_repuesto
                        ) AS nombre_repuesto
                FROM repuesto
                WHERE estado = 1
                ORDER BY nombre_repuesto ASC"""));
        r.put("listaFestivos", detalle.obtenerFestivos());
        return r;
    }

    @PostMapping(params = "accion=ajaxPuntos")
    public Object ajaxPuntos(Req req) {
        if (Conv.empty(req.get("id_cliente"))) return Map.of("error", "ID Cliente no recibido");
        try {
            return db.list("""
                    SELECT p.id_punto, p.nombre_punto, p.codigo_1,
                            COALESCE(p.id_modalidad, 1) as id_modalidad,
                            mo.nombre_modalidad
                    FROM punto p
                    LEFT JOIN modalidad_operativa mo ON p.id_modalidad = mo.id_modalidad
                    WHERE p.id_cliente = :id_cliente AND p.estado = 1
                    ORDER BY p.nombre_punto ASC""", "id_cliente", req.intv("id_cliente"));
        } catch (Exception e) {
            return List.of();
        }
    }

    @PostMapping(params = "accion=ajaxMaquinas")
    public Object ajaxMaquinas(Req req) {
        if (Conv.empty(req.get("id_punto"))) return Map.of("error", "ID Punto no recibido");
        try {
            return db.list("""
                    SELECT m.id_maquina, m.device_id, m.id_tipo_maquina, tm.nombre_tipo_maquina
                    FROM maquina m
                    JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE m.id_punto = :id_punto AND m.estado = 1""", "id_punto", req.intv("id_punto"));
        } catch (Exception e) {
            return List.of();
        }
    }

    @PostMapping(params = "accion=ajaxCalcularPrecio")
    public Map<String, Object> ajaxCalcularPrecio(Req req) {
        if (!req.has("id_maquina_tipo") || !req.has("id_manto") || !req.has("id_modalidad")) {
            return Map.of("precio", 0, "error", "Faltan datos");
        }
        return Map.of("precio", consultarTarifa(req.intv("id_maquina_tipo"), req.intv("id_manto"), req.intv("id_modalidad"),
                req.str("fecha_visita", F.hoy())));
    }

    /** Devuelve -1 si no hay tarifa; si existe, el precio tal cual (DECIMAL como texto). */
    private Object consultarTarifa(int tipoMaq, int tipoManto, int modalidad, String fecha) {
        try {
            String anio = !Conv.empty(fecha) && fecha.length() >= 4 ? fecha.substring(0, 4) : F.hoy().substring(0, 4);
            Map<String, Object> r = db.one("""
                    SELECT precio FROM tarifa
                    WHERE id_tipo_maquina = :tipo_maq
                        AND id_tipo_mantenimiento = :tipo_manto
                        AND id_modalidad = :modalidad
                        AND año_vigencia = :anio
                    LIMIT 1""", "tipo_maq", tipoMaq, "tipo_manto", tipoManto, "modalidad", modalidad, "anio", Conv.toInt(anio));
            return r == null ? -1 : r.get("precio");
        } catch (Exception e) {
            return -1;
        }
    }

    @PostMapping(params = "accion=ajaxInventarioTecnico")
    public List<Map<String, Object>> ajaxInventarioTecnico(Req req) {
        if (!req.has("id_tecnico")) return List.of();
        try {
            return db.list("""
                    SELECT i.id_repuesto,
                            CASE
                                WHEN r.codigo_referencia IS NOT NULL AND r.codigo_referencia != ''
                                THEN CONCAT(r.codigo_referencia, ' - ', r.nombre_repuesto)
                                ELSE r.nombre_repuesto
                            END AS nombre_repuesto,
                            r.codigo_referencia,
                            i.cantidad_actual
                    FROM inventario_tecnico i
                    INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                    WHERE i.id_tecnico = :id_tec AND i.estado = 1 AND i.cantidad_actual > 0
                    ORDER BY r.nombre_repuesto ASC""", "id_tec", req.intv("id_tecnico"));
        } catch (Exception e) {
            return List.of();
        }
    }

    @PostMapping(params = "accion=ajaxValidarRemision")
    public Map<String, Object> ajaxValidarRemision(Req req) {
        if (!req.has("numero_remision") || !req.has("id_tecnico")) return Map.of("disponible", false, "mensaje", "Datos incompletos");
        try {
            Map<String, Object> r = db.one("""
                    SELECT e.nombre_estado, cr.id_orden_servicio
                    FROM control_remisiones cr
                    INNER JOIN estados_remision e ON cr.id_estado = e.id_estado
                    WHERE cr.numero_remision = :remision
                    AND cr.id_tecnico = :tecnico
                    LIMIT 1""", "remision", req.str("numero_remision"), "tecnico", req.intv("id_tecnico"));
            if (r == null) return Map.of("disponible", false, "mensaje", "Remisión no encontrada en el sistema");
            String estado = Conv.str(r.get("nombre_estado"));
            if ("USADA".equals(estado)) return Map.of("disponible", false, "mensaje", "Remisión ya fue usada en orden #" + Conv.str(r.get("id_orden_servicio")));
            if ("ANULADA".equals(estado) || "ELIMINADO".equals(estado)) return Map.of("disponible", false, "mensaje", "Esta remisión está " + estado);
            return Map.of("disponible", true, "mensaje", "Remisión disponible");
        } catch (Exception e) {
            return Map.of("disponible", false, "mensaje", "Error al verificar remisión");
        }
    }

    @PostMapping(params = "accion=ajaxProgramacion")
    public Map<String, Object> ajaxProgramacion(Req req) {
        if (!req.has("fecha")) return Map.of("status", false, "error", "Sin fecha");
        return Map.of("status", true, "data", List.of());
    }

    @PostMapping(params = "accion=ajaxRemisiones")
    public Object ajaxRemisiones(Req req) {
        if (Conv.empty(req.get("id_tecnico"))) return List.of();
        return db.list("""
                SELECT id_control, numero_remision
                FROM control_remisiones
                WHERE id_tecnico = :t
                AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)
                ORDER BY CAST(numero_remision AS UNSIGNED) ASC""", "t", req.intv("id_tecnico"));
    }

    // ================================================================ GUARDAR

    @PostMapping(params = "accion=ajaxGuardarJSON")
    public Map<String, Object> ajaxGuardarJSON(Req req) {
        try {
            String fechaReporte = req.str("fecha_reporte", F.hoy());
            List<Object> filas = Conv.list(Req.jsonDecode(req.str("json_data", "[]")));
            Object raw = Req.jsonDecode(req.str("json_data", "[]"));
            if (raw instanceof Map<?, ?> m) filas = new ArrayList<>(m.values());
            if (filas.isEmpty()) return Map.of("status", "error", "msg", "No hay datos para guardar.");
            int[] res = procesarFilas(filas, fechaReporte, false);
            int guardados = res[0], errores = res[1];
            String detalles = ultimoDetalle;
            if (guardados > 0 && errores == 0) return Map.of("status", "ok", "msg", "Se guardaron " + guardados + " servicios correctamente.");
            if (guardados > 0) return Map.of("status", "warning", "msg", "Se guardaron " + guardados + ", pero hubo " + errores + " errores. " + detalles);
            return Map.of("status", "error", "msg", "Ningún servicio procesado. " + detalles);
        } catch (Exception e) {
            return Map.of("status", "error", "msg", String.valueOf(e.getMessage()));
        }
    }

    /** guardar() - envío de formulario tradicional (filas[n][campo]) */
    @PostMapping(params = "accion=guardar")
    public Map<String, Object> guardar(Req req) {
        String fechaReporte = req.str("fecha_reporte");
        List<Object> filas = Req.asList(req.get("filas"));
        int[] res = procesarFilas(filas, fechaReporte, true);
        int guardados = res[0], errores = res[1];
        if (guardados > 0 && errores == 0) {
            return Map.of("status", "ok", "msg", "✅ ÉXITO TOTAL: Se guardaron " + guardados + " servicios correctamente.", "redirect", "ordenCrear");
        }
        return Map.of("status", "warning", "msg", "⚠️ ATENCIÓN: Se guardaron " + guardados + " servicios, pero hubo " + errores + " errores.\nDetalles: " + ultimoDetalle,
                "redirect", "ordenCrear");
    }

    private String ultimoDetalle = "";

    private synchronized int[] procesarFilas(List<Object> filas, String fechaReporte, boolean formulario) {
        int guardados = 0, errores = 0;
        StringBuilder det = new StringBuilder();
        for (int i = 0; i < filas.size(); i++) {
            Map<String, Object> f = Conv.map(filas.get(i));
            if (Conv.empty(f.get("id_maquina"))) continue;
            if (Conv.empty(f.get("id_cliente")) || Conv.empty(f.get("id_punto"))) {
                errores++;
                det.append("Fila #").append(i + 1).append(" rechazada: Falta Cliente o Punto. ");
                continue;
            }
            String valorLimpio = Conv.str(f.getOrDefault("valor", "0")).replace("$", "").replace(".", "").replace(" ", "");
            Object valorFinal = Conv.esNumero(valorLimpio) ? valorLimpio : 0;
            Map<String, Object> d = new LinkedHashMap<>();
            d.put("id_orden_previa", Conv.empty(f.get("id_orden_previa")) ? null : Conv.toInt(f.get("id_orden_previa")));
            d.put("remision", Conv.str(f.getOrDefault("remision", "")));
            d.put("id_cliente", f.get("id_cliente"));
            d.put("id_punto", f.get("id_punto"));
            d.put("id_modalidad", f.get("id_modalidad") != null ? f.get("id_modalidad") : 1);
            d.put("fecha", fechaReporte);
            d.put("id_maquina", f.get("id_maquina"));
            d.put("id_tecnico", f.get("id_tecnico"));
            d.put("tipo_servicio", f.get("tipo_servicio"));
            d.put("valor", valorFinal);
            d.put("hora_entrada", f.get("hora_in"));
            d.put("hora_salida", f.get("hora_out"));
            d.put("estado", Conv.nullIfEmpty(f.get("estado")));
            d.put("calif", Conv.nullIfEmpty(f.get("calif")));
            d.put("obs", Conv.str(f.getOrDefault("obs", "")));
            d.put("json_repuestos", Conv.str(f.getOrDefault("json_repuestos", "[]")));
            if (formulario && d.get("id_orden_previa") != null) actualizarFechaModificacion(d.get("id_orden_previa"));
            Object idOrden = guardarOrden(d);
            if (idOrden != null) guardados++;
            else {
                errores++;
                det.append("Fila #").append(i + 1).append(" falló al guardar. ");
            }
        }
        ultimoDetalle = det.toString();
        return new int[]{guardados, errores};
    }

    private void actualizarFechaModificacion(Object idOrden) {
        try {
            Path file = archivos.resolver("app/models/temp/modificaciones.json");
            Map<String, Object> data = new LinkedHashMap<>();
            if (Files.exists(file)) data.putAll(Conv.map(Req.jsonDecode(Files.readString(file, StandardCharsets.UTF_8))));
            data.put(String.valueOf(idOrden), F.ahora());
            Files.createDirectories(file.getParent());
            Files.writeString(file, Req.jsonEncode(data), StandardCharsets.UTF_8);
        } catch (Exception ignored) {
        }
    }

    private static String tiempo(Object entrada, Object salida) {
        if (Conv.empty(entrada) || Conv.empty(salida)) return "00:00";
        LocalTime a = F.hora(entrada);
        LocalTime b = F.hora(salida);
        if (a == null || b == null) return "00:00";
        long min = Duration.between(a, b).toMinutes();
        if (min < 0) min += 24 * 60;
        return String.format("%02d:%02d", min / 60, min % 60);
    }

    /** guardarOrden() del modelo: INSERT o UPDATE + remisión + repuestos + devoluciones. */
    private Object guardarOrden(Map<String, Object> d) {
        try {
            return db.tx(() -> {
                int esFuera = 0, dias = 0;
                double valViat = 0;
                int idDel = 0;
                Object v = db.scalar("SELECT id_delegacion FROM punto WHERE id_punto = :id LIMIT 1", "id", d.get("id_punto"));
                if (v != null) idDel = Conv.toInt(v);
                boolean principal = idDel >= 1 && idDel <= 5;
                if (idDel > 0 && !principal) {
                    esFuera = 1;
                    String sqlCheck = """
                            SELECT count(*) as total
                            FROM ordenes_servicio
                            WHERE id_tecnico = :id_tec
                                AND fecha_visita = :fecha
                                AND valor_viaticos > 0""";
                    if (d.get("id_orden_previa") != null) sqlCheck += " AND id_ordenes_servicio != " + Conv.toInt(d.get("id_orden_previa"));
                    long ya = db.scalarLong(sqlCheck, "id_tec", d.get("id_tecnico"), "fecha", d.get("fecha"));
                    if (ya == 0) {
                        dias = 1;
                        Object t = db.scalar("SELECT valor FROM parametros WHERE clave = :clave LIMIT 1", "clave", "Recargo_Servicios_Interurbanos");
                        valViat = dias * (t == null ? 0 : Conv.toDouble(t));
                    }
                }
                String tiempo = tiempo(d.get("hora_entrada"), d.get("hora_salida"));
                Object idOrden;
                Map<String, Object> p = new LinkedHashMap<>();
                p.put("id_modalidad", d.get("id_modalidad"));
                p.put("remision", d.get("remision"));
                p.put("id_maquina", d.get("id_maquina"));
                p.put("id_manto", d.get("tipo_servicio"));
                p.put("valor", d.get("valor"));
                p.put("es_fuera", esFuera);
                p.put("dias", dias);
                p.put("val_viaticos", valViat);
                p.put("entrada", d.get("hora_entrada"));
                p.put("salida", d.get("hora_salida"));
                p.put("tiempo", tiempo);
                p.put("id_estado", d.get("estado"));
                p.put("id_calif", d.get("calif"));
                p.put("actividades", d.get("obs"));
                if (d.get("id_orden_previa") != null) {
                    p.put("id_orden", d.get("id_orden_previa"));
                    db.update("""
                            UPDATE ordenes_servicio SET
                                id_modalidad = :id_modalidad,
                                numero_remision = :remision,
                                id_maquina = :id_maquina,
                                id_tipo_mantenimiento = :id_manto,
                                valor_servicio = :valor,
                                es_fuera_delegacion = :es_fuera,
                                dias_viaticos = :dias,
                                valor_viaticos = :val_viaticos,
                                hora_entrada = :entrada,
                                hora_salida = :salida,
                                tiempo_servicio = :tiempo,
                                id_estado_maquina = :id_estado,
                                id_calificacion = :id_calif,
                                actividades_realizadas = :actividades,
                                estado = 1
                            WHERE id_ordenes_servicio = :id_orden""", p);
                    idOrden = d.get("id_orden_previa");
                } else {
                    p.put("id_cliente", d.get("id_cliente"));
                    p.put("id_punto", d.get("id_punto"));
                    p.put("fecha", d.get("fecha"));
                    p.put("id_tecnico", d.get("id_tecnico"));
                    idOrden = db.insert("""
                            INSERT INTO ordenes_servicio
                            (id_cliente, id_punto, id_modalidad, numero_remision, fecha_visita, id_maquina, id_tecnico, id_tipo_mantenimiento,
                            valor_servicio, es_fuera_delegacion, dias_viaticos, valor_viaticos,
                            hora_entrada, hora_salida, tiempo_servicio, id_estado_maquina, id_calificacion, actividades_realizadas, estado, created_at)
                            VALUES
                            (:id_cliente, :id_punto, :id_modalidad, :remision, :fecha, :id_maquina, :id_tecnico, :id_manto,
                            :valor, :es_fuera, :dias, :val_viaticos,
                            :entrada, :salida, :tiempo, :id_estado, :id_calif, :actividades, 1, NOW())""", p);
                }
                if (!Conv.empty(d.get("remision"))) {
                    db.update("""
                            UPDATE control_remisiones
                            SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1),
                                id_orden_servicio = :o,
                                fecha_uso = NOW()
                            WHERE numero_remision = :n AND id_tecnico = :t""", "o", idOrden, "n", d.get("remision"), "t", d.get("id_tecnico"));
                }
                detalle.actualizarInfoMantenimientoPunto(d.get("id_punto"));
                List<Object> reps = Conv.list(Req.jsonDecode(Conv.str(d.get("json_repuestos"))));
                if (!reps.isEmpty()) {
                    if (d.get("id_orden_previa") != null) {
                        db.update("DELETE FROM orden_servicio_repuesto WHERE id_orden_servicio = :o", "o", idOrden);
                        db.update("DELETE FROM control_devolucion_repuestos WHERE id_orden_servicio = :o", "o", idOrden);
                    }
                    for (Object o : reps) {
                        Map<String, Object> rep = Conv.map(o);
                        if (rep.containsKey("id") && rep.get("id") != null && rep.containsKey("origen") && rep.get("origen") != null) {
                            int cant = Conv.toInt(rep.get("cantidad")) > 0 ? Conv.toInt(rep.get("cantidad")) : 1;
                            db.update("INSERT INTO orden_servicio_repuesto (id_orden_servicio, id_repuesto, origen, cantidad) VALUES (:o, :r, :or, :c)",
                                    "o", idOrden, "r", rep.get("id"), "or", rep.get("origen"), "c", cant);
                            if (d.get("id_orden_previa") == null && "INEES".equals(rep.get("origen"))) {
                                try {
                                    db.update("""
                                            UPDATE inventario_tecnico
                                            SET cantidad_actual = cantidad_actual - :cant
                                            WHERE id_tecnico = :id_tec AND id_repuesto = :id_rep""", "cant", cant, "id_tec", d.get("id_tecnico"), "id_rep", rep.get("id"));
                                } catch (Exception e) {
                                    log.error("Error descontando inventario: {}", e.getMessage());
                                }
                            }
                            Object req = db.scalar("SELECT requiere_devolucion FROM repuesto WHERE id_repuesto = :r", "r", rep.get("id"));
                            if (Conv.toInt(req) == 1) {
                                db.update("""
                                        INSERT INTO control_devolucion_repuestos (id_tecnico, id_orden_servicio, id_repuesto, cantidad, estado_devolucion)
                                        VALUES (:t, :o, :r, :c, 'Pendiente')""", "t", d.get("id_tecnico"), "o", idOrden, "r", rep.get("id"), "c", cant);
                            }
                        }
                    }
                }
                return idOrden;
            });
        } catch (Exception e) {
            log.error("ERROR SQL fila fallida. Datos: {} | Error real: {}", Req.jsonEncode(d), e.getMessage());
            return null;
        }
    }
}
