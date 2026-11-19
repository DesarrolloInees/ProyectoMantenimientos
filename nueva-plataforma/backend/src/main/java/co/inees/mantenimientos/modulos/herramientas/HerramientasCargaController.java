package co.inees.mantenimientos.modulos.herramientas;

import java.nio.ByteBuffer;
import java.nio.charset.CharacterCodingException;
import java.nio.charset.Charset;
import java.nio.charset.CodingErrorAction;
import java.nio.charset.StandardCharsets;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.time.format.ResolverStyle;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/**
 * Port de los scripts sueltos de helps/*.php (cargas iniciales por CSV):
 * importar_clientes, importar_puntos, importar_maquinas, importar_delegaciones, importar_municipios,
 * importar_parametros y actualizar_puntos. Mismas consultas y reglas; solo accesible para el administrador.
 */
@RestController
public class HerramientasCargaController {

    private static final String SES_CLIENTES = "helps_clientes_filas";
    private final Db db;

    public HerramientasCargaController(Db db) {
        this.db = db;
    }

    // ================================================================ utilidades CSV

    /** Texto del archivo: UTF-8 si es válido, si no Windows-1252 (equivale al mb_convert_encoding de PHP). */
    static String texto(MultipartFile f) throws Exception {
        byte[] b = f.getBytes();
        String s;
        try {
            s = StandardCharsets.UTF_8.newDecoder().onMalformedInput(CodingErrorAction.REPORT)
                    .onUnmappableCharacter(CodingErrorAction.REPORT).decode(ByteBuffer.wrap(b)).toString();
        } catch (CharacterCodingException e) {
            s = new String(b, Charset.forName("windows-1252"));
        }
        return s;
    }

    /** Filas como fgetcsv (las líneas en blanco cuentan como fila con un único campo vacío). */
    static List<List<String>> csv(String texto, char sep) {
        List<List<String>> filas = new ArrayList<>();
        List<String> fila = new ArrayList<>();
        StringBuilder cur = new StringBuilder();
        boolean q = false;
        int n = texto.length();
        for (int i = 0; i < n; i++) {
            char c = texto.charAt(i);
            if (q) {
                if (c == '"') {
                    if (i + 1 < n && texto.charAt(i + 1) == '"') {
                        cur.append('"');
                        i++;
                    } else q = false;
                } else cur.append(c);
            } else if (c == '"') q = true;
            else if (c == sep) {
                fila.add(cur.toString());
                cur.setLength(0);
            } else if (c == '\r' || c == '\n') {
                if (c == '\r' && i + 1 < n && texto.charAt(i + 1) == '\n') i++;
                fila.add(cur.toString());
                cur.setLength(0);
                filas.add(fila);
                fila = new ArrayList<>();
            } else cur.append(c);
        }
        if (cur.length() > 0 || !fila.isEmpty()) {
            fila.add(cur.toString());
            filas.add(fila);
        }
        return filas;
    }

    private static String col(List<String> f, int i) {
        return i >= 0 && i < f.size() ? f.get(i) : null;
    }

    private static boolean existe(List<String> f, int i) {
        return i >= 0 && i < f.size();
    }

    private static String up(String s) {
        return s == null ? "" : s.toUpperCase(Locale.ROOT);
    }

    /** strtoupper() de PHP 8: solo letras ASCII. */
    private static String upAscii(String s) {
        StringBuilder b = new StringBuilder(s.length());
        for (char c : s.toCharArray()) b.append(c >= 'a' && c <= 'z' ? (char) (c - 32) : c);
        return b.toString();
    }

    private static MultipartFile archivo(Req req) {
        MultipartFile f = req.file("archivo_csv");
        return f == null || f.isEmpty() ? null : f;
    }

    private static Map<String, Object> error(String m) {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("exito", false);
        r.put("error", m);
        return r;
    }

    @GetMapping(value = "/api/herramientasCarga", params = "accion=index")
    public Map<String, Object> index() {
        return Map.of("titulo", "Herramientas de Carga Inicial (CSV)");
    }

