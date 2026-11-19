package co.inees.mantenimientos.modulos.cronometro;

import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.core.io.ClassPathResource;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Excel;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Pdf;
import co.inees.mantenimientos.core.Req;
import co.inees.mantenimientos.core.Telegram;
import tools.jackson.databind.json.JsonMapper;

/**
 * Port de app/controllers/cronometro/* (cronometroCrear, cronometroHistorial, cronometroAdmin,
 * cronometroReportes, cronometroPdf, cronometroExcel) y sus modelos.
 */
@RestController
public class CronometroController {

    private static final Logger log = LoggerFactory.getLogger(CronometroController.class);
    private static final JsonMapper JSON = JsonMapper.builder().build();
    private static final ZoneId BOGOTA = ZoneId.of("America/Bogota");
    /** Catálogo usado por la vista del técnico (cronometroCrearControlador::index) */
    static final Map<Integer, Integer> CATALOGO_VISTA = Map.of(1, 60, 2, 120, 3, 45, 4, 20);
    /** Catálogo usado al iniciar (cronometroCrearControlador::iniciar) */
    static final Map<Integer, Integer> CATALOGO_INICIO = Map.of(1, 60, 2, 120, 3, 45, 4, 90);

    private final Db db;
    private final Pdf pdf;
    private final Telegram telegram;

    public CronometroController(Db db, Pdf pdf, Telegram telegram) {
        this.db = db;
        this.pdf = pdf;
        this.telegram = telegram;
    }

    private Map<String, Object> tecnicoDeUsuario(int idUsuario) {
        try {
            return db.one("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE usuario_id = :id_usuario AND estado = 1 LIMIT 1", "id_usuario", idUsuario);
        } catch (Exception e) {
            log.error("Error obtenerDatosTecnico: {}", e.getMessage());
            return null;
        }
    }

    private List<Map<String, Object>> lista(String sql, Object... kv) {
        try {
            return db.list(sql, kv);
        } catch (Exception e) {
            log.error("Cronómetro: {}", e.getMessage());
            return List.of();
        }
    }

    // ================================================================ CREAR (técnico)

