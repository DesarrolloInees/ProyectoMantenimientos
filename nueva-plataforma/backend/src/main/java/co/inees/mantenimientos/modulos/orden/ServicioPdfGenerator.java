package co.inees.mantenimientos.modulos.orden;

import java.nio.charset.StandardCharsets;
import java.text.Normalizer;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.core.io.ClassPathResource;
import org.springframework.stereotype.Component;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Pdf;

/**
 * Port de app/helpers/servicioPdfGenerator.php + app/views/orden/plantillaPdfServicio.php
 * + las consultas de serviciosPdfModelo.php usadas por el PDF.
 */
@Component
public class ServicioPdfGenerator {

    private static final Logger log = LoggerFactory.getLogger(ServicioPdfGenerator.class);
    private static final String PIXEL = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";

    private final Db db;
    private final Pdf pdf;
    private final Archivos archivos;
    private String css;

    public ServicioPdfGenerator(Db db, Pdf pdf, Archivos archivos) {
        this.db = db;
        this.pdf = pdf;
        this.archivos = archivos;
    }

    public record Resultado(boolean ok, String error, String nombreArchivo, byte[] contenido, Map<String, Object> datos) {
    }

    // ================================================================ CONSULTAS (serviciosPdfModelo)

    public Map<String, Object> obtenerDatosCompletosOrden(int idOrden) {
        try {
            return db.one("""
                    SELECT
                        o.id_ordenes_servicio, o.numero_remision, o.fecha_visita,
                        o.hora_entrada, o.hora_salida, o.tiempo_servicio,
                        o.actividades_realizadas as observaciones, o.tiene_novedad, o.detalle_novedad,
                        t.nombre_tecnico,
                        t.ruta_firma,
                        COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente, 'SIN CLIENTE') as nombre_cliente,
                        COALESCE(p_directo.nombre_punto, p_maq.nombre_punto, 'SIN PUNTO') as nombre_punto,
                        COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion, 'SIN ASIGNAR') as delegacion,
                        m.device_id,
                        tm.nombre_tipo_maquina,
                        tman.nombre_completo as tipo_servicio,
                        em.nombre_estado as estado_maquina,
                        cal.nombre_calificacion,
                        osc.numero_maquina,
                        osc.serial_maquina,
                        osc.serial_router,
                        osc.serial_ups,
                        osc.pendientes,
                        osc.administrador_punto,
                        osc.celular_encargado,
                        em_ini.nombre_estado as estado_inicial,
                        CASE
                            WHEN o.id_modalidad = 1 THEN 'URBANO'
                            WHEN o.id_modalidad = 2 THEN 'INTERURBANO'
                            ELSE 'NO DEFINIDO'
                        END as modalidad
                    FROM ordenes_servicio o
                    LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
                    LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
                    LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN tipo_mantenimiento tman ON o.id_tipo_mantenimiento = tman.id_tipo_mantenimiento
                    LEFT JOIN estado_maquina em ON o.id_estado_maquina = em.id_estado
                    LEFT JOIN calificacion_servicio cal ON o.id_calificacion = cal.id_calificacion
                    LEFT JOIN ordenes_servicio_complemento osc ON o.id_ordenes_servicio = osc.id_orden_servicio
                    LEFT JOIN estado_maquina em_ini ON osc.id_estado_inicial = em_ini.id_estado
                    LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
                    LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
                    LEFT JOIN delegacion d_maq ON p_maq.id_delegacion = d_maq.id_delegacion
                    LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
                    LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
                    LEFT JOIN delegacion d_directo ON p_directo.id_delegacion = d_directo.id_delegacion
                    WHERE o.id_ordenes_servicio = :id""", "id", idOrden);
        } catch (Exception e) {
            log.error("Error obteniendo datos para PDF: {}", e.getMessage());
            return null;
        }
    }

    private List<Map<String, Object>> evidencias(int idOrden) {
        try {
            return db.list("""
                    SELECT tipo_evidencia, ruta_archivo, fecha_subida
                    FROM evidencia_servicio
                    WHERE id_orden_servicio = :id
                    ORDER BY
                        FIELD(tipo_evidencia, 'antes', 'componentes', 'despues'),
                        fecha_subida ASC""", "id", idOrden);
        } catch (Exception e) {
            return List.of();
        }
    }

