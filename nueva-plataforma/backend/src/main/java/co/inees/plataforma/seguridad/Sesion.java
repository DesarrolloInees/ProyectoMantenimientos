package co.inees.plataforma.seguridad;

import org.springframework.http.HttpStatus;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.security.oauth2.jwt.Jwt;

import co.inees.plataforma.config.NegocioException;

/** Datos del usuario que hace la petición, tomados del token. */
public final class Sesion {

	public record Usuario(int usuarioId, String nombre, int rol, Integer idTecnico) {
	}

	private Sesion() {
	}

	public static Usuario actual() {
		var auth = SecurityContextHolder.getContext().getAuthentication();
		if (auth == null || !(auth.getPrincipal() instanceof Jwt jwt)) {
			throw new NegocioException(HttpStatus.UNAUTHORIZED, "La sesión expiró. Ingrese de nuevo.");
		}
		Number tecnico = jwt.getClaim("idTecnico");
		Number rol = jwt.getClaim("rol");
		return new Usuario(Integer.parseInt(jwt.getSubject()), jwt.getClaimAsString("nombre"),
			rol == null ? 0 : rol.intValue(), tecnico == null ? null : tecnico.intValue());
	}

	public static int idTecnico() {
		Integer id = actual().idTecnico();
		if (id == null) {
			throw new NegocioException(HttpStatus.FORBIDDEN, "El usuario no está vinculado a un técnico.");
		}
		return id;
	}
}
