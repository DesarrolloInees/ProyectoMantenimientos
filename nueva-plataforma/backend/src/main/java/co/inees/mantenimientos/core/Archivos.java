package co.inees.mantenimientos.core;

import java.io.IOException;
import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Component;
import org.springframework.web.multipart.MultipartFile;

/**
 * Manejo de archivos en la MISMA estructura de carpetas que la versión PHP
 * (uploads/imagenes_servicios/..., app/uploads/parqueaderos/..., imagenes/firmas/...),
 * de modo que las rutas guardadas en la base de datos siguen siendo válidas.
 */
@Component
public class Archivos {

    private final Path raiz;

    public Archivos(@Value("${inees.legacy-root}") String legacyRoot) {
        this.raiz = Path.of(legacyRoot).toAbsolutePath().normalize();
    }

    /** Equivalente a __DIR__ . '/../../..' (raíz del proyecto PHP). */
    public Path raiz() {
        return raiz;
    }

    /** Resuelve una ruta relativa guardada en BD a ruta absoluta (sin salir de la raíz). */
    public Path resolver(String relativa) {
        if (relativa == null) relativa = "";
        String limpia = relativa.replace('\\', '/');
        while (limpia.startsWith("/")) limpia = limpia.substring(1);
        Path p = raiz.resolve(limpia).normalize();
        if (!p.startsWith(raiz)) throw new IllegalArgumentException("Ruta inválida");
        return p;
    }

    public boolean existe(String relativa) {
        try {
            return relativa != null && !relativa.isBlank() && Files.exists(resolver(relativa));
        } catch (Exception e) {
            return false;
        }
    }

    /** move_uploaded_file() -> guarda en la ruta relativa indicada y la devuelve. */
    public String guardar(MultipartFile f, String rutaRelativa) throws IOException {
        Path destino = resolver(rutaRelativa);
        Files.createDirectories(destino.getParent());
        try (InputStream in = f.getInputStream()) {
            Files.copy(in, destino, StandardCopyOption.REPLACE_EXISTING);
        }
        return rutaRelativa.replace('\\', '/');
    }

    public String guardar(byte[] datos, String rutaRelativa) throws IOException {
        Path destino = resolver(rutaRelativa);
        Files.createDirectories(destino.getParent());
        Files.write(destino, datos);
        return rutaRelativa.replace('\\', '/');
    }

    /**
     * optimizarImagen() de PHP (GD): redimensiona al ancho máximo y guarda como JPEG con la calidad dada.
     * Si no es una imagen legible, guarda el archivo tal cual (move_uploaded_file).
     */
    public boolean guardarImagenOptimizada(MultipartFile f, String rutaRelativa, int anchoMaximo, int calidad) {
        return guardarImagenOptimizada(f, rutaRelativa, anchoMaximo, calidad, false);
    }

    /**
     * @param modoTecnico como optimizarImagen() de tecnicoReporteControlador.php: corrige la orientación EXIF
     *                    y gira 90° (antihorario) las fotos horizontales para dejarlas siempre verticales.
     */
    public boolean guardarImagenOptimizada(MultipartFile f, String rutaRelativa, int anchoMaximo, int calidad, boolean modoTecnico) {
        return guardarImagenOptimizada(f, rutaRelativa, anchoMaximo, calidad, modoTecnico, modoTecnico);
    }

    /** Sólo corrige la orientación EXIF (optimizarImagen() de horaExtra / parqueadero), sin forzar vertical. */
    public boolean guardarImagenOptimizadaExif(MultipartFile f, String rutaRelativa, int anchoMaximo, int calidad) {
        return guardarImagenOptimizada(f, rutaRelativa, anchoMaximo, calidad, false, true);
    }

    private boolean guardarImagenOptimizada(MultipartFile f, String rutaRelativa, int anchoMaximo, int calidad, boolean modoTecnico, boolean exif) {
        try {
            byte[] datos = f.getBytes();
            Path destino = resolver(rutaRelativa);
            Files.createDirectories(destino.getParent());
            java.awt.image.BufferedImage orig = null;
            try {
                orig = javax.imageio.ImageIO.read(new java.io.ByteArrayInputStream(datos));
            } catch (Exception ignored) {
            }
            if (orig == null) {
                Files.write(destino, datos);
                return true;
            }
            if (exif) {
                int o = orientacionExif(datos);
                if (o == 3) orig = rotar(orig, 180);
                else if (o == 6) orig = rotar(orig, -90);
                else if (o == 8) orig = rotar(orig, 90);
            }
            if (modoTecnico && orig.getWidth() > orig.getHeight()) orig = rotar(orig, 90);
            int w = orig.getWidth(), h = orig.getHeight();
            int nw = w, nh = h;
            if (w > anchoMaximo) {
                nw = anchoMaximo;
                nh = (int) Math.round(h * ((double) anchoMaximo / w));
            }
            java.awt.image.BufferedImage out = new java.awt.image.BufferedImage(nw, nh, java.awt.image.BufferedImage.TYPE_INT_RGB);
            java.awt.Graphics2D g = out.createGraphics();
            g.setRenderingHint(java.awt.RenderingHints.KEY_INTERPOLATION, java.awt.RenderingHints.VALUE_INTERPOLATION_BICUBIC);
            g.setRenderingHint(java.awt.RenderingHints.KEY_RENDERING, java.awt.RenderingHints.VALUE_RENDER_QUALITY);
            g.setColor(java.awt.Color.WHITE);
            g.fillRect(0, 0, nw, nh);
            g.drawImage(orig, 0, 0, nw, nh, null);
            g.dispose();
            javax.imageio.ImageWriter wr = javax.imageio.ImageIO.getImageWritersByFormatName("jpg").next();
            javax.imageio.ImageWriteParam p = wr.getDefaultWriteParam();
            p.setCompressionMode(javax.imageio.ImageWriteParam.MODE_EXPLICIT);
            p.setCompressionQuality(Math.max(0, Math.min(100, calidad)) / 100f);
            try (javax.imageio.stream.ImageOutputStream ios = javax.imageio.ImageIO.createImageOutputStream(destino.toFile())) {
                wr.setOutput(ios);
                wr.write(null, new javax.imageio.IIOImage(out, null, null), p);
            } finally {
                wr.dispose();
            }
            return true;
        } catch (Exception e) {
            try {
                guardar(f, rutaRelativa);
                return true;
            } catch (Exception e2) {
                return false;
            }
        }
    }