    private List<Map<String, Object>> novedades(int idOrden) {
        try {
            return db.list("""
                    SELECT tn.nombre_novedad
                    FROM orden_servicio_novedad osn
                    INNER JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    WHERE osn.id_orden_servicio = :id""", "id", idOrden);
        } catch (Exception e) {
            return List.of();
        }
    }

    private List<Map<String, Object>> repuestos(int idOrden) {
        try {
            return db.list("""
                    SELECT r.nombre_repuesto, osr.origen, osr.cantidad
                    FROM orden_servicio_repuesto osr
                    INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    WHERE osr.id_orden_servicio = :id""", "id", idOrden);
        } catch (Exception e) {
            return List.of();
        }
    }

    public String obtenerParametro(String clave) {
        try {
            Map<String, Object> r = db.one("SELECT valor FROM parametros WHERE clave = :c", "c", clave);
            return r == null ? null : Conv.str(r.get("valor"));
        } catch (Exception e) {
            return null;
        }
    }

    /** puedeVerOrden(): rol 4 (Prosegur) sólo ve órdenes con más de N meses. */
    public boolean puedeVerOrden(int idOrden, int idRol, int meses) {
        if (idRol != 4 || meses <= 0) return true;
        try {
            Map<String, Object> o = db.one("SELECT fecha_visita FROM ordenes_servicio WHERE id_ordenes_servicio = :id", "id", idOrden);
            if (o == null || Conv.empty(o.get("fecha_visita"))) return false;
            java.time.LocalDate fv = java.time.LocalDate.parse(Conv.str(o.get("fecha_visita")).substring(0, 10));
            return !fv.atStartOfDay().isAfter(java.time.LocalDateTime.now().minusMonths(meses));
        } catch (Exception e) {
            return false;
        }
    }

    // ================================================================ GENERACIÓN

    public Resultado generarPdf(int idOrden, Map<String, Object> datosOrden) {
        Map<String, Object> d = datosOrden != null ? datosOrden : obtenerDatosCompletosOrden(idOrden);
        if (d == null) return new Resultado(false, "La orden no existe.", null, null, null);
        try {
            String html = renderizar(d, evidencias(idOrden), novedades(idOrden), repuestos(idOrden));
            byte[] contenido = pdf.generar(html, "size: A4; margin: 10mm");
            return new Resultado(true, null, construirNombreArchivo(d, idOrden), contenido, d);
        } catch (Exception e) {
            log.error("Error generando PDF de orden {}: {}", idOrden, e.getMessage());
            return new Resultado(false, String.valueOf(e.getMessage()), null, null, null);
        }
    }

    public String construirNombreArchivo(Map<String, Object> d, Object idOrden) {
        Object rem = d.get("numero_remision") != null ? d.get("numero_remision") : idOrden;
        String punto = limpiarSegmento(Conv.str(d.get("nombre_punto")), "Sin_Punto");
        String fecha = !Conv.empty(d.get("fecha_visita")) ? Conv.str(d.get("fecha_visita")).substring(0, 10) : F.hoy();
        return String.join("_", "Servicio", limpiarSegmento(Conv.str(rem), "Sin_Remision"), punto, fecha) + ".pdf";
    }

    public String construirRutaRelativa(Map<String, Object> d, String nombreArchivo, boolean incluirDelegacion, boolean incluirTecnico) {
        StringBuilder sb = new StringBuilder();
        if (incluirDelegacion) {
            Object del = d.get("nombre_delegacion") != null ? d.get("nombre_delegacion") : d.get("delegacion");
            sb.append(limpiarSegmento(Conv.str(del), "SIN_DELEGACION")).append('/');
        }
        if (incluirTecnico) sb.append(limpiarSegmento(Conv.str(d.get("nombre_tecnico")), "SIN_TECNICO")).append('/');
        return sb.append(nombreArchivo).toString();
    }

