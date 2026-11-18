package co.inees.plataforma.costos;

import java.io.IOException;
import java.time.LocalDate;

import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RequestPart;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.comun.Descarga;
import co.inees.plataforma.config.NegocioException;

/**
 * Analizador de cotizaciones (reporteFacturacion de PHP, solo SuperUsuario: permiso COSTOS). Cada llamada recibe el
 * Excel de control de cotizaciones; no se guarda nada.
 */
@RestController
@RequestMapping("/api/costos/cotizaciones")
public class CotizacionesController {

	@PostMapping(consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	public AnalizadorCotizaciones.Analisis analizar(@RequestPart MultipartFile archivo) {
		return leer(archivo).analisis();
	}

	@PostMapping(value = "/excel", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	public ResponseEntity<byte[]> excel(@RequestPart MultipartFile archivo,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta,
		@RequestParam(required = false) String categoria, @RequestParam(required = false) String estado) {
		return Descarga.excel("Cotizaciones_" + LocalDate.now(), leer(archivo).excel(new AnalizadorCotizaciones.Filtro(desde, hasta, categoria, estado)));
	}

	/** {@code tipo=lista}: la tabla filtrada; {@code tipo=gerencial}: facturas, cotizaciones y Q pendientes. */
	@PostMapping(value = "/pdf", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	public ResponseEntity<byte[]> pdf(@RequestPart MultipartFile archivo, @RequestParam(defaultValue = "gerencial") String tipo,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta,
		@RequestParam(required = false) String categoria, @RequestParam(required = false) String estado) {
		var a = leer(archivo);
		return "lista".equals(tipo)
			? Descarga.pdf("Cotizaciones_" + LocalDate.now(), a.pdfLista(new AnalizadorCotizaciones.Filtro(desde, hasta, categoria, estado)))
			: Descarga.pdf("Reportes_Gerenciales_" + LocalDate.now(), a.pdfGerencial());
	}

	private static AnalizadorCotizaciones leer(MultipartFile archivo) {
		if (archivo == null || archivo.isEmpty()) {
			throw new NegocioException("Suba el Excel de control de cotizaciones.");
		}
		try {
			return AnalizadorCotizaciones.leer(archivo.getInputStream());
		}
		catch (IOException e) {
			throw new NegocioException("No se pudo leer el archivo.");
		}
	}
}
