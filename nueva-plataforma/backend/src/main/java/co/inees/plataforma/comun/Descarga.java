package co.inees.plataforma.comun;

import java.nio.charset.StandardCharsets;

import org.springframework.http.ContentDisposition;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;

/** Respuestas de descarga (Excel, PDF, ZIP) con el nombre del archivo. */
public final class Descarga {

	public static final MediaType XLSX = MediaType
		.parseMediaType("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
	public static final MediaType ZIP = MediaType.parseMediaType("application/zip");

	private Descarga() {
	}

	public static ResponseEntity<byte[]> excel(String nombre, byte[] bytes) {
		return archivo(nombre.endsWith(".xlsx") ? nombre : nombre + ".xlsx", XLSX, bytes);
	}

	public static ResponseEntity<byte[]> pdf(String nombre, byte[] bytes) {
		return archivo(nombre.endsWith(".pdf") ? nombre : nombre + ".pdf", MediaType.APPLICATION_PDF, bytes);
	}

	public static ResponseEntity<byte[]> zip(String nombre, byte[] bytes) {
		return archivo(nombre.endsWith(".zip") ? nombre : nombre + ".zip", ZIP, bytes);
	}

	public static ResponseEntity<byte[]> archivo(String nombre, MediaType tipo, byte[] bytes) {
		return ResponseEntity.ok()
			.header(HttpHeaders.CONTENT_DISPOSITION,
				ContentDisposition.attachment().filename(nombre, StandardCharsets.UTF_8).build().toString())
			.header(HttpHeaders.ACCESS_CONTROL_EXPOSE_HEADERS, HttpHeaders.CONTENT_DISPOSITION)
			.contentType(tipo)
			.body(bytes);
	}

	/** Nombre de archivo seguro: sin tildes ni caracteres que Windows no acepta. */
	public static String nombreSeguro(String texto, String porDefecto) {
		if (texto == null || texto.isBlank()) {
			return porDefecto;
		}
		String s = java.text.Normalizer.normalize(texto.trim(), java.text.Normalizer.Form.NFD).replaceAll("\\p{M}", "");
		s = s.replaceAll("[<>:\"/\\\\|?*\\x00-\\x1F]", "_").replaceAll("\\s+", "_").replaceAll("_+", "_");
		s = s.replaceAll("^[._-]+|[._-]+$", "");
		if (s.length() > 90) {
			s = s.substring(0, 90);
		}
		return s.isEmpty() ? porDefecto : s;
	}
}
