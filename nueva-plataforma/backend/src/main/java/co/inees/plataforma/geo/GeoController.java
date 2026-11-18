package co.inees.plataforma.geo;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.client.RestClient;

import co.inees.plataforma.config.AppProperties;
import co.inees.plataforma.config.NegocioException;

/**
 * Geocodificación de puntos por dirección con Google (geolocalizar de PHP). La clave va en GOOGLE_GEOCODING_KEY;
 * en PHP estaba escrita en el código. Las coordenadas también pasan a las máquinas del punto que no tienen ubicación.
 */
@RestController
@RequestMapping("/api/geo")
public class GeoController {

	public record Pendientes(int total, int sinIntentos) {
	}

	public record Resultado(int procesados, int ubicados, int fallidos, List<String> detalle) {
	}

	record Punto(int idPunto, String nombrePunto, String direccion, String municipio, String delegacion) {
	}

	private static final Logger log = LoggerFactory.getLogger(GeoController.class);

	private final JdbcClient jdbc;
	private final AppProperties props;
	private final RestClient http = RestClient.create();

	public GeoController(JdbcClient jdbc, AppProperties props) {
		this.jdbc = jdbc;
		this.props = props;
	}

	@GetMapping("/pendientes")
	public Pendientes pendientes() {
		return jdbc.sql("""
			SELECT COUNT(*) AS total, SUM(intentos_api = 0) AS sin_intentos FROM punto
			WHERE estado = 1 AND (latitud IS NULL OR longitud IS NULL OR latitud = 0) AND intentos_api < 3""")
			.query(Pendientes.class).single();
	}

	/** Geocodifica un lote de puntos sin coordenadas (máximo 3 intentos por punto). */
	@PostMapping("/geocodificar")
	public Resultado geocodificar(@RequestParam(defaultValue = "25") int lote) {
		String clave = props.google() == null ? null : props.google().claveGeocodificacion();
		if (clave == null || clave.isBlank()) {
			throw new NegocioException("Falta la clave de Google (GOOGLE_GEOCODING_KEY) en el servidor.");
		}
		List<Punto> puntos = jdbc.sql("""
			SELECT p.id_punto, p.nombre_punto, p.direccion, mu.nombre_municipio AS municipio, d.nombre_delegacion AS delegacion
			FROM punto p LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			WHERE p.estado = 1 AND (p.latitud IS NULL OR p.longitud IS NULL OR p.latitud = 0) AND p.intentos_api < 3
			ORDER BY p.intentos_api, p.id_punto LIMIT :n""").param("n", Math.min(Math.max(lote, 1), 100)).query(Punto.class).list();
		int ubicados = 0;
		List<String> detalle = new ArrayList<>();
		for (Punto p : puntos) {
			String direccion = String.join(", ", java.util.stream.Stream.of(p.direccion(), p.municipio(), p.delegacion(), "Colombia")
				.filter(s -> s != null && !s.isBlank()).toList());
			double[] coord = consultar(direccion, clave);
			if (coord == null) {
				jdbc.sql("UPDATE punto SET intentos_api = intentos_api + 1 WHERE id_punto = :id").param("id", p.idPunto()).update();
				detalle.add("Sin resultado: " + p.nombrePunto());
				continue;
			}
			jdbc.sql("UPDATE punto SET latitud = :lat, longitud = :lon, intentos_api = intentos_api + 1 WHERE id_punto = :id")
				.param("lat", coord[0]).param("lon", coord[1]).param("id", p.idPunto()).update();
			jdbc.sql("""
				UPDATE maquina SET latitud = :lat, longitud = :lon, geo_origen = 'DIRECCION', geo_fecha = NOW()
				WHERE id_punto = :id AND latitud IS NULL""").param("lat", coord[0]).param("lon", coord[1]).param("id", p.idPunto()).update();
			ubicados++;
		}
		return new Resultado(puntos.size(), ubicados, puntos.size() - ubicados, detalle);
	}

	@SuppressWarnings("unchecked")
	private double[] consultar(String direccion, String clave) {
		try {
			Map<String, Object> r = http.get()
				.uri("https://maps.googleapis.com/maps/api/geocode/json?address={a}&region=co&key={k}", direccion, clave)
				.retrieve().body(Map.class);
			if (r == null || !"OK".equals(r.get("status"))) {
				return null;
			}
			var resultados = (List<Map<String, Object>>) r.get("results");
			var loc = (Map<String, Object>) ((Map<String, Object>) resultados.get(0).get("geometry")).get("location");
			return new double[] { ((Number) loc.get("lat")).doubleValue(), ((Number) loc.get("lng")).doubleValue() };
		}
		catch (RuntimeException e) {
			log.warn("Geocodificación fallida: {}", e.getMessage());
			return null;
		}
	}
}
