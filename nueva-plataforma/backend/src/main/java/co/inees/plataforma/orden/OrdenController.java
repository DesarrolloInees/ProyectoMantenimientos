package co.inees.plataforma.orden;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.ModelAttribute;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RequestPart;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.servlet.mvc.method.annotation.StreamingResponseBody;

import co.inees.plataforma.comun.Descarga;
import co.inees.plataforma.comun.Excel;
import co.inees.plataforma.comun.Html;
import co.inees.plataforma.comun.Valorizacion;
import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.remision.Talonario;
import jakarta.validation.Valid;
import jakarta.validation.constraints.NotNull;

/** Servicios en la oficina: listado por día, búsqueda, registro manual, corrección, PDF y Excel (permiso ORDENES). */
@RestController
public class OrdenController {

	public record Lote(@NotNull @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate fecha,
		@NotNull List<OrdenService.DatosServicio> servicios) {
	}

	public record Correccion(@NotNull OrdenService.DatosServicio datos, String motivo) {
	}

	public record Motivo(String motivo) {
	}

	public record DiaDelegacion(String delegacion, int servicios, BigDecimal valor, int tecnicos) {
	}

	public record Dia(LocalDate fecha, int servicios, BigDecimal valor, List<DiaDelegacion> delegaciones) {
	}

	public record Precio(BigDecimal tarifa, boolean fueraDelegacion, int diasViaticos, BigDecimal valorViaticos) {
	}

	public record Stock(int idRepuesto, String nombre, String codigo, int cantidad) {
	}

	public record Zip(@NotNull List<Integer> ids, boolean porDelegacion, boolean porTecnico) {
	}

	public record ServicioPdf(int idOrden, String numeroRemision, LocalDate fechaVisita, String tecnico, String cliente,
		String punto, String delegacion, String tipoMantenimiento) {
	}

	private final JdbcClient jdbc;
	private final ServicioConsultas consultas;
	private final OrdenService servicio;
	private final PdfServicio pdf;
	private final Valorizacion valorizacion;
	private final Talonario talonario;

	public OrdenController(JdbcClient jdbc, ServicioConsultas consultas, OrdenService servicio, PdfServicio pdf,
		Valorizacion valorizacion, Talonario talonario) {
		this.jdbc = jdbc;
		this.consultas = consultas;
		this.servicio = servicio;
		this.pdf = pdf;
		this.valorizacion = valorizacion;
		this.talonario = talonario;
	}

	// ------------------------------------------------------------------ listados
	/** Resumen por día y delegación (pantalla «Servicios por día»). */
	@GetMapping("/api/ordenes/dias")
	public List<Dia> dias(@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		LocalDate fin = hasta == null ? LocalDate.now() : hasta;
		LocalDate ini = desde == null ? fin.minusDays(60) : desde;
		record Fila(LocalDate fecha, String delegacion, int servicios, BigDecimal valor, int tecnicos) {
		}
		List<Fila> filas = jdbc.sql("""
			SELECT o.fecha_visita AS fecha, COALESCE(d.nombre_delegacion, 'SIN ASIGNAR') AS delegacion, COUNT(*) AS servicios,
			       COALESCE(SUM(o.valor_servicio), 0) AS valor, COUNT(DISTINCT o.id_tecnico) AS tecnicos
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto
			LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :ini AND :fin
			GROUP BY o.fecha_visita, d.nombre_delegacion ORDER BY o.fecha_visita DESC, d.nombre_delegacion""")
			.param("ini", ini).param("fin", fin).query(Fila.class).list();
		Map<LocalDate, List<Fila>> porDia = new LinkedHashMap<>();
		filas.forEach(f -> porDia.computeIfAbsent(f.fecha(), k -> new ArrayList<>()).add(f));
		return porDia.entrySet().stream().map(e -> new Dia(e.getKey(), e.getValue().stream().mapToInt(Fila::servicios).sum(),
			e.getValue().stream().map(Fila::valor).reduce(BigDecimal.ZERO, BigDecimal::add),
			e.getValue().stream().map(f -> new DiaDelegacion(f.delegacion(), f.servicios(), f.valor(), f.tecnicos())).toList()))
			.toList();
	}

	@GetMapping("/api/ordenes")
	public List<ServicioConsultas.Servicio> buscar(@ModelAttribute ServicioConsultas.Filtros f,
		@RequestParam(defaultValue = "500") int limite) {
		exigirFiltro(f);
		return consultas.buscar(f, "o.fecha_visita DESC, t.nombre_tecnico, o.hora_entrada, o.id_ordenes_servicio DESC",
			Math.min(Math.max(limite, 1), 3000));
	}

	@GetMapping("/api/ordenes/{id}")
	public ServicioConsultas.Servicio obtener(@PathVariable int id) {
		return consultas.uno(id);
	}

	// ------------------------------------------------------------------ registro y corrección
	@PostMapping("/api/ordenes/lote")
	public OrdenService.ResultadoLote registrar(@Valid @RequestBody Lote l) {
		if (l.servicios().isEmpty()) {
			throw new NegocioException("No hay servicios para guardar.");
		}
		return servicio.registrarLote(l.fecha(), l.servicios(), d -> servicio.registrar(l.fecha(), d));
	}

