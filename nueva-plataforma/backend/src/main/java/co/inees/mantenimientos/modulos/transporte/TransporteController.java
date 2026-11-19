package co.inees.mantenimientos.modulos.transporte;

import java.nio.file.Files;
import java.nio.file.Path;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.Base64;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Pdf;
import co.inees.mantenimientos.core.Req;

/** Port de app/controllers/transporte/* (transporteVer, transporteCrear, transporteEditar, transportePdf) y modelos. */
@RestController
public class TransporteController {

    private static final Logger log = LoggerFactory.getLogger(TransporteController.class);
    private final Db db;
    private final Archivos archivos;
    private final Pdf pdf;

    public TransporteController(Db db, Archivos archivos, Pdf pdf) {
        this.db = db;
        this.archivos = archivos;
        this.pdf = pdf;
    }

    private List<Map<String, Object>> lista(String sql, Object... kv) {
        try {
            return db.list(sql, kv);
        } catch (Exception e) {
            log.error("Transporte: {}", e.getMessage());
            return List.of();
        }
    }

    private Map<String, Object> catalogos() {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("tecnicos", lista("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
        r.put("tiposMaquina", lista("SELECT id_tipo_maquina, nombre_tipo_maquina FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC"));
        r.put("clientes", lista("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC"));
        return r;
    }

    private List<Map<String, Object>> puntosCliente(Object idCliente) {
        return lista("""
                SELECT id_punto, nombre_punto, direccion
                FROM punto
                WHERE id_cliente = :id_cliente AND estado = 1
                ORDER BY nombre_punto ASC""", "id_cliente", Conv.toInt(idCliente));
    }

    private List<Map<String, Object>> remisiones(int idTecnico, Integer idActual) {
        if (idActual == null) {
            return lista("""
                    SELECT cr.id_control, cr.numero_remision
                    FROM control_remisiones cr
                    INNER JOIN estados_remision er ON cr.id_estado = er.id_estado
                    WHERE cr.id_tecnico = :id_tecnico
                    AND UPPER(er.nombre_estado) LIKE '%DISPONIBLE%'
                    ORDER BY cr.numero_remision ASC""", "id_tecnico", idTecnico);
        }
        return lista("""
                SELECT cr.id_control, cr.numero_remision
                FROM control_remisiones cr
                INNER JOIN estados_remision er ON cr.id_estado = er.id_estado
                WHERE cr.id_tecnico = :id_tecnico
                AND (UPPER(er.nombre_estado) LIKE '%DISPONIBLE%' OR cr.id_control = :id_actual)
                ORDER BY cr.numero_remision ASC""", "id_tecnico", idTecnico, "id_actual", idActual);
    }

    /** parseDinamico(): número → id, texto → texto libre */
    private static Object[] dinamico(String v) {
        String s = v == null ? "" : v.trim();
        if (s.isEmpty()) return new Object[] { null, null };
        if (Conv.esNumero(s)) return new Object[] { (int) Conv.toDouble(s), null };
        return new Object[] { null, s };
    }

    private static Object vacioNull(Object v) {
        return Conv.empty(v) ? null : v;
    }

    private static Integer intONull(Object v) {
        return Conv.empty(v) ? null : Conv.toInt(v);
    }

    /** Campos comunes de crear/actualizar (mismo mapeo que el PHP). */
    private static Map<String, Object> datosComunes(Req req) {
        String cat = req.str("categoria_servicio", "");
        String tipo = null;
        if (cat.equals("Inees")) tipo = req.str("tipo_servicio_inees", null);
        else if (cat.equals("Prosegur_Cobro")) tipo = req.str("tipo_servicio_cobro", null);
        else if (cat.equals("Prosegur_NoCobro")) tipo = req.str("tipo_servicio_nocobro", null);
        Object[] co = dinamico(req.str("cliente_origen", "")), po = dinamico(req.str("punto_origen", ""));
        Object[] cd = dinamico(req.str("cliente_destino", "")), pd = dinamico(req.str("punto_destino", ""));
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("id_tecnico", Conv.toInt(req.str("id_tecnico", "0")));
        p.put("id_control_remision", intONull(req.get("id_control_remision")));
        p.put("fecha_instalacion", vacioNull(req.get("fecha_instalacion")));
        p.put("categoria_servicio", cat);
        p.put("tipo_servicio_nombre", tipo);
        p.put("notas", req.str("notas", "").trim());
        p.put("descripcion_inees", req.str("descripcion_inees", "").trim());
        p.put("lugar_recogida", req.str("lugar_recogida", null));
        p.put("fecha_recogida", vacioNull(req.get("fecha_recogida")));
        p.put("es_maquina", req.has("es_maquina") ? req.intv("es_maquina", 1) : 1);
        p.put("id_tipo_maquina", intONull(req.get("id_tipo_maquina")));
        p.put("serial_maquina", req.str("serial_maquina", "").trim());
        p.put("producto_otro", req.str("producto_otro", "").trim());
        p.put("id_cliente_origen", co[0]);
        p.put("cliente_origen_texto", co[1]);
        p.put("id_punto_origen", po[0]);
        p.put("punto_origen_texto", po[1]);
        p.put("id_cliente_destino", cd[0]);
        p.put("cliente_destino_texto", cd[1]);
        p.put("id_punto_destino", pd[0]);
        p.put("punto_destino_texto", pd[1]);
        return p;
    }

    // ================================================================ VER

    @GetMapping(value = "/api/transporteVer", params = "accion=index")
    public Map<String, Object> verIndex() {
        return Map.of("titulo", "Gestión de Transportes");
    }

    @GetMapping(value = "/api/transporteVer", params = "accion=ajaxListar")
    public Map<String, Object> listar() {
        var datos = lista("""
                SELECT
                    i.id_instalacion,
                    i.fecha_instalacion,
                    i.categoria_servicio,
                    i.tipo_servicio_nombre,
                    i.es_maquina,
                    i.serial_maquina,
                    i.producto_otro,
                    t.nombre_tecnico,
                    tm.nombre_tipo_maquina,
                    c.nombre_cliente,
                    i.cliente_destino_texto,
                    p.nombre_punto,
                    i.punto_destino_texto,
                    i.estado
                FROM instalaciones_desinstalaciones i
                LEFT JOIN tecnico t ON i.id_tecnico = t.id_tecnico
                LEFT JOIN tipo_maquina tm ON i.id_tipo_maquina = tm.id_tipo_maquina
                LEFT JOIN cliente c ON i.id_cliente_destino = c.id_cliente
                LEFT JOIN punto p ON i.id_punto_destino = p.id_punto
                WHERE i.estado = 1
                ORDER BY i.id_instalacion DESC""");
        return Map.of("data", datos);
    }

    @PostMapping(value = "/api/transporteVer", params = "accion=eliminar")
    public Map<String, Object> eliminar(Req req) {
        if (!req.has("id")) return Map.of("status", "error", "message", "Petición inválida.");
        try {
            db.update("UPDATE instalaciones_desinstalaciones SET estado = 0 WHERE id_instalacion = :id", "id", req.intv("id", 0));
            return Map.of("status", "success", "message", "Registro eliminado correctamente.");
        } catch (Exception e) {
            log.error("ERROR al eliminar instalacion: {}", e.getMessage());
            return Map.of("status", "error", "message", "No se pudo eliminar el registro.");
        }
    }

    // ================================================================ CREAR

    @GetMapping(value = "/api/transporteCrear", params = "accion=index")
    public Map<String, Object> crearIndex() {
        Map<String, Object> r = catalogos();
        r.put("titulo", "Registro de Transportes");
        r.put("hoy", F.hoy());
        return r;
    }

    @PostMapping(value = { "/api/transporteCrear", "/api/transporteEditar" }, params = "accion=ajaxPuntos")
    public Object ajaxPuntos(Req req) {
        String id = req.str("id_cliente", "");
        if (id.isEmpty() || !Conv.esNumero(id)) return List.of();
        return puntosCliente(id);
    }

    @PostMapping(value = { "/api/transporteCrear", "/api/transporteEditar" }, params = "accion=ajaxRemisiones")
    public Object ajaxRemisiones(Req req) {
        if (Conv.empty(req.get("id_tecnico"))) return List.of();
        return remisiones(req.intv("id_tecnico", 0), null);
    }

    private String subirFoto(MultipartFile f, String dir, String prefijo) {
        if (f == null || f.isEmpty()) return null;
        String ruta = dir + prefijo + "_" + Archivos.uniqid() + "." + Archivos.extension(f.getOriginalFilename());
        try {
            archivos.guardar(f, ruta);
            return ruta;
        } catch (Exception e) {
            return null;
        }
    }

    @PostMapping(value = "/api/transporteCrear", params = "accion=guardar")
    public Map<String, Object> guardar(Req req) {
        Map<String, Object> p = datosComunes(req);
        String nombreRem = req.str("texto_remision", "SIN_REMISION").replaceAll("[^a-zA-Z0-9_-]", "_");
        if (nombreRem.isBlank()) nombreRem = "SIN_REMISION";
        String dir = "app/uploads/transporte/" + nombreRem + "/";
        p.put("valor_servicio", 0.0);
        p.put("foto_remision", subirFoto(req.file("foto_remision"), dir, "remision"));
        p.put("foto_maquina", subirFoto(req.file("foto_maquina"), dir, "maquina"));
        p.put("foto_chazos", subirFoto(req.file("foto_chazos"), dir, "chazos"));
        if (Conv.empty(p.get("categoria_servicio")) || Conv.toInt(p.get("id_tecnico")) == 0 || Conv.empty(p.get("fecha_instalacion")))
            return Map.of("success", false, "message", "⚠️ Faltan datos requeridos (Categoría, Técnico o Fecha).");
        try {
            long id = db.tx(() -> {
                long nuevo = db.insert("""
                        INSERT INTO instalaciones_desinstalaciones
                        (
                            id_tecnico, id_control_remision, fecha_instalacion,
                            categoria_servicio, tipo_servicio_nombre, notas, descripcion_inees,
                            lugar_recogida, fecha_recogida, es_maquina, id_tipo_maquina, serial_maquina, producto_otro,
                            id_cliente_origen, cliente_origen_texto, id_punto_origen, punto_origen_texto,
                            id_cliente_destino, cliente_destino_texto, id_punto_destino, punto_destino_texto,
                            valor_servicio, foto_remision, foto_maquina, foto_chazos, estado, created_at
                        ) VALUES (
                            :id_tecnico, :id_control_remision, :fecha_instalacion,
                            :categoria_servicio, :tipo_servicio_nombre, :notas, :descripcion_inees,
                            :lugar_recogida, :fecha_recogida, :es_maquina, :id_tipo_maquina, :serial_maquina, :producto_otro,
                            :id_cliente_origen, :cliente_origen_texto, :id_punto_origen, :punto_origen_texto,
                            :id_cliente_destino, :cliente_destino_texto, :id_punto_destino, :punto_destino_texto,
                            :valor_servicio, :foto_remision, :foto_maquina, :foto_chazos, 1, NOW()
                        )""", p);
                if (p.get("id_control_remision") != null) marcarRemision(p.get("id_control_remision"), nuevo, p.get("id_tecnico"));
                return nuevo;
            });
            return Map.of("success", true, "message", "✅ Registro #" + id + " guardado correctamente.");
        } catch (Exception e) {
            log.error("ERROR guardando instalación: {}", e.getMessage());
            return Map.of("success", false, "message", "❌ Error al guardar el registro. Revisa los datos e intenta de nuevo.");
        }
    }

    private void marcarRemision(Object idControl, Object idInstalacion, Object idTecnico) {
        db.update("""
                UPDATE control_remisiones
                SET id_estado = :id_estado,
                    fecha_uso = NOW(),
                    id_instalacion = :id_instalacion
                WHERE id_control = :id_control AND id_tecnico = :id_tecnico""",
                "id_estado", 2, "id_instalacion", idInstalacion, "id_control", idControl, "id_tecnico", idTecnico);
    }

    // ================================================================ EDITAR

    @GetMapping(value = "/api/transporteEditar", params = "accion=index")
    public Map<String, Object> editarIndex(Req req) {
        if (Conv.empty(req.get("id"))) return Map.of("error", "ID no especificado.");
        Map<String, Object> inst;
        try {
            inst = db.one("SELECT * FROM instalaciones_desinstalaciones WHERE id_instalacion = :id AND estado = 1 LIMIT 1", "id", req.intv("id", 0));
        } catch (Exception e) {
            inst = null;
        }
        if (inst == null) return Map.of("error", "Registro no encontrado o fue eliminado.");
        Map<String, Object> r = catalogos();
        r.put("titulo", "Editar Transporte #" + inst.get("id_instalacion"));
        r.put("instalacion", inst);
        r.put("puntosOrigen", Conv.empty(inst.get("id_cliente_origen")) ? List.of() : puntosCliente(inst.get("id_cliente_origen")));
        r.put("puntosDestino", Conv.empty(inst.get("id_cliente_destino")) ? List.of() : puntosCliente(inst.get("id_cliente_destino")));
        r.put("remisionesTecnico", Conv.empty(inst.get("id_tecnico")) ? List.of()
                : remisiones(Conv.toInt(inst.get("id_tecnico")), Conv.empty(inst.get("id_control_remision")) ? 0 : Conv.toInt(inst.get("id_control_remision"))));
        return r;
    }

    @RequestMapping(value = "/api/transporteEditar", params = "accion=actualizar", method = RequestMethod.POST)
    public Map<String, Object> actualizar(Req req) {
        if (Conv.empty(req.get("id_instalacion"))) return Map.of("success", false, "message", "Petición inválida.");
        String limpio = req.str("valor_servicio", "0").replace("$", "").replace(".", "").replace(" ", "").replace(",", "");
        double valor = Conv.esNumero(limpio) ? Conv.toDouble(limpio) : 0;
        Map<String, Object> p = datosComunes(req);
        int id = req.intv("id_instalacion", 0);
        p.put("valor_servicio", valor);
        p.put("id_instalacion", id);
        try {
            db.update("""
                    UPDATE instalaciones_desinstalaciones SET
                        id_tecnico = :id_tecnico,
                        id_control_remision = :id_control_remision,
                        fecha_instalacion = :fecha_instalacion,
                        categoria_servicio = :categoria_servicio,
                        tipo_servicio_nombre = :tipo_servicio_nombre,
                        notas = :notas,
                        descripcion_inees = :descripcion_inees,
                        lugar_recogida = :lugar_recogida,
                        fecha_recogida = :fecha_recogida,
                        es_maquina = :es_maquina,
                        id_tipo_maquina = :id_tipo_maquina,
                        serial_maquina = :serial_maquina,
                        producto_otro = :producto_otro,
                        id_cliente_origen = :id_cliente_origen,
                        cliente_origen_texto = :cliente_origen_texto,
                        id_punto_origen = :id_punto_origen,
                        punto_origen_texto = :punto_origen_texto,
                        id_cliente_destino = :id_cliente_destino,
                        cliente_destino_texto = :cliente_destino_texto,
                        id_punto_destino = :id_punto_destino,
                        punto_destino_texto = :punto_destino_texto,
                        valor_servicio = :valor_servicio
                    WHERE id_instalacion = :id_instalacion""", p);
            if (p.get("id_control_remision") != null) {
                try {
                    marcarRemision(p.get("id_control_remision"), id, p.get("id_tecnico"));
                } catch (Exception e) {
                    log.error("ERROR actualizando estado de remisión (editar): {}", e.getMessage());
                }
            }
            String v = String.format(java.util.Locale.US, "%,.0f", valor).replace(",", ".");
            return Map.of("success", true, "message", "✅ Registro actualizado correctamente. Tarifa aplicada: $" + v);
        } catch (Exception e) {
            log.error("ERROR actualizando instalación: {}", e.getMessage());
            return Map.of("success", false, "message", "❌ Error al actualizar el registro.");
        }
    }

    // ================================================================ PDF

    private String base64(String rel) {
        if (Conv.empty(rel)) return null;
        try {
            Path f = archivos.resolver(rel);
            if (!Files.isRegularFile(f)) return null;
            String ext = Archivos.extension(f.getFileName().toString());
            String mime = "pdf".equals(ext) ? "application/pdf" : "image/" + ("jpg".equals(ext) ? "jpeg" : ext);
            return "data:" + mime + ";base64," + Base64.getEncoder().encodeToString(Files.readAllBytes(f));
        } catch (Exception e) {
            return null;
        }
    }

    private static String h(Object v, String def) {
        return Conv.h(Conv.empty(v) ? def : Conv.str(v));
    }

    private static String nl2br(String s) {
        return s.replace("\r\n", "\n").replace("\n", "<br />\n");
    }

    @GetMapping(value = "/api/transportePdf")
    public ResponseEntity<?> generarPdf(Req req) {
        if (Conv.empty(req.get("id"))) return Descarga.html("Error: ID de registro no proporcionado.");
        Map<String, Object> i;
        try {
            i = db.one("""
                    SELECT
                        i.*,
                        t.nombre_tecnico,
                        t.ruta_firma,
                        tm.nombre_tipo_maquina,
                        co.nombre_cliente AS cliente_origen_nombre,
                        po.nombre_punto AS punto_origen_nombre,
                        po.direccion AS direccion_origen,
                        cd.nombre_cliente AS cliente_destino_nombre,
                        pd.nombre_punto AS punto_destino_nombre,
                        pd.direccion AS direccion_destino,
                        cr.numero_remision
                    FROM instalaciones_desinstalaciones i
                    LEFT JOIN tecnico t ON i.id_tecnico = t.id_tecnico
                    LEFT JOIN tipo_maquina tm ON i.id_tipo_maquina = tm.id_tipo_maquina
                    LEFT JOIN cliente co ON i.id_cliente_origen = co.id_cliente
                    LEFT JOIN punto po ON i.id_punto_origen = po.id_punto
                    LEFT JOIN cliente cd ON i.id_cliente_destino = cd.id_cliente
                    LEFT JOIN punto pd ON i.id_punto_destino = pd.id_punto
                    LEFT JOIN control_remisiones cr ON i.id_control_remision = cr.id_control
                    WHERE i.id_instalacion = :id AND i.estado = 1
                    LIMIT 1""", "id", req.intv("id", 0));
        } catch (Exception e) {
            i = null;
        }
        if (i == null) return Descarga.html("Error: No se encontró el registro o fue eliminado.");

        String logo = archivos.existe("app/logos/logoInees.jpg") ? pdf.dataUri("app/logos/logoInees.jpg") : "";
        String firma = "";
        if (!Conv.empty(i.get("ruta_firma"))) {
            String ruta = Conv.str(i.get("ruta_firma"));
            String base = Path.of(ruta.replace('\\', '/')).getFileName().toString();
            String b = base64("app/Imagenes/firmas/" + base);
            if (b == null) b = base64(ruta);
            if (b != null) firma = b;
        }
        String evRem = base64(Conv.str(i.get("foto_remision"))), evMaq = base64(Conv.str(i.get("foto_maquina"))), evCh = base64(Conv.str(i.get("foto_chazos")));
        String cat = Conv.str(i.get("categoria_servicio"));
        String clase = "bg-cobro", textoCat = "PROSEGUR - COBRO";
        if (cat.equals("Prosegur_NoCobro")) { clase = "bg-nocobro"; textoCat = "PROSEGUR - SIN COBRO"; }
        else if (cat.equals("Inees")) { clase = "bg-inees"; textoCat = "INEES (INTERNO)"; }

        String css;
        try (var in = new org.springframework.core.io.ClassPathResource("plantillas/transporte-pdf.css").getInputStream()) {
            css = new String(in.readAllBytes(), java.nio.charset.StandardCharsets.UTF_8);
        } catch (Exception e) {
            css = "";
        }
        StringBuilder s = new StringBuilder();
        s.append("<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><title>Reporte de Transporte #").append(i.get("id_instalacion"))
                .append("</title><style>").append(css).append("</style></head><body><div class=\"header\"><div class=\"header-col\">")
                .append(logo.isEmpty() ? "<h2 style=\"margin:0; color:#1e3a8a;\">SISTEMA LOGÍSTICO</h2>" : "<img src=\"" + logo + "\" class=\"logo\" alt=\"Logo\">")
                .append("</div><div class=\"header-col title-box\"><h1 class=\"title\">Reporte de Operación</h1><p class=\"subtitle\">Consecutivo: <strong>#")
                .append(String.format("%05d", Conv.toInt(i.get("id_instalacion")))).append("</strong></p><p class=\"subtitle\" style=\"margin-top:2px;\">Fecha Realización: ")
                .append(F.dmy(i.get("fecha_instalacion"))).append("</p></div></div>")
                .append("<div class=\"section\"><div class=\"section-title\">1. Información General</div><div class=\"section-body\"><div class=\"row\"><div class=\"col\"><span class=\"label\">Categoría del Servicio</span><span class=\"badge ")
                .append(clase).append("\">").append(textoCat).append("</span></div><div class=\"col\"><span class=\"label\">Tipo de Servicio</span><span class=\"value font-bold\">")
                .append(h(i.get("tipo_servicio_nombre"), "N/A")).append("</span></div></div><div class=\"row\"><div class=\"col\"><span class=\"label\">Técnico Asignado</span><span class=\"value\">")
                .append(h(i.get("nombre_tecnico"), "N/A")).append("</span></div><div class=\"col\"><span class=\"label\">Número de Remisión</span><span class=\"value\">")
                .append(h(i.get("numero_remision"), "Sin remisión asignada")).append("</span></div></div></div></div>");
        if (cat.equals("Inees") && !Conv.empty(i.get("descripcion_inees"))) {
            s.append("<div class=\"section\"><div class=\"section-title\">2. Actividades Internas (Inees)</div><div class=\"section-body\"><div class=\"col-full\"><span class=\"label\">Descripción de lo realizado</span><span class=\"value\">")
                    .append(nl2br(Conv.h(i.get("descripcion_inees")))).append("</span></div></div></div>");
        }
        String destinoCliente = h(Conv.empty(i.get("cliente_destino_nombre")) ? i.get("cliente_destino_texto") : i.get("cliente_destino_nombre"), "N/A");
        if (!cat.equals("Inees")) {
            s.append("<div class=\"section\"><div class=\"section-title\">2. Logística y Producto</div><div class=\"section-body\"><div class=\"row\"><div class=\"col\"><span class=\"label\">Lugar de Recogida</span><span class=\"value\">")
                    .append(h(i.get("lugar_recogida"), "N/A")).append("</span></div><div class=\"col\"><span class=\"label\">Fecha de Recogida</span><span class=\"value\">")
                    .append(Conv.empty(i.get("fecha_recogida")) ? "N/A" : F.dmy(i.get("fecha_recogida"))).append("</span></div></div><div class=\"row\">");
            if (Conv.toInt(i.get("es_maquina")) == 1) {
                s.append("<div class=\"col\"><span class=\"label\">Tipo de Máquina</span><span class=\"value\">").append(h(i.get("nombre_tipo_maquina"), "N/A"))
                        .append("</span></div><div class=\"col\"><span class=\"label\">Serial Físico</span><span class=\"value font-bold\">").append(h(i.get("serial_maquina"), "N/A")).append("</span></div>");
            } else {
                s.append("<div class=\"col-full\"><span class=\"label\">Producto / Elementos Transportados</span><span class=\"value\">").append(h(i.get("producto_otro"), "N/A")).append("</span></div>");
            }
            s.append("</div><div class=\"row\"><div class=\"col-full\" style=\"margin-top:10px; border-top:1px dashed #ccc; padding-top:10px;\"><span class=\"label text-emerald-600\">Tarifa de Cobro del Servicio</span><span class=\"value font-bold\" style=\"font-size:16px;\">$ ")
                    .append(String.format(java.util.Locale.US, "%,.0f", Conv.toDouble(i.get("valor_servicio"))).replace(",", ".")).append("</span></div></div></div></div>");
            String oc = h(Conv.empty(i.get("cliente_origen_nombre")) ? i.get("cliente_origen_texto") : i.get("cliente_origen_nombre"), "N/A");
            String op = h(Conv.empty(i.get("punto_origen_nombre")) ? i.get("punto_origen_texto") : i.get("punto_origen_nombre"), "N/A");
            String dp = h(Conv.empty(i.get("punto_destino_nombre")) ? i.get("punto_destino_texto") : i.get("punto_destino_nombre"), "N/A");
            s.append("<div class=\"section\"><div class=\"section-title\">3. Origen y Destino</div><div class=\"section-body\"><div class=\"row\"><div class=\"col\"><span class=\"label\">Cliente Origen</span><span class=\"value\">")
                    .append(oc).append("</span><br><span class=\"label\" style=\"margin-top:5px;\">Punto Origen</span><span class=\"value\">").append(op)
                    .append("</span></div><div class=\"col\"><span class=\"label\">Cliente Destino</span><span class=\"value font-bold\">").append(destinoCliente)
                    .append("</span><br><span class=\"label\" style=\"margin-top:5px;\">Punto Destino</span><span class=\"value font-bold\">").append(dp).append("</span></div></div></div></div>");
        }
        s.append("<div class=\"section\"><div class=\"section-title\">Observaciones</div><div class=\"section-body\" style=\"padding-top: 5px;\"><div class=\"observaciones\">")
                .append(nl2br(h(i.get("notas"), "Ninguna observación registrada."))).append("</div></div></div>");
        if (evRem != null || evMaq != null || evCh != null) {
            s.append("<div class=\"section no-break\"><div class=\"section-title\"><i class=\"fas fa-camera\"></i> Evidencias del Servicio</div><div class=\"section-body\"><div class=\"evidencia-container\">");
            if (evRem != null) s.append("<div class=\"evidencia-box\"><div class=\"evidencia-titulo\">Remisión</div>")
                    .append(evRem.contains("application/pdf") ? "<span style=\"font-size:10px; color:#666;\">(Documento PDF Adjunto)</span>" : "<img src=\"" + evRem + "\" class=\"evidencia-img\">").append("</div>");
            if (evMaq != null) s.append("<div class=\"evidencia-box\"><div class=\"evidencia-titulo\">Máquina</div><img src=\"").append(evMaq).append("\" class=\"evidencia-img\"></div>");
            if (evCh != null) s.append("<div class=\"evidencia-box\"><div class=\"evidencia-titulo\">Chazos</div><img src=\"").append(evCh).append("\" class=\"evidencia-img\"></div>");
            s.append("</div></div></div>");
        }
        s.append("<div class=\"no-break\"><div class=\"firmas\"><div class=\"firma-box\"><div class=\"firma-espacio\">")
                .append(firma.isEmpty() ? "" : "<img src=\"" + firma + "\" alt=\"Firma Técnico\" class=\"img-firma\">")
                .append("</div><div class=\"firma-linea\"></div><div class=\"firma-nombre\">").append(Conv.h(i.get("nombre_tecnico") == null ? "TÉCNICO NO ASIGNADO" : i.get("nombre_tecnico")))
                .append("</div><div class=\"firma-rol\">Técnico de Servicio</div></div><div class=\"firma-box\"><div class=\"firma-espacio\"></div><div class=\"firma-linea\"></div><div class=\"firma-nombre\">")
                .append(!cat.equals("Inees") ? destinoCliente : "SUPERVISOR INEES").append("</div><div class=\"firma-rol\">Recibido y Aprobado</div></div></div>")
                .append("<div class=\"footer-strip\"><span>SISTEMA LOGÍSTICO Y TRANSPORTES</span><span class=\"remision-footer\">Remisión #")
                .append(Conv.h(i.get("numero_remision") == null ? "N/A" : i.get("numero_remision"))).append("</span><span>Generado el: ")
                .append(LocalDateTime.now().format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm"))).append("</span></div></div></body></html>");
        try {
            byte[] b = pdf.generar(s.toString(), "size: Letter; margin: 10mm");
            String rem = Conv.empty(i.get("numero_remision")) ? "SinRemision" : Conv.str(i.get("numero_remision"));
            return Descarga.pdf(b, "Transporte_" + rem + ".pdf", true);
        } catch (Exception e) {
            return Descarga.html("<h1>Error generando PDF</h1><p>" + Conv.h(e.getMessage()) + "</p>");
        }
    }
}
