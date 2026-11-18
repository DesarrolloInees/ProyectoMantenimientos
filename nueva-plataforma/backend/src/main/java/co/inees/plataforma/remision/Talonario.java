package co.inees.plataforma.remision;

import java.time.LocalDateTime;
import java.util.Objects;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.stereotype.Component;

import co.inees.plataforma.config.NegocioException;

/** Reglas de las remisiones de papel de cada técnico: solo se usa una remisión DISPONIBLE de su propio talonario. */
@Component
public class Talonario {

	public record Estado(boolean disponible, String mensaje) {
	}

	private final JdbcClient jdbc;

	public Talonario(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	/** Verifica una remisión para un servicio (la que ya tiene el mismo servicio cuenta como disponible). */
	public Estado verificar(String numero, int idTecnico, Integer idOrdenActual) {
		record Fila(String estado, Integer idOrden, Integer idInstalacion) {
		}
		Fila f = jdbc.sql("""
			SELECT e.nombre_estado AS estado, cr.id_orden_servicio AS id_orden, cr.id_instalacion
			FROM control_remisiones cr JOIN estados_remision e ON e.id_estado = cr.id_estado
			WHERE cr.numero_remision = :n AND cr.id_tecnico = :t""")
			.param("n", numero.trim()).param("t", idTecnico).query(Fila.class).optional().orElse(null);
		if (f == null) {
			return new Estado(false, "La remisión " + numero + " no está asignada a este técnico.");
		}
		if (idOrdenActual != null && Objects.equals(f.idOrden(), idOrdenActual)) {
			return new Estado(true, "Remisión de este servicio.");
		}
		if (!"DISPONIBLE".equals(f.estado())) {
			String uso = f.idOrden() != null ? " en el servicio #" + f.idOrden()
				: f.idInstalacion() != null ? " en el transporte #" + f.idInstalacion() : "";
			return new Estado(false, "La remisión " + numero + " está " + f.estado().toLowerCase() + uso + ".");
		}
		return new Estado(true, "Remisión disponible.");
	}

	public void exigir(String numero, int idTecnico, Integer idOrdenActual) {
		Estado e = verificar(numero, idTecnico, idOrdenActual);
		if (!e.disponible()) {
			throw new NegocioException(e.mensaje());
		}
	}

	/** Marca la remisión como usada por el servicio. */
	public void ocupar(String numero, int idTecnico, int idOrden, LocalDateTime fechaUso) {
		jdbc.sql("""
			UPDATE control_remisiones SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA'),
			       id_orden_servicio = :o, fecha_uso = :f
			WHERE numero_remision = :n AND id_tecnico = :t""")
			.param("o", idOrden).param("f", fechaUso).param("n", numero.trim()).param("t", idTecnico).update();
	}

	/** Devuelve al talonario la remisión que tenía el servicio (al cambiarla, cambiar de técnico o anular el servicio). */
	public void liberarDeOrden(int idOrden) {
		jdbc.sql("""
			UPDATE control_remisiones SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE'),
			       id_orden_servicio = NULL, fecha_uso = NULL
			WHERE id_orden_servicio = :o""").param("o", idOrden).update();
	}

	/** Remisión usada en un transporte (instalación, desinstalación, traslado). */
	public void ocuparEnTransporte(int idControl, int idTecnico, int idInstalacion) {
		int n = jdbc.sql("""
			UPDATE control_remisiones SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA'),
			       fecha_uso = NOW(), id_instalacion = :i
			WHERE id_control = :c AND id_tecnico = :t
			  AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE')""")
			.param("i", idInstalacion).param("c", idControl).param("t", idTecnico).update();
		if (n == 0) {
			throw new NegocioException("La remisión elegida ya no está disponible para ese técnico.");
		}
	}

	public void liberarDeTransporte(int idInstalacion) {
		jdbc.sql("""
			UPDATE control_remisiones SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE'),
			       id_instalacion = NULL, fecha_uso = NULL
			WHERE id_instalacion = :i""").param("i", idInstalacion).update();
	}
}
