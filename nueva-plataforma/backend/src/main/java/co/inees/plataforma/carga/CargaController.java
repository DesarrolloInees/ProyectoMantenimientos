package co.inees.plataforma.carga;

import java.time.LocalDate;
import java.util.List;

import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RequestPart;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

/** Carga del Excel diario de Prosegur (Auxiliar, Supervisor y SuperUsuario). */
@RestController
@RequestMapping("/api/cargas")
public class CargaController {

	private final CargaService servicio;

	public CargaController(CargaService servicio) {
		this.servicio = servicio;
	}

	@PostMapping(consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	public CargaService.Detalle cargar(@RequestPart("archivo") MultipartFile archivo,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate fechaServicio) {
		return servicio.cargar(archivo, fechaServicio);
	}

	@GetMapping
	public List<CargaService.Resumen> listar() {
		return servicio.listar();
	}

	@GetMapping("/{id}")
	public CargaService.Detalle detalle(@PathVariable int id) {
		return servicio.detalle(id);
	}

	@PostMapping("/{id}/confirmar")
	public CargaService.Detalle confirmar(@PathVariable int id) {
		return servicio.confirmar(id);
	}

	@PostMapping("/{id}/anular")
	public CargaService.Detalle anular(@PathVariable int id) {
		return servicio.anular(id);
	}
}
