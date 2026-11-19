package co.inees.mantenimientos.modulos.horaextra;

import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.text.Normalizer;
import java.time.Duration;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Base64;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.core.io.ClassPathResource;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Excel;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Pdf;
import co.inees.mantenimientos.core.Req;

/**
 * Port de app/controllers/horaExtra/* (horaExtraCrear, horaExtraHistorial, horaExtraAdmin,
 * horaExtraGenerar = PDF, horaExtraExcel) y sus modelos.
 */
@RestController
public class HoraExtraController {

    private static final Logger log = LoggerFactory.getLogger(HoraExtraController.class);
    private final Db db;
    private final Archivos archivos;
    private final Pdf pdf;

    public HoraExtraController(Db db, Archivos archivos, Pdf pdf) {
        this.db = db;
        this.archivos = archivos;
        this.pdf = pdf;
    }

    private static String primeroMes() {
        return F.hoy().substring(0, 8) + "01";
    }

    private static String ultimoMes() {
        LocalDate h = LocalDate.now();
        return h.withDayOfMonth(h.lengthOfMonth()).toString();
    }

    private Map<String, Object> tecnicoDeUsuario(int idUsuario) {
        try {
            return db.one("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE usuario_id = :id_usuario AND estado = 1 LIMIT 1", "id_usuario", idUsuario);
        } catch (Exception e) {
            log.error("Error obtenerDatosTecnicoPorUsuario: {}", e.getMessage());
            return null;
        }
    }

    private List<Map<String, Object>> lista(String sql, Object... kv) {
        try {
            return db.list(sql, kv);
        } catch (Exception e) {
            log.error("Horas extra: {}", e.getMessage());
            return List.of();
        }
    }

    /** diff() de PHP: horas + minutos/60 redondeado a 2 decimales (el día de más no se cuenta, igual que $diferencia->h). */
    private static double totalHoras(LocalDateTime ini, LocalDateTime fin) {
        long min = Duration.between(ini, fin).toMinutes();
        long h = (min / 60) % 24, m = min % 60;
        return Math.round((h + m / 60.0) * 100) / 100.0;
    }

    private static LocalTime hora(String s) {
        String t = s.trim();
        if (t.length() == 5) t += ":00";
        return LocalTime.parse(t.substring(0, 8));
    }

    /** Sanitiza el nombre del técnico igual que iconv TRANSLIT + preg_replace + strtoupper. */
    public static String carpetaTecnico(String nombre) {
        String n = Normalizer.normalize(nombre == null ? "" : nombre, Normalizer.Form.NFD).replaceAll("\\p{M}", "");
        n = n.replaceAll("[^A-Za-z0-9\\s_]", "");
        return n.trim().replaceAll("\\s+", "_").toUpperCase();
    }

    private void guardarFotos(int idRegistro, String nombreTecnico, String fechaReporte, List<MultipartFile> fotos) {
        if (fotos.isEmpty()) return;
        String mes;
        try {
            mes = LocalDate.parse(fechaReporte.substring(0, 10)).toString().substring(0, 7);
        } catch (Exception e) {
            mes = "1970-01";
        }
        String carpeta = "app/uploads/horas_extra/" + carpetaTecnico(nombreTecnico) + "/" + mes + "/";
        for (MultipartFile f : fotos) {
            String ruta = carpeta + "HE_" + idRegistro + "_FOTO_" + Archivos.uniqid() + ".jpg";
            if (archivos.guardarImagenOptimizadaExif(f, ruta, 1000, 75)) {
                try {
                    db.update("INSERT INTO evidencia_horas_extra (id_registro_he, ruta_archivo) VALUES (:id_registro, :ruta)", "id_registro", idRegistro, "ruta", ruta);
                } catch (Exception e) {
                    log.error("Error guardarEvidenciaFoto HE: {}", e.getMessage());
                }
            }
        }
    }

    // ================================================================ CREAR (técnico)

    @GetMapping(value = "/api/horaExtraCrear", params = "accion=index")
    public Map<String, Object> crearIndex(Req req) {
        int idUsuario = req.sesion().usuarioId();
        if (idUsuario == 0) return Map.of("error", "Sesión expirada.");
        var tec = tecnicoDeUsuario(idUsuario);
        if (tec == null || Conv.empty(tec.get("id_tecnico"))) return Map.of("error", "Tu usuario no está vinculado a un perfil de técnico.");
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Reportar Horas Extra");
        r.put("hoy", F.hoy());
        r.put("clientes", lista("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC"));
        r.put("puntos", lista("SELECT id_punto, nombre_punto, id_cliente FROM punto WHERE estado = 1 ORDER BY nombre_punto ASC"));
        return r;
    }

