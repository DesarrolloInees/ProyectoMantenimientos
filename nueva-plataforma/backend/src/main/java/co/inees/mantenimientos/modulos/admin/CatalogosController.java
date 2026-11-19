package co.inees.mantenimientos.modulos.admin;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/**
 * Catálogos simples de administración (Ver / Crear / Editar / Eliminar), con el mismo SQL
 * que los modelos PHP de app/models/admin:
 *  calificacionServicio, delegacion, estadoMaquina, modalidadOperativa, tecnico,
 *  tipoMantenimiento, tipoMaquina, tipoUsuario.
 */
@RestController
public class CatalogosController {

    private static final Logger log = LoggerFactory.getLogger(CatalogosController.class);

    /** Definición de un catálogo. */
    record Cat(String clave, String id, String campo, boolean estadoEnEditar,
               String sqlListar, String sqlCrear, String sqlObtener, String sqlEditar, String sqlEliminar,
               String errObligatorioCrear, String errCrear, String errActualizar) {}

    private static final Map<String, Cat> CATS = new LinkedHashMap<>();

    static {
        reg(new Cat("calificacionServicio", "id_calificacion", "nombre_calificacion", false,
                "SELECT * FROM calificacion_servicio ORDER BY id_calificacion ASC",
                "INSERT INTO calificacion_servicio (nombre_calificacion) VALUES (:nombre)",
                "SELECT * FROM calificacion_servicio WHERE id_calificacion = :id",
                "UPDATE calificacion_servicio SET nombre_calificacion = :nombre WHERE id_calificacion = :id",
                "DELETE FROM calificacion_servicio WHERE id_calificacion = :id",
                "El nombre de la calificación es obligatorio.", "Error al guardar en base de datos.", "Error al actualizar."));
        reg(new Cat("delegacion", "id_delegacion", "nombre_delegacion", true,
                "SELECT * FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC",
                "INSERT INTO delegacion (nombre_delegacion, estado) VALUES (:nombre, 1)",
                "SELECT * FROM delegacion WHERE id_delegacion = :id",
                "UPDATE delegacion SET nombre_delegacion = :nombre, estado = :estado WHERE id_delegacion = :id",
                "UPDATE delegacion SET estado = 0 WHERE id_delegacion = :id",
                "El nombre de la delegación es obligatorio.", "Error: Es posible que esta Delegación ya exista.",
                "Error al actualizar (nombre duplicado?)."));
        reg(new Cat("estadoMaquina", "id_estado", "nombre_estado", false,
                "SELECT * FROM estado_maquina ORDER BY id_estado ASC",
                "INSERT INTO estado_maquina (nombre_estado) VALUES (:nombre)",
                "SELECT * FROM estado_maquina WHERE id_estado = :id",
                "UPDATE estado_maquina SET nombre_estado = :nombre WHERE id_estado = :id",
                "DELETE FROM estado_maquina WHERE id_estado = :id",
                "El nombre del estado es obligatorio.", "Error al guardar en base de datos.", "Error al actualizar."));
        reg(new Cat("modalidadOperativa", "id_modalidad", "nombre_modalidad", false,
                "SELECT * FROM modalidad_operativa ORDER BY id_modalidad ASC",
                "INSERT INTO modalidad_operativa (nombre_modalidad) VALUES (:nombre)",
                "SELECT * FROM modalidad_operativa WHERE id_modalidad = :id",
                "UPDATE modalidad_operativa SET nombre_modalidad = :nombre WHERE id_modalidad = :id",
                "DELETE FROM modalidad_operativa WHERE id_modalidad = :id",
                "El nombre de la modalidad es obligatorio.", "Error al guardar en base de datos.", "Error al actualizar."));
        reg(new Cat("tecnico", "id_tecnico", "nombre_tecnico", true,
                "SELECT * FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC",
                "INSERT INTO tecnico (nombre_tecnico, estado) VALUES (:nombre, 1)",
                "SELECT * FROM tecnico WHERE id_tecnico = :id",
                "UPDATE tecnico SET nombre_tecnico = :nombre, estado = :estado WHERE id_tecnico = :id",
                "UPDATE tecnico SET estado = 0 WHERE id_tecnico = :id",
                "El nombre del técnico es obligatorio.", "Error al guardar en base de datos.", "Error al actualizar."));
        reg(new Cat("tipoMantenimiento", "id_tipo_mantenimiento", "nombre_completo", true,
                "SELECT * FROM tipo_mantenimiento WHERE estado = 1 ORDER BY nombre_completo ASC",
                "INSERT INTO tipo_mantenimiento (nombre_completo, estado) VALUES (:nombre, 1)",
                "SELECT * FROM tipo_mantenimiento WHERE id_tipo_mantenimiento = :id",
                "UPDATE tipo_mantenimiento SET nombre_completo = :nombre, estado = :estado WHERE id_tipo_mantenimiento = :id",
                "UPDATE tipo_mantenimiento SET estado = 0 WHERE id_tipo_mantenimiento = :id",
                "El nombre del mantenimiento es obligatorio.", "Error al guardar en la base de datos.", "Error al actualizar."));
        reg(new Cat("tipoMaquina", "id_tipo_maquina", "nombre_tipo_maquina", true,
                "SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC",
                "INSERT INTO tipo_maquina (nombre_tipo_maquina, estado) VALUES (:nombre, 1)",
                "SELECT * FROM tipo_maquina WHERE id_tipo_maquina = :id",
                "UPDATE tipo_maquina SET nombre_tipo_maquina = :nombre, estado = :estado WHERE id_tipo_maquina = :id",
                "UPDATE tipo_maquina SET estado = 0 WHERE id_tipo_maquina = :id",
                "El nombre es obligatorio.", "Error: Es posible que este Tipo de Máquina ya exista.",
                "Error al actualizar (Posible nombre duplicado)."));
        reg(new Cat("tipoUsuario", "idTipoUsuario", "nombreTipoUsuario", false,
                "SELECT * FROM tipousuario ORDER BY idTipoUsuario ASC",
                "INSERT INTO tipousuario (nombreTipoUsuario) VALUES (:nombre)",
                "SELECT * FROM tipousuario WHERE idTipoUsuario = :id",
                "UPDATE tipousuario SET nombreTipoUsuario = :nombre WHERE idTipoUsuario = :id",
                "DELETE FROM tipousuario WHERE idTipoUsuario = :id",
                "El nombre del rol es obligatorio.", "Error al guardar (posible duplicado).", "Error al actualizar."));
    }

