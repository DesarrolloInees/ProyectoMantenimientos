package co.inees.mantenimientos.modulos.asistencia;

import java.io.BufferedReader;
import java.io.StringReader;
import java.nio.ByteBuffer;
import java.nio.charset.CharacterCodingException;
import java.nio.charset.Charset;
import java.nio.charset.CodingErrorAction;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.time.DayOfWeek;
import java.time.LocalDate;
import java.time.LocalTime;
import java.time.format.DateTimeFormatter;
import java.time.temporal.ChronoUnit;
import java.time.temporal.IsoFields;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;
import java.util.TreeMap;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Excel;
import co.inees.mantenimientos.core.Excel.E;
import co.inees.mantenimientos.core.Req;
import co.inees.mantenimientos.core.Sesion;
import tools.jackson.databind.json.JsonMapper;

/**
 * Port de asistenciaControlador.php + asistenciaModelo.php: procesa el CSV del huellero, lo cruza con
 * empleados y servicios de la BD, guarda la edición en vivo en sesión y genera el Excel "Consolidado
 * Nómina Mensual (con recargos)" con las mismas fórmulas de la plantilla.
 */
@RestController
public class AsistenciaController {

    private static final JsonMapper JSON = JsonMapper.builder().build();

    static final double TASA_HED = 0.25, TASA_HEN = 0.75, TASA_RN = 0.35, TASA_RDF = 0.90, TASA_HEDDF = 1.15,
            TASA_HENDF = 1.65, TASA_RNDF = 1.25;
    static final int DIVISOR_HORAS_MES = 210;
    static final Map<String, String> HORARIO_DEFECTO = new LinkedHashMap<>();
    static {
        HORARIO_DEFECTO.put("lv", "09:00");
        HORARIO_DEFECTO.put("sab", "04:00");
        HORARIO_DEFECTO.put("tope", "02:00");
        HORARIO_DEFECTO.put("nocturna", "19:00");
        HORARIO_DEFECTO.put("turno", "REAL");
    }
    static final String[] CAMPOS_RECARGOS = { "rec_nocturno", "rec_domfest", "hed_df", "hen_df", "rec_noct_df" };

