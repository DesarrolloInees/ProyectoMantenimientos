package co.inees.plataforma.config;

import java.util.List;

import org.springframework.boot.context.properties.ConfigurationProperties;

/**
 * Configuración propia. Las claves (correo, Telegram, IA, Google) llegan por variables de entorno;
 * ninguna queda escrita en el código como pasaba en la aplicación PHP.
 */
@ConfigurationProperties(prefix = "app")
public record AppProperties(String zonaHoraria, Jwt jwt, Almacenamiento almacenamiento, List<String> corsOrigenes,
	Correo correo, Telegram telegram, Ia ia, Google google, Osrm osrm) {

	public record Jwt(String secreto, int horasValidez) {
	}

	public record Almacenamiento(String ruta, int ladoMaximoPx) {
	}

	/** Remitente de los correos; si spring.mail.host está vacío no se envían correos. */
	public record Correo(String remitente, String nombreRemitente) {
	}

	public record Telegram(String token, String chatId) {
		public boolean configurado() {
			return token != null && !token.isBlank() && chatId != null && !chatId.isBlank();
		}
	}

	/** Groq (API compatible con OpenAI) para mejorar la redacción de los reportes. */
	public record Ia(String url, List<String> claves, List<String> modelos) {
	}

	public record Google(String claveGeocodificacion) {
	}

	/**
	 * Servidor OSRM propio para las distancias por carretera (OSRM_URL, p. ej. http://localhost:5000). Vacío = línea
	 * recta × factor vial. maxTabla = el --max-table-size del servidor (coordenadas por consulta).
	 */
	public record Osrm(String url, Integer maxTabla) {
	}
}
