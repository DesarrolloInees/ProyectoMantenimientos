package co.inees.mantenimientos.core;

import java.util.List;
import java.util.Set;

import org.springframework.stereotype.Component;

/**
 * "EL PORTERO" + "CEREBRO RBAC" de index.php.
 * Consulta las tablas rutas y rol_permisos exactamente igual que la versión PHP.
 */
@Component
public class Rbac {

    /** Páginas que puede ver TODO EL MUNDO (sin loguearse). */
    public static final Set<String> PAGINAS_PUBLICAS = Set.of(
            "login", "solicitarCodigo", "resetPassword", "procesarResetPassword", "enviarCodigo",
            "cambiarPassword", "procesarCambioPassword", "mensajeEnviado", "error404");

    private final Db db;

    public Rbac(Db db) {
        this.db = db;
    }

    public static boolean esPublica(String pagina) {
        return PAGINAS_PUBLICAS.contains(pagina);
    }

    /**
     * Devuelve el controlador_ruta registrado (o null si no tiene acceso / no existe).
     */
    /**
     * Scripts sueltos de helps/*.php (carga inicial de datos). En PHP se abrían por URL directa sin
     * sesión; aquí solo los ve el administrador (nivel 1) y no requieren fila en `rutas`.
     */
    public static final String HERRAMIENTAS_CARGA = "herramientasCarga";

    public String controladorPara(String pagina, Sesion sesion) {
        if (HERRAMIENTAS_CARGA.equals(pagina)) {
            return sesion.logueado() && sesion.nivel() == 1 ? "helps/" : null;
        }
        if (sesion.logueado()) {
            int nivel = sesion.nivel();
            if (nivel == 1) {
                return (String) db.scalar(
                        "SELECT controlador_ruta FROM rutas WHERE nombre_ruta = :pagina AND estado = 'activo'",
                        "pagina", pagina);
            }
            return (String) db.scalar("""
                    SELECT r.controlador_ruta
                    FROM rol_permisos rp
                    INNER JOIN rutas r ON rp.ruta_id = r.id_ruta
                    WHERE rp.rol_id = :rol_id AND r.nombre_ruta = :pagina AND r.estado = 'activo'""",
                    "rol_id", nivel, "pagina", pagina);
        } else if (esPublica(pagina)) {
            return (String) db.scalar(
                    "SELECT controlador_ruta FROM rutas WHERE nombre_ruta = :pagina AND estado = 'activo'",
                    "pagina", pagina);
        }
        return null;
    }

    public boolean tieneAcceso(String pagina, Sesion sesion) {
        return controladorPara(pagina, sesion) != null;
    }

    /** Lista de nombres de ruta permitidos para el rol (para el menú/guard de Angular). */
    public List<Object> rutasPermitidas(int nivel) {
        if (nivel == 1) {
            List<Object> r = new java.util.ArrayList<>(db.column("SELECT nombre_ruta FROM rutas WHERE estado = 'activo'"));
            r.add(HERRAMIENTAS_CARGA);
            return r;
        }
        return db.column("""
                SELECT r.nombre_ruta FROM rol_permisos rp
                INNER JOIN rutas r ON rp.ruta_id = r.id_ruta
                WHERE rp.rol_id = :rol AND r.estado = 'activo'""", "rol", nivel);
    }
}
