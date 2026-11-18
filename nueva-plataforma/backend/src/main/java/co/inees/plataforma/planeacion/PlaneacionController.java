package co.inees.plataforma.planeacion;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.List;

import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.simple.JdbcClient;
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
import co.inees.plataforma.comun.Filas;
import co.inees.plataforma.config.NegocioException;
import jakarta.validation.Valid;
import jakarta.validation.constraints.NotNull;

/** Bolsa de servicios y rutas (Auxiliar, Supervisor y SuperUsuario). */
@RestController
@RequestMapping("/api/planeacion")
public class PlaneacionController {

	public record Movimiento(@NotNull Integer idOrden, Integer idRutaDestino, Integer posicion, String motivo) {
	}

	public record NuevoOrden(@NotNull List<Integer> idsOrden) {
	}

	public record OrdenManual(@NotNull Integer idMaquina, @NotNull Integer idTipoMantenimiento,
		@NotNull @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate fecha, String motivo) {
	}

	public record Motivo(String motivo) {
	}

	private final PlaneacionService servicio;
	private final JdbcClient jdbc;

	public PlaneacionController(PlaneacionService servicio, JdbcClient jdbc) {
		this.servicio = servicio;
		this.jdbc = jdbc;
	}

	/**
	 * Excel maestro de la programación (programacionConsolidado de PHP): todas las paradas de las rutas del rango,
	 * con las columnas del Excel que se descargaba allá más la secuencia, la hora estimada y el estado de la ruta.
	 */
	@GetMapping("/maestro/excel")
	public ResponseEntity<byte[]> maestroExcel(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate desde,
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate hasta, @RequestParam(required = false) Integer idDelegacion,
		@RequestParam(required = false) Integer idTecnico) {
		if (hasta.isBefore(desde) || desde.plusDays(62).isBefore(hasta)) {
			throw new NegocioException("Elija un rango de hasta 62 días.");
		}
		List<List<Object>> filas = jdbc.sql("""
			SELECT r.fecha, COALESCE(t.codigo_ruta, 'Sin ruta'), t.nombre_tecnico, rp.secuencia, rp.hora_estimada,
			       c.codigo_cliente, c.nombre_cliente, p.nombre_punto, p.direccion, p.codigo_1, mu.nombre_municipio, p.zona,
			       d.nombre_delegacion, m.device_id, tm.nombre_completo, IF(m.activo_operativo = 0, 'SI', ''),
			       COALESCE(m.ultima_visita, DATE(p.fecha_ultima_visita)),
			       CASE r.estado WHEN 'BORRADOR' THEN 'Borrador' WHEN 'PUBLICADA' THEN 'Publicada' WHEN 'EN_CURSO' THEN 'En curso'
			                     ELSE 'Cerrada' END,
			       CASE rp.estado WHEN 'PENDIENTE' THEN 'Pendiente' WHEN 'HECHA' THEN 'Realizado' WHEN 'NO_INGRESO' THEN 'Sin ingreso'
			                      WHEN 'NO_ALCANZADA' THEN 'No alcanzada' ELSE 'Retirada' END,
			       o.id_ordenes_servicio
			FROM ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta JOIN tecnico t ON t.id_tecnico = r.id_tecnico
			JOIN ordenes_servicio o ON o.id_ordenes_servicio = rp.id_orden_servicio JOIN punto p ON p.id_punto = o.id_punto
			LEFT JOIN cliente c ON c.id_cliente = COALESCE(o.id_cliente, p.id_cliente) LEFT JOIN maquina m ON m.id_maquina = o.id_maquina
			LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE r.fecha BETWEEN :d AND :h AND rp.estado <> 'RETIRADA'
			  AND (:del IS NULL OR t.id_delegacion = :del) AND (:tec IS NULL OR r.id_tecnico = :tec)
			ORDER BY r.fecha, t.codigo_ruta, t.nombre_tecnico, rp.secuencia""")
			.param("d", desde).param("h", hasta).param("del", idDelegacion).param("tec", idTecnico)
			.query((rs, i) -> {
				List<Object> f = new ArrayList<>();
				for (int c = 1; c <= 20; c++) {
					f.add(Filas.valor(rs.getObject(c)));
				}
				return f;
			}).list();
		if (filas.isEmpty()) {
			throw new NegocioException("No hay rutas programadas en ese rango.");
		}
		byte[] x = new Excel().hoja("Maestro Rutas", "Programación del " + desde + " al " + hasta,
			List.of("Fecha visita", "Ruta", "Técnico asignado", "Orden en la ruta", "Hora estimada", "Código cliente", "Cliente",
				"Nombre del punto", "Dirección", "COD_1", "Municipio", "Zona", "Delegación", "ID dispositivo (device)",
				"Tipo de servicio", "Aún fuera de servicio", "Fecha última visita", "Estado de la ruta", "Estado del servicio",
				"N.º de servicio"),
			filas).bytes();
		return Descarga.excel("Maestro_Programacion_" + desde + "_" + hasta, x);
	}

	@GetMapping
	public PlaneacionService.Plan plan(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate fecha,
		@RequestParam int idDelegacion) {
		return servicio.plan(fecha, idDelegacion);
	}

	@PostMapping("/generar")
	public PlaneacionService.ResultadoGeneracion generar(
		@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate fecha, @RequestParam int idDelegacion) {
		return servicio.generar(fecha, idDelegacion);
	}

	@PostMapping("/publicar")
	public PlaneacionService.Plan publicar(@RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate fecha,
		@RequestParam int idDelegacion) {
		return servicio.publicar(fecha, idDelegacion);
	}

	@GetMapping("/rutas/{id}")
	public PlaneacionService.Ruta ruta(@PathVariable int id) {
		return servicio.ruta(id);
	}

	@PostMapping("/rutas/{id}/publicar")
	public PlaneacionService.Ruta publicarRuta(@PathVariable int id) {
		return servicio.publicarRuta(id);
	}

	@PutMapping("/rutas/{id}/orden")
	public PlaneacionService.Ruta reordenar(@PathVariable int id, @Valid @RequestBody NuevoOrden cuerpo) {
		return servicio.reordenar(id, cuerpo.idsOrden());
	}

	@PostMapping("/rutas/{id}/recalcular")
	public PlaneacionService.Ruta recalcular(@PathVariable int id) {
		return servicio.recalcular(id);
	}

	@PutMapping("/paradas/{id}/fijada")
	public PlaneacionService.Ruta fijar(@PathVariable int id, @RequestParam boolean valor) {
		return servicio.fijar(id, valor);
	}

	@PostMapping("/mover")
	public void mover(@Valid @RequestBody Movimiento m) {
		servicio.mover(m.idOrden(), m.idRutaDestino(), m.posicion(), m.motivo());
	}

	@PostMapping("/ordenes")
	public PlaneacionService.Orden crear(@Valid @RequestBody OrdenManual o) {
		return servicio.crearManual(o.idMaquina(), o.idTipoMantenimiento(), o.fecha(), o.motivo());
	}

	@PostMapping("/ordenes/{id}/cancelar")
	public void cancelar(@PathVariable int id, @RequestBody Motivo m) {
		servicio.cancelar(id, m.motivo());
	}
}
