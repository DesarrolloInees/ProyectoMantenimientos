package co.inees.plataforma.campo;

import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RequestPart;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.config.NegocioException;

/** Lo que usa el motorizado desde el celular. */
@RestController
@RequestMapping("/api/campo")
public class CampoController {

	public record Gps(Double latitud, Double longitud, String novedad) {
	}

	private static final CampoService.Paso VACIO = new CampoService.Paso(null, null, null, null, null, null, null, null,
		null, null, null, null, null, null, null, null, null, null, null);

	private final CampoService servicio;

	public CampoController(CampoService servicio) {
		this.servicio = servicio;
	}

	@GetMapping("/hoy")
	public CampoService.MiDia hoy() {
		return servicio.miDia();
	}

	@PostMapping("/jornada/iniciar")
	public CampoService.MiDia iniciar(@RequestBody Gps gps) {
		return servicio.iniciarJornada(gps.latitud(), gps.longitud(), gps.novedad());
	}

	@PostMapping("/jornada/finalizar")
	public CampoService.MiDia finalizar(@RequestBody Gps gps) {
		return servicio.finalizarJornada(gps.latitud(), gps.longitud());
	}

	@GetMapping("/formularios")
	public CampoService.Formularios formularios() {
		return servicio.formularios();
	}

	@GetMapping("/ordenes/{id}")
	public CampoService.OrdenCampo orden(@PathVariable int id) {
		return servicio.orden(id);
	}

	@PostMapping("/ordenes/{id}/pasos/{etapa}")
	public CampoService.RespuestaPaso paso(@PathVariable int id, @PathVariable String etapa,
		@RequestBody(required = false) CampoService.Paso paso) {
		Etapa e = Etapa.de(etapa);
		if (e == null) {
			throw new NegocioException("Paso desconocido: " + etapa);
		}
		return servicio.registrarPaso(id, e, paso == null ? VACIO : paso);
	}

	@PostMapping(value = "/ordenes/{id}/evidencias", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	public CampoService.Evidencia evidencia(@PathVariable int id, @RequestParam String tipo,
		@RequestPart("archivo") MultipartFile archivo, @RequestParam(required = false) Double latitud,
		@RequestParam(required = false) Double longitud) {
		return servicio.subirEvidencia(id, tipo, archivo, latitud, longitud);
	}
}
