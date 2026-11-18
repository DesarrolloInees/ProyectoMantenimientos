package co.inees.plataforma.remision;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.comun.Filtro;
import co.inees.plataforma.config.NegocioException;
import jakarta.validation.Valid;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;

/** Talonarios de remisiones por técnico y detección de remisiones salteadas (permiso REMISIONES). */
@RestController
@RequestMapping("/api/remisiones")
public class RemisionController {

	public record Remision(int idControl, String numeroRemision, int idTecnico, String tecnico, int idEstado, String estado,
		LocalDateTime fechaAsignacion, LocalDateTime fechaUso, Integer idOrden, Integer idInstalacion) {
	}

	public record Pagina(List<Remision> items, long total) {
	}

	public record Rango(@NotNull Integer idTecnico, @NotNull Long desde, Long hasta) {
	}

	public record ResultadoRango(int creadas, int repetidas) {
	}

	public record Cambio(@NotBlank String numeroRemision, @NotNull Integer idTecnico, @NotNull Integer idEstado) {
	}

	public record Siguiente(String ultima, Long siguiente) {
	}

	/** Remisión DISPONIBLE entre dos USADAS consecutivas del mismo técnico: probablemente se saltó. */
	public record Salteada(int idControl, String numeroRemision, String tecnico, LocalDateTime fechaAsignacion,
		String anterior, String siguiente) {
	}

	private final JdbcClient jdbc;

