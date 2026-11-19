package co.inees.mantenimientos.core;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.Collection;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.util.StreamUtils;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.multipart.MultipartHttpServletRequest;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpSession;
import tools.jackson.databind.json.JsonMapper;

/**
 * Acceso a la petición con la misma semántica de PHP:
 *  $_POST / $_GET (con arrays anidados "a[b][]"), $_FILES y php://input (JSON).
 * Se inyecta en los métodos de los controladores como parámetro.
 */
public class Req {

    private static final JsonMapper JSON = JsonMapper.builder().build();

    private final HttpServletRequest request;
    private Map<String, Object> datos;      // $_REQUEST anidado
    private Map<String, Object> json;       // php://input
    private String cuerpo;

    public Req(HttpServletRequest request) {
        this.request = request;
    }

    public HttpServletRequest raw() {
        return request;
    }

    public HttpSession session() {
        return request.getSession(true);
    }

    public Sesion sesion() {
        return new Sesion(request.getSession(true));
    }

    public String method() {
        return request.getMethod();
    }

    public boolean isPost() {
        return "POST".equalsIgnoreCase(request.getMethod());
    }

    public String ip() {
        String xf = request.getHeader("X-Forwarded-For");
        if (xf != null && !xf.isBlank()) return xf.split(",")[0].trim();
        String ra = request.getRemoteAddr();
        return ra == null ? "Desconocida" : ra;
    }

    // ------------------------------------------------------------ parámetros

    /** Estructura anidada al estilo PHP de todos los parámetros (GET + POST). */
    public Map<String, Object> all() {
        if (datos == null) {
            datos = new LinkedHashMap<>();
            Map<String, String[]> pm = request.getParameterMap();
            for (Map.Entry<String, String[]> e : pm.entrySet()) {
                for (String v : e.getValue()) {
                    poner(datos, e.getKey(), v);
                }
            }
        }
        return datos;
    }

    @SuppressWarnings("unchecked")
    private static void poner(Map<String, Object> raiz, String nombre, String valor) {
        int b = nombre.indexOf('[');
        if (b <= 0) {
            raiz.put(nombre, valor);
            return;
        }
        List<String> claves = new ArrayList<>();
        claves.add(nombre.substring(0, b));
        String resto = nombre.substring(b);
        while (resto.startsWith("[")) {
            int c = resto.indexOf(']');
            if (c < 0) break;
            claves.add(resto.substring(1, c));
            resto = resto.substring(c + 1);
        }
        Map<String, Object> actual = raiz;
        for (int i = 0; i < claves.size(); i++) {
            String k = claves.get(i);
            boolean ultimo = i == claves.size() - 1;
            if (k.isEmpty()) {
                k = String.valueOf(actual.size());
            }
            if (ultimo) {
                actual.put(k, valor);
            } else {
                Object sig = actual.get(k);
                if (!(sig instanceof Map)) {
                    sig = new LinkedHashMap<String, Object>();
                    actual.put(k, sig);
                }
                actual = (Map<String, Object>) sig;
            }
        }
    }

    public boolean has(String nombre) {
        return all().containsKey(nombre) || (json() != null && json().containsKey(nombre));
    }

    /** Valor crudo (String, Map anidado o null). Busca en parámetros y en JSON. */
    public Object get(String nombre) {
        Object v = all().get(nombre);
        if (v == null && json() != null) v = json().get(nombre);
        return v;
    }

    /** $_POST['x'] ?? '' */
    public String str(String nombre) {
        Object v = get(nombre);
        return v == null || v instanceof Map ? "" : String.valueOf(v);
    }

    public String str(String nombre, String def) {
        Object v = get(nombre);
        return v == null || v instanceof Map ? def : String.valueOf(v);
    }

    /** trim($_POST['x'] ?? '') */
    public String trim(String nombre) {
        return str(nombre).trim();
    }

    public int intv(String nombre) {
        return Conv.toInt(get(nombre));
    }

    public int intv(String nombre, int def) {
        Object v = get(nombre);
        return v == null || String.valueOf(v).isEmpty() ? def : Conv.toInt(v);
    }

    public Integer intOrNull(String nombre) {
        return Conv.intOrNull(get(nombre));
    }

    public double dbl(String nombre) {
        return Conv.toDouble(get(nombre));
    }