    @PostMapping(value = "/api/horaExtraCrear", params = "accion=guardar")
    public Map<String, Object> guardar(Req req) {
        var tec = tecnicoDeUsuario(req.sesion().usuarioId());
        if (tec == null) return Map.of("success", false, "msj", "Error de sesión del técnico.");
        int idCliente = req.intv("id_cliente", 0), idPunto = req.intv("id_punto", 0);
        String fecha = req.str("fecha_reporte", ""), hi = req.str("hora_inicio", ""), hf = req.str("hora_fin", "");
        String just = req.str("justificacion", "").trim();
        if (fecha.isEmpty() || hi.isEmpty() || hf.isEmpty() || just.isEmpty() || idPunto == 0)
            return Map.of("success", false, "msj", "⚠️ Por favor completa todos los campos requeridos.");
        double total;
        try {
            LocalDateTime ini = LocalDate.now().atTime(hora(hi)), fin = LocalDate.now().atTime(hora(hf));
            if (fin.isBefore(ini)) fin = fin.plusDays(1);
            total = totalHoras(ini, fin);
        } catch (Exception e) {
            return Map.of("success", false, "msj", "⚠️ Por favor completa todos los campos requeridos.");
        }
        long id;
        try {
            id = db.insert("""
                    INSERT INTO registro_horas_extra
                    (id_tecnico, id_cliente, id_punto, fecha_reporte, hora_inicio, hora_fin, total_horas, justificacion_tecnico, id_estado_aprobacion)
                    VALUES (:id_tecnico, :id_cliente, :id_punto, :fecha, :inicio, :fin, :total, :justificacion, 1)""",
                    "id_tecnico", tec.get("id_tecnico"), "id_cliente", idCliente == 0 ? null : idCliente, "id_punto", idPunto, "fecha", fecha,
                    "inicio", hi, "fin", hf, "total", total, "justificacion", just);
        } catch (Exception e) {
            log.error("Error guardarHoraExtra: {}", e.getMessage());
            return Map.of("success", false, "msj", "❌ Error al guardar el registro.");
        }
        guardarFotos((int) id, Conv.str(tec.get("nombre_tecnico")), fecha, req.files("evidencias"));
        return Map.of("success", true, "msj", "✅ Horas extra registradas con éxito.");
    }

    // ================================================================ HISTORIAL (técnico)

    @GetMapping(value = "/api/horaExtraHistorial", params = "accion=index")
    public Map<String, Object> historial(Req req) {
        int idUsuario = req.sesion().usuarioId();
        if (idUsuario == 0) return Map.of("error", "Sesión expirada.");
        var tec = tecnicoDeUsuario(idUsuario);
        if (tec == null) return Map.of("error", "Tu usuario no está vinculado a un perfil de técnico.");
        var lista = lista("""
                SELECT
                    rhe.id_registro_he,
                    rhe.fecha_reporte,
                    rhe.hora_inicio,
                    rhe.hora_fin,
                    rhe.total_horas,
                    rhe.justificacion_tecnico,
                    rhe.observacion_supervisor,
                    ea.nombre_estado AS estado_nombre,
                    c.nombre_cliente,
                    p.nombre_punto,
                    (
                        SELECT GROUP_CONCAT(CONCAT(ehe.id_evidencia, '::', ehe.ruta_archivo) SEPARATOR '||')
                        FROM evidencia_horas_extra ehe
                        WHERE ehe.id_registro_he = rhe.id_registro_he
                    ) AS fotos_detalle
                FROM registro_horas_extra rhe
                INNER JOIN estados_aprobacion_he ea ON rhe.id_estado_aprobacion = ea.id_estado
                LEFT JOIN cliente c ON rhe.id_cliente = c.id_cliente
                LEFT JOIN punto p ON rhe.id_punto = p.id_punto
                WHERE rhe.id_tecnico = :id_tecnico
                ORDER BY rhe.fecha_reporte DESC, rhe.id_registro_he DESC""", "id_tecnico", tec.get("id_tecnico"));
        return Map.of("titulo", "Mis Horas Extra", "reportesHE", lista);
    }

