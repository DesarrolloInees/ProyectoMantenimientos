package co.inees.mantenimientos.modulos.turno;

import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Excel;
import co.inees.mantenimientos.core.Excel.E;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Pdf;
import co.inees.mantenimientos.core.Req;

/**
 * Port de turnoControlador, turnoReportesControlador, turnoExcelControlador, turnoPdfControlador.
 */
@RestController
@RequestMapping("/api")
public class TurnoController {

    private static final Pattern P_HORA = Pattern.compile("^(\\d{1,2}):(\\d{2})(?::(\\d{2}))?$");

    private final TurnoModelo modelo;
    private final Archivos archivos;
    private final Pdf pdf;

    public TurnoController(TurnoModelo modelo, Archivos archivos, Pdf pdf) {
        this.modelo = modelo;
        this.archivos = archivos;
        this.pdf = pdf;
    }

    // ================================================================ turno

    @PostMapping(value = "/turno", params = "accion=guardar")
    public Map<String, Object> guardar(Req req) {
        Map<String, Object> tec = tecnicoDeLaSesion(req);
        if (tec.containsKey("ok")) return tec;

        String fecha = req.trim("fecha");
        if (!F.esYmd(fecha)) return Map.of("ok", false, "msj", "La fecha no es válida.");
        if (fecha.compareTo(F.hoy()) > 0) return Map.of("ok", false, "msj", "No puedes registrar una fecha futura.");

        String horaEntrada = normalizarHora(req.str("hora_entrada"));
        if (horaEntrada == null) return Map.of("ok", false, "msj", "La hora de entrada no es válida (usa el formato HH:MM).");

        String novedad = req.trim("novedad");
        if (novedad.codePointCount(0, novedad.length()) > 1000) {
            novedad = novedad.substring(0, novedad.offsetByCodePoints(0, 1000));
        }
        Double latitud = normalizarCoordenada(req.get("latitud"), 90);
        Double longitud = normalizarCoordenada(req.get("longitud"), 180);

        int idTecnico = Conv.toInt(tec.get("id_tecnico"));
        boolean existia = modelo.obtenerTurnoPorDia(idTecnico, fecha) != null;

        Map<String, Object> d = new LinkedHashMap<>();
        d.put("id_tecnico", idTecnico);
        d.put("fecha", fecha);
        d.put("hora_entrada", horaEntrada);
        d.put("novedad", novedad.isEmpty() ? null : novedad);
        d.put("latitud", latitud);
        d.put("longitud", longitud);
        if (!modelo.guardarEntrada(d)) {
            return Map.of("ok", false, "msj", "No se pudo guardar la marcación. Intenta de nuevo.");
        }
        Map<String, Object> turno = modelo.obtenerTurnoPorDia(idTecnico, fecha);
        String fechaTexto = F.dmy(fecha);
        String horaTexto = horaEntrada.substring(0, 5);
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("ok", true);
        r.put("msj", existia
                ? "Se actualizó tu entrada del " + fechaTexto + " a las " + horaTexto + "."
                : "¡Entrada registrada! " + fechaTexto + " a las " + horaTexto + ".");
        r.put("turno", turno != null ? formatearTurno(turno) : null);
        return r;
    }

    @GetMapping(value = "/turno", params = "accion=estadoHoy")
    public Map<String, Object> estadoHoy(Req req) {
        Map<String, Object> tec = tecnicoDeLaSesion(req);
        if (tec.containsKey("ok")) return tec;
        String fecha = req.trim("fecha");
        if (fecha.isEmpty() || !fecha.matches("^\\d{4}-\\d{2}-\\d{2}$")) fecha = F.hoy();
        Map<String, Object> turno = modelo.obtenerTurnoPorDia(Conv.toInt(tec.get("id_tecnico")), fecha);
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("ok", true);
        r.put("turno", turno != null ? formatearTurno(turno) : null);
        return r;
    }

    private Map<String, Object> tecnicoDeLaSesion(Req req) {
        int idUsuario = req.sesion().usuarioId();
        if (idUsuario == 0) return Map.of("ok", false, "msj", "Tu sesión expiró. Vuelve a iniciar sesión.");
        Map<String, Object> t = modelo.obtenerDatosTecnicoPorUsuario(idUsuario);
        if (t == null || Conv.empty(t.get("id_tecnico"))) {
            return Map.of("ok", false, "msj", "Tu usuario no está vinculado a un perfil de técnico.");
        }
        return t;
    }

