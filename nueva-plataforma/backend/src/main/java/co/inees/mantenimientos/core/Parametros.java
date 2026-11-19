package co.inees.mantenimientos.core;

import org.springframework.stereotype.Component;

/** Lectura de la tabla `parametros` (obtenerParametro de loginModelo.php). */
@Component
public class Parametros {

    private final Db db;

    public Parametros(Db db) {
        this.db = db;
    }

    public String obtener(String clave, String porDefecto) {
        try {
            Object v = db.scalar("SELECT valor FROM parametros WHERE clave = :clave LIMIT 1", "clave", clave);
            return v == null ? porDefecto : String.valueOf(v);
        } catch (Exception e) {
            return porDefecto;
        }
    }

    public String obtener(String clave) {
        return obtener(clave, null);
    }
}