    /** Lista de valores para "x[]" (o un único valor "x"). */
    public List<Object> arr(String nombre) {
        return asList(get(nombre));
    }

    public List<String> strs(String nombre) {
        List<String> r = new ArrayList<>();
        for (Object o : arr(nombre)) r.add(o == null ? "" : String.valueOf(o));
        return r;
    }

    /** Valor anidado: en("order","0","column") == $_GET['order'][0]['column'] */
    public Object en(String... ruta) {
        Object actual = get(ruta[0]);
        for (int i = 1; i < ruta.length && actual != null; i++) {
            if (actual instanceof Map<?, ?> m) actual = m.get(ruta[i]);
            else if (actual instanceof List<?> l) {
                int idx = Conv.toInt(ruta[i]);
                actual = idx < l.size() ? l.get(idx) : null;
            } else actual = null;
        }
        return actual;
    }

    @SuppressWarnings("unchecked")
    public Map<String, Object> mapa(String nombre) {
        Object v = get(nombre);
        return v instanceof Map ? (Map<String, Object>) v : new LinkedHashMap<>();
    }

    /** Convierte un array PHP (Map con claves 0..n o List) a List. */
    public static List<Object> asList(Object v) {
        if (v == null) return new ArrayList<>();
        if (v instanceof Map<?, ?> m) return new ArrayList<>(m.values());
        if (v instanceof Collection<?> c) return new ArrayList<>(c);
        List<Object> r = new ArrayList<>();
        r.add(v);
        return r;
    }

    // ------------------------------------------------------------ JSON body

    public String body() {
        if (cuerpo == null) {
            try {
                cuerpo = StreamUtils.copyToString(request.getInputStream(), StandardCharsets.UTF_8);
            } catch (IOException e) {
                cuerpo = "";
            }
        }
        return cuerpo;
    }

    /** json_decode(file_get_contents('php://input'), true) */
    @SuppressWarnings("unchecked")
    public Map<String, Object> json() {
        if (json == null) {
            String ct = request.getContentType();
            if (ct != null && ct.toLowerCase().contains("application/json")) {
                try {
                    String b = body();
                    if (b != null && !b.isBlank()) {
                        Object o = JSON.readValue(b, Object.class);
                        if (o instanceof Map) json = (Map<String, Object>) o;
                        else {
                            json = new LinkedHashMap<>();
                            json.put("_root", o);
                        }
                    }
                } catch (Exception e) {
                    json = null;
                }
            }
            if (json == null) json = new LinkedHashMap<>();
        }
        return json;
    }

    // ------------------------------------------------------------ archivos

    public MultipartFile file(String nombre) {
        if (request instanceof MultipartHttpServletRequest mr) {
            MultipartFile f = mr.getFile(nombre);
            if (f == null) f = mr.getFile(nombre + "[]");
            return f == null || f.isEmpty() ? null : f;
        }
        return null;
    }

    public List<MultipartFile> files(String nombre) {
        List<MultipartFile> r = new ArrayList<>();
        if (request instanceof MultipartHttpServletRequest mr) {
            for (MultipartFile f : mr.getFiles(nombre)) if (f != null && !f.isEmpty()) r.add(f);
            for (MultipartFile f : mr.getFiles(nombre + "[]")) if (f != null && !f.isEmpty()) r.add(f);
        }
        return r;
    }

    /** Todos los archivos cuyo nombre de campo empieza por el prefijo (ej. "fotos[3]"). */
    public Map<String, List<MultipartFile>> filesPorPrefijo(String prefijo) {
        Map<String, List<MultipartFile>> r = new LinkedHashMap<>();
        if (request instanceof MultipartHttpServletRequest mr) {
            mr.getMultiFileMap().forEach((k, v) -> {
                if (k.startsWith(prefijo)) {
                    List<MultipartFile> l = new ArrayList<>();
                    for (MultipartFile f : v) if (f != null && !f.isEmpty()) l.add(f);
                    if (!l.isEmpty()) r.put(k, l);
                }
            });
        }
        return r;
    }

    public static String jsonEncode(Object o) {
        try {
            return JSON.writeValueAsString(o);
        } catch (Exception e) {
            return "null";
        }
    }

    @SuppressWarnings("unchecked")
    public static Object jsonDecode(String s) {
        if (s == null || s.isBlank()) return null;
        try {
            return JSON.readValue(s, Object.class);
        } catch (Exception e) {
            return null;
        }
    }
}
