package co.inees.plataforma.config;

import org.springframework.http.HttpStatus;

/** Error de una regla de negocio; el mensaje se muestra tal cual al usuario. */
public class NegocioException extends RuntimeException {

	private final HttpStatus estado;

	public NegocioException(String mensaje) {
		this(HttpStatus.UNPROCESSABLE_CONTENT, mensaje);
	}

	public NegocioException(HttpStatus estado, String mensaje) {
		super(mensaje);
		this.estado = estado;
	}

	public HttpStatus getEstado() {
		return estado;
	}

	public static NegocioException noEncontrado(String que) {
		return new NegocioException(HttpStatus.NOT_FOUND, que + " no existe.");
	}
}
