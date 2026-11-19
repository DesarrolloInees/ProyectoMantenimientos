package co.inees.mantenimientos.modulos.login;

import java.security.SecureRandom;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.regex.Pattern;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Correo;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Hash;
import co.inees.mantenimientos.core.Rbac;
import co.inees.mantenimientos.core.Req;
import co.inees.mantenimientos.core.Sesion;

/**
 * Port de:
 *  loginControlador, cambiarPasswordControlador, procesarCambioPasswordControlador,
 *  solicitarCodigoControlador, enviarCodigoControlador, resetPasswordControlador,
 *  y la lógica de logout / sesión de index.php.
 */
@RestController
@RequestMapping("/api")
public class LoginController {

    private static final Logger log = LoggerFactory.getLogger(LoginController.class);
    private static final Pattern REGEX_PWD =
            Pattern.compile("^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&.])[A-Za-z\\d@$!%*?&.]{8,}$");

    private final LoginModelo modelo;
    private final Correo correo;
    private final Rbac rbac;
    private final Db db;
    private final SecureRandom random = new SecureRandom();

    public LoginController(LoginModelo modelo, Correo correo, Rbac rbac, Db db) {
        this.modelo = modelo;
        this.correo = correo;
        this.rbac = rbac;
        this.db = db;
    }

    // ================================================================ SESIÓN

    /** Estado de la sesión para el frontend (menú y guard RBAC). */
    @GetMapping("/sesion")
    public Map<String, Object> sesion(Sesion s) {
        Map<String, Object> r = new LinkedHashMap<>();
        boolean logueado = s.logueado();
        r.put("logueado", logueado);
        r.put("temporal", s.has("temp_user_id"));
        r.put("usuario_id", logueado ? s.usuarioId() : null);
        r.put("usuario_name", logueado ? Conv.str(s.get("usuario_name"), "Usuario") : "");
        r.put("nivel_acceso", logueado ? s.nivel() : 0);
        r.put("usuario_cargo", logueado ? Conv.str(s.get("usuario_cargo"), "Colaborador") : "");
        r.put("permisos", logueado ? rbac.rutasPermitidas(s.nivel()) : java.util.List.of());
        return r;
    }

    /** B. LOGOUT: session_unset(); session_destroy(); */
    @PostMapping("/logout")
    public Map<String, Object> logout(Sesion s) {
        s.destruir();
        return Map.of("status", "success", "redirect", "login");
    }

    @GetMapping("/logout")
    public Map<String, Object> logoutGet(Sesion s) {
        return logout(s);
    }

    // ================================================================ LOGIN

    @PostMapping(value = "/login")
    public Map<String, Object> procesarLogin(Req req) {
        String usuario = req.str("usuario");
        String password = req.str("password");

        Map<String, Object> user = modelo.authenticateUser(usuario, password);
        Sesion s = req.sesion();
        if (user != null) {
            if (Conv.toInt(user.get("forzar_cambio_pwd")) == 1) {
                s.set("temp_user_id", user.get("usuario_id"));
                s.set("temp_username", user.get("nombre"));
                s.set("temp_nivel_acceso", user.get("nivel_acceso"));
                return Map.of("status", "success", "redirect", "cambiarPassword");
            }
            s.set("usuario_id", user.get("usuario_id"));
            s.set("usuario_name", user.get("nombre"));
            s.set("nivel_acceso", user.get("nivel_acceso"));
            s.set("usuario_cargo", user.get("cargo"));

            modelo.logAccess(user.get("usuario_id"), user.get("nombre"), req.ip());
            modelo.updateLastLoginTime(user.get("usuario_id"));
            return Map.of("status", "success", "redirect", "inicio");
        }
        return Map.of("status", "error", "message", "Usuario o contraseña incorrectos");
    }

    // ================================================================ CAMBIAR PASSWORD (forzado)

    @GetMapping(value = "/cambiarPassword")
    public Map<String, Object> cambiarPassword(Req req) {
        Sesion s = req.sesion();
        Map<String, Object> r = new LinkedHashMap<>();
        if (!s.has("temp_user_id")) {
            r.put("permitido", false);
            r.put("redirect", "login");
            return r;
        }
        r.put("permitido", true);
        r.put("usuario", s.get("temp_username"));
        r.put("error", req.get("error"));
        return r;
    }

