package co.inees.mantenimientos.modulos.admin;

import java.sql.SQLException;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.dao.DuplicateKeyException;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/** Port de tipoNovedad{Ver,Crear,Editar,Eliminar} y diasFestivos{Ver,Crear,Editar,Eliminar}. */
@RestController
@RequestMapping("/api")
public class TipoNovedadFestivosController {

    private static final Logger log = LoggerFactory.getLogger(TipoNovedadFestivosController.class);
    private final Db db;

    public TipoNovedadFestivosController(Db db) {
        this.db = db;
    }

    // ================================================================ tipoNovedad

    @GetMapping("/tipoNovedadVer")
    public Map<String, Object> novedadVer() {
        List<Map<String, Object>> lista;
        try {
            lista = db.list("""
                    SELECT id_tipo_novedad, nombre_novedad, estado
                    FROM tipo_novedad
                    WHERE estado = 1
                    ORDER BY id_tipo_novedad DESC""");
        } catch (Exception e) {
            lista = new ArrayList<>();
        }
        for (Map<String, Object> f : lista) f.put("nombre_novedad", Conv.titulo(Conv.str(f.get("nombre_novedad"))));
        return Map.of("novedades", lista);
    }

    @PostMapping("/tipoNovedadCrear")
    public Map<String, Object> novedadCrear(Req req) {
        String nombre = Conv.titulo(req.trim("nombre_novedad"));
        List<String> errores = new ArrayList<>();
        if (Conv.empty(nombre)) errores.add("El nombre de la novedad es obligatorio.");
        if (errores.isEmpty()) {
            try {
                db.update("INSERT INTO tipo_novedad (nombre_novedad, estado) VALUES (:nombre, 1)", "nombre", nombre);
                return Map.of("success", true, "redirect", "tipoNovedadVer");
            } catch (Exception e) {
                errores.add("Error: Es posible que este Tipo de Novedad ya exista.");
            }
        }
        return Map.of("success", false, "errores", errores, "nombrePrevio", nombre);
    }

    @GetMapping("/tipoNovedadEditar")
    public Map<String, Object> novedadObtener(Req req) {
        String id = req.str("id");
        if (id.isEmpty()) return Map.of("redirect", "tipoNovedadVer");
        Map<String, Object> d = null;
        try {
            d = db.one("SELECT * FROM tipo_novedad WHERE id_tipo_novedad = :id LIMIT 1", "id", id);
        } catch (Exception ignored) {
        }
        if (d == null) return Map.of("redirect", "tipoNovedadVer");
        return Map.of("datos", d);
    }

    @PostMapping("/tipoNovedadEditar")
    public Map<String, Object> novedadEditar(Req req) {
        String id = req.str("id_tipo_novedad", req.str("id"));
        String nombre = Conv.titulo(req.trim("nombre_novedad"));
        String estado = req.str("estado", "1");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(nombre)) errores.add("El nombre de la novedad es obligatorio.");
        if (errores.isEmpty()) {
            try {
                db.update("""
                        UPDATE tipo_novedad
                        SET nombre_novedad = :nombre, estado = :estado
                        WHERE id_tipo_novedad = :id""", "nombre", nombre, "estado", estado, "id", id);
                return Map.of("success", true, "redirect", "tipoNovedadVer");
            } catch (Exception e) {
                errores.add("Error: Nombre duplicado o fallo en base de datos.");
            }
        }
        Map<String, Object> datos = new LinkedHashMap<>();
        datos.put("id_tipo_novedad", id);
        datos.put("nombre_novedad", nombre);
        datos.put("estado", estado);
        return Map.of("success", false, "errores", errores, "datos", datos);
    }

    @PostMapping("/tipoNovedadEliminar")
    public Map<String, Object> novedadEliminar(Req req) {
        String id = req.str("id");
        if (!id.isEmpty()) {
            try {
                db.update("UPDATE tipo_novedad SET estado = 0 WHERE id_tipo_novedad = :id", "id", id);
            } catch (Exception e) {
                log.error("Error al eliminar tipo novedad: {}", e.getMessage());
            }
        }
        return Map.of("success", true, "redirect", "tipoNovedadVer");
    }

    // ================================================================ diasFestivos

    @GetMapping("/diasFestivosVer")
    public Map<String, Object> festivosVer() {
        try {
            return Map.of("festivos", db.list("SELECT * FROM dias_festivos ORDER BY fecha DESC"));
        } catch (Exception e) {
            return Map.of("festivos", List.of());
        }
    }

    private static boolean esDuplicado(Exception e) {
        if (e instanceof DuplicateKeyException) return true;
        Throwable t = e;
        while (t != null) {
            if (t instanceof SQLException s && s.getErrorCode() == 1062) return true;
            t = t.getCause();
        }
        return false;
    }

    @PostMapping("/diasFestivosCrear")
    public Map<String, Object> festivoCrear(Req req) {
        String fecha = req.str("fecha");
        String descripcion = req.trim("descripcion");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(fecha)) errores.add("La fecha es obligatoria.");
        if (errores.isEmpty()) {
            try {
                db.update("INSERT INTO dias_festivos (fecha, descripcion) VALUES (:fecha, :desc)", "fecha", fecha, "desc", descripcion);
                return Map.of("success", true, "redirect", "diasFestivosVer");
            } catch (Exception e) {
                if (esDuplicado(e)) errores.add("Ya existe un festivo registrado con esa fecha.");
                else errores.add("Error al guardar en base de datos.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    @GetMapping("/diasFestivosEditar")
    public Map<String, Object> festivoObtener(Req req) {
        Map<String, Object> d = db.one("SELECT * FROM dias_festivos WHERE id_festivo = :id", "id", req.str("id"));
        if (d == null) return Map.of("redirect", "diasFestivosVer");
        return Map.of("datos", d);
    }

    @PostMapping("/diasFestivosEditar")
    public Map<String, Object> festivoEditar(Req req) {
        String id = req.str("id_festivo");
        String fecha = req.str("fecha");
        String descripcion = req.trim("descripcion");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(fecha)) errores.add("La fecha es obligatoria.");
        if (errores.isEmpty()) {
            try {
                db.update("UPDATE dias_festivos SET fecha = :fecha, descripcion = :desc WHERE id_festivo = :id",
                        "fecha", fecha, "desc", descripcion, "id", id);
                return Map.of("success", true, "redirect", "diasFestivosVer");
            } catch (Exception e) {
                if (esDuplicado(e)) errores.add("Ya existe otro festivo con esa fecha.");
                else errores.add("Error al actualizar.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    @PostMapping("/diasFestivosEliminar")
    public Map<String, Object> festivoEliminar(Req req) {
        if (!req.str("id").isEmpty()) {
            try {
                db.update("DELETE FROM dias_festivos WHERE id_festivo = :id", "id", req.str("id"));
            } catch (Exception e) {
                log.error("Error al eliminar festivo: {}", e.getMessage());
            }
        }
        return Map.of("success", true, "redirect", "diasFestivosVer");
    }
}
