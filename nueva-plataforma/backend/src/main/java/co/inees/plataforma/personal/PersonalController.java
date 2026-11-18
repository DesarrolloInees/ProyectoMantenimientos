package co.inees.plataforma.personal;

import static co.inees.plataforma.comun.Html.esc;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.comun.Almacenamiento;
import co.inees.plataforma.comun.Descarga;
import co.inees.plataforma.comun.Excel;
import co.inees.plataforma.comun.Html;
import co.inees.plataforma.comun.Pdf;
import co.inees.plataforma.config.NegocioException;
import jakarta.validation.Valid;
import jakarta.validation.constraints.NotNull;

/**
 * Oficina: aprobación de horas extra, parqueaderos, jornadas, tiempos de servicio y rastreo de técnicos
 * (permiso PERSONAL; el rastreo también con SEGUIMIENTO).
 */
@RestController
public class PersonalController {

	public record Aprobacion(@NotNull Integer idEstado, String observacion) {
	}

	public record Rendimiento(int idTecnico, String tecnico, int registrados, int finalizados, int enProgreso,
		Map<String, Integer> porTipo, int minutosTotales, int excedidos) {
	}

	public record PuntoRastro(int idOrden, String tecnico, String cliente, String punto, LocalTime horaEntrada,
		LocalTime horaSalida, Double latitudInicio, Double longitudInicio, Double latitudFin, Double longitudFin,
		String observacion) {
	}

	public record EventoRastro(String tecnico, LocalDateTime fecha, String etapa, Double latitud, Double longitud,
		String punto) {
	}

	public record Rastro(List<PuntoRastro> servicios, List<EventoRastro> eventos) {
	}

	private static final String HORIZONTAL = "@page { size: A4 landscape; margin: 10mm; } .mini { width: 70pt; max-height: 60pt; }";

	private final JdbcClient jdbc;
	private final PersonalConsultas consultas;
	private final Almacenamiento almacenamiento;

	public PersonalController(JdbcClient jdbc, PersonalConsultas consultas, Almacenamiento almacenamiento) {
		this.jdbc = jdbc;
		this.consultas = consultas;
		this.almacenamiento = almacenamiento;
	}

	// ================================================================== horas extra
	@GetMapping("/api/personal/horas-extra")
	public List<PersonalConsultas.HoraExtra> horasExtra(@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta,
		@RequestParam(required = false) Integer idTecnico, @RequestParam(required = false) Integer idEstado) {
		return consultas.horasExtra(desde, hasta, idTecnico, idEstado);
	}

	@PutMapping("/api/personal/horas-extra/{id}/estado")
	public void aprobar(@PathVariable int id, @Valid @RequestBody Aprobacion a) {
		if (a.idEstado() == 3 && (a.observacion() == null || a.observacion().isBlank())) {
			throw new NegocioException("Escriba el motivo del rechazo.");
		}
		int n = jdbc.sql("""
			UPDATE registro_horas_extra SET id_estado_aprobacion = :e, observacion_supervisor = :o WHERE id_registro_he = :id""")
			.param("e", a.idEstado()).param("o", a.observacion() == null ? null : a.observacion().trim()).param("id", id).update();
		if (n == 0) {
			throw NegocioException.noEncontrado("El registro");
		}
	}

	/** Borra el registro y sus fotos; los aprobados no se borran (igual que en PHP). */
	@DeleteMapping("/api/personal/horas-extra/{id}")
	@Transactional
	public void eliminarHoraExtra(@PathVariable int id) {
		Integer estado = jdbc.sql("SELECT id_estado_aprobacion FROM registro_horas_extra WHERE id_registro_he = :id")
			.param("id", id).query(Integer.class).optional().orElseThrow(() -> NegocioException.noEncontrado("El registro"));
		if (estado == 2) {
			throw new NegocioException("No se puede borrar: el registro ya está aprobado.");
		}
		List<String> rutas = jdbc.sql("SELECT ruta_archivo FROM evidencia_horas_extra WHERE id_registro_he = :id").param("id", id)
			.query(String.class).list();
		jdbc.sql("DELETE FROM registro_horas_extra WHERE id_registro_he = :id").param("id", id).update();
		rutas.forEach(almacenamiento::eliminar);
	}

