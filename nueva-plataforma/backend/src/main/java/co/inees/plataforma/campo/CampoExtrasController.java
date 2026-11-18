package co.inees.plataforma.campo;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.time.Duration;
import java.time.LocalDate;
import java.time.LocalTime;
import java.util.List;

import org.springframework.http.MediaType;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RequestPart;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.comun.Almacenamiento;
import co.inees.plataforma.comun.Historial;
import co.inees.plataforma.comun.Modalidad;
import co.inees.plataforma.comun.Notificador;
import co.inees.plataforma.comun.Parametros;
import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.inventario.InventarioController;
import co.inees.plataforma.personal.PersonalConsultas;
import co.inees.plataforma.seguridad.Sesion;
import jakarta.validation.Valid;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotEmpty;
import jakarta.validation.constraints.NotNull;

/**
 * Lo demás que el motorizado hace desde el celular: horas extra, parqueaderos, solicitud de repuestos, su
 * inventario, historial de un punto, sus tiempos y servicios extra (permiso CAMPO).
 */
@RestController
@RequestMapping("/api/campo")
public class CampoExtrasController {

	public record Historico(int idOrden, LocalDate fecha, String tecnico, String tipoServicio, String actividades,
		String repuestos) {
	}

	public record ItemSolicitud(@NotNull Integer idRepuesto, @Min(1) int cantidad) {
	}

	public record NuevaSolicitud(@NotEmpty List<@Valid ItemSolicitud> items, String observaciones) {
	}

	public record ServicioExtra(@NotNull Integer idMaquina, @NotNull Integer idTipoMantenimiento, String motivo) {
	}

	private final JdbcClient jdbc;
	private final Almacenamiento almacenamiento;
	private final PersonalConsultas personal;
	private final InventarioController inventario;
	private final Notificador notificador;
	private final Parametros parametros;
	private final Historial historial;
	private final Modalidad modalidad;

	public CampoExtrasController(JdbcClient jdbc, Almacenamiento almacenamiento, PersonalConsultas personal,
		InventarioController inventario, Notificador notificador, Parametros parametros, Historial historial, Modalidad modalidad) {
		this.jdbc = jdbc;
		this.almacenamiento = almacenamiento;
		this.personal = personal;
		this.inventario = inventario;
		this.notificador = notificador;
		this.parametros = parametros;
		this.historial = historial;
		this.modalidad = modalidad;
	}

	// ================================================================== horas extra
	@GetMapping("/horas-extra")
	public List<PersonalConsultas.HoraExtra> misHorasExtra() {
		return personal.horasExtra(null, null, Sesion.idTecnico(), null);
	}

