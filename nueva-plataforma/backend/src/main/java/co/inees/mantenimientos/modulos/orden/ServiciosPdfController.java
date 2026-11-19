package co.inees.mantenimientos.modulos.orden;

import java.io.IOException;
import java.io.OutputStream;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.security.SecureRandom;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.HexFormat;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Map;
import java.util.zip.ZipEntry;
import java.util.zip.ZipOutputStream;

import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Req;

/** Port de serviciosPdfControlador.php + serviciosPdfModelo.php (descarga masiva de PDFs en ZIP por lotes) */
@RestController
@RequestMapping("/api/serviciosPdf")
public class ServiciosPdfController {

    static final int MAX_SERVICIOS_ZIP = 1000;
    static final int LOTE_PDF_DEFAULT = 3;
    static final long TTL_TRABAJO_SEGUNDOS = 7200;
    private static final SecureRandom RND = new SecureRandom();

    private final Db db;
    private final ServicioPdfGenerator gen;

    public ServiciosPdfController(Db db, ServicioPdfGenerator gen) {
        this.db = db;
        this.gen = gen;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> cargarVista() {
        Map<String, Object> op = new LinkedHashMap<>();
        op.put("clientes", List.of());
        op.put("tecnicos", List.of());
        op.put("delegaciones", List.of());
        op.put("tipos", List.of());
        try {
            op.put("clientes", db.list("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC"));
            op.put("tecnicos", db.list("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
            op.put("delegaciones", db.list("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC"));
            op.put("tipos", db.list("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1 ORDER BY nombre_completo ASC"));
        } catch (Exception ignored) {
        }
        return Map.of("titulo", "Generador de PDFs", "opciones", op);
    }

    @RequestMapping(params = "accion=ajaxListar", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> ajaxListar(Req req) {
        int[] r = restriccion(req);
        return Map.of("data", listarServicios(r[0], r[1], leerFiltros(req), MAX_SERVICIOS_ZIP));
    }

    @RequestMapping(params = "accion=ajaxPuntos", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> ajaxPuntos(Req req) {
        Integer idCliente = !Conv.empty(req.get("id_cliente")) ? req.intv("id_cliente") : null;
        String sql = "SELECT id_punto, id_cliente, nombre_punto FROM punto WHERE estado = 1";
        Map<String, Object> p = new LinkedHashMap<>();
        if (idCliente != null && idCliente != 0) {
            sql += " AND id_cliente = :c";
            p.put("c", idCliente);
        }
        sql += " ORDER BY nombre_punto ASC LIMIT 5000";
        try {
            return Map.of("data", db.list(sql, p));
        } catch (Exception e) {
            return Map.of("data", List.of());
        }
    }

    // ================================================================ TRABAJOS ZIP

    @PostMapping(params = "accion=crearTrabajoZip")
    public Map<String, Object> crearTrabajoZip(Req req) throws IOException {
        int[] r = restriccion(req);
        Map<String, Object> filtros = leerFiltros(req);
        List<Integer> ids = leerIds(req);
        String modo = !ids.isEmpty() ? "ids" : "filtros";
        String advertencia = null;
        List<Map<String, Object>> servicios;
        if ("ids".equals(modo)) {
            servicios = serviciosPorIds(ids, r[0], r[1]);
            if (servicios.size() > MAX_SERVICIOS_ZIP) {
                servicios = servicios.subList(0, MAX_SERVICIOS_ZIP);
                advertencia = "Se limitó la descarga a " + MAX_SERVICIOS_ZIP + " servicios.";
            }
        } else {
            servicios = listarServicios(r[0], r[1], filtros, MAX_SERVICIOS_ZIP + 1);
            if (servicios.size() > MAX_SERVICIOS_ZIP) {
                servicios = servicios.subList(0, MAX_SERVICIOS_ZIP);
                advertencia = "La selección supera el máximo permitido y se limitó a " + MAX_SERVICIOS_ZIP + " servicios.";
            }
        }
        if (servicios.isEmpty()) return Map.of("status", "error", "msg", "No se encontraron servicios con los filtros seleccionados.");

        byte[] b = new byte[16];
        RND.nextBytes(b);
        String token = HexFormat.of().formatHex(b);
        Files.createDirectories(base());
        limpiarTrabajosViejos();
        Files.createDirectories(dirTrabajo(token));

        Map<String, Object> t = new LinkedHashMap<>();
        t.put("token", token);
        t.put("usuario_id", req.sesion().has("usuario_id") ? req.sesion().usuarioId() : null);
        t.put("creado", System.currentTimeMillis() / 1000);
        t.put("modo", modo);
        t.put("filtros", filtros);
        t.put("ids", servicios.stream().map(s -> Conv.toInt(s.get("id_ordenes_servicio"))).toList());
        t.put("offset", 0);
        t.put("total", servicios.size());
        t.put("errores", new ArrayList<String>());
        t.put("incluir_delegacion", Conv.empty(filtros.get("id_delegacion")));
        t.put("incluir_tecnico", Conv.empty(filtros.get("id_tecnico")));
        t.put("completado", false);
        guardarTrabajo(t);

        Map<String, Object> res = new LinkedHashMap<>();
        res.put("status", "ok");
        res.put("token", token);
        res.put("total", servicios.size());
        res.put("advertencia", advertencia);
        return res;
    }

    @PostMapping(params = "accion=procesarLoteZip")
    public Map<String, Object> procesarLoteZip(Req req) throws IOException {
        String token = req.str("token", "");
        Map<String, Object> t = leerTrabajo(token, req);
        if (t == null) return Map.of("status", "error", "msg", "El trabajo de descarga no existe o expiró.");
        int total = Conv.toInt(t.get("total"));
        if (Boolean.TRUE.equals(t.get("completado"))) return Map.of("status", "ok", "procesados", total, "total", total, "completado", true);

        int lote = Math.max(1, Math.min(10, req.intv("lote", LOTE_PDF_DEFAULT)));
        int offset = Conv.toInt(t.get("offset"));
        if (offset >= total) {
            t.put("completado", true);
            guardarTrabajo(t);
            return Map.of("status", "ok", "procesados", total, "total", total, "completado", true);
        }
        List<Object> todos = Conv.list(t.get("ids"));
        List<Object> idsLote = todos.subList(offset, Math.min(todos.size(), offset + lote));
        Path dir = dirTrabajo(token);
        Files.createDirectories(dir);
        int generados = 0;
        List<String> errores = new ArrayList<>();
        String actual = null;
        for (Object o : idsLote) {
            int id = Conv.toInt(o);
            Map<String, Object> datos = gen.obtenerDatosCompletosOrden(id);
            if (datos == null) {
                errores.add("Orden " + id + ": no encontrada o sin datos.");
                continue;
            }
            var res = gen.generarPdf(id, datos);
            if (!res.ok()) {
                errores.add("Orden " + id + ": " + res.error());
                continue;
            }
            String rel = gen.construirRutaRelativa(datos, res.nombreArchivo(), Boolean.TRUE.equals(t.get("incluir_delegacion")),
                    Boolean.TRUE.equals(t.get("incluir_tecnico")));
            rel = nombreUnico(dir, rel, id);
            Path abs = dir.resolve(rel).normalize();
            try {
                Files.createDirectories(abs.getParent());
                Files.write(abs, res.contenido());
            } catch (Exception e) {
                errores.add("Orden " + id + ": no se pudo guardar el PDF.");
                continue;
            }
            generados++;
            actual = abs.getFileName().toString();
        }
        t = leerTrabajo(token, req);
        if (t == null) return Map.of("status", "error", "msg", "El trabajo de descarga expiró mientras se generaban los PDFs.");
        int nuevoOffset = offset + idsLote.size();
        t.put("offset", nuevoOffset);
        if (!errores.isEmpty()) {
            List<Object> prev = new ArrayList<>(Conv.list(t.get("errores")));
            prev.addAll(errores);
            t.put("errores", prev);
        }
        boolean completado = nuevoOffset >= Conv.toInt(t.get("total"));
        t.put("completado", completado);
        guardarTrabajo(t);
        Map<String, Object> out = new LinkedHashMap<>();
        out.put("status", "ok");
        out.put("procesados", nuevoOffset);
        out.put("total", Conv.toInt(t.get("total")));
        out.put("generados", generados);
        out.put("errores", errores.size());
        out.put("actual", actual);
        out.put("completado", completado);
        return out;
    }

    @RequestMapping(params = "accion=descargarZipTrabajo", method = { RequestMethod.GET, RequestMethod.POST })
    public ResponseEntity<?> descargarZipTrabajo(Req req) throws IOException {
        String token = req.str("token", "");
        Map<String, Object> t = leerTrabajo(token, req);
        if (t == null) return texto("El trabajo de descarga no existe o expiró.");
        if (!Boolean.TRUE.equals(t.get("completado"))) return texto("El ZIP todavía no está listo. Vuelve a intentarlo cuando finalice la generación.");
        Path dir = dirTrabajo(token);
        if (!Files.isDirectory(dir)) return texto("No se encontraron los archivos generados.");

        Path zip = base().resolve(token + ".zip");
        int agregados = 0;
        try (OutputStream fo = Files.newOutputStream(zip); ZipOutputStream zos = new ZipOutputStream(fo, StandardCharsets.UTF_8)) {
            List<Path> archivos;
            try (var w = Files.walk(dir)) {
                archivos = w.filter(Files::isRegularFile).toList();
            }
            for (Path p : archivos) {
                zos.putNextEntry(new ZipEntry(dir.relativize(p).toString().replace('\\', '/')));
                Files.copy(p, zos);
                zos.closeEntry();
                agregados++;
            }
            List<Object> errs = Conv.list(t.get("errores"));
            if (!errs.isEmpty()) {
                zos.putNextEntry(new ZipEntry("errores.txt"));
                zos.write(String.join(System.lineSeparator(), errs.stream().map(String::valueOf).toList()).getBytes(StandardCharsets.UTF_8));
                zos.closeEntry();
            }
        }
        if (agregados == 0) {
            List<Object> errs = Conv.list(t.get("errores"));
            String detalle = errs.isEmpty() ? "" : " Detalle: " + Conv.h(String.join(" | ", errs.stream().limit(3).map(String::valueOf).toList()));
            eliminarTrabajo(token);
            return texto("No se generó ningún PDF. Revisa los filtros e intenta de nuevo." + detalle);
        }
        byte[] datos = Files.readAllBytes(zip);
        eliminarTrabajo(token);
        String nombre = "Servicios_" + LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyyMMdd_HHmmss")) + ".zip";
        return ResponseEntity.ok()
                .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"" + nombre + "\"")
                .header(HttpHeaders.CACHE_CONTROL, "no-store, no-cache, must-revalidate")
                .header("Pragma", "no-cache")
                .contentType(MediaType.parseMediaType("application/zip"))
                .body(datos);
    }

    @RequestMapping(params = "accion=cancelarTrabajoZip", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> cancelarTrabajoZip(Req req) {
        String token = req.str("token", "");
        if (leerTrabajo(token, req) != null) eliminarTrabajo(token);
        return Map.of("status", "ok");
    }

    private static ResponseEntity<?> texto(String msg) {
        return Descarga.html(Conv.h(msg));
    }

    // ================================================================ MODELO

    private int[] restriccion(Req req) {
        int idRol = req.sesion().nivel();
        int meses = 0;
        if (idRol == 4) {
            String v = gen.obtenerParametro("meses_restriccion_prosegur");
            meses = !Conv.empty(v) ? Conv.toInt(v) : 1;
        }
        return new int[] { idRol, meses };
    }

    private Map<String, Object> leerFiltros(Req req) {
        Map<String, Object> f = new LinkedHashMap<>();
        String fi = req.trim("fecha_inicio");
        String ff = req.trim("fecha_fin");
        if (fechaValida(fi)) f.put("fecha_inicio", fi);
        if (fechaValida(ff)) f.put("fecha_fin", ff);
        for (String c : List.of("id_cliente", "id_punto", "id_tecnico", "id_delegacion", "id_tipo_mantenimiento")) {
            if (!Conv.str(req.get(c)).isEmpty()) {
                int v = req.intv(c, 0);
                if (v > 0) f.put(c, v);
            }
        }
        return f;
    }

    private static boolean fechaValida(String s) {
        if (s == null || !s.matches("\\d{4}-\\d{2}-\\d{2}")) return false;
        try {
            return LocalDate.parse(s).toString().equals(s);
        } catch (Exception e) {
            return false;
        }
    }

    private List<Integer> leerIds(Req req) {
        List<String> crudos = new ArrayList<>();
        Object v = req.get("ids");
        if (v instanceof String s) {
            for (String x : s.split(",")) crudos.add(x);
        } else {
            for (Object x : Req.asList(v)) crudos.add(Conv.str(x));
        }
        LinkedHashSet<Integer> r = new LinkedHashSet<>();
        for (String x : crudos) {
            int i = Conv.toInt(x.trim());
            if (i > 0) r.add(i);
        }
        return new ArrayList<>(r);
    }

    private static final String SELECT = """
            SELECT
                o.id_ordenes_servicio,
                o.numero_remision,
                o.fecha_visita,
                o.id_tecnico,
                t.nombre_tecnico,
                COALESCE(c_directo.id_cliente, c_maq.id_cliente) AS id_cliente,
                COALESCE(c_directo.nombre_cliente, c_maq.nombre_cliente, 'SIN CLIENTE') AS nombre_cliente,
                COALESCE(p_directo.id_punto, p_maq.id_punto) AS id_punto,
                COALESCE(p_directo.nombre_punto, p_maq.nombre_punto, 'SIN PUNTO') AS nombre_punto,
                COALESCE(d_directo.id_delegacion, d_maq.id_delegacion) AS id_delegacion,
                COALESCE(d_directo.nombre_delegacion, d_maq.nombre_delegacion, 'SIN ASIGNAR') AS nombre_delegacion,
                o.id_tipo_mantenimiento,
                tman.nombre_completo AS tipo_servicio
            FROM ordenes_servicio o
            LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
            LEFT JOIN tipo_mantenimiento tman ON o.id_tipo_mantenimiento = tman.id_tipo_mantenimiento
            LEFT JOIN cliente c_directo ON o.id_cliente = c_directo.id_cliente
            LEFT JOIN punto p_directo ON o.id_punto = p_directo.id_punto
            LEFT JOIN delegacion d_directo ON p_directo.id_delegacion = d_directo.id_delegacion
            LEFT JOIN maquina m ON o.id_maquina = m.id_maquina
            LEFT JOIN punto p_maq ON m.id_punto = p_maq.id_punto
            LEFT JOIN cliente c_maq ON p_maq.id_cliente = c_maq.id_cliente
            LEFT JOIN delegacion d_maq ON p_maq.id_delegacion = d_maq.id_delegacion""";

    private String condiciones(int idRol, int meses, Map<String, Object> f, Map<String, Object> p) {
        List<String> c = new ArrayList<>(List.of("o.estado = 1"));
        if (idRol == 4 && meses > 0) {
            c.add("o.fecha_visita <= DATE_SUB(CURDATE(), INTERVAL :meses MONTH)");
            p.put("meses", meses);
        }
        String fi = Conv.str(f.get("fecha_inicio")).trim(), ff = Conv.str(f.get("fecha_fin")).trim();
        if (!fi.isEmpty() && !ff.isEmpty()) {
            c.add("o.fecha_visita BETWEEN :fi AND :ff");
            p.put("fi", fi);
            p.put("ff", ff);
        } else if (!fi.isEmpty()) {
            c.add("o.fecha_visita >= :fi");
            p.put("fi", fi);
        } else if (!ff.isEmpty()) {
            c.add("o.fecha_visita <= :ff");
            p.put("ff", ff);
        }
        if (!Conv.empty(f.get("id_cliente"))) {
            c.add("COALESCE(c_directo.id_cliente, c_maq.id_cliente) = :cli");
            p.put("cli", f.get("id_cliente"));
        }
        if (!Conv.empty(f.get("id_punto"))) {
            c.add("COALESCE(p_directo.id_punto, p_maq.id_punto) = :pun");
            p.put("pun", f.get("id_punto"));
        }
        if (!Conv.empty(f.get("id_tecnico"))) {
            c.add("o.id_tecnico = :tec");
            p.put("tec", f.get("id_tecnico"));
        }
        if (!Conv.empty(f.get("id_delegacion"))) {
            c.add("COALESCE(d_directo.id_delegacion, d_maq.id_delegacion) = :del");
            p.put("del", f.get("id_delegacion"));
        }
        if (!Conv.empty(f.get("id_tipo_mantenimiento"))) {
            c.add("o.id_tipo_mantenimiento = :tm");
            p.put("tm", f.get("id_tipo_mantenimiento"));
        }
        return String.join(" AND ", c);
    }

    private List<Map<String, Object>> listarServicios(int idRol, int meses, Map<String, Object> f, int limite) {
        Map<String, Object> p = new LinkedHashMap<>();
        String w = condiciones(idRol, meses, f, p);
        try {
            return db.list(SELECT + " WHERE " + w + " ORDER BY o.fecha_visita DESC, o.id_ordenes_servicio DESC LIMIT " + Math.max(1, limite), p);
        } catch (Exception e) {
            return List.of();
        }
    }

    private List<Map<String, Object>> serviciosPorIds(List<Integer> ids, int idRol, int meses) {
        if (ids.isEmpty()) return List.of();
        Map<String, Object> p = new LinkedHashMap<>();
        String w = condiciones(idRol, meses, Map.of(), p) + " AND o.id_ordenes_servicio IN (:ids)";
        p.put("ids", ids);
        try {
            return db.list(SELECT + " WHERE " + w + " ORDER BY o.fecha_visita DESC, o.id_ordenes_servicio DESC", p);
        } catch (Exception e) {
            return List.of();
        }
    }

    // ================================================================ ARCHIVOS DE TRABAJO

    private Path base() {
        return Path.of(System.getProperty("java.io.tmpdir"), "inees_zip_jobs");
    }

    private Path dirTrabajo(String token) {
        return base().resolve(token);
    }

    private Path estado(String token) {
        return base().resolve(token + ".json");
    }

    private synchronized void guardarTrabajo(Map<String, Object> t) {
        try {
            Files.writeString(estado(Conv.str(t.get("token"))), Req.jsonEncode(t), StandardCharsets.UTF_8);
        } catch (IOException ignored) {
        }
    }

    private Map<String, Object> leerTrabajo(String token, Req req) {
        if (token == null || !token.matches("[a-f0-9]{32}")) return null;
        Path p = estado(token);
        if (!Files.isRegularFile(p)) return null;
        try {
            Object o = Req.jsonDecode(Files.readString(p, StandardCharsets.UTF_8));
            if (!(o instanceof Map<?, ?>)) return null;
            Map<String, Object> t = new LinkedHashMap<>(Conv.map(o));
            if (req.sesion().has("usuario_id")) {
                Integer dueño = t.get("usuario_id") == null ? null : Conv.toInt(t.get("usuario_id"));
                if (dueño == null || dueño != req.sesion().usuarioId()) return null;
            }
            return t;
        } catch (Exception e) {
            return null;
        }
    }

    private String nombreUnico(Path dir, String rel, int idOrden) {
        if (!Files.exists(dir.resolve(rel))) return rel;
        int barra = rel.lastIndexOf('/');
        String prefijo = barra >= 0 ? rel.substring(0, barra + 1) : "";
        String nombre = barra >= 0 ? rel.substring(barra + 1) : rel;
        String sinExt = nombre.contains(".") ? nombre.substring(0, nombre.lastIndexOf('.')) : nombre;
        return prefijo + sinExt + "_" + idOrden + ".pdf";
    }

    private void limpiarTrabajosViejos() {
        try (var s = Files.list(base())) {
            for (Path p : s.filter(x -> x.toString().endsWith(".json")).toList()) {
                long creado = 0;
                try {
                    creado = Conv.toLong(Conv.map(Req.jsonDecode(Files.readString(p))).get("creado"));
                } catch (Exception ignored) {
                }
                if (creado == 0 || (System.currentTimeMillis() / 1000 - creado) > TTL_TRABAJO_SEGUNDOS) {
                    String n = p.getFileName().toString();
                    eliminarTrabajo(n.substring(0, n.length() - 5));
                }
            }
        } catch (Exception ignored) {
        }
    }

    private void eliminarTrabajo(String token) {
        if (token == null || !token.matches("[a-f0-9]{32}")) return;
        Path d = dirTrabajo(token);
        if (Files.isDirectory(d)) {
            try (var w = Files.walk(d)) {
                w.sorted(java.util.Comparator.reverseOrder()).forEach(x -> x.toFile().delete());
            } catch (Exception ignored) {
            }
        }
        try {
            Files.deleteIfExists(estado(token));
            Files.deleteIfExists(base().resolve(token + ".zip"));
        } catch (Exception ignored) {
        }
    }
}
