package co.inees.mantenimientos.modulos.reportes;

import java.text.Normalizer;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Pdf;
import co.inees.mantenimientos.core.Req;
import co.inees.mantenimientos.core.ResumenTiposServicio;
import co.inees.mantenimientos.modulos.orden.OrdenReporteController;

/** Port de reporteTecnicoControlador.php + reporteTecnicoModelo.php */
@RestController
@RequestMapping("/api/reporteTecnico")
public class ReporteTecnicoController {

    private static final Logger log = LoggerFactory.getLogger(ReporteTecnicoController.class);
    static final List<String> ORDEN_PERSONALIZADO = List.of("MURGAS", "JHONY", "MAICOL", "RUIZ", "ORJUELA", "FORERO", "ESPINOSA", "MAURICIO", "JHONATAN", "ORTIZ",
            "CERVERA", "VILORIA", "SAAVEDRA", "BENAVIDES");

    private final Db db;
    private final Pdf pdf;
    private final Archivos archivos;
    private final OrdenReporteController ordenReporte;

    public ReporteTecnicoController(Db db, Pdf pdf, Archivos archivos, OrdenReporteController ordenReporte) {
        this.db = db;
        this.pdf = pdf;
        this.archivos = archivos;
        this.ordenReporte = ordenReporte;
    }

    // ================================================================ MODELO

    List<Object> festivos(String ini, String fin) {
        try {
            return db.column("SELECT fecha FROM dias_festivos WHERE fecha BETWEEN :inicio AND :fin", "inicio", ini, "fin", fin).stream()
                    .map(f -> (Object) Conv.str(f).substring(0, Math.min(10, Conv.str(f).length()))).toList();
        } catch (Exception e) {
            return List.of();
        }
    }

    /** generarReporteServicios(): órdenes normales + remisiones sueltas de instalación/desinstalación */
    public List<Map<String, Object>> generarReporteServicios(String idTecnico, String ini, String fin) {
        try {
            Map<String, Object> p = new LinkedHashMap<>(Map.of("inicio", ini, "fin", fin));
            String sql1 = """
                    SELECT
                    os.id_ordenes_servicio,
                    os.numero_remision,
                    os.fecha_visita,
                    os.hora_entrada,
                    os.hora_salida,
                    os.valor_servicio,
                    os.actividades_realizadas,
                    t.nombre_tecnico,
                    c.nombre_cliente,
                    p.nombre_punto,
                    d.nombre_delegacion AS delegacion,
                    m.device_id,
                    tm.nombre_completo AS tipo_mantenimiento,
                    IFNULL(
                        (SELECT GROUP_CONCAT(tn.nombre_novedad SEPARATOR ', ')
                         FROM orden_servicio_novedad osn
                         JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                         WHERE osn.id_orden_servicio = os.id_ordenes_servicio), '') AS novedades
                        FROM ordenes_servicio os
                        INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                        INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                        INNER JOIN punto p ON os.id_punto = p.id_punto
                        LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                        INNER JOIN maquina m ON os.id_maquina = m.id_maquina
                        LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                        WHERE os.fecha_visita BETWEEN :inicio AND :fin
                        AND UPPER(tm.nombre_completo) NOT LIKE '%INSTALACIÓN%'
                        AND UPPER(tm.nombre_completo) NOT LIKE '%INSTALACION%'""";
            String sql2 = """
                    SELECT
                    cr.id_control AS id_ordenes_servicio,
                    cr.numero_remision,
                    DATE(cr.fecha_uso) AS fecha_visita,
                    TIME(cr.fecha_uso) AS hora_entrada,
                    TIME(cr.fecha_uso) AS hora_salida,
                    0 AS valor_servicio,
                    er.nombre_estado AS actividades_realizadas,
                    t.nombre_tecnico,
                    'SIN CLIENTE (REMISIÓN)' AS nombre_cliente,
                    'N/A' AS nombre_punto,
                    'N/A' AS delegacion,
                    'N/A' AS device_id,
                    er.nombre_estado AS tipo_mantenimiento,
                    '' AS novedades
                    FROM control_remisiones cr
                    INNER JOIN tecnico t ON cr.id_tecnico = t.id_tecnico
                    INNER JOIN estados_remision er ON cr.id_estado = er.id_estado
                    WHERE cr.fecha_uso IS NOT NULL
                    AND cr.fecha_uso != '0000-00-00 00:00:00'
                    AND cr.fecha_uso != ''
                    AND DATE(cr.fecha_uso) BETWEEN :inicio AND :fin
                    AND (cr.id_orden_servicio IS NULL OR cr.id_orden_servicio = 0)""";
            if (!Conv.empty(idTecnico)) {
                sql1 += " AND os.id_tecnico = :id_tecnico";
                sql2 += " AND cr.id_tecnico = :id_tecnico";
                p.put("id_tecnico", Conv.toInt(idTecnico));
            }
            sql2 += """
                     AND UPPER(TRIM(er.nombre_estado)) IN (
                    'KISAN', 'DESINSTALACIÓN', 'DESINSTALACION', 'CAMBIO DE MÁQUINA', 'CAMBIO DE MAQUINA',
                    'INSTALACIÓN FALLIDA', 'INSTALACION FALLIDA', 'DESANCLAJE', 'ANCLAJE',
                    'INSTALACIÓN MÁS CAPACITACIÓN', 'INSTALACION MAS CAPACITACION',
                    'INSTALACIÓN SIN CAPACITACIÓN', 'INSTALACION SIN CAPACITACION',
                    'INSTALACIÓN', 'INSTALACION'
                    )""";
            List<Map<String, Object>> r = new ArrayList<>(db.list(sql1, p));
            r.addAll(db.list(sql2, p));
            r.sort((a, b) -> {
                String ta = Conv.str(a.get("nombre_tecnico")), tb = Conv.str(b.get("nombre_tecnico"));
                if (ta.equals(tb)) return Conv.str(b.get("fecha_visita")).compareTo(Conv.str(a.get("fecha_visita")));
                return ta.compareTo(tb);
            });
            return r;
        } catch (Exception e) {
            log.error("Error en reporte técnico: {}", e.getMessage());
            return List.of();
        }
    }

