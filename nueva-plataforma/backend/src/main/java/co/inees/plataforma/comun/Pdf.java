package co.inees.plataforma.comun;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;

import org.springframework.core.io.ClassPathResource;

import com.openhtmltopdf.pdfboxout.PdfRendererBuilder;

/**
 * Convierte HTML (XHTML bien formado) en PDF con openhtmltopdf. Reemplaza a Chrome/Puppeteer de la aplicación PHP:
 * no necesita navegador en el servidor.
 */
public final class Pdf {

	/** Estilos base de todos los documentos: A4, márgenes de 10 mm y tipografía del sistema. */
	public static final String ESTILO_BASE = """
		@page { size: A4; margin: 10mm; }
		@page :first { margin-top: 10mm; }
		body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9.5pt; color: #1b2430; }
		h1 { font-size: 15pt; margin: 0 0 4pt 0; }
		h2 { font-size: 12pt; margin: 12pt 0 6pt 0; color: #072942; border-bottom: 1.5pt solid #2d7f3e; padding-bottom: 2pt; }
		h3 { font-size: 10.5pt; margin: 10pt 0 4pt 0; color: #072942; }
		table { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
		th { background: #072942; color: #fff; text-align: left; padding: 3pt 4pt; font-size: 8.5pt; }
		td { border-bottom: 0.5pt solid #d5dbe1; padding: 3pt 4pt; vertical-align: top; font-size: 8.5pt; }
		.der { text-align: right; }
		.cen { text-align: center; }
		.muted { color: #6f7d8c; }
		.caja { border: 0.75pt solid #d5dbe1; border-radius: 4pt; padding: 6pt; margin-bottom: 6pt; }
		.encabezado { width: 100%; border-bottom: 2pt solid #072942; margin-bottom: 8pt; }
		.encabezado td { border: none; padding: 0 0 6pt 0; vertical-align: bottom; }
		.encabezado .logo-inees { height: 34pt; }
		.encabezado h1 { color: #072942; }
		.encabezado .der { text-align: right; }
		.total td { font-weight: bold; background: #eef1f4; }
		.salto { page-break-before: always; }
		""";

	static {
		// openhtmltopdf escribe mensajes informativos por cada documento; solo interesan los errores.
		com.openhtmltopdf.util.XRLog.setLoggingEnabled(false);
	}

	private Pdf() {
	}

	/** @param html documento completo; usar {@link #documento} para envolver un cuerpo con los estilos base. */
	public static byte[] generar(String html) {
		try (var out = new ByteArrayOutputStream()) {
			var builder = new PdfRendererBuilder();
			builder.useFastMode();
			builder.withHtmlContent(html, null);
			builder.toStream(out);
			builder.run();
			return out.toByteArray();
		}
		catch (IOException e) {
			throw new IllegalStateException("No se pudo generar el PDF", e);
		}
	}

	public static String documento(String titulo, String estilosExtra, String cuerpo) {
		return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"/><title>" + Html.esc(titulo) + "</title><style>"
			+ ESTILO_BASE + (estilosExtra == null ? "" : estilosExtra) + "</style></head><body>" + cuerpo + "</body></html>";
	}

	/** Encabezado estándar de los documentos de INEES: logo del kit de marca a la izquierda, título a la derecha. */
	public static String encabezado(String titulo, String subtitulo) {
		return "<table class=\"encabezado\"><tr><td>" + Html.imagen(logo(), "logo-inees") + "</td><td class=\"der\"><h1>"
			+ Html.esc(titulo) + "</h1>" + (subtitulo == null ? "" : "<div class=\"muted\">" + Html.esc(subtitulo) + "</div>")
			+ "</td></tr></table>";
	}

	private static byte[] logo;

	/** Logo «INEES Secure Ai Systems» (inees-logo.svg de inees.com.co, renderizado a PNG). */
	public static synchronized byte[] logo() {
		if (logo == null) {
			try (InputStream in = new ClassPathResource("pdf/logo-inees.png").getInputStream()) {
				logo = in.readAllBytes();
			}
			catch (IOException e) {
				logo = new byte[0];
			}
		}
		return logo;
	}
}
