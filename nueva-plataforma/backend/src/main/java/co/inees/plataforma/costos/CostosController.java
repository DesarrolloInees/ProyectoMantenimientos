package co.inees.plataforma.costos;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.YearMonth;
import java.util.List;

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
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.config.NegocioException;
import jakarta.validation.Valid;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;

/**
 * Costos mensuales: nómina y gastos de los motorizados, nómina administrativa y gastos generales (permiso COSTOS).
 * Una fila de costos_operativos es de un técnico o de un usuario administrativo, nunca de los dos.
 */
@RestController
@RequestMapping("/api/costos")
public class CostosController {

	public record Mes(String mes, int personas, BigDecimal nomina, BigDecimal operativo, BigDecimal gastos, BigDecimal total) {
	}

	/** Costos de una persona en el mes; id es el técnico (motorizados) o el usuario (administrativos). */
	public record Costo(int id, String nombre, String cargo, BigDecimal salario, BigDecimal horasExtra, BigDecimal bonoMeta,
		BigDecimal gasolina, BigDecimal auxilioComunicacion, BigDecimal auxilioRodamiento) {

		BigDecimal total() {
			return n(salario).add(n(horasExtra)).add(n(bonoMeta)).add(n(gasolina)).add(n(auxilioComunicacion)).add(n(auxilioRodamiento));
		}
	}

	public record Gasto(int idGasto, String concepto, String categoria, BigDecimal valor, String observacion) {
	}

	public record NuevoGasto(@NotBlank String concepto, String categoria, @NotNull BigDecimal valor, String observacion) {
	}

	public record Administrativos(List<Costo> nomina, List<Gasto> gastos) {
	}

	private final JdbcClient jdbc;