    // ================================================================ importar_clientes.php

    @PostMapping(value = "/api/herramientasCarga", params = "accion=clientesAnalizar")
    public Map<String, Object> clientesAnalizar(Req req) throws Exception {
        MultipartFile f = archivo(req);
        if (f == null) return error("❌ Error al subir el archivo.");
        List<String[]> filas = new ArrayList<>();
        int fila = 0;
        for (List<String> d : csv(texto(f), ';')) {
            fila++;
            String d0 = col(d, 0);
            if (fila == 1 && d0 != null && d0.toUpperCase(Locale.ROOT).contains("COD")) continue;
            if (Conv.empty(d0)) continue;
            String nom = col(d, 1) == null ? "" : col(d, 1).trim();
            filas.add(new String[] { d0.trim(), nom });
        }
        Map<String, Set<String>> conflictos = new LinkedHashMap<>();
        for (String[] r : filas) conflictos.computeIfAbsent(r[1], k -> new LinkedHashSet<>()).add(r[0]);
        Map<String, Object> problemas = new LinkedHashMap<>();
        for (Map.Entry<String, Set<String>> e : conflictos.entrySet()) {
            if (e.getValue().size() > 1) problemas.put(e.getKey(), new ArrayList<>(e.getValue()));
        }
        req.sesion().set(SES_CLIENTES, filas);
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("exito", true);
        r.put("filas", filas.size());
        r.put("conflictos", problemas);
        if (problemas.isEmpty()) r.put("mensaje", "✅ No hay conflictos. Dale a Guardar.");
        return r;
    }

    @SuppressWarnings("unchecked")
    @PostMapping(value = "/api/herramientasCarga", params = "accion=clientesGuardar")
    public Map<String, Object> clientesGuardar(Req req) {
        if (!(req.sesion().get(SES_CLIENTES) instanceof List<?> l)) return error("⚠️ La sesión expiró. Vuelve a subir el archivo.");
        List<String[]> filas = (List<String[]>) l;
        Map<String, Object> correcciones;
        try {
            correcciones = tools.jackson.databind.json.JsonMapper.builder().build().readValue(req.str("correcciones", "{}"), Map.class);
        } catch (Exception e) {
            correcciones = Map.of();
        }
        final Map<String, Object> corr = correcciones;
        try {
            int insertados = db.tx(() -> {
                int n = 0;
                for (String[] f : filas) {
                    String codigo = corr.containsKey(f[1]) ? Conv.str(corr.get(f[1])) : f[0];
                    n += db.update("INSERT IGNORE INTO cliente (codigo_cliente, nombre_cliente) VALUES (:c, :n)", "c", codigo, "n", f[1]) > 0 ? 1 : 0;
                }
                return n;
            });
            req.sesion().remove(SES_CLIENTES);
            return Map.of("exito", true, "mensaje", "🚀 ¡Proceso Terminado! Se han insertado " + insertados + " clientes correctamente.");
        } catch (Exception e) {
            return error("❌ Error durante el proceso: " + e.getMessage());
        }
    }

    // ================================================================ importar_puntos.php

