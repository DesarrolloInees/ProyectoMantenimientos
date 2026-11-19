package co.inees.mantenimientos.modulos.reportes;

import java.nio.charset.StandardCharsets;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.time.temporal.IsoFields;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

import org.springframework.core.io.ClassPathResource;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Excel;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Pdf;
import co.inees.mantenimientos.core.Req;
import tools.jackson.databind.json.JsonMapper;

/**
 * Port de reporteEjecutivoGenerarControlador.php (generarReporteControlador): configurar + PDF ejecutivo.
 * La vista reporteEjecutivoGenerar.php se portó a resources/plantillas/reporte-ejecutivo.html
 * (Chrome headless ejecuta la plantilla igual que Browsershot ejecutaba la de PHP).
 */
@RestController
public class GenerarReporteController {

    private static final JsonMapper JSON = JsonMapper.builder().build();
    private static final Map<String, String> DIAS = Map.of("MONDAY", "Lun", "TUESDAY", "Mar", "WEDNESDAY", "Mié", "THURSDAY", "Jue", "FRIDAY", "Vie", "SATURDAY", "Sáb", "SUNDAY", "Dom");

    private final ReporteEjecutivoModelo modelo;
    private final Pdf pdf;

    public GenerarReporteController(ReporteEjecutivoModelo modelo, Pdf pdf) {
        this.modelo = modelo;
        this.pdf = pdf;
    }

    private static String primeroMes() {
        return F.hoy().substring(0, 8) + "01";
    }

    private static String ultimoMes() {
        LocalDate h = LocalDate.now();
        return h.withDayOfMonth(h.lengthOfMonth()).toString();
    }

    @GetMapping(value = "/api/generarReporte", params = "accion=configurar")
    public Map<String, Object> configurar(Req req) {
        Map<String, Object> f = new LinkedHashMap<>();
        f.put("fecha_inicio", req.str("inicio", primeroMes()));
        f.put("fecha_fin", req.str("fin", ultimoMes()));
        return Map.of("titulo", "Configurar Reporte Ejecutivo", "filtros", f);
    }

