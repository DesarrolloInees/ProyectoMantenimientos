package co.inees.plataforma.inventario;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.List;

import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.comun.Descarga;
import co.inees.plataforma.comun.Excel;
import co.inees.plataforma.comun.Filtro;
import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.seguridad.Sesion;
import jakarta.validation.Valid;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotEmpty;
import jakarta.validation.constraints.NotNull;

/** Stock de los motorizados, kardex, solicitudes de repuestos y devoluciones (permiso INVENTARIO). */
@RestController
@RequestMapping("/api/inventario")
public class InventarioController {

	public record StockFila(int idTecnico, String tecnico, int idRepuesto, String repuesto, String codigo, int cantidad,
		LocalDateTime ultimaActualizacion) {
	}

	public record Item(@NotNull Integer idRepuesto, @Min(1) int cantidad) {
	}

	public record Asignacion(@NotNull Integer idTecnico, @NotEmpty List<@Valid Item> items, String observacion) {
	}

	public record Ajuste(@NotNull Integer idTecnico, @NotNull Integer idRepuesto, @Min(0) int cantidad, String observacion) {
	}

	public record Movimiento(int idMovimiento, LocalDateTime fecha, String tecnico, String repuesto, String tipo, int cantidad,
		Integer idOrden, String remision, String usuario, String observacion) {
	}

	public record Solicitud(int idSolicitud, String solicitante, LocalDateTime fecha, String estado, String observaciones,
		LocalDateTime actualizada, String detalle) {
	}

	public record Devolucion(int idDevolucion, int idOrden, LocalDate fechaVisita, String remision, String tecnico,
		String repuesto, String codigo, int cantidad, String estado, LocalDateTime fechaDevolucion, String cliente,
		String punto, String delegacion) {
	}

	public record CambioEstado(@NotNull String estado) {
	}

	private final JdbcClient jdbc;
	private final Kardex kardex;

	public InventarioController(JdbcClient jdbc, Kardex kardex) {
		this.jdbc = jdbc;
		this.kardex = kardex;
	}

	// ------------------------------------------------------------------ stock
	@GetMapping("/stock")
	public List<StockFila> stock(@RequestParam(required = false) Integer idTecnico, @RequestParam(required = false) String texto,
		@RequestParam(defaultValue = "false") boolean conCeros) {
		Filtro f = new Filtro().y("i.estado = 1").y("i.id_tecnico = :t", "t", idTecnico)
			.texto("q", texto, "r.nombre_repuesto", "r.codigo_referencia", "t.nombre_tecnico");
		if (!conCeros) {
			f.y("i.cantidad_actual <> 0");
		}
		return jdbc.sql("""
			SELECT i.id_tecnico, t.nombre_tecnico AS tecnico, i.id_repuesto, r.nombre_repuesto AS repuesto,
			       r.codigo_referencia AS codigo, i.cantidad_actual AS cantidad, i.ultima_actualizacion
			FROM inventario_tecnico i JOIN tecnico t ON t.id_tecnico = i.id_tecnico JOIN repuesto r ON r.id_repuesto = i.id_repuesto
			WHERE """ + " " + f.where() + " ORDER BY t.nombre_tecnico, r.nombre_repuesto").params(f.params())
			.query(StockFila.class).list();
	}

	/** Entrega de repuestos a un técnico (suma al stock). */
	@PostMapping("/asignar")
	@Transactional
	public List<StockFila> asignar(@Valid @RequestBody Asignacion a) {
		int usuario = Sesion.actual().usuarioId();
		for (Item i : a.items()) {
			kardex.mover(a.idTecnico(), i.idRepuesto(), Kardex.Tipo.ASIGNACION, i.cantidad(), null, usuario,
				a.observacion() == null || a.observacion().isBlank() ? "Entrega de repuestos" : a.observacion().trim());
		}
		return stock(a.idTecnico(), null, false);
	}