    @PostMapping(value = "/api/herramientasCarga", params = "accion=puntos")
    public Map<String, Object> puntos(Req req) throws Exception {
        MultipartFile f = archivo(req);
        if (f == null) return error("❌ Error al subir el archivo.");
        List<List<String>> filas = csv(texto(f), ';');
        List<String> errores = new ArrayList<>();
        try {
            int insertados = db.tx(() -> {
                // PASO 0: municipio comodín "SIN DEFINIR"
                Object idDef = db.scalar("SELECT id_municipio FROM municipio WHERE nombre_municipio = 'SIN DEFINIR' LIMIT 1");
                long idMuniDefault = idDef != null ? Conv.toLong(idDef)
                        : db.insert("INSERT INTO municipio (nombre_municipio, id_delegacion) VALUES ('SIN DEFINIR', NULL)");

                Map<String, Object> clientes = new LinkedHashMap<>();
                for (Map<String, Object> r : db.list("SELECT id_cliente, nombre_cliente FROM cliente"))
                    clientes.put(up(Conv.str(r.get("nombre_cliente")).trim()), r.get("id_cliente"));
                Map<String, Object> municipios = new LinkedHashMap<>();
                for (Map<String, Object> r : db.list("SELECT id_municipio, nombre_municipio FROM municipio"))
                    municipios.put(up(Conv.str(r.get("nombre_municipio")).trim()), r.get("id_municipio"));

                int nom = -1, dir = -1, c1 = -1, c2 = -1, mun = -1, cli = -1, del = -1;
                int n = 0, filaNum = 0;
                for (List<String> d : filas) {
                    filaNum++;
                    if (filaNum == 1) {
                        for (int i = 0; i < d.size(); i++) {
                            String v = up(d.get(i).trim());
                            if (v.contains("NOMBRE DEL PUNTO")) nom = i;
                            if (v.contains("DIRECCIÓN") || v.contains("DIRECCION")) dir = i;
                            if (v.contains("COD 1")) c1 = i;
                            if (v.contains("COD 2")) c2 = i;
                            if (v.contains("MUNICIPIO")) mun = i;
                            if (v.contains("CLIENTE")) cli = i;
                            if (v.contains("DELEGACI")) del = i;
                        }
                        continue;
                    }
                    if (!existe(d, nom)) continue;
                    String rawNombre = d.get(nom).trim();
                    String rawCli = up(Conv.str(col(d, cli)).trim());
                    if (Conv.empty(rawNombre)) continue;
                    String rawDir = dir >= 0 ? Conv.str(col(d, dir)).trim() : "";
                    String rawC1 = c1 >= 0 ? Conv.str(col(d, c1)).trim() : null;
                    String rawC2 = c2 >= 0 ? Conv.str(col(d, c2)).trim() : null;
                    String rawMun = mun >= 0 ? up(Conv.str(col(d, mun)).trim()) : ".";
                    String rawDel = del >= 0 ? up(Conv.str(col(d, del)).trim()) : "";

                    Object idCliente = clientes.get(rawCli);
                    if (idCliente == null) {
                        errores.add("Fila " + filaNum + ": Cliente '" + rawCli + "' no existe en BD.");
                        continue;
                    }
                    Object idMun = municipios.containsKey(rawMun) ? municipios.get(rawMun)
                            : municipios.containsKey(rawDel) ? municipios.get(rawDel) : idMuniDefault;
                    n += db.update("""
                            INSERT IGNORE INTO punto (nombre_punto, direccion, codigo_1, codigo_2, id_municipio, id_cliente)
                            VALUES (:nom, :dir, :c1, :c2, :mun, :cli)""",
                            "nom", rawNombre, "dir", rawDir, "c1", rawC1, "c2", rawC2, "mun", idMun, "cli", idCliente) > 0 ? 1 : 0;
                }
                return n;
            });
            Map<String, Object> r = new LinkedHashMap<>();
            r.put("exito", true);
            r.put("mensaje", "✅ ¡IMPORTACIÓN FINALIZADA! Puntos insertados correctamente: " + insertados
                    + " (Los puntos sin municipio se guardaron como 'SIN DEFINIR')");
            r.put("errores", errores);
            return r;
        } catch (Exception e) {
            return error("❌ Error Fatal: " + e.getMessage());
        }
    }

    // ================================================================ importar_maquinas.php

    /** normalizar_clave(): mayúsculas, sin tildes ni Ñ y solo A-Z0-9. */
    static String clave(String s) {
        String x = up(s == null ? "" : s.trim()).replace('Á', 'A').replace('É', 'E').replace('Í', 'I').replace('Ó', 'O')
                .replace('Ú', 'U').replace('Ü', 'U').replace('Ñ', 'N');
        return x.replaceAll("[^A-Z0-9]", "");
    }