    static String normalizarHora(String valor) {
        valor = valor == null ? "" : valor.trim();
        if (valor.isEmpty()) return null;
        Matcher m = P_HORA.matcher(valor);
        if (!m.matches()) return null;
        int h = Integer.parseInt(m.group(1));
        int i = Integer.parseInt(m.group(2));
        int s = m.group(3) != null ? Integer.parseInt(m.group(3)) : 0;
        if (h > 23 || i > 59 || s > 59) return null;
        return String.format("%02d:%02d:%02d", h, i, s);
    }

    static Double normalizarCoordenada(Object valor, double limite) {
        if (valor == null) return null;
        String s = String.valueOf(valor).trim();
        if (s.isEmpty()) return null;
        try {
            double n = Double.parseDouble(s);
            if (n < -limite || n > limite) return null;
            return n;
        } catch (NumberFormatException e) {
            return null;
        }
    }

    private Map<String, Object> formatearTurno(Map<String, Object> t) {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("id_turno", Conv.toInt(t.get("id_turno")));
        r.put("fecha", t.get("fecha"));
        r.put("fecha_texto", F.dmy(t.get("fecha")));
        r.put("hora_entrada", Conv.str(t.get("hora_entrada")).length() >= 5 ? Conv.str(t.get("hora_entrada")).substring(0, 5) : Conv.str(t.get("hora_entrada")));
        r.put("novedad", Conv.str(t.get("novedad")));
        r.put("latitud", t.get("latitud"));
        r.put("longitud", t.get("longitud"));
        return r;
    }

    // ================================================================ turnoReportes

    @GetMapping(value = "/turnoReportes", params = "accion=index")
    public Map<String, Object> reportes(Req req) {
        String fi = F.fechaValida(req.str("fecha_inicio"), F.primeroMes());
        String ff = F.fechaValida(req.str("fecha_fin"), F.hoy());
        Integer idTecnico = Conv.empty(req.get("id_tecnico")) ? null : req.intv("id_tecnico");
        if (fi.compareTo(ff) > 0) {
            String tmp = fi;
            fi = ff;
            ff = tmp;
        }
        List<Map<String, Object>> registros = modelo.obtenerTurnos(fi, ff, idTecnico);
        int novedades = 0, ubicacion = 0;
        Set<Object> tecs = new HashSet<>();
        for (Map<String, Object> r : registros) {
            if (!Conv.empty(Conv.str(r.get("novedad")).trim())) novedades++;
            if (!Conv.empty(r.get("latitud")) && !Conv.empty(r.get("longitud"))) ubicacion++;
            tecs.add(r.get("nombre_tecnico"));
        }
        Map<String, Object> totales = new LinkedHashMap<>();
        totales.put("registros", registros.size());
        totales.put("novedades", novedades);
        totales.put("ubicacion", ubicacion);
        totales.put("tecnicos", tecs.size());

        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Reporte de Turnos");
        r.put("fechaInicio", fi);
        r.put("fechaFin", ff);
        r.put("idTecnico", idTecnico);
        r.put("tecnicos", modelo.obtenerTecnicosActivos());
        r.put("registros", registros);
        r.put("totales", totales);
        return r;
    }

    // ================================================================ turnoExcel

