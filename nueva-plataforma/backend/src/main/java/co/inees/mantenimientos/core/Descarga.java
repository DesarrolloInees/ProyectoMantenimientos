package co.inees.mantenimientos.core;

import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;

import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;

/** Respuestas de archivo (header Content-Type + Content-Disposition de PHP). */
public final class Descarga {

    public static final String XLSX = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
    public static final String PDF = "application/pdf";

    private Descarga() {}

    public static ResponseEntity<byte[]> archivo(byte[] datos, String nombre, String tipo, boolean inline) {
        String enc = URLEncoder.encode(nombre, StandardCharsets.UTF_8).replace("+", "%20");
        String ascii = nombre.replaceAll("[^\\x20-\\x7E]", "_").replace("\"", "");
        return ResponseEntity.ok()
                .header(HttpHeaders.CONTENT_DISPOSITION,
                        (inline ? "inline" : "attachment") + "; filename=\"" + ascii + "\"; filename*=UTF-8''" + enc)
                .header(HttpHeaders.CACHE_CONTROL, "max-age=0")
                .contentType(MediaType.parseMediaType(tipo))
                .contentLength(datos.length)
                .body(datos);
    }

    public static ResponseEntity<byte[]> excel(byte[] datos, String nombre) {
        return archivo(datos, nombre, XLSX, false);
    }

    public static ResponseEntity<byte[]> pdf(byte[] datos, String nombre, boolean inline) {
        return archivo(datos, nombre, PDF, inline);
    }

    public static ResponseEntity<byte[]> html(String html) {
        return ResponseEntity.ok().contentType(MediaType.parseMediaType("text/html;charset=UTF-8"))
                .body(html.getBytes(StandardCharsets.UTF_8));
    }
}