    @PostMapping(value = "/api/herramientasCarga", params = "accion=maquinas")
    public Map<String, Object> maquinas(Req req) throws Exception {
        MultipartFile f = archivo(req);
        if (f == null) return error("❌ Error al subir el archivo.");
        List<List<String>> filas = csv(texto(f), ';');
        List<String> errores = new ArrayList<>();
        try {
            int[] res = db.tx(() -> {
                Map<String, Object> tipos = new LinkedHashMap<>();
                for (Map<String, Object> r : db.list("SELECT id_tipo_maquina, nombre_tipo_maquina FROM tipo_maquina"))
                    tipos.put(clave(Conv.str(r.get("nombre_tipo_maquina"))), r.get("id_tipo_maquina"));
                Map<String, Object> puntos = new LinkedHashMap<>();
                for (Map<String, Object> r : db.list("SELECT id_punto, nombre_punto FROM punto"))
                    puntos.put(clave(Conv.str(r.get("nombre_punto"))), r.get("id_punto"));

                int dev = -1, pto = -1, tip = -1, insertados = 0, nuevosTipos = 0, filaNum = 0;
                for (List<String> d : filas) {
                    filaNum++;
                    if (filaNum == 1) {
                        for (int i = 0; i < d.size(); i++) {
                            String v = clave(d.get(i));
                            if (v.contains("DEVICEID")) dev = i;
                            if (v.contains("NOMBREDELPUNTO")) pto = i;
                            if (v.contains("TIPODEMAQUINA")) tip = i;
                        }
                        if (dev == -1 || pto == -1) throw new IllegalStateException("No encontré las columnas DEVICE ID o NOMBRE DEL PUNTO.");
                        continue;
                    }
                    if (!existe(d, dev) || !existe(d, pto)) continue;
                    String rawDevice = d.get(dev).trim();
                    String rawPunto = d.get(pto).trim();
                    String rawTipo = existe(d, tip) ? d.get(tip).trim() : "DESCONOCIDO";
                    if (Conv.empty(rawDevice) || Conv.empty(rawPunto)) continue;

                    Object idPunto = puntos.get(clave(rawPunto));
                    if (idPunto == null) {
                        errores.add("Fila " + filaNum + ": El punto '" + rawPunto + "' no existe en la BD (Device: " + rawDevice + ").");
                        continue;
                    }
                    String ct = clave(rawTipo);
                    Object idTipo = tipos.get(ct);
                    if (idTipo == null) {
                        idTipo = db.insert("INSERT INTO tipo_maquina (nombre_tipo_maquina) VALUES (:n)", "n", rawTipo);
                        tipos.put(ct, idTipo);
                        nuevosTipos++;
                    }
                    insertados += db.update("INSERT IGNORE INTO maquina (device_id, id_punto, id_tipo_maquina) VALUES (:d, :p, :t)",
                            "d", rawDevice, "p", idPunto, "t", idTipo) > 0 ? 1 : 0;
                }
                return new int[] { insertados, nuevosTipos };
            });
            Map<String, Object> r = new LinkedHashMap<>();
            r.put("exito", true);
            r.put("mensaje", "🤖 ¡PROCESO FINALIZADO! Máquinas insertadas: " + res[0] + " · Nuevos Tipos de Máquina creados: " + res[1]);
            r.put("errores", errores);
            return r;
        } catch (Exception e) {
            return error("❌ Error Fatal: " + e.getMessage());
        }
    }

    // ================================================================ importar_delegaciones.php