    private Map<String, Object> registroDeTecnico(int idRegistro, Object idTecnico) {
        try {
            return db.one("SELECT * FROM registro_horas_extra WHERE id_registro_he = :id_registro AND id_tecnico = :id_tecnico LIMIT 1",
                    "id_registro", idRegistro, "id_tecnico", idTecnico);
        } catch (Exception e) {
            return null;
        }
    }

    @PostMapping(value = "/api/horaExtraHistorial", params = "accion=ajaxEditarRegistro")
    public Map<String, Object> editarRegistro(Req req) {
        var tec = tecnicoDeUsuario(req.sesion().usuarioId());
        if (tec != null) {
            int idTec = Conv.toInt(tec.get("id_tecnico"));
            int idReg = req.intv("id_registro", 0);
            String fecha = req.str("fecha_reporte", "").trim(), hi = req.str("hora_inicio", "").trim(), hf = req.str("hora_fin", "").trim();
            String just = req.str("justificacion_tecnico", "").trim();
            var actual = registroDeTecnico(idReg, idTec);
            if (actual == null) return Map.of("success", false, "msj", "El registro no existe o no te pertenece.");
            if (Conv.toInt(actual.get("id_estado_aprobacion")) == 2) return Map.of("success", false, "msj", "No puedes editar un registro que ya fue APROBADO.");
            try {
                LocalDate d = LocalDate.parse(fecha);
                LocalDateTime ini = d.atTime(hora(hi)), fin = d.atTime(hora(hf));
                if (!fin.isAfter(ini)) fin = fin.plusDays(1);
                double total = totalHoras(ini, fin);
                db.update("""
                        UPDATE registro_horas_extra
                        SET fecha_reporte = :fecha,
                            hora_inicio = :hora_inicio,
                            hora_fin = :hora_fin,
                            total_horas = :total_horas,
                            justificacion_tecnico = :justificacion,
                            id_estado_aprobacion = 1,
                            observacion_supervisor = NULL
                        WHERE id_registro_he = :id_registro AND id_tecnico = :id_tecnico""",
                        "fecha", fecha, "hora_inicio", hi, "hora_fin", hf, "total_horas", total, "justificacion", just, "id_registro", idReg, "id_tecnico", idTec);
                guardarFotos(idReg, Conv.str(tec.get("nombre_tecnico")).isEmpty() ? "TECNICO" : Conv.str(tec.get("nombre_tecnico")), fecha, req.files("nuevas_evidencias"));
                return Map.of("success", true, "msj", "Registro y evidencias actualizados correctamente.");
            } catch (Exception e) {
                log.error("Error actualizando hora extra: {}", e.getMessage());
            }
        }
        return Map.of("success", false, "msj", "No se pudo guardar la información.");
    }

    @PostMapping(value = "/api/horaExtraHistorial", params = "accion=ajaxEliminarEvidencia")
    public Map<String, Object> eliminarEvidencia(Req req) {
        var tec = tecnicoDeUsuario(req.sesion().usuarioId());
        if (tec != null) {
            int idEv = req.intv("id_evidencia", 0), idReg = req.intv("id_registro", 0);
            var actual = registroDeTecnico(idReg, tec.get("id_tecnico"));
            if (actual == null) return Map.of("success", false, "msj", "El registro no te pertenece.");
            if (Conv.toInt(actual.get("id_estado_aprobacion")) == 2) return Map.of("success", false, "msj", "No puedes eliminar imágenes de un registro APROBADO.");
            try {
                var ev = db.one("""
                        SELECT id_evidencia, id_registro_he, ruta_archivo
                        FROM evidencia_horas_extra
                        WHERE id_evidencia = :id_evidencia AND id_registro_he = :id_registro LIMIT 1""", "id_evidencia", idEv, "id_registro", idReg);
                if (ev != null) {
                    archivos.borrar(Conv.str(ev.get("ruta_archivo")));
                    db.update("DELETE FROM evidencia_horas_extra WHERE id_evidencia = :id_evidencia AND id_registro_he = :id_registro", "id_evidencia", idEv, "id_registro", idReg);
                    return Map.of("success", true, "msj", "Imagen eliminada correctamente.");
                }
            } catch (Exception e) {
                log.error("Error eliminarEvidenciaFoto HE: {}", e.getMessage());
            }
        }
        return Map.of("success", false, "msj", "No se pudo eliminar la imagen.");
    }

    // ================================================================ ADMIN