    private static void reg(Cat c) {
        CATS.put(c.clave(), c);
    }

    private final Db db;

    public CatalogosController(Db db) {
        this.db = db;
    }

    private static Cat cat(Req req, String sufijo) {
        String uri = req.raw().getRequestURI();
        String pagina = uri.substring(uri.lastIndexOf('/') + 1);
        String clave = pagina.substring(0, pagina.length() - sufijo.length());
        for (Map.Entry<String, Cat> e : CATS.entrySet()) {
            if (e.getKey().equalsIgnoreCase(clave)) return e.getValue();
        }
        throw new IllegalArgumentException("Catálogo desconocido: " + clave);
    }

    // ---------------------------------------------------------------- Ver

    @GetMapping({"/api/calificacionServicioVer", "/api/delegacionVer", "/api/estadoMaquinaVer", "/api/modalidadOperativaVer",
            "/api/modalidadOPerativaVer", "/api/tecnicoVer", "/api/tipoMantenimientoVer", "/api/tipoMaquinaVer", "/api/tipoUsuarioVer"})
    public Map<String, Object> ver(Req req) {
        Cat c = cat(req, "Ver");
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("lista", db.list(c.sqlListar()));
        return r;
    }

    // ---------------------------------------------------------------- Crear

    @PostMapping({"/api/calificacionServicioCrear", "/api/delegacionCrear", "/api/estadoMaquinaCrear", "/api/modalidadOperativaCrear",
            "/api/tecnicoCrear", "/api/tipoMantenimientoCrear", "/api/tipoMaquinaCrear", "/api/tipoUsuarioCrear"})
    public Map<String, Object> crear(Req req) {
        Cat c = cat(req, "Crear");
        String nombre = req.trim(c.campo());
        List<String> errores = new ArrayList<>();
        if (Conv.empty(nombre)) errores.add(c.errObligatorioCrear());
        if (errores.isEmpty()) {
            try {
                db.update(c.sqlCrear(), "nombre", nombre);
                return Map.of("success", true, "redirect", c.clave() + "Ver");
            } catch (Exception e) {
                log.error("Error crear {}: {}", c.clave(), e.getMessage());
                errores.add(c.errCrear());
            }
        }
        return Map.of("success", false, "errores", errores, "nombrePrevio", nombre);
    }