    /** imagerotate() de GD: ángulo positivo = antihorario. Sólo múltiplos de 90. */
    public static java.awt.image.BufferedImage rotar(java.awt.image.BufferedImage src, int grados) {
        int g = ((grados % 360) + 360) % 360;
        if (g == 0) return src;
        int w = src.getWidth(), h = src.getHeight();
        boolean cambia = g == 90 || g == 270;
        java.awt.image.BufferedImage out = new java.awt.image.BufferedImage(cambia ? h : w, cambia ? w : h, java.awt.image.BufferedImage.TYPE_INT_RGB);
        java.awt.Graphics2D gr = out.createGraphics();
        java.awt.geom.AffineTransform t = new java.awt.geom.AffineTransform();
        if (g == 90) { // antihorario
            t.translate(0, w);
            t.rotate(-Math.PI / 2);
        } else if (g == 180) {
            t.translate(w, h);
            t.rotate(Math.PI);
        } else { // 270 antihorario = 90 horario
            t.translate(h, 0);
            t.rotate(Math.PI / 2);
        }
        gr.drawImage(src, t, null);
        gr.dispose();
        return out;
    }

    /** Lee la etiqueta Orientation (0x0112) del bloque EXIF de un JPEG; 1 si no existe. */
    static int orientacionExif(byte[] b) {
        try {
            if (b.length < 4 || (b[0] & 0xFF) != 0xFF || (b[1] & 0xFF) != 0xD8) return 1;
            int i = 2;
            while (i + 4 < b.length) {
                if ((b[i] & 0xFF) != 0xFF) return 1;
                int marcador = b[i + 1] & 0xFF;
                int largo = ((b[i + 2] & 0xFF) << 8) | (b[i + 3] & 0xFF);
                if (marcador == 0xE1 && i + 10 < b.length && b[i + 4] == 'E' && b[i + 5] == 'x' && b[i + 6] == 'i' && b[i + 7] == 'f') {
                    int tiff = i + 10;
                    boolean le = b[tiff] == 'I';
                    java.util.function.IntUnaryOperator u16 = p -> le ? (b[p] & 0xFF) | ((b[p + 1] & 0xFF) << 8) : ((b[p] & 0xFF) << 8) | (b[p + 1] & 0xFF);
                    java.util.function.IntUnaryOperator u32 = p -> le
                            ? (b[p] & 0xFF) | ((b[p + 1] & 0xFF) << 8) | ((b[p + 2] & 0xFF) << 16) | ((b[p + 3] & 0xFF) << 24)
                            : ((b[p] & 0xFF) << 24) | ((b[p + 1] & 0xFF) << 16) | ((b[p + 2] & 0xFF) << 8) | (b[p + 3] & 0xFF);
                    int ifd = tiff + u32.applyAsInt(tiff + 4);
                    int n = u16.applyAsInt(ifd);
                    for (int k = 0; k < n; k++) {
                        int e = ifd + 2 + k * 12;
                        if (u16.applyAsInt(e) == 0x0112) return u16.applyAsInt(e + 8);
                    }
                    return 1;
                }
                if (marcador == 0xDA) return 1;
                i += 2 + largo;
            }
        } catch (Exception ignored) {
        }
        return 1;
    }

    public boolean borrar(String relativa) {
        try {
            return Files.deleteIfExists(resolver(relativa));
        } catch (Exception e) {
            return false;
        }
    }

    public byte[] leer(String relativa) throws IOException {
        return Files.readAllBytes(resolver(relativa));
    }

    /** pathinfo($nombre, PATHINFO_EXTENSION) en minúsculas */
    public static String extension(String nombre) {
        if (nombre == null) return "";
        int i = nombre.lastIndexOf('.');
        return i < 0 ? "" : nombre.substring(i + 1).toLowerCase();
    }

    /** uniqid() de PHP (13 caracteres hex basados en el tiempo). */
    public static String uniqid() {
        long micros = System.currentTimeMillis() * 1000 + (System.nanoTime() / 1000) % 1000;
        long seg = micros / 1_000_000;
        long usec = micros % 1_000_000;
        return String.format("%08x%05x", seg, usec);
    }

    /** Nombre seguro para carpetas (reemplaza espacios y caracteres raros por _). */
    public static String limpiarNombre(String s) {
        if (s == null) return "";
        String n = java.text.Normalizer.normalize(s, java.text.Normalizer.Form.NFD).replaceAll("\\p{M}", "");
        return n.trim().replaceAll("[^A-Za-z0-9_\\-]+", "_");
    }
}
