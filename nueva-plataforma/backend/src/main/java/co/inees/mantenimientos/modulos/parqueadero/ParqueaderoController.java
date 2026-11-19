package co.inees.mantenimientos.modulos.parqueadero;

import java.awt.image.BufferedImage;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.Base64;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

import javax.imageio.IIOImage;
import javax.imageio.ImageIO;
import javax.imageio.ImageWriteParam;
import javax.imageio.ImageWriter;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
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
import co.inees.mantenimientos.modulos.horaextra.HoraExtraController;

/** Port de app/controllers/parqueadero/* (parqueaderoCrear, parqueaderoHistorial, parqueaderoAdmin + PDF/Excel/rotación). */
@RestController
public class ParqueaderoController {

    private static final Logger log = LoggerFactory.getLogger(ParqueaderoController.class);
    private final Db db;
    private final Archivos archivos;
    private final Pdf pdf;

    public ParqueaderoController(Db db, Archivos archivos, Pdf pdf) {
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
            return null;
        }
    }

    private List<Map<String, Object>> puntos() {
        try {
            return db.list("SELECT id_punto, nombre_punto FROM punto WHERE estado = 1 ORDER BY nombre_punto ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    private static String mesDe(String fecha) {
        try {
            return LocalDate.parse(fecha.substring(0, 10)).toString().substring(0, 7);
        } catch (Exception e) {
            return "1970-01";
        }
    }

    // ================================================================ CREAR

    @GetMapping(value = "/api/parqueaderoCrear", params = "accion=index")
    public Map<String, Object> crearIndex(Req req) {
        int idUsuario = req.sesion().usuarioId();
        if (idUsuario == 0) return Map.of("error", "Sesión expirada.");
        var tec = tecnicoDeUsuario(idUsuario);
        if (tec == null || Conv.empty(tec.get("id_tecnico"))) return Map.of("error", "Tu usuario no está vinculado a un perfil de técnico.");
        return Map.of("titulo", "Registrar Parqueadero", "hoy", F.hoy(), "puntos", puntos());
    }

    @PostMapping(value = "/api/parqueaderoCrear", params = "accion=guardar")
    public Map<String, Object> guardar(Req req) {
        var tec = tecnicoDeUsuario(req.sesion().usuarioId());
        if (tec == null) return Map.of("success", false, "msj", "Error de sesión del técnico.");
        String carpetaTec = HoraExtraController.carpetaTecnico(Conv.str(tec.get("nombre_tecnico")));
        MultipartFile foto = req.file("foto_factura");
        if (foto == null || foto.isEmpty()) return Map.of("success", false, "msj", "Error al subir la imagen de la factura.");
        String fecha = req.str("fecha_servicio", "");
        String numero = req.str("numero_factura", "").replaceAll("[^A-Za-z0-9_-]", "").toUpperCase();
        String carpeta = "app/uploads/parqueaderos/" + carpetaTec + "/" + mesDe(fecha) + "/";
        String ruta = carpeta + "PARQ_" + carpetaTec + "_" + fecha + "_FACT_" + numero + ".jpg";
        if (!archivos.guardarImagenOptimizadaExif(foto, ruta, 1200, 80)) return Map.of("success", false, "msj", "❌ Error al procesar la imagen.");
        try {
            db.update("""
                    INSERT INTO facturas_parqueadero
                    (id_tecnico, id_punto, fecha_servicio, hora_inicio, hora_fin, numero_factura, valor_factura, ruta_foto)
                    VALUES (:id_tecnico, :id_punto, :fecha_servicio, :hora_inicio, :hora_fin, :numero_factura, :valor_factura, :ruta_foto)""",
                    "id_tecnico", tec.get("id_tecnico"), "id_punto", req.str("id_punto", ""), "fecha_servicio", fecha,
                    "hora_inicio", req.str("hora_inicio", ""), "hora_fin", req.str("hora_fin", ""), "numero_factura", req.str("numero_factura", ""),
                    "valor_factura", req.str("valor_factura", ""), "ruta_foto", ruta);
            return Map.of("success", true, "msj", "✅ Factura registrada correctamente.");
        } catch (Exception e) {
            log.error("Error guardando factura parqueadero: {}", e.getMessage());
            archivos.borrar(ruta);
            return Map.of("success", false, "msj", "❌ Error al guardar en BD.");
        }
    }

    // ================================================================ HISTORIAL

    @GetMapping(value = "/api/parqueaderoHistorial", params = "accion=index")
    public Map<String, Object> historial(Req req) {
        int idUsuario = req.sesion().usuarioId();
        if (idUsuario == 0) return Map.of("error", "Sesión expirada.");
        var tec = tecnicoDeUsuario(idUsuario);
        if (tec == null) return Map.of("error", "Tu usuario no está vinculado a un perfil de técnico.");
        List<Map<String, Object>> facturas;
        try {
            facturas = db.list("""
                    SELECT fp.id_factura_parqueadero, fp.id_punto, fp.fecha_servicio, fp.hora_inicio, fp.hora_fin,
                            fp.numero_factura, fp.valor_factura, fp.ruta_foto, p.nombre_punto
                    FROM facturas_parqueadero fp
                    INNER JOIN punto p ON fp.id_punto = p.id_punto
                    WHERE fp.id_tecnico = :id_tecnico AND fp.estado = 1
                    ORDER BY fp.fecha_servicio DESC, fp.id_factura_parqueadero DESC""", "id_tecnico", tec.get("id_tecnico"));
        } catch (Exception e) {
            log.error("Error obteniendo historial de parqueaderos: {}", e.getMessage());
            facturas = List.of();
        }
        return Map.of("titulo", "Historial de Parqueaderos", "facturas", facturas, "puntos", puntos());
    }

    private Map<String, Object> facturaDeTecnico(int idFactura, Object idTecnico) {
        try {
            return db.one("SELECT * FROM facturas_parqueadero WHERE id_factura_parqueadero = :id_factura AND id_tecnico = :id_tecnico AND estado = 1 LIMIT 1",
                    "id_factura", idFactura, "id_tecnico", idTecnico);
        } catch (Exception e) {
            return null;
        }
    }

    @PostMapping(value = "/api/parqueaderoHistorial", params = "accion=ajaxEditarFactura")
    public Map<String, Object> editarFactura(Req req) {
        var tec = tecnicoDeUsuario(req.sesion().usuarioId());
        if (tec != null) {
            int idTec = Conv.toInt(tec.get("id_tecnico"));
            int idFactura = req.intv("id_factura", 0), idPunto = req.intv("id_punto", 0);
            String fecha = req.str("fecha_servicio", ""), hi = req.str("hora_inicio", ""), hf = req.str("hora_fin", "");
            String valor = req.str("valor_factura", "0");
            String numero = req.str("numero_factura", "").replaceAll("[^A-Za-z0-9_-]", "").toUpperCase();
            if (fecha.isEmpty() || hi.isEmpty() || hf.isEmpty() || idPunto == 0 || numero.isEmpty() || Conv.empty(valor) || "0".equals(valor) || idFactura == 0)
                return Map.of("success", false, "msj", "Todos los campos marcados son obligatorios.");
            var actual = facturaDeTecnico(idFactura, idTec);
            if (actual == null) return Map.of("success", false, "msj", "El registro no existe o no te pertenece.");
            String nuevaRuta = null;
            MultipartFile foto = req.file("foto_factura");
            if (foto != null && !foto.isEmpty()) {
                String carpetaTec = HoraExtraController.carpetaTecnico(Conv.str(tec.get("nombre_tecnico")));
                String ext = Archivos.extension(foto.getOriginalFilename());
                String ruta = "app/uploads/parqueaderos/" + carpetaTec + "/" + mesDe(fecha) + "/PARQ_" + carpetaTec + "_" + fecha + "_FACT_" + numero + "_EDIT_" + Archivos.uniqid() + "." + ext;
                try {
                    archivos.guardar(foto, ruta);
                    nuevaRuta = ruta;
                    if (!Conv.empty(actual.get("ruta_foto"))) archivos.borrar(Conv.str(actual.get("ruta_foto")));
                } catch (Exception e) {
                    log.warn("No se pudo guardar la nueva foto de parqueadero: {}", e.getMessage());
                }
            }
            Map<String, Object> p = new LinkedHashMap<>();
            p.put("id_punto", idPunto);
            p.put("fecha_servicio", fecha);
            p.put("hora_inicio", hi);
            p.put("hora_fin", hf);
            p.put("numero_factura", req.str("numero_factura", ""));
            p.put("valor_factura", valor);
            p.put("id_factura", idFactura);
            p.put("id_tecnico", idTec);
            String sql = """
                    UPDATE facturas_parqueadero
                    SET id_punto = :id_punto,
                        fecha_servicio = :fecha_servicio,
                        hora_inicio = :hora_inicio,
                        hora_fin = :hora_fin,
                        numero_factura = :numero_factura,
                        valor_factura = :valor_factura""";
            if (nuevaRuta != null) {
                sql += ", ruta_foto = :ruta_foto";
                p.put("ruta_foto", nuevaRuta);
            }
            sql += " WHERE id_factura_parqueadero = :id_factura AND id_tecnico = :id_tecnico";
            try {
                db.update(sql, p);
                return Map.of("success", true, "msj", "Factura actualizada con éxito.");
            } catch (Exception e) {
                log.error("Error actualizando factura parqueadero: {}", e.getMessage());
            }
        }
        return Map.of("success", false, "msj", "No se pudo actualizar el registro.");
    }

    @PostMapping(value = "/api/parqueaderoHistorial", params = "accion=ajaxEliminarFoto")
    public Map<String, Object> eliminarFoto(Req req) {
        var tec = tecnicoDeUsuario(req.sesion().usuarioId());
        if (tec != null) {
            int idFactura = req.intv("id_factura", 0), idTec = Conv.toInt(tec.get("id_tecnico"));
            var f = facturaDeTecnico(idFactura, idTec);
            if (f != null) {
                if (!Conv.empty(f.get("ruta_foto"))) archivos.borrar(Conv.str(f.get("ruta_foto")));
                try {
                    db.update("UPDATE facturas_parqueadero SET ruta_foto = '' WHERE id_factura_parqueadero = :id_factura AND id_tecnico = :id_tecnico",
                            "id_factura", idFactura, "id_tecnico", idTec);
                    return Map.of("success", true, "msj", "Foto eliminada correctamente. Recuerda adjuntar una nueva foto antes de guardar.");
                } catch (Exception e) {
                    log.error("Error al eliminar foto de parqueadero: {}", e.getMessage());
                }
            }
        }
        return Map.of("success", false, "msj", "No se pudo eliminar la imagen de la factura.");
    }

    // ================================================================ ADMIN

    private List<Map<String, Object>> facturas(String fi, String ff, String idTec, String orden) {
        StringBuilder sql = new StringBuilder("""
                SELECT fp.id_factura_parqueadero, fp.fecha_servicio, fp.hora_inicio, fp.hora_fin,
                        fp.numero_factura, fp.valor_factura, fp.ruta_foto, fp.fecha_registro,
                        t.nombre_tecnico, p.nombre_punto
                FROM facturas_parqueadero fp
                INNER JOIN tecnico t ON fp.id_tecnico = t.id_tecnico
                INNER JOIN punto p ON fp.id_punto = p.id_punto
                WHERE fp.estado = 1""");
        Map<String, Object> p = new LinkedHashMap<>();
        if (!Conv.empty(fi)) { sql.append(" AND fp.fecha_servicio >= :fecha_inicio"); p.put("fecha_inicio", fi); }
        if (!Conv.empty(ff)) { sql.append(" AND fp.fecha_servicio <= :fecha_fin"); p.put("fecha_fin", ff); }
        if (!Conv.empty(idTec)) { sql.append(" AND fp.id_tecnico = :id_tecnico"); p.put("id_tecnico", idTec); }
        sql.append(orden);
        try {
            return db.list(sql.toString(), p);
        } catch (Exception e) {
            log.error("Error obteniendo facturas de parqueadero: {}", e.getMessage());
            return List.of();
        }
    }

    @GetMapping(value = "/api/parqueaderoAdmin", params = "accion=index")
    public Map<String, Object> admin(Req req) {
        if (req.sesion().usuarioId() == 0) return Map.of("error", "Sesión expirada.");
        String fi = req.str("fecha_inicio", primeroMes()), ff = req.str("fecha_fin", ultimoMes()), idTec = req.str("id_tecnico", "");
        var facturas = facturas(fi, ff, idTec, " ORDER BY fp.fecha_servicio DESC, fp.id_factura_parqueadero DESC");
        double total = 0;
        for (var f : facturas) total += Conv.toDouble(f.get("valor_factura"));
        List<Map<String, Object>> tecnicos;
        try {
            tecnicos = db.list("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC");
        } catch (Exception e) {
            tecnicos = List.of();
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Administración de Parqueaderos");
        r.put("filtros", Map.of("fecha_inicio", fi, "fecha_fin", ff, "id_tecnico", idTec));
        r.put("tecnicos", tecnicos);
        r.put("facturas", facturas);
        r.put("totalGastado", total);
        r.put("totalFacturas", facturas.size());
        return r;
    }

    private static String fmtCO(double v) {
        String s = String.format(Locale.US, "%,.2f", v);
        return s.replace(",", "#").replace(".", ",").replace("#", ".");
    }

    private static String hm(Object t) {
        String s = Conv.str(t);
        return s.length() >= 5 ? s.substring(0, 5) : s;
    }

    private String fotoBase64(Object ruta) {
        if (Conv.empty(ruta)) return "";
        try {
            Path f = archivos.resolver(Conv.str(ruta));
            if (!Files.isRegularFile(f)) return "";
            return "data:image/" + Archivos.extension(f.getFileName().toString()) + ";base64," + Base64.getEncoder().encodeToString(Files.readAllBytes(f));
        } catch (Exception e) {
            return "";
        }
    }

    @GetMapping(value = "/api/parqueaderoAdmin", params = "accion=exportarPdf")
    public ResponseEntity<?> exportarPdf(Req req) {
        String fi = req.str("fecha_inicio", primeroMes()), ff = req.str("fecha_fin", ultimoMes());
        var datos = facturas(fi, ff, req.str("id_tecnico", ""), " ORDER BY fp.fecha_servicio ASC, t.nombre_tecnico ASC");
        double total = 0;
        for (var d : datos) total += Conv.toDouble(d.get("valor_factura"));
        StringBuilder h = new StringBuilder();
        h.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><title>Reporte de Parqueaderos - Modo Horizontal</title>")
                .append("<script src=\"https://cdn.tailwindcss.com\"></script><style>@page { size: A4 landscape; margin: 8mm; } body { font-family: system-ui, -apple-system, sans-serif; } .page-break { page-break-after: always; }</style>")
                .append("</head><body class=\"bg-white p-4 text-gray-800\">")
                .append("<div class=\"border-b-2 border-blue-800 pb-3 mb-4 flex justify-between items-center\"><div><h1 class=\"text-2xl font-bold text-blue-900 leading-none\">REPORTE DE PARQUEADEROS</h1>")
                .append("<p class=\"text-xs text-gray-500 mt-1\">Auditoría y control de comprobantes de gastos</p></div><div class=\"text-right\"><span class=\"block text-[10px] text-gray-400 font-bold uppercase\">Rango de fechas</span>")
                .append("<span class=\"text-xs font-semibold text-gray-700 bg-gray-100 px-2 py-1 rounded border border-gray-200\">").append(F.dmy(fi)).append(" — ").append(F.dmy(ff)).append("</span></div></div>")
                .append("<div class=\"grid grid-cols-2 gap-4 mb-4\"><div class=\"bg-gray-50 p-2.5 rounded-lg border border-gray-200 flex justify-between items-center\"><span class=\"text-xs font-bold text-gray-600 uppercase\">Total Comprobantes Registrados:</span>")
                .append("<span class=\"text-base font-bold text-gray-900\">").append(datos.size()).append("</span></div><div class=\"bg-blue-50 p-2.5 rounded-lg border border-blue-200 flex justify-between items-center\"><span class=\"text-xs font-bold text-blue-700 uppercase\">Monto Total Acumulado:</span>")
                .append("<span class=\"text-lg font-extrabold text-blue-900\">$").append(fmtCO(total)).append("</span></div></div>")
                .append("<table class=\"w-full text-xs text-left border-collapse mb-6\"><thead><tr class=\"bg-blue-800 text-white uppercase font-bold text-[11px]\">")
                .append("<th class=\"p-2 border border-blue-800 text-center w-10\">N°</th><th class=\"p-2 border border-blue-800\">Fecha Servicio</th><th class=\"p-2 border border-blue-800\">Técnico Asignado</th>")
                .append("<th class=\"p-2 border border-blue-800\">Punto / Sede Visitada</th><th class=\"p-2 border border-blue-800 text-center\">Horario</th><th class=\"p-2 border border-blue-800 text-center\">N° Factura</th>")
                .append("<th class=\"p-2 border border-blue-800 text-right\">Valor ($)</th><th class=\"p-2 border border-blue-800 text-center w-16\">Vista Previa</th></tr></thead><tbody>");
        StringBuilder anexo = new StringBuilder();
        for (int i = 0; i < datos.size(); i++) {
            var it = datos.get(i);
            String b64 = fotoBase64(it.get("ruta_foto"));
            h.append("<tr class=\"").append(i % 2 == 0 ? "bg-white" : "bg-gray-50").append(" border-b border-gray-200\"><td class=\"p-1.5 border text-center font-bold text-gray-500\">").append(i + 1)
                    .append("</td><td class=\"p-1.5 border font-semibold\">").append(F.dmy(it.get("fecha_servicio"))).append("</td><td class=\"p-1.5 border font-semibold text-blue-900\">")
                    .append(Conv.h(it.get("nombre_tecnico"))).append("</td><td class=\"p-1.5 border\">").append(Conv.h(it.get("nombre_punto")))
                    .append("</td><td class=\"p-1.5 border text-center font-mono text-[11px]\">").append(hm(it.get("hora_inicio"))).append(" - ").append(hm(it.get("hora_fin")))
                    .append("</td><td class=\"p-1.5 border text-center font-mono font-bold\">").append(Conv.h(it.get("numero_factura")))
                    .append("</td><td class=\"p-1.5 border text-right font-bold text-green-700\">$").append(fmtCO(Conv.toDouble(it.get("valor_factura")))).append("</td><td class=\"p-1 border text-center\">")
                    .append(b64.isEmpty() ? "<span class=\"text-[9px] text-gray-400 italic\">Sin foto</span>" : "<img src=\"" + b64 + "\" class=\"w-10 h-10 object-cover rounded border border-gray-300 mx-auto\">")
                    .append("</td></tr>");
            if (!b64.isEmpty()) {
                anexo.append("<div class=\"border border-gray-300 rounded-lg p-2.5 bg-gray-50 flex flex-col items-center\"><div class=\"w-full text-[11px] font-bold text-gray-700 border-b pb-1 mb-1.5 flex justify-between\"><span>#")
                        .append(i + 1).append(" — Factura: ").append(Conv.h(it.get("numero_factura"))).append("</span><span class=\"text-blue-700\">").append(F.dmy(it.get("fecha_servicio")))
                        .append("</span></div><div class=\"text-[10px] text-gray-600 w-full mb-2 space-y-0.5\"><p class=\"truncate\"><strong>Técnico:</strong> ").append(Conv.h(it.get("nombre_tecnico")))
                        .append("</p><p class=\"truncate\"><strong>Punto:</strong> ").append(Conv.h(it.get("nombre_punto"))).append("</p><p><strong>Valor:</strong> $").append(fmtCO(Conv.toDouble(it.get("valor_factura"))))
                        .append("</p></div><div class=\"w-full h-44 flex items-center justify-center bg-white border border-gray-200 rounded p-1\"><img src=\"").append(b64)
                        .append("\" class=\"max-h-full max-w-full object-contain\"></div></div>");
            }
        }
        h.append("</tbody><tfoot><tr class=\"bg-blue-100 font-bold text-blue-900\"><td colspan=\"6\" class=\"p-2 text-right uppercase border border-blue-200\">Total General Gastado:</td>")
                .append("<td class=\"p-2 text-right text-sm border border-blue-200\">$").append(fmtCO(total)).append("</td><td class=\"border border-blue-200\"></td></tr></tfoot></table>")
                .append("<div class=\"page-break\"></div><div class=\"border-b-2 border-blue-800 pb-2 mb-4 flex justify-between items-center\"><h2 class=\"text-lg font-bold text-blue-900\">ANEXO DE COMPROBANTES Y FACTURAS</h2>")
                .append("<span class=\"text-xs text-gray-500\">Muestrario de imágenes adjuntas</span></div><div class=\"grid grid-cols-3 gap-4\">").append(anexo).append("</div></body></html>");
        try {
            byte[] b = pdf.generar(h.toString());
            return Descarga.pdf(b, "Reporte_Parqueaderos_" + fi + "_al_" + ff + ".pdf", true);
        } catch (Exception e) {
            return Descarga.html("<h1>Error generando PDF de Parqueaderos</h1><p>" + Conv.h(e.getMessage()) + "</p>");
        }
    }

    @GetMapping(value = "/api/parqueaderoAdmin", params = "accion=exportarExcel")
    public ResponseEntity<byte[]> exportarExcel(Req req) {
        String fi = req.str("fecha_inicio", primeroMes()), ff = req.str("fecha_fin", ultimoMes());
        var datos = facturas(fi, ff, req.str("id_tecnico", ""), " ORDER BY fp.fecha_servicio ASC, t.nombre_tecnico ASC");
        Excel x = new Excel("Parqueaderos");
        x.merge("A1:H1").set("A1", "REPORTE DE FACTURAS DE PARQUEADERO");
        x.estilo("A1", Excel.E.of().bold().size(14).color("FFFFFF").fill("1E3A8A").h("center"));
        x.merge("A2:H2").set("A2", "Período: " + F.dmy(fi) + " al " + F.dmy(ff));
        x.estilo("A2", Excel.E.of().italic().size(10).h("center"));
        String[] hd = { "N°", "Fecha Servicio", "Técnico", "Punto Visitado", "Horario", "N° Factura", "Valor Pagado ($)", "Foto Comprobante" };
        for (int i = 0; i < hd.length; i++) x.set(i + 1, 4, hd[i]);
        x.estilo("A4:H4", Excel.E.of().bold().color("FFFFFF").fill("2563EB").h("center"));
        int fila = 5;
        double total = 0;
        for (int i = 0; i < datos.size(); i++) {
            var it = datos.get(i);
            double valor = Conv.toDouble(it.get("valor_factura"));
            total += valor;
            x.alto(fila, 75);
            x.set("A" + fila, i + 1);
            x.setTexto("B" + fila, F.dmy(it.get("fecha_servicio")));
            x.setTexto("C" + fila, it.get("nombre_tecnico"));
            x.setTexto("D" + fila, it.get("nombre_punto"));
            x.setTexto("E" + fila, hm(it.get("hora_inicio")) + " - " + hm(it.get("hora_fin")));
            x.setTexto("F" + fila, it.get("numero_factura"));
            x.set("G" + fila, valor);
            x.estilo("G" + fila, Excel.E.of().formato("$#,##0.00"));
            x.estilo("A" + fila + ":B" + fila, Excel.E.of().center());
            x.estilo("C" + fila + ":F" + fila, Excel.E.of().v("center"));
            x.estilo("E" + fila + ":F" + fila, Excel.E.of().h("center"));
            String ruta = Conv.str(it.get("ruta_foto")).replace('\\', '/').trim();
            Path foto = null;
            if (!ruta.isEmpty() && !ruta.toLowerCase().startsWith("http")) {
                int pos = ruta.indexOf("app/uploads/");
                String limpia = pos >= 0 ? ruta.substring(pos) : ruta.replaceFirst("^/+", "");
                try {
                    Path c = archivos.resolver(limpia);
                    if (Files.isRegularFile(c) && ImageIO.read(c.toFile()) != null) foto = c;
                } catch (Exception ignored) {
                }
            }
            if (foto != null) {
                x.imagen(foto, "H" + fila, 60, 10, 5);
            } else {
                x.set("H" + fila, "Sin Foto");
                x.estilo("H" + fila, Excel.E.of().center());
            }
            fila++;
        }
        x.alto(fila, 25);
        x.merge("A" + fila + ":F" + fila).set("A" + fila, "TOTAL GASTADO:").set("G" + fila, total);
        x.estilo("A" + fila + ":H" + fila, Excel.E.of().bold().fill("E0E7FF"));
        x.estilo("A" + fila, Excel.E.of().h("right").v("center"));
        x.estilo("G" + fila, Excel.E.of().v("center").formato("$#,##0.00"));
        x.autoAncho("A", "G");
        x.ancho("H", 20);
        return Descarga.excel(x.bytes(), "Reporte_Parqueaderos_" + LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyyMMdd_HHmmss")) + ".xlsx");
    }

    @PostMapping(value = "/api/parqueaderoAdmin", params = "accion=guardarRotacion")
    public Map<String, Object> guardarRotacion(Req req) {
        String ruta = req.str("ruta_foto", "");
        int grados = req.intv("grados", 0);
        if (ruta.isEmpty() || grados == 0) return Map.of("exito", false, "mensaje", "Parámetros inválidos.");
        int pos = ruta.indexOf("app/uploads/");
        String limpia = pos >= 0 ? ruta.substring(pos) : ruta.replaceFirst("^/+", "");
        Path f;
        try {
            f = archivos.resolver(limpia);
        } catch (Exception e) {
            return Map.of("exito", false, "mensaje", "No se encontró la imagen en el servidor.");
        }
        if (!Files.isRegularFile(f)) return Map.of("exito", false, "mensaje", "No se encontró la imagen en el servidor.");
        BufferedImage img;
        try {
            img = ImageIO.read(f.toFile());
        } catch (Exception e) {
            img = null;
        }
        if (img == null) return Map.of("exito", false, "mensaje", "Formato de imagen no soportado para rotación.");
        BufferedImage rot = Archivos.rotar(img, -grados);
        String ext = Archivos.extension(f.getFileName().toString());
        try {
            if (ext.equals("png")) {
                ImageIO.write(rot, "png", f.toFile());
            } else {
                BufferedImage rgb = new BufferedImage(rot.getWidth(), rot.getHeight(), BufferedImage.TYPE_INT_RGB);
                rgb.createGraphics().drawImage(rot, 0, 0, java.awt.Color.WHITE, null);
                ImageWriter wr = ImageIO.getImageWritersByFormatName("jpg").next();
                ImageWriteParam p = wr.getDefaultWriteParam();
                p.setCompressionMode(ImageWriteParam.MODE_EXPLICIT);
                p.setCompressionQuality(0.9f);
                try (var ios = ImageIO.createImageOutputStream(f.toFile())) {
                    wr.setOutput(ios);
                    wr.write(null, new IIOImage(rgb, null, null), p);
                } finally {
                    wr.dispose();
                }
            }
            return Map.of("exito", true, "mensaje", "Rotación guardada correctamente.");
        } catch (Exception e) {
            return Map.of("exito", false, "mensaje", "No se pudo guardar la imagen rotada.");
        }
    }
}
