package co.inees.plataforma.inventario;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.stereotype.Component;

import co.inees.plataforma.config.NegocioException;

/**
 * Único punto por donde cambia el stock de un motorizado: actualiza inventario_tecnico y deja el movimiento en
 * movimiento_inventario (asignación, consumo, devolución o ajuste). En PHP el stock se editaba directo y no quedaba
 * rastro de quién ni por qué.
 */
@Component
public class Kardex {

	public enum Tipo {
		ASIGNACION, CONSUMO, DEVOLUCION, AJUSTE
	}

	private final JdbcClient jdbc;

	public Kardex(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	public int stock(int idTecnico, int idRepuesto) {
		return jdbc.sql("SELECT cantidad_actual FROM inventario_tecnico WHERE id_tecnico = :t AND id_repuesto = :r AND estado = 1")
			.param("t", idTecnico).param("r", idRepuesto).query(Integer.class).optional().orElse(0);
	}

	/**
	 * Registra un movimiento. La cantidad es positiva si entra al stock del técnico y negativa si sale.
	 *
	 * @return id del movimiento
	 */
	public int mover(int idTecnico, int idRepuesto, Tipo tipo, int cantidad, Integer idOrden, Integer idUsuario,
		String observacion) {
		if (cantidad == 0) {
			throw new NegocioException("La cantidad no puede ser cero.");
		}
		// Bloquea la fila para que dos consumos simultáneos no dejen el stock en negativo.
		Integer actual = jdbc.sql("""
			SELECT cantidad_actual FROM inventario_tecnico WHERE id_tecnico = :t AND id_repuesto = :r FOR UPDATE""")
			.param("t", idTecnico).param("r", idRepuesto).query(Integer.class).optional().orElse(null);
		int disponible = actual == null ? 0 : actual;
		if (disponible + cantidad < 0) {
			String nombre = jdbc.sql("SELECT nombre_repuesto FROM repuesto WHERE id_repuesto = :r").param("r", idRepuesto)
				.query(String.class).optional().orElse("el repuesto");
			throw new NegocioException("No hay suficiente " + nombre + " en el stock del técnico (tiene " + disponible + ").");
		}
		if (actual == null) {
			jdbc.sql("INSERT INTO inventario_tecnico (id_tecnico, id_repuesto, cantidad_actual, estado) VALUES (:t, :r, :c, 1)")
				.param("t", idTecnico).param("r", idRepuesto).param("c", cantidad).update();
		}
		else {
			jdbc.sql("""
				UPDATE inventario_tecnico SET cantidad_actual = cantidad_actual + :c, estado = 1
				WHERE id_tecnico = :t AND id_repuesto = :r""")
				.param("c", cantidad).param("t", idTecnico).param("r", idRepuesto).update();
		}
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO movimiento_inventario (id_tecnico, id_repuesto, tipo, cantidad, id_orden_servicio, id_usuario, observacion)
			VALUES (:t, :r, :tipo, :c, :o, :u, :obs)""")
			.param("t", idTecnico).param("r", idRepuesto).param("tipo", tipo.name()).param("c", cantidad)
			.param("o", idOrden).param("u", idUsuario)
			.param("obs", observacion == null || observacion.length() <= 255 ? observacion : observacion.substring(0, 255))
			.update(llave);
		return llave.getKey().intValue();
	}

	/** Deja el stock en una cantidad exacta (conteo físico) con un movimiento de ajuste por la diferencia. */
	public void fijar(int idTecnico, int idRepuesto, int cantidadNueva, Integer idUsuario, String observacion) {
		if (cantidadNueva < 0) {
			throw new NegocioException("La cantidad no puede ser negativa.");
		}
		int diferencia = cantidadNueva - stock(idTecnico, idRepuesto);
		if (diferencia != 0) {
			mover(idTecnico, idRepuesto, Tipo.AJUSTE, diferencia, null, idUsuario,
				observacion == null || observacion.isBlank() ? "Ajuste por conteo" : observacion);
		}
	}
}