    @PostMapping(value = "/api/herramientasCarga", params = "accion=delegaciones")
    public Map<String, Object> delegaciones(Req req) throws Exception {
        MultipartFile f = archivo(req);
        if (f == null) return error("Error al subir archivo.");
        String t = texto(f);
        int nl = t.indexOf('\n');
        String primera = nl >= 0 ? t.substring(0, nl) : t;
        char sep = primera.contains(";") ? ';' : ',';
        List<List<String>> filas = csv(t, sep);
        try {
            int vinculados = db.tx(() -> {
                int iPunto = -1, iCodigo = -1, iNombre = -1, fila = 0, n = 0;
                for (List<String> d : filas) {
                    fila++;
                    if (fila == 1) {
                        if (!d.isEmpty()) d.set(0, d.get(0).replaceFirst("^﻿", ""));
                        for (int i = 0; i < d.size(); i++) {
                            String h = up(d.get(i).trim());
                            if (h.contains("NOMBRE DEL PUNTO")) iPunto = i;
                            if (h.equals("DELEGACION") || h.equals("CODIGO")) iCodigo = i;
                            if (h.equals("DELEGACIÓN") || h.equals("NOMBRE DELEGACION")) iNombre = i;
                        }
                        if (iPunto == -1 || iCodigo == -1)
                            throw new IllegalStateException("No se encontraron las columnas necesarias ('NOMBRE DEL PUNTO' y 'DELEGACION'). Revisa el archivo.");
                        continue;
                    }
                    if (!existe(d, iCodigo) || !existe(d, iPunto)) continue;
                    String codigo = d.get(iCodigo).trim();
                    String punto = d.get(iPunto).trim();
                    String nomDel = iNombre >= 0 && existe(d, iNombre) ? d.get(iNombre).trim() : "DELEGACION " + codigo;
                    int idDel = intval(codigo);
                    if (idDel > 0 && !Conv.empty(punto)) {
                        db.update("""
                                INSERT INTO delegacion (id_delegacion, nombre_delegacion) VALUES (:id, :n)
                                ON DUPLICATE KEY UPDATE nombre_delegacion = VALUES(nombre_delegacion)""", "id", idDel, "n", up(nomDel));
                        n += db.update("UPDATE punto SET id_delegacion = :id WHERE nombre_punto = :p", "id", idDel, "p", punto) > 0 ? 1 : 0;
                    }
                }
                return n;
            });
            Map<String, Object> r = new LinkedHashMap<>();
            r.put("exito", true);
            r.put("tipo", vinculados == 0 ? "warning" : "success");
            String m = "✅ Importación Exitosa. Delegaciones procesadas: el sistema aseguró que existan los IDs correctos. Puntos actualizados: "
                    + vinculados + ". Los puntos ahora están conectados a su delegación correcta (001 → 1).";
            if (vinculados == 0) m += " ⚠️ Atención: No se vinculó ningún punto. Verifica que los nombres en la columna 'NOMBRE DEL PUNTO' sean idénticos a los de tu base de datos.";
            r.put("mensaje", m);
            return r;
        } catch (Exception e) {
            return error("Error: " + e.getMessage());
        }
    }

    /** intval() de PHP sobre un texto ("001" → 1, "12abc" → 12, "abc" → 0). */
    static int intval(String s) {
        s = s.trim();
        int i = 0, sign = 1;
        if (i < s.length() && (s.charAt(i) == '-' || s.charAt(i) == '+')) sign = s.charAt(i++) == '-' ? -1 : 1;
        long v = 0;
        while (i < s.length() && Character.isDigit(s.charAt(i)) && v < Integer.MAX_VALUE) v = v * 10 + (s.charAt(i++) - '0');
        return (int) Math.min(Integer.MAX_VALUE, v) * sign;
    }

    // ================================================================ importar_municipios.php

