package co.inees.plataforma.comun;

import java.awt.Graphics2D;
import java.awt.RenderingHints;
import java.awt.image.BufferedImage;
import java.io.IOException;
import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.LocalDate;
import java.util.Locale;
import java.util.UUID;

import javax.imageio.ImageIO;

import org.springframework.core.io.FileSystemResource;
import org.springframework.core.io.Resource;
import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Component;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.config.AppProperties;
import co.inees.plataforma.config.NegocioException;

/**
 * Guarda fotos y firmas. Las rutas quedan como "uploads/imagenes_servicios/AAAA/MM/orden/archivo.jpg",
 * el mismo prefijo que usa la aplicación PHP, para que ambas encuentren los archivos.
 */
@Component
public class Almacenamiento {

	private final Path base;
	private final int ladoMaximo;

	public Almacenamiento(AppProperties props) throws IOException {
		this.base = Path.of(props.almacenamiento().ruta()).toAbsolutePath().normalize();
		this.ladoMaximo = props.almacenamiento().ladoMaximoPx();
		Files.createDirectories(base);
	}

	/** Guarda una imagen reduciéndola a ladoMaximo px; devuelve la ruta relativa que va a la base de datos. */
	public String guardarImagen(int idOrden, String prefijo, MultipartFile archivo) {
		if (archivo == null || archivo.isEmpty()) {
			throw new NegocioException("Debe adjuntar la foto.");
		}
		try (InputStream in = archivo.getInputStream()) {
			BufferedImage original = ImageIO.read(in);
			if (original == null) {
				throw new NegocioException("El archivo no es una imagen válida (use JPG o PNG).");
			}
			return guardar(idOrden, prefijo, reducir(original));
		}
		catch (IOException e) {
			throw new NegocioException(HttpStatus.INTERNAL_SERVER_ERROR, "No se pudo guardar la foto.");
		}
	}

	/** Guarda la firma que llega como data URL base64 (canvas del navegador). */
	public String guardarFirma(int idOrden, String dataUrl) {
		if (dataUrl == null || !dataUrl.contains(",")) {
			throw new NegocioException("Falta la firma de quien recibe el servicio.");
		}
		try {
			byte[] bytes = java.util.Base64.getDecoder().decode(dataUrl.substring(dataUrl.indexOf(',') + 1));
			BufferedImage img = ImageIO.read(new java.io.ByteArrayInputStream(bytes));
			if (img == null) {
				throw new NegocioException("La firma no es válida.");
			}
			BufferedImage blanco = new BufferedImage(img.getWidth(), img.getHeight(), BufferedImage.TYPE_INT_RGB);
			Graphics2D g = blanco.createGraphics();
			g.setColor(java.awt.Color.WHITE);
			g.fillRect(0, 0, img.getWidth(), img.getHeight());
			g.drawImage(img, 0, 0, null);
			g.dispose();
			return guardar(idOrden, "firma", blanco);
		}
		catch (IOException | IllegalArgumentException e) {
			throw new NegocioException("La firma no es válida.");
		}
	}

	/**
	 * Guarda una imagen de otros módulos (horas extra, parqueaderos, transporte, firmas de técnicos) en
	 * uploads/{carpeta}/AAAA/MM/{prefijo}_xxxx.jpg.
	 */
	public String guardarImagenEn(String carpeta, String prefijo, MultipartFile archivo) {
		if (archivo == null || archivo.isEmpty()) {
			throw new NegocioException("Debe adjuntar la foto.");
		}
		try (InputStream in = archivo.getInputStream()) {
			BufferedImage original = ImageIO.read(in);
			if (original == null) {
				throw new NegocioException("El archivo no es una imagen válida (use JPG o PNG).");
			}
			LocalDate hoy = LocalDate.now();
			String relativa = String.format(Locale.ROOT, "uploads/%s/%d/%02d/%s_%s.jpg", carpeta, hoy.getYear(),
				hoy.getMonthValue(), prefijo.replaceAll("[^A-Za-z0-9_-]", "_"), UUID.randomUUID().toString().substring(0, 8));
			Path destino = base.resolve(relativa);
			Files.createDirectories(destino.getParent());
			ImageIO.write(reducir(original), "jpg", destino.toFile());
			return relativa;
		}
		catch (IOException e) {
			throw new NegocioException(HttpStatus.INTERNAL_SERVER_ERROR, "No se pudo guardar la foto.");
		}
	}

	/** Contenido de un archivo guardado, o null si no existe (para incrustarlo en PDF). */
	public byte[] bytes(String rutaRelativa) {
		if (rutaRelativa == null || rutaRelativa.isBlank()) {
			return null;
		}
		Path archivo = base.resolve(rutaRelativa.replace('\\', '/').replaceFirst("^/+", "")).normalize();
		if (!archivo.startsWith(base) || !Files.isRegularFile(archivo)) {
			return null;
		}
		try {
			return Files.readAllBytes(archivo);
		}
		catch (IOException e) {
			return null;
		}
	}

	/** Borra un archivo guardado (si existe). */
	public void eliminar(String rutaRelativa) {
		if (rutaRelativa == null || rutaRelativa.isBlank()) {
			return;
		}
		Path archivo = base.resolve(rutaRelativa).normalize();
		if (archivo.startsWith(base)) {
			try {
				Files.deleteIfExists(archivo);
			}
			catch (IOException e) {
				// queda huérfano en disco; no impide la operación
			}
		}
	}

	public static org.springframework.http.MediaType tipo(String ruta) {
		String r = ruta.toLowerCase(Locale.ROOT);
		if (r.endsWith(".png")) {
			return org.springframework.http.MediaType.IMAGE_PNG;
		}
		if (r.endsWith(".pdf")) {
			return org.springframework.http.MediaType.APPLICATION_PDF;
		}
		if (r.endsWith(".webp")) {
			return org.springframework.http.MediaType.parseMediaType("image/webp");
		}
		return org.springframework.http.MediaType.IMAGE_JPEG;
	}

	public Resource leer(String rutaRelativa) {
		Path archivo = base.resolve(rutaRelativa).normalize();
		if (!archivo.startsWith(base) || !Files.isRegularFile(archivo)) {
			throw NegocioException.noEncontrado("El archivo");
		}
		return new FileSystemResource(archivo);
	}

	private String guardar(int idOrden, String prefijo, BufferedImage imagen) throws IOException {
		LocalDate hoy = LocalDate.now();
		String relativa = String.format(Locale.ROOT, "uploads/imagenes_servicios/%d/%02d/%d/%s_%s.jpg",
			hoy.getYear(), hoy.getMonthValue(), idOrden, prefijo, UUID.randomUUID().toString().substring(0, 8));
		Path destino = base.resolve(relativa);
		Files.createDirectories(destino.getParent());
		ImageIO.write(imagen, "jpg", destino.toFile());
		return relativa;
	}

	private BufferedImage reducir(BufferedImage img) {
		int w = img.getWidth(), h = img.getHeight();
		double escala = Math.min(1.0, (double) ladoMaximo / Math.max(w, h));
		int nw = (int) Math.round(w * escala), nh = (int) Math.round(h * escala);
		BufferedImage salida = new BufferedImage(nw, nh, BufferedImage.TYPE_INT_RGB);
		Graphics2D g = salida.createGraphics();
		g.setRenderingHint(RenderingHints.KEY_INTERPOLATION, RenderingHints.VALUE_INTERPOLATION_BILINEAR);
		g.drawImage(img, 0, 0, nw, nh, null);
		g.dispose();
		return salida;
	}
}