    private static final String SES_DATOS = "datos_asistencia_procesados";
    private static final String SES_SALARIOS = "salarios_empleados";
    private static final String SES_CONFIG = "config_empleados";
    private static final String[] DIAS_ES = { "", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo" };

    private final Db db;

    public AsistenciaController(Db db) {
        this.db = db;
    }

    // ================================================================ modelo

    private List<Map<String, Object>> obtenerEmpleadosActivos() {
        try {
            return db.list("""
                    SELECT t.nombre_tecnico as nombre_bd, 'Técnico' as cargo
                    FROM tecnico t
                    LEFT JOIN usuarios u ON t.usuario_id = u.usuario_id
                    WHERE t.estado = 1 AND (u.estado = 'activo' OR u.estado IS NULL)
                    UNION
                    SELECT nombre as nombre_bd, cargo
                    FROM usuarios
                    WHERE estado = 'activo'""", Map.of());
        } catch (Exception e) {
            return db.list("SELECT t.nombre_tecnico as nombre_bd, 'Técnico' as cargo FROM tecnico t WHERE t.estado = 1", Map.of());
        }
    }

    private List<Map<String, Object>> obtenerResumenServicios(String ini, String fin) {
        try {
            return db.list("""
                    SELECT t.nombre_tecnico as nombre_bd,
                           DATE(os.fecha_visita) as fecha_ymd,
                           MIN(os.hora_entrada) as entrada_srv,
                           MAX(os.hora_salida) as salida_srv,
                           COUNT(os.id_ordenes_servicio) as cant_servicios
                    FROM ordenes_servicio os
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    WHERE os.fecha_visita BETWEEN :ini AND :fin
                        AND os.estado != 0
                    GROUP BY t.id_tecnico, DATE(os.fecha_visita)""", Map.of("ini", ini, "fin", fin));
        } catch (Exception e) {
            return List.of();
        }
    }

    private Set<String> obtenerFestivos(String ini, String fin) {
        Set<String> r = new HashSet<>();
        try {
            for (Map<String, Object> f : db.list("SELECT fecha FROM dias_festivos WHERE fecha BETWEEN :ini AND :fin",
                    Map.of("ini", ini, "fin", fin))) {
                String s = Conv.str(f.get("fecha"));
                if (s.length() >= 10) r.add(s.substring(0, 10));
            }
        } catch (Exception ignored) {
        }
        return r;
    }

    // ================================================================ index

    @GetMapping(value = "/api/asistencia", params = "accion=index")
    public Map<String, Object> index() {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Procesador de Asistencias (Huellero + Servicios)");
        r.put("tasasDefecto", tasasPorDefecto());
        r.put("horarioDefecto", HORARIO_DEFECTO);
        r.put("divisor", DIVISOR_HORAS_MES);
        return r;
    }

    private static Map<String, Object> tasasPorDefecto() {
        Map<String, Object> t = new LinkedHashMap<>();
        t.put("hed", TASA_HED);
        t.put("hen", TASA_HEN);
        t.put("rn", TASA_RN);
        t.put("rdf", TASA_RDF);
        t.put("heddf", TASA_HEDDF);
        t.put("hendf", TASA_HENDF);
        t.put("rndf", TASA_RNDF);
        return t;
    }

    // ================================================================ utilidades de texto / hora

    /** normalizarTexto(): quita tildes (respeta la Ñ) y pasa a mayúsculas. */
    static String normalizarTexto(String s) {
        s = s == null ? "" : s.trim();
        StringBuilder b = new StringBuilder(s.length());
        for (char ch : s.toCharArray()) {
            b.append(switch (ch) {
                case 'á', 'à', 'ä' -> 'a';
                case 'é', 'è', 'ë' -> 'e';
                case 'í', 'ì', 'ï' -> 'i';
                case 'ó', 'ò', 'ö' -> 'o';
                case 'ú', 'ù', 'ü' -> 'u';
                case 'Á', 'À', 'Ä' -> 'A';
                case 'É', 'È', 'Ë' -> 'E';
                case 'Í', 'Ì', 'Ï' -> 'I';
                case 'Ó', 'Ò', 'Ö' -> 'O';
                case 'Ú', 'Ù', 'Ü' -> 'U';
                default -> ch;
            });
        }
        return b.toString().toUpperCase(Locale.ROOT);
    }

    /** Porcentaje de similar_text() de PHP (algoritmo de Oliver sobre bytes UTF-8). */
    static double similarText(String a, String b) {
        byte[] s1 = a.getBytes(StandardCharsets.UTF_8), s2 = b.getBytes(StandardCharsets.UTF_8);
        if (s1.length + s2.length == 0) return 0;
        return simChars(s1, 0, s1.length, s2, 0, s2.length) * 2.0 * 100.0 / (s1.length + s2.length);
    }

    private static int simChars(byte[] s1, int o1, int l1, byte[] s2, int o2, int l2) {
        int max = 0, p1 = 0, p2 = 0;
        for (int i = 0; i < l1; i++) {
            for (int j = 0; j < l2; j++) {
                int k = 0;
                while (i + k < l1 && j + k < l2 && s1[o1 + i + k] == s2[o2 + j + k]) k++;
                if (k > max) {
                    max = k;
                    p1 = i;
                    p2 = j;
                }
            }
        }
        if (max == 0) return 0;
        int sum = max;
        if (p1 > 0 && p2 > 0) sum += simChars(s1, o1, p1, s2, o2, p2);
        if (p1 + max < l1 && p2 + max < l2)
            sum += simChars(s1, o1 + p1 + max, l1 - p1 - max, s2, o2 + p2 + max, l2 - p2 - max);
        return sum;
    }

    private static final Pattern HORA = Pattern.compile("^\\s*(\\d{1,2}):(\\d{2})(?::(\\d{2}))?\\s*([aApP])?\\.?\\s*[mM]?\\.?\\s*$");

    /** strtotime() de una hora: segundos del día, o null si no se reconoce. */
    static Integer segundos(String h) {
        if (h == null) return null;
        Matcher m = HORA.matcher(h.replace(' ', ' '));
        if (!m.matches()) return null;
        int hh = Integer.parseInt(m.group(1)), mm = Integer.parseInt(m.group(2));
        int ss = m.group(3) != null ? Integer.parseInt(m.group(3)) : 0;
        if (m.group(4) != null) {
            boolean pm = m.group(4).equalsIgnoreCase("p");
            if (hh == 12) hh = pm ? 12 : 0;
            else if (pm) hh += 12;
        }
        if (hh > 23 || mm > 59 || ss > 59) return null;
        return hh * 3600 + mm * 60 + ss;
    }

    /** date('H:i', strtotime($h)) */
    private static String hi(String h) {
        Integer s = segundos(h);
        if (s == null) return "00:00";
        return String.format("%02d:%02d", s / 3600, (s % 3600) / 60);
    }

    /** TIME de la BD → "HH:MM:SS" (como lo entrega PDO). */
    private static String horaBd(Object o) {
        if (o == null) return null;
        if (o instanceof LocalTime t) return t.format(DateTimeFormatter.ofPattern("HH:mm:ss"));
        String s = Conv.str(o);
        return s.isEmpty() ? null : s;
    }

    /** strtotime() de la fecha del huellero (d-m-Y, Y-m-d, con o sin hora). */
    static LocalDate fecha(String f) {
        String s = f.replace('/', '-').trim();
        int sp = s.indexOf(' ');
        if (sp > 0) s = s.substring(0, sp);
        Matcher m = Pattern.compile("^(\\d{4})-(\\d{1,2})-(\\d{1,2})$").matcher(s);
        try {
            if (m.matches()) return LocalDate.of(Integer.parseInt(m.group(1)), Integer.parseInt(m.group(2)), Integer.parseInt(m.group(3)));
            m = Pattern.compile("^(\\d{1,2})-(\\d{1,2})-(\\d{4})$").matcher(s);
            if (m.matches()) return LocalDate.of(Integer.parseInt(m.group(3)), Integer.parseInt(m.group(2)), Integer.parseInt(m.group(1)));
            m = Pattern.compile("^(\\d{1,2})-(\\d{1,2})-(\\d{2})$").matcher(s);
            if (m.matches()) {
                // strtotime interpreta "dd-mm-yy" como "yy-mm-dd"
                return LocalDate.of(2000 + Integer.parseInt(m.group(1)), Integer.parseInt(m.group(2)), Integer.parseInt(m.group(3)));
            }
        } catch (Exception ignored) {
        }
        return null;
    }

    private static String md5(String s) {
        try {
            byte[] d = MessageDigest.getInstance("MD5").digest(s.getBytes(StandardCharsets.UTF_8));
            StringBuilder b = new StringBuilder();
            for (byte x : d) b.append(String.format("%02x", x));
            return b.toString();
        } catch (Exception e) {
            throw new IllegalStateException(e);
        }
    }

    /** Lee el CSV respetando la codificación (UTF-8 o Windows-1252, como mb_convert_encoding). */
    private static String decodificar(byte[] bytes) {
        try {
            return StandardCharsets.UTF_8.newDecoder().onMalformedInput(CodingErrorAction.REPORT)
                    .onUnmappableCharacter(CodingErrorAction.REPORT).decode(ByteBuffer.wrap(bytes)).toString();
        } catch (CharacterCodingException e) {
            return new String(bytes, Charset.forName("windows-1252"));
        }
    }

    /** fgetcsv($gestor, 10000, ",") de una línea (con comillas). */
    private static List<String> csv(String linea) {
        List<String> r = new ArrayList<>();
        StringBuilder cur = new StringBuilder();
        boolean q = false;
        for (int i = 0; i < linea.length(); i++) {
            char c = linea.charAt(i);
            if (q) {
                if (c == '"') {
                    if (i + 1 < linea.length() && linea.charAt(i + 1) == '"') {
                        cur.append('"');
                        i++;
                    } else q = false;
                } else cur.append(c);
            } else if (c == '"') q = true;
            else if (c == ',') {
                r.add(cur.toString());
                cur.setLength(0);
            } else cur.append(c);
        }
        r.add(cur.toString());
        return r;
    }

    // ================================================================ procesarArchivo

    @PostMapping(value = "/api/asistencia", params = "accion=procesarArchivo")
    public Map<String, Object> procesarArchivo(Req req) {
        MultipartFile archivo = req.file("archivo_huellero");
        if (archivo == null) return Map.of("exito", false, "error", "No se recibió archivo.");
        try {
            String nombreArchivo = archivo.getOriginalFilename() == null ? "" : archivo.getOriginalFilename();
            String ext = nombreArchivo.contains(".") ? nombreArchivo.substring(nombreArchivo.lastIndexOf('.') + 1).toLowerCase(Locale.ROOT) : "";

            Map<String, Map<String, String[]>> csvAgrupado = new LinkedHashMap<>();
            LocalDate min = null, max = null;

            if (ext.equals("csv")) {
                String texto = decodificar(archivo.getBytes());
                if (texto.startsWith("﻿")) texto = texto.substring(1);
                BufferedReader br = new BufferedReader(new StringReader(texto));
                String linea;
                while ((linea = br.readLine()) != null) {
                    if (linea.isBlank()) continue;
                    List<String> fila = csv(linea);
                    if (fila.size() == 1 && fila.get(0).contains(",")) fila = List.of(fila.get(0).split(",", -1));
                    if (fila.size() < 3) continue;
                    String nombre = fila.get(0).trim();
                    String fecha = fila.get(1).trim();
                    String hora = fila.get(2).trim();
                    if (nombre.toLowerCase(Locale.ROOT).contains("empleado") || fecha.toLowerCase(Locale.ROOT).contains("fecha")) continue;
                    if (Conv.empty(nombre) || Conv.empty(fecha) || Conv.empty(hora)) continue;
                    LocalDate d = fecha(fecha);
                    if (d == null) continue;
                    String ymd = d.toString();
                    if (min == null || d.isBefore(min)) min = d;
                    if (max == null || d.isAfter(max)) max = d;
                    Map<String, String[]> porFecha = csvAgrupado.computeIfAbsent(nombre, k -> new LinkedHashMap<>());
                    String[] es = porFecha.get(ymd);
                    if (es == null) {
                        porFecha.put(ymd, new String[] { hora, hora });
                    } else {
                        Integer t = segundos(hora), te = segundos(es[0]), ts = segundos(es[1]);
                        int tv = t == null ? 0 : t;
                        if (tv < (te == null ? 0 : te)) es[0] = hora;
                        if (tv > (ts == null ? 0 : ts)) es[1] = hora;
                    }
                }
            }

            if (min == null) throw new IllegalStateException("El archivo CSV está vacío o tiene un formato de fecha irreconocible.");

            String fechaInicio = min.toString(), fechaFin = max.toString();
            List<String> rango = new ArrayList<>();
            for (LocalDate d = min; !d.isAfter(max); d = d.plusDays(1)) rango.add(d.toString());

            // Matriz de empleados oficiales
            Map<String, Persona> oficiales = new LinkedHashMap<>();
            for (Map<String, Object> emp : obtenerEmpleadosActivos()) {
                String nombreBd = Conv.str(emp.get("nombre_bd"));
                oficiales.put(normalizarTexto(nombreBd), new Persona(nombreBd, Conv.str(emp.get("cargo")), rango));
            }

            // Match difuso CSV → oficiales
            for (Map.Entry<String, Map<String, String[]>> e : csvAgrupado.entrySet()) {
                String nomCsv = e.getKey();
                String nomLimpio = normalizarTexto(nomCsv);
                String[] partes = nomLimpio.split(" ", -1);
                String key = null;
                if (oficiales.containsKey(nomLimpio)) {
                    key = nomLimpio;
                } else {
                    double mejor = 0;
                    String candidato = null;
                    for (String empClave : oficiales.keySet()) {
                        boolean todas = true;
                        for (String p : partes) {
                            if (Conv.empty(p)) continue;
                            if (!empClave.contains(p)) {
                                todas = false;
                                break;
                            }
                        }
                        if (todas) {
                            key = empClave;
                            break;
                        }
                        double pc = similarText(nomLimpio, empClave);
                        if (pc > mejor) {
                            mejor = pc;
                            candidato = empClave;
                        }
                    }
                    if (key == null && mejor >= 85) key = candidato;
                }
                if (key == null) {
                    key = nomLimpio;
                    oficiales.put(key, new Persona(nomCsv, "No registrado en BD", rango));
                }
                Persona p = oficiales.get(key);
                for (Map.Entry<String, String[]> f : e.getValue().entrySet()) {
                    Dia d = p.fechas.computeIfAbsent(f.getKey(), k -> new Dia());
                    d.entrada = f.getValue()[0];
                    d.salida = f.getValue()[1];
                }
            }

            // Servicios de la app en el rango
            for (Map<String, Object> srv : obtenerResumenServicios(fechaInicio, fechaFin)) {
                String nomSrv = normalizarTexto(Conv.str(srv.get("nombre_bd")));
                String ymd = Conv.str(srv.get("fecha_ymd"));
                if (ymd.length() > 10) ymd = ymd.substring(0, 10);
                String key = null;
                if (oficiales.containsKey(nomSrv)) key = nomSrv;
                else {
                    for (String empClave : oficiales.keySet()) {
                        if (similarText(empClave, nomSrv) > 85) {
                            key = empClave;
                            break;
                        }
                    }
                }
                if (key == null) continue;
                Dia d = oficiales.get(key).fechas.get(ymd);
                if (d == null) continue;
                String entSrv = horaBd(srv.get("entrada_srv")), salSrv = horaBd(srv.get("salida_srv"));
                String curE = d.entrada, curS = d.salida;
                if (!Conv.empty(entSrv)) {
                    if (Conv.empty(curE) || cmp(segundos(entSrv), segundos(curE)) < 0) d.entrada = entSrv;
                }
                if (!Conv.empty(salSrv)) {
                    if (Conv.empty(curS) || cmp(segundos(salSrv), segundos(curS)) > 0 || java.util.Objects.equals(curS, curE)) d.salida = salSrv;
                }
                d.servicios = Conv.toInt(srv.get("cant_servicios"));
            }

            Set<String> festivos = obtenerFestivos(fechaInicio, fechaFin);
            DateTimeFormatter dmy = DateTimeFormatter.ofPattern("dd/MM/yyyy");
            List<Map<String, Object>> resultado = new ArrayList<>();
            for (Map.Entry<String, Persona> pe : oficiales.entrySet()) {
                Persona p = pe.getValue();
                for (Map.Entry<String, Dia> de : p.fechas.entrySet()) {
                    String ymd = de.getKey();
                    Dia d = de.getValue();
                    LocalDate fd = LocalDate.parse(ymd);
                    int n = fd.getDayOfWeek().getValue();
                    String entradaValor = !Conv.empty(d.entrada) ? hi(d.entrada) : "";
                    String salidaValor = !Conv.empty(d.salida) ? hi(d.salida) : "";
                    boolean salidaDistinta = !Conv.empty(d.salida) && !d.salida.equals(d.entrada);
                    Map<String, Object> r = new LinkedHashMap<>();
                    r.put("id", md5(pe.getKey() + "|" + ymd));
                    r.put("nombre", p.nombreOriginal);
                    r.put("cargo", p.cargo);
                    r.put("fecha_raw", ymd);
                    r.put("fecha_formateada", DIAS_ES[n] + " " + fd.format(dmy));
                    r.put("dom_fest", (n == 7 || festivos.contains(ymd)) ? 1 : 0);
                    r.put("entrada", !entradaValor.isEmpty() ? entradaValor : "Falta Entrada");
                    r.put("salida", salidaDistinta ? salidaValor : (d.servicios == 0 ? "Falta Salida" : entradaValor));
                    r.put("entrada_valor", entradaValor);
                    r.put("salida_valor", salidaDistinta ? salidaValor : (!entradaValor.isEmpty() && d.servicios != 0 ? entradaValor : ""));
                    r.put("servicios", d.servicios);
                    r.put("novedades", "");
                    resultado.add(r);
                }
            }

            Sesion s = req.sesion();
            s.set(SES_DATOS, resultado);
            s.set(SES_SALARIOS, new LinkedHashMap<String, Object>());
            s.set(SES_CONFIG, new LinkedHashMap<String, Object>());

            Map<String, Object> resp = new LinkedHashMap<>();
            resp.put("exito", true);
            resp.put("mensaje", "Generado automáticamente del " + min.format(dmy) + " al " + max.format(dmy));
            resp.put("datos", resultado);
            resp.put("fecha_inicio_detectada", fechaInicio);
            resp.put("fecha_fin_detectada", fechaFin);
            return resp;
        } catch (Exception e) {
            return Map.of("exito", false, "error", "Error: " + e.getMessage());
        }
    }

    private static int cmp(Integer a, Integer b) {
        return Integer.compare(a == null ? 0 : a, b == null ? 0 : b);
    }

    private static final class Dia {
        String entrada, salida;
        int servicios;
    }

    private static final class Persona {
        final String nombreOriginal, cargo;
        final Map<String, Dia> fechas = new LinkedHashMap<>();

        Persona(String n, String c, List<String> rango) {
            nombreOriginal = n;
            cargo = c;
            for (String f : rango) fechas.put(f, new Dia());
        }
    }

    // ================================================================ guardarEdicion

    @SuppressWarnings("unchecked")
    @PostMapping(value = "/api/asistencia", params = "accion=guardarEdicion")
    public Map<String, Object> guardarEdicion(Req req) {
        Sesion s = req.sesion();
        try {
            Object o = s.get(SES_DATOS);
            if (!(o instanceof List<?>)) throw new IllegalStateException("No hay una sesión de procesamiento activa. Vuelve a subir el archivo.");
            List<Map<String, Object>> datos = (List<Map<String, Object>>) o;
            String crudo = req.str("datos", "");
            if (crudo.isEmpty()) throw new IllegalStateException("No se recibieron datos para guardar.");
            Map<String, Object> payload;
            try {
                payload = JSON.readValue(crudo, Map.class);
            } catch (Exception e) {
                payload = null;
            }
            if (payload == null || !payload.containsKey("registros")) throw new IllegalStateException("Formato de datos inválido.");

            Map<String, Integer> porId = new HashMap<>();
            for (int i = 0; i < datos.size(); i++) porId.put(Conv.str(datos.get(i).get("id")), i);

            for (Object x : Conv.list(payload.get("registros"))) {
                if (!(x instanceof Map<?, ?>)) continue;
                Map<String, Object> edit = (Map<String, Object>) x;
                Integer i = edit.get("id") == null ? null : porId.get(Conv.str(edit.get("id")));
                if (i == null) continue;
                Map<String, Object> reg = datos.get(i);
                String ent = Conv.str(edit.get("entrada_valor")).trim();
                String sal = Conv.str(edit.get("salida_valor")).trim();
                reg.put("entrada_valor", ent);
                reg.put("salida_valor", sal);
                reg.put("entrada", !ent.isEmpty() ? ent : "Falta Entrada");
                reg.put("salida", !sal.isEmpty() ? sal : "Falta Salida");
                reg.put("servicios", Conv.toInt(edit.get("servicios")));
                reg.put("novedades", Conv.str(edit.get("novedades")).trim());
                for (String campo : CAMPOS_RECARGOS) reg.put(campo, normalizarHoraManual(edit.get(campo)));
            }

            Map<String, Object> config = new LinkedHashMap<>();
            if (payload.get("empleados") instanceof Map<?, ?> emps) {
                for (Map.Entry<?, ?> e : emps.entrySet()) {
                    if (e.getValue() instanceof Map<?, ?>) config.put(String.valueOf(e.getKey()), e.getValue());
                }
            }
            Map<String, Object> salarios = new LinkedHashMap<>();
            if (payload.get("salarios") instanceof Map<?, ?> sal) {
                for (Map.Entry<?, ?> e : sal.entrySet()) {
                    String nombre = String.valueOf(e.getKey());
                    salarios.put(nombre, Conv.toDouble(e.getValue()));
                    if (!config.containsKey(nombre)) config.put(nombre, new LinkedHashMap<>(Map.of("salario", Conv.toDouble(e.getValue()))));
                }
            }
            for (Map.Entry<String, Object> e : config.entrySet()) {
                Object sv = ((Map<String, Object>) e.getValue()).get("salario");
                if (esNumerico(sv)) salarios.put(e.getKey(), Conv.toDouble(sv));
            }
            s.set(SES_SALARIOS, salarios);
            s.set(SES_CONFIG, config);
            s.set(SES_DATOS, datos);
            return Map.of("exito", true, "mensaje", "Cambios guardados. Ya puedes descargar el reporte.");
        } catch (Exception e) {
            return Map.of("exito", false, "error", String.valueOf(e.getMessage()));
        }
    }

    private static boolean esNumerico(Object o) {
        if (o == null || o instanceof Boolean) return false;
        if (o instanceof Number) return true;
        return Conv.esNumero(String.valueOf(o).trim());
    }

    /** "HH:MM" o "" (acepta 7:00, 07:00, 07:00:00 y descarta "FALTA ..."). */
    static String normalizarHoraManual(Object v) {
        String s = v == null ? "" : String.valueOf(v).trim();
        if (s.isEmpty() || s.toLowerCase(Locale.ROOT).contains("falta")) return "";
        Matcher m = Pattern.compile("^(\\d{1,2}):(\\d{2})").matcher(s);
        if (m.find()) return String.format("%02d", Integer.parseInt(m.group(1)) % 24) + ":" + m.group(2);
        return "";
    }

    /** "HH:MM" → fracción de día de Excel (null si falta). */
    static Double fraccionHora(Object t) {
        String s = t == null ? "" : String.valueOf(t);
        if (s.isEmpty() || s.toLowerCase(Locale.ROOT).contains("falta")) return null;
        String[] p = s.split(":");
        int h = p.length > 0 ? (int) Conv.toLong(p[0]) : 0;
        int m = p.length > 1 ? (int) Conv.toLong(p[1]) : 0;
        return (h + m / 60.0) / 24.0;
    }

    private static boolean esCargoTecnico(String cargo) {
        String c = cargo == null ? "" : cargo.trim().toUpperCase(Locale.ROOT);
        return c.contains("TÉCNICO") || c.contains("TECNICO");
    }

    // ================================================================ descargarExcel

    @SuppressWarnings("unchecked")
    @GetMapping(value = "/api/asistencia", params = "accion=descargarExcel")
    public ResponseEntity<byte[]> descargarExcel(Req req) {
        Sesion s = req.sesion();
        if (!(s.get(SES_DATOS) instanceof List<?> l))
            return ResponseEntity.ok().contentType(MediaType.parseMediaType("text/html;charset=UTF-8"))
                    .body("No hay datos procesados para descargar.".getBytes(StandardCharsets.UTF_8));
        List<Map<String, Object>> datos = (List<Map<String, Object>>) l;
        Map<String, Object> configEmpleados = s.get(SES_CONFIG) instanceof Map<?, ?> m ? (Map<String, Object>) m : Map.of();
        Map<String, Object> salariosViejos = s.get(SES_SALARIOS) instanceof Map<?, ?> m2 ? (Map<String, Object>) m2 : Map.of();

        String ini = null, fin = null;
        for (Map<String, Object> r : datos) {
            String f = Conv.str(r.get("fecha_raw"));
            if (ini == null || f.compareTo(ini) < 0) ini = f;
            if (fin == null || f.compareTo(fin) > 0) fin = f;
        }
        if (ini == null) ini = fin = LocalDate.now().toString();
        Set<String> festivos = obtenerFestivos(ini, fin);

        Map<String, List<Map<String, Object>>> empleados = new LinkedHashMap<>();
        for (Map<String, Object> r : datos) empleados.computeIfAbsent(Conv.str(r.get("nombre")), k -> new ArrayList<>()).add(r);

        try (Excel x = new Excel()) {
            boolean primera = true;
            Set<String> usados = new HashSet<>();
            List<Map<String, String>> resumen = new ArrayList<>();
            for (Map.Entry<String, List<Map<String, Object>>> e : empleados.entrySet()) {
                String nombre = e.getKey();
                List<Map<String, Object>> registros = e.getValue();
                String cargo = Conv.str(registros.get(0).get("cargo"));
                Map<String, Object> config = configEmpleado(nombre, configEmpleados, salariosViejos, cargo);

                String titulo = nombre.replaceAll("[^a-zA-Z0-9\\sñÑáéíóúÁÉÍÓÚ]", "");
                if (titulo.codePointCount(0, titulo.length()) > 30) titulo = titulo.substring(0, titulo.offsetByCodePoints(0, 30));
                if (titulo.isBlank()) titulo = "Hoja";
                String base = titulo;
                for (int k = 2; usados.contains(titulo.toLowerCase(Locale.ROOT)); k++) {
                    String suf = " (" + k + ")";
                    titulo = (base.length() + suf.length() > 31 ? base.substring(0, 31 - suf.length()) : base) + suf;
                }
                usados.add(titulo.toLowerCase(Locale.ROOT));
                if (primera) {
                    x.tituloHoja(titulo);
                    primera = false;
                } else x.nuevaHoja(titulo);

                escribirEncabezadoHoja(x, nombre, cargo, config);
                Semanas sem = escribirSemanasHoja(x, registros, festivos);
                Map<String, Object> celdas = escribirConsolidadoMes(x, sem.bloques, sem.fila + 1);
                int ff = (int) celdas.get("fila_final");
                x.estilo("A1:W" + ff, E.of().center());
                escribirAnchosColumna(x);

                Map<String, String> rs = new LinkedHashMap<>();
                rs.put("nombre", nombre);
                rs.put("hoja", titulo);
                rs.put("actividad", Conv.str(config.get("actividad")));
                rs.put("autoriza", Conv.str(config.get("autoriza")));
                rs.put("horas_extras", (String) celdas.get("horas_extras"));
                rs.put("valor_extras", (String) celdas.get("valor_extras"));
                rs.put("valor_recargos", (String) celdas.get("valor_recargos"));
                rs.put("total_general", (String) celdas.get("total_general"));
                resumen.add(rs);
            }
            if (!resumen.isEmpty()) escribirHojaResumen(x, resumen);
            x.wb().setActiveSheet(0);
            x.wb().setForceFormulaRecalculation(true);
            try {
                org.apache.poi.ss.usermodel.FormulaEvaluator ev = x.wb().getCreationHelper().createFormulaEvaluator();
                ev.evaluateAll();
            } catch (Exception ignored) {
                // Igual que setPreCalculateFormulas: si algo no se puede precalcular, Excel lo recalcula al abrir.
            }
            return Descarga.excel(x.bytes(), "Consolidado_Nomina_Mensual.xlsx");
        }
    }

    @SuppressWarnings("unchecked")
    private Map<String, Object> configEmpleado(String nombre, Map<String, Object> configEmpleados, Map<String, Object> salariosViejos, String cargo) {
        Map<String, Object> b = new LinkedHashMap<>();
        b.put("salario", Conv.toDouble(salariosViejos.get(nombre)));
        b.put("lv", HORARIO_DEFECTO.get("lv"));
        b.put("sab", HORARIO_DEFECTO.get("sab"));
        b.put("tope", HORARIO_DEFECTO.get("tope"));
        b.put("nocturna", HORARIO_DEFECTO.get("nocturna"));
        b.put("turno", esCargoTecnico(cargo) ? "REAL" : "07:00");
        b.put("actividad", "");
        b.put("autoriza", "");
        b.put("divisor", (double) DIVISOR_HORAS_MES);
        Map<String, Object> tasas = tasasPorDefecto();
        b.put("tasas", tasas);

        if (!(configEmpleados.get(nombre) instanceof Map<?, ?> cm)) return b;
        Map<String, Object> c = (Map<String, Object>) cm;
        if (esNumerico(c.get("salario"))) b.put("salario", Conv.toDouble(c.get("salario")));
        for (String campo : new String[] { "lv", "sab", "tope", "nocturna" }) {
            String h = normalizarHoraManual(c.get(campo));
            if (!h.isEmpty()) b.put(campo, h);
        }
        if (!Conv.empty(c.get("turno"))) {
            String turno = Conv.str(c.get("turno")).trim();
            if (turno.toUpperCase(Locale.ROOT).equals("REAL")) b.put("turno", "REAL");
            else {
                String h = normalizarHoraManual(turno);
                if (!h.isEmpty()) b.put("turno", h);
            }
        }
        if (c.containsKey("actividad") && c.get("actividad") != null) b.put("actividad", Conv.str(c.get("actividad")).trim());
        if (c.containsKey("autoriza") && c.get("autoriza") != null) b.put("autoriza", Conv.str(c.get("autoriza")).trim());
        if (c.get("divisor") != null && Conv.toDouble(c.get("divisor")) > 0) b.put("divisor", Conv.toDouble(c.get("divisor")));
        if (c.get("tasas") instanceof Map<?, ?> tm) {
            for (String k : tasas.keySet()) {
                Object v = tm.get(k);
                if (esNumerico(v)) tasas.put(k, Conv.toDouble(v));
            }
        }
        return b;
    }

    private static final String[][] COLUMNAS = {
            { "A", "FECHA" }, { "B", "H. ENTRADA" }, { "C", "H. SALIDA" }, { "D", "TOTAL TRABAJADO" },
            { "E", "H. EXTRA DIURNA" }, { "F", "H. EXTRA NOCTURNA" }, { "G", "DOM/FEST" },
            { "H", "VALOR BASE H.E. DIURNA (100%)" }, { "I", "RECARGO H.E. DIURNA" },
            { "J", "VALOR BASE H.E. NOCTURNA (100%)" }, { "K", "RECARGO H.E. NOCTURNA" },
            { "L", "# RECARGO NOCTURNO" }, { "M", "VALOR RECARGO NOCTURNO" }, { "N", "# RECARGO DOM/FEST" },
            { "O", "VALOR RECARGO DOM/FEST" }, { "P", "# H.E. DIURNA DOM/FEST" }, { "Q", "VALOR H.E. DIURNA DOM/FEST" },
            { "R", "# H.E. NOCTURNA DOM/FEST" }, { "S", "VALOR H.E. NOCTURNA DOM/FEST" },
            { "T", "# RECARGO NOCTURNO DOM/FEST" }, { "U", "VALOR RECARGO NOCTURNO DOM/FEST" },
            { "V", "SERVICIOS" }, { "W", "NOVEDADES" } };

    private static E blancoNegrita(String fill) {
        return E.of().bold().color("FFFFFFFF").fill(fill);
    }

    @SuppressWarnings("unchecked")
    private void escribirEncabezadoHoja(Excel x, String nombre, String cargo, Map<String, Object> config) {
        x.set("A1", "NOMBRE DEL TRABAJADOR").set("C1", nombre).set("A2", "CARGO").set("C2", cargo);
        x.set("E1", "L-V").set("F1", "S").set("G1", "LÍMITE EXTRAS").set("H1", "INICIO TURNO").set("I1", "INICIO NOCTURNA");
        x.set("E2", fraccionHora(config.get("lv")));
        x.set("F2", fraccionHora(config.get("sab")));
        x.set("G2", fraccionHora(config.get("tope")));
        x.set("I2", fraccionHora(config.get("nocturna")));
        x.estilo("E2:G2", E.of().formato("hh:mm"));
        x.estilo("I2", E.of().formato("hh:mm"));
        String turno = Conv.str(config.get("turno"));
        if (!turno.equals("REAL")) {
            Double fr = fraccionHora(turno);
            x.set("H2", fr != null ? fr : turno);
            x.estilo("H2", E.of().formato("hh:mm"));
        } else {
            x.set("H2", "REAL");
        }
        x.estilo("A1:A2", blancoNegrita("FF548235"));

        // Bloque de tarifas (Y/Z/AA)
        Map<String, Object> t = (Map<String, Object>) config.get("tasas");
        x.set("Y1", "CONCEPTO").set("Z1", "VALOR $").set("AA1", "TASA / BASE");
        x.set("Y2", "SALARIO BÁSICO").set("Z2", Conv.toDouble(config.get("salario")));
        x.set("Y3", "VALOR HORA BÁSICA").set("Z3", "=Z2/AA3").set("AA3", Conv.toDouble(config.get("divisor")));
        Object[][] filas = { { 4, "REC. H.E. DIURNA", "hed" }, { 5, "REC. H.E. NOCTURNA", "hen" },
                { 6, "REC. NOCTURNO ORDINARIO", "rn" }, { 7, "REC. DOM/FEST ORDINARIO", "rdf" },
                { 8, "REC. H.E. DIURNA DOM/FEST", "heddf" }, { 9, "REC. H.E. NOCTURNA DOM/FEST", "hendf" },
                { 10, "REC. NOCTURNO DOM/FEST", "rndf" } };
        for (Object[] f : filas) {
            int n = (int) f[0];
            x.set("Y" + n, f[1]).set("Z" + n, "=$Z$3*AA" + n).set("AA" + n, Conv.toDouble(t.get((String) f[2])));
        }
        x.estilo("Y1:AA1", blancoNegrita("FF1F4E78"));
        x.estilo("Y2:Y10", E.of().bold());
        x.estilo("Z2:Z10", E.of().formato("#,##0"));
        x.estilo("AA3", E.of().formato("#,##0"));
        x.estilo("AA4:AA10", E.of().formato("0%"));
        x.estilo("Z2", blancoNegrita("FF1F4E78"));
        x.ancho("Y", 28).ancho("Z", 14).ancho("AA", 12);
    }

    private static void escribirAnchosColumna(Excel x) {
        Object[][] a = { { "A", 25 }, { "B", 13 }, { "C", 22 }, { "D", 18 }, { "E", 14 }, { "F", 14 }, { "G", 10 }, { "H", 18 },
                { "I", 16 }, { "J", 20 }, { "K", 16 }, { "L", 16 }, { "M", 18 }, { "N", 16 }, { "O", 18 }, { "P", 16 }, { "Q", 18 },
                { "R", 16 }, { "S", 18 }, { "T", 18 }, { "U", 20 }, { "V", 12 }, { "W", 24 } };
        for (Object[] c : a) x.ancho((String) c[0], ((Integer) c[1]).doubleValue());
    }

    private record Semanas(List<Map<String, String>> bloques, int fila) {}

    private Semanas escribirSemanasHoja(Excel x, List<Map<String, Object>> registros, Set<String> festivos) {
        String[] horas = { "D", "E", "F", "L", "N", "P", "R", "T" };
        String[] dinero = { "H", "I", "J", "K", "M", "O", "Q", "S", "U" };

        // Agrupar por semana ISO (date('W')); ksort numérico como en PHP
        TreeMap<Integer, List<Map<String, Object>>> semanas = new TreeMap<>();
        List<Map<String, Object>> extra = new ArrayList<>();
        for (Map<String, Object> r : registros) {
            LocalDate d = fecha(Conv.str(r.get("fecha_raw")));
            if (d != null) semanas.computeIfAbsent(d.get(IsoFields.WEEK_OF_WEEK_BASED_YEAR), k -> new ArrayList<>()).add(r);
            else extra.add(r);
        }
        LinkedHashMap<String, List<Map<String, Object>>> orden = new LinkedHashMap<>();
        for (Map.Entry<Integer, List<Map<String, Object>>> e : semanas.entrySet()) orden.put(String.format("%02d", e.getKey()), e.getValue());
        if (!extra.isEmpty()) orden.put("Extra", extra);

        List<Map<String, String>> bloques = new ArrayList<>();
        int fila = 4;
        for (Map.Entry<String, List<Map<String, Object>>> e : orden.entrySet()) {
            x.set("A" + fila, "REPORTE SEMANA " + e.getKey());
            x.merge("A" + fila + ":W" + fila);
            x.estilo("A" + fila + ":W" + fila, blancoNegrita("FF1F4E78"));
            fila++;

            for (String[] c : COLUMNAS) x.set(c[0] + fila, c[1]);
            x.estilo("A" + fila + ":W" + fila, blancoNegrita("FF385D22").wrap());
            fila++;

            int ini = fila;
            List<Map<String, Object>> regs = new ArrayList<>(e.getValue());
            regs.sort((a, b) -> Conv.str(a.get("fecha_raw")).compareTo(Conv.str(b.get("fecha_raw"))));
            for (Map<String, Object> r : regs) {
                escribirFilaDia(x, fila, r, festivos);
                fila++;
            }
            int finD = fila - 1;

            int fr = fila;
            x.set("C" + fr, "REPORTE SEMANAL");
            for (String c : horas) x.set(c + fr, "=SUM(" + c + ini + ":" + c + finD + ")");
            for (String c : dinero) x.set(c + fr, "=SUM(" + c + ini + ":" + c + finD + ")");
            x.set("V" + fr, "=SUM(V" + ini + ":V" + finD + ")");
            x.estilo("A" + fr + ":W" + fr, blancoNegrita("FF996600"));
            for (String c : horas) x.estilo(c + fr, E.of().formato("[h]:mm"));
            for (String c : dinero) x.estilo(c + fr, E.of().formato("#,##0"));
            fila++;

            int fHE = fila;
            filaTotal(x, fila++, "TOTAL HORAS EXTRAS A PAGAR (horas)", "=E" + fr + "+F" + fr, "FF2F5597", "[h]:mm");
            int fVE = fila;
            filaTotal(x, fila++, "TOTAL $ HORAS EXTRAS A PAGAR", "=H" + fr + "+I" + fr + "+J" + fr + "+K" + fr, "FF107C41", "#,##0");
            int fHR = fila;
            filaTotal(x, fila++, "TOTAL HORAS DOMINICALES/RECARGOS ORD. (horas)", "=N" + fr + "+T" + fr, "FFC55A11", "[h]:mm");
            int fVR = fila;
            filaTotal(x, fila, "TOTAL $ RECARGOS Y DOM/FEST (semana)", "=M" + fr + "+O" + fr + "+Q" + fr + "+S" + fr + "+U" + fr, "FF7030A0", "#,##0");
            fila += 3;

            Map<String, String> b = new LinkedHashMap<>();
            b.put("horas_trabajadas", "D" + fr);
            b.put("servicios", "V" + fr);
            b.put("horas_extras", "D" + fHE);
            b.put("valor_extras", "D" + fVE);
            b.put("horas_recargos", "D" + fHR);
            b.put("valor_recargos", "D" + fVR);
            bloques.add(b);
        }
        return new Semanas(bloques, fila);
    }

    private static void filaTotal(Excel x, int fila, String etiqueta, String formula, String color, String formato) {
        x.set("C" + fila, etiqueta).set("D" + fila, formula);
        x.estilo("C" + fila + ":D" + fila, blancoNegrita(color));
        x.estilo("D" + fila, E.of().formato(formato));
    }

    private void escribirFilaDia(Excel x, int f, Map<String, Object> reg, Set<String> festivos) {
        String refLv = "$E$2", refSab = "$F$2", refTope = "$G$2", refTurno = "$H$2", refNoct = "$I$2";
        String refHora = "$Z$3", refHed = "$Z$4", refHen = "$Z$5", refRn = "$Z$6", refRdf = "$Z$7", refHedDf = "$Z$8",
                refHenDf = "$Z$9", refRndf = "$Z$10";

        String raw = Conv.str(reg.get("fecha_raw"));
        LocalDate d = fecha(raw);
        if (d != null) {
            x.set("A" + f, (double) ChronoUnit.DAYS.between(LocalDate.of(1899, 12, 30), d));
            x.estilo("A" + f, E.of().formato("[$-es-ES]dddd dd-mm-yyyy;@"));
        } else {
            x.set("A" + f, Conv.str(reg.get("fecha_formateada")));
        }
        boolean domFest = d != null && (d.getDayOfWeek() == DayOfWeek.SUNDAY || festivos.contains(raw));
        x.set("G" + f, domFest ? 1 : 0);

        Double ve = fraccionHora(reg.containsKey("entrada_valor") && reg.get("entrada_valor") != null ? reg.get("entrada_valor") : reg.get("entrada"));
        Double vs = fraccionHora(reg.containsKey("salida_valor") && reg.get("salida_valor") != null ? reg.get("salida_valor") : reg.get("salida"));

        if (ve != null) {
            x.set("B" + f, ve);
            x.estilo("B" + f, E.of().formato("hh:mm"));
        } else x.set("B" + f, "FALTA ENT");

        if (vs != null) {
            x.set("C" + f, vs);
            x.estilo("C" + f, E.of().formato("hh:mm"));
            x.set("D" + f, "=IF(AND(ISNUMBER(B" + f + "), ISNUMBER(C" + f + ")), MAX(0, C" + f + " - IF(ISTEXT(" + refTurno + "), B" + f
                    + ", MAX(B" + f + ", " + refTurno + "))), \"\")");
            String limite = "IF(WEEKDAY(A" + f + ",2)<6, " + refLv + ", " + refSab + ")";
            String bruto = "MAX(0, D" + f + "-" + limite + ")";
            String tope = "IF(" + bruto + " > " + refTope + ", " + refTope + ", " + bruto + ")";
            x.set("F" + f, "=IF(ISNUMBER(C" + f + "), MAX(0, MIN(" + tope + ", MAX(0, C" + f + "-" + refNoct + "))), \"\")");
            x.set("E" + f, "=IF(ISNUMBER(D" + f + "), MAX(0, " + tope + " - F" + f + "), \"\")");
            x.set("H" + f, "=IF(ISNUMBER(E" + f + "), E" + f + "*24*" + refHora + ", \"\")");
            x.set("I" + f, "=IF(ISNUMBER(E" + f + "), E" + f + "*24*IF(G" + f + "=1, " + refHedDf + ", " + refHed + "), \"\")");
            x.set("J" + f, "=IF(ISNUMBER(F" + f + "), F" + f + "*24*" + refHora + ", \"\")");
            x.set("K" + f, "=IF(ISNUMBER(F" + f + "), F" + f + "*24*IF(G" + f + "=1, " + refHenDf + ", " + refHen + "), \"\")");
        } else {
            x.set("C" + f, "FALTA SALIDA");
        }

        String[][] manuales = { { "L", "rec_nocturno", refRn, "M" }, { "N", "rec_domfest", refRdf, "O" }, { "P", "hed_df", refHedDf, "Q" },
                { "R", "hen_df", refHenDf, "S" }, { "T", "rec_noct_df", refRndf, "U" } };
        for (String[] m : manuales) {
            Double fr = fraccionHora(reg.get(m[1]));
            if (fr != null) {
                x.set(m[0] + f, fr);
                x.estilo(m[0] + f, E.of().formato("[h]:mm"));
            }
            x.set(m[3] + f, "=IF(ISNUMBER(" + m[0] + f + "), " + m[0] + f + "*24*" + m[2] + ", 0)");
            x.estilo(m[3] + f, E.of().formato("#,##0"));
        }

        int serv = Conv.toInt(reg.get("servicios"));
        x.set("V" + f, serv != 0 ? (Object) serv : "");
        x.set("W" + f, Conv.str(reg.get("novedades")));

        for (String c : new String[] { "D", "E", "F" }) x.estilo(c + f, E.of().formato("[h]:mm"));
        for (String c : new String[] { "H", "I", "J", "K" }) x.estilo(c + f, E.of().formato("#,##0"));
        if (domFest) x.estilo("A" + f + ":W" + f, E.of().fill("FFFFF2CC"));
    }

    private Map<String, Object> escribirConsolidadoMes(Excel x, List<Map<String, String>> bloques, int fila) {
        x.set("B" + fila, "CONSOLIDADO FINAL DEL MES");
        x.merge("B" + fila + ":D" + fila);
        x.estilo("B" + fila + ":D" + fila, blancoNegrita("FF000000").size(12));
        fila++;

        String[][] filas = { { "horas_trabajadas", "GRAN TOTAL HORAS TRABAJADAS", "FF996600", "[h]:mm" },
                { "horas_extras", "GRAN TOTAL HORAS EXTRAS (horas)", "FF2F5597", "[h]:mm" },
                { "valor_extras", "GRAN TOTAL $ HORAS EXTRAS A PAGAR", "FF107C41", "#,##0" },
                { "horas_recargos", "GRAN TOTAL DOMINICALES/RECARGOS ORD. (horas)", "FFC55A11", "[h]:mm" },
                { "servicios", "GRAN TOTAL SERVICIOS (TICKETS)", "FF1F4E78", "#,##0" },
                { "valor_recargos", "GRAN TOTAL $ RECARGOS Y DOM/FEST (mes)", "FF7030A0", "#,##0" } };
        Map<String, Object> celdas = new LinkedHashMap<>();
        for (String[] info : filas) {
            List<String> refs = new ArrayList<>();
            for (Map<String, String> b : bloques) if (!Conv.empty(b.get(info[0]))) refs.add(b.get(info[0]));
            x.set("C" + fila, info[1]);
            x.set("D" + fila, refs.isEmpty() ? (Object) 0 : "=SUM(" + String.join(",", refs) + ")");
            x.estilo("C" + fila + ":D" + fila, blancoNegrita(info[2]));
            x.estilo("D" + fila, E.of().formato(info[3]));
            celdas.put(info[0], "D" + fila);
            fila++;
        }
        x.set("C" + fila, "GRAN TOTAL GENERAL A PAGAR (EXTRAS + RECARGOS)");
        x.set("D" + fila, "=" + celdas.get("valor_extras") + "+" + celdas.get("valor_recargos"));
        x.estilo("C" + fila + ":D" + fila, blancoNegrita("FF000000").size(12));
        x.estilo("D" + fila, E.of().formato("#,##0"));
        celdas.put("total_general", "D" + fila);
        celdas.put("fila_final", fila);
        return celdas;
    }

    private void escribirHojaResumen(Excel x, List<Map<String, String>> resumen) {
        x.nuevaHoja("Resumen Extras");
        String[][] enc = { { "A1", "NOMBRE DE LA PERSONA" }, { "B1", "SALARIO BÁSICO" }, { "C1", "HORAS EXTRA (TOTAL)" },
                { "D1", "VALOR TOTAL A PAGAR ($)" }, { "E1", "ACTIVIDAD REALIZADA" }, { "F1", "QUIEN AUTORIZA" },
                { "G1", "RECARGOS Y DOM/FEST ($)" }, { "H1", "TOTAL GENERAL A PAGAR ($)" } };
        for (String[] e : enc) x.set(e[0], e[1]);
        x.estilo("A1:H1", blancoNegrita("FF1F4E78").center().wrap());

        int fila = 2;
        for (Map<String, String> r : resumen) {
            String hoja = "'" + r.get("hoja").replace("'", "''") + "'";
            x.set("A" + fila, r.get("nombre"));
            x.set("B" + fila, "=" + hoja + "!Z2");
            x.set("C" + fila, "=IF(" + hoja + "!" + r.get("horas_extras") + "=0, \"\", " + hoja + "!" + r.get("horas_extras") + ")");
            x.set("D" + fila, "=IF(" + hoja + "!" + r.get("valor_extras") + "=0, \"\", " + hoja + "!" + r.get("valor_extras") + ")");
            x.set("E" + fila, r.get("actividad"));
            x.set("F" + fila, r.get("autoriza"));
            x.set("G" + fila, "=IF(" + hoja + "!" + r.get("valor_recargos") + "=0, \"\", " + hoja + "!" + r.get("valor_recargos") + ")");
            x.set("H" + fila, "=IF(" + hoja + "!" + r.get("total_general") + "=0, \"\", " + hoja + "!" + r.get("total_general") + ")");
            x.estilo("B" + fila, E.of().formato("#,##0"));
            x.estilo("C" + fila, E.of().formato("[h]:mm;;;"));
            x.estilo("D" + fila, E.of().formato("#,##0;;;"));
            x.estilo("G" + fila, E.of().formato("#,##0;;;"));
            x.estilo("H" + fila, E.of().formato("#,##0;;;"));
            fila++;
        }
        Object[][] anchos = { { "A", 35 }, { "B", 18 }, { "C", 20 }, { "D", 24 }, { "E", 35 }, { "F", 25 }, { "G", 20 }, { "H", 22 } };
        for (Object[] a : anchos) x.ancho((String) a[0], ((Integer) a[1]).doubleValue());
        int ultima = fila - 1;
        if (ultima >= 1) {
            x.estilo("A1:H" + ultima, E.of().borde().bordeColor("FF000000"));
            if (ultima >= 2) {
                x.estilo("B2:H" + ultima, E.of().h("center"));
                x.estilo("A2:H" + ultima, E.of().v("center"));
            }
        }
    }
}
