package co.inees.mantenimientos.modulos.usuario;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Hash;
import co.inees.mantenimientos.core.Req;

/** Port de usuario{Ver,Crear,Editar,Eliminar}Controlador + modelos. */
@RestController
@RequestMapping("/api")
public class UsuarioController {

    private static final Logger log = LoggerFactory.getLogger(UsuarioController.class);
    private final Db db;

    public UsuarioController(Db db) {
        this.db = db;
    }

    private List<Map<String, Object>> tiposUsuario() {
        try {
            return db.list("SELECT idTipoUsuario, nombreTipoUsuario FROM tipousuario ORDER BY nombreTipoUsuario ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    @GetMapping("/usuarioVer")
    public Map<String, Object> ver() {
        try {
            return Map.of("usuarios", db.list("""
                    SELECT
                        u.usuario_id,
                        u.nombre,
                        u.cedula,
                        u.cargo,
                        u.email,
                        u.celular,
                        u.usuario,
                        tu.nombreTipoUsuario AS rol
                    FROM
                        usuarios u
                    INNER JOIN
                        tipousuario tu ON u.nivel_acceso = tu.idTipoUsuario
                    WHERE
                        u.estado = 'activo'
                    ORDER BY
                        u.nombre ASC"""));
        } catch (Exception e) {
            log.error("Error al obtener los usuarios: {}", e.getMessage());
            return Map.of("usuarios", List.of());
        }
    }

    @GetMapping("/usuarioCrear")
    public Map<String, Object> crearForm() {
        List<Map<String, Object>> tipos = tiposUsuario();
        List<String> errores = new ArrayList<>();
        if (tipos.isEmpty()) errores.add("¡ALERTA! No hay roles creados en la tabla 'tipousuario'.");
        return Map.of("tiposUsuario", tipos, "errores", errores);
    }

    @PostMapping("/usuarioCrear")
    public Map<String, Object> crear(Req req) {
        String usuario = req.trim("usuario");
        String pass = req.str("pass");
        String nivel = req.str("nivel_acceso");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(usuario) || Conv.empty(pass)) errores.add("Usuario y contraseña son obligatorios.");
        if (Conv.empty(nivel)) errores.add("Debes seleccionar un Rol.");
        if (errores.isEmpty()) {
            try {
                db.update("""
                        INSERT INTO usuarios (
                            nombre, cedula, cargo, email, celular, usuario, password_hash,
                            nivel_acceso, forzar_cambio_pwd, pwd_ultimo_cambio, estado
                        ) VALUES (
                            :nombre, :cedula, :cargo, :email, :celular, :usuario, :pass,
                            :nivel_acceso, 1, NOW(), 'activo'
                        )""",
                        "nombre", req.trim("nombre"), "cedula", req.trim("cedula"), "cargo", req.trim("cargo"),
                        "email", req.trim("email"), "celular", req.trim("celular"), "usuario", usuario,
                        "pass", Hash.hash(pass), "nivel_acceso", Conv.toInt(nivel));
                return Map.of("success", true, "redirect", "usuarioVer");
            } catch (Exception e) {
                log.error("Error Base de Datos (crearUsuario): {}", e.getMessage());
                errores.add("Error al guardar en BD. Verifica que la Cédula o Usuario no estén repetidos.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    @GetMapping("/usuarioEditar")
    public Map<String, Object> editarForm(Req req) {
        String id = req.str("id");
        if (id.isEmpty()) return Map.of("redirect", "usuarioVer");
        Map<String, Object> u = null;
        try {
            u = db.one("SELECT * FROM usuarios WHERE usuario_id = :id_usuario", "id_usuario", Conv.toInt(id));
        } catch (Exception ignored) {
        }
        if (u != null) u.remove("password_hash");
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("usuario", u);
        r.put("tiposUsuario", tiposUsuario());
        r.put("errores", u == null ? List.of("El usuario solicitado no existe.") : List.of());
        return r;
    }

    @PostMapping("/usuarioEditar")
    public Map<String, Object> editar(Req req) {
        String id = req.str("usuario_id");
        String pass = req.str("pass");
        try {
            String sql = """
                    UPDATE usuarios SET
                        nombre = :nombre,
                        cedula = :cedula,
                        cargo = :cargo,
                        email = :email,
                        celular = :celular,
                        usuario = :usuario,
                        nivel_acceso = :nivel_acceso,
                        estado = :estado""";
            Map<String, Object> p = Db.p("nombre", req.trim("nombre"), "cedula", req.trim("cedula"), "cargo", req.trim("cargo"),
                    "email", req.trim("email"), "celular", req.trim("celular"), "usuario", req.trim("usuario"),
                    "nivel_acceso", req.str("nivel_acceso"), "estado", req.str("estado", "activo"), "id_usuario", id);
            if (!Conv.empty(pass)) {
                sql += ", password_hash = :pass";
                p.put("pass", Hash.hash(pass));
            }
            sql += " WHERE usuario_id = :id_usuario";
            db.update(sql, p);
            return Map.of("success", true, "redirect", "usuarioVer");
        } catch (Exception e) {
            log.error("Error editarUsuario: {}", e.getMessage());
            return Map.of("success", false, "errores", List.of("Error al actualizar. Posible duplicado de Cédula o Usuario."));
        }
    }

    @PostMapping("/usuarioEliminar")
    public Map<String, Object> eliminar(Req req) {
        String id = req.str("id");
        if (!id.isEmpty() && id.matches("^\\d+$")) {
            try {
                db.update("UPDATE usuarios SET estado = 'inactivo' WHERE usuario_id = :id", "id", Conv.toInt(id));
            } catch (Exception e) {
                log.error("Error al eliminar el usuario lógicamente: {}", e.getMessage());
            }
        }
        return Map.of("success", true, "redirect", "usuarioVer");
    }
}
