package co.inees.mantenimientos.core;

import java.util.LinkedHashMap;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.RestControllerAdvice;
import org.springframework.web.servlet.resource.NoResourceFoundException;

/**
 * Las excepciones se devuelven como JSON {success:false, status:'error', message}
 * igual que los bloques try/catch de los controladores PHP.
 */
@RestControllerAdvice
public class ApiErrores {

    private static final Logger log = LoggerFactory.getLogger(ApiErrores.class);

    @ExceptionHandler(NoResourceFoundException.class)
    public ResponseEntity<Map<String, Object>> noEncontrado(NoResourceFoundException e) {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("success", false);
        r.put("status", "error");
        r.put("message", "Error 404: Página no encontrada o acceso denegado.");
        return ResponseEntity.status(HttpStatus.NOT_FOUND).body(r);
    }

    @ExceptionHandler(Exception.class)
    public ResponseEntity<Map<String, Object>> error(Exception e) {
        log.error("Error en la petición", e);
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("success", false);
        r.put("status", "error");
        r.put("message", "Error: " + (e.getMessage() == null ? e.getClass().getSimpleName() : e.getMessage()));
        return ResponseEntity.status(HttpStatus.OK).body(r);
    }
}