	@PostMapping(value = "/horas-extra", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	@Transactional
	public PersonalConsultas.HoraExtra registrarHoraExtra(@RequestParam LocalDate fecha, @RequestParam LocalTime horaInicio,
		@RequestParam LocalTime horaFin, @RequestParam String justificacion, @RequestParam(required = false) Integer idCliente,
		@RequestParam(required = false) Integer idPunto, @RequestPart(name = "fotos", required = false) List<MultipartFile> fotos) {
		int tecnico = Sesion.idTecnico();
		validarHoraExtra(fecha, justificacion);
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO registro_horas_extra (id_tecnico, id_cliente, id_punto, fecha_reporte, hora_inicio, hora_fin, total_horas,
			       justificacion_tecnico, id_estado_aprobacion)
			VALUES (:t, :c, :p, :f, :i, :fin, :total, :j, 1)""")
			.param("t", tecnico).param("c", idCliente).param("p", idPunto).param("f", fecha).param("i", horaInicio)
			.param("fin", horaFin).param("total", horas(horaInicio, horaFin)).param("j", justificacion.trim()).update(llave);
		int id = llave.getKey().intValue();
		guardarFotosHoraExtra(id, fotos);
		return unaHoraExtra(id, tecnico);
	}

	/** Corrige un registro (no aprobado); vuelve a quedar pendiente de revisión. */
	@PostMapping(value = "/horas-extra/{id}", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	@Transactional
	public PersonalConsultas.HoraExtra corregirHoraExtra(@PathVariable int id, @RequestParam LocalDate fecha,
		@RequestParam LocalTime horaInicio, @RequestParam LocalTime horaFin, @RequestParam String justificacion,
		@RequestParam(required = false) Integer idCliente, @RequestParam(required = false) Integer idPunto,
		@RequestPart(name = "fotos", required = false) List<MultipartFile> fotos) {
		int tecnico = Sesion.idTecnico();
		exigirHoraExtraEditable(id, tecnico);
		validarHoraExtra(fecha, justificacion);
		jdbc.sql("""
			UPDATE registro_horas_extra SET fecha_reporte = :f, hora_inicio = :i, hora_fin = :fin, total_horas = :total,
			       justificacion_tecnico = :j, id_cliente = :c, id_punto = :p, id_estado_aprobacion = 1, observacion_supervisor = NULL
			WHERE id_registro_he = :id AND id_tecnico = :t""")
			.param("f", fecha).param("i", horaInicio).param("fin", horaFin).param("total", horas(horaInicio, horaFin))
			.param("j", justificacion.trim()).param("c", idCliente).param("p", idPunto).param("id", id).param("t", tecnico).update();
		guardarFotosHoraExtra(id, fotos);
		return unaHoraExtra(id, tecnico);
	}

	@DeleteMapping("/horas-extra/{id}/fotos/{idFoto}")
	public void eliminarFotoHoraExtra(@PathVariable int id, @PathVariable int idFoto) {
		exigirHoraExtraEditable(id, Sesion.idTecnico());
		String ruta = jdbc.sql("SELECT ruta_archivo FROM evidencia_horas_extra WHERE id_evidencia = :e AND id_registro_he = :r")
			.param("e", idFoto).param("r", id).query(String.class).optional().orElseThrow(() -> NegocioException.noEncontrado("La foto"));
		jdbc.sql("DELETE FROM evidencia_horas_extra WHERE id_evidencia = :e").param("e", idFoto).update();
		almacenamiento.eliminar(ruta);
	}

	private void exigirHoraExtraEditable(int id, int tecnico) {
		Integer estado = jdbc.sql("SELECT id_estado_aprobacion FROM registro_horas_extra WHERE id_registro_he = :id AND id_tecnico = :t")
			.param("id", id).param("t", tecnico).query(Integer.class).optional()
			.orElseThrow(() -> new NegocioException("El registro no existe o no le pertenece."));
		if (estado == 2) {
			throw new NegocioException("No puede modificar un registro que ya fue aprobado.");
		}
	}

	private static void validarHoraExtra(LocalDate fecha, String justificacion) {
		if (fecha.isAfter(LocalDate.now())) {
			throw new NegocioException("La fecha no puede ser futura.");
		}
		if (justificacion == null || justificacion.trim().length() < 10) {
			throw new NegocioException("Explique por qué trabajó horas extra (mínimo 10 caracteres).");
		}
	}

	private void guardarFotosHoraExtra(int id, List<MultipartFile> fotos) {
		if (fotos == null) {
			return;
		}
		for (MultipartFile f : fotos) {
			if (f != null && !f.isEmpty()) {
				String ruta = almacenamiento.guardarImagenEn("horas_extra", "he_" + id, f);
				jdbc.sql("INSERT INTO evidencia_horas_extra (id_registro_he, ruta_archivo) VALUES (:r, :ruta)")
					.param("r", id).param("ruta", ruta).update();
			}
		}
	}

	private PersonalConsultas.HoraExtra unaHoraExtra(int id, int tecnico) {
		return personal.horasExtra(null, null, tecnico, null).stream().filter(h -> h.id() == id).findFirst()
			.orElseThrow(() -> NegocioException.noEncontrado("El registro"));
	}

	static BigDecimal horas(LocalTime inicio, LocalTime fin) {
		long min = Duration.between(inicio, fin).toMinutes();
		if (min <= 0) {
			min += 24 * 60;
		}
		return BigDecimal.valueOf(min).divide(BigDecimal.valueOf(60), 2, RoundingMode.HALF_UP);
	}

	// ================================================================== parqueaderos
	@GetMapping("/parqueaderos")
	public List<PersonalConsultas.Parqueadero> misParqueaderos() {
		return personal.parqueaderos(null, null, Sesion.idTecnico());
	}

	@PostMapping(value = "/parqueaderos", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	public PersonalConsultas.Parqueadero registrarParqueadero(@RequestParam int idPunto, @RequestParam LocalDate fecha,
		@RequestParam LocalTime horaInicio, @RequestParam LocalTime horaFin, @RequestParam String numeroFactura,
		@RequestParam BigDecimal valor, @RequestPart("foto") MultipartFile foto) {
		int tecnico = Sesion.idTecnico();
		validarParqueadero(fecha, horaInicio, horaFin, numeroFactura, valor);
		String ruta = almacenamiento.guardarImagenEn("parqueaderos", "factura_" + tecnico, foto);
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO facturas_parqueadero (id_tecnico, id_punto, fecha_servicio, hora_inicio, hora_fin, numero_factura,
			       valor_factura, ruta_foto)
			VALUES (:t, :p, :f, :i, :fin, :n, :v, :r)""")
			.param("t", tecnico).param("p", idPunto).param("f", fecha).param("i", horaInicio).param("fin", horaFin)
			.param("n", numeroFactura.trim()).param("v", valor).param("r", ruta).update(llave);
		return unParqueadero(llave.getKey().intValue(), tecnico);
	}

