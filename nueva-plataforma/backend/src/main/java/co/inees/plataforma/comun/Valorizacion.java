package co.inees.plataforma.comun;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.Objects;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.stereotype.Component;

/**
 * Precio de un servicio (tarifa por tipo de máquina, tipo de mantenimiento, modalidad y año) y viáticos.
 * Una sola regla para el cierre en campo, el registro manual y la corrección en oficina (en PHP estaba repetida
 * en tres sitios y con diferencias).
 */
@Component
public class Valorizacion {

	public record Viaticos(boolean fueraDelegacion, int dias, BigDecimal valor) {
	}

	private final JdbcClient jdbc;
	private final Parametros parametros;

	public Valorizacion(JdbcClient jdbc, Parametros parametros) {
		this.jdbc = jdbc;
		this.parametros = parametros;
	}

	/** Tarifa vigente; null si no hay tarifa para esa combinación y año. */
	public BigDecimal tarifa(int idTipoMaquina, Integer idTipoMantenimiento, Integer idModalidad, LocalDate fecha) {
		if (idTipoMantenimiento == null) {
			return null;
		}
		return jdbc.sql("""
			SELECT precio FROM tarifa WHERE id_tipo_maquina = :tm AND id_tipo_mantenimiento = :mant
			   AND id_modalidad = :mod AND anio_vigencia = :anio""")
			.param("tm", idTipoMaquina).param("mant", idTipoMantenimiento)
			.param("mod", idModalidad == null ? 1 : idModalidad).param("anio", fecha.getYear())
			.query(BigDecimal.class).optional().orElse(null);
	}

	/** Tarifa de una máquina (por su tipo). */
	public BigDecimal tarifaMaquina(int idMaquina, Integer idTipoMantenimiento, Integer idModalidad, LocalDate fecha) {
		Integer tipo = jdbc.sql("SELECT id_tipo_maquina FROM maquina WHERE id_maquina = :m").param("m", idMaquina)
			.query(Integer.class).optional().orElse(null);
		return tipo == null ? null : tarifa(tipo, idTipoMantenimiento, idModalidad, fecha);
	}

	/**
	 * Viáticos: un día por técnico y fecha cuando el servicio es interurbano y el punto queda fuera de las
	 * delegaciones principales (parámetro delegaciones_principales). Si ese día ya se cobraron en otro servicio, cero.
	 */
	public Viaticos viaticos(Integer idTecnico, LocalDate fecha, Integer idModalidad, int idPunto, Integer idOrdenExcluir) {
		Integer delegacion = jdbc.sql("""
			SELECT COALESCE(p.id_delegacion, mu.id_delegacion) FROM punto p
			LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio WHERE p.id_punto = :p""")
			.param("p", idPunto).query(Integer.class).optional().orElse(null);
		boolean fuera = Objects.equals(idModalidad, 2) && delegacion != null
			&& !parametros.enteros(Parametros.DELEGACIONES_PRINCIPALES, "1,2,3,4,5").contains(delegacion);
		if (!fuera || idTecnico == null) {
			return new Viaticos(fuera, 0, BigDecimal.ZERO);
		}
		int yaCobrados = jdbc.sql("""
			SELECT COUNT(*) FROM ordenes_servicio WHERE id_tecnico = :t AND fecha_visita = :f AND valor_viaticos > 0
			   AND estado = 1 AND id_ordenes_servicio <> :o""")
			.param("t", idTecnico).param("f", fecha).param("o", idOrdenExcluir == null ? 0 : idOrdenExcluir)
			.query(Integer.class).single();
		if (yaCobrados > 0) {
			return new Viaticos(true, 0, BigDecimal.ZERO);
		}
		return new Viaticos(true, 1, BigDecimal.valueOf(parametros.decimal(Parametros.RECARGO_VIATICOS, 0)));
	}

	/** Actualiza la última visita real del punto y de la máquina (no cuentan los fallidos). */
	public void actualizarUltimaVisita(int idPunto, int idMaquina) {
		jdbc.sql("""
			UPDATE punto p
			  JOIN (SELECT id_punto, fecha_visita, id_tipo_mantenimiento FROM ordenes_servicio
			         WHERE id_punto = :p AND estado = 1 AND id_tipo_mantenimiento <> 4
			         ORDER BY fecha_visita DESC, id_ordenes_servicio DESC LIMIT 1) u ON u.id_punto = p.id_punto
			   SET p.fecha_ultima_visita = u.fecha_visita, p.id_ultimo_tipo_mantenimiento = u.id_tipo_mantenimiento
			 WHERE p.id_punto = :p""").param("p", idPunto).update();
		jdbc.sql("""
			UPDATE maquina m SET m.ultima_visita = (SELECT MAX(fecha_visita) FROM ordenes_servicio
			       WHERE id_maquina = :m AND estado = 1 AND id_tipo_mantenimiento <> 4)
			 WHERE m.id_maquina = :m""").param("m", idMaquina).update();
	}
}