    @GetMapping(value = "/turnoExcel", params = "accion=generar")
    public ResponseEntity<byte[]> excel(Req req) {
        String fi = req.str("fecha_inicio", F.primeroMes());
        String ff = req.str("fecha_fin", F.hoy());
        Integer idTecnico = Conv.empty(req.get("id_tecnico")) ? null : req.intv("id_tecnico");
        List<Map<String, Object>> registros = modelo.obtenerTurnos(fi, ff, idTecnico);
        String periodo = F.dmy(fi) + " AL " + F.dmy(ff);

        try (Excel x = new Excel("TURNOS")) {
            String[][] anchos = {{"A", "24"}, {"B", "12"}, {"C", "12"}, {"D", "52"}, {"E", "20"}, {"F", "13"}, {"G", "13"}};
            for (String[] a : anchos) x.ancho(a[0], Double.parseDouble(a[1]));
            E negrita = E.of().bold();

            x.merge("A1:B1").merge("C1:G1").alto(1, 45);
            x.set("C1", "COORDINACIÓN DE OPERACIONES TÉCNICAS\nFORMATO DE REGISTRO DE ENTRADA DE TURNOS - MOTORIZADOS");
            x.estilo("C1", E.of().wrap().center().bold().size(11));
            x.imagen(archivos.resolver("app/logos/logoInees.jpg"), "A1", 50, 5, 5);

            x.merge("B2:C2");
            x.set("A2", "PERIODO:").set("B2", periodo);
            x.estilo("A2", E.of().bold().fill("FFF1F5F9"));
            x.estilo("B2", negrita);

            x.merge("A3:G3");
            x.set("A3", "NOTA: LA \"HORA ENTRADA\" ES LA REPORTADA POR EL TÉCNICO. LA COLUMNA \"REGISTRADO EL\" ES LA FECHA Y HORA REAL DEL SISTEMA CUANDO SE GUARDÓ LA MARCACIÓN.");
            x.estilo("A3", E.of().italic().size(9).wrap().v("center"));
            x.alto(3, 26);

            String[] enc = {"TÉCNICO", "FECHA", "HORA ENTRADA", "NOVEDAD", "REGISTRADO EL", "LATITUD", "LONGITUD"};
            for (int i = 0; i < enc.length; i++) x.set(Excel.col(i + 1) + "4", enc[i]);
            x.alto(4, 22);
            x.estilo("A4:G4", E.of().bold().center().fill("FFDBEAFE").wrap());

            int fila = 5;
            for (Map<String, Object> r : registros) {
                x.set("A" + fila, r.get("nombre_tecnico"));
                x.set("B" + fila, F.dmy(r.get("fecha")));
                x.set("C" + fila, F.hm(r.get("hora_entrada")));
                x.set("D" + fila, Conv.str(r.get("novedad")));
                x.set("E" + fila, F.fmt(r.get("fecha_registro"), "dd/MM/yyyy HH:mm"));
                x.set("F" + fila, r.get("latitud") != null ? (Object) Conv.toDouble(r.get("latitud")) : "");
                x.set("G" + fila, r.get("longitud") != null ? (Object) Conv.toDouble(r.get("longitud")) : "");
                x.estilo("D" + fila, E.of().wrap().v("center"));
                fila++;
            }
            int finDatos = fila - 1;
            if (finDatos >= 5) {
                x.estilo("A4:G" + finDatos, E.of().borde().bordeColor("FF000000"));
                x.estilo("A5:C" + finDatos, E.of().center());
                x.estilo("E5:G" + finDatos, E.of().center());
            } else {
                finDatos = 4;
            }
            int filaTotal = finDatos + 1;
            x.merge("A" + filaTotal + ":C" + filaTotal);
            x.set("A" + filaTotal, "TOTAL REGISTROS: " + registros.size());
            x.estilo("A" + filaTotal, E.of().bold().fill("FFF1F5F9"));
            x.estilo("A" + filaTotal + ":G" + filaTotal, E.of().borde().bordeColor("FF000000"));

            int ff2 = filaTotal + 3;
            x.merge("A" + ff2 + ":C" + ff2).merge("E" + ff2 + ":G" + ff2);
            x.set("A" + ff2, "_____________________________").set("E" + ff2, "_____________________________");
            x.set("A" + (ff2 + 1), "FIRMA SUPERVISOR").set("E" + (ff2 + 1), "FIRMA COORDINACIÓN");
            x.estilo("A" + ff2 + ":G" + (ff2 + 1), E.of().center());
            x.estilo("A" + (ff2 + 1), E.of().bold().size(9));
            x.estilo("E" + (ff2 + 1), E.of().bold().size(9));
            return Descarga.excel(x.bytes(), "Reporte_Turnos_" + fi + "_al_" + ff + ".xlsx");
        }
    }

    // ================================================================ turnoPdf