    @PostMapping(value = "/api/herramientasCarga", params = "accion=municipios")
    public Map<String, Object> municipios(Req req) throws Exception {
        MultipartFile f = archivo(req);
        if (f == null) return error("❌ Error subiendo archivo.");
        Map<String, Object> mapaDel = new LinkedHashMap<>();
        for (Map<String, Object> r : db.list("SELECT id_delegacion, nombre_delegacion FROM delegacion"))
            mapaDel.put(upAscii(Conv.str(r.get("nombre_delegacion"))), r.get("id_delegacion"));

        int insertados = 0, saltados = 0, fila = 0, iMuni = -1, iDel = -1, iDir = -1;
        for (List<String> d : csv(texto(f), ';')) {
            fila++;
            if (fila == 1) {
                for (int i = 0; i < d.size(); i++) {
                    String c = upAscii(d.get(i).trim());
                    if (c.contains("MUNICIPIO")) iMuni = i;
                    if (c.contains("DELEGACI")) iDel = i;
                    if (c.contains("DIRECCI")) iDir = i;
                }
                if (iDel == -1) return error("❌ No encontré la columna DELEGACIÓN.");
                continue;
            }
            if (!existe(d, iDel)) continue;
            String rawDel = up(d.get(iDel).trim());
            String rawMun = iMuni >= 0 ? up(Conv.str(col(d, iMuni)).trim()) : ".";
            String rawDir = iDir >= 0 ? Conv.str(col(d, iDir)) : "";
            Object idDel = mapaDel.get(rawDel);
            if (idDel == null) {
                saltados++;
                continue;
            }
            String nombre = "";
            if (bytes(rawMun) > 2 && !rawMun.equals(".")) {
                nombre = rawMun;
            } else {
                String[] partes = rawDir.split("/", -1);
                if (partes.length > 1) {
                    String pos = up(partes[1].trim().split(",", -1)[0].trim());
                    if (bytes(pos) > 2 && bytes(pos) < 40) nombre = pos;
                }
                if (nombre.isEmpty()) nombre = rawDel;
            }
            if (db.one("SELECT id_municipio FROM municipio WHERE nombre_municipio = :n AND id_delegacion = :d", "n", nombre, "d", idDel) == null) {
                db.update("INSERT INTO municipio (nombre_municipio, id_delegacion) VALUES (:n, :d)", "n", nombre, "d", idDel);
                insertados++;
            }
        }
        return Map.of("exito", true, "mensaje", "🎉 ¡Proceso Terminado! Municipios nuevos insertados: " + insertados
                + " · Filas saltadas (Delegación no encontrada): " + saltados);
    }

    private static int bytes(String s) {
        return s.getBytes(StandardCharsets.UTF_8).length;
    }

    // ================================================================ importar_parametros.php

    static String normalizar(String s) {
        return up(s == null ? "" : s.trim()).replace('Á', 'A').replace('É', 'E').replace('Í', 'I').replace('Ó', 'O').replace('Ú', 'U')
                .replace('Ñ', 'N');
    }

    private List<Object> idsMaquina(String textoRaw) {
        String texto = normalizar(textoRaw);
        Set<Object> ids = new LinkedHashSet<>();
        if (texto.contains("TODAS")) return db.column("SELECT id_tipo_maquina FROM tipo_maquina");
        String exClean = texto.replace("-", "").replace("_", "");
        for (Map<String, Object> r : db.list("SELECT id_tipo_maquina, nombre_tipo_maquina FROM tipo_maquina")) {
            String bdClean = normalizar(Conv.str(r.get("nombre_tipo_maquina"))).replace("-", "");
            if (exClean.contains(bdClean)) ids.add(r.get("id_tipo_maquina"));
        }
        return new ArrayList<>(ids);
    }

    private Object idMantenimiento(String t) {
        String texto = normalizar(t);
        for (Map<String, Object> r : db.list("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento")) {
            String bd = normalizar(Conv.str(r.get("nombre_completo")));
            if (texto.contains(bd) || bd.contains(texto)) return r.get("id_tipo_mantenimiento");
            if (texto.contains("FALLIDO") && bd.contains("FALLIDA")) return r.get("id_tipo_mantenimiento");
        }
        return null;
    }