    public static String limpiarSegmento(String texto, String fallback) {
        String t = texto == null ? "" : texto.trim();
        if (t.isEmpty()) return fallback;
        // iconv ASCII//TRANSLIT//IGNORE
        t = Normalizer.normalize(t, Normalizer.Form.NFD).replaceAll("\\p{M}", "").replaceAll("[^\\x00-\\x7F]", "");
        t = t.replaceAll("[<>:\"/\\\\|?*\\x00-\\x1F]", "_");
        t = t.replaceAll("\\s+", "_");
        t = t.replaceAll("_+", "_");
        t = trimChars(t, "._-");
        if (t.length() > 90) t = t.substring(0, 90);
        t = trimChars(t, "._-");
        return t.isEmpty() ? fallback : t;
    }

    private static String trimChars(String s, String chars) {
        int a = 0, b = s.length();
        while (a < b && chars.indexOf(s.charAt(a)) >= 0) a++;
        while (b > a && chars.indexOf(s.charAt(b - 1)) >= 0) b--;
        return s.substring(a, b);
    }

    private String css() {
        if (css == null) {
            try (var in = new ClassPathResource("plantillas/servicio-pdf.css").getInputStream()) {
                css = new String(in.readAllBytes(), StandardCharsets.UTF_8);
            } catch (Exception e) {
                css = "";
            }
        }
        return css;
    }

    /** Ruta física de la evidencia: app/ + (desde 'uploads/') */
    private String rutaLimpia(String rutaBD) {
        String r = rutaBD == null ? "" : rutaBD;
        int pos = r.indexOf("uploads/");
        return pos >= 0 ? r.substring(pos) : r.replaceAll("^/+", "");
    }

    private static String o(Object v, String def) {
        String s = Conv.str(v);
        return s.isEmpty() || "0".equals(s) ? def : Conv.h(s);
    }

    private static String h(Object v) {
        return Conv.h(Conv.str(v));
    }

