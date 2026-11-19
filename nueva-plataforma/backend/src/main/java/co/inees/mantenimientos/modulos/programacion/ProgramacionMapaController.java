package co.inees.mantenimientos.modulos.programacion;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/** Port de programacionMapaControlador.php + programacionMapaModelo.php */
@RestController
@RequestMapping("/api/programacionMapa")
public class ProgramacionMapaController {

    private final Db db;

    public ProgramacionMapaController(Db db) {
        this.db = db;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> index(Req req) {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Beta: Programación por Mapa");
        r.put("listaDelegaciones", db.list("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC"));
        String del = req.str("delegacion", "");
        List<Object> zonas = new ArrayList<>();
        if (!del.isEmpty()) {
            db.list("SELECT DISTINCT zona FROM punto WHERE id_delegacion = :id_delegacion AND zona IS NOT NULL AND zona != '' ORDER BY zona ASC", "id_delegacion", del)
                    .forEach(z -> zonas.add(z.get("zona")));
        }
        r.put("delegacionSeleccionada", del);
        r.put("listaZonas", zonas);
        return r;
    }

    @PostMapping(params = "accion=ajax_obtener_puntos")
    public Map<String, Object> ajaxObtenerPuntos(Req req) {
        String del = req.str("delegacion", "");
        Object crudo = req.get("zonas");
        List<String> zonas = new ArrayList<>();
        if (crudo instanceof List<?> || crudo instanceof Map<?, ?>) Req.asList(crudo).forEach(z -> zonas.add(Conv.str(z)));
        else if (crudo != null) for (String z : Conv.str(crudo).split(",")) zonas.add(z.trim());
        zonas.removeIf(String::isEmpty);
        if (del.isEmpty() || zonas.isEmpty()) return Map.of("success", false, "msj", "Faltan parámetros");
        List<Map<String, Object>> puntos = db.list("""
                SELECT p.id_punto, p.nombre_punto, p.direccion, p.zona,
                        p.latitud, p.longitud, c.nombre_cliente
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                WHERE p.estado = 1
                    AND p.id_delegacion = :delegacion
                    AND p.zona IN (:zonas)
                    AND NOT EXISTS (
                        SELECT 1 FROM ordenes_servicio os
                        WHERE os.id_punto = p.id_punto AND os.estado = 2
                    )""", "delegacion", del, "zonas", zonas);
        return Map.of("success", true, "data", puntos);
    }
}