	public RemisionController(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	@GetMapping
	public Pagina listar(@RequestParam(required = false) String texto, @RequestParam(required = false) Integer idTecnico,
		@RequestParam(required = false) Integer idEstado, @RequestParam(defaultValue = "0") int pagina,
		@RequestParam(defaultValue = "50") int tamano) {
		Filtro f = new Filtro().y("e.nombre_estado <> 'ELIMINADO'").y("cr.id_tecnico = :t", "t", idTecnico)
			.y("cr.id_estado = :e", "e", idEstado).texto("q", texto, "cr.numero_remision", "t.nombre_tecnico");
		String from = """
			FROM control_remisiones cr JOIN tecnico t ON t.id_tecnico = cr.id_tecnico
			JOIN estados_remision e ON e.id_estado = cr.id_estado WHERE """ + " " + f.where();
		long total = jdbc.sql("SELECT COUNT(*) " + from).params(f.params()).query(Long.class).single();
		int t = Math.min(Math.max(tamano, 1), 500);
		List<Remision> items = jdbc.sql("""
			SELECT cr.id_control, cr.numero_remision, cr.id_tecnico, t.nombre_tecnico AS tecnico, cr.id_estado,
			       e.nombre_estado AS estado, cr.fecha_asignacion, cr.fecha_uso, cr.id_orden_servicio AS id_orden, cr.id_instalacion
			""" + from + " ORDER BY cr.id_control DESC LIMIT " + t + " OFFSET " + Math.max(pagina, 0) * t)
			.params(f.params()).query(Remision.class).list();
		return new Pagina(items, total);
	}

	@GetMapping("/siguiente")
	public Siguiente siguiente(@RequestParam int idTecnico) {
		String ultima = jdbc.sql("""
			SELECT numero_remision FROM control_remisiones WHERE id_tecnico = :t
			ORDER BY CAST(numero_remision AS UNSIGNED) DESC LIMIT 1""").param("t", idTecnico).query(String.class).optional()
			.orElse(null);
		Long siguiente = null;
		try {
			siguiente = ultima == null ? null : Long.parseLong(ultima.trim()) + 1;
		}
		catch (NumberFormatException e) {
			// numeración no numérica
		}
		return new Siguiente(ultima, siguiente);
	}

	/** Asigna un rango de remisiones a un técnico (las que ya tenía se omiten). */
	@PostMapping("/rango")
	@Transactional
	public ResultadoRango asignarRango(@Valid @RequestBody Rango r) {
		long fin = r.hasta() == null ? r.desde() : r.hasta();
		if (fin < r.desde()) {
			throw new NegocioException("El número final no puede ser menor que el inicial.");
		}
		if (fin - r.desde() > 2000) {
			throw new NegocioException("Máximo 2.000 remisiones por asignación.");
		}
		int creadas = 0;
		for (long n = r.desde(); n <= fin; n++) {
			creadas += jdbc.sql("""
				INSERT IGNORE INTO control_remisiones (numero_remision, id_tecnico, id_estado, fecha_asignacion)
				VALUES (:n, :t, (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE'), NOW())""")
				.param("n", String.valueOf(n)).param("t", r.idTecnico()).update();
		}
		int total = (int) (fin - r.desde() + 1);
		return new ResultadoRango(creadas, total - creadas);
	}

	@PutMapping("/{id}")
	public void actualizar(@PathVariable int id, @Valid @RequestBody Cambio c) {
		jdbc.sql("""
			UPDATE control_remisiones SET numero_remision = :n, id_tecnico = :t, id_estado = :e WHERE id_control = :id""")
			.param("n", c.numeroRemision().trim()).param("t", c.idTecnico()).param("e", c.idEstado()).param("id", id).update();
	}

	/** Cambio rápido de estado desde la pantalla de salteadas: ANULADA, USADA, FALTANTE, ELIMINADO o DISPONIBLE. */
	@PostMapping("/{id}/estado")
	public void cambiarEstado(@PathVariable int id, @RequestParam String estado) {
		if (!List.of("ANULADA", "USADA", "FALTANTE", "ELIMINADO", "DISPONIBLE").contains(estado)) {
			throw new NegocioException("Estado no permitido.");
		}
		jdbc.sql("""
			UPDATE control_remisiones SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = :e)
			WHERE id_control = :id""").param("e", estado).param("id", id).update();
	}

	@GetMapping("/salteadas")
	public List<Salteada> salteadas() {
		record Fila(int idControl, String numeroRemision, String estado, LocalDateTime fechaAsignacion, int idTecnico,
			String tecnico) {
		}
		List<Fila> todas = jdbc.sql("""
			SELECT cr.id_control, cr.numero_remision, e.nombre_estado AS estado, cr.fecha_asignacion, cr.id_tecnico,
			       t.nombre_tecnico AS tecnico
			FROM control_remisiones cr JOIN tecnico t ON t.id_tecnico = cr.id_tecnico
			JOIN estados_remision e ON e.id_estado = cr.id_estado
			WHERE e.nombre_estado IN ('USADA', 'DISPONIBLE') AND cr.numero_remision REGEXP '^[0-9]+$'
			ORDER BY cr.id_tecnico, CAST(cr.numero_remision AS UNSIGNED)""").query(Fila.class).list();
		Map<Integer, List<Fila>> porTecnico = new LinkedHashMap<>();
		todas.forEach(f -> porTecnico.computeIfAbsent(f.idTecnico(), k -> new ArrayList<>()).add(f));
		List<Salteada> r = new ArrayList<>();
		for (List<Fila> l : porTecnico.values()) {
			// Busca huecos de 1 o 2 remisiones disponibles entre dos usadas consecutivas (igual que en PHP).
			for (int i = 0; i + 2 < l.size(); i++) {
				Fila a = l.get(i);
				if (!a.estado().equals("USADA")) {
					continue;
				}
				for (int hueco = 1; hueco <= 2 && i + hueco + 1 < l.size(); hueco++) {
					Fila fin = l.get(i + hueco + 1);
					boolean consecutivas = true;
					boolean disponibles = true;
					for (int k = i; k <= i + hueco; k++) {
						consecutivas &= Long.parseLong(l.get(k + 1).numeroRemision()) == Long.parseLong(l.get(k).numeroRemision()) + 1;
						if (k > i) {
							disponibles &= l.get(k).estado().equals("DISPONIBLE");
						}
					}
					if (consecutivas && disponibles && fin.estado().equals("USADA")) {
						for (int k = i + 1; k <= i + hueco; k++) {
							Fila h = l.get(k);
							r.add(new Salteada(h.idControl(), h.numeroRemision(), h.tecnico(), h.fechaAsignacion(),
								a.numeroRemision(), fin.numeroRemision()));
						}
					}
				}
			}
		}
		return r;
	}
}
