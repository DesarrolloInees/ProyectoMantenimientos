package co.inees.plataforma.comun;

import java.util.Objects;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.stereotype.Component;

/** Registra en orden_servicio_historial quién cambió qué en una orden y por qué. */
@Component
public class Historial {

	private final JdbcClient jdbc;

	public Historial(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	/** @param idUsuario null cuando el cambio lo hace el sistema */
	public void registrar(int idOrden, String campo, Object anterior, Object nuevo, String motivo, Integer idUsuario) {
		if (Objects.equals(String.valueOf(anterior), String.valueOf(nuevo))) {
			return;
		}
		jdbc.sql("""
			INSERT INTO orden_servicio_historial (id_orden_servicio, campo, valor_anterior, valor_nuevo, motivo, id_usuario)
			VALUES (:orden, :campo, :anterior, :nuevo, :motivo, :usuario)""")
			.param("orden", idOrden)
			.param("campo", campo)
			.param("anterior", recortar(anterior))
			.param("nuevo", recortar(nuevo))
			.param("motivo", recortar(motivo))
			.param("usuario", idUsuario)
			.update();
	}

	private static String recortar(Object valor) {
		if (valor == null) {
			return null;
		}
		String s = String.valueOf(valor);
		return s.length() > 255 ? s.substring(0, 255) : s;
	}
}