	@PutMapping("/api/ordenes/{id}")
	public ServicioConsultas.Servicio corregir(@PathVariable int id, @Valid @RequestBody Correccion c) {
		return servicio.actualizar(id, c.datos(), c.motivo());
	}

	@PostMapping("/api/ordenes/{id}/anular")
	public void anular(@PathVariable int id, @RequestBody Motivo m) {
		servicio.anular(id, m.motivo());
	}

	@PostMapping("/api/ordenes/{id}/repuestos")
	public List<ServicioConsultas.Repuesto> agregarRepuesto(@PathVariable int id, @RequestBody OrdenService.RepuestoFila r) {
		return servicio.agregarRepuesto(id, r);
	}

	@DeleteMapping("/api/ordenes/{id}/repuestos/{idRepuesto}")
	public List<ServicioConsultas.Repuesto> quitarRepuesto(@PathVariable int id, @PathVariable int idRepuesto,
		@RequestParam String origen) {
		return servicio.quitarRepuesto(id, idRepuesto, origen);
	}

	@PutMapping("/api/ordenes/{id}/novedades")
	public ServicioConsultas.Servicio novedades(@PathVariable int id, @RequestBody OrdenService.Novedades n) {
		return servicio.guardarNovedades(id, n);
	}

	@GetMapping("/api/ordenes/{id}/complemento")
	public OrdenService.Complemento complemento(@PathVariable int id) {
		return servicio.complemento(id);
	}

	@PutMapping("/api/ordenes/{id}/complemento")
	public OrdenService.Complemento guardarComplemento(@PathVariable int id, @RequestBody OrdenService.Complemento c) {
		return servicio.guardarComplemento(id, c);
	}

	@GetMapping("/api/ordenes/{id}/evidencias")
	public List<OrdenService.Evidencia> evidencias(@PathVariable int id) {
		return servicio.evidencias(id);
	}

