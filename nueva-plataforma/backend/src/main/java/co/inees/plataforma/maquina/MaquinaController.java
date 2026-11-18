package co.inees.plataforma.maquina;

import java.time.LocalDate;
import java.util.List;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.seguridad.Sesion;
import jakarta.validation.Valid;
import jakarta.validation.constraints.DecimalMax;
import jakarta.validation.constraints.DecimalMin;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotNull;

@RestController
@RequestMapping("/api/maquinas")
public class MaquinaController {

	public record Maquina(int idMaquina, String deviceId, String tipoMaquina, int idPunto, String punto, String direccion,
		String municipio, String delegacion, String cliente, Double latitud, Double longitud, String geoOrigen,
		boolean geoVerificada, Double latitudPunto, Double longitudPunto, boolean esVip, Integer frecuenciaDias,
		Integer frecuenciaTipo, LocalDate ultimaVisita, boolean activoOperativo, Double latitudReferencia,
		Double longitudReferencia) {
	}

	public record Ubicacion(@NotNull @DecimalMin("-4.3") @DecimalMax("13.5") Double latitud,
		@NotNull @DecimalMin("-82") @DecimalMax("-66") Double longitud) {
	}

	public record Configuracion(boolean esVip, @Min(1) Integer frecuenciaDias) {
	}

	static final String SELECT = """
		SELECT m.id_maquina, m.device_id, tm.nombre_tipo_maquina AS tipo_maquina, p.id_punto, p.nombre_punto AS punto,
		       p.direccion, mu.nombre_municipio AS municipio, d.nombre_delegacion AS delegacion, c.nombre_cliente AS cliente,
		       m.latitud, m.longitud, m.geo_origen, m.geo_verificada,
		       NULLIF(p.latitud, 0) AS latitud_punto, NULLIF(p.longitud, 0) AS longitud_punto,
		       m.es_vip, m.frecuencia_dias, tm.frecuencia_base_dias AS frecuencia_tipo, m.ultima_visita, m.activo_operativo,
		       -- Referencia para centrar el mapa: el punto o, si no tiene, el centro de las máquinas del mismo municipio
		       COALESCE(NULLIF(p.latitud, 0), (SELECT AVG(m2.latitud) FROM maquina m2 JOIN punto p2 ON p2.id_punto = m2.id_punto
		                WHERE p2.id_municipio = p.id_municipio AND m2.latitud IS NOT NULL)) AS latitud_referencia,
		       COALESCE(NULLIF(p.longitud, 0), (SELECT AVG(m2.longitud) FROM maquina m2 JOIN punto p2 ON p2.id_punto = m2.id_punto
		                WHERE p2.id_municipio = p.id_municipio AND m2.longitud IS NOT NULL)) AS longitud_referencia
		FROM maquina m
		JOIN punto p ON p.id_punto = m.id_punto
		JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina
		JOIN cliente c ON c.id_cliente = p.id_cliente
		LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
		LEFT JOIN delegacion d ON d.id_delegacion = COALESCE(p.id_delegacion, mu.id_delegacion)
		""";

	private final JdbcClient jdbc;

	public MaquinaController(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	@GetMapping
	public List<Maquina> buscar(@RequestParam String texto) {
		String t = "%" + texto.trim() + "%";
		return jdbc.sql(SELECT + """
			WHERE m.estado = 1 AND (m.device_id LIKE :t OR p.nombre_punto LIKE :t OR c.nombre_cliente LIKE :t)
			ORDER BY p.nombre_punto LIMIT 30""").param("t", t).query(Maquina.class).list();
	}

	@GetMapping("/{id}")
	public Maquina obtener(@PathVariable int id) {
		return jdbc.sql(SELECT + " WHERE m.id_maquina = :id").param("id", id).query(Maquina.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("La máquina"));
	}

	/** Máquinas de una carga de Prosegur (o de una delegación) que todavía no tienen ubicación. */
	@GetMapping("/sin-ubicacion")
	public List<Maquina> sinUbicacion(@RequestParam(required = false) Integer idCarga,
		@RequestParam(required = false) Integer idDelegacion) {
		if (idCarga != null) {
			return jdbc.sql(SELECT + """
				JOIN solicitud_servicio s ON s.id_maquina = m.id_maquina
				WHERE s.id_carga = :carga AND s.estado = 'VALIDA' AND m.latitud IS NULL
				ORDER BY p.nombre_punto""").param("carga", idCarga).query(Maquina.class).list();
		}
		return jdbc.sql(SELECT + """
			WHERE m.estado = 1 AND p.estado = 1 AND m.latitud IS NULL
			  AND (:del IS NULL OR COALESCE(p.id_delegacion, mu.id_delegacion) = :del)
			ORDER BY p.nombre_punto LIMIT 300""").param("del", idDelegacion).query(Maquina.class).list();
	}

	/** El Supervisor o el Auxiliar pone el pin con la ubicación exacta. */
	@PutMapping("/{id}/ubicacion")
	@Transactional
	public Maquina ponerPin(@PathVariable int id, @Valid @RequestBody Ubicacion u) {
		int n = jdbc.sql("""
			UPDATE maquina SET latitud = :lat, longitud = :lon, geo_origen = 'PIN', geo_verificada = 1,
			       geo_id_usuario = :usuario, geo_fecha = NOW()
			WHERE id_maquina = :id""")
			.param("lat", u.latitud()).param("lon", u.longitud()).param("usuario", Sesion.actual().usuarioId())
			.param("id", id).update();
		if (n == 0) {
			throw NegocioException.noEncontrado("La máquina");
		}
		return obtener(id);
	}

	@PutMapping("/{id}/configuracion")
	public Maquina configurar(@PathVariable int id, @Valid @RequestBody Configuracion c) {
		jdbc.sql("UPDATE maquina SET es_vip = :vip, frecuencia_dias = :frec WHERE id_maquina = :id")
			.param("vip", c.esVip()).param("frec", c.frecuenciaDias()).param("id", id).update();
		return obtener(id);
	}
}