	/** Corrige la cantidad exacta (conteo físico); la diferencia queda como ajuste en el kardex. */
	@PutMapping("/stock")
	@Transactional
	public List<StockFila> ajustar(@Valid @RequestBody Ajuste a) {
		kardex.fijar(a.idTecnico(), a.idRepuesto(), a.cantidad(), Sesion.actual().usuarioId(), a.observacion());
		return stock(a.idTecnico(), null, false);
	}

	/** Retira el repuesto del inventario del técnico (lo deja en cero con su ajuste). */
	@DeleteMapping("/stock/{idTecnico}/{idRepuesto}")
	@Transactional
	public void retirar(@PathVariable int idTecnico, @PathVariable int idRepuesto, @RequestParam(required = false) String motivo) {
		kardex.fijar(idTecnico, idRepuesto, 0, Sesion.actual().usuarioId(),
			motivo == null || motivo.isBlank() ? "Retirado del inventario del técnico" : motivo);
		jdbc.sql("UPDATE inventario_tecnico SET estado = 0 WHERE id_tecnico = :t AND id_repuesto = :r")
			.param("t", idTecnico).param("r", idRepuesto).update();
	}

	@GetMapping("/kardex")
	public List<Movimiento> movimientos(@RequestParam(required = false) Integer idTecnico,
		@RequestParam(required = false) Integer idRepuesto,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		Filtro f = new Filtro().y("m.id_tecnico = :t", "t", idTecnico).y("m.id_repuesto = :r", "r", idRepuesto)
			.y("m.fecha >= :d", "d", desde).y("m.fecha < DATE_ADD(:h, INTERVAL 1 DAY)", "h", hasta);
		return jdbc.sql("""
			SELECT m.id_movimiento, m.fecha, t.nombre_tecnico AS tecnico, r.nombre_repuesto AS repuesto, m.tipo, m.cantidad,
			       m.id_orden_servicio AS id_orden, o.numero_remision AS remision, u.nombre AS usuario, m.observacion
			FROM movimiento_inventario m JOIN tecnico t ON t.id_tecnico = m.id_tecnico JOIN repuesto r ON r.id_repuesto = m.id_repuesto
			LEFT JOIN ordenes_servicio o ON o.id_ordenes_servicio = m.id_orden_servicio
			LEFT JOIN usuarios u ON u.usuario_id = m.id_usuario
			WHERE """ + " " + f.where() + " ORDER BY m.fecha DESC, m.id_movimiento DESC LIMIT 2000").params(f.params())
			.query(Movimiento.class).list();
	}

	@GetMapping("/stock/excel")
	public ResponseEntity<byte[]> stockExcel(@RequestParam(required = false) Integer idTecnico) {
		var filas = stock(idTecnico, null, false).stream()
			.map(s -> (List<?>) java.util.Arrays.asList(s.tecnico(), s.repuesto(), s.codigo(), s.cantidad(), s.ultimaActualizacion()))
			.toList();
		return Descarga.excel("Inventario_tecnicos_" + LocalDate.now(),
			new Excel().hoja("Inventario", List.of("Técnico", "Repuesto", "Código", "Cantidad", "Última actualización"), filas).bytes());
	}

	// ------------------------------------------------------------------ solicitudes de los técnicos
	@GetMapping("/solicitudes")
	public List<Solicitud> solicitudes(@RequestParam(required = false) String estado) {
		return jdbc.sql("""
			SELECT s.id_solicitud, s.solicitante_nombre AS solicitante, s.fecha_solicitud AS fecha, s.estado, s.observaciones,
			       s.fecha_actualizacion AS actualizada,
			       (SELECT GROUP_CONCAT(CONCAT(d.cantidad, ' × ', r.nombre_repuesto) ORDER BY r.nombre_repuesto SEPARATOR '\\n')
			          FROM solicitud_inventario_detalle d JOIN repuesto r ON r.id_repuesto = d.id_repuesto
			         WHERE d.id_solicitud = s.id_solicitud) AS detalle
			FROM solicitudes_inventario s WHERE (:e IS NULL OR s.estado = :e)
			ORDER BY FIELD(s.estado, 'Pendiente', 'En gestión', 'Finalizado'), s.fecha_solicitud DESC""")
			.param("e", estado == null || estado.isBlank() ? null : estado).query(Solicitud.class).list();
	}

