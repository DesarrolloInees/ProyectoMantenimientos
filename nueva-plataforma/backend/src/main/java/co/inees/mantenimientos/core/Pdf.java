package co.inees.mantenimientos.core;

import java.io.ByteArrayOutputStream;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.ArrayList;
import java.util.Base64;
import java.util.List;
import java.util.concurrent.TimeUnit;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Component;

import com.openhtmltopdf.pdfboxout.PdfRendererBuilder;

/**
 * Conversión HTML -> PDF.
 * Reemplaza Browsershot (Chrome headless), Dompdf y mPDF.
 * 1) Si hay Chrome/Chromium disponible (CHROME_PATH) se usa en modo headless (igual que Browsershot).
 * 2) Si no, se usa OpenHTMLtoPDF (Java puro, similar a Dompdf).
 */
@Component
public class Pdf {

    private static final Logger log = LoggerFactory.getLogger(Pdf.class);

    private final String chromePath;
    private final Archivos archivos;

    public Pdf(@Value("${inees.chrome-path:}") String chromePath, Archivos archivos) {
        this.chromePath = chromePath;
        this.archivos = archivos;
    }

    public boolean chromeDisponible() {
        return chromePath != null && !chromePath.isBlank() && Files.exists(Path.of(chromePath));
    }

    /**
     * @param html     documento HTML completo
     * @param pageCss  regla @page opcional, ej: "size: A4 landscape; margin: 10mm"
     */
    public byte[] generar(String html, String pageCss) {
        String doc = html;
        if (pageCss != null && !pageCss.isBlank()) {
            String estilo = "<style>@page{" + pageCss + "}</style>";
            if (doc.contains("</head>")) doc = doc.replace("</head>", estilo + "</head>");
            else doc = estilo + doc;
        }
        if (chromeDisponible()) {
            try {
                return conChrome(doc);
            } catch (Exception e) {
                log.warn("Chrome headless falló, usando OpenHTMLtoPDF: {}", e.getMessage());
            }
        }
        return conOpenHtml(doc);
    }

    public byte[] generar(String html) {
        return generar(html, null);
    }

    private byte[] conChrome(String html) throws Exception {
        Path dir = Files.createTempDirectory("inees-pdf");
        Path in = dir.resolve("doc.html");
        Path out = dir.resolve("doc.pdf");
        Files.writeString(in, html, StandardCharsets.UTF_8);
        List<String> cmd = new ArrayList<>(List.of(
                chromePath, "--headless=new", "--disable-gpu", "--no-sandbox", "--no-first-run",
                "--disable-extensions", "--no-pdf-header-footer", "--print-to-pdf-no-header",
                "--run-all-compositor-stages-before-draw", "--virtual-time-budget=15000",
                "--user-data-dir=" + dir.resolve("perfil").toAbsolutePath(),
                "--print-to-pdf=" + out.toAbsolutePath(),
                in.toUri().toString()));
        Process p = new ProcessBuilder(cmd).redirectErrorStream(true).start();
        p.getInputStream().transferTo(java.io.OutputStream.nullOutputStream());
        if (!p.waitFor(120, TimeUnit.SECONDS)) {
            p.destroyForcibly();
            throw new IllegalStateException("Timeout generando PDF con Chrome");
        }
        if (!Files.exists(out) || Files.size(out) == 0) {
            throw new IllegalStateException("Chrome no generó el PDF");
        }
        byte[] r = Files.readAllBytes(out);
        try {
            try (var w = Files.walk(dir)) {
                w.sorted(java.util.Comparator.reverseOrder()).forEach(x -> x.toFile().delete());
            }
        } catch (Exception ignored) {
        }
        return r;
    }

    private byte[] conOpenHtml(String html) {
        try (ByteArrayOutputStream os = new ByteArrayOutputStream()) {
            org.jsoup.nodes.Document d = org.jsoup.Jsoup.parse(html);
            d.outputSettings().syntax(org.jsoup.nodes.Document.OutputSettings.Syntax.xml);
            d.outputSettings().escapeMode(org.jsoup.nodes.Entities.EscapeMode.xhtml);
            PdfRendererBuilder b = new PdfRendererBuilder();
            b.useFastMode();
            b.withHtmlContent(d.html(), archivos.raiz().toUri().toString());
            b.toStream(os);
            b.run();
            return os.toByteArray();
        } catch (Exception e) {
            throw new IllegalStateException("Error generando PDF: " + e.getMessage(), e);
        }
    }

    /** Imagen local -> data URI base64 (equivalente a base64_encode(file_get_contents()) en las plantillas). */
    public String dataUri(String rutaRelativa) {
        try {
            if (!archivos.existe(rutaRelativa)) return "";
            byte[] b = archivos.leer(rutaRelativa);
            String ext = Archivos.extension(rutaRelativa);
            String mime = switch (ext) {
                case "png" -> "image/png";
                case "gif" -> "image/gif";
                case "webp" -> "image/webp";
                case "svg" -> "image/svg+xml";
                default -> "image/jpeg";
            };
            return "data:" + mime + ";base64," + Base64.getEncoder().encodeToString(b);
        } catch (Exception e) {
            return "";
        }
    }
}
