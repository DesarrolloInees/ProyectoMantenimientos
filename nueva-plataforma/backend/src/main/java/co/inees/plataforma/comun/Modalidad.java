package co.inees.plataforma.comun;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.stereotype.Component;

/**
 * Urbano o interurbano: lo decide el sistema al programar el servicio, nunca el motorizado.
 * Es interurbano cuando el motorizado sale de su ciudad base a otro municipio (p. ej. de Bogotá a Soacha), cuando el
 * punto es de otra delegación que la suya, o cuando el punto ya está marcado como interurbano.
 */
@Component
public class Modalidad {

	public static final int URBANO = 1;
	public static final int INTERURBANO = 2;

	private final JdbcClient jdbc;

	public Modalidad(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	public int decidir(int idPunto, Integer idTecnico) {
		record Datos(int modalidadPunto, int municipioInterurbano, Integer delegacionPunto, Integer delegacionTecnico) {
		}
		Datos d = jdbc.sql("""
			SELECT COALESCE(p.id_modalidad, 1) AS modalidad_punto, COALESCE(mu.interurbano, 0) AS municipio_interurbano,
			       COALESCE(p.id_delegacion, mu.id_delegacion) AS delegacion_punto,
			       (SELECT t.id_delegacion FROM tecnico t WHERE t.id_tecnico = :tec) AS delegacion_tecnico
			FROM punto p LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
			WHERE p.id_punto = :p""").param("p", idPunto).param("tec", idTecnico).query(Datos.class).single();
		boolean otraDelegacion = d.delegacionPunto() != null && d.delegacionTecnico() != null
			&& !d.delegacionPunto().equals(d.delegacionTecnico());
		return d.modalidadPunto() == INTERURBANO || d.municipioInterurbano() == 1 || otraDelegacion ? INTERURBANO : URBANO;
	}
}