    @PostMapping(value = "/api/herramientasCarga", params = "accion=parametros")
    public Map<String, Object> parametros(Req req) throws Exception {
        MultipartFile f = archivo(req);
        if (f == null) return error("❌ Error al subir el archivo.");
        List<List<String>> filas = csv(texto(f), ';');

        // Protección: el script PHP fue escrito para un esquema anterior (tarifa.id_tipo_zona y
        // municipio.id_tipo_zona). Con el esquema actual vaciaba la tabla tarifa y luego fallaba al
        // insertar, dejando el sistema SIN tarifas. Aquí se verifica antes de tocar nada.
        long colsZona = db.scalarLong("""
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'id_tipo_zona' AND TABLE_NAME IN ('tarifa', 'municipio')""");
        if (colsZona < 2) {
            return error("⛔ Proceso cancelado sin modificar nada: la base de datos actual ya no tiene la columna 'id_tipo_zona' en "
                    + "las tablas 'tarifa' y 'municipio' (este importador corresponde a una versión anterior del esquema). "
                    + "Las tarifas actuales se administran desde el módulo de Tarifas.");
        }

        // --- PARTE A: TARIFAS ---
        int tarifas = 0, actualizados = 0, nuevos = 0, fila = 0;
        db.update("TRUNCATE TABLE tarifa");
        for (List<String> d : filas) {
            fila++;
            String c2 = col(d, 2);
            if (c2 != null && c2.contains("$")) {
                double precio = Conv.toDouble(c2.replaceAll("[^0-9]", "").isEmpty() ? "0" : c2.replaceAll("[^0-9]", ""));
                int zona = fila < 14 ? 1 : 2;
                List<Object> maquinas = idsMaquina(Conv.str(col(d, 0)));
                Object mant = idMantenimiento(Conv.str(col(d, 1)));
                if (mant != null && !maquinas.isEmpty()) {
                    for (Object idMaq : maquinas) {
                        db.update("INSERT INTO tarifa (id_tipo_maquina, id_tipo_mantenimiento, id_tipo_zona, precio) VALUES (:m, :t, :z, :p)",
                                "m", idMaq, "t", mant, "z", zona, "p", precio);
                        tarifas++;
                    }
                }
            }
        }

        // --- PARTE B: MUNICIPIOS (fila 23 en adelante) ---
        int[] cols = { 1, 4, 7, 10 };
        String[] delCtx = { "BOGOTA", "MEDELLIN", "BARRANQUILLA", "CALI" };
        int[] zonaCtx = { 1, 1, 1, 1 };
        fila = 0;
        for (List<String> d : filas) {
            fila++;
            if (fila < 23) continue;
            for (int k = 0; k < cols.length; k++) {
                int ci = cols[k];
                String cab = existe(d, ci - 1) ? normalizar(d.get(ci - 1)) : "";
                if (cab.contains("EXTERNOS") || cab.contains("INTERURBANO")) {
                    zonaCtx[k] = 2;
                    if (cab.contains("MEDELLIN")) delCtx[k] = "MEDELLIN";
                    if (cab.contains("BOGOTA")) delCtx[k] = "BOGOTA";
                    if (cab.contains("CALI")) delCtx[k] = "CALI";
                    if (cab.contains("BARRAN")) delCtx[k] = "BARRANQUILLA";
                }
                if (existe(d, ci) && !Conv.empty(d.get(ci).trim())) {
                    String muni = normalizar(d.get(ci));
                    if (List.of("URBANOS", "EXTERNOS", "MUNICIPIO", "CIUDAD").contains(muni)) continue;
                    Object idDelBd = db.scalar("SELECT id_delegacion FROM delegacion WHERE nombre_delegacion LIKE :d LIMIT 1", "d", "%" + delCtx[k] + "%");
                    if (idDelBd == null) idDelBd = 1;
                    Map<String, Object> ex = db.one("SELECT id_municipio FROM municipio WHERE nombre_municipio = :n", "n", muni);
                    if (ex != null) {
                        db.update("UPDATE municipio SET id_tipo_zona = :z WHERE id_municipio = :id", "z", zonaCtx[k], "id", ex.get("id_municipio"));
                        actualizados++;
                    } else {
                        db.update("INSERT INTO municipio (nombre_municipio, id_delegacion, id_tipo_zona) VALUES (:n, :d, :z)",
                                "n", muni, "d", idDelBd, "z", zonaCtx[k]);
                        nuevos++;
                    }
                }
            }
        }
        return Map.of("exito", true, "mensaje", "🚀 ¡Importación Parametrizada Exitosa! Tarifas creadas: " + tarifas
                + " · Municipios actualizados (Zona asignada): " + actualizados + " · Municipios nuevos creados: " + nuevos);
    }