	public CostosController(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	// ------------------------------------------------------------------ motorizados
	@GetMapping("/motorizados/meses")
	public List<Mes> mesesMotorizados() {
		return jdbc.sql("""
			SELECT DATE_FORMAT(mes_reporte, '%Y-%m') AS mes, COUNT(id_tecnico) AS personas, SUM(salario) AS nomina,
			       SUM(auxilio_rodamiento + gasolina + bono_meta + horas_extra + auxilio_comunicacion) AS operativo,
			       0 AS gastos,
			       SUM(salario + auxilio_rodamiento + gasolina + bono_meta + horas_extra + auxilio_comunicacion) AS total
			FROM costos_operativos WHERE id_tecnico IS NOT NULL AND estado = 1
			GROUP BY DATE_FORMAT(mes_reporte, '%Y-%m') ORDER BY mes DESC""").query(Mes.class).list();
	}

	@GetMapping("/motorizados")
	public List<Costo> motorizados(@RequestParam String mes) {
		return jdbc.sql("""
			SELECT t.id_tecnico AS id, t.nombre_tecnico AS nombre, 'Técnico motorizado' AS cargo, c.salario, c.horas_extra,
			       c.bono_meta, c.gasolina, c.auxilio_comunicacion, c.auxilio_rodamiento
			FROM tecnico t LEFT JOIN costos_operativos c ON c.id_tecnico = t.id_tecnico AND c.mes_reporte = :mes AND c.estado = 1
			WHERE t.estado = 1 OR c.id_costo IS NOT NULL ORDER BY t.nombre_tecnico""")
			.param("mes", primerDia(mes)).query(Costo.class).list();
	}

	@PutMapping("/motorizados")
	@Transactional
	public List<Costo> guardarMotorizados(@RequestParam String mes, @Valid @RequestBody List<Costo> costos) {
		guardar("id_tecnico", primerDia(mes), costos);
		return motorizados(mes);
	}

	@DeleteMapping("/motorizados")
	public void eliminarMesMotorizados(@RequestParam String mes) {
		jdbc.sql("UPDATE costos_operativos SET estado = 0 WHERE mes_reporte = :m AND id_tecnico IS NOT NULL")
			.param("m", primerDia(mes)).update();
	}

	// ------------------------------------------------------------------ administrativos
	@GetMapping("/administrativos/meses")
	public List<Mes> mesesAdministrativos() {
		return jdbc.sql("""
			SELECT DATE_FORMAT(m.mes, '%Y-%m') AS mes,
			       (SELECT COUNT(*) FROM costos_operativos c WHERE c.mes_reporte = m.mes AND c.id_usuario IS NOT NULL AND c.estado = 1) AS personas,
			       (SELECT COALESCE(SUM(c.salario + c.horas_extra + c.bono_meta + c.gasolina + c.auxilio_comunicacion + c.auxilio_rodamiento), 0)
			          FROM costos_operativos c WHERE c.mes_reporte = m.mes AND c.id_usuario IS NOT NULL AND c.estado = 1) AS nomina,
			       0 AS operativo,
			       (SELECT COALESCE(SUM(valor), 0) FROM gastos_administrativos g WHERE g.mes_reporte = m.mes AND g.estado = 1) AS gastos,
			       0 AS total
			FROM (SELECT DISTINCT mes_reporte AS mes FROM gastos_administrativos WHERE estado = 1
			      UNION SELECT DISTINCT mes_reporte FROM costos_operativos WHERE id_usuario IS NOT NULL AND estado = 1) m
			ORDER BY m.mes DESC""").query(Mes.class).list().stream()
			.map(m -> new Mes(m.mes(), m.personas(), m.nomina(), m.operativo(), m.gastos(), n(m.nomina()).add(n(m.gastos())))).toList();
	}

	@GetMapping("/administrativos")
	public Administrativos administrativos(@RequestParam String mes) {
		LocalDate d = primerDia(mes);
		List<Costo> nomina = jdbc.sql("""
			SELECT u.usuario_id AS id, u.nombre, COALESCE(u.cargo, 'Administrativo') AS cargo, c.salario, c.horas_extra, c.bono_meta,
			       c.gasolina, c.auxilio_comunicacion, c.auxilio_rodamiento
			FROM usuarios u LEFT JOIN costos_operativos c ON c.id_usuario = u.usuario_id AND c.mes_reporte = :mes AND c.estado = 1
			WHERE (u.estado = 'activo' AND u.id_rol <> 3) OR c.id_costo IS NOT NULL ORDER BY u.nombre""")
			.param("mes", d).query(Costo.class).list();
		return new Administrativos(nomina, gastos(d));
	}

	@PutMapping("/administrativos/nomina")
	@Transactional
	public Administrativos guardarNomina(@RequestParam String mes, @Valid @RequestBody List<Costo> costos) {
		guardar("id_usuario", primerDia(mes), costos);
		return administrativos(mes);
	}

	@PostMapping("/administrativos/gastos")
	public Gasto crearGasto(@RequestParam String mes, @Valid @RequestBody NuevoGasto g) {
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO gastos_administrativos (mes_reporte, concepto, categoria, valor, observacion, estado)
			VALUES (:m, :c, :cat, :v, :o, 1)""")
			.param("m", primerDia(mes)).param("c", g.concepto().trim())
			.param("cat", g.categoria() == null || g.categoria().isBlank() ? "Administrativo" : g.categoria().trim())
			.param("v", g.valor()).param("o", g.observacion()).update(llave);
		int id = llave.getKey().intValue();
		return new Gasto(id, g.concepto().trim(), g.categoria(), g.valor(), g.observacion());
	}

	@PutMapping("/administrativos/gastos/{id}")
	public void actualizarGasto(@PathVariable int id, @Valid @RequestBody NuevoGasto g) {
		jdbc.sql("""
			UPDATE gastos_administrativos SET concepto = :c, categoria = :cat, valor = :v, observacion = :o
			WHERE id_gasto = :id AND estado = 1""")
			.param("c", g.concepto().trim()).param("cat", g.categoria() == null || g.categoria().isBlank() ? "Administrativo" : g.categoria())
			.param("v", g.valor()).param("o", g.observacion()).param("id", id).update();
	}

	@DeleteMapping("/administrativos/gastos/{id}")
	public void eliminarGasto(@PathVariable int id) {
		jdbc.sql("UPDATE gastos_administrativos SET estado = 0 WHERE id_gasto = :id").param("id", id).update();
	}

	@DeleteMapping("/administrativos")
	@Transactional
	public void eliminarMesAdministrativo(@RequestParam String mes) {
		LocalDate d = primerDia(mes);
		jdbc.sql("UPDATE gastos_administrativos SET estado = 0 WHERE mes_reporte = :m").param("m", d).update();
		jdbc.sql("UPDATE costos_operativos SET estado = 0 WHERE mes_reporte = :m AND id_usuario IS NOT NULL").param("m", d).update();
	}

	// ------------------------------------------------------------------ apoyo
	private List<Gasto> gastos(LocalDate mes) {
		return jdbc.sql("""
			SELECT id_gasto, concepto, categoria, valor, observacion FROM gastos_administrativos
			WHERE mes_reporte = :m AND estado = 1 ORDER BY id_gasto DESC""").param("m", mes).query(Gasto.class).list();
	}

	/** Guarda la grilla del mes: filas en cero se desactivan; las demás se crean o actualizan. */
	private void guardar(String columna, LocalDate mes, List<Costo> costos) {
		for (Costo c : costos) {
			if (c.total().signum() == 0) {
				jdbc.sql("UPDATE costos_operativos SET estado = 0 WHERE " + columna + " = :id AND mes_reporte = :m")
					.param("id", c.id()).param("m", mes).update();
				continue;
			}
			jdbc.sql("INSERT INTO costos_operativos (" + columna + """
				, mes_reporte, salario, horas_extra, bono_meta, gasolina, auxilio_comunicacion, auxilio_rodamiento, estado)
				VALUES (:id, :m, :sal, :he, :bono, :gas, :com, :rod, 1)
				ON DUPLICATE KEY UPDATE salario = VALUES(salario), horas_extra = VALUES(horas_extra), bono_meta = VALUES(bono_meta),
				       gasolina = VALUES(gasolina), auxilio_comunicacion = VALUES(auxilio_comunicacion),
				       auxilio_rodamiento = VALUES(auxilio_rodamiento), estado = 1""")
				.param("id", c.id()).param("m", mes).param("sal", n(c.salario())).param("he", n(c.horasExtra()))
				.param("bono", n(c.bonoMeta())).param("gas", n(c.gasolina())).param("com", n(c.auxilioComunicacion()))
				.param("rod", n(c.auxilioRodamiento())).update();
		}
	}

	private static LocalDate primerDia(String mes) {
		try {
			return YearMonth.parse(mes.substring(0, 7)).atDay(1);
		}
		catch (RuntimeException e) {
			throw new NegocioException("El mes debe tener el formato AAAA-MM.");
		}
	}

	static BigDecimal n(BigDecimal v) {
		return v == null ? BigDecimal.ZERO : v;
	}
}
