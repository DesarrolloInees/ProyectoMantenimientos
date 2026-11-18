package co.inees.plataforma.admin;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.util.List;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.config.NegocioException;
import jakarta.validation.Valid;
import jakarta.validation.constraints.NotEmpty;
import jakarta.validation.constraints.NotNull;

/**
 * Tarifas en matriz (tipo de mantenimiento × modalidad) para uno o varios tipos de máquina a la vez.
 * Reemplaza tarifaCrear (varias máquinas) y tarifaMasiva (edición por máquina y año) de PHP.
 */
@RestController
@RequestMapping("/api/admin/tarifas-matriz")
public class TarifaController {

	public record Celda(int idTipoMantenimiento, int idModalidad, BigDecimal precio) {
	}

	public record Matriz(int anio, Integer idTipoMaquina, List<Celda> celdas) {
	}

	public record Guardar(@NotNull Integer anio, @NotEmpty List<Integer> idsTiposMaquina, @NotNull List<Celda> celdas) {
	}

	public record Copia(int creadas, int existentes) {
	}

	private final JdbcClient jdbc;

	public TarifaController(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	@GetMapping
	public Matriz matriz(@RequestParam int anio, @RequestParam int idTipoMaquina) {
		List<Celda> celdas = jdbc.sql("""
			SELECT id_tipo_mantenimiento, id_modalidad, precio FROM tarifa
			WHERE id_tipo_maquina = :tm AND anio_vigencia = :anio""")
			.param("tm", idTipoMaquina).param("anio", anio).query(Celda.class).list();
		return new Matriz(anio, idTipoMaquina, celdas);
	}

	/** Tipos de máquina que ya tienen alguna tarifa en el año. */
	@GetMapping("/con-tarifa")
	public List<Integer> conTarifa(@RequestParam int anio) {
		return jdbc.sql("SELECT DISTINCT id_tipo_maquina FROM tarifa WHERE anio_vigencia = :anio").param("anio", anio)
			.query(Integer.class).list();
	}

	/** Crea o actualiza los precios; una celda vacía o en cero borra la tarifa de esa combinación. */
	@PutMapping
	@Transactional
	public int guardar(@Valid @RequestBody Guardar g) {
		int n = 0;
		for (int tipo : g.idsTiposMaquina()) {
			for (Celda c : g.celdas()) {
				if (c.precio() == null || c.precio().signum() <= 0) {
					jdbc.sql("""
						DELETE FROM tarifa WHERE id_tipo_maquina = :tm AND id_tipo_mantenimiento = :mant
						   AND id_modalidad = :mod AND anio_vigencia = :anio""")
						.param("tm", tipo).param("mant", c.idTipoMantenimiento()).param("mod", c.idModalidad())
						.param("anio", g.anio()).update();
					continue;
				}
				n += jdbc.sql("""
					INSERT INTO tarifa (id_tipo_maquina, id_tipo_mantenimiento, id_modalidad, precio, anio_vigencia)
					VALUES (:tm, :mant, :mod, :precio, :anio) ON DUPLICATE KEY UPDATE precio = VALUES(precio)""")
					.param("tm", tipo).param("mant", c.idTipoMantenimiento()).param("mod", c.idModalidad())
					.param("precio", c.precio()).param("anio", g.anio()).update() > 0 ? 1 : 0;
			}
		}
		return n;
	}

	/** Copia las tarifas de un año al siguiente con un incremento porcentual (redondeado a la centena). */
	@PostMapping("/copiar")
	@Transactional
	public Copia copiar(@RequestParam int desde, @RequestParam int hacia, @RequestParam(defaultValue = "0") BigDecimal incremento) {
		if (desde == hacia) {
			throw new NegocioException("El año de destino debe ser distinto del de origen.");
		}
		record Fila(int idTipoMaquina, int idTipoMantenimiento, int idModalidad, BigDecimal precio) {
		}
		List<Fila> filas = jdbc.sql("""
			SELECT id_tipo_maquina, id_tipo_mantenimiento, id_modalidad, precio FROM tarifa WHERE anio_vigencia = :a""")
			.param("a", desde).query(Fila.class).list();
		if (filas.isEmpty()) {
			throw new NegocioException("No hay tarifas en " + desde + ".");
		}
		BigDecimal factor = BigDecimal.ONE.add(incremento.movePointLeft(2));
		int creadas = 0;
		for (Fila f : filas) {
			BigDecimal precio = f.precio().multiply(factor).divide(BigDecimal.valueOf(100), 0, RoundingMode.HALF_UP)
				.multiply(BigDecimal.valueOf(100));
			creadas += jdbc.sql("""
				INSERT IGNORE INTO tarifa (id_tipo_maquina, id_tipo_mantenimiento, id_modalidad, precio, anio_vigencia)
				VALUES (:tm, :mant, :mod, :precio, :anio)""")
				.param("tm", f.idTipoMaquina()).param("mant", f.idTipoMantenimiento()).param("mod", f.idModalidad())
				.param("precio", precio).param("anio", hacia).update();
		}
		return new Copia(creadas, filas.size() - creadas);
	}
}
