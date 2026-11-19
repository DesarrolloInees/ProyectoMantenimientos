package co.inees.mantenimientos.modulos.orden;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.security.MessageDigest;
import java.time.Duration;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.HexFormat;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;
import co.inees.mantenimientos.core.Sesion;

/**
 * Port de ordenDetalleControlador.php y ordenDetalleBuscarControlador.php
 * (ordenDetalleBuscar reutiliza el mismo modelo y las mismas acciones AJAX).
 */
@RestController
@RequestMapping("/api")
public class OrdenDetalleController {

    private static final Logger log = LoggerFactory.getLogger(OrdenDetalleController.class);
    private static final Pattern P_HORA = Pattern.compile("^(\\d{1,2}):(\\d{2})");

    private final OrdenDetalleModelo modelo;
    private final Archivos archivos;
    private final String groqKey1;
    private final String groqKey2;
    private final HttpClient http = HttpClient.newBuilder().connectTimeout(Duration.ofSeconds(10)).build();

    public OrdenDetalleController(OrdenDetalleModelo modelo, Archivos archivos,
                                  @Value("${inees.groq-api-key:}") String k1, @Value("${inees.groq-api-key-2:}") String k2) {
        this.modelo = modelo;
        this.archivos = archivos;
        this.groqKey1 = k1 == null ? "" : k1.trim();
        this.groqKey2 = k2 == null ? "" : k2.trim();
    }

    private static void ocultarValores(List<Map<String, Object>> servicios, int rol) {
        if (rol == 5 && servicios != null) {
            for (Map<String, Object> s : servicios) {
                s.put("valor_servicio", 0);
                s.put("valor_viaticos", 0);
            }
        }
    }

    private Map<String, Object> catalogos() {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("listaClientes", modelo.obtenerTodosLosClientes());
        r.put("listaTecnicos", modelo.obtenerTodosLosTecnicos());
        r.put("listaMantos", modelo.obtenerTiposMantenimiento());
        r.put("listaRepuestos", modelo.obtenerListaRepuestos());
        r.put("listaEstados", modelo.obtenerEstados());
        r.put("listaCalifs", modelo.obtenerCalificaciones());
        r.put("listaModalidades", modelo.obtenerModalidades());
        r.put("listaFestivos", modelo.obtenerFestivos());
        r.put("listaNovedades", modelo.obtenerTiposNovedad());
        return r;
    }

    // ================================================================ VISTAS

    @GetMapping(value = "/ordenDetalle", params = "accion=index")
    public Map<String, Object> cargarVista(Req req, Sesion s) {
        String fecha = req.str("fecha", F.hoy());
        if (fecha.isEmpty()) fecha = F.hoy();
        List<Map<String, Object>> servicios = modelo.obtenerServiciosPorFecha(fecha);
        ocultarValores(servicios, s.nivel());
        Map<String, Object> r = catalogos();
        r.put("titulo", "Edición Total: " + fecha);
        r.put("fecha", fecha);
        r.put("servicios", servicios);
        r.put("remisionesGlobales", modelo.obtenerTodasRemisionesDisponibles());
        r.put("rolUsuario", s.nivel());
        return r;
    }

    @GetMapping(value = "/ordenDetalleBuscar", params = "accion=index")
    public Map<String, Object> cargarVistaBusqueda(Sesion s) {
        Map<String, Object> r = catalogos();
        r.put("listaDelegaciones", modelo.obtenerDelegaciones());
        r.put("titulo", "Buscador Individual de Servicios");
        r.put("remisionesGlobales", modelo.obtenerTodasRemisionesDisponibles());
        r.put("rolUsuario", s.nivel());
        return r;
    }