    @GetMapping(value = "/api/generarReporte", params = "accion=generar")
    public ResponseEntity<?> generar(Req req) {
        String inicio = req.str("inicio", primeroMes());
        String fin = req.str("fin", ultimoMes());
        List<String> secciones = Req.asList(req.get("secciones")).stream().map(Conv::str).toList();
        Map<String, Object> d = new LinkedHashMap<>();
        d.put("inicio", inicio);
        d.put("fin", fin);
        d.put("secciones", secciones);

        // 1. Evolución diaria agrupada por semanas (clave "Semana W")
        Map<String, List<Map<String, Object>>> semanas = new LinkedHashMap<>();
        for (var dia : modelo.getServiciosPorDia(inicio, fin)) {
            String fecha = Conv.str(dia.get("fecha_visita")).substring(0, 10);
            LocalDate ld = LocalDate.parse(fecha);
            String sem = "Semana " + String.format("%02d", ld.get(IsoFields.WEEK_OF_WEEK_BASED_YEAR));
            semanas.computeIfAbsent(sem, k -> new ArrayList<>())
                    .add(Excel.fila("fecha", fecha, "dia_nombre", DIAS.get(ld.getDayOfWeek().name()), "total", dia.get("total"), "num_tecnicos", dia.get("num_tecnicos")));
        }
        List<Map<String, Object>> semanasGroup = new ArrayList<>();
        semanas.forEach((k, v) -> semanasGroup.add(Excel.fila("nombre", k, "dias", v)));
        d.put("semanasGroup", semanasGroup);

        d.put("logo", pdf.dataUri("app/logos/logoInees.jpg"));

        // 2. Matriz tipo de máquina por delegación
        d.put("tiposMaquinaCols", modelo.getTiposMaquinaActivos(inicio, fin));
        Map<String, Map<String, Object>> matrizMaquina = new LinkedHashMap<>();
        for (var r : modelo.getDatosMatrizTipoMaquina(inicio, fin)) {
            matrizMaquina.computeIfAbsent(Conv.str(r.get("nombre_delegacion")), k -> new LinkedHashMap<>()).put(Conv.str(r.get("nombre_tipo_maquina")), r.get("total"));
        }
        List<String> delegMaq = new ArrayList<>(matrizMaquina.keySet());
        delegMaq.sort(null);
        d.put("matrizMaquina", matrizMaquina);
        d.put("delegacionesListaMaquina", delegMaq);

        // 3. Repuestos top por delegación
        Map<String, Map<String, Object>> rep = new LinkedHashMap<>();
        for (var r : modelo.getRepuestosPorDelegacion(inicio, fin)) {
            String del = Conv.str(r.get("nombre_delegacion"));
            var g = rep.computeIfAbsent(del, k -> {
                var m = Excel.fila("delegacion", k, "total_gral", 0.0);
                m.put("items", new ArrayList<Map<String, Object>>());
                return m;
            });
            @SuppressWarnings("unchecked")
            var items = (List<Map<String, Object>>) g.get("items");
            if (items.size() < 10) items.add(Excel.fila("nombre", r.get("descripcion_repuesto"), "cantidad", r.get("cantidad_usada")));
            g.put("total_gral", Conv.toDouble(g.get("total_gral")) + Conv.toDouble(r.get("cantidad_usada")));
        }
        rep.values().forEach(g -> g.put("total_gral", numero(Conv.toDouble(g.get("total_gral")))));
        d.put("repuestosPorDelegacion", new ArrayList<>(rep.values()));

        // Puntos más visitados
        int minVisitas = Conv.esNumero(req.str("min_visitas", "")) ? (int) Conv.toDouble(req.str("min_visitas", "")) : 2;
        Map<String, List<Map<String, Object>>> pva = new LinkedHashMap<>();
        for (var pv : modelo.getPuntosMasVisitados(inicio, fin, minVisitas)) {
            pva.computeIfAbsent(Conv.str(pv.get("nombre_delegacion")), k -> new ArrayList<>())
                    .add(Excel.fila("punto", pv.get("nombre_punto"), "tipo", pv.get("nombre_tipo_maquina") == null ? "S/D" : pv.get("nombre_tipo_maquina"), "total", pv.get("total_visitas")));
        }
        List<Map<String, Object>> pvaList = new ArrayList<>();
        pva.forEach((k, v) -> pvaList.add(Excel.fila("delegacion", k, "puntos", v)));
        d.put("puntosVisitadosAgrupados", pvaList);

        // Calificaciones agrupadas
        Map<String, Map<String, Object>> cal = new LinkedHashMap<>();
        for (var c : modelo.getCalificacionesServicio(inicio, fin)) {
            var g = cal.computeIfAbsent(Conv.str(c.get("nombre_delegacion")), k -> {
                var m = Excel.fila("delegacion", k, "total_zona", 0L);
                m.put("items", new ArrayList<Map<String, Object>>());
                return m;
            });
            @SuppressWarnings("unchecked")
            var items = (List<Map<String, Object>>) g.get("items");
            items.add(Excel.fila("nombre", c.get("nombre_calificacion"), "total", c.get("total")));
            g.put("total_zona", Conv.toLong(g.get("total_zona")) + Conv.toLong(c.get("total")));
        }
        d.put("calificacionesAgrupadas", new ArrayList<>(cal.values()));

        // 4. Técnicos (top 15) con desglose por tipo
        var detallado = modelo.getProductividadDetallada(inicio, fin);
        List<Map<String, Object>> top = new ArrayList<>();
        Map<String, Map<String, Object>> mapaDesglose = new LinkedHashMap<>();
        for (var r : modelo.getDesgloseMantenimientoPorTecnico(inicio, fin)) {
            mapaDesglose.computeIfAbsent(Conv.str(r.get("nombre_tecnico")), k -> new LinkedHashMap<>()).put(Conv.str(r.get("tipo")), r.get("cantidad"));
        }
        for (var t : detallado.subList(0, Math.min(15, detallado.size()))) {
            var m = new LinkedHashMap<>(t);
            m.put("desglose", mapaDesglose.getOrDefault(Conv.str(t.get("nombre_tecnico")), Map.of()));
            top.add(m);
        }
        d.put("topTecnicos", top);
        d.put("todosTiposMant", modelo.getAllTiposMantenimiento());
        d.put("datosNovedad", modelo.getDistribucionNovedades(inicio, fin));

        // Novedades
        var rawNov = modelo.getNovedadesPorDelegacion(inicio, fin, false);
        d.put("novedadesPorDelegacion", ReporteEjecutivoController.agruparTopNovedades(rawNov, 6));
        d.put("novedadesFallidasPorDelegacion", ReporteEjecutivoController.agruparTopNovedades(modelo.getNovedadesPorDelegacion(inicio, fin, true), 6));
        d.put("clientesNovedades", ReporteEjecutivoController.agruparTopClientesNovedades(modelo.getClientesConMasNovedades(inicio, fin), 3, 3));

        Map<String, Object> meta = modelo.getMetaServiciosEsperados(inicio, fin);
        d.put("metaServicios", meta);
        d.put("datosOrigenRepuestos", modelo.getOrigenRepuestos(inicio, fin));
        d.put("datosEstadosFinales", modelo.getDistribucionEstados(inicio, fin));

        // Matriz mantenimiento
        Map<String, Map<String, Object>> matrizMant = new LinkedHashMap<>();
        for (var r : modelo.getDatosMatrizMantenimiento(inicio, fin)) {
            matrizMant.computeIfAbsent(Conv.str(r.get("nombre_delegacion")), k -> new LinkedHashMap<>()).put(Conv.str(r.get("tipo")), r.get("total"));
        }
        List<String> delegMant = new ArrayList<>(matrizMant.keySet());
        delegMant.sort(null);
        d.put("matrizMant", matrizMant);
        d.put("delegacionesListaMant", delegMant);

        // Matriz puntos por tipo
        Map<String, Map<String, Object>> matrizPuntos = new LinkedHashMap<>();
        for (var r : modelo.getDatosMatrizPuntosPorTipo(inicio, fin)) {
            matrizPuntos.computeIfAbsent(Conv.str(r.get("nombre_delegacion")), k -> new LinkedHashMap<>()).put(Conv.str(r.get("nombre_tipo_maquina")), r.get("total"));
        }
        d.put("matrizPuntosTipo", matrizPuntos);

        // Puntos fallidos por delegación (ksort)
        Map<String, Map<String, Object>> pf = new TreeMap<>();
        for (var r : modelo.getPuntosConMasFallidos(inicio, fin)) {
            var g = pf.computeIfAbsent(Conv.str(r.get("nombre_delegacion")), k -> {
                var m = Excel.fila("delegacion", k, "total_zona", 0L);
                m.put("items", new ArrayList<Map<String, Object>>());
                return m;
            });
            @SuppressWarnings("unchecked")
            var items = (List<Map<String, Object>>) g.get("items");
            items.add(Excel.fila("nombre", r.get("nombre_punto"), "cantidad", r.get("total_fallidos")));
            g.put("total_zona", Conv.toLong(g.get("total_zona")) + Conv.toLong(r.get("total_fallidos")));
        }
        d.put("puntosFallidosPorDelegacion", new ArrayList<>(pf.values()));

        // KPIs portada
        var rawKpis = modelo.getKpisPorDelegacion(inicio, fin);
        long totalGlobal = 0;
        for (var k : rawKpis) totalGlobal += Conv.toLong(k.get("total_servicios"));
        List<Map<String, Object>> kpis = new ArrayList<>();
        for (var k : rawKpis) {
            long dias = Conv.toLong(k.get("dias_efectivos"));
            double prom = Conv.toDouble(k.get("total_servicios")) / (dias > 0 ? dias : 1);
            kpis.add(Excel.fila(
                    "delegacion", k.get("nombre_delegacion"),
                    "total", k.get("total_servicios"),
                    "num_tecnicos", k.get("total_tecnicos"),
                    "porcentaje", totalGlobal > 0 ? numero(ReporteEjecutivoController.redondear(Conv.toDouble(k.get("total_servicios")) / totalGlobal * 100, 1)) : 0,
                    "novedades", k.get("total_novedades"),
                    "promedio_diario", numero(ReporteEjecutivoController.redondear(prom, 2))));
        }
        d.put("kpisDelegacion", kpis);
        d.put("totalGlobalServicios", totalGlobal);
        d.put("totalServiciosFallidos", modelo.getTotalServiciosFallidos(inicio, fin));
        int esperados = Conv.toInt(meta.get("esperados"));
        d.put("cumplimientoMeta", esperados > 0 ? ReporteEjecutivoController.redondear((double) totalGlobal / esperados * 100, 1) : 0);

        // 5. Financiero
        var desglose = modelo.getDesgloseIngresosPorTipo(inicio, fin);
        Map<String, Object> ing = new LinkedHashMap<>();
        ing.put("preventivo", valor(desglose, "1", "total"));
        ing.put("profundo", valor(desglose, "2", "total"));
        ing.put("correctivo", valor(desglose, "3", "total"));
        ing.put("fallido", valor(desglose, "4", "total"));
        ing.put("garantia", valor(desglose, "5", "total"));
        ing.put("cant_prev", valor(desglose, "1", "cantidad"));
        ing.put("cant_prof", valor(desglose, "2", "cantidad"));
        ing.put("cant_corr", valor(desglose, "3", "cantidad"));
        ing.put("cant_fall", valor(desglose, "4", "cantidad"));
        ing.put("cant_garan", valor(desglose, "5", "cantidad"));
        d.put("ingresos", ing);
        d.put("ingresoRepuestos", modelo.getTotalIngresosRepuestos(inicio, fin));
        d.put("listaCostos", modelo.getListadoCostosOperativos(inicio, fin));
        d.put("listaGastosGenerales", modelo.getListadoGastosGenerales(inicio, fin));

        try {
            String plantilla;
            try (var in = new ClassPathResource("plantillas/reporte-ejecutivo.html").getInputStream()) {
                plantilla = new String(in.readAllBytes(), StandardCharsets.UTF_8);
            }
            String json = JSON.writeValueAsString(d).replace("</", "<\\/");
            String html = plantilla.replace("/*DATOS*/null", json)
                    .replace("PIE_PERIODO", F.dmy(inicio) + " - " + F.dmy(fin));
            byte[] contenido = pdf.generar(html);
            DateTimeFormatter f = DateTimeFormatter.ofPattern("dd-MM-yyyy");
            String nombre = "Reporte_Ejecutivo_" + LocalDate.parse(inicio).format(f) + "_al_" + LocalDate.parse(fin).format(f) + ".pdf";
            return Descarga.pdf(contenido, nombre, true);
        } catch (Exception e) {
            return Descarga.html("<h1>Error generando PDF</h1><p>" + Conv.h(e.getMessage()) + "</p>");
        }
    }

    private static Object valor(Map<String, Map<String, Object>> m, String id, String campo) {
        var x = m.get(id);
        return x == null ? 0 : x.get(campo);
    }

    /** Devuelve un entero cuando no hay decimales (como PHP al imprimir floats). */
    private static Object numero(double v) {
        return v == Math.rint(v) ? (Object) (long) v : (Object) v;
    }
}
