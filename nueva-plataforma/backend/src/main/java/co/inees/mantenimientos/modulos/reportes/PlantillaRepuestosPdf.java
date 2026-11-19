package co.inees.mantenimientos.modulos.reportes;

import java.util.List;
import java.util.Map;

import co.inees.mantenimientos.core.Conv;

/** Port de app/views/reportes/reporteRepuestosGenerar.php (Browsershot: A4 horizontal, escala 0.8, pie con página). */
final class PlantillaRepuestosPdf {

    private PlantillaRepuestosPdf() {
    }

    private static String h(Object o) {
        return Conv.h(Conv.str(o));
    }

    private static String miles(Object n) {
        return String.format(java.util.Locale.US, "%,d", Conv.toLong(n));
    }

    static String render(Map<String, Object> kpis, List<Map<String, Object>> consolidado, Map<String, List<Map<String, Object>>> porTecnico, String fechaReporte,
            String logo) {
        int mitad = (int) Math.ceil(consolidado.size() / 2.0);
        List<Map<String, Object>> izq = consolidado.subList(0, mitad), der = consolidado.subList(mitad, consolidado.size());
        String pie = Conv.h(fechaReporte).replace("\"", "'");
        StringBuilder s = new StringBuilder(60_000);
        s.append("""
                <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Reporte de Repuestos</title>
                <script src="https://cdn.tailwindcss.com"></script>
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
                <style>
                body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; zoom: 0.8; }
                @page {
                  size: A4 landscape; margin: 10mm 10mm 15mm 10mm;
                  @bottom-left { content: "CONTROL DE INVENTARIO"; font-family: sans-serif; font-size: 9px; font-weight: bold; letter-spacing: 1px; color: #64748b; border-top: 1px solid #e2e8f0; }
                  @bottom-center { content: "Generado: PIE_FECHA"; font-family: sans-serif; font-size: 9px; font-weight: bold; color: #64748b; border-top: 1px solid #e2e8f0; }
                  @bottom-right { content: "Página " counter(page); font-family: sans-serif; font-size: 9px; font-weight: bold; color: #475569; border-top: 1px solid #e2e8f0; }
                }
                .page-break { page-break-after: always; break-after: page; }
                .avoid-break { page-break-inside: avoid; break-inside: avoid; }
                .card-wrapper { display: block; width: 100%; box-sizing: border-box; margin-bottom: 1.5rem; }
                .card { background-color: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; padding: 24px; }
                .tecnico-page { width: 100% !important; page-break-after: always; break-after: page; }
                .repuestos-grid { column-count: 3; column-gap: 1.5rem; width: 100%; }
                .repuesto-item { break-inside: avoid; page-break-inside: avoid; margin-bottom: 0.5rem; padding: 0.4rem 0.3rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
                </style></head><body class="p-4 text-slate-800">
                """.replace("PIE_FECHA", pie));

        s.append("<div class=\"card-wrapper avoid-break\"><div class=\"card flex justify-between items-center border-t-8 border-indigo-600 relative overflow-hidden\"><div>")
                .append("<h1 class=\"text-4xl font-black text-indigo-700 tracking-tight\">REPORTE DE <span class=\"text-slate-800\">INVENTARIO</span></h1>")
                .append("<p class=\"text-slate-500 mt-2 font-medium\">Estado actual del stock asignado a técnicos en campo.</p>")
                .append("<div class=\"mt-3 inline-block bg-indigo-50 border border-indigo-100 px-4 py-1.5 rounded-md text-sm font-bold text-indigo-700\">Corte al: ")
                .append(Conv.h(fechaReporte)).append("</div></div>");
        if (!logo.isEmpty()) s.append("<img src=\"").append(logo).append("\" class=\"h-16 object-contain\" alt=\"Logo\">");
        s.append("</div></div>");

        String[][] k = { { "bg-blue-100", "text-blue-600", "📦", "Total Piezas Asignadas", miles(kpis.get("total_piezas")), "text-slate-400", "text-slate-800", "" },
                { "bg-emerald-100", "text-emerald-600", "👷", "Técnicos con Stock", h(Conv.toInt(kpis.get("tecnicos_con_stock"))), "text-slate-400", "text-slate-800", "" },
                { "bg-purple-100", "text-purple-600", "🔖", "Referencias Activas", h(Conv.toInt(kpis.get("referencias_distintas"))), "text-slate-400", "text-slate-800", "" },
                { "bg-rose-100", "text-rose-600", "⚠️", "Repuestos Agotados", h(Conv.toInt(kpis.get("repuestos_agotados"))), "text-rose-500", "text-rose-700",
                        " border-rose-200 bg-rose-50/30" } };
        s.append("<div class=\"grid grid-cols-4 gap-4 mb-6 avoid-break\">");
        for (String[] x : k) {
            s.append("<div class=\"bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4").append(x[7]).append("\">")
                    .append("<div class=\"w-12 h-12 rounded-full ").append(x[0]).append(" flex items-center justify-center ").append(x[1]).append(" text-2xl\">").append(x[2])
                    .append("</div><div><p class=\"text-[10px] uppercase font-bold ").append(x[5]).append("\">").append(x[3]).append("</p>")
                    .append("<p class=\"text-2xl font-black ").append(x[6]).append("\">").append(x[4]).append("</p></div></div>");
        }
        s.append("</div>");

        s.append("<div class=\"card-wrapper\"><div class=\"card\"><h3 class=\"text-lg font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4\"><i class=\"text-indigo-500 mr-2\">📊</i> Consolidado Global de Repuestos en Calle</h3><div class=\"flex flex-row gap-6\">");
        for (List<Map<String, Object>> lado : List.of(izq, der)) {
            s.append("<div class=\"w-1/2\"><table class=\"w-full text-xs text-left\"><thead class=\"bg-slate-100 text-slate-600 font-bold uppercase text-[9px]\"><tr>")
                    .append("<th class=\"py-2 px-3 rounded-tl-lg\">Repuesto</th><th class=\"py-2 px-3 text-center\">Ref.</th><th class=\"py-2 px-3 text-center\">Técnicos</th><th class=\"py-2 px-3 text-right rounded-tr-lg\">Cantidad Total</th>")
                    .append("</tr></thead><tbody class=\"divide-y divide-slate-100\">");
            for (var it : lado) {
                s.append("<tr class=\"avoid-break hover:bg-slate-50\"><td class=\"py-1.5 px-3 font-medium text-slate-700\">").append(h(it.get("nombre_repuesto")))
                        .append("</td><td class=\"py-1.5 px-3 text-center text-[10px] text-slate-500\">").append(Conv.empty(it.get("codigo_referencia")) ? "N/A" : h(it.get("codigo_referencia")))
                        .append("</td><td class=\"py-1.5 px-3 text-center\"><span class=\"bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px]\">").append(h(it.get("tecnicos_lo_tienen")))
                        .append("</span></td><td class=\"py-1.5 px-3 text-right font-black text-indigo-600\">").append(h(it.get("total_asignado"))).append("</td></tr>");
            }
            s.append("</tbody></table></div>");
        }
        s.append("</div></div><div class=\"page-break\"></div>");

        s.append("<div class=\"card-wrapper\"><h3 class=\"text-2xl font-black text-slate-800 mb-4 px-2\">Desglose por Técnico</h3>");
        for (var e : porTecnico.entrySet()) {
            List<Map<String, Object>> reps = e.getValue();
            long totalTec = reps.stream().mapToLong(r -> Conv.toLong(r.get("cantidad_actual"))).sum();
            s.append("<div class=\"tecnico-page w-full\" style=\"page-break-after: always; break-after: page;\">")
                    .append("<div class=\"bg-white border border-slate-200 rounded-xl shadow-sm p-4 mb-4 flex justify-between items-center\"><div class=\"flex items-center gap-3\">")
                    .append("<div class=\"w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 text-base\">👷</div><div>")
                    .append("<h3 class=\"text-xl font-extrabold text-slate-800 tracking-tight\">").append(Conv.h(e.getKey())).append("</h3>")
                    .append("<p class=\"text-[10px] text-slate-400 font-medium\">Inventario asignado en campo</p></div></div><div class=\"flex items-center gap-3\">")
                    .append("<span class=\"text-xs bg-indigo-50 text-indigo-700 border border-indigo-100 px-3 py-1 rounded-md font-bold\">").append(reps.size()).append(" Referencias</span>")
                    .append("<span class=\"text-xs bg-slate-100 text-slate-700 border border-slate-200 px-3 py-1 rounded-md font-black\">Total Piezas: ").append(totalTec).append("</span>")
                    .append("</div></div><div class=\"repuestos-grid\" style=\"column-count: 3; column-gap: 1.5rem; width: 100%;\">");
            for (var r : reps) {
                String color = Conv.toLong(r.get("cantidad_actual")) > 0 ? "text-emerald-600" : "text-rose-500";
                s.append("<div class=\"repuesto-item\" style=\"break-inside: avoid; page-break-inside: avoid; margin-bottom: 0.5rem; padding: 0.4rem 0.3rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;\">")
                        .append("<div style=\"flex: 1;\"><div class=\"font-medium text-slate-700 text-[10px] leading-tight\">").append(h(r.get("nombre_repuesto"))).append("</div>");
                if (!Conv.empty(r.get("codigo_referencia"))) s.append("<div class=\"text-[8px] text-slate-400 font-mono\">Ref: ").append(h(r.get("codigo_referencia"))).append("</div>");
                s.append("</div><div class=\"ml-2 whitespace-nowrap\"><span class=\"font-bold px-2 py-0.5 rounded text-[10px] ").append(color).append("\">").append(h(r.get("cantidad_actual")))
                        .append("</span></div></div>");
            }
            s.append("</div></div>");
        }
        s.append("</div></div></body></html>");
        return s.toString();
    }
}
