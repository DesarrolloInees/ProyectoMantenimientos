package co.inees.mantenimientos.modulos.login;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Service;

import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Hash;

/** Port de app/models/login/loginModelo.php */
@Service
public class LoginModelo {

    private static final Logger log = LoggerFactory.getLogger(LoginModelo.class);
    private final Db db;

    public LoginModelo(Db db) {
        this.db = db;
    }

    public Map<String, Object> authenticateUser(String usuario, String password) {
        try {
            Map<String, Object> user = db.one("""
                    SELECT u.*, ut.idTipoUsuario
                    FROM usuarios u
                    INNER JOIN tipousuario ut ON u.nivel_acceso = ut.idTipoUsuario
                    WHERE u.usuario = :usuario AND u.estado = 'activo'
                    LIMIT 1""", "usuario", usuario);
            if (user != null && user.get("password_hash") != null
                    && Hash.verificar(password, String.valueOf(user.get("password_hash")))) {
                return user;
            }
            return null;
        } catch (Exception e) {
            log.error("Error de autenticación: {}", e.getMessage());
            return null;
        }
    }

    public boolean logAccess(Object usuarioId, Object nombreUsuario, String ip) {
        try {
            return db.update("INSERT INTO login (usuario_id, fecha, hora, usuario, ip) VALUES (:usuario_id, CURDATE(), CURTIME(), :usuario, :ip)",
                    "usuario_id", usuarioId, "usuario", nombreUsuario, "ip", ip) > 0;
        } catch (Exception e) {
            log.error("Error al registrar el acceso: {}", e.getMessage());
            return false;
        }
    }

    public boolean updateLastLoginTime(Object usuarioId) {
        try {
            db.update("UPDATE usuarios SET ultimo_acceso = NOW() WHERE usuario_id = :usuario_id", "usuario_id", usuarioId);
            return true;
        } catch (Exception e) {
            log.error("Error al actualizar el último acceso: {}", e.getMessage());
            return false;
        }
    }

    public String getLastPreviousAccess(Object usuarioId) {
        try {
            var acceso = db.list("SELECT fecha, hora FROM login WHERE usuario_id = :usuario_id ORDER BY fecha DESC, hora DESC LIMIT 2",
                    "usuario_id", usuarioId);
            if (acceso.size() >= 2) {
                return "Último acceso: " + acceso.get(1).get("fecha") + " a las " + acceso.get(1).get("hora");
            }
        } catch (Exception e) {
            log.error("Error al obtener el último acceso: {}", e.getMessage());
        }
        return "Sin registro de acceso previo.";
    }

    public boolean actualizarPassword(Object usuarioId, String nuevoHash) {
        db.update("""
                UPDATE usuarios SET
                    password_hash = :hash,
                    forzar_cambio_pwd = 0,
                    pwd_ultimo_cambio = NOW()
                WHERE usuario_id = :id""", "hash", nuevoHash, "id", usuarioId);
        return true;
    }

    public boolean marcarParaCambioPassword(Object usuarioId) {
        try {
            db.update("UPDATE usuarios SET forzar_cambio_pwd = TRUE WHERE usuario_id = :id", "id", usuarioId);
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    public Map<String, Object> obtenerUsuarioPorEmail(String email) {
        return db.one("SELECT * FROM usuarios WHERE email = :email AND estado = 'activo' LIMIT 1", "email", email);
    }

    public boolean guardarCodigoReset(String email, String codigoHash, String expiracion) {
        db.update("UPDATE password_reset SET usado = 1 WHERE usuario_email = :email", "email", email);
        return db.update("INSERT INTO password_reset (usuario_email, codigo_hash, expira_en, usado) VALUES (:email, :hash, :expira, 0)",
                "email", email, "hash", codigoHash, "expira", expiracion) > 0;
    }

    public Object verificarCodigoReset(String email, String codigoEnviado) {
        String ahora = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
        Map<String, Object> registro = db.one("""
                SELECT id, codigo_hash FROM password_reset
                WHERE usuario_email = :email
                AND usado = 0
                AND expira_en > :ahora
                ORDER BY id DESC LIMIT 1""", "email", email, "ahora", ahora);
        if (registro != null && Hash.verificar(codigoEnviado, String.valueOf(registro.get("codigo_hash")))) {
            return registro.get("id");
        }
        return null;
    }

    public boolean marcarCodigoComoUsado(Object idCodigo) {
        return db.update("UPDATE password_reset SET usado = 1 WHERE id = :id", "id", idCodigo) > 0;
    }
}