    @PostMapping(value = "/procesarCambioPassword")
    public Map<String, Object> procesarCambioPassword(Req req) {
        Sesion s = req.sesion();
        if (!s.has("temp_user_id")) {
            return Map.of("status", "error", "redirect", "login");
        }
        String nuevaPass = req.str("nueva_password");
        String confirmPass = req.str("confirmar_password");
        Object usuarioId = s.get("temp_user_id");

        if (!nuevaPass.equals(confirmPass)) {
            return Map.of("status", "error", "error", "no_coinciden", "redirect", "cambiarPassword?error=no_coinciden");
        }
        if (!REGEX_PWD.matcher(nuevaPass).matches()) {
            return Map.of("status", "error", "error", "no_segura", "redirect", "cambiarPassword?error=no_segura");
        }
        boolean ok;
        try {
            ok = modelo.actualizarPassword(usuarioId, Hash.hash(nuevaPass));
        } catch (Exception e) {
            ok = false;
        }
        if (ok) {
            s.set("usuario_id", s.get("temp_user_id"));
            s.set("usuario_name", s.get("temp_username"));
            s.set("nivel_acceso", s.get("temp_nivel_acceso"));
            s.remove("temp_user_id");
            s.remove("temp_username");
            s.remove("temp_nivel_acceso");
            modelo.logAccess(s.get("usuario_id"), s.get("usuario_name"), req.ip());
            modelo.updateLastLoginTime(s.get("usuario_id"));
            return Map.of("status", "success", "redirect", "inicio");
        }
        return Map.of("status", "error", "error", "error_db", "redirect", "cambiarPassword?error=error_db");
    }

    // ================================================================ RECUPERACIÓN

    @PostMapping(value = {"/solicitarCodigo", "/enviarCodigo"}, params = "accion=enviarCodigo")
    public Map<String, Object> enviarCodigo(Req req) {
        String email = req.str("email");
        Map<String, Object> usuario = email.isEmpty() ? null : modelo.obtenerUsuarioPorEmail(email);
        if (usuario != null) {
            int codigo = 100000 + random.nextInt(900000);
            String hash = Hash.hash(String.valueOf(codigo));
            String expiracion = LocalDateTime.now().plusSeconds(900).format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
            modelo.guardarCodigoReset(email, hash, expiracion);
            try {
                correo.enviar(email, "Tu código de recuperación I-Nexis",
                        "Hola,<br><br>Tu código para recuperar tu contraseña es: <b>" + codigo + "</b><br>Expira en 15 min.");
            } catch (Exception e) {
                log.error("Error PHPMailer enviar código: {}", e.getMessage());
            }
        }
        return Map.of("status", "success", "redirect", "mensajeEnviado");
    }

    @PostMapping(value = "/resetPassword")
    public Map<String, Object> procesarResetPassword(Req req) {
        String email = req.str("email");
        String codigo = req.trim("codigo");
        String p1 = req.str("nueva_password");
        String p2 = req.str("confirmar_password");

        if (!p1.equals(p2)) return error("Las contraseñas no coinciden.", email);
        if (!REGEX_PWD.matcher(p1).matches()) {
            return error("La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.", email);
        }
        Map<String, Object> registro = db.one(
                "SELECT * FROM password_reset WHERE usuario_email = :email ORDER BY id DESC LIMIT 1", "email", email);
        if (registro == null) return error("No se encontró una solicitud de restablecimiento válida.", email);

        String ahora = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
        if (Conv.toInt(registro.get("usado")) == 1) return error("Este código ya ha sido utilizado.", email);
        if (Conv.str(registro.get("expira_en")).compareTo(ahora) <= 0) {
            return error("El código ha expirado. Por favor, solicita uno nuevo.", email);
        }
        if (!Hash.verificar(codigo, Conv.str(registro.get("codigo_hash")))) {
            return error("El código de verificación es incorrecto.", email);
        }
        Map<String, Object> usuario = modelo.obtenerUsuarioPorEmail(email);
        if (usuario == null) return error("Usuario no encontrado.", email);

        boolean act;
        try {
            act = modelo.actualizarPassword(usuario.get("usuario_id"), Hash.hash(p1));
        } catch (Exception e) {
            act = false;
        }
        if (act) {
            modelo.marcarCodigoComoUsado(registro.get("id"));
            return Map.of("status", "success", "success", "Tu contraseña ha sido actualizada correctamente.",
                    "redirect", "login");
        }
        return error("Error interno al actualizar la contraseña.", email);
    }

    private Map<String, Object> error(String mensaje, String email) {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("status", "error");
        r.put("error", mensaje);
        r.put("email", email);
        return r;
    }
}