	@GetMapping("/api/personal/horas-extra/excel")
	public ResponseEntity<byte[]> horasExtraExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta,
		@RequestParam(required = false) Integer idTecnico, @RequestParam(required = false) Integer idEstado) {
		var lista = consultas.horasExtra(desde, hasta, idTecnico, idEstado);
		var filas = lista.stream().map(h -> (List<?>) Arrays.asList(h.fecha(), h.tecnico(), h.cliente(), h.punto(), h.horaInicio(),
			h.horaFin(), h.totalHoras(), h.justificacion(), h.estado(), h.observacionSupervisor(), h.registrado())).toList();
		// Resumen por técnico: total de horas por estado
		Map<String, BigDecimal[]> resumen = new LinkedHashMap<>();
		for (var h : lista) {
			BigDecimal[] v = resumen.computeIfAbsent(h.tecnico(), k -> new BigDecimal[] { BigDecimal.ZERO, BigDecimal.ZERO, BigDecimal.ZERO });
			v[h.idEstado() - 1] = v[h.idEstado() - 1].add(h.totalHoras() == null ? BigDecimal.ZERO : h.totalHoras());
		}
		var filasResumen = resumen.entrySet().stream().map(e -> (List<?>) Arrays.asList(e.getKey(), e.getValue()[0], e.getValue()[1],
			e.getValue()[2], e.getValue()[0].add(e.getValue()[1]).add(e.getValue()[2]))).toList();
		byte[] xlsx = new Excel()
			.hoja("Horas extra", "Horas extra del " + desde + " al " + hasta, List.of("Fecha", "Técnico", "Cliente", "Punto", "Inicio",
				"Fin", "Horas", "Justificación", "Estado", "Observación del supervisor", "Registrado"), filas)
			.hoja("Resumen", List.of("Técnico", "Pendientes", "Aprobadas", "Rechazadas", "Total"), filasResumen).bytes();
		return Descarga.excel("Horas_extra_" + desde + "_" + hasta, xlsx);
	}

	@GetMapping("/api/personal/horas-extra/pdf")
	public ResponseEntity<byte[]> horasExtraPdf(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta,
		@RequestParam(required = false) Integer idTecnico, @RequestParam(required = false) Integer idEstado) {
		var lista = consultas.horasExtra(desde, hasta, idTecnico, idEstado);
		BigDecimal total = lista.stream().map(h -> h.totalHoras() == null ? BigDecimal.ZERO : h.totalHoras())
			.reduce(BigDecimal.ZERO, BigDecimal::add);
		String cuerpo = Pdf.encabezado("Reporte de horas extra", Html.fecha(desde) + " al " + Html.fecha(hasta) + " · "
			+ lista.size() + " registros · " + total.stripTrailingZeros().toPlainString() + " horas")
			+ Html.tabla(List.of("Fecha", "Técnico", "Cliente / Punto", "Horario", "Horas", "Justificación", "Estado", "Evidencia"), lista,
				h -> List.of(Html.fecha(h.fecha()), esc(h.tecnico()), esc(h.cliente()) + "<br/><span class=\"muted\">" + esc(h.punto()) + "</span>",
					Html.hora(h.horaInicio()) + " – " + Html.hora(h.horaFin()), esc(h.totalHoras()), Html.parrafo(h.justificacion()),
					esc(h.estado()) + (h.observacionSupervisor() == null ? "" : "<br/><span class=\"muted\">" + esc(h.observacionSupervisor()) + "</span>"),
					h.fotos().isEmpty() ? "" : Html.imagen(almacenamiento.bytes(h.fotos().get(0).ruta()), "mini")));
		return Descarga.pdf("Horas_extra_" + desde + "_" + hasta, Pdf.generar(Pdf.documento("Horas extra", HORIZONTAL, cuerpo)));
	}

	// ================================================================== parqueaderos
	@GetMapping("/api/personal/parqueaderos")
	public List<PersonalConsultas.Parqueadero> parqueaderos(@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta,
		@RequestParam(required = false) Integer idTecnico) {
		return consultas.parqueaderos(desde, hasta, idTecnico);
	}

	@GetMapping("/api/personal/parqueaderos/excel")
	public ResponseEntity<byte[]> parqueaderosExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		var filas = consultas.parqueaderos(desde, hasta, idTecnico).stream().map(p -> (List<?>) Arrays.asList(p.fecha(), p.tecnico(),
			p.punto(), p.horaInicio(), p.horaFin(), p.numeroFactura(), new Excel.Pesos(p.valor()), p.registrado())).toList();
		return Descarga.excel("Parqueaderos_" + desde + "_" + hasta, new Excel().hoja("Parqueaderos", "Facturas de parqueadero del "
			+ desde + " al " + hasta, List.of("Fecha", "Técnico", "Punto", "Inicio", "Fin", "N° factura", "Valor", "Registrado"), filas).bytes());
	}

	@GetMapping("/api/personal/parqueaderos/pdf")
	public ResponseEntity<byte[]> parqueaderosPdf(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		var lista = consultas.parqueaderos(desde, hasta, idTecnico);
		BigDecimal total = lista.stream().map(p -> p.valor() == null ? BigDecimal.ZERO : p.valor()).reduce(BigDecimal.ZERO, BigDecimal::add);
		int[] n = { 0 };
		String cuerpo = Pdf.encabezado("Facturas de parqueadero", Html.fecha(desde) + " al " + Html.fecha(hasta) + " · "
			+ lista.size() + " facturas · Total " + Html.pesos(total))
			+ Html.tabla(List.of("N°", "Fecha", "Técnico", "Punto", "Horario", "N° factura", "Valor", "Factura"), lista,
				p -> List.of(String.valueOf(++n[0]), Html.fecha(p.fecha()), esc(p.tecnico()), esc(p.punto()),
					Html.hora(p.horaInicio()) + " – " + Html.hora(p.horaFin()), esc(p.numeroFactura()),
					"<div class=\"der\">" + Html.pesos(p.valor()) + "</div>", Html.imagen(almacenamiento.bytes(p.rutaFoto()), "mini")));
		return Descarga.pdf("Parqueaderos_" + desde + "_" + hasta, Pdf.generar(Pdf.documento("Parqueaderos", HORIZONTAL, cuerpo)));
	}

	// ================================================================== jornadas (turnos)
	@GetMapping("/api/personal/jornadas")
	public List<PersonalConsultas.Jornada> jornadas(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		return consultas.jornadas(desde, hasta, idTecnico);
	}

	@GetMapping("/api/personal/jornadas/excel")
	public ResponseEntity<byte[]> jornadasExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		var filas = consultas.jornadas(desde, hasta, idTecnico).stream().map(j -> (List<?>) Arrays.asList(j.tecnico(), j.codigoRuta(),
			j.fecha(), j.horaEntrada(), j.horaSalida(), j.servicios(), j.novedad(), j.registrado(),
			j.latitud() == null ? null : j.latitud() + "," + j.longitud())).toList();
		return Descarga.excel("Jornadas_" + desde + "_" + hasta, new Excel().hoja("Jornadas", "Entradas de turno del " + desde + " al " + hasta,
			List.of("Técnico", "Código de ruta", "Fecha", "Entrada", "Salida", "Servicios", "Novedad", "Registrado", "Ubicación"), filas).bytes());
	}

	@GetMapping("/api/personal/jornadas/pdf")
	public ResponseEntity<byte[]> jornadasPdf(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		var lista = consultas.jornadas(desde, hasta, idTecnico);
		String cuerpo = Pdf.encabezado("Entradas de turno", Html.fecha(desde) + " al " + Html.fecha(hasta) + " · " + lista.size() + " jornadas")
			+ Html.tabla(List.of("Técnico", "Fecha", "Entrada", "Salida", "Servicios", "Novedad", "Ubicación"), lista,
				j -> List.of(esc(j.tecnico()), Html.fecha(j.fecha()), Html.hora(j.horaEntrada()), Html.hora(j.horaSalida()),
					String.valueOf(j.servicios()), Html.parrafo(j.novedad()),
					j.latitud() == null ? "" : String.format(java.util.Locale.ROOT, "%.5f, %.5f", j.latitud(), j.longitud())));
		return Descarga.pdf("Jornadas_" + desde + "_" + hasta, Pdf.generar(Pdf.documento("Jornadas", null, cuerpo)));
	}

	// ================================================================== tiempos de servicio (antes cronómetro)
	@GetMapping("/api/personal/tiempos")
	public List<PersonalConsultas.Tiempo> tiempos(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		return consultas.tiempos(desde, hasta, idTecnico);
	}

	@GetMapping("/api/personal/tiempos/rendimiento")
	public List<Rendimiento> rendimiento(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		Map<String, List<PersonalConsultas.Tiempo>> porTecnico = new LinkedHashMap<>();
		consultas.tiempos(desde, hasta, null).forEach(t -> porTecnico.computeIfAbsent(t.tecnico(), k -> new ArrayList<>()).add(t));
		Map<String, Integer> ids = new LinkedHashMap<>();
		jdbc.sql("SELECT id_tecnico, nombre_tecnico FROM tecnico").query((rs, i) -> ids.put(rs.getString(2), rs.getInt(1))).list();
		return porTecnico.entrySet().stream().map(e -> {
			Map<String, Integer> porTipo = new LinkedHashMap<>();
			int fin = 0, prog = 0, min = 0, exc = 0;
			for (var t : e.getValue()) {
				if ("Finalizado".equals(t.estado())) {
					fin++;
					porTipo.merge(t.tipoMantenimiento() == null ? "Sin tipo" : t.tipoMantenimiento(), 1, Integer::sum);
					min += t.minutos() == null ? 0 : t.minutos();
					if (t.minutos() != null && t.estimado() != null && t.minutos() > t.estimado()) {
						exc++;
					}
				}
				else {
					prog++;
				}
			}
			return new Rendimiento(ids.getOrDefault(e.getKey(), 0), e.getKey(), e.getValue().size(), fin, prog, porTipo, min, exc);
		}).sorted((a, b) -> b.finalizados() - a.finalizados()).toList();
	}

	@GetMapping("/api/personal/tiempos/excel")
	public ResponseEntity<byte[]> tiemposExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		var filas = consultas.tiempos(desde, hasta, idTecnico).stream().map(t -> (List<?>) Arrays.asList(t.fecha(), t.tecnico(),
			t.cliente(), t.punto(), t.tipoMantenimiento(), t.inicio(), t.fin(), t.minutos(), t.estimado(),
			t.minutos() != null && t.estimado() != null && t.minutos() > t.estimado() ? "Sí" : "No", t.estado(), t.justificacion(),
			t.fuente().equals("SERVICIO") ? "Flujo en campo" : "Cronómetro anterior")).toList();
		return Descarga.excel("Tiempos_servicio_" + desde + "_" + hasta, new Excel().hoja("Tiempos", List.of("Fecha", "Técnico",
			"Cliente", "Punto", "Tipo", "Inicio", "Fin", "Minutos", "Estimado", "Excedido", "Estado", "Justificación", "Fuente"),
			filas).bytes());
	}

	@GetMapping("/api/personal/tiempos/pdf")
	public ResponseEntity<byte[]> tiemposPdf(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idTecnico) {
		var lista = consultas.tiempos(desde, hasta, idTecnico);
		String cuerpo = Pdf.encabezado("Tiempos de servicio", Html.fecha(desde) + " al " + Html.fecha(hasta))
			+ Html.tabla(List.of("Fecha", "Técnico", "Cliente / Punto", "Tipo", "Inicio", "Fin", "Real", "Estimado", "Estado"), lista,
				t -> List.of(Html.fecha(t.fecha()), esc(t.tecnico()), esc(t.cliente()) + "<br/><span class=\"muted\">" + esc(t.punto()) + "</span>",
					esc(t.tipoMantenimiento()), t.inicio() == null ? "" : Html.hora(t.inicio().toLocalTime()),
					t.fin() == null ? "" : Html.hora(t.fin().toLocalTime()), Html.minutos(t.minutos()), Html.minutos(t.estimado()),
					esc(t.estado()) + (t.minutos() != null && t.estimado() != null && t.minutos() > t.estimado()
						? " <b style=\"color:#d2493f\">excedido</b>" : "")));
		return Descarga.pdf("Tiempos_servicio_" + desde + "_" + hasta, Pdf.generar(Pdf.documento("Tiempos", HORIZONTAL, cuerpo)));
	}

	// ================================================================== rastreo
	/** Recorrido de uno o todos los técnicos en una fecha: servicios con GPS de inicio/fin y pasos registrados en campo. */
	@GetMapping("/api/rastreo")
	public Rastro rastreo(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate fecha,
		@RequestParam(required = false) Integer idTecnico) {
		List<PuntoRastro> servicios = jdbc.sql("""
			SELECT o.id_ordenes_servicio AS id_orden, t.nombre_tecnico AS tecnico, c.nombre_cliente AS cliente,
			       p.nombre_punto AS punto, o.hora_entrada, o.hora_salida, comp.latitud_inicio, comp.longitud_inicio,
			       comp.latitud_fin, comp.longitud_fin, o.actividades_realizadas AS observacion
			FROM ordenes_servicio o JOIN ordenes_servicio_complemento comp ON comp.id_orden_servicio = o.id_ordenes_servicio
			JOIN tecnico t ON t.id_tecnico = o.id_tecnico JOIN punto p ON p.id_punto = o.id_punto JOIN cliente c ON c.id_cliente = p.id_cliente
			WHERE o.fecha_visita = :f AND (comp.latitud_inicio IS NOT NULL OR comp.latitud_fin IS NOT NULL)
			  AND (:t IS NULL OR o.id_tecnico = :t)
			ORDER BY t.nombre_tecnico, o.hora_entrada, o.id_ordenes_servicio""")
			.param("f", fecha).param("t", idTecnico).query(PuntoRastro.class).list();
		List<EventoRastro> eventos = jdbc.sql("""
			SELECT t.nombre_tecnico AS tecnico, e.fecha_hora AS fecha, e.etapa, e.latitud, e.longitud, p.nombre_punto AS punto
			FROM orden_servicio_evento e JOIN ordenes_servicio o ON o.id_ordenes_servicio = e.id_orden_servicio
			JOIN tecnico t ON t.id_tecnico = o.id_tecnico JOIN punto p ON p.id_punto = o.id_punto
			WHERE DATE(e.fecha_hora) = :f AND e.latitud IS NOT NULL AND (:t IS NULL OR o.id_tecnico = :t)
			UNION ALL
			SELECT t.nombre_tecnico, TIMESTAMP(tt.fecha, tt.hora_entrada), 'INICIO_JORNADA', tt.latitud, tt.longitud, NULL
			FROM turno_tecnico tt JOIN tecnico t ON t.id_tecnico = tt.id_tecnico
			WHERE tt.fecha = :f AND tt.latitud IS NOT NULL AND (:t IS NULL OR tt.id_tecnico = :t)
			ORDER BY tecnico, fecha""")
			.param("f", fecha).param("t", idTecnico).query(EventoRastro.class).list();
		return new Rastro(servicios, eventos);
	}
}