    @GetMapping(value = "/api/horaExtraAdmin", params = "accion=index")
    public Map<String, Object> admin(Req req) {
        if (req.sesion().usuarioId() == 0) return Map.of("error", "Sesión expirada.");
        String fi = req.str("fecha_inicio", primeroMes()), ff = req.str("fecha_fin", ultimoMes());
        String idTec = req.str("id_tecnico", ""), idEst = req.str("id_estado", "");
        StringBuilder sql = new StringBuilder("""
                SELECT
                    rhe.id_registro_he,
                    rhe.fecha_reporte,
                    rhe.hora_inicio,
                    rhe.hora_fin,
                    rhe.total_horas,
                    rhe.justificacion_tecnico,
                    rhe.observacion_supervisor,
                    rhe.fecha_registro,
                    rhe.id_estado_aprobacion,
                    ea.nombre_estado AS estado_nombre,
                    t.nombre_tecnico,
                    c.nombre_cliente,
                    p.nombre_punto,
                    (
                        SELECT GROUP_CONCAT(ehe.ruta_archivo SEPARATOR '||')
                        FROM evidencia_horas_extra ehe
                        WHERE ehe.id_registro_he = rhe.id_registro_he
                    ) AS rutas_fotos
                FROM registro_horas_extra rhe
                INNER JOIN tecnico t ON rhe.id_tecnico = t.id_tecnico
                INNER JOIN estados_aprobacion_he ea ON rhe.id_estado_aprobacion = ea.id_estado
                LEFT JOIN cliente c ON rhe.id_cliente = c.id_cliente
                LEFT JOIN punto p ON rhe.id_punto = p.id_punto
                WHERE 1=1""");
        Map<String, Object> p = new LinkedHashMap<>();
        if (!fi.isEmpty()) { sql.append(" AND rhe.fecha_reporte >= :fecha_inicio"); p.put("fecha_inicio", fi); }
        if (!ff.isEmpty()) { sql.append(" AND rhe.fecha_reporte <= :fecha_fin"); p.put("fecha_fin", ff); }
        if (!idTec.isEmpty()) { sql.append(" AND rhe.id_tecnico = :id_tecnico"); p.put("id_tecnico", idTec); }
        if (!idEst.isEmpty()) { sql.append(" AND rhe.id_estado_aprobacion = :id_estado"); p.put("id_estado", idEst); }
        sql.append(" ORDER BY rhe.fecha_reporte DESC, rhe.id_registro_he DESC");
        List<Map<String, Object>> reportes;
        try {
            reportes = db.list(sql.toString(), p);
        } catch (Exception e) {
            log.error("Error obteniendo horas extra para admin: {}", e.getMessage());
            reportes = List.of();
        }
        double hA = 0, hP = 0, hR = 0;
        int cA = 0, cP = 0, cR = 0;
        for (var r : reportes) {
            double h = Conv.toDouble(r.get("total_horas"));
            int est = Conv.toInt(r.get("id_estado_aprobacion"));
            if (est == 1) { cP++; hP += h; }
            else if (est == 2) { cA++; hA += h; }
            else if (est == 3) { cR++; hR += h; }
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Administración de Horas Extra");
        r.put("filtros", Map.of("fecha_inicio", fi, "fecha_fin", ff, "id_tecnico", idTec, "id_estado", idEst));
        r.put("tecnicos", lista("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
        r.put("estados", lista("SELECT id_estado, nombre_estado FROM estados_aprobacion_he ORDER BY id_estado ASC"));
        r.put("reportes", reportes);
        r.put("totalHorasAprobadas", hA);
        r.put("totalHorasPendientes", hP);
        r.put("totalHorasRechazadas", hR);
        r.put("cantAprobadas", cA);
        r.put("cantPendientes", cP);
        r.put("cantRechazadas", cR);
        return r;
    }

    @PostMapping(value = "/api/horaExtraAdmin", params = "accion=ajaxCambiarEstado")
    public Map<String, Object> cambiarEstado(Req req) {
        int idReg = req.intv("id_registro", 0), idEst = req.intv("id_estado", 0);
        String obs = req.str("observacion", "").trim();
        if (idReg > 0 && idEst > 0) {
            try {
                db.update("""
                        UPDATE registro_horas_extra
                        SET id_estado_aprobacion = :id_estado,
                            observacion_supervisor = :observacion
                        WHERE id_registro_he = :id_registro""", "id_estado", idEst, "observacion", obs, "id_registro", idReg);
                return Map.of("success", true, "msj", "Estado actualizado correctamente.");
            } catch (Exception e) {
                log.error("Error actualizando estado de horas extra: {}", e.getMessage());
            }
        }
        return Map.of("success", false, "msj", "No se pudo actualizar el registro.");
    }

    @PostMapping(value = "/api/horaExtraAdmin", params = "accion=ajaxEliminarRegistro")
    public Map<String, Object> eliminarRegistro(Req req) {
        int idReg = req.intv("id_registro", 0);
        if (idReg <= 0) return Map.of("success", false, "msj", "Registro inválido.");
        Map<String, Object> reg;
        try {
            reg = db.one("SELECT id_registro_he, id_estado_aprobacion FROM registro_horas_extra WHERE id_registro_he = :id_registro LIMIT 1", "id_registro", idReg);
        } catch (Exception e) {
            reg = null;
        }
        if (reg == null) return Map.of("success", false, "msj", "El registro no existe.");
        if (Conv.toInt(reg.get("id_estado_aprobacion")) == 2) return Map.of("success", false, "msj", "No se puede borrar: el registro ya está APROBADO.");
        try {
            for (Object ruta : db.column("SELECT ruta_archivo FROM evidencia_horas_extra WHERE id_registro_he = :id_registro", "id_registro", idReg)) {
                archivos.borrar(Conv.str(ruta));
            }
        } catch (Exception e) {
            log.error("Error obteniendo rutas evidencias HE: {}", e.getMessage());
        }
        try {
            boolean ok = db.tx(() -> {
                db.update("DELETE FROM evidencia_horas_extra WHERE id_registro_he = :id_registro", "id_registro", idReg);
                int n = db.update("DELETE FROM registro_horas_extra WHERE id_registro_he = :id_registro", "id_registro", idReg);
                if (n == 0) throw new IllegalStateException("sin filas");
                return true;
            });
            if (ok) return Map.of("success", true, "msj", "Registro y fotos eliminados correctamente.");
        } catch (Exception e) {
            log.error("Error eliminando registro HE: {}", e.getMessage());
        }
        return Map.of("success", false, "msj", "No se pudo eliminar el registro.");
    }

    // ================================================================ REPORTE (PDF / Excel)

    private List<Map<String, Object>> reporte(String fi, String ff, String idTec, String idEst) {
        StringBuilder sql = new StringBuilder("""
                SELECT
                    rhe.id_registro_he,
                    rhe.fecha_reporte,
                    rhe.hora_inicio,
                    rhe.hora_fin,
                    rhe.total_horas,
                    rhe.justificacion_tecnico,
                    rhe.observacion_supervisor,
                    rhe.fecha_registro,
                    ea.nombre_estado AS estado_nombre,
                    t.nombre_tecnico,
                    COALESCE(c.nombre_cliente, 'SIN CLIENTE') AS nombre_cliente,
                    COALESCE(p.nombre_punto, 'SIN PUNTO') AS nombre_punto,
                    (
                        SELECT GROUP_CONCAT(ehe.ruta_archivo SEPARATOR '||')
                        FROM evidencia_horas_extra ehe
                        WHERE ehe.id_registro_he = rhe.id_registro_he
                    ) AS rutas_fotos
                FROM registro_horas_extra rhe
                INNER JOIN tecnico t ON rhe.id_tecnico = t.id_tecnico
                INNER JOIN estados_aprobacion_he ea ON rhe.id_estado_aprobacion = ea.id_estado
                LEFT JOIN cliente c ON rhe.id_cliente = c.id_cliente
                LEFT JOIN punto p ON rhe.id_punto = p.id_punto
                WHERE rhe.fecha_reporte BETWEEN :fecha_inicio AND :fecha_fin""");
        Map<String, Object> p = new LinkedHashMap<>(Map.of("fecha_inicio", fi, "fecha_fin", ff));
        if (!Conv.empty(idTec)) { sql.append(" AND rhe.id_tecnico = :id_tecnico"); p.put("id_tecnico", idTec); }
        if (!Conv.empty(idEst)) { sql.append(" AND rhe.id_estado_aprobacion = :id_estado"); p.put("id_estado", idEst); }
        sql.append(" ORDER BY rhe.fecha_reporte ASC, rhe.hora_inicio ASC");
        try {
            return db.list(sql.toString(), p);
        } catch (Exception e) {
            log.error("Error en obtenerReporteHorasExtra: {}", e.getMessage());
            return List.of();
        }
    }

    private static String hm(Object t) {
        String s = Conv.str(t);
        return s.length() >= 5 ? s.substring(0, 5) : s;
    }

    @GetMapping(value = "/api/horaExtraGenerar", params = "accion=generar")
    public ResponseEntity<?> generarPdf(Req req) {
        String fi = req.str("fecha_inicio", primeroMes()), ff = req.str("fecha_fin", ultimoMes());
        var reportes = reporte(fi, ff, req.str("id_tecnico", ""), req.str("id_estado", ""));
        String css;
        try (var in = new ClassPathResource("plantillas/hora-extra-pdf.css").getInputStream()) {
            css = new String(in.readAllBytes(), StandardCharsets.UTF_8);
        } catch (Exception e) {
            css = "";
        }
        StringBuilder h = new StringBuilder();
        h.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><title>Reporte de Horas Extra</title>")
                .append("<link href=\"https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&family=JetBrains+Mono:wght@500;700&display=swap\" rel=\"stylesheet\">")
                .append("<style>").append(css).append("</style></head><body><div class=\"page\"><div class=\"header\"><div><img src=\"")
                .append(pdf.dataUri("app/logos/logoInees.jpg")).append("\" alt=\"Logo INEES\"></div><div class=\"header-title\"><h1>Reporte de Horas Extra</h1><p>Rango: <strong>")
                .append(F.dmy(fi)).append("</strong> al <strong>").append(F.dmy(ff)).append("</strong></p></div></div><div class=\"divider\"></div>")
                .append("<div class=\"table-wrapper\"><table class=\"report-table\"><thead><tr><th>Fecha</th><th>Técnico</th><th>Cliente / Punto</th><th>Horario</th>")
                .append("<th style=\"text-align: center;\">Horas</th><th>Justificación</th><th style=\"text-align: center;\">Estado</th></tr></thead><tbody>");
        double suma = 0;
        for (var r : reportes) {
            suma += Conv.toDouble(r.get("total_horas"));
            String est = Conv.str(r.get("estado_nombre"));
            String b = "Aprobada".equals(est) ? "badge-aprobada" : "Rechazada".equals(est) ? "badge-rechazada" : "badge-pendiente";
            h.append("<tr class=\"no-break\"><td style=\"font-weight: 600; color: #0f172a;\">").append(F.dmy(r.get("fecha_reporte")))
                    .append("</td><td><span class=\"tecnico\">").append(Conv.h(r.get("nombre_tecnico"))).append("</span></td><td><strong>")
                    .append(Conv.h(r.get("nombre_punto"))).append("</strong><span class=\"cliente-detalle\">").append(Conv.h(r.get("nombre_cliente")))
                    .append("</span></td><td><span class=\"horario\">").append(hm(r.get("hora_inicio"))).append(" – ").append(hm(r.get("hora_fin")))
                    .append("</span></td><td style=\"text-align: center;\"><span class=\"horas\">").append(String.format(java.util.Locale.US, "%,.2f", Conv.toDouble(r.get("total_horas"))))
                    .append("</span></td><td><span class=\"justificacion\">").append(Conv.h(r.get("justificacion_tecnico"))).append("</span>");
            List<String> fotos = new ArrayList<>();
            if (!Conv.empty(r.get("rutas_fotos"))) {
                for (String ruta : Conv.str(r.get("rutas_fotos")).split("\\|\\|")) {
                    int pos = ruta.indexOf("app/uploads/");
                    String limpia = pos >= 0 ? ruta.substring(pos) : ruta.replaceFirst("^/+", "");
                    try {
                        Path f = archivos.resolver(limpia);
                        if (Files.isRegularFile(f)) {
                            String ext = Archivos.extension(f.getFileName().toString());
                            fotos.add("data:image/" + ("jpg".equals(ext) ? "jpeg" : ext) + ";base64," + Base64.getEncoder().encodeToString(Files.readAllBytes(f)));
                        }
                    } catch (Exception ignored) {
                    }
                }
            }
            if (!fotos.isEmpty()) {
                h.append("<div class=\"foto-container\">");
                for (String f : fotos) h.append("<img src=\"").append(f).append("\" alt=\"Evidencia\">");
                h.append("</div>");
            }
            h.append("</td><td style=\"text-align: center;\"><span class=\"badge ").append(b).append("\">").append(Conv.h(est)).append("</span></td></tr>");
        }
        h.append("</tbody></table></div><div class=\"total-box\"><span class=\"label\">📊 Total Horas Registradas en el período</span><span class=\"value\">")
                .append(String.format(java.util.Locale.US, "%,.2f", suma)).append(" <small>hrs</small></span></div></div></body></html>");
        try {
            byte[] b = pdf.generar(h.toString(), "size: A4; margin: 10mm");
            return Descarga.pdf(b, "Reporte_Horas_Extra_" + fi + "_al_" + ff + ".pdf", true);
        } catch (Exception e) {
            return Descarga.html("<h1>Error generando PDF de Horas Extra</h1><p>" + Conv.h(e.getMessage()) + "</p>");
        }
    }

    @GetMapping(value = "/api/horaExtraExcel", params = "accion=generar")
    public ResponseEntity<byte[]> generarExcel(Req req) {
        String fi = req.str("fecha_inicio", primeroMes()), ff = req.str("fecha_fin", ultimoMes());
        var reportes = reporte(fi, ff, req.str("id_tecnico", ""), req.str("id_estado", ""));
        String periodo = F.dmy(fi) + " AL " + F.dmy(ff);
        Map<String, List<Map<String, Object>>> porTecnico = new TreeMap<>();
        for (var r : reportes) porTecnico.computeIfAbsent(Conv.str(r.get("nombre_tecnico")), k -> new ArrayList<>()).add(r);
        porTecnico.values().forEach(g -> g.sort((a, b) -> Conv.str(a.get("fecha_reporte")).compareTo(Conv.str(b.get("fecha_reporte")))));

        Excel x = null;
        if (porTecnico.isEmpty()) {
            x = new Excel(nombreHoja("SIN REGISTROS"));
            hojaAsistencia(x, List.of(), "SIN REGISTROS", periodo);
        } else {
            for (var e : porTecnico.entrySet()) {
                String nombre = e.getKey().isEmpty() ? "SIN NOMBRE" : e.getKey();
                if (x == null) x = new Excel(nombreHoja(nombre));
                else x.nuevaHoja(nombreHoja(nombre));
                hojaAsistencia(x, e.getValue(), e.getKey(), periodo);
            }
        }
        x.usarHoja(0);
        x.wb().setActiveSheet(0);
        String nombre = "FORMATO_HORAS_EXTRAS_" + LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyyMMdd_HHmmss")) + ".xlsx";
        return Descarga.excel(x.bytes(), nombre);
    }

    private static String nombreHoja(String n) {
        String l = n.replaceAll("[\\\\/?*\\[\\]:]", "-");
        return l.length() > 31 ? l.substring(0, 31) : l;
    }

    private void hojaAsistencia(Excel x, List<Map<String, Object>> reportes, String tecnico, String periodo) {
        String[][] anchos = { { "A", "12" }, { "B", "20" }, { "C", "10" }, { "D", "10" }, { "E", "22" }, { "F", "10" }, { "G", "20" }, { "H", "20" }, { "I", "26" } };
        for (String[] a : anchos) x.ancho(a[0], Double.parseDouble(a[1]));
        Excel.E borde = Excel.E.of().borde().bordeColor("000000");
        x.merge("A1:B1").merge("C1:I1").alto(1, 45);
        x.set("C1", "COORDINACIÓN DE OPERACIONES TÉCNICAS\nFORMATO REGISTRO DE ASISTENCIA PARA EL RECONOCIMIENTO DE HORAS EXTRAS, RECARGOS NOCTURNOS, DOMINICALES FESTIVOS Y COMPENSATORIOS");
        x.estilo("C1", Excel.E.of().wrap().center().bold().size(10));
        x.imagen(archivos.resolver("app/logos/logoInees.jpg"), "A1", 50, 5, 5);
        x.merge("B2:D2").merge("H2:I2");
        x.set("A2", "TÉCNICO:").set("B2", tecnico).set("G2", "PERIODO REGISTRO:").set("H2", periodo);
        x.estilo("A2", Excel.E.of().bold().fill("F1F5F9"));
        x.estilo("G2", Excel.E.of().bold().fill("F1F5F9"));
        x.merge("A3:I3").alto(3, 50);
        x.set("A3", "NOTA: TOTALICE LAS HORAS ORDINARIAS DEL PERIODO, FESTIVAS O DOMINICALES EN SU RESPECTIVA CASILLA. RECUERDE QUE EL FORMATO MAL DILIGENCIADO SERÁ DEVUELTO. CONSERVE COPIA DE ESTE FORMATO.");
        x.estilo("A3", Excel.E.of().wrap().v("top").size(8));
        for (String m : new String[] { "A4:A5", "B4:B5", "C4:D4", "E4:E5", "F4:F5", "G4:H5", "I4:I5" }) x.merge(m);
        x.alto(4, 20).alto(5, 20);
        x.set("A4", "FECHA D/M/A").set("B4", "TÉCNICO").set("C4", "HORA ENTRADA / SALIDA").set("E4", "NÚMERO MÁQUINA / PUNTO").set("F4", "TOTAL (HRS)")
                .set("G4", "DETALLE DEL LUGAR Y ACTIVIDAD").set("I4", "EVIDENCIA FOTOGRÁFICA").set("C5", "ENTRADA").set("D5", "SALIDA");
        x.estilo("A4:I5", Excel.E.of().bold().center().fill("DBEAFE").wrap());

        int fila = 6;
        double total = 0;
        for (var r : reportes) {
            total += Conv.toDouble(r.get("total_horas"));
            x.merge("G" + fila + ":H" + fila).alto(fila, 60);
            x.setTexto("A" + fila, F.dmy(r.get("fecha_reporte")));
            x.setTexto("B" + fila, r.get("nombre_tecnico"));
            x.setTexto("C" + fila, hm(r.get("hora_inicio")));
            x.setTexto("D" + fila, hm(r.get("hora_fin")));
            x.setTexto("E" + fila, r.get("nombre_punto"));
            x.setTexto("F" + fila, String.format(java.util.Locale.US, "%,.2f", Conv.toDouble(r.get("total_horas"))));
            x.setTexto("G" + fila, "Cliente: " + Conv.str(r.get("nombre_cliente")) + " - Detalle: " + Conv.str(r.get("justificacion_tecnico")));
            x.estilo("G" + fila, Excel.E.of().wrap().v("top"));
            if (!Conv.empty(r.get("rutas_fotos"))) {
                int offX = 3;
                for (String ruta : Conv.str(r.get("rutas_fotos")).split("\\|\\|")) {
                    ruta = ruta.trim();
                    if (ruta.isEmpty()) continue;
                    Path disco;
                    try {
                        disco = archivos.resolver(ruta);
                    } catch (Exception e) {
                        continue;
                    }
                    if (!Files.isRegularFile(disco)) {
                        log.warn("Excel horas extra: no se encontró la evidencia en disco -> {}", disco);
                        continue;
                    }
                    x.imagen(disco, "I" + fila, 52, offX, 3);
                    int ancho = 52;
                    try {
                        var img = javax.imageio.ImageIO.read(disco.toFile());
                        if (img != null && img.getHeight() > 0) ancho = (int) Math.round(52.0 * img.getWidth() / img.getHeight());
                    } catch (Exception ignored) {
                    }
                    offX += ancho + 4;
                }
            }
            fila++;
        }
        int finDatos = fila - 1;
        if (finDatos >= 6) {
            x.estilo("A4:I" + finDatos, borde);
            x.estilo("A6:F" + finDatos, Excel.E.of().center());
        } else {
            finDatos = 5;
        }
        int t1 = finDatos + 1, t2 = finDatos + 2, t3 = finDatos + 3;
        x.merge("A" + t1 + ":F" + t3);
        x.set("A" + t1, "_______________________          _______________________          _______________________\nFIRMA FUNCIONARIO                          FIRMA JEFE                            FIRMA SUPERVISOR");
        x.estilo("A" + t1, Excel.E.of().wrap().v("bottom").h("center").bold());
        x.merge("G" + t1 + ":H" + t1).set("G" + t1, "TOTAL DOMINICALES:").setTexto("I" + t1, "0.00");
        x.merge("G" + t2 + ":H" + t2).set("G" + t2, "TOTAL ORDINARIAS:").setTexto("I" + t2, String.format(java.util.Locale.US, "%,.2f", total));
        x.merge("G" + t3 + ":H" + t3).set("G" + t3, "TOTAL CON RECARGO NOCT:").setTexto("I" + t3, "0.00");
        x.estilo("G" + t1 + ":H" + t3, Excel.E.of().bold().fill("F1F5F9"));
        x.estilo("I" + t1 + ":I" + t3, Excel.E.of().h("right").bold());
        x.estilo("A" + t1 + ":I" + t3, borde);
        int obs = t3 + 1;
        x.merge("A" + obs + ":I" + obs).alto(obs, 50).set("A" + obs, "OBSERVACIONES:");
        x.estilo("A" + obs, Excel.E.of().bold().v("top").borde().bordeColor("000000"));
    }
}