    @GetMapping(value = "/turnoPdf", params = "accion=generar")
    public ResponseEntity<byte[]> pdf(Req req) {
        String fi = req.str("fecha_inicio", F.primeroMes());
        String ff = req.str("fecha_fin", F.hoy());
        Integer idTecnico = Conv.empty(req.get("id_tecnico")) ? null : req.intv("id_tecnico");
        List<Map<String, Object>> registros = modelo.obtenerTurnos(fi, ff, idTecnico);
        String periodo = F.dmy(fi) + " AL " + F.dmy(ff);
        String logo = pdf.dataUri("app/logos/logoInees.jpg");

        StringBuilder h = new StringBuilder();
        h.append("""
                <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Reporte de Turnos</title>
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
                <style>
                *{margin:0;padding:0;box-sizing:border-box}
                body{font-family:'Inter','Segoe UI',Arial,sans-serif;font-size:11px;color:#1f2937}
                .header{width:100%;border-bottom:2px solid #1e3a8a;padding-bottom:8px;margin-bottom:10px}
                .header td{vertical-align:middle}.logo{height:48px}.titulo{text-align:right}
                .titulo h1{font-size:16px;color:#1e3a8a;text-transform:uppercase}
                .titulo p{font-size:10px;color:#64748b;margin-top:2px}
                .info-periodo{background-color:#f1f5f9;border:1px solid #cbd5e1;padding:6px 8px;margin-bottom:8px}
                .info-periodo strong{color:#1e3a8a}
                table.datos{width:100%;border-collapse:collapse}
                table.datos th{background-color:#1e3a8a;color:#fff;font-size:9px;text-transform:uppercase;padding:5px 4px;border:1px solid #1e3a8a}
                table.datos td{border:1px solid #cbd5e1;padding:5px 4px;font-size:10px;vertical-align:top}
                table.datos tr:nth-child(even) td{background-color:#f8fafc}
                .col-centro{text-align:center}
                .fila-total td{background-color:#e2e8f0 !important;font-weight:bold}
                .sin-datos{border:1px solid #cbd5e1;padding:18px;text-align:center;color:#64748b}
                .firmas{width:100%;margin-top:40px}
                .firmas td{text-align:center;padding-top:22px;border-top:1px solid #1f2937;font-size:10px;font-weight:bold}
                .firmas .nombre{display:block;margin-top:4px;font-weight:normal;font-size:9px;color:#64748b}
                .pie{margin-top:14px;font-size:8px;color:#94a3b8;text-align:center}
                </style></head><body>
                <table class="header"><tr><td style="width:30%;">""");
        h.append(logo.isEmpty() ? "<strong style=\"color:#1e3a8a;\">INEES</strong>" : "<img src=\"" + logo + "\" class=\"logo\" alt=\"INEES\">");
        h.append("""
                </td><td class="titulo" style="width:70%;"><h1>Registro de entrada de turnos</h1>
                <p>Coordinación de Operaciones Técnicas - Motorizados</p></td></tr></table>""");
        h.append("<div class=\"info-periodo\"><strong>PERIODO:</strong> ").append(Conv.h(periodo))
                .append(" &nbsp;|&nbsp; <strong>TOTAL REGISTROS:</strong> ").append(registros.size())
                .append(" &nbsp;|&nbsp; <strong>GENERADO:</strong> ").append(F.fmt(F.ahora(), "dd/MM/yyyy HH:mm")).append("</div>");
        if (registros.isEmpty()) {
            h.append("<div class=\"sin-datos\">No hay marcaciones de entrada registradas en el periodo seleccionado.</div>");
        } else {
            h.append("""
                    <table class="datos"><thead><tr>
                    <th style="width:20%;">Técnico</th><th style="width:10%;">Fecha</th><th style="width:10%;">Hora entrada</th>
                    <th style="width:30%;">Novedad</th><th style="width:16%;">Registrado el</th><th style="width:14%;">Ubicación</th>
                    </tr></thead><tbody>""");
            for (Map<String, Object> r : registros) {
                boolean ub = r.get("latitud") != null && r.get("longitud") != null;
                String ubic = ub ? Conv.numberFormat(r.get("latitud"), 5, ".", ",") + ", " + Conv.numberFormat(r.get("longitud"), 5, ".", ",") : "Sin ubicación";
                String nov = Conv.str(r.get("novedad"));
                h.append("<tr><td>").append(Conv.h(r.get("nombre_tecnico"))).append("</td>")
                        .append("<td class=\"col-centro\">").append(F.dmy(r.get("fecha"))).append("</td>")
                        .append("<td class=\"col-centro\"><strong>").append(F.hm(r.get("hora_entrada"))).append("</strong></td>")
                        .append("<td>").append(r.get("novedad") != null && !nov.isEmpty()
                                ? Conv.h(nov).replace("\n", "<br />\n") : "<span style=\"color:#94a3b8;\">Sin novedad</span>").append("</td>")
                        .append("<td class=\"col-centro\">").append(F.fmt(r.get("fecha_registro"), "dd/MM/yyyy HH:mm")).append("</td>")
                        .append("<td class=\"col-centro\" style=\"font-size:8px;\">").append(Conv.h(ubic)).append("</td></tr>");
            }
            h.append("</tbody><tfoot><tr class=\"fila-total\"><td colspan=\"6\">TOTAL DE REGISTROS: ")
                    .append(registros.size()).append("</td></tr></tfoot></table>");
        }
        h.append("""
                <table class="firmas"><tr>
                <td style="width:45%;">FIRMA SUPERVISOR<span class="nombre">Nombre y documento</span></td>
                <td style="width:10%;"></td>
                <td style="width:45%;">FIRMA COORDINACIÓN<span class="nombre">Nombre y documento</span></td>
                </tr></table>
                <p class="pie">"Hora entrada" es la reportada por el técnico. "Registrado el" es la fecha y hora real del sistema al guardar la marcación.</p>
                </body></html>""");
        byte[] out = pdf.generar(h.toString(), "size: A4; margin: 10mm");
        return Descarga.pdf(out, "Reporte_Turnos_" + fi + "_al_" + ff + ".pdf", true);
    }
}