    private String renderizar(Map<String, Object> d, List<Map<String, Object>> evidencias, List<Map<String, Object>> novedades,
            List<Map<String, Object>> repuestos) {
        String fecha = F.dmy(d.get("fecha_visita"));
        String rem = h(d.get("numero_remision"));
        StringBuilder s = new StringBuilder(40_000);
        s.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><title>Reporte de Servicio #").append(rem).append("</title>")
                .append("<link href=\"https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=IBM+Plex+Mono:wght@400;600&display=swap\" rel=\"stylesheet\">")
                .append("<style>").append(css()).append("</style></head><body><div class=\"page\">");

        // HEADER
        s.append("<div class=\"header\"><div class=\"header-marca\"><img src=\"").append(pdf.dataUri("app/logos/logoTransBank.jpg"))
                .append("\" alt=\"Logo-Inees\"><span class=\"subtitulo\">Reporte Técnico de Servicio</span></div>")
                .append("<div class=\"header-remision\"><div class=\"num\">Remisión <span>#").append(rem).append("</span></div>")
                .append("<div class=\"fecha\">Fecha: ").append(fecha).append("</div></div></div><div class=\"divider\"></div>");

        // CLIENTE + EJECUCIÓN
        s.append("<div class=\"grid-2 no-break\"><div class=\"card\"><div class=\"card-label\">Datos del Cliente</div>")
                .append("<div class=\"card-nombre\">").append(h(d.get("nombre_cliente"))).append("</div>")
                .append("<div class=\"card-sub\">📍 ").append(h(d.get("nombre_punto"))).append("</div>")
                .append("<div class=\"card-sub\">Delegación: <strong>").append(h(d.get("delegacion"))).append("</strong></div>")
                .append("<hr class=\"card-divider\"><div class=\"card-mini-label\">Contacto en Sitio</div><div class=\"card-sub\"><strong>")
                .append(o(d.get("administrador_punto"), "No registrado")).append("</strong> &nbsp;☎️ ").append(o(d.get("celular_encargado"), "N/A"))
                .append("</div></div>");
        s.append("<div class=\"card azul\"><div class=\"card-label\">Ejecución del Servicio</div><div class=\"card-mini-label\">Técnico Asignado</div>")
                .append("<div class=\"tecnico-nombre\">").append(h(d.get("nombre_tecnico"))).append("</div>")
                .append("<div class=\"horas-row\" style=\"margin-bottom:10px;\">")
                .append("<div class=\"hora-box\"><div class=\"h-label\">Entrada</div><div class=\"h-valor\">").append(h(d.get("hora_entrada"))).append("</div></div>")
                .append("<div class=\"hora-box\"><div class=\"h-label\">Salida</div><div class=\"h-valor\">").append(h(d.get("hora_salida"))).append("</div></div>")
                .append("<div class=\"hora-box\"><div class=\"h-label\">Duración</div><div class=\"h-valor\">").append(h(d.get("tiempo_servicio"))).append("h</div></div>")
                .append("</div><hr class=\"card-divider\"><div class=\"card-mini-label\">Calificación del Cliente</div>")
                .append("<span class=\"calificacion-pill\">").append(o(d.get("nombre_calificacion"), "Pendiente")).append("</span></div></div>");

        // EQUIPOS
        s.append("<div class=\"equipos-grid no-break\"><div class=\"equipos-header\"><h3>Detalles de Equipos e Inventario</h3>")
                .append("<span class=\"badge-tipo\">").append(h(d.get("tipo_servicio"))).append("</span></div><div class=\"equipos-body\">");
        String[][] celdas = { { "Device ID", "device_id", "N/A" }, { "N° Máquina", "numero_maquina", "N/A" }, { "Tipo Máquina", "nombre_tipo_maquina", "N/E" },
                { "Serial Máquina", "serial_maquina", "N/A" }, { "Serial Router", "serial_router", "N/A" }, { "Serial UPS", "serial_ups", "N/A" } };
        for (int i = 0; i < celdas.length; i++) {
            s.append("<div class=\"equipo-cell\"").append(i == 5 ? " style=\"border-right:none;\"" : "").append("><div class=\"ec-label\">").append(celdas[i][0])
                    .append("</div><div class=\"ec-valor\">").append(o(d.get(celdas[i][1]), celdas[i][2])).append("</div></div>");
        }
        s.append("</div></div>");

        // DIAGNÓSTICO
        s.append("<div class=\"diagnostico-box no-break\"><div class=\"diag-header\"><h3>Diagnóstico y Mantenimiento</h3></div><div class=\"estado-grid\">")
                .append("<div class=\"estado-cell\"><div class=\"estado-label\">Estado Inicial (Diagnóstico)</div><div class=\"estado-valor\">")
                .append(o(d.get("estado_inicial"), "No registrado")).append("</div></div>")
                .append("<div class=\"estado-cell\"><div class=\"estado-label\">Estado Final de Operación</div><div class=\"estado-valor verde\">")
                .append(h(d.get("estado_maquina"))).append("</div></div></div>")
                .append("<div class=\"actividades-box\"><div class=\"act-label\">Actividades Realizadas</div><p>")
                .append(Conv.empty(d.get("observaciones")) ? "Sin observaciones registradas." : h(d.get("observaciones"))).append("</p></div>");
        if (!Conv.empty(d.get("pendientes"))) {
            s.append("<div class=\"pendientes-box\"><div class=\"pend-label\">⚠ Pendientes / Recomendaciones</div><p>").append(h(d.get("pendientes"))).append("</p></div>");
        }
        if (!novedades.isEmpty() || !Conv.empty(d.get("detalle_novedad"))) {
            s.append("<div class=\"novedades-box\"><div class=\"nov-label\">🔴 Novedades Reportadas</div>");
            if (!novedades.isEmpty()) {
                s.append("<ul>");
                novedades.forEach(n -> s.append("<li>").append(h(n.get("nombre_novedad"))).append("</li>"));
                s.append("</ul>");
            }
            if (!Conv.empty(d.get("detalle_novedad"))) s.append("<div class=\"nov-detalle\"><strong>Detalle:</strong> ").append(h(d.get("detalle_novedad"))).append("</div>");
            s.append("</div>");
        }
        if (!repuestos.isEmpty()) {
            s.append("<div class=\"repuestos-box no-break\"><div class=\"repuestos-header\"><h3>⚙️ Repuestos Utilizados</h3></div><table class=\"repuestos-table\"><thead><tr>")
                    .append("<th>Repuesto</th><th style=\"text-align: center;\">Cantidad</th><th style=\"text-align: center;\">Origen</th></tr></thead><tbody>");
            for (var r : repuestos) {
                String origen = Conv.str(r.get("origen"));
                s.append("<tr><td>").append(h(r.get("nombre_repuesto"))).append("</td><td style=\"text-align: center; font-family: 'IBM Plex Mono', monospace;\">")
                        .append(h(r.get("cantidad"))).append("</td><td style=\"text-align: center;\"><span class=\"badge-origen ")
                        .append("INEES".equalsIgnoreCase(origen) ? "origen-inees" : "origen-prosegur").append("\">").append(Conv.h(origen)).append("</span></td></tr>");
            }
            s.append("</tbody></table></div>");
        }
        s.append("</div>");

        // FIRMAS
        String firmaSrc = null;
        for (var e : evidencias) {
            if ("firma".equals(e.get("tipo_evidencia"))) {
                String u = pdf.dataUri("app/" + rutaLimpia(Conv.str(e.get("ruta_archivo"))));
                firmaSrc = u.isEmpty() ? null : u;
                break;
            }
        }
        String firmaTec = null;
        if (!Conv.empty(d.get("ruta_firma"))) {
            String u = pdf.dataUri("app/" + rutaLimpia(Conv.str(d.get("ruta_firma"))));
            firmaTec = u.isEmpty() ? null : u;
        }
        s.append("<div class=\"no-break\"><div class=\"firmas\"><div class=\"firma-box\"><div class=\"firma-espacio\">");
        if (firmaTec != null) s.append("<img src=\"").append(firmaTec).append("\" alt=\"Firma Técnico\" class=\"img-firma\">");
        s.append("</div><div class=\"firma-linea\"></div><div class=\"firma-nombre\">").append(h(d.get("nombre_tecnico")))
                .append("</div><div class=\"firma-rol\">Técnico de Servicio</div></div><div class=\"firma-box\"><div class=\"firma-espacio\">");
        if (firmaSrc != null) s.append("<img src=\"").append(firmaSrc).append("\" alt=\"Firma Cliente\" class=\"img-firma\">");
        s.append("</div><div class=\"firma-linea\"></div><div class=\"firma-nombre\">").append(o(d.get("administrador_punto"), "Responsable en Sitio"))
                .append("</div><div class=\"firma-rol\">Recibido y Aprobado</div></div></div>")
                .append("<div class=\"footer-strip\"><span>INEES — Reporte Técnico de Servicio</span><span class=\"remision-footer\">Remisión #").append(rem)
                .append("</span><span>").append(fecha).append("</span></div></div>");

        // EVIDENCIA
        if (!evidencias.isEmpty()) {
            s.append("<div class=\"evidencia-header\" style=\"margin-top: 30px;\"><h2>Evidencia Fotográfica</h2><div class=\"ev-line\"></div></div>");
            Map<String, String> grupos = new LinkedHashMap<>();
            grupos.put("antes", "Antes del Servicio");
            grupos.put("remision", "Remisión");
            grupos.put("despues", "Después del Servicio");
            for (var g : grupos.entrySet()) {
                List<Map<String, Object>> fotos = evidencias.stream().filter(e -> g.getKey().equals(e.get("tipo_evidencia"))).toList();
                if (fotos.isEmpty()) continue;
                s.append("<div class=\"foto-grupo \"><div class=\"foto-grupo-title\">").append(g.getValue()).append("</div><div class=\"foto-grid\">");
                for (var f : fotos) {
                    String rl = rutaLimpia(Conv.str(f.get("ruta_archivo")));
                    String src = pdf.dataUri("app/" + rl);
                    boolean falta = src.isEmpty();
                    s.append("<div class=\"foto-item\"><img src=\"").append(falta ? PIXEL : src).append("\" alt=\"Evidencia\">");
                    if (falta) s.append("<div class=\"foto-error\">Error: ").append(Conv.h(archivos.raiz().resolve("app").toString().replace('\\', '/') + "/" + rl)).append("</div>");
                    s.append("</div>");
                }
                s.append("</div></div>");
            }
        } else {
            s.append("<div class=\"sin-evidencia\"><p>📷 No se adjuntaron evidencias fotográficas para este servicio.</p></div>");
        }
        s.append("</div></body></html>");
        return s.toString();
    }
}