	@PutMapping("/solicitudes/{id}/estado")
	public void cambiarEstadoSolicitud(@PathVariable int id, @Valid @RequestBody CambioEstado c) {
		if (!List.of("Pendiente", "En gestión", "Finalizado").contains(c.estado())) {
			throw new NegocioException("Estado no válido.");
		}
		jdbc.sql("UPDATE solicitudes_inventario SET estado = :e WHERE id_solicitud = :id").param("e", c.estado())
			.param("id", id).update();
	}

	// ------------------------------------------------------------------ devoluciones de piezas cambiadas
	@GetMapping("/devoluciones")
	public List<Devolucion> devoluciones(@RequestParam(required = false) Integer idTecnico,
		@RequestParam(defaultValue = "Pendiente") String estado,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		Filtro f = new Filtro().y("d.id_tecnico = :t", "t", idTecnico)
			.y("d.estado_devolucion = :e", "e", "Todas".equalsIgnoreCase(estado) ? null : estado)
			.y("o.fecha_visita >= :desde", "desde", desde).y("o.fecha_visita <= :hasta", "hasta", hasta);
		return jdbc.sql("""
			SELECT d.id_devolucion, d.id_orden_servicio AS id_orden, o.fecha_visita, o.numero_remision AS remision,
			       t.nombre_tecnico AS tecnico, r.nombre_repuesto AS repuesto, r.codigo_referencia AS codigo, d.cantidad,
			       d.estado_devolucion AS estado, d.fecha_devolucion, c.nombre_cliente AS cliente, p.nombre_punto AS punto,
			       de.nombre_delegacion AS delegacion
			FROM control_devolucion_repuestos d
			JOIN repuesto r ON r.id_repuesto = d.id_repuesto JOIN tecnico t ON t.id_tecnico = d.id_tecnico
			JOIN ordenes_servicio o ON o.id_ordenes_servicio = d.id_orden_servicio JOIN punto p ON p.id_punto = o.id_punto
			JOIN cliente c ON c.id_cliente = p.id_cliente LEFT JOIN delegacion de ON de.id_delegacion = p.id_delegacion
			WHERE """ + " " + f.where() + " ORDER BY o.fecha_visita, t.nombre_tecnico").params(f.params())
			.query(Devolucion.class).list();
	}

	@PostMapping("/devoluciones/{id}/devuelto")
	public void marcarDevuelto(@PathVariable int id) {
		int n = jdbc.sql("""
			UPDATE control_devolucion_repuestos SET estado_devolucion = 'Devuelto', fecha_devolucion = NOW()
			WHERE id_devolucion = :id AND estado_devolucion = 'Pendiente'""").param("id", id).update();
		if (n == 0) {
			throw new NegocioException("La devolución no existe o ya estaba marcada como devuelta.");
		}
	}

	@GetMapping("/devoluciones/excel")
	public ResponseEntity<byte[]> devolucionesExcel(@RequestParam(required = false) Integer idTecnico,
		@RequestParam(defaultValue = "Todas") String estado,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta) {
		var filas = devoluciones(idTecnico, estado, desde, hasta).stream()
			.map(d -> (List<?>) java.util.Arrays.asList(d.fechaVisita(), d.remision(), d.tecnico(), d.repuesto(), d.codigo(),
				d.cantidad(), d.estado(), d.fechaDevolucion(), d.cliente(), d.punto(), d.delegacion()))
			.toList();
		return Descarga.excel("Devoluciones_" + LocalDate.now(), new Excel().hoja("Devoluciones", List.of("Fecha servicio",
			"Remisión", "Técnico", "Repuesto", "Código", "Cantidad", "Estado", "Devuelto el", "Cliente", "Punto", "Delegación"),
			filas).bytes());
	}
}
