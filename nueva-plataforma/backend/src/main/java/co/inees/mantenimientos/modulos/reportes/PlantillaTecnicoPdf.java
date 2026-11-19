package co.inees.mantenimientos.modulos.reportes;

import java.util.List;
import java.util.Locale;
import java.util.Map;

import co.inees.mantenimientos.core.Conv;

/** Port de app/views/reportes/reporteTecnicoGenerar.php (A4 horizontal, escala 0.8, pie "Documento Confidencial"). */
final class PlantillaTecnicoPdf {

    private PlantillaTecnicoPdf() {
    }

    private static String h(Object o) {
        return Conv.h(Conv.str(o));
    }

    private static String nf(Object n) {
        return String.format(Locale.US, "%,d", Conv.toLong(n));
    }

    private static String hm(int min) {
        return (min / 60) + "h " + String.format("%02d", min % 60);
    }

    /** round() de PHP con 1 decimal, mostrado como PHP (sin ".0") */
    private static String pct1(double v) {
        double r = Math.round(v * 10) / 10.0;
        return r == Math.floor(r) ? String.valueOf((long) r) : String.valueOf(r);
    }

    private static String num(double v) {
        return v == Math.floor(v) ? String.valueOf((long) v) : String.valueOf(v);
    }

    @SuppressWarnings("unchecked")
    static String render(List<Map<String, Object>> dias, String logo, String etiquetaPeriodo) {
        String pie = Conv.h(etiquetaPeriodo).replace("\"", "'");
        StringBuilder s = new StringBuilder(80_000);
        s.append("""
                <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Reporte de Servicios por Técnico</title>
                <script src="https://cdn.tailwindcss.com"></script>
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
                <style>
                body { font-family: 'Inter', sans-serif; background-color: #ffffff; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; zoom: 0.8; }
                @page {
                  size: A4 landscape; margin: 10mm 10mm 15mm 10mm;
                  @bottom-left { content: "DOCUMENTO CONFIDENCIAL"; font-family: sans-serif; font-size: 9px; font-weight: bold; letter-spacing: 2px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
                  @bottom-center { content: "PIE_PERIODO"; font-family: sans-serif; font-size: 9px; font-weight: bold; color: #64748b; border-top: 1px solid #e2e8f0; }
                  @bottom-right { content: "Página " counter(page); font-family: sans-serif; font-size: 9px; font-weight: bold; color: #475569; border-top: 1px solid #e2e8f0; }
                }
                thead { display: table-header-group; }
                tr, .no-corte, .bloque { page-break-inside: avoid; break-inside: avoid; }
                table { border-collapse: collapse; }
                .dia-seccion:last-child { page-break-after: auto; break-after: auto; }
                </style></head><body class="text-slate-800">
                """.replace("PIE_PERIODO", pie));

        String generado = ReporteTecnicoController.ahora();
        for (int idx = 0; idx < dias.size(); idx++) {
            Map<String, Object> d = dias.get(idx);
            boolean sinMeta = Boolean.TRUE.equals(d.get("esNoLaboral"));
            Map<String, Map<String, Object>> rend = (Map<String, Map<String, Object>>) d.get("rendimiento");
            Map<String, Object> rt = (Map<String, Object>) d.get("resumenTipos");
            int metaTotal = rend.values().stream().mapToInt(r -> Conv.toInt(r.get("meta"))).sum();

            s.append("<div class=\"bloque dia-seccion relative border-t-[10px] border-blue-600 rounded-xl overflow-hidden shadow-sm border border-slate-200\" style=\"")
                    .append(idx > 0 ? "page-break-before: always;" : "").append("\">")
                    .append("<div class=\"absolute inset-y-0 right-0 w-1/3 bg-gradient-to-l from-blue-50 to-transparent\"></div>")
                    .append("<div class=\"relative flex items-center justify-between px-8 py-6\"><div class=\"flex items-center gap-6\">");
            if (!logo.isEmpty()) s.append("<img src=\"").append(logo).append("\" class=\"h-20 w-auto object-contain\" alt=\"Logo Empresa\">");
            s.append("<div><div class=\"text-[9px] font-black uppercase tracking-[4px] text-blue-600 mb-1\">Reporte Diario</div>")
                    .append("<h1 class=\"text-3xl font-black text-slate-800 leading-none tracking-tight\">SERVICIOS DEL DÍA <span class=\"text-blue-600\">POR TÉCNICO</span></h1>")
                    .append("<p class=\"text-[11px] text-slate-500 mt-2 font-medium\">").append(h(d.get("diaSemana")));
            if (Boolean.TRUE.equals(d.get("esFestivo"))) s.append(" <span class=\"ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 text-[9px] font-black uppercase\">Festivo</span>");
            if (Boolean.TRUE.equals(d.get("esDomingo"))) s.append(" <span class=\"ml-1 px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 text-[9px] font-black uppercase\">No laborable</span>");
            else if (Boolean.TRUE.equals(d.get("esSabado"))) s.append(" <span class=\"ml-1 px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[9px] font-black uppercase\">Jornada de sábado</span>");
            s.append("</p>");
            if (!sinMeta) {
                s.append("<div class=\"mt-2 flex items-center gap-2 flex-wrap\"><span class=\"px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 text-[9px] font-bold uppercase tracking-wide\">")
                        .append(d.get("metaVerde")).append(" por técnico · Nueva Arq. ").append(d.get("metaNuevaArquitectura")).append("</span></div>");
            }
            s.append("</div></div><div class=\"text-right\"><div class=\"inline-block bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow\">")
                    .append("<div class=\"text-[9px] uppercase tracking-widest opacity-80 leading-none mb-1\">Fecha</div><div class=\"text-base font-black leading-none\">")
                    .append(h(d.get("rangoInicio"))).append("</div></div><div class=\"mt-3 text-[10px] text-slate-400 font-medium leading-tight\">Generado el ").append(generado)
                    .append("<br>Documento confidencial</div></div></div></div>");

            // KPIs
            int totalHoras = Conv.toInt(d.get("totalHorasDia"));
            String horasTxt = (totalHoras / 60) + "h " + String.format("%02d", totalHoras % 60) + "m";
            String metaTxt = sinMeta ? "N/A" : String.valueOf(d.get("metaVerde"));
            if (!sinMeta && Boolean.TRUE.equals(d.get("hayNuevaArquitectura"))) metaTxt = d.get("metaVerde") + " / " + d.get("metaNuevaArquitectura") + " Nueva Arq.";
            int totalServ = Conv.toInt(rt.get("total"));
            int sinFall = rend.values().stream().mapToInt(r -> Conv.toInt(r.get("sin_fallidos"))).sum();
            int pctCumpl = metaTotal > 0 ? (int) Math.round((sinFall / (double) metaTotal) * 100) : 0;
            String pctColor = metaTotal <= 0 ? "text-slate-400" : pctCumpl >= 100 ? "text-emerald-700" : pctCumpl >= 66 ? "text-amber-700" : "text-rose-700";
            List<String> tecOrd = (List<String>) d.get("tecnicosOrdenados");
            String[][] kpis = { { "Meta por técnico", metaTxt, "emerald-700", "emerald-50", "emerald-200" },
                    { "Técnicos en ruta", nf(tecOrd.size()), "blue-700", "blue-50", "blue-200" }, { "Horas laboradas", horasTxt, "violet-700", "violet-50", "violet-200" },
                    { "Servicios fallidos", nf(rt.get("fallido")), "rose-700", "rose-50", "rose-200" } };
            s.append("<div class=\"grid grid-cols-5 gap-3 mt-5 bloque\"><div class=\"border rounded-xl p-3.5 border-slate-200 bg-slate-50\">")
                    .append("<div class=\"text-[8.5px] uppercase font-black tracking-wide text-slate-400 leading-none mb-1.5\">Servicios del día</div>")
                    .append("<div class=\"flex items-end justify-between gap-2\"><div class=\"text-2xl font-black text-slate-800 leading-none\">").append(nf(totalServ))
                    .append("</div><div class=\"text-right leading-none\">");
            if (metaTotal > 0) {
                s.append("<div class=\"text-[13px] font-black text-emerald-700 leading-none\">Meta ").append(nf(metaTotal)).append("</div>")
                        .append("<div class=\"text-[16px] font-black ").append(pctColor).append(" leading-none mt-1\" title=\"Cumplimiento calculado con servicios sin fallidos\">")
                        .append(pctCumpl).append("% cumpl.</div>");
            } else {
                s.append("<div class=\"text-[10px] font-black text-slate-400 leading-none\">Meta N/A</div>");
            }
            s.append("</div></div></div>");
            for (String[] k : kpis) {
                s.append("<div class=\"border rounded-xl p-3.5 border-").append(k[4]).append(" bg-").append(k[3]).append("\"><div class=\"text-[8.5px] uppercase font-black tracking-wide text-")
                        .append(k[4]).append(" leading-none mb-1.5\">").append(k[0]).append("</div><div class=\"text-2xl font-black text-").append(k[2]).append(" leading-none\">")
                        .append(Conv.h(k[1])).append("</div></div>");
            }
            s.append("</div>");

            // Distribución por tipo
            int totalBase = totalServ > 0 ? totalServ : 1;
            Object[][] barras = { { "Preventivo Básico", rt.get("basico"), "#0ea5e9" }, { "Preventivo Profundo", rt.get("profundo"), "#6366f1" },
                    { "Correctivo", rt.get("correctivo"), "#f59e0b" }, { "Fallido", rt.get("fallido"), "#f43f5e" }, { "Nueva Arquitectura", rt.get("nueva_arquitectura"), "#14b8a6" },
                    { "Otros", rt.get("otros"), "#94a3b8" } };
            s.append("<div class=\"mt-5 bloque bg-slate-50 border border-slate-200 rounded-xl p-4\"><div class=\"flex justify-between items-center mb-3\">")
                    .append("<h2 class=\"text-xs font-black uppercase tracking-wider text-slate-600\">Distribución por tipo de servicio</h2>")
                    .append("<span class=\"text-[9px] text-slate-400 font-bold uppercase\">Total: ").append(nf(totalServ)).append(" servicios</span></div>");
            for (Object[] b : barras) {
                String pct = pct1((Conv.toInt(b[1]) / (double) totalBase) * 100);
                s.append("<div class=\"flex items-center gap-3 mb-1.5\"><div class=\"w-40 text-[10px] font-bold text-slate-600 text-right shrink-0\">").append(b[0]).append("</div>")
                        .append("<div class=\"flex-1 h-4 bg-white rounded-full border border-slate-200 overflow-hidden\"><div class=\"h-full rounded-full\" style=\"width: ").append(pct)
                        .append("%; background: ").append(b[2]).append(";\"></div></div><div class=\"w-24 shrink-0\"><span class=\"text-[11px] font-black text-slate-800\">")
                        .append(nf(b[1])).append("</span><span class=\"text-[9px] font-bold text-slate-400 ml-1\">").append(pct).append("%</span></div></div>");
            }
            s.append("</div>");

            // Tabla productividad
            List<String> tipos = (List<String>) d.get("tiposColumnas");
            Map<String, Map<String, Integer>> resumen = (Map<String, Map<String, Integer>>) d.get("resumen");
            Map<String, Map<String, Object>> jornada = (Map<String, Map<String, Object>>) d.get("jornada");
            Map<String, Map<String, Object>> desp = (Map<String, Map<String, Object>>) d.get("desplazamientos");
            Map<String, Integer> totCol = (Map<String, Integer>) d.get("totalesColumna");
            s.append("<div class=\"mt-5 bloque\"><div class=\"flex justify-between items-center mb-2\"><h2 class=\"text-xs font-black uppercase tracking-wider text-slate-600\">Productividad por técnico del día</h2>")
                    .append("<div class=\"flex items-center gap-3 text-[8.5px] font-bold text-slate-500\">")
                    .append("<span class=\"flex items-center gap-1\"><span class=\"w-2 h-2 rounded-full bg-emerald-500\"></span> Meta cumplida (≥ meta del técnico)</span>")
                    .append("<span class=\"flex items-center gap-1\"><span class=\"w-2 h-2 rounded-full bg-amber-400\"></span> Por debajo (≥ 2/3 de la meta)</span>")
                    .append("<span class=\"flex items-center gap-1\"><span class=\"w-2 h-2 rounded-full bg-rose-500\"></span> Bajo meta</span>")
                    .append("<span class=\"flex items-center gap-1\"><span class=\"w-2 h-2 rounded-full bg-rose-300\"></span> Fallidos (marcados en la barra)</span></div></div>")
                    .append("<div class=\"rounded-xl border border-slate-200 overflow-hidden\"><table class=\"w-full text-[10px] text-center\"><thead class=\"bg-slate-800 text-white\"><tr>")
                    .append("<th class=\"px-3 py-2.5 text-left uppercase tracking-wider text-[9px] w-52\">Nombre del Técnico</th>")
                    .append("<th class=\"px-3 py-2.5 uppercase tracking-wider text-[9px] w-24\">Servicios<div class=\"text-[7px] font-medium opacity-70 normal-case tracking-normal\">total</div></th>")
                    .append("<th class=\"px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16\">Fallidos<div class=\"text-[7px] font-medium opacity-70 normal-case tracking-normal\">del total</div></th>")
                    .append("<th class=\"px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-20\">Meta<div class=\"text-[7px] font-medium opacity-70 normal-case tracking-normal\">esperada</div></th>")
                    .append("<th class=\"px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16\">Entrada</th>")
                    .append("<th class=\"px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16\">Salida</th>")
                    .append("<th class=\"px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16\">Duración</th>")
                    .append("<th class=\"px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-28\">Desplazamiento<div class=\"text-[7px] font-medium opacity-70 normal-case tracking-normal\">entre servicios</div></th>");
            for (String t : tipos) s.append("<th class=\"px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15\" style=\"min-width: 78px;\">").append(Conv.h(t)).append("</th>");
            s.append("</tr></thead><tbody class=\"divide-y divide-slate-100\">");
            for (String tec : tecOrd) {
                Map<String, Integer> fila = resumen.getOrDefault(tec, Map.of());
                Map<String, Object> jor = jornada.get(tec);
                Map<String, Object> r = rend.get(tec);
                int minutos = jor == null ? 0 : Conv.toInt(jor.get("minutos"));
                String dur = minutos > 0 ? hm(minutos) : "--";
                boolean hayF = Conv.toInt(r.get("fallidos")) > 0;
                s.append("<tr class=\"no-corte\"><td class=\"px-3 py-1.5 text-left bg-slate-50/60\"><span class=\"font-black text-slate-800 uppercase\">").append(Conv.h(tec)).append("</span></td>")
                        .append("<td class=\"px-3 py-1.5\"><div class=\"flex flex-col items-center gap-1\"><div class=\"text-[15px] font-black text-slate-900 leading-none\">")
                        .append(nf(r.get("servicios"))).append("</div><div class=\"flex w-28 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200\"><div class=\"h-full ")
                        .append(r.get("color")).append("\" style=\"width: ").append(num(Conv.toDouble(r.get("porcentaje")))).append("%\"></div>");
                if (hayF) s.append("<div class=\"h-full bg-rose-300\" style=\"width: ").append(num(Conv.toDouble(r.get("ancho_fallidos")))).append("%\"></div>");
                s.append("</div><div class=\"text-[8px] font-black ").append(r.get("texto")).append(" leading-none\">")
                        .append(Conv.toInt(r.get("meta")) > 0 ? Math.round(Conv.toDouble(r.get("porcentaje_real"))) + "%" : "Sin meta").append("</div></div></td>");
                s.append("<td class=\"px-2 py-1.5 border-l border-slate-100\">")
                        .append(hayF ? "<span class=\"inline-block min-w-[24px] px-2 py-0.5 rounded bg-rose-50 border border-rose-200 text-rose-700 font-black\">" + nf(r.get("fallidos")) + "</span>"
                                : "<span class=\"text-slate-300 font-bold\">-</span>")
                        .append("</td><td class=\"px-2 py-1.5 border-l border-slate-100\">");
                if (Conv.toInt(r.get("meta")) > 0) {
                    s.append("<span class=\"text-[13px] font-black text-slate-900\">").append(nf(r.get("meta"))).append("</span>");
                    if (Boolean.TRUE.equals(r.get("es_na"))) s.append("<div class=\"text-[7px] font-black text-indigo-600 uppercase leading-none mt-0.5\">Nueva Arq.</div>");
                } else s.append("<span class=\"text-slate-300 font-bold\">--</span>");
                s.append("</td><td class=\"px-2 py-1.5 border-l border-slate-100 font-bold text-slate-600\">").append(jor != null && jor.get("entrada") != null ? h(jor.get("entrada")) : "--")
                        .append("</td><td class=\"px-2 py-1.5 border-l border-slate-100 font-bold text-slate-600\">").append(jor != null && jor.get("salida") != null ? h(jor.get("salida")) : "--")
                        .append("</td><td class=\"px-2 py-1.5 border-l border-slate-100 font-black text-slate-800\">").append(dur).append("</td>");
                Map<String, Object> ds = desp.get(tec);
                List<String> tramos = ds == null ? List.of() : (List<String>) ds.get("tramos");
                s.append("<td class=\"px-2 py-1.5 border-l border-slate-100\"><div class=\"font-black text-slate-800 leading-none\">").append(ds == null ? "--" : h(ds.get("total_txt")))
                        .append("</div>");
                if (!tramos.isEmpty()) s.append("<div class=\"text-[7.5px] font-semibold text-slate-400 leading-tight mt-0.5\">").append(Conv.h(String.join(" · ", tramos))).append("</div>");
                s.append("</td>");
                for (String t : tipos) {
                    int c = fila.getOrDefault(t, 0);
                    s.append("<td class=\"px-1 py-1.5 border-l border-slate-100\">")
                            .append(c > 0 ? "<span class=\"inline-block min-w-[22px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-800 font-black\">" + nf(c) + "</span>"
                                    : "<span class=\"text-slate-300 font-bold\">-</span>")
                            .append("</td>");
                }
                s.append("</tr>");
            }
            int totalDesp = Conv.toInt(d.get("totalDesplazamientoDia"));
            s.append("</tbody><tfoot><tr class=\"bg-blue-600 text-white\"><td class=\"px-3 py-2.5 text-left font-black uppercase tracking-wider text-[10px]\">TOTALES</td>")
                    .append("<td class=\"px-3 py-2.5 text-center font-black text-sm\">").append(nf(d.get("totalGeneral"))).append("</td>")
                    .append("<td class=\"px-2 py-2.5 text-center font-black border-l border-white/20 text-rose-100\">").append(nf(d.get("totalFallidos"))).append("</td>")
                    .append("<td class=\"px-2 py-2.5 text-center font-black text-sm border-l border-white/20\">").append(sinMeta ? "--" : nf(metaTotal)).append("</td>")
                    .append("<td class=\"px-2 py-2.5 text-center font-black border-l border-white/20 text-white/70\">--</td><td class=\"px-2 py-2.5 text-center font-black border-l border-white/20 text-white/70\">--</td>")
                    .append("<td class=\"px-2 py-2.5 text-center font-black border-l border-white/20\">").append(hm(totalHoras)).append("</td>")
                    .append("<td class=\"px-2 py-2.5 text-center font-black border-l border-white/20\">").append(totalDesp > 0 ? hm(totalDesp) + "m" : "--").append("</td>");
            for (String t : tipos) s.append("<td class=\"px-1 py-2.5 text-center font-black border-l border-white/20\">").append(nf(totCol.getOrDefault(t, 0))).append("</td>");
            s.append("</tr></tfoot></table></div><p class=\"text-[8.5px] text-slate-400 italic mt-1.5\">(-) = Sin actividad registrada. Entrada/Salida corresponden al primer ingreso y la última salida del día. ")
                    .append("Meta = servicios esperados del técnico según el día: hábil 6, sábado 3, Nueva Arquitectura ").append(d.get("metaNuevaArquitectura")).append(" (")
                    .append(Boolean.TRUE.equals(d.get("esSabado")) ? "sábado" : "día hábil")
                    .append("). La barra muestra los servicios sin fallidos (color sólido) y los fallidos (tono claro) sobre esa meta. Domingos y festivos no tienen meta. El porcentaje se calcula solo con servicios sin fallidos. ")
                    .append("Desplazamiento = tiempo entre la salida de un servicio y la entrada al siguiente del mismo técnico, descontando 1 hora de almuerzo (franja 12:00–14:00).</p></div>");

            // Tabla de fallidos
            Map<String, List<Map<String, Object>>> fallidos = (Map<String, List<Map<String, Object>>>) d.get("fallidos");
            s.append("<div class=\"mt-5 bloque\"><div class=\"flex justify-between items-center mb-2\"><div class=\"flex items-center gap-2\">")
                    .append("<div class=\"w-7 h-7 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 text-xs font-black\">!</div><div>")
                    .append("<h2 class=\"text-xs font-black uppercase tracking-wider text-rose-700\">Tabla de fallidos</h2><p class=\"text-[9px] text-slate-400 leading-none\">Clientes con servicios fallidos en la jornada</p></div></div>")
                    .append("<span class=\"text-[10px] font-black text-rose-700 bg-rose-50 border border-rose-200 px-3 py-1 rounded-full\">Total fallidos: ").append(nf(d.get("totalFallidos")))
                    .append("</span></div>");
            if (fallidos.isEmpty()) {
                s.append("<div class=\"rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-5 text-center\"><p class=\"text-[12px] font-black text-emerald-700\">Excelente gestión: ningún cliente registra servicios fallidos en este periodo.</p></div>");
            } else {
                int maxFila = 1;
                for (var items : fallidos.values()) for (var it : items) maxFila = Math.max(maxFila, Conv.toInt(it.get("total")));
                s.append("<div class=\"rounded-xl border border-rose-200 overflow-hidden\"><table class=\"w-full text-[10px] text-left\"><thead class=\"bg-rose-700 text-white\"><tr>")
                        .append("<th class=\"px-3 py-2 uppercase tracking-wider text-[9px] w-52\">Delegación</th><th class=\"px-3 py-2 uppercase tracking-wider text-[9px]\">Cliente</th>")
                        .append("<th class=\"px-3 py-2 uppercase tracking-wider text-[9px]\">Novedad (revisión)</th><th class=\"px-3 py-2 uppercase tracking-wider text-[9px] text-right w-24\">Fallidos</th>")
                        .append("</tr></thead><tbody class=\"divide-y divide-rose-100\">");
                for (var e : fallidos.entrySet()) {
                    int totZona = e.getValue().stream().mapToInt(it -> Conv.toInt(it.get("total"))).sum();
                    s.append("<tr class=\"no-corte bg-rose-50/70\"><td class=\"px-3 py-1.5 font-black text-rose-800 uppercase\">- ").append(Conv.h(e.getKey()))
                            .append("</td><td class=\"px-3 py-1.5 text-[8.5px] text-rose-400 font-bold uppercase tracking-wide\" colspan=\"2\">Subtotal delegación</td>")
                            .append("<td class=\"px-3 py-1.5 text-right font-black text-rose-700\">").append(nf(totZona)).append("</td></tr>");
                    for (var it : e.getValue()) {
                        int tot = Conv.toInt(it.get("total"));
                        String punto = Conv.str(it.get("punto"));
                        String nov = Conv.str(it.get("novedad")).trim();
                        s.append("<tr class=\"no-corte\"><td class=\"px-3 py-1 pl-8\"></td><td class=\"px-3 py-1\"><div class=\"flex items-center gap-2\"><span class=\"text-slate-700 font-semibold\">")
                                .append(h(it.get("cliente"))).append("</span>");
                        if (!punto.isEmpty()) s.append("<span class=\"text-slate-500 text-[10px] font-normal\">(").append(Conv.h(punto)).append(")</span>");
                        s.append("<div class=\"flex-1 h-1.5 bg-rose-50 rounded-full overflow-hidden border border-rose-100 max-w-[320px]\"><div class=\"h-full bg-rose-500 rounded-full\" style=\"width: ")
                                .append(num((tot / (double) maxFila) * 100)).append("%\"></div></div></div></td><td class=\"px-3 py-1 text-[8.5px] leading-tight max-w-[420px]\">")
                                .append(!nov.isEmpty() ? "<span class=\"text-slate-700 font-semibold\">" + Conv.h(nov) + "</span>" : "<span class=\"text-slate-300 italic\">Sin novedad registrada</span>")
                                .append("</td><td class=\"px-3 py-1 text-right font-black text-rose-600\">").append(tot == 1 ? "-" : nf(tot)).append("</td></tr>");
                    }
                }
                s.append("</tbody><tfoot><tr class=\"bg-rose-800 text-white\"><td class=\"px-3 py-2 font-black uppercase tracking-wider\" colspan=\"3\">TOTAL SERVICIOS FALLIDOS</td>")
                        .append("<td class=\"px-3 py-2 text-right font-black text-sm\">").append(nf(d.get("totalFallidos"))).append("</td></tr></tfoot></table></div>");
            }
            s.append("</div>");
        }
        s.append("</body></html>");
        return s.toString();
    }
}
