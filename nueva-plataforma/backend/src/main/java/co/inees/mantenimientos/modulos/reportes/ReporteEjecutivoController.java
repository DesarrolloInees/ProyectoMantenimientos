package co.inees.mantenimientos.modulos.reportes;

import java.time.LocalDate;
import java.time.temporal.ChronoUnit;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.TreeMap;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Excel;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/** Port de reporteEjecutivoVerControlador.php (Tablero Ejecutivo de Servicios + exportación Excel) */
@RestController
public class ReporteEjecutivoController {

    private final ReporteEjecutivoModelo modelo;

    public ReporteEjecutivoController(ReporteEjecutivoModelo modelo) {
        this.modelo = modelo;
    }

    private static String primeroMes() {
        return F.hoy().substring(0, 8) + "01";
    }

    @RequestMapping(value = "/api/reporteEjecutivo", params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> index(Req req) {
        Map<String, Object> filtros = new LinkedHashMap<>();
        filtros.put("fecha_inicio", primeroMes());
        filtros.put("fecha_fin", F.hoy());
        if (req.isPost()) {
            if (req.has("fecha_inicio")) filtros.put("fecha_inicio", req.str("fecha_inicio", ""));
            if (req.has("fecha_fin")) filtros.put("fecha_fin", req.str("fecha_fin", ""));
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Tablero Ejecutivo de Servicios");
        r.put("filtros", filtros);

        Map<String, Object> meta = new LinkedHashMap<>(Map.of("esperados", 0, "dias_laborables", 0, "tecnicos_dias", 0, "esperados_normal", 0, "esperados_nueva_arquitectura", 0));
        String mensaje = "";
        String ini = Conv.str(filtros.get("fecha_inicio")), fin = Conv.str(filtros.get("fecha_fin"));
        List<Map<String, Object>> datosDia = List.of(), datosHoras = List.of();
        List<Map<String, Object>> datosDelegacion = new ArrayList<>();
        double mediaGlobal = 0, mediaDiaria = 0, cumplimiento = 0;
        long totalServicios = 0;

        if (!ini.isEmpty() && !fin.isEmpty()) {
            datosDia = modelo.getServiciosPorDia(ini, fin);
            for (var k : modelo.getKpisPorDelegacion(ini, fin)) {
                datosDelegacion.add(Excel.fila("nombre_delegacion", k.get("nombre_delegacion"), "total", k.get("total_servicios")));
            }
            datosHoras = modelo.getHorasVsServicios(ini, fin);
            r.put("datosTipo", modelo.getPorTipoMantenimiento(ini, fin));
            r.put("datosNovedad", modelo.getDistribucionNovedades(ini, fin));
            r.put("datosPuntosCriticos", modelo.getPuntosConMasFallidos(ini, fin));
            r.put("datosRepuestos", modelo.getOrigenRepuestos(ini, fin));

            var rawNov = modelo.getNovedadesPorDelegacion(ini, fin, false);
            var rawNovF = modelo.getNovedadesPorDelegacion(ini, fin, true);
            r.put("datosNovedadesPorDelegacion", agruparTopNovedades(rawNov, 3));
            r.put("datosNovedadesFallidasPorDelegacion", agruparTopNovedades(rawNovF, 6));
            r.put("topNovedadesGlobales", topNovedadesGlobales(rawNov, 3));
            r.put("clientesNovedades", agruparTopClientesNovedades(modelo.getClientesConMasNovedades(ini, fin), 3, 3));

            r.put("puntosVisitados", modelo.getPuntosMasVisitadosWeb(ini, fin, 2, 20, null));
            r.put("detallePuntosHover", agruparDetallePorPunto(modelo.getDetalleServiciosPorPunto(ini, fin, null), 6));

            var rawDet = modelo.getDetalleNovedades(ini, fin, null, null);
            List<Map<String, Object>> noFall = new ArrayList<>(), fall = new ArrayList<>();
            for (var f : rawDet) {
                if (Conv.str(f.get("tipo_servicio")).toUpperCase().contains("FALLID")) fall.add(f);
                else noFall.add(f);
            }
            r.put("detalleNovedadesHover", agruparDetalleNovedades(noFall, 6));
            r.put("detalleNovedadesFallidasHover", agruparDetalleNovedades(fall, 6));
            r.put("detalleClientesHover", agruparDetalleClientes(rawDet, 6));
            r.put("detalleClienteNovedadHover", agruparDetalleClienteNovedad(rawDet, 6));

            meta = modelo.getMetaServiciosEsperados(ini, fin);

            for (var d : datosDia) totalServicios += Conv.toLong(d.get("total"));
            long dias;
            try {
                dias = Math.abs(ChronoUnit.DAYS.between(LocalDate.parse(ini), LocalDate.parse(fin))) + 1;
            } catch (Exception e) {
                dias = 1;
            }
            int numTec = datosHoras.size();
            mediaGlobal = numTec > 0 ? redondear((double) totalServicios / numTec, 1) : 0;
            mediaDiaria = numTec > 0 && dias > 0 ? redondear(((double) totalServicios / numTec) / dias, 2) : 0;
            int esperados = Conv.toInt(meta.get("esperados"));
            cumplimiento = esperados > 0 ? redondear((double) totalServicios / esperados * 100, 1) : 0;
            if (datosDia.isEmpty() && datosDelegacion.isEmpty()) mensaje = "No se encontraron datos para el rango de fechas seleccionado.";
        } else {
            mensaje = "Por favor selecciona un rango de fechas válido.";
        }
        r.putIfAbsent("datosTipo", List.of());
        r.putIfAbsent("datosNovedad", Map.of());
        r.putIfAbsent("datosPuntosCriticos", List.of());
        r.putIfAbsent("datosRepuestos", List.of());
        r.putIfAbsent("datosNovedadesPorDelegacion", Map.of());
        r.putIfAbsent("datosNovedadesFallidasPorDelegacion", Map.of());
        r.putIfAbsent("topNovedadesGlobales", List.of());
        r.putIfAbsent("clientesNovedades", Map.of());
        r.putIfAbsent("puntosVisitados", List.of());
        r.putIfAbsent("detallePuntosHover", Map.of());
        r.putIfAbsent("detalleNovedadesHover", Map.of());
        r.putIfAbsent("detalleNovedadesFallidasHover", Map.of());
        r.putIfAbsent("detalleClientesHover", Map.of());
        r.putIfAbsent("detalleClienteNovedadHover", Map.of());
        r.put("datosDia", datosDia);
        r.put("datosDelegacion", datosDelegacion);
        r.put("datosHoras", datosHoras);
        r.put("metaServicios", meta);
        r.put("cumplimientoMeta", cumplimiento);
        r.put("totalServicios", totalServicios);
        r.put("mediaGlobal", mediaGlobal);
        r.put("mediaDiaria", mediaDiaria);
        r.put("mensaje", mensaje);
        return r;
    }

    static double redondear(double v, int dec) {
        double p = Math.pow(10, dec);
        return Math.round(v * p) / p;
    }

    // ================================================================ agrupadores

    private static final Comparator<Map<String, Object>> POR_TOTAL_NOMBRE = (a, b) -> {
        int ta = Conv.toInt(a.get("total")), tb = Conv.toInt(b.get("total"));
        if (ta == tb) return Conv.str(a.get("nombre")).compareTo(Conv.str(b.get("nombre")));
        return ta < tb ? 1 : -1;
    };

    private static String or(Object v, String def) {
        return v == null ? def : Conv.str(v);
    }

    private static String orEmpty(Object v, String def) {
        return Conv.empty(v) ? def : Conv.str(v);
    }

    static Map<String, List<Map<String, Object>>> agruparTopNovedades(List<Map<String, Object>> filas, int limite) {
        Map<String, List<Map<String, Object>>> ag = new TreeMap<>();
        for (var f : filas) {
            ag.computeIfAbsent(or(f.get("nombre_delegacion"), "SIN DELEGACIÓN"), k -> new ArrayList<>())
                    .add(Excel.fila("nombre", or(f.get("nombre_novedad"), "Sin novedad"), "total", Conv.toInt(f.get("total"))));
        }
        ag.replaceAll((k, items) -> {
            items.sort(POR_TOTAL_NOMBRE);
            return new ArrayList<>(items.subList(0, Math.min(limite, items.size())));
        });
        return ag;
    }

    static List<String> topNovedadesGlobales(List<Map<String, Object>> filas, int limite) {
        Map<String, Integer> tot = new LinkedHashMap<>();
        for (var f : filas) tot.merge(or(f.get("nombre_novedad"), "Sin novedad"), Conv.toInt(f.get("total")), Integer::sum);
        List<Map.Entry<String, Integer>> l = new ArrayList<>(tot.entrySet());
        l.sort((a, b) -> b.getValue() - a.getValue());
        return l.stream().limit(limite).map(Map.Entry::getKey).toList();
    }

    private static String claveCD(Object del, Object idCli) {
        return orEmpty(del, "SIN DELEGACIÓN") + "||" + orEmpty(idCli, "");
    }

    static Map<String, List<Map<String, Object>>> agruparTopClientesNovedades(Map<String, List<Map<String, Object>>> datos, int limCli, int limNov) {
        Map<String, List<Map<String, Object>>> chips = new LinkedHashMap<>();
        for (var n : datos.getOrDefault("novedades", List.of())) {
            chips.computeIfAbsent(claveCD(n.get("nombre_delegacion") == null ? "SIN DELEGACIÓN" : n.get("nombre_delegacion"), n.get("id_cliente")), k -> new ArrayList<>())
                    .add(Excel.fila("nombre", or(n.get("nombre_novedad"), "Sin novedad"), "total", Conv.toInt(n.get("total"))));
        }
        Map<String, List<Map<String, Object>>> ag = new TreeMap<>();
        for (var f : datos.getOrDefault("totales", List.of())) {
            String del = orEmpty(f.get("nombre_delegacion"), "SIN DELEGACIÓN");
            String cli = orEmpty(f.get("nombre_cliente"), "Sin Cliente");
            List<Map<String, Object>> items = new ArrayList<>(chips.getOrDefault(claveCD(del, f.get("id_cliente")), List.of()));
            items.sort(POR_TOTAL_NOMBRE);
            ag.computeIfAbsent(del, k -> new ArrayList<>()).add(Excel.fila(
                    "id_cliente", Conv.toInt(f.get("id_cliente")),
                    "cliente", cli,
                    "total", Conv.toInt(f.get("total")),
                    "novedades", items.subList(0, Math.min(limNov, items.size()))));
        }
        ag.replaceAll((k, cl) -> {
            cl.sort((a, b) -> {
                int ta = Conv.toInt(a.get("total")), tb = Conv.toInt(b.get("total"));
                if (ta == tb) return Conv.str(a.get("cliente")).compareTo(Conv.str(b.get("cliente")));
                return ta < tb ? 1 : -1;
            });
            return new ArrayList<>(cl.subList(0, Math.min(limCli, cl.size())));
        });
        return ag;
    }

    private static Map<String, Object> filaDetalle(Map<String, Object> f, boolean conNovedades) {
        return Excel.fila(
                "fecha", fechaTexto(f.get("fecha_visita")),
                "cliente", or(f.get("nombre_cliente"), "Sin Cliente"),
                "punto", or(f.get("nombre_punto"), ""),
                "tecnico", or(f.get("nombre_tecnico"), "SIN TÉCNICO"),
                "tipo", or(f.get("tipo_servicio"), "SIN TIPO"),
                "novedades", conNovedades ? or(f.get("novedades"), "") : "");
    }

    static Map<String, List<Map<String, Object>>> agruparDetallePorPunto(List<Map<String, Object>> filas, int limite) {
        Map<String, List<Map<String, Object>>> g = new LinkedHashMap<>();
        for (var f : filas) {
            String id = or(f.get("id_punto"), "");
            if (id.isEmpty()) continue;
            var l = g.computeIfAbsent(id, k -> new ArrayList<>());
            if (l.size() >= limite) continue;
            l.add(filaDetalle(f, true));
        }
        return g;
    }

    static Map<String, List<Map<String, Object>>> agruparDetalleNovedades(List<Map<String, Object>> filas, int limite) {
        Map<String, List<Map<String, Object>>> g = new LinkedHashMap<>();
        for (var f : filas) {
            String clave = or(f.get("nombre_delegacion"), "SIN DELEGACIÓN") + "||" + or(f.get("nombre_novedad"), "Sin novedad");
            var l = g.computeIfAbsent(clave, k -> new ArrayList<>());
            if (l.size() >= limite) continue;
            var it = filaDetalle(f, false);
            it.put("novedades", or(f.get("nombre_novedad"), ""));
            l.add(it);
        }
        return g;
    }

    @SuppressWarnings("unchecked")
    static Map<String, List<Map<String, Object>>> agruparDetalleClientes(List<Map<String, Object>> filas, int limite) {
        Map<String, List<Map<String, Object>>> g = new LinkedHashMap<>();
        Map<String, Map<String, Integer>> idx = new LinkedHashMap<>();
        for (var f : filas) {
            String clave = or(f.get("nombre_delegacion"), "SIN DELEGACIÓN") + "||" + or(f.get("id_cliente"), "");
            String orden = or(f.get("id_ordenes_servicio"), "");
            var ix = idx.computeIfAbsent(clave, k -> new LinkedHashMap<>());
            if (!ix.containsKey(orden)) {
                var l = g.computeIfAbsent(clave, k -> new ArrayList<>());
                if (l.size() >= limite) continue;
                var it = filaDetalle(f, false);
                it.put("novedades", new ArrayList<String>());
                l.add(it);
                ix.put(orden, l.size() - 1);
            }
            Integer i = ix.get(orden);
            if (i != null) {
                String nov = or(f.get("nombre_novedad"), "").trim();
                List<String> lst = (List<String>) g.get(clave).get(i).get("novedades");
                if (!nov.isEmpty() && !lst.contains(nov)) lst.add(nov);
            }
        }
        for (var l : g.values()) for (var it : l) it.put("novedades", String.join(", ", (List<String>) it.get("novedades")));
        return g;
    }

    static Map<String, List<Map<String, Object>>> agruparDetalleClienteNovedad(List<Map<String, Object>> filas, int limite) {
        Map<String, List<Map<String, Object>>> g = new LinkedHashMap<>();
        Map<String, Set<String>> vistos = new LinkedHashMap<>();
        for (var f : filas) {
            String nov = or(f.get("nombre_novedad"), "Sin novedad");
            String clave = or(f.get("nombre_delegacion"), "SIN DELEGACIÓN") + "||" + or(f.get("id_cliente"), "") + "||" + nov;
            String orden = or(f.get("id_ordenes_servicio"), "");
            var v = vistos.computeIfAbsent(clave, k -> new LinkedHashSet<>());
            if (v.contains(orden)) continue;
            var l = g.computeIfAbsent(clave, k -> new ArrayList<>());
            if (l.size() >= limite) continue;
            var it = filaDetalle(f, false);
            it.put("novedades", nov);
            l.add(it);
            v.add(orden);
        }
        return g;
    }

    // ================================================================ Excel

    @GetMapping(value = "/api/reporteEjecutivo", params = "accion=descargarExcel")
    public ResponseEntity<byte[]> descargarExcel(Req req) {
        String ini = validarFecha(req.str("fecha_inicio", ""), primeroMes());
        String fin = validarFecha(req.str("fecha_fin", ""), F.hoy());
        String tipo = req.str("tipo", "puntos").trim().toLowerCase();
        if (!List.of("puntos", "novedades", "novedades_fallidas", "clientes").contains(tipo)) tipo = "puntos";

        Map<String, Object> filtros = new LinkedHashMap<>();
        filtros.put("id_punto", numerico(req.str("id_punto", null)));
        filtros.put("id_cliente", numerico(req.str("id_cliente", null)));
        filtros.put("id_tipo_novedad", numerico(req.str("id_tipo_novedad", null)));
        filtros.put("delegacion", req.str("delegacion", "").trim());
        filtros.put("novedad", req.str("novedad", "").trim());

        List<String> tf = new ArrayList<>();
        if (!Conv.empty(filtros.get("delegacion"))) tf.add("Delegación: " + filtros.get("delegacion"));
        if (!Conv.empty(filtros.get("novedad"))) tf.add("Novedad: " + filtros.get("novedad"));
        if (!Conv.empty(filtros.get("id_cliente"))) tf.add("Cliente ID: " + filtros.get("id_cliente"));
        if (!Conv.empty(filtros.get("id_punto"))) tf.add("Punto ID: " + filtros.get("id_punto"));
        String sub = "Período: " + fechaTexto(ini) + " al " + fechaTexto(fin);
        if (!tf.isEmpty()) sub += " | " + String.join(" · ", tf);

        Excel x = new Excel("Resumen");
        if (tipo.equals("puntos")) hojaPuntos(x, ini, fin, filtros, sub);
        else if (tipo.equals("clientes")) hojaClientes(x, ini, fin, filtros, sub);
        else hojaNovedades(x, ini, fin, filtros, sub, tipo.equals("novedades_fallidas"));
        x.usarHoja(0);
        x.wb().setActiveSheet(0);
        return Descarga.excel(x.bytes(), "Reporte_Ejecutivo_" + tipo + "_" + ini + "_al_" + fin + ".xlsx");
    }

    private static Integer numerico(String v) {
        if (v == null || !Conv.esNumero(v)) return null;
        return (int) Conv.toDouble(v);
    }

    private void hojaPuntos(Excel x, String ini, String fin, Map<String, Object> filtros, String sub) {
        var resumen = modelo.getPuntosMasVisitadosWeb(ini, fin, 1, null, filtros);
        var detalle = modelo.getDetalleServiciosPorPunto(ini, fin, filtros);
        preparar(x, "Reporte Ejecutivo - Puntos Más Visitados", sub, "Delegación", "Punto", "Tipos de máquina", "Total visitas");
        int f = 5;
        for (var r : resumen) {
            x.setTexto("A" + f, r.get("nombre_delegacion"));
            x.setTexto("B" + f, r.get("nombre_punto"));
            x.setTexto("C" + f, orEmpty(r.get("tipos"), "S/D"));
            x.set("D" + f, Conv.toInt(r.get("total_visitas")));
            f++;
        }
        finalizar(x, f - 1, 4, new double[] { 28, 40, 34, 15 });

        x.nuevaHoja("Detalle servicios");
        preparar(x, "Detalle de servicios por punto", sub, "Fecha", "Cliente", "Delegación", "Punto", "Técnico", "Tipo de servicio", "Novedades");
        f = 5;
        for (var d : detalle) {
            filaTexto(x, f++, fechaTexto(d.get("fecha_visita")), d.get("nombre_cliente"), d.get("nombre_delegacion"), d.get("nombre_punto"),
                    d.get("nombre_tecnico"), d.get("tipo_servicio"), orEmpty(d.get("novedades"), ""));
        }
        finalizar(x, f - 1, 7, new double[] { 13, 34, 26, 36, 28, 30, 48 });
    }

    private void hojaNovedades(Excel x, String ini, String fin, Map<String, Object> filtros, String sub, boolean soloFallidos) {
        var detalle = modelo.getDetalleNovedades(ini, fin, soloFallidos, filtros);
        Map<String, Object[]> resumen = new LinkedHashMap<>();
        for (var d : detalle) {
            String clave = Conv.str(d.get("nombre_delegacion")) + "||" + Conv.str(d.get("nombre_novedad"));
            Object[] r = resumen.computeIfAbsent(clave, k -> new Object[] { Conv.str(d.get("nombre_delegacion")), Conv.str(d.get("nombre_novedad")), new LinkedHashSet<String>() });
            asSet(r[2]).add(Conv.str(d.get("id_ordenes_servicio")));
        }
        List<Object[]> lst = new ArrayList<>(resumen.values());
        lst.sort((a, b) -> {
            int c = ((String) a[0]).compareTo((String) b[0]);
            return c != 0 ? c : Integer.compare(asSet(b[2]).size(), asSet(a[2]).size());
        });
        preparar(x, soloFallidos ? "Reporte Ejecutivo - Novedades en Servicios Fallidos" : "Reporte Ejecutivo - Principales Novedades por Delegación", sub,
                "Delegación", "Novedad", "Total servicios");
        int f = 5;
        for (var r : lst) {
            x.setTexto("A" + f, r[0]);
            x.setTexto("B" + f, r[1]);
            x.set("C" + f, asSet(r[2]).size());
            f++;
        }
        finalizar(x, f - 1, 3, new double[] { 30, 50, 16 });

        x.nuevaHoja("Detalle servicios");
        preparar(x, "Detalle de servicios con novedad", sub, "Fecha", "Delegación", "Cliente", "Punto", "Técnico", "Tipo de servicio", "Novedad");
        f = 5;
        for (var d : detalle) {
            filaTexto(x, f++, fechaTexto(d.get("fecha_visita")), d.get("nombre_delegacion"), d.get("nombre_cliente"), d.get("nombre_punto"),
                    d.get("nombre_tecnico"), d.get("tipo_servicio"), d.get("nombre_novedad"));
        }
        finalizar(x, f - 1, 7, new double[] { 13, 28, 34, 36, 28, 30, 40 });
    }

    @SuppressWarnings("unchecked")
    private static Set<String> asSet(Object o) {
        return (Set<String>) o;
    }

    @SuppressWarnings("unchecked")
    private void hojaClientes(Excel x, String ini, String fin, Map<String, Object> filtros, String sub) {
        var detalle = modelo.getDetalleNovedades(ini, fin, null, filtros);
        Map<String, Map<String, Object>> servicios = new LinkedHashMap<>();
        Map<String, Object[]> resumen = new LinkedHashMap<>();
        for (var d : detalle) {
            String orden = Conv.str(d.get("id_ordenes_servicio"));
            var s = servicios.computeIfAbsent(orden, k -> {
                var m = Excel.fila("fecha", d.get("fecha_visita"), "delegacion", d.get("nombre_delegacion"), "cliente", d.get("nombre_cliente"),
                        "punto", d.get("nombre_punto"), "tecnico", d.get("nombre_tecnico"), "tipo", d.get("tipo_servicio"));
                m.put("novedades", new ArrayList<String>());
                return m;
            });
            String nov = Conv.str(d.get("nombre_novedad")).trim();
            List<String> ln = (List<String>) s.get("novedades");
            if (!nov.isEmpty() && !ln.contains(nov)) ln.add(nov);

            String clave = Conv.str(d.get("nombre_delegacion")) + "||" + Conv.str(d.get("id_cliente"));
            Object[] r = resumen.computeIfAbsent(clave, k -> new Object[] { Conv.str(d.get("nombre_delegacion")), Conv.str(d.get("nombre_cliente")),
                    new LinkedHashSet<String>(), new LinkedHashSet<String>() });
            asSet(r[2]).add(orden);
            asSet(r[3]).add(nov);
        }
        List<Object[]> lst = new ArrayList<>(resumen.values());
        lst.sort((a, b) -> {
            int c = ((String) a[0]).compareTo((String) b[0]);
            return c != 0 ? c : Integer.compare(asSet(b[2]).size(), asSet(a[2]).size());
        });
        preparar(x, "Reporte Ejecutivo - Clientes con Más Novedades por Delegación", sub, "Delegación", "Cliente", "Total servicios con novedad", "Novedades");
        int f = 5;
        for (var r : lst) {
            x.setTexto("A" + f, r[0]);
            x.setTexto("B" + f, r[1]);
            x.set("C" + f, asSet(r[2]).size());
            x.setTexto("D" + f, String.join(", ", asSet(r[3])));
            f++;
        }
        finalizar(x, f - 1, 4, new double[] { 28, 38, 22, 60 });

        x.nuevaHoja("Detalle servicios");
        preparar(x, "Detalle de servicios con novedad por cliente", sub, "Fecha", "Delegación", "Cliente", "Punto", "Técnico", "Tipo de servicio", "Novedades");
        f = 5;
        for (var s : servicios.values()) {
            filaTexto(x, f++, fechaTexto(s.get("fecha")), s.get("delegacion"), s.get("cliente"), s.get("punto"), s.get("tecnico"), s.get("tipo"),
                    String.join(", ", (List<String>) s.get("novedades")));
        }
        finalizar(x, f - 1, 7, new double[] { 13, 28, 34, 36, 28, 30, 50 });
    }

    private static void filaTexto(Excel x, int fila, Object... vals) {
        for (int i = 0; i < vals.length; i++) x.setTexto(Excel.ref(i + 1, fila), vals[i]);
    }

    private static void preparar(Excel x, String titulo, String sub, String... headers) {
        String ult = Excel.col(headers.length);
        x.set("A1", titulo).merge("A1:" + ult + "1");
        x.estilo("A1", Excel.E.of().bold().size(14).color("1F4E78").v("center"));
        x.alto(1, 24);
        x.set("A2", sub).merge("A2:" + ult + "2");
        x.estilo("A2", Excel.E.of().italic().size(10).color("64748B"));
        for (int i = 0; i < headers.length; i++) x.set(i + 1, 4, headers[i]);
        x.estilo("A4:" + ult + "4", Excel.E.of().bold().color("FFFFFF").size(11).fill("1F4E78").center().wrap().borde().bordeColor("B0B0B0"));
        x.alto(4, 30);
    }

    private static void finalizar(Excel x, int ultimaFila, int numCols, double[] anchos) {
        for (int i = 0; i < anchos.length; i++) x.ancho(Excel.col(i + 1), anchos[i]);
        String ultCol = Excel.col(numCols);
        if (ultimaFila > 4) {
            x.autoFiltro("A4:" + ultCol + ultimaFila);
            x.estilo("A5:" + ultCol + ultimaFila, Excel.E.of().borde().bordeColor("D9D9D9").v("center"));
        }
        x.congelar("A5");
    }

    private static String validarFecha(String f, String fallback) {
        return f != null && f.matches("^\\d{4}-\\d{2}-\\d{2}$") ? f : fallback;
    }

    static String fechaTexto(Object f) {
        if (Conv.empty(f)) return "";
        String s = Conv.str(f);
        String d = F.dmy(s);
        return Conv.empty(d) ? s : d;
    }
}
