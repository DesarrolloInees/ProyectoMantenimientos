package co.inees.mantenimientos.modulos.varios;

import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;
import tools.jackson.databind.json.JsonMapper;

/**
 * Port de informacionBaseDatosControlador.php, exportarExcelControlador.php,
 * geolocalizarControlador.php y rastreoTecnicoControlador.php (con sus modelos).
 */
@RestController
public class VariosController {

    private static final Logger log = LoggerFactory.getLogger(VariosController.class);
    private static final JsonMapper JSON = JsonMapper.builder().build();
    private final Db db;
    private final String googleKey;
    private final HttpClient http = HttpClient.newBuilder().connectTimeout(Duration.ofSeconds(10)).build();

    public VariosController(Db db, @Value("${inees.google-maps-key:}") String googleKey) {
        this.db = db;
        this.googleKey = googleKey;
    }

    private List<Map<String, Object>> lista(String sql, Object... kv) {
        try {
            return db.list(sql, kv);
        } catch (Exception e) {
            log.error("{}", e.getMessage());
            return List.of();
        }
    }

    // ================================================================ INFORMACIÓN BASE DE DATOS

    @GetMapping(value = "/api/informacionBaseDatos", params = "accion=index")
    public Map<String, Object> informacion() {
        Map<String, Object> kpis;
        try {
            kpis = db.one("""
                    SELECT
                        (SELECT COUNT(*) FROM maquina) as total_maquinas,
                        (SELECT COUNT(*) FROM maquina WHERE estado = 1) as maquinas_activas,
                        (SELECT COUNT(*) FROM punto) as total_puntos,
                        (SELECT COUNT(*) FROM delegacion) as total_delegaciones""");
        } catch (Exception e) {
            kpis = Map.of("total_maquinas", 0, "maquinas_activas", 0);
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Estado de Base Instalada");
        r.put("kpis", kpis);
        r.put("datosInventario", lista("""
                SELECT
                    d.nombre_delegacion,
                    tm.nombre_tipo_maquina,
                    COUNT(m.id_maquina) as total
                FROM maquina m
                INNER JOIN punto p ON m.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE m.estado = 1
                GROUP BY d.nombre_delegacion, tm.nombre_tipo_maquina
                ORDER BY d.nombre_delegacion ASC, total DESC"""));
        r.put("topClientes", lista("""
                SELECT c.nombre_cliente, COUNT(m.id_maquina) as total
                FROM maquina m
                INNER JOIN punto p ON m.id_punto = p.id_punto
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                WHERE m.estado = 1
                GROUP BY c.nombre_cliente
                ORDER BY total DESC
                LIMIT 10"""));
        r.put("antiguedad", lista("""
                SELECT
                    CASE
                        WHEN fecha_ultima_visita IS NULL THEN 'Sin Visita Registrada'
                        WHEN fecha_ultima_visita >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 'Al día (< 30 días)'
                        WHEN fecha_ultima_visita >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 'Hace 1-3 meses'
                        ELSE 'Olvidada (> 3 meses)'
                    END as rango,
                    COUNT(*) as total
                FROM punto
                WHERE estado = 1
                GROUP BY rango
                ORDER BY total DESC"""));
        return r;
    }

    // ================================================================ EXPORTAR EXCEL

    @GetMapping(value = "/api/exportarExcel", params = "accion=index")
    public Map<String, Object> exportarIndex() {
        return Map.of("titulo", "Exportación de Datos");
    }

    @GetMapping(value = "/api/exportarExcel", params = "accion=descargarReporte")
    public List<Map<String, Object>> exportarDatos() {
        return lista("""
                SELECT
                    c.codigo_cliente,
                    c.nombre_cliente,
                    p.nombre_punto,
                    p.direccion,
                    mu.nombre_municipio,
                    p.zona,
                    d.nombre_delegacion,
                    m.device_id,
                    tmq.nombre_tipo_maquina,
                    p.fecha_ultima_visita,
                    tm.nombre_completo as nombre_mantenimiento,
                    p.frecuencia_mantenimiento_dias
                FROM punto p
                INNER JOIN maquina m ON p.id_punto = m.id_punto
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                INNER JOIN municipio mu ON p.id_municipio = mu.id_municipio
                INNER JOIN tipo_maquina tmq ON m.id_tipo_maquina = tmq.id_tipo_maquina
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                LEFT JOIN tipo_mantenimiento tm ON p.id_ultimo_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE p.fecha_actualizacion IS NOT NULL
                    AND p.estado = 1
                ORDER BY c.nombre_cliente ASC, p.nombre_punto ASC""");
    }

    // ================================================================ GEOLOCALIZAR (lote Google Geocoding)

    @GetMapping(value = "/api/geolocalizar", params = "accion=index")
    public Map<String, Object> geolocalizar() {
        int limite = 5;
        var puntos = lista("""
                SELECT p.id_punto, p.direccion, d.nombre_delegacion
                FROM punto p
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                WHERE (p.latitud IS NULL OR p.longitud IS NULL)
                AND p.intentos_api < 3
                LIMIT :limite""", "limite", limite);
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("limite", limite);
        if (puntos.isEmpty()) {
            r.put("terminado", true);
            r.put("resultados", List.of());
            return r;
        }
        if (googleKey == null || googleKey.isBlank()) {
            r.put("error", "No está configurada la clave de Google Maps (GOOGLE_MAPS_KEY).");
            r.put("resultados", List.of());
            return r;
        }
        List<Map<String, Object>> res = new ArrayList<>();
        for (var p : puntos) {
            Object id = p.get("id_punto");
            String dir = Conv.str(p.get("direccion")).replaceAll("[^a-zA-Z0-9\\s#\\-,ñÑáéíóúÁÉÍÓÚ]", " ");
            String del = Conv.str(p.get("nombre_delegacion")).trim();
            String busqueda = dir + (del.isEmpty() ? "" : ", " + del) + ", Colombia";
            String status = "Error desconocido";
            try {
                String url = "https://maps.googleapis.com/maps/api/geocode/json?address=" + URLEncoder.encode(busqueda, StandardCharsets.UTF_8) + "&key=" + googleKey;
                String body = http.send(HttpRequest.newBuilder(URI.create(url)).timeout(Duration.ofSeconds(20)).GET().build(), HttpResponse.BodyHandlers.ofString()).body();
                Map<?, ?> datos = JSON.readValue(body, Map.class);
                status = Conv.str(datos.get("status"));
                if ("OK".equals(status)) {
                    Map<?, ?> r0 = (Map<?, ?>) ((List<?>) datos.get("results")).get(0);
                    Map<?, ?> loc = (Map<?, ?>) ((Map<?, ?>) r0.get("geometry")).get("location");
                    db.update("UPDATE punto SET latitud = :latitud, longitud = :longitud, intentos_api = intentos_api + 1 WHERE id_punto = :id_punto",
                            "latitud", loc.get("lat"), "longitud", loc.get("lng"), "id_punto", id);
                    res.add(Map.of("ok", true, "id", id, "texto", Conv.str(r0.get("formatted_address"))));
                    continue;
                }
            } catch (Exception e) {
                log.warn("Geocoding: {}", e.getMessage());
            }
            try {
                db.update("UPDATE punto SET intentos_api = intentos_api + 1 WHERE id_punto = :id_punto", "id_punto", id);
            } catch (Exception ignored) {
            }
            res.add(Map.of("ok", false, "id", id, "texto", status.isEmpty() ? "Error desconocido" : status, "busqueda", busqueda));
        }
        r.put("resultados", res);
        return r;
    }

    // ================================================================ RASTREO DE TÉCNICOS

    @GetMapping(value = "/api/rastreoTecnico", params = "accion=index")
    public Map<String, Object> rastreoIndex() {
        return Map.of("titulo", "Rastreo de Técnicos", "hoy", F.hoy(),
                "tecnicos", lista("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC"));
    }

    @PostMapping(value = "/api/rastreoTecnico", params = "accion=ajaxObtenerRuta")
    public Map<String, Object> obtenerRuta(Req req) {
        String idTec = req.str("id_tecnico", "");
        String fecha = req.str("fecha", F.hoy());
        if (idTec.isEmpty()) return Map.of("success", false, "msj", "Seleccione un técnico o la opción 'Todos'.");
        String sql = """
                SELECT
                    os.id_ordenes_servicio,
                    os.id_tecnico,
                    os.fecha_visita,
                    os.actividades_realizadas AS observacion_servicio,
                    c.nombre_cliente,
                    p.nombre_punto,
                    os.hora_entrada,
                    os.hora_salida,
                    comp.latitud_inicio,
                    comp.longitud_inicio,
                    comp.latitud_fin,
                    comp.longitud_fin,
                    t.nombre_tecnico
                FROM ordenes_servicio os
                INNER JOIN ordenes_servicio_complemento comp ON os.id_ordenes_servicio = comp.id_orden_servicio
                LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                LEFT JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                WHERE os.fecha_visita = :fecha
                    AND (comp.latitud_inicio IS NOT NULL OR comp.latitud_fin IS NOT NULL)""";
        Map<String, Object> p = new LinkedHashMap<>();
        p.put("fecha", fecha);
        if (!idTec.equals("todos")) {
            sql += " AND os.id_tecnico = :id_tecnico";
            p.put("id_tecnico", idTec);
        }
        sql += " ORDER BY os.id_tecnico ASC, os.hora_entrada ASC, os.id_ordenes_servicio ASC";
        List<Map<String, Object>> ruta;
        try {
            ruta = db.list(sql, p);
        } catch (Exception e) {
            log.error("Error obteniendo ruta: {}", e.getMessage());
            ruta = List.of();
        }
        if (!ruta.isEmpty()) return Map.of("success", true, "data", ruta);
        return Map.of("success", false, "msj", "No hay coordenadas registradas en la fecha seleccionada.");
    }
}