	@PostMapping(value = "/parqueaderos/{id}", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	public PersonalConsultas.Parqueadero corregirParqueadero(@PathVariable int id, @RequestParam int idPunto,
		@RequestParam LocalDate fecha, @RequestParam LocalTime horaInicio, @RequestParam LocalTime horaFin,
		@RequestParam String numeroFactura, @RequestParam BigDecimal valor,
		@RequestPart(name = "foto", required = false) MultipartFile foto) {
		int tecnico = Sesion.idTecnico();
		PersonalConsultas.Parqueadero actual = unParqueadero(id, tecnico);
		validarParqueadero(fecha, horaInicio, horaFin, numeroFactura, valor);
		String ruta = actual.rutaFoto();
		if (foto != null && !foto.isEmpty()) {
			ruta = almacenamiento.guardarImagenEn("parqueaderos", "factura_" + tecnico, foto);
			almacenamiento.eliminar(actual.rutaFoto());
		}
		jdbc.sql("""
			UPDATE facturas_parqueadero SET id_punto = :p, fecha_servicio = :f, hora_inicio = :i, hora_fin = :fin,
			       numero_factura = :n, valor_factura = :v, ruta_foto = :r
			WHERE id_factura_parqueadero = :id AND id_tecnico = :t""")
			.param("p", idPunto).param("f", fecha).param("i", horaInicio).param("fin", horaFin).param("n", numeroFactura.trim())
			.param("v", valor).param("r", ruta == null ? "" : ruta).param("id", id).param("t", tecnico).update();
		return unParqueadero(id, tecnico);
	}

	private static void validarParqueadero(LocalDate fecha, LocalTime inicio, LocalTime fin, String factura, BigDecimal valor) {
		if (fecha.isAfter(LocalDate.now())) {
			throw new NegocioException("La fecha no puede ser futura.");
		}
		if (!fin.isAfter(inicio)) {
			throw new NegocioException("La hora de salida debe ser posterior a la de entrada.");
		}
		if (factura == null || factura.isBlank()) {
			throw new NegocioException("Escriba el número de la factura.");
		}
		if (valor == null || valor.signum() <= 0) {
			throw new NegocioException("El valor debe ser mayor que cero.");
		}
	}

	private PersonalConsultas.Parqueadero unParqueadero(int id, int tecnico) {
		return personal.parqueaderos(null, null, tecnico).stream().filter(p -> p.id() == id).findFirst()
			.orElseThrow(() -> new NegocioException("La factura no existe o no le pertenece."));
	}

	// ================================================================== inventario propio y solicitudes
	@GetMapping("/inventario")
	public List<InventarioController.StockFila> miInventario() {
		return inventario.stock(Sesion.idTecnico(), null, false);
	}

	@GetMapping("/inventario/movimientos")
	public List<InventarioController.Movimiento> misMovimientos() {
		return inventario.movimientos(Sesion.idTecnico(), null, LocalDate.now().minusDays(90), null);
	}

	@GetMapping("/solicitudes")
	public List<InventarioController.Solicitud> misSolicitudes() {
		int usuario = Sesion.actual().usuarioId();
		return inventario.solicitudes(null).stream()
			.filter(s -> jdbc.sql("SELECT COUNT(*) FROM solicitudes_inventario WHERE id_solicitud = :s AND id_usuario = :u")
				.param("s", s.idSolicitud()).param("u", usuario).query(Integer.class).single() > 0)
			.toList();
	}

	/** Pide repuestos al almacén; se avisa por correo como en PHP (si el correo está configurado). */
	@PostMapping("/solicitudes")
	@Transactional
	public int solicitar(@Valid @RequestBody NuevaSolicitud s) {
		var u = Sesion.actual();
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO solicitudes_inventario (id_usuario, solicitante_nombre, estado, observaciones)
			VALUES (:u, :n, 'Pendiente', :o)""")
			.param("u", u.usuarioId()).param("n", u.nombre()).param("o", s.observaciones() == null ? null : s.observaciones().trim())
			.update(llave);
		int id = llave.getKey().intValue();
		StringBuilder detalle = new StringBuilder("<ul>");
		for (ItemSolicitud i : s.items()) {
			jdbc.sql("INSERT INTO solicitud_inventario_detalle (id_solicitud, id_repuesto, cantidad) VALUES (:s, :r, :c)")
				.param("s", id).param("r", i.idRepuesto()).param("c", i.cantidad()).update();
			String nombre = jdbc.sql("SELECT nombre_repuesto FROM repuesto WHERE id_repuesto = :r").param("r", i.idRepuesto())
				.query(String.class).optional().orElse("Repuesto " + i.idRepuesto());
			detalle.append("<li>").append(i.cantidad()).append(" × ").append(co.inees.plataforma.comun.Html.esc(nombre)).append("</li>");
		}
		detalle.append("</ul>");
		if (notificador.correoConfigurado()) {
			try {
				notificador.correo(parametros.lista("correos_solicitud_inventario",
					"almacen@inees.co,administrativo@inees.co,operaciones@inees.co,auxadministrativo@inees.co"),
					"Nueva solicitud de inventario #" + id + " - " + u.nombre(),
					"<h3>Solicitud de repuestos #" + id + "</h3><p><b>Técnico:</b> " + co.inees.plataforma.comun.Html.esc(u.nombre())
						+ "</p>" + detalle + (s.observaciones() == null ? "" : "<p><b>Observaciones:</b> "
							+ co.inees.plataforma.comun.Html.esc(s.observaciones()) + "</p>"));
			}
			catch (NegocioException e) {
				// la solicitud queda registrada aunque el correo falle
			}
		}
		return id;
	}

	// ================================================================== historial de un punto y tiempos propios
	/** Últimos 20 servicios del punto (sin fallidos ni garantías), para saber qué se le hizo antes. */
	@GetMapping("/historial")
	public List<Historico> historial(@RequestParam int idPunto) {
		return jdbc.sql("""
			SELECT o.id_ordenes_servicio AS id_orden, o.fecha_visita AS fecha, t.nombre_tecnico AS tecnico,
			       tm.nombre_completo AS tipo_servicio, o.actividades_realizadas AS actividades,
			       (SELECT GROUP_CONCAT(CONCAT(r.nombre_repuesto, ' (x', osr.cantidad, ')') SEPARATOR ', ')
			          FROM orden_servicio_repuesto osr JOIN repuesto r ON r.id_repuesto = osr.id_repuesto
			         WHERE osr.id_orden_servicio = o.id_ordenes_servicio) AS repuestos
			FROM ordenes_servicio o LEFT JOIN tecnico t ON t.id_tecnico = o.id_tecnico
			LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.id_punto = :p AND o.estado = 1 AND o.id_tipo_mantenimiento NOT IN (4, 5)
			ORDER BY o.fecha_visita DESC LIMIT 20""").param("p", idPunto).query(Historico.class).list();
	}

	@GetMapping("/tiempos")
	public List<PersonalConsultas.Tiempo> misTiempos() {
		return personal.tiempos(LocalDate.now().minusDays(60), LocalDate.now(), Sesion.idTecnico());
	}

	// ================================================================== servicio extra
	/**
	 * El técnico agrega un servicio que no estaba en su ruta (p. ej. el cliente lo pide en el punto). Queda al final de
	 * su ruta de hoy y el Supervisor lo ve en el historial del servicio.
	 */
	@PostMapping("/servicios-extra")
	@Transactional
	public int servicioExtra(@Valid @RequestBody ServicioExtra s) {
		var u = Sesion.actual();
		int tecnico = Sesion.idTecnico();
		LocalDate hoy = LocalDate.now();
		record Maq(int idPunto, int idCliente, int idModalidad) {
		}
		Maq m = jdbc.sql("""
			SELECT p.id_punto, p.id_cliente, p.id_modalidad FROM maquina mq JOIN punto p ON p.id_punto = mq.id_punto
			WHERE mq.id_maquina = :m AND mq.estado = 1""").param("m", s.idMaquina()).query(Maq.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("La máquina"));
		Integer idRuta = jdbc.sql("""
			SELECT id_ruta FROM ruta_diaria WHERE id_tecnico = :t AND fecha = :hoy AND estado IN ('PUBLICADA','EN_CURSO')""")
			.param("t", tecnico).param("hoy", hoy).query(Integer.class).optional()
			.orElseThrow(() -> new NegocioException("No tiene una ruta publicada hoy; pídale el servicio al Supervisor."));
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO ordenes_servicio (id_cliente, id_punto, id_modalidad, id_maquina, id_tecnico, id_tipo_mantenimiento,
			       fecha_visita, estado, origen, prioridad)
			VALUES (:c, :p, :mod, :m, :t, :tipo, :hoy, 2, 'MANUAL', 4)""")
			.param("c", m.idCliente()).param("p", m.idPunto()).param("mod", modalidad.decidir(m.idPunto(), tecnico)).param("m", s.idMaquina())
			.param("t", tecnico).param("tipo", s.idTipoMantenimiento()).param("hoy", hoy).update(llave);
		int id = llave.getKey().intValue();
		jdbc.sql("""
			INSERT INTO ruta_parada (id_ruta, id_orden_servicio, secuencia)
			SELECT :r, :o, COALESCE(MAX(secuencia), 0) + 1 FROM ruta_parada WHERE id_ruta = :r""")
			.param("r", idRuta).param("o", id).update();
		historial.registrar(id, "estado", null, "PROGRAMADA", "Servicio extra agregado por el técnico"
			+ (s.motivo() == null || s.motivo().isBlank() ? "" : ": " + s.motivo().trim()), u.usuarioId());
		return id;
	}

	/** Quita un servicio extra que el técnico agregó por error (solo si no lo ha empezado). */
	@DeleteMapping("/servicios-extra/{id}")
	@Transactional
	public void quitarServicioExtra(@PathVariable int id) {
		int tecnico = Sesion.idTecnico();
		int n = jdbc.sql("""
			DELETE FROM ordenes_servicio WHERE id_ordenes_servicio = :o AND id_tecnico = :t AND estado = 2 AND origen = 'MANUAL'
			   AND etapa_actual IS NULL""").param("o", id).param("t", tecnico).update();
		if (n == 0) {
			throw new NegocioException("Solo puede quitar servicios extra suyos que no haya empezado.");
		}
	}
}
