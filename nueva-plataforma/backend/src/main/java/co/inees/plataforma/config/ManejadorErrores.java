package co.inees.plataforma.config;

import java.util.Map;
import java.util.stream.Collectors;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.AccessDeniedException;
import org.springframework.web.bind.MethodArgumentNotValidException;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.RestControllerAdvice;
import org.springframework.web.multipart.MaxUploadSizeExceededException;

/** Todas las respuestas de error tienen la forma {"mensaje": "..."}. */
@RestControllerAdvice
public class ManejadorErrores {

	private static final Logger log = LoggerFactory.getLogger(ManejadorErrores.class);

	@ExceptionHandler(NegocioException.class)
	ResponseEntity<Map<String, String>> negocio(NegocioException e) {
		return ResponseEntity.status(e.getEstado()).body(Map.of("mensaje", e.getMessage()));
	}

	@ExceptionHandler(MethodArgumentNotValidException.class)
	ResponseEntity<Map<String, String>> validacion(MethodArgumentNotValidException e) {
		String detalle = e.getBindingResult().getFieldErrors().stream()
			.map(f -> f.getField() + ": " + f.getDefaultMessage())
			.collect(Collectors.joining("; "));
		return ResponseEntity.badRequest().body(Map.of("mensaje", "Datos incompletos. " + detalle));
	}

	@ExceptionHandler(AccessDeniedException.class)
	ResponseEntity<Map<String, String>> sinPermiso(AccessDeniedException e) {
		return ResponseEntity.status(HttpStatus.FORBIDDEN).body(Map.of("mensaje", "No tiene permiso para esta acción."));
	}

	@ExceptionHandler(MaxUploadSizeExceededException.class)
	ResponseEntity<Map<String, String>> archivoGrande(MaxUploadSizeExceededException e) {
		return ResponseEntity.status(HttpStatus.CONTENT_TOO_LARGE).body(Map.of("mensaje", "El archivo supera los 20 MB."));
	}

	/** Llave duplicada o registro en uso por otras tablas. */
	@ExceptionHandler(DataIntegrityViolationException.class)
	ResponseEntity<Map<String, String>> integridad(DataIntegrityViolationException e) {
		String causa = e.getMostSpecificCause().getMessage();
		String mensaje;
		if (causa != null && causa.contains("Duplicate entry")) {
			mensaje = "Ya existe un registro con ese valor" + valorDuplicado(causa) + ".";
		}
		else if (causa != null && (causa.contains("foreign key constraint fails") && causa.contains("Cannot delete"))) {
			mensaje = "No se puede eliminar porque otros registros lo usan. Desactívelo en su lugar.";
		}
		else if (causa != null && causa.contains("foreign key constraint fails")) {
			mensaje = "Uno de los datos seleccionados no existe o ya no está disponible.";
		}
		else {
			log.warn("Integridad de datos: {}", causa);
			mensaje = "Los datos no son válidos para guardar (" + (causa == null ? "integridad" : causa.split("\n")[0]) + ").";
		}
		return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("mensaje", mensaje));
	}

	@ExceptionHandler(org.springframework.web.method.annotation.MethodArgumentTypeMismatchException.class)
	ResponseEntity<Map<String, String>> tipo(org.springframework.web.method.annotation.MethodArgumentTypeMismatchException e) {
		return ResponseEntity.badRequest().body(Map.of("mensaje", "El valor de «" + e.getName() + "» no es válido."));
	}

	@ExceptionHandler(org.springframework.web.bind.MissingServletRequestParameterException.class)
	ResponseEntity<Map<String, String>> falta(org.springframework.web.bind.MissingServletRequestParameterException e) {
		return ResponseEntity.badRequest().body(Map.of("mensaje", "Falta el dato «" + e.getParameterName() + "»."));
	}

	@ExceptionHandler(org.springframework.web.servlet.resource.NoResourceFoundException.class)
	ResponseEntity<Map<String, String>> sinRuta(org.springframework.web.servlet.resource.NoResourceFoundException e) {
		return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("mensaje", "No existe: " + e.getResourcePath()));
	}

	private static String valorDuplicado(String causa) {
		int i = causa.indexOf("Duplicate entry '");
		if (i < 0) {
			return "";
		}
		int f = causa.indexOf('\'', i + 17);
		return f > i ? " («" + causa.substring(i + 17, f) + "»)" : "";
	}

	@ExceptionHandler(Exception.class)
	ResponseEntity<Map<String, String>> inesperado(Exception e) {
		log.error("Error no controlado", e);
		return ResponseEntity.internalServerError()
			.body(Map.of("mensaje", "Ocurrió un error inesperado. Intente de nuevo o avise al administrador."));
	}
}