	@PostMapping(value = "/api/ordenes/{id}/evidencias", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	public OrdenService.Evidencia subirEvidencia(@PathVariable int id, @RequestParam String tipo,
		@RequestPart("archivo") MultipartFile archivo) {
		return servicio.subirEvidencia(id, tipo, archivo);
	}

	@DeleteMapping("/api/ordenes/{id}/evidencias/{idEvidencia}")
	public void eliminarEvidencia(@PathVariable int id, @PathVariable int idEvidencia) {
		servicio.eliminarEvidencia(id, idEvidencia);
	}

	@GetMapping("/api/ordenes/{id}/historial")
	public List<OrdenService.HistorialFila> historial(@PathVariable int id) {
		return servicio.historial(id);
	}

	// ------------------------------------------------------------------ ayudas del formulario
	@GetMapping("/api/ordenes/precio")
	public Precio precio(@RequestParam int idMaquina, @RequestParam int idPunto, @RequestParam int idTipoMantenimiento,
		@RequestParam(required = false) Integer idModalidad, @RequestParam(required = false) Integer idTecnico,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate fecha,
		@RequestParam(required = false) Integer idOrden) {
		int modalidad = idModalidad != null ? idModalidad
			: jdbc.sql("SELECT id_modalidad FROM punto WHERE id_punto = :p").param("p", idPunto).query(Integer.class).single();
		var v = valorizacion.viaticos(idTecnico, fecha, modalidad, idPunto, idOrden);
		return new Precio(valorizacion.tarifaMaquina(idMaquina, idTipoMantenimiento, modalidad, fecha), v.fueraDelegacion(),
			v.dias(), v.valor());
	}

	@GetMapping("/api/ordenes/remision")
	public Talonario.Estado verificarRemision(@RequestParam String numero, @RequestParam int idTecnico,
		@RequestParam(required = false) Integer idOrden) {
		return talonario.verificar(numero, idTecnico, idOrden);
	}

	@GetMapping("/api/ordenes/remisiones-disponibles")
	public List<String> remisionesDisponibles(@RequestParam int idTecnico) {
		return jdbc.sql("""
			SELECT numero_remision FROM control_remisiones
			WHERE id_tecnico = :t AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE')
			ORDER BY CAST(numero_remision AS UNSIGNED)""").param("t", idTecnico).query(String.class).list();
	}

	@GetMapping("/api/ordenes/stock")
	public List<Stock> stock(@RequestParam int idTecnico) {
		return jdbc.sql("""
			SELECT r.id_repuesto, r.nombre_repuesto AS nombre, r.codigo_referencia AS codigo, i.cantidad_actual AS cantidad
			FROM inventario_tecnico i JOIN repuesto r ON r.id_repuesto = i.id_repuesto
			WHERE i.id_tecnico = :t AND i.estado = 1 AND i.cantidad_actual > 0 ORDER BY r.nombre_repuesto""")
			.param("t", idTecnico).query(Stock.class).list();
	}

	// ------------------------------------------------------------------ PDF y Excel
	@GetMapping("/api/ordenes/{id}/pdf")
	public ResponseEntity<byte[]> pdf(@PathVariable int id) {
		ServicioConsultas.Servicio s = consultas.uno(id);
		return Descarga.pdf(pdf.nombre(s), pdf.pdf(id));
	}

	@GetMapping("/api/servicios-pdf")
	public List<ServicioPdf> serviciosParaPdf(@ModelAttribute ServicioConsultas.Filtros f) {
		exigirFiltro(f);
		return consultas.buscar(f, "o.fecha_visita DESC, o.id_ordenes_servicio DESC", 1000).stream()
			.map(s -> new ServicioPdf(s.idOrden(), s.numeroRemision(), s.fechaVisita(), s.tecnico(), s.cliente(), s.punto(),
				s.delegacion(), s.tipoMantenimiento()))
			.toList();
	}

	@PostMapping("/api/servicios-pdf/zip")
	public ResponseEntity<StreamingResponseBody> zip(@Valid @RequestBody Zip z) {
		if (z.ids().isEmpty()) {
			throw new NegocioException("Seleccione al menos un servicio.");
		}
		if (z.ids().size() > 600) {
			throw new NegocioException("Máximo 600 servicios por descarga; filtre por fechas o delegación.");
		}
		StreamingResponseBody cuerpo = out -> pdf.zip(z.ids(), z.porDelegacion(), z.porTecnico(), out);
		return ResponseEntity.ok()
			.header("Content-Disposition", "attachment; filename=\"Servicios_" + LocalDate.now() + ".zip\"")
			.header("Access-Control-Expose-Headers", "Content-Disposition")
			.contentType(Descarga.ZIP).body(cuerpo);
	}

	@GetMapping("/api/ordenes/excel")
	public ResponseEntity<byte[]> excel(@ModelAttribute ServicioConsultas.Filtros f) {
		exigirFiltro(f);
		List<ServicioConsultas.Servicio> lista = consultas.buscar(f, "t.nombre_tecnico, o.fecha_visita, o.hora_entrada", 50000);
		List<? extends List<?>> filas = lista.stream().map(s -> List.<Object>of(
			s.idOrden(), n(s.numeroRemision()), s.fechaVisita(), n(s.horaEntrada()), n(s.horaSalida()), Html.minutos(s.minutosServicio()),
			new Excel.Pesos(s.valorServicio()), new Excel.Pesos(s.valorViaticos()), n(s.tipoMantenimiento()), s.deviceId(),
			n(s.tipoMaquina()), s.cliente(), s.punto(), n(s.delegacion()), n(s.modalidad()), n(s.tecnico()), n(s.estadoMaquina()),
			n(s.calificacion()), n(s.novedades()), n(s.repuestosTexto()), n(s.actividades()))).map(l -> (List<?>) l).toList();
		byte[] xlsx = new Excel().hoja("Servicios", List.of("ID", "Remisión", "Fecha", "Entrada", "Salida", "Duración", "Valor",
			"Viáticos", "Tipo de servicio", "Device ID", "Tipo de máquina", "Cliente", "Punto", "Delegación", "Modalidad",
			"Técnico", "Estado máquina", "Calificación", "Novedades", "Repuestos", "Actividades"), filas).bytes();
		return Descarga.excel("Servicios_" + (f.desde() == null ? "" : f.desde()) + "_" + (f.hasta() == null ? "" : f.hasta()), xlsx);
	}

	/** Servicios con novedad en un rango (una fila por servicio con todas sus novedades). */
	@GetMapping("/api/ordenes/novedades-excel")
	public ResponseEntity<byte[]> novedadesExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		var f = new ServicioConsultas.Filtros(desde, hasta, null, null, null, null, null, null, null, true, 1);
		List<? extends List<?>> filas = consultas.buscar(f, "o.fecha_visita, t.nombre_tecnico", 50000).stream()
			.map(s -> (List<?>) java.util.Arrays.asList(s.fechaVisita(), s.numeroRemision(), s.novedades(), s.detalleNovedad(),
				s.actividades(), s.deviceId(), s.tipoMaquina(), s.tecnico(), s.cliente(), s.punto(), s.delegacion()))
			.toList();
		byte[] xlsx = new Excel().hoja("Novedades", List.of("Fecha", "Remisión", "Novedades", "Detalle", "Observación",
			"Device ID", "Tipo de máquina", "Técnico", "Cliente", "Punto", "Delegación"), filas).bytes();
		return Descarga.excel("Novedades_" + desde + "_" + hasta, xlsx);
	}

	private static Object n(Object v) {
		return v == null ? "" : v;
	}

	private static void exigirFiltro(ServicioConsultas.Filtros f) {
		boolean alguno = f.desde() != null || f.hasta() != null || f.idCliente() != null || f.idPunto() != null
			|| f.idTecnico() != null || (f.remision() != null && !f.remision().isBlank())
			|| (f.deviceId() != null && !f.deviceId().isBlank());
		if (!alguno) {
			throw new NegocioException("Indique al menos un filtro (fechas, cliente, punto, técnico, remisión o máquina).");
		}
	}
}