    // ================================================================ VISTA

    @RequestMapping(params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> index(Req req) {
        Map<String, Object> filtros = new LinkedHashMap<>(Map.of("id_tecnico", "", "fecha_inicio", F.hoy().substring(0, 8) + "01", "fecha_fin", F.hoy()));
        List<Map<String, Object>> datos = List.of(), excel = List.of(), global = List.of();
        double totalValor = 0;
        String mensaje = "";
        Map<String, Object> resumenTipos = null;
        int rol = req.sesion().nivel();
        if (req.isPost()) {
            filtros.put("id_tecnico", req.str("id_tecnico", ""));
            filtros.put("fecha_inicio", req.str("fecha_inicio", ""));
            filtros.put("fecha_fin", req.str("fecha_fin", ""));
            String ini = Conv.str(filtros.get("fecha_inicio")), fin = Conv.str(filtros.get("fecha_fin"));
            if (!ini.isEmpty() && !fin.isEmpty()) {
                datos = new ArrayList<>(generarReporteServicios(Conv.str(filtros.get("id_tecnico")), ini, fin));
                excel = new ArrayList<>(generarReporteServicios("", ini, fin));
                try {
                    global = new ArrayList<>(ordenReporte.obtenerServiciosPorRango(ini, fin, "todos"));
                } catch (Exception e) {
                    log.error("Error obteniendo copia ordenReporte: {}", e.getMessage());
                }
                if (rol == 5) {
                    datos = ceros(datos, false);
                    excel = ceros(excel, false);
                    global = ceros(global, true);
                }
                for (var r : datos) totalValor += Conv.toDouble(r.get("valor_servicio"));
                resumenTipos = ResumenTiposServicio.resumir(datos);
                if (datos.isEmpty()) mensaje = "No se encontraron servicios para la vista en ese rango.";
            } else {
                mensaje = "Por favor selecciona el rango de fechas.";
            }
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Reporte de Servicios por Técnico");
        r.put("rolActual", rol);
        r.put("filtros", filtros);
        r.put("listaTecnicos", db.list("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
        r.put("listaFestivos", festivos(Conv.str(filtros.get("fecha_inicio")), Conv.str(filtros.get("fecha_fin"))));
        r.put("datosReporte", datos);
        r.put("datosExcel", excel);
        r.put("datosServiciosGlobal", global);
        r.put("totalValor", totalValor);
        r.put("resumenTipos", resumenTipos != null ? resumenTipos : ResumenTiposServicio.vacio());
        r.put("mensaje", mensaje);
        return r;
    }

    private static List<Map<String, Object>> ceros(List<Map<String, Object>> l, boolean viaticos) {
        List<Map<String, Object>> r = new ArrayList<>();
        for (var m : l) {
            Map<String, Object> x = new LinkedHashMap<>(m);
            x.put("valor_servicio", 0);
            if (viaticos) x.put("valor_viaticos", 0);
            r.add(x);
        }
        return r;
    }

    // ================================================================ PDF DIARIO

    @GetMapping(params = "accion=generarPDF")
    public ResponseEntity<?> generarPDF(Req req) {
        String ini = req.str("fecha_inicio", F.hoy()), fin = req.str("fecha_fin", F.hoy());
        if (!ini.matches("\\d{4}-\\d{2}-\\d{2}")) ini = F.hoy();
        if (!fin.matches("\\d{4}-\\d{2}-\\d{2}")) fin = F.hoy();
        List<Map<String, Object>> datos = generarReporteServicios(req.str("id_tecnico", ""), ini, fin);
        TreeMap<String, List<Map<String, Object>>> porDia = new TreeMap<>();
        for (var it : datos) {
            String f = !Conv.empty(it.get("fecha_visita")) ? Conv.str(it.get("fecha_visita")).substring(0, 10) : F.hoy();
            porDia.computeIfAbsent(f, k -> new ArrayList<>()).add(it);
        }
        List<Map<String, Object>> dias = new ArrayList<>();
        porDia.forEach((f, d) -> dias.add(procesarDia(d, f)));
        String logo = archivos.existe("app/logos/logoIneesSinFondo.png") ? pdf.dataUri("app/logos/logoIneesSinFondo.png") : pdf.dataUri("app/logos/logoInees.jpg");
        String html = PlantillaTecnicoPdf.render(dias, logo, ini + " al " + fin);
        try {
            byte[] contenido = pdf.generar(html);
            DateTimeFormatter dmy = DateTimeFormatter.ofPattern("dd-MM-yyyy");
            String nombre = "Reporte_Tecnico_" + LocalDate.parse(ini).format(dmy) + "_al_" + LocalDate.parse(fin).format(dmy) + ".pdf";
            return Descarga.pdf(contenido, nombre, true);
        } catch (Exception e) {
            return Descarga.html("<h1>Error generando PDF de Técnicos</h1><p>" + Conv.h(e.getMessage()) + "</p>");
        }
    }

    private static String hm(Object v) {
        String s = v == null ? "" : Conv.str(v).trim();
        return !s.isEmpty() && !"null".equals(s) && s.length() >= 5 ? s.substring(0, 5) : null;
    }

    private static int min(String hhmm) {
        return Integer.parseInt(hhmm.substring(0, 2)) * 60 + Integer.parseInt(hhmm.substring(3, 5));
    }

    private Map<String, Object> procesarDia(List<Map<String, Object>> datosDia, String fecha) {
        Map<String, Object> resumenTipos = ResumenTiposServicio.resumir(datosDia);
        LocalDate fd = LocalDate.parse(fecha);
        int dow = fd.getDayOfWeek().getValue();
        boolean esSabado = dow == 6, esDomingo = dow == 7;
        boolean esFestivo = festivos(fecha, fecha).contains(fecha);
        boolean esNoLaboral = esDomingo || esFestivo;
        int metaVerde = esSabado ? 3 : 6;
        int metaAmarillo = (int) Math.ceil(metaVerde * 2 / 3.0);
        int metaNA = esSabado ? 2 : 3;
        int metaNAAmarillo = (int) Math.ceil(metaNA * 2 / 3.0);
        boolean hayNA = false;

        TreeMap<String, Boolean> setTipos = new TreeMap<>();
        for (var it : datosDia) setTipos.put(!Conv.empty(it.get("tipo_mantenimiento")) ? Conv.str(it.get("tipo_mantenimiento")) : "SIN ESPECIFICAR", true);
        List<String> tiposCol = new ArrayList<>(setTipos.keySet());

        Map<String, Map<String, Integer>> resumen = new LinkedHashMap<>();
        Map<String, Map<String, Object>> jornada = new LinkedHashMap<>();
        Map<String, Integer> fallidosTec = new LinkedHashMap<>();
        Map<String, Boolean> naTec = new LinkedHashMap<>();
        for (var it : datosDia) {
            String tec = !Conv.empty(it.get("nombre_tecnico")) ? Conv.str(it.get("nombre_tecnico")).trim() : "Sin Nombre";
            String tipo = !Conv.empty(it.get("tipo_mantenimiento")) ? Conv.str(it.get("tipo_mantenimiento")) : "SIN ESPECIFICAR";
            if (!resumen.containsKey(tec)) {
                Map<String, Integer> fila = new LinkedHashMap<>();
                tiposCol.forEach(t -> fila.put(t, 0));
                resumen.put(tec, fila);
                Map<String, Object> j = new LinkedHashMap<>();
                j.put("entrada", null);
                j.put("salida", null);
                j.put("minutos", 0);
                jornada.put(tec, j);
                fallidosTec.put(tec, 0);
                naTec.put(tec, false);
            }
            resumen.get(tec).merge(tipo, 1, Integer::sum);
            if (esNuevaArquitectura(tipo)) {
                naTec.put(tec, true);
                hayNA = true;
            }
            if ("fallido".equals(ResumenTiposServicio.clasificar(tipo))) fallidosTec.merge(tec, 1, Integer::sum);
            Map<String, Object> j = jornada.get(tec);
            String he = hm(it.get("hora_entrada")), hs = hm(it.get("hora_salida"));
            if (he != null && (j.get("entrada") == null || he.compareTo((String) j.get("entrada")) < 0)) j.put("entrada", he);
            if (hs != null && (j.get("salida") == null || hs.compareTo((String) j.get("salida")) > 0)) j.put("salida", hs);
            String ts = it.get("tiempo_servicio") == null ? "" : Conv.str(it.get("tiempo_servicio")).trim();
            if (!ts.isEmpty() && !"null".equals(ts) && ts.contains(":")) {
                String[] p = ts.split(":");
                int m = Conv.toInt(p[0]) * 60 + Conv.toInt(p[1]);
                if (m > 0) j.put("minutos", (int) j.get("minutos") + m);
            }
        }
        for (var j : jornada.values()) {
            if ((int) j.get("minutos") == 0 && j.get("entrada") != null && j.get("salida") != null) {
                int dif = min((String) j.get("salida")) - min((String) j.get("entrada"));
                if (dif > 0) j.put("minutos", dif);
            }
        }
        for (var j : jornada.values()) {
            int m = (int) j.get("minutos");
            if (m > 60) j.put("minutos", m - 60);
            else if (m > 0) j.put("minutos", 0);
        }
        Map<String, Integer> totalesCol = new LinkedHashMap<>();
        tiposCol.forEach(t -> totalesCol.put(t, 0));
        int totalGeneral = 0;
        for (var fila : resumen.values()) {
            for (var e : fila.entrySet()) {
                totalesCol.merge(e.getKey(), e.getValue(), Integer::sum);
                totalGeneral += e.getValue();
            }
        }
        List<String> tecOrd = ordenarTecnicos(new ArrayList<>(resumen.keySet()));

        Map<String, Map<String, Object>> rend = new LinkedHashMap<>();
        for (var e : resumen.entrySet()) {
            String tec = e.getKey();
            int servicios = e.getValue().values().stream().mapToInt(Integer::intValue).sum();
            int fall = fallidosTec.getOrDefault(tec, 0);
            int sinF = Math.max(0, servicios - fall);
            boolean esNA = Boolean.TRUE.equals(naTec.get(tec));
            int meta, metaAm;
            if (esNoLaboral) {
                meta = 0;
                metaAm = 0;
            } else if (esNA) {
                meta = metaNA;
                metaAm = metaNAAmarillo;
            } else {
                meta = metaVerde;
                metaAm = metaAmarillo;
            }
            double pct = meta > 0 ? (sinF / (double) meta) * 100 : 0;
            double pctF = meta > 0 ? (fall / (double) meta) * 100 : 0;
            double solido = Math.min(pct, 100);
            double anchoF = Math.min(Math.max(0, 100 - solido), pctF);
            String color, txt, sem;
            if (meta <= 0) {
                color = "bg-slate-300";
                txt = "text-slate-500";
                sem = "Sin meta";
            } else if (sinF >= meta) {
                color = "bg-emerald-500";
                txt = "text-emerald-700";
                sem = "Cumple meta";
            } else if (sinF >= metaAm) {
                color = "bg-amber-400";
                txt = "text-amber-700";
                sem = "Por debajo";
            } else {
                color = "bg-rose-500";
                txt = "text-rose-700";
                sem = "Bajo meta";
            }
            Map<String, Object> r = new LinkedHashMap<>();
            r.put("servicios", servicios);
            r.put("sin_fallidos", sinF);
            r.put("fallidos", fall);
            r.put("meta", meta);
            r.put("meta_amarillo", metaAm);
            r.put("es_na", esNA);
            r.put("porcentaje", solido);
            r.put("porcentaje_real", pct);
            r.put("porcentaje_fallidos", pctF);
            r.put("ancho_fallidos", anchoF);
            r.put("color", color);
            r.put("texto", txt);
            r.put("semaforo", sem);
            rend.put(tec, r);
        }

        // Fallidos por delegación / cliente
        Map<String, Map<String, Integer>> fall = new LinkedHashMap<>();
        Map<String, Map<String, List<String>>> puntos = new LinkedHashMap<>();
        Map<String, Map<String, LinkedHashSet<String>>> novs = new LinkedHashMap<>();
        int totalFallidos = 0;
        for (var it : datosDia) {
            if (!Conv.str(it.get("tipo_mantenimiento")).toUpperCase().contains("FALLID")) continue;
            String del = !Conv.empty(it.get("delegacion")) ? Conv.str(it.get("delegacion")) : "SIN DELEGACIÓN";
            String cli = !Conv.empty(it.get("nombre_cliente")) ? Conv.str(it.get("nombre_cliente")) : "Sin Cliente";
            String pun = !Conv.empty(it.get("nombre_punto")) ? Conv.str(it.get("nombre_punto")) : "Sin Punto";
            fall.computeIfAbsent(del, k -> new LinkedHashMap<>()).merge(cli, 1, Integer::sum);
            List<String> lp = puntos.computeIfAbsent(del, k -> new LinkedHashMap<>()).computeIfAbsent(cli, k -> new ArrayList<>());
            if (!lp.contains(pun)) lp.add(pun);
            String nov = Conv.str(it.get("novedades")).trim();
            if (!nov.isEmpty()) {
                var set = novs.computeIfAbsent(del, k -> new LinkedHashMap<>()).computeIfAbsent(cli, k -> new LinkedHashSet<>());
                for (String n : nov.split(",")) if (!n.trim().isEmpty()) set.add(n.trim());
            }
            totalFallidos++;
        }
        TreeMap<String, List<Map<String, Object>>> fallidosLista = new TreeMap<>();
        for (var e : fall.entrySet()) {
            String del = e.getKey();
            List<Map.Entry<String, Integer>> ent = new ArrayList<>(e.getValue().entrySet());
            ent.sort(Map.Entry.<String, Integer>comparingByValue().reversed()); // arsort (estable)
            List<Map<String, Object>> items = new ArrayList<>();
            for (var c : ent) {
                List<String> ps = puntos.getOrDefault(del, Map.of()).getOrDefault(c.getKey(), List.of());
                var ns = novs.getOrDefault(del, Map.of()).getOrDefault(c.getKey(), new LinkedHashSet<>());
                Map<String, Object> i = new LinkedHashMap<>();
                i.put("cliente", c.getKey());
                i.put("punto", ps.isEmpty() ? "Sin Punto" : String.join(", ", ps));
                i.put("total", c.getValue());
                i.put("novedad", ns.isEmpty() ? "" : String.join(", ", ns));
                items.add(i);
            }
            fallidosLista.put(del, items);
        }
        int totalHoras = jornada.values().stream().mapToInt(j -> (int) j.get("minutos")).sum();
        Map<String, Map<String, Object>> desp = calcularDesplazamientos(datosDia);
        int totalDesp = desp.values().stream().mapToInt(d -> (int) d.get("total_min")).sum();

        Map<String, Object> r = new LinkedHashMap<>();
        r.put("fecha", fecha);
        r.put("rangoInicio", F.dmy(fecha));
        r.put("diaSemana", nombreDiaSemana(fd));
        r.put("esFestivo", esFestivo);
        r.put("esSabado", esSabado);
        r.put("esDomingo", esDomingo);
        r.put("esNoLaboral", esNoLaboral);
        r.put("metaVerde", metaVerde);
        r.put("metaAmarillo", metaAmarillo);
        r.put("metaNuevaArquitectura", metaNA);
        r.put("hayNuevaArquitectura", hayNA);
        r.put("resumenTipos", resumenTipos);
        r.put("resumen", resumen);
        r.put("jornada", jornada);
        r.put("rendimiento", rend);
        r.put("tiposColumnas", tiposCol);
        r.put("tecnicosOrdenados", tecOrd);
        r.put("totalesColumna", totalesCol);
        r.put("totalGeneral", totalGeneral);
        r.put("totalHorasDia", totalHoras);
        r.put("desplazamientos", desp);
        r.put("totalDesplazamientoDia", totalDesp);
        r.put("fallidos", fallidosLista);
        r.put("totalFallidos", totalFallidos);
        return r;
    }

    private Map<String, Map<String, Object>> calcularDesplazamientos(List<Map<String, Object>> datosDia) {
        Map<String, List<String[]>> porTec = new LinkedHashMap<>();
        for (var it : datosDia) {
            String tec = !Conv.empty(it.get("nombre_tecnico")) ? Conv.str(it.get("nombre_tecnico")).trim() : "Sin Nombre";
            porTec.computeIfAbsent(tec, k -> new ArrayList<>()).add(new String[] { hm(it.get("hora_entrada")), hm(it.get("hora_salida")) });
        }
        Map<String, Map<String, Object>> res = new LinkedHashMap<>();
        for (var e : porTec.entrySet()) {
            List<String[]> sv = new ArrayList<>(e.getValue());
            sv.sort(Comparator.comparing(s -> s[0] != null ? s[0] : "99:99"));
            List<int[]> tramos = new ArrayList<>(); // {min, inicio, fin}
            String prevSalida = null;
            for (String[] s : sv) {
                if (prevSalida != null && s[0] != null) {
                    int mS = min(prevSalida), mE = min(s[0]);
                    int dif = mE - mS;
                    if (dif > 0) tramos.add(new int[] { dif, mS, mE });
                }
                prevSalida = s[1];
            }
            descontarAlmuerzo(tramos);
            int total = 0;
            List<String> txt = new ArrayList<>();
            for (int[] t : tramos) {
                if (t[0] <= 0) continue;
                total += t[0];
                txt.add(formatearMinutos(t[0]));
            }
            Map<String, Object> d = new LinkedHashMap<>();
            d.put("total_min", total);
            d.put("total_txt", formatearMinutos(total));
            d.put("tramos", txt);
            res.put(e.getKey(), d);
        }
        return res;
    }

    private static void descontarAlmuerzo(List<int[]> tramos) {
        int ini = 12 * 60, fin = 14 * 60;
        Integer obj = null;
        int mejor = 0;
        for (int i = 0; i < tramos.size(); i++) {
            int[] t = tramos.get(i);
            int solape = Math.min(t[2], fin) - Math.max(t[1], ini);
            if (solape > mejor) {
                mejor = solape;
                obj = i;
            }
        }
        if (obj == null) {
            int mejorMin = 60;
            for (int i = 0; i < tramos.size(); i++) {
                if (tramos.get(i)[0] > mejorMin) {
                    mejorMin = tramos.get(i)[0];
                    obj = i;
                }
            }
        }
        if (obj != null) tramos.get(obj)[0] = Math.max(0, tramos.get(obj)[0] - 60);
    }

    static String formatearMinutos(int m) {
        if (m <= 0) return "--";
        int h = m / 60, mm = m % 60;
        if (h > 0 && mm > 0) return h + "h " + mm + "m";
        return h > 0 ? h + "h" : mm + "m";
    }

    private static boolean esNuevaArquitectura(String tipo) {
        String t = Normalizer.normalize(Conv.str(tipo).trim().toUpperCase(), Normalizer.Form.NFD).replaceAll("\\p{M}", "");
        return t.contains("NUEVA ARQUITECTURA") || t.contains("NUEVAARQUITECTURA");
    }

    private static final String[] DIAS = { "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo" };
    private static final String[] MESES = { "enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre" };

    private static String nombreDiaSemana(LocalDate d) {
        return DIAS[d.getDayOfWeek().getValue() - 1] + ", " + d.getDayOfMonth() + " de " + MESES[d.getMonthValue() - 1] + " de " + d.getYear();
    }

    static List<String> ordenarTecnicos(List<String> nombres) {
        nombres.sort((a, b) -> {
            int ia = indice(a), ib = indice(b);
            if (ia == ib) return a.compareTo(b);
            return ia < ib ? -1 : 1;
        });
        return nombres;
    }

    private static int indice(String nombre) {
        String n = (nombre + "NFD").toUpperCase(); // réplica literal del strtr() del PHP
        for (int i = 0; i < ORDEN_PERSONALIZADO.size(); i++) if (n.contains(ORDEN_PERSONALIZADO.get(i))) return i;
        return 999;
    }

    static String ahora() {
        return LocalDateTime.now().format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm"));
    }
}
