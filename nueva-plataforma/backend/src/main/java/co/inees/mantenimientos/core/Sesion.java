package co.inees.mantenimientos.core;

import jakarta.servlet.http.HttpSession;

/**
 * Equivalente a $_SESSION. Usa exactamente las mismas claves que la versión PHP:
 * usuario_id, usuario_name, nivel_acceso, usuario_cargo, temp_user_id, temp_username,
 * temp_nivel_acceso, etc.
 */
public class Sesion {

    private final HttpSession s;

    public Sesion(HttpSession s) {
        this.s = s;
    }

    public HttpSession raw() {
        return s;
    }

    public Object get(String k) {
        return s.getAttribute(k);
    }

    public void set(String k, Object v) {
        s.setAttribute(k, v);
    }

    public void remove(String k) {
        s.removeAttribute(k);
    }

    public boolean has(String k) {
        return s.getAttribute(k) != null;
    }

    public boolean logueado() {
        return has("usuario_id");
    }

    public int usuarioId() {
        return Conv.toInt(get("usuario_id"));
    }

    public int nivel() {
        return Conv.toInt(get("nivel_acceso"));
    }

    public String nombre() {
        return Conv.str(get("usuario_name"));
    }

    public String cargo() {
        return Conv.str(get("usuario_cargo"));
    }

    /** session_unset(); session_destroy(); */
    public void destruir() {
        try {
            s.invalidate();
        } catch (IllegalStateException ignored) {
        }
    }
}