    // ---------------------------------------------------------------- Editar

    @GetMapping({"/api/calificacionServicioEditar", "/api/delegacionEditar", "/api/estadoMaquinaEditar", "/api/modalidadOperativaEditar",
            "/api/tecnicoEditar", "/api/tipoMantenimientoEditar", "/api/tipoMaquinaEditar", "/api/tipoUsuarioEditar"})
    public Map<String, Object> obtener(Req req) {
        Cat c = cat(req, "Editar");
        String id = req.str("id");
        if (id.isEmpty()) return Map.of("redirect", c.clave() + "Ver");
        Map<String, Object> datos = db.one(c.sqlObtener(), "id", Conv.toInt(id));
        if (datos == null) return Map.of("redirect", c.clave() + "Ver");
        return Map.of("datos", datos);
    }

    @PostMapping({"/api/calificacionServicioEditar", "/api/delegacionEditar", "/api/estadoMaquinaEditar", "/api/modalidadOperativaEditar",
            "/api/tecnicoEditar", "/api/tipoMantenimientoEditar", "/api/tipoMaquinaEditar", "/api/tipoUsuarioEditar"})
    public Map<String, Object> editar(Req req) {
        Cat c = cat(req, "Editar");
        String id = req.str("id", req.str(c.id()));
        if (id.isEmpty()) return Map.of("success", false, "redirect", c.clave() + "Ver");
        String nombre = req.trim(c.campo());
        String estado = req.str("estado", "1");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(nombre)) errores.add("El nombre es obligatorio.");
        if (errores.isEmpty()) {
            try {
                Map<String, Object> p = Db.p("nombre", nombre, "id", Conv.toInt(id));
                if (c.estadoEnEditar()) p.put("estado", estado);
                db.update(c.sqlEditar(), p);
                return Map.of("success", true, "redirect", c.clave() + "Ver");
            } catch (Exception e) {
                log.error("Error editar {}: {}", c.clave(), e.getMessage());
                errores.add(c.errActualizar());
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    // ---------------------------------------------------------------- Eliminar

    @PostMapping({"/api/calificacionServicioEliminar", "/api/delegacionEliminar", "/api/estadoMaquinaEliminar", "/api/modalidadOperativaEliminar",
            "/api/tecnicoEliminar", "/api/tipoMantenimientoEliminar", "/api/tipoMaquinaEliminar", "/api/tipoUsuarioEliminar"})
    public Map<String, Object> eliminar(Req req) {
        Cat c = cat(req, "Eliminar");
        String id = req.str("id");
        boolean ok = false;
        if (!id.isEmpty() && id.matches("^-?\\d+(\\.\\d+)?$")) {
            try {
                ok = db.update(c.sqlEliminar(), "id", Conv.toInt(id)) >= 0;
            } catch (Exception e) {
                log.error("Error eliminar {}: {}", c.clave(), e.getMessage());
            }
        }
        return Map.of("success", ok, "redirect", c.clave() + "Ver");
    }
}