    // ================================================================ AJAX (comunes a ordenDetalle y ordenDetalleBuscar)

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxObtenerPuntos")
    public List<Map<String, Object>> ajaxObtenerPuntos(Req req) {
        List<Map<String, Object>> puntos = new ArrayList<>(modelo.obtenerPuntosPorCliente(req.intv("id_cliente")));
        return reinyectarValorInactivo(puntos, req.str("id_punto_actual"), req.str("nombre_punto_actual"), "id_punto", "nombre_punto");
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxObtenerMaquinas")
    public List<Map<String, Object>> ajaxObtenerMaquinas(Req req) {
        List<Map<String, Object>> maq = new ArrayList<>(modelo.obtenerMaquinasPorPunto(req.intv("id_punto")));
        return reinyectarValorInactivo(maq, req.str("id_maquina_actual"), req.str("nombre_maquina_actual"), "id_maquina", "device_id");
    }

    private static List<Map<String, Object>> reinyectarValorInactivo(List<Map<String, Object>> lista, String idActual, String nombreActual,
                                                                      String campoId, String campoNombre) {
        idActual = idActual == null ? "" : idActual.trim();
        nombreActual = nombreActual == null ? "" : nombreActual.trim();
        if (idActual.isEmpty() || nombreActual.isEmpty()) return lista;
        for (Map<String, Object> it : lista) if (Conv.str(it.get(campoId)).equals(idActual)) return lista;
        Map<String, Object> m = new LinkedHashMap<>();
        m.put(campoId, idActual);
        m.put(campoNombre, nombreActual + " (desactivado)");
        m.put("inactivo", true);
        lista.add(m);
        return lista;
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxObtenerDelegacion")
    public Map<String, Object> ajaxObtenerDelegacion(Req req) {
        return Map.of("delegacion", modelo.obtenerDelegacionPorPunto(req.intv("id_punto")));
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxObtenerPrecio")
    public Map<String, Object> ajaxObtenerPrecio(Req req, Sesion s) {
        if (s.nivel() == 5) return Map.of("precio", 0);
        String fecha = req.str("fecha_visita", F.hoy());
        String anio = fecha.length() >= 4 ? fecha.substring(0, 4) : F.hoy().substring(0, 4);
        double precio = modelo.obtenerPrecioTarifa(req.intv("id_tipo_maquina"), req.intv("id_tipo_mantenimiento"),
                req.intv("id_modalidad", 1), anio);
        return Map.of("precio", precio == Math.floor(precio) ? (Object) (long) precio : precio);
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxObtenerStockTecnico")
    public List<Map<String, Object>> ajaxObtenerStockTecnico(Req req) {
        int t = req.intv("id_tecnico");
        return t > 0 ? modelo.obtenerStockPorTecnico(t) : List.of();
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxObtenerRemisiones")
    public List<Map<String, Object>> ajaxObtenerRemisiones(Req req) {
        int t = req.intv("id_tecnico");
        String actual = req.trim("remision_actual");
        return t > 0 ? modelo.obtenerRemisionesDisponiblesPorTecnico(t, actual.isEmpty() ? null : actual) : List.of();
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxGestionarRepuestoRT")
    public Map<String, Object> ajaxGestionarRepuestoRT(Req req) {
        String tipo = req.str("tipo");
        if ("agregar".equals(tipo)) {
            return modelo.agregarRepuestoRealTime(req.get("id_orden"), req.get("id_repuesto"), req.get("cantidad"), req.str("origen"), req.get("id_tecnico"));
        }
        return modelo.eliminarRepuestoRealTime(req.get("id_orden"), req.get("id_repuesto"), req.str("origen"), req.get("id_tecnico"));
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxGuardarNovedad")
    public Map<String, Object> ajaxGuardarNovedad(Req req) {
        int idOrden = req.intv("id_orden");
        if (idOrden <= 0) return Map.of("success", false, "msg", "ID de orden inválido");
        List<Object> nov = new ArrayList<>();
        Object raw = req.get("novedades");
        if (raw instanceof Map<?, ?> m) nov.addAll(m.values());
        else if (raw != null && !Conv.str(raw).isEmpty()) nov.add(raw);
        return Map.of("success", modelo.guardarNovedadesOrden(idOrden, nov));
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxExportarDetalle")
    public Map<String, Object> ajaxExportarDetalle(Req req, Sesion s) {
        String fecha = req.str("fecha", F.hoy());
        List<Map<String, Object>> servicios = modelo.obtenerServiciosPorFecha(fecha);
        Map<String, String> mapaNov = new LinkedHashMap<>();
        for (Map<String, Object> n : modelo.obtenerTiposNovedad()) mapaNov.put(Conv.str(n.get("id_tipo_novedad")), Conv.str(n.get("nombre_novedad")));
        for (Map<String, Object> sv : servicios) {
            if (s.nivel() == 5) {
                sv.put("valor_servicio", 0);
                sv.put("valor_viaticos", 0);
            }
            String ids = Conv.str(sv.get("ids_novedades"));
            if (!ids.isEmpty()) {
                List<String> nombres = new ArrayList<>();
                for (String id : ids.split(",")) {
                    String k = id.trim();
                    nombres.add(mapaNov.getOrDefault(k, "ID:" + k));
                }
                sv.put("nombres_novedades_resueltos", String.join(", ", nombres));
            } else {
                sv.put("nombres_novedades_resueltos", "");
            }
        }
        return Map.of("status", "ok", "datos", servicios);
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxExportarBusqueda")
    public Map<String, Object> ajaxExportarBusqueda(Req req, Sesion s) {
        List<Map<String, Object>> r = modelo.buscarOrdenesFiltros(filtros(req));
        ocultarValores(r, s.nivel());
        if (!r.isEmpty()) return Map.of("status", "ok", "datos", r);
        return Map.of("status", "error", "msg", "No hay datos");
    }

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxBuscarOrdenes")
    public Map<String, Object> ajaxBuscarOrdenes(Req req, Sesion s) {
        List<Map<String, Object>> servicios = modelo.buscarOrdenesFiltros(filtros(req));
        ocultarValores(servicios, s.nivel());
        return Map.of("servicios", servicios);
    }

    private static Map<String, String> filtros(Req req) {
        Map<String, String> f = new LinkedHashMap<>();
        for (String k : List.of("remision", "id_cliente", "id_punto", "id_delegacion", "fecha_inicio", "fecha_fin")) f.put(k, req.str(k));
        return f;
    }

    // ================================================================ GUARDAR

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxGuardarCambiosJSON")
    public Map<String, Object> ajaxGuardarCambiosJSON(Req req, Sesion s) {
        Object decod = Req.jsonDecode(req.str("json_data"));
        Map<String, Object> servicios = Conv.map(decod);
        if (decod instanceof List<?> l) {
            servicios = new LinkedHashMap<>();
            for (int i = 0; i < l.size(); i++) servicios.put(String.valueOf(i), l.get(i));
        }
        String fechaOrigen = req.str("fecha_origen", F.hoy());
        int rol = s.nivel();
        if (servicios.isEmpty()) return Map.of("status", "error", "msg", "No se recibieron datos para guardar.");
        int errores = 0;
        for (Map.Entry<String, Object> e : servicios.entrySet()) {
            Map<String, Object> datos = new LinkedHashMap<>(Conv.map(e.getValue()));
            if (rol != 5 && datos.containsKey("valor") && datos.get("valor") != null) {
                datos.put("valor", Conv.str(datos.get("valor")).replace(".", "").replace(",", "."));
            }
            datos.put("entrada", sanitizarHora(Conv.str(datos.get("entrada"))));
            datos.put("salida", sanitizarHora(Conv.str(datos.get("salida"))));
            completarTiempo(datos);
            if (Conv.empty(datos.get("fecha_individual"))) datos.put("fecha_individual", fechaOrigen);
            if (!modelo.actualizarOrdenFull(e.getKey(), datos, rol)) errores++;
        }
        if (errores > 0) return Map.of("status", "warning", "msg", "Se guardaron los cambios, pero hubo errores en " + errores + " filas.");
        return Map.of("status", "ok", "msg", "¡Todos los cambios guardados correctamente sin recargar la página!");
    }

    /** guardarCambios (envío de formulario tradicional) */
    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=guardarCambios")
    public Map<String, Object> guardarCambios(Req req, Sesion s) {
        Map<String, Object> servicios = req.mapa("servicios");
        String fechaOrigen = req.str("fecha_origen", F.hoy());
        boolean esBusqueda = "1".equals(req.str("es_busqueda"));
        int rol = s.nivel();
        int errores = 0;
        for (Map.Entry<String, Object> e : servicios.entrySet()) {
            Map<String, Object> datos = new LinkedHashMap<>(Conv.map(e.getValue()));
            if (rol != 5 && datos.get("valor") != null) {
                datos.put("valor", Conv.str(datos.get("valor")).replace(".", "").replace(",", "."));
            }
            completarTiempo(datos);
            if (Conv.empty(datos.get("fecha_individual"))) datos.put("fecha_individual", fechaOrigen);
            if (!modelo.actualizarOrdenFull(e.getKey(), datos, rol)) errores++;
        }
        String destino = esBusqueda ? "ordenDetalleBuscar" : "ordenDetalle/" + fechaOrigen;
        String msg = errores > 0 ? "Se guardaron los cambios, pero hubo errores en " + errores + " filas." : "¡Cambios guardados correctamente!";
        return Map.of("status", errores > 0 ? "warning" : "ok", "msg", msg, "redirect", destino);
    }

    private static void completarTiempo(Map<String, Object> datos) {
        if (Conv.empty(datos.get("tiempo"))) {
            datos.put("tiempo", "00:00");
            String en = Conv.str(datos.get("entrada"));
            String sa = Conv.str(datos.get("salida"));
            if (!en.isEmpty() && !sa.isEmpty()) {
                try {
                    LocalTime d1 = F.hora(en);
                    LocalTime d2 = F.hora(sa);
                    if (d1 != null && d2 != null) {
                        long min = Duration.between(d1, d2).toMinutes();
                        if (min < 0) min += 24 * 60;
                        datos.put("tiempo", String.format("%02d:%02d", min / 60, min % 60));
                    }
                } catch (Exception ignored) {
                }
            }
        }
    }

    static String sanitizarHora(String valor) {
        if (valor == null || valor.trim().isEmpty()) return "";
        String v = valor.trim();
        Matcher m = P_HORA.matcher(v);
        if (m.find()) {
            return String.format("%02d:%02d", Math.min(23, Integer.parseInt(m.group(1))), Math.min(59, Integer.parseInt(m.group(2))));
        }
        String nums = v.replaceAll("\\D", "");
        if (nums.isEmpty()) return "";
        nums = (nums + "0000").substring(0, 4);
        return String.format("%02d:%02d", Math.min(23, Integer.parseInt(nums.substring(0, 2))), Math.min(59, Integer.parseInt(nums.substring(2, 4))));
    }

    // ================================================================ IA (Groq)

    @PostMapping(value = {"/ordenDetalle", "/ordenDetalleBuscar"}, params = "accion=ajaxMejorarTextoIA")
    public Map<String, Object> ajaxMejorarTextoIA(Req req) {
        return mejorarTexto(req.str("texto"));
    }

    public Map<String, Object> mejorarTexto(String textoOriginal) {
        if (textoOriginal == null || textoOriginal.trim().isEmpty()) return Map.of("status", "error", "msg", "Texto vacío");
        List<String> keys = new ArrayList<>();
        if (!groqKey1.isEmpty()) keys.add(groqKey1);
        if (!groqKey2.isEmpty()) keys.add(groqKey2);
        if (keys.isEmpty()) return Map.of("status", "error", "msg", "No se encontraron API Keys en el .env");

        Path cacheFile = archivos.resolver("app/temp/cache_ia_" + md5(textoOriginal) + ".json");
        try {
            if (Files.exists(cacheFile) && (System.currentTimeMillis() - Files.getLastModifiedTime(cacheFile).toMillis()) < 86_400_000L) {
                Map<String, Object> cache = Conv.map(Req.jsonDecode(Files.readString(cacheFile, StandardCharsets.UTF_8)));
                if (!Conv.empty(cache.get("texto_mejorado"))) {
                    return Map.of("status", "ok", "texto_mejorado", cache.get("texto_mejorado"), "cache", true);
                }
            }
        } catch (Exception ignored) {
        }

        int total = textoOriginal.codePointCount(0, textoOriginal.length());
        int minimo = (int) Math.floor(total * 0.8);
        int maximo = (int) Math.ceil(total * 1.5);
        String prompt = "TAREA: Reorganiza y mejora la redacción del siguiente reporte técnico de mantenimiento (español).\n"
                + "REGLAS OBLIGATORIAS:\n"
                + "1. CONSERVA EL 100% DE LA INFORMACIÓN: NO resumas, NO omitas y NO elimines datos, medidas, marcas, modelos, seriales, códigos de error, repuestos, cantidades, tiempos, nombres, puntos, remisiones ni observaciones.\n"
                + "2. EXTENSIÓN: la respuesta debe tener al menos " + minimo + " caracteres y como máximo unos " + maximo + ". Si una idea está abreviada, telegráfica o incompleta, AMPLÍALA con detalle técnico; si ya está completa, reordénala y redáctala mejor sin encogerla de más.\n"
                + "3. CONSERVA CIFRAS Y CÓDIGOS TAL CUAL: números, cantidades, seriales, modelos, versiones, medidas y códigos de error se escriben en dígitos exactamente como aparecen (ej: 20, 045, 3.2.1), nunca con palabras ni aproximaciones.\n"
                + "4. Corrige ortografía, tildes, puntuación y gramática, y usa lenguaje técnico profesional.\n"
                + "5. Reordena la información de forma lógica y coherente (por actividad, por equipo/sistema o cronológicamente). Puedes unir o separar ideas y mejorar la redacción, pero NUNCA descartar ninguna.\n"
                + "6. NO INVENTES INFORMACIÓN: amplía solo lo que ya estaba escrito; NO agregues equipos, fallas, procedimientos, pruebas, repuestos ni datos que no aparezcan en el texto original.\n"
                + "7. Devuelve SOLO el texto final del reporte: sin títulos, sin listas de reglas, sin comillas y sin explicaciones.\n"
                + "Texto original (" + total + " caracteres):\n";
        String[] modelos = {"openai/gpt-oss-20b", "qwen/qwen3.8-27b"};
        int tokens = (int) Math.ceil(total / 2.2);
        int maxTokens = Math.min(Math.max(900, tokens * 2 + 400), 4096);
        long deadline = System.currentTimeMillis() + 25_000;
        int idx = 0;
        String mejorado = null;
        List<String> errores = new ArrayList<>();
        for (String modeloIa : modelos) {
            if (System.currentTimeMillis() >= deadline) {
                errores.add("Tiempo agotado antes de probar " + modeloIa);
                break;
            }
            String key = keys.get(idx++ % keys.size());
            Map<String, Object> body = new LinkedHashMap<>();
            body.put("model", modeloIa);
            body.put("messages", List.of(
                    Map.of("role", "system", "content", "Eres un editor técnico experto en mantenimiento industrial. Conservas el 100% de la información del reporte, nunca resumes ni recortas, amplías solo lo que ya está escrito y respetas cifras, seriales y códigos tal cual aparecen."),
                    Map.of("role", "user", "content", prompt + textoOriginal)));
            body.put("temperature", 0.1);
            body.put("max_tokens", maxTokens);
            try {
                HttpRequest rq = HttpRequest.newBuilder(URI.create("https://api.groq.com/openai/v1/chat/completions"))
                        .timeout(Duration.ofSeconds(15))
                        .header("Content-Type", "application/json")
                        .header("Authorization", "Bearer " + key)
                        .POST(HttpRequest.BodyPublishers.ofString(Req.jsonEncode(body)))
                        .build();
                HttpResponse<String> rs = http.send(rq, HttpResponse.BodyHandlers.ofString());
                if (rs.statusCode() == 200) {
                    Map<String, Object> j = Conv.map(Req.jsonDecode(rs.body()));
                    List<Object> choices = Conv.list(j.get("choices"));
                    String cand = choices.isEmpty() ? "" : Conv.str(Conv.map(Conv.map(choices.get(0)).get("message")).get("content")).trim();
                    if (!cand.isEmpty()) {
                        mejorado = cand;
                        break;
                    }
                    errores.add(modeloIa + " -> Respuesta 200 sin contenido");
                } else if (rs.statusCode() == 429) {
                    errores.add(modeloIa + " -> Límite gratis alcanzado (429), se probó siguiente modelo");
                } else {
                    errores.add(modeloIa + " -> HTTP " + rs.statusCode());
                }
            } catch (Exception e) {
                errores.add(modeloIa + " -> " + e.getMessage());
            }
        }
        if (mejorado != null && !mejorado.trim().isEmpty()) {
            try {
                Files.createDirectories(cacheFile.getParent());
                Files.writeString(cacheFile, Req.jsonEncode(Map.of("texto_mejorado", mejorado.trim(), "fecha", F.ahora())), StandardCharsets.UTF_8);
            } catch (Exception ignored) {
            }
            Map<String, Object> r = new LinkedHashMap<>();
            r.put("status", "ok");
            r.put("texto_mejorado", mejorado.trim());
            r.put("minimo_esperado", minimo);
            r.put("cache", false);
            return r;
        }
        log.error("IA Detalle - fallo: {}", String.join(" | ", errores));
        return Map.of("status", "error", "msg",
                "La IA no respondió a tiempo o el servicio está saturado. Tu texto original quedó intacto; intenta de nuevo en unos segundos.");
    }

    private static String md5(String s) {
        try {
            return HexFormat.of().formatHex(MessageDigest.getInstance("MD5").digest(s.getBytes(StandardCharsets.UTF_8)));
        } catch (Exception e) {
            return Integer.toHexString(s.hashCode());
        }
    }
}