    // ================================================================ actualizar_puntos.php

    private static final DateTimeFormatter DMY = DateTimeFormatter.ofPattern("d/M/uuuu").withResolverStyle(ResolverStyle.STRICT);

    @PostMapping(value = "/api/herramientasCarga", params = "accion=actualizarPuntos")
    public Map<String, Object> actualizarPuntos(Req req) throws Exception {
        MultipartFile f = archivo(req);
        if (f == null) return error("No se pudo abrir el archivo CSV.");
        List<List<String>> filas = csv(texto(f), ';');
        List<Map<String, Object>> omitidos = new ArrayList<>();
        List<String> noEncontrados = new ArrayList<>();
        int[] cont = { 0, 0 };
        try {
            db.txRun(() -> {
                boolean primera = true;
                for (List<String> d : filas) {
                    if (primera) {
                        primera = false;
                        continue;
                    }
                    cont[0]++;
                    String device = Conv.str(col(d, 0)).trim();
                    String fechaRaw = Conv.str(col(d, 1)).trim();
                    String tipoRaw = up(Conv.str(col(d, 4))).trim();
                    LocalDate fecha;
                    try {
                        fecha = LocalDate.parse(fechaRaw, DMY);
                    } catch (Exception e) {
                        continue;
                    }
                    String fechaCsv = fecha.toString();
                    Integer tipo = switch (tipoRaw) {
                        case "MTTO PREVENTIVO" -> 1;
                        case "MTTO PROFUNDO" -> 2;
                        case "MTTO CORRECTIVO", "MTTO CORECTIVO" -> 3;
                        default -> tipoRaw.contains("PROFUNDO") ? 2 : tipoRaw.contains("PREVENTIVO") ? 1
                                : (tipoRaw.contains("CORRECTIVO") || tipoRaw.contains("CORECTIVO")) ? 3 : null;
                    };
                    if (tipo == null) continue;
                    Map<String, Object> info = db.one("""
                            SELECT p.id_punto, p.nombre_punto, p.fecha_ultima_visita
                            FROM maquina m INNER JOIN punto p ON m.id_punto = p.id_punto
                            WHERE m.device_id = :d LIMIT 1""", "d", device);
                    if (info == null) {
                        noEncontrados.add(device);
                        continue;
                    }
                    String fechaBd = info.get("fecha_ultima_visita") == null ? null : Conv.str(info.get("fecha_ultima_visita"));
                    if (fechaBd != null && fechaBd.length() > 10) fechaBd = fechaBd.substring(0, 10);
                    if (fechaBd == null || fechaCsv.compareTo(fechaBd) > 0) {
                        db.update("UPDATE punto SET fecha_ultima_visita = :f, id_ultimo_tipo_mantenimiento = :t WHERE id_punto = :id",
                                "f", fechaCsv, "t", tipo, "id", info.get("id_punto"));
                        cont[1]++;
                    } else {
                        Map<String, Object> o = new LinkedHashMap<>();
                        o.put("device", device);
                        o.put("punto", info.get("nombre_punto"));
                        o.put("fecha_csv", fechaCsv);
                        o.put("fecha_bd", fechaBd);
                        omitidos.add(o);
                    }
                }
            });
        } catch (Exception e) {
            return error("Error: " + e.getMessage());
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("exito", true);
        r.put("leidos", cont[0]);
        r.put("actualizados", cont[1]);
        r.put("omitidos", omitidos);
        r.put("noEncontrados", noEncontrados);
        return r;
    }
}