    @GetMapping(value = "/api/cronometroCrear", params = "accion=index")
    public Map<String, Object> crearIndex(Req req) {
        int idUsuario = req.sesion().usuarioId();
        if (idUsuario == 0) return Map.of("error", "Sesión expirada.");
        var tec = tecnicoDeUsuario(idUsuario);
        if (tec == null || Conv.empty(tec.get("id_tecnico"))) return Map.of("error", "Tu usuario no está vinculado a un perfil de técnico.");

        Map<String, Object> activo = null;
        try {
            activo = db.one("""
                    SELECT ml.*, c.nombre_cliente, p.nombre_punto, tm.nombre_completo as tipo_mantenimiento
                    FROM monitoreo_motorizados_live ml
                    INNER JOIN cliente c ON ml.id_cliente = c.id_cliente
                    INNER JOIN punto p ON ml.id_punto = p.id_punto
                    INNER JOIN tipo_mantenimiento tm ON ml.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE ml.id_tecnico = :id_tecnico AND ml.estado_actual = 'En Progreso'
                    ORDER BY ml.id_monitoreo DESC LIMIT 1""", "id_tecnico", tec.get("id_tecnico"));
        } catch (Exception e) {
            log.error("Error obtenerServicioEnProgreso: {}", e.getMessage());
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Cronómetro de Servicio");
        r.put("catalogoTiempos", CATALOGO_VISTA);
        if (activo != null) {
            activo = new LinkedHashMap<>(activo);
            activo.put("tiempo_estimado_minutos", CATALOGO_VISTA.getOrDefault(Conv.toInt(activo.get("id_tipo_mantenimiento")), 60));
        }
        r.put("servicioActivo", activo);
        r.put("tiposMantenimiento", lista("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1 ORDER BY nombre_completo ASC"));
        r.put("clientes", activo == null ? lista("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC") : List.of());
        r.put("puntos", activo == null ? lista("SELECT id_punto, nombre_punto, id_cliente FROM punto WHERE estado = 1 ORDER BY nombre_punto ASC") : List.of());
        return r;
    }

    @PostMapping(value = "/api/cronometroCrear", params = "accion=iniciar")
    public Map<String, Object> iniciar(Req req) {
        var tec = tecnicoDeUsuario(req.sesion().usuarioId());
        if (tec == null) return Map.of("status", "error", "message", "Tu usuario no está vinculado a un perfil de técnico.");
        int cli = req.intv("id_cliente", 0), pun = req.intv("id_punto", 0), tipo = req.intv("id_tipo_mantenimiento", 0);
        if (cli == 0 || pun == 0 || tipo == 0) return Map.of("status", "error", "message", "⚠️ Faltan datos.");
        LocalDateTime ahora = LocalDateTime.now(BOGOTA);
        try {
            db.update("""
                    INSERT INTO monitoreo_motorizados_live
                    (id_tecnico, id_cliente, id_punto, id_tipo_mantenimiento, fecha_servicio, hora_inicio)
                    VALUES (:id_tecnico, :id_cliente, :id_punto, :id_tipo_mantenimiento, :fecha, :hora_inicio)""",
                    "id_tecnico", tec.get("id_tecnico"), "id_cliente", cli, "id_punto", pun, "id_tipo_mantenimiento", tipo,
                    "fecha", ahora.toLocalDate().toString(), "hora_inicio", ahora.format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")));
            return Map.of("status", "success");
        } catch (Exception e) {
            log.error("Error iniciarCronometro: {}", e.getMessage());
            return Map.of("status", "error", "message", "❌ Error al iniciar.");
        }
    }

    @PostMapping(value = "/api/cronometroCrear", params = "accion=finalizar")
    public Map<String, Object> finalizar(Req req) {
        int idMon = req.intv("id_monitoreo", 0);
        String horaInicio = req.str("hora_inicio_real", "");
        int tipoFinal = req.intv("id_tipo_mantenimiento_final", 0);
        int tipoInicial = req.intv("id_tipo_mantenimiento_inicial", 0);
        String just = req.str("justificacion_retraso", "").trim();
        if (idMon == 0 || horaInicio.isEmpty() || tipoFinal == 0) return Map.of("status", "error", "message", "⚠️ Faltan datos para finalizar.");
        LocalDateTime fin = LocalDateTime.now(BOGOTA);
        LocalDateTime ini;
        try {
            ini = LocalDateTime.parse(horaInicio.replace(' ', 'T').substring(0, 19));
        } catch (Exception e) {
            return Map.of("status", "error", "message", "⚠️ Faltan datos para finalizar.");
        }
        long minutos = Math.abs(Duration.between(ini, fin).toMinutes());
        int modificado = tipoFinal != tipoInicial ? 1 : 0;
        try {
            db.update("""
                    UPDATE monitoreo_motorizados_live
                    SET estado_actual = 'Finalizado',
                        hora_fin = :hora_fin,
                        duracion_real_minutos = :duracion,
                        id_tipo_mantenimiento = :id_tipo,
                        servicio_modificado = :modificado,
                        justificacion_retraso = :justificacion
                    WHERE id_monitoreo = :id_monitoreo""",
                    "hora_fin", fin.format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")), "duracion", minutos, "id_tipo", tipoFinal,
                    "modificado", modificado, "justificacion", just.isEmpty() ? null : just, "id_monitoreo", idMon);
            return Map.of("status", "success", "message", "✅ Servicio finalizado con éxito. Tiempo: " + minutos + " min.");
        } catch (Exception e) {
            log.error("Error finalizarCronometro: {}", e.getMessage());
            return Map.of("status", "error", "message", "❌ Error al finalizar.");
        }
    }

    @PostMapping(value = "/api/cronometroCrear", params = "accion=actualizarTipoVivo")
    public Map<String, Object> actualizarTipoVivo(Req req) {
        int idMon = req.intv("id_monitoreo", 0), tipo = req.intv("id_tipo_mantenimiento", 0);
        if (idMon > 0 && tipo > 0) {
            int n;
            try {
                n = db.update("""
                        UPDATE monitoreo_motorizados_live
                        SET id_tipo_mantenimiento = :id_tipo,
                            servicio_modificado = 1
                        WHERE id_monitoreo = :id_monitoreo AND servicio_modificado = 0""", "id_tipo", tipo, "id_monitoreo", idMon);
            } catch (Exception e) {
                log.error("Error actualizarTipoMantenimientoLive: {}", e.getMessage());
                n = 0;
            }
            if (n > 0) return Map.of("status", "success", "message", "Tipo de servicio modificado correctamente (1/1).");
            return Map.of("status", "limit_reached", "message", "Ya has alcanzado el límite máximo de 1 modificación para este servicio.");
        }
        return Map.of("status", "error", "message", "No se pudo procesar la solicitud.");
    }

    // ================================================================ HISTORIAL (técnico)

    @GetMapping(value = "/api/cronometroHistorial", params = "accion=index")
    public Map<String, Object> historial(Req req) {
        int idUsuario = req.sesion().usuarioId();
        if (idUsuario == 0) return Map.of("error", "Sesión expirada.");
        var tec = tecnicoDeUsuario(idUsuario);
        if (tec == null) return Map.of("error", "Tu usuario no está vinculado a un perfil de técnico.");
        var hist = lista("""
                SELECT
                    ml.id_monitoreo,
                    ml.fecha_servicio,
                    ml.hora_inicio,
                    ml.hora_fin,
                    ml.duracion_real_minutos,
                    ml.estado_actual,
                    c.nombre_cliente,
                    p.nombre_punto,
                    tm.nombre_completo AS tipo_mantenimiento,
                    CASE
                        WHEN ml.id_tipo_mantenimiento = 1 THEN 60
                        WHEN ml.id_tipo_mantenimiento = 2 THEN 120
                        WHEN ml.id_tipo_mantenimiento = 3 THEN 45
                        WHEN ml.id_tipo_mantenimiento = 4 THEN 90
                        ELSE 60
                    END AS tiempo_estimado_minutos
                FROM monitoreo_motorizados_live ml
                INNER JOIN cliente c ON ml.id_cliente = c.id_cliente
                INNER JOIN punto p ON ml.id_punto = p.id_punto
                INNER JOIN tipo_mantenimiento tm ON ml.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE ml.id_tecnico = :id_tecnico
                ORDER BY ml.fecha_servicio DESC, ml.hora_inicio DESC""", "id_tecnico", tec.get("id_tecnico"));
        return Map.of("titulo", "Historial de Tiempos", "historialServicios", hist);
    }

    // ================================================================ ADMIN (supervisor)

    @GetMapping(value = "/api/cronometroAdmin", params = "accion=index")
    public Map<String, Object> admin(Req req) {
        if (req.sesion().usuarioId() == 0) return Map.of("error", "Sesión expirada.");
        String fi = req.str("fecha_inicio", F.hoy()), ff = req.str("fecha_fin", F.hoy());
        String idTec = req.str("id_tecnico", ""), estado = req.str("estado_actual", "");

        Map<Integer, Integer> catalogo = new LinkedHashMap<>();
        try {
            Object v = db.scalar("SELECT valor FROM parametros WHERE clave = 'tiempos_mantenimiento_minutos' LIMIT 1");
            if (!Conv.empty(v)) {
                Map<?, ?> m = JSON.readValue(Conv.str(v), Map.class);
                m.forEach((k, x) -> catalogo.put(Conv.toInt(k), Conv.toInt(x)));
            }
        } catch (Exception e) {
            log.debug("Sin catálogo de tiempos: {}", e.getMessage());
        }
        if (catalogo.isEmpty()) catalogo.putAll(Map.of(1, 60, 2, 120, 3, 45, 4, 20));

        StringBuilder sql = new StringBuilder("""
                SELECT
                    ml.*,
                    t.nombre_tecnico,
                    c.nombre_cliente,
                    p.nombre_punto,
                    tm.nombre_completo AS tipo_mantenimiento
                FROM monitoreo_motorizados_live ml
                INNER JOIN tecnico t ON ml.id_tecnico = t.id_tecnico
                INNER JOIN cliente c ON ml.id_cliente = c.id_cliente
                INNER JOIN punto p ON ml.id_punto = p.id_punto
                INNER JOIN tipo_mantenimiento tm ON ml.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE 1=1""");
        Map<String, Object> p = new LinkedHashMap<>();
        if (!fi.isEmpty()) { sql.append(" AND ml.fecha_servicio >= :fecha_inicio"); p.put("fecha_inicio", fi); }
        if (!ff.isEmpty()) { sql.append(" AND ml.fecha_servicio <= :fecha_fin"); p.put("fecha_fin", ff); }
        if (!idTec.isEmpty()) { sql.append(" AND ml.id_tecnico = :id_tecnico"); p.put("id_tecnico", idTec); }
        if (!estado.isEmpty()) { sql.append(" AND ml.estado_actual = :estado_actual"); p.put("estado_actual", estado); }
        sql.append(" ORDER BY CASE WHEN ml.estado_actual = 'En Progreso' THEN 0 ELSE 1 END, ml.fecha_servicio DESC, ml.hora_inicio DESC");
        List<Map<String, Object>> servicios = new ArrayList<>();
        try {
            for (var s : db.list(sql.toString(), p)) servicios.add(new LinkedHashMap<>(s));
        } catch (Exception e) {
            log.error("Error obteniendo monitoreo admin: {}", e.getMessage());
        }

        var ses = req.sesion();
        int activos = 0, finalizados = 0, retrasados = 0, modificados = 0;
        for (var s : servicios) {
            int meta = catalogo.getOrDefault(Conv.toInt(s.get("id_tipo_mantenimiento")), 60);
            s.put("tiempo_estimado_minutos", meta);
            if ("En Progreso".equals(s.get("estado_actual"))) {
                activos++;
                try {
                    LocalDateTime ini = LocalDateTime.parse(Conv.str(s.get("hora_inicio")).replace(' ', 'T').substring(0, 19));
                    long trans = Math.floorDiv(Duration.between(ini.atZone(BOGOTA), java.time.ZonedDateTime.now(BOGOTA)).getSeconds(), 60);
                    String key = "alerta_retraso_" + s.get("id_monitoreo");
                    if (trans > meta && ses.get(key) == null) {
                        String msg = "⚠️ <b>ALERTA DE RETRASO EN VIVO</b>\n\n"
                                + "<b>Técnico:</b> " + Conv.h(s.get("nombre_tecnico")) + "\n"
                                + "<b>Servicio:</b> " + Conv.h(s.get("tipo_mantenimiento")) + "\n"
                                + "<b>Punto:</b> " + Conv.h(s.get("nombre_punto")) + "\n"
                                + "<b>Transcurrido:</b> " + trans + " min (Meta: " + meta + " min)\n";
                        telegram.enviar(msg);
                        ses.set(key, true);
                    }
                } catch (Exception ignored) {
                }
            } else {
                finalizados++;
                if (Conv.toInt(s.get("duracion_real_minutos")) > meta) retrasados++;
            }
            if (Conv.toInt(s.get("servicio_modificado")) == 1) modificados++;
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Monitoreo de Servicios (Supervisor)");
        r.put("filtros", Map.of("fecha_inicio", fi, "fecha_fin", ff, "id_tecnico", idTec, "estado_actual", estado));
        r.put("tecnicos", lista("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
        r.put("servicios", servicios);
        r.put("totalActivos", activos);
        r.put("totalFinalizados", finalizados);
        r.put("totalRetrasados", retrasados);
        r.put("totalModificados", modificados);
        return r;
    }

    // ================================================================ REPORTES (rendimiento)

    private List<Map<String, Object>> tiposMant() {
        return lista("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1 ORDER BY id_tipo_mantenimiento");
    }

    private List<Map<String, Object>> rendimiento(String fi, String ff, List<Map<String, Object>> tipos) {
        StringBuilder sel = new StringBuilder();
        Map<String, Object> p = new LinkedHashMap<>(Map.of("fecha_inicio", fi, "fecha_fin", ff));
        for (var t : tipos) {
            int id = Conv.toInt(t.get("id_tipo_mantenimiento"));
            sel.append(", SUM(CASE WHEN ml.id_tipo_mantenimiento = :id_").append(id)
                    .append(" AND ml.estado_actual = 'Finalizado' THEN 1 ELSE 0 END) AS tipo_").append(id);
            p.put("id_" + id, id);
        }
        try {
            return db.list("""
                    SELECT
                        t.id_tecnico,
                        t.nombre_tecnico,
                        COUNT(ml.id_monitoreo) AS total_registrados,
                        SUM(CASE WHEN ml.estado_actual = 'Finalizado' THEN 1 ELSE 0 END) AS total_finalizados,
                        SUM(CASE WHEN ml.estado_actual = 'En Progreso' THEN 1 ELSE 0 END) AS total_en_progreso
                    """ + sel + """

                    FROM tecnico t
                    LEFT JOIN monitoreo_motorizados_live ml
                        ON t.id_tecnico = ml.id_tecnico
                        AND ml.fecha_servicio BETWEEN :fecha_inicio AND :fecha_fin
                    WHERE t.estado = 1
                    GROUP BY t.id_tecnico, t.nombre_tecnico
                    ORDER BY total_finalizados DESC, t.nombre_tecnico ASC""", p);
        } catch (Exception e) {
            log.error("Error obtenerRendimientoTecnicos: {}", e.getMessage());
            return List.of();
        }
    }

    static int calcularMetaRango(String fi, String ff, int metaLV, int metaSab) {
        int total = 0;
        try {
            for (LocalDate d = LocalDate.parse(fi); !d.isAfter(LocalDate.parse(ff)); d = d.plusDays(1)) {
                int dow = d.getDayOfWeek().getValue();
                if (dow <= 5) total += metaLV;
                else if (dow == 6) total += metaSab;
            }
        } catch (Exception ignored) {
        }
        return total;
    }

    private int meta(Req req, String fi, String ff) {
        String m = req.str("meta_total", "");
        if (Conv.esNumero(m) && (int) Conv.toDouble(m) > 0) return (int) Conv.toDouble(m);
        return calcularMetaRango(fi, ff, 6, 3);
    }

    static double pct(int fin, int meta) {
        return meta > 0 ? Math.round((double) fin / meta * 1000) / 10.0 : 0;
    }

    @GetMapping(value = "/api/cronometroReportes", params = "accion=index")
    public Map<String, Object> reportes(Req req) {
        if (req.sesion().usuarioId() == 0) return Map.of("error", "Sesión expirada.");
        String fi = req.str("fecha_inicio", F.hoy().substring(0, 8) + "01"), ff = req.str("fecha_fin", F.hoy());
        int meta = meta(req, fi, ff);
        var tipos = tiposMant();
        var rend = rendimiento(fi, ff, tipos);
        List<Object> nombres = new ArrayList<>(), realizados = new ArrayList<>();
        List<String> colores = new ArrayList<>();
        int total = 0, cumplieron = 0;
        for (var r : rend) {
            int f = Conv.toInt(r.get("total_finalizados"));
            total += f;
            double p = pct(f, meta);
            nombres.add(r.get("nombre_tecnico"));
            realizados.add(f);
            if (p >= 100) { colores.add("#10b981"); cumplieron++; }
            else if (p >= 70) colores.add("#f59e0b");
            else colores.add("#ef4444");
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Reporte de Rendimiento de Flota");
        r.put("fechaInicio", fi);
        r.put("fechaFin", ff);
        r.put("metaTotalServicios", meta);
        r.put("tipos", tipos);
        r.put("rendimientoRaw", rend);
        r.put("tecnicosNombres", nombres);
        r.put("serviciosRealizados", realizados);
        r.put("coloresBarras", colores);
        r.put("totalGeneralServicios", total);
        r.put("tecnicosCumplieron", cumplieron);
        return r;
    }

    // ================================================================ PDF

    @GetMapping(value = "/api/cronometroPdf", params = "accion=generar")
    public ResponseEntity<?> generarPdf(Req req) {
        String fi = req.str("fecha_inicio", F.hoy().substring(0, 8) + "01"), ff = req.str("fecha_fin", F.hoy());
        var tipos = tiposMant();
        int meta = meta(req, fi, ff);
        var rend = rendimiento(fi, ff, tipos);
        List<String> nombres = new ArrayList<>(), colores = new ArrayList<>();
        List<Integer> datos = new ArrayList<>();
        for (var r : rend) {
            int f = Conv.toInt(r.get("total_finalizados"));
            double p = pct(f, meta);
            nombres.add(Conv.str(r.get("nombre_tecnico")));
            datos.add(f);
            colores.add(p >= 100 ? "#10b981" : p >= 70 ? "#f59e0b" : "#ef4444");
        }
        String svg = graficoSvg(nombres, datos, colores, meta);

        String css;
        try (var in = new ClassPathResource("plantillas/cronometro-pdf.css").getInputStream()) {
            css = new String(in.readAllBytes(), StandardCharsets.UTF_8);
        } catch (Exception e) {
            css = "";
        }
        int totalTec = rend.size(), totalFin = 0, totalProg = 0, cumplida = 0;
        for (var r : rend) {
            totalFin += Conv.toInt(r.get("total_finalizados"));
            totalProg += Conv.toInt(r.get("total_en_progreso"));
            if (pct(Conv.toInt(r.get("total_finalizados")), meta) >= 100) cumplida++;
        }
        StringBuilder h = new StringBuilder();
        h.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><title>Reporte de Rendimiento - Flota Motorizada</title>")
                .append("<link href=\"https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&family=JetBrains+Mono:wght@500;700&display=swap\" rel=\"stylesheet\">")
                .append("<style>").append(css).append("</style></head><body><div class=\"page\">")
                .append("<div class=\"header\"><div><img src=\"").append(pdf.dataUri("app/logos/logoInees.jpg")).append("\" alt=\"Logo INEES\"></div>")
                .append("<div class=\"header-text\"><h1>Rendimiento de Flota</h1><div class=\"sub\">Periodo: <strong>").append(F.dmy(fi))
                .append("</strong> al <strong>").append(F.dmy(ff)).append("</strong></div><span class=\"meta-badge\">🎯 Meta: ").append(meta)
                .append(" servicios / técnico</span></div></div>")
                .append("<div class=\"summary-grid\">")
                .append(card("👷", "primary", totalTec, "Técnicos Activos")).append(card("✅", "green", totalFin, "Servicios Finalizados"))
                .append(card("⏳", "amber", totalProg, "En Progreso")).append(card("🏆", "rose", cumplida, "Meta Cumplida"))
                .append("</div><div class=\"chart-container\">").append(svg).append("</div>")
                .append("<div class=\"table-wrapper\"><table><thead><tr><th class=\"text-left\">Técnico</th>");
        for (var t : tipos) h.append("<th>").append(Conv.h(t.get("nombre_completo"))).append("</th>");
        h.append("<th>✅ Finalizados</th><th>⏳ En Progreso</th><th>🎯 Meta</th><th>📊 Cumplimiento</th><th>Estado</th></tr></thead><tbody>");
        for (var r : rend) {
            int f = Conv.toInt(r.get("total_finalizados"));
            double p = pct(f, meta);
            String color, badge, txt;
            if (p >= 100) { color = "#22c55e"; badge = "badge-green"; txt = "🏆 Meta"; }
            else if (p >= 70) { color = "#eab308"; badge = "badge-amber"; txt = "⚡ Aceptable"; }
            else { color = "#ef4444"; badge = "badge-red"; txt = "⚠️ Crítico"; }
            h.append("<tr><td class=\"text-left\">").append(Conv.h(r.get("nombre_tecnico"))).append("</td>");
            for (var t : tipos) h.append("<td>").append(Conv.toInt(r.get("tipo_" + Conv.toInt(t.get("id_tipo_mantenimiento"))))).append("</td>");
            h.append("<td><strong>").append(f).append("</strong></td><td>").append(Conv.toInt(r.get("total_en_progreso"))).append("</td><td>").append(meta)
                    .append("</td><td><div class=\"progress-wrap\"><div class=\"progress-bar-bg\"><div class=\"progress-bar-fill\" style=\"width: ")
                    .append(num(Math.min(p, 100))).append("%; background: ").append(color).append(";\"></div></div><span class=\"progress-label\" style=\"color: ")
                    .append(color).append(";\">").append(num(p)).append("%</span></div></td><td><span class=\"badge ").append(badge).append("\">").append(txt)
                    .append("</span></td></tr>");
        }
        h.append("</tbody></table></div><div class=\"footer-stats\"><span>📋 <strong>").append(totalTec).append("</strong> técnicos evaluados · <strong>")
                .append(totalFin + totalProg).append("</strong> servicios totales</span><span>🎯 <span class=\"highlight\">").append(cumplida)
                .append("</span> de ").append(totalTec).append(" técnicos cumplieron la meta</span></div></div></body></html>");
        try {
            byte[] b = pdf.generar(h.toString(), "size: A4 landscape; margin: 10mm");
            return Descarga.pdf(b, "Rendimiento_Cronometro_" + fi + "_al_" + ff + ".pdf", true);
        } catch (Exception e) {
            return Descarga.html("<h1>Error generando PDF de Rendimiento</h1><p>" + Conv.h(e.getMessage()) + "</p>");
        }
    }

    /**
     * En PHP cronometroPdf/cronometroExcel no existen en la tabla `rutas` (index.php responde 404),
     * así que los botones nunca funcionaron. Se exponen también bajo el permiso de cronometroReportes.
     */
    @GetMapping(value = "/api/cronometroReportes", params = "accion=pdf")
    public ResponseEntity<?> reportesPdf(Req req) {
        return generarPdf(req);
    }

    @GetMapping(value = "/api/cronometroReportes", params = "accion=excel")
    public ResponseEntity<byte[]> reportesExcel(Req req) {
        return generarExcel(req);
    }

    private static String card(String icono, String clase, int n, String label) {
        return "<div class=\"summary-card\"><span class=\"icon\">" + icono + "</span><div class=\"number " + clase + "\">" + n + "</div><div class=\"label\">" + label + "</div></div>";
    }

    /** Imprime números como PHP (sin ".0" en enteros). */
    static String num(double v) {
        return v == Math.rint(v) ? String.valueOf((long) v) : String.valueOf(v);
    }

    static String graficoSvg(List<String> nombres, List<Integer> datos, List<String> colores, int meta) {
        int cantidad = nombres.size();
        double ancho = 950, alto = 350, pt = 30, pb = 90, pl = 50, pr = 30;
        double barW = 35, gap = 15;
        double totalAncho = ancho - pl - pr;
        double espacio = cantidad * (barW + gap);
        if (espacio > totalAncho && cantidad > 1) {
            gap = Math.max(4, (totalAncho - cantidad * barW) / (cantidad - 1));
            if (gap < 4) {
                barW = Math.max(12, (totalAncho - (cantidad - 1) * 4) / cantidad);
                gap = 4;
            }
        }
        double max = datos.isEmpty() ? 0 : datos.stream().mapToInt(Integer::intValue).max().getAsInt() * 1.2;
        if (max == 0) max = 1;
        if (max < 5) max = 5;
        double altoUtil = alto - pt - pb;
        StringBuilder s = new StringBuilder();
        s.append("<svg width=\"").append(num(ancho)).append("\" height=\"").append(num(alto))
                .append("\" xmlns=\"http://www.w3.org/2000/svg\" style=\"background:#fafcff; border-radius:12px; font-family: Inter, sans-serif; border: 1px solid #e9edf4;\">");
        s.append(linea(pl, pt, pl, alto - pb, "#cbd5e1", "1.5", null));
        s.append(linea(pl, alto - pb, ancho - pr, alto - pb, "#cbd5e1", "1.5", null));
        for (int i = 0; i <= 5; i++) {
            double value = Math.round(max / 5 * i);
            double y = (alto - pb) - (value / max) * altoUtil;
            s.append("<text x=\"").append(num(pl - 10)).append("\" y=\"").append(num(y + 4))
                    .append("\" text-anchor=\"end\" font-size=\"10\" fill=\"#64748b\" font-weight=\"600\">").append(num(value)).append("</text>");
            if (i > 0) s.append(linea(pl, y, ancho - pr, y, "#e2e8f0", "1", "4,4"));
        }
        if (meta > 0) {
            double metaY = (alto - pb) - (meta / max) * altoUtil;
            s.append(linea(pl, metaY, ancho - pr, metaY, "#ef4444", "2", "8,6"));
            s.append("<text x=\"").append(num(ancho - pr - 5)).append("\" y=\"").append(num(metaY - 6))
                    .append("\" text-anchor=\"end\" font-size=\"10\" fill=\"#ef4444\" font-weight=\"700\">Meta (").append(meta).append(")</text>");
        }
        double x = pl + gap * 1.5, baseY = alto - pb;
        for (int i = 0; i < cantidad; i++) {
            double bh = (datos.get(i) / max) * altoUtil;
            double y = baseY - bh;
            String color = i < colores.size() ? colores.get(i) : "#4f46e5";
            if (bh > 0) s.append("<rect x=\"").append(num(x)).append("\" y=\"").append(num(y)).append("\" width=\"").append(num(barW))
                    .append("\" height=\"").append(num(bh)).append("\" fill=\"").append(color).append("\" rx=\"2\"/>");
            s.append("<text x=\"").append(num(x + barW / 2)).append("\" y=\"").append(num(y - 6))
                    .append("\" text-anchor=\"middle\" font-size=\"11\" font-weight=\"800\" fill=\"#0f172a\">").append(datos.get(i)).append("</text>");
            String[] partes = nombres.get(i).trim().split(" ");
            String corto = partes[0];
            if (partes.length > 1 && partes[1].getBytes(StandardCharsets.UTF_8).length > 2) corto += " " + partes[1].substring(0, 1) + ".";
            double cx = x + barW / 2, cy = baseY + 12;
            s.append("<text x=\"").append(num(cx)).append("\" y=\"").append(num(cy)).append("\" transform=\"rotate(-45 ").append(num(cx)).append(" ").append(num(cy))
                    .append(")\" text-anchor=\"end\" font-size=\"9\" fill=\"#475569\" font-weight=\"700\">").append(Conv.h(corto)).append("</text>");
            x += barW + gap;
        }
        return s.append("</svg>").toString();
    }

    private static String linea(double x1, double y1, double x2, double y2, String color, String w, String dash) {
        return "<line x1=\"" + num(x1) + "\" y1=\"" + num(y1) + "\" x2=\"" + num(x2) + "\" y2=\"" + num(y2) + "\" stroke=\"" + color + "\""
                + (dash != null ? " stroke-dasharray=\"" + dash + "\"" : "") + " stroke-width=\"" + w + "\"/>";
    }

    // ================================================================ EXCEL

    @GetMapping(value = "/api/cronometroExcel", params = "accion=generar")
    public ResponseEntity<byte[]> generarExcel(Req req) {
        String fi = req.str("fecha_inicio", F.hoy().substring(0, 8) + "01"), ff = req.str("fecha_fin", F.hoy());
        var tipos = tiposMant();
        int meta = meta(req, fi, ff);
        var rend = rendimiento(fi, ff, tipos);
        int nt = tipos.size();
        String ultimaTotal = Excel.col(1 + nt + 5);
        Excel x = new Excel("Worksheet");
        x.set("A1", "Reporte de Rendimiento - Flota Motorizada").merge("A1:" + ultimaTotal + "1");
        x.estilo("A1", Excel.E.of().bold().size(14).h("center"));
        x.set("A2", "Periodo: " + fi + " al " + ff + " | Meta del Periodo: " + meta + " servicios").merge("A2:" + ultimaTotal + "2");
        x.estilo("A2", Excel.E.of().size(11).h("center"));
        int c = 1;
        x.set(c++, 3, "Técnico");
        for (var t : tipos) x.set(c++, 3, Conv.str(t.get("nombre_completo")));
        for (String hd : new String[] { "Finalizados", "En Progreso", "Meta Esperada", "% Cumplimiento" }) x.set(c++, 3, hd);
        x.set(c, 3, "Estado");
        String ult = Excel.col(c);
        x.estilo("A3:" + ult + "3", Excel.E.of().bold().size(10).color("FFFFFF").fill("4F46E5").center().borde().bordeColor("CBD5E1"));

        int fila = 4, totalTec = 0, totalFin = 0, cumplida = 0;
        for (var r : rend) {
            int f = Conv.toInt(r.get("total_finalizados"));
            totalFin += f;
            totalTec++;
            double p = pct(f, meta);
            String estado, fondo;
            if (p >= 100) { estado = "Meta Cumplida"; fondo = "DCFCE7"; cumplida++; }
            else if (p >= 70) { estado = "Aceptable"; fondo = "FEF3C7"; }
            else { estado = "Crítico"; fondo = "FEE2E2"; }
            c = 1;
            x.set(c++, fila, Conv.str(r.get("nombre_tecnico")));
            for (var t : tipos) x.set(c++, fila, Conv.toInt(r.get("tipo_" + Conv.toInt(t.get("id_tipo_mantenimiento")))));
            x.set(c++, fila, f);
            x.set(c++, fila, Conv.toInt(r.get("total_en_progreso")));
            x.set(c++, fila, meta);
            x.set(c++, fila, num(p) + "%");
            x.set(c, fila, estado);
            x.estilo("A" + fila + ":" + Excel.col(c) + fila, Excel.E.of().borde().bordeColor("CBD5E1").center());
            x.estilo(Excel.col(c) + fila, Excel.E.of().fill(fondo));
            x.estilo("A" + fila, Excel.E.of().h("left"));
            fila++;
        }
        x.set("A" + fila, "RESUMEN").merge("A" + fila + ":" + Excel.col(1 + nt) + fila);
        x.estilo("A" + fila, Excel.E.of().bold().h("right"));
        c = 2 + nt;
        x.set(c++, fila, totalFin);
        x.set(c++, fila, "");
        x.set(c++, fila, "");
        double pg = totalTec > 0 ? Math.round((double) cumplida / totalTec * 1000) / 10.0 : 0;
        x.set(c++, fila, num(pg) + "%");
        x.set(c, fila, cumplida + " de " + totalTec + " técnicos cumplieron la meta");
        x.estilo("A" + fila + ":" + Excel.col(c) + fila, Excel.E.of().bold().fill("F1F5F9").borde().bordeColor("CBD5E1"));
        x.autoAncho("A", Excel.col(c));
        return Descarga.excel(x.bytes(), "Reporte_Rendimiento_" + fi + "_al_" + ff + ".xlsx");
    }
}
