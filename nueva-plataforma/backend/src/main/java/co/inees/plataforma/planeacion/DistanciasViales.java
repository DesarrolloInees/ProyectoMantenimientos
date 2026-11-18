package co.inees.plataforma.planeacion;

import java.time.Duration;
import java.util.ArrayList;
import java.util.Collection;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.StringJoiner;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.client.SimpleClientHttpRequestFactory;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestClient;

import co.inees.plataforma.config.AppProperties;

/**
 * Distancias por carretera con un servidor OSRM propio (OpenStreetMap, ver nueva-plataforma/osrm). Se pide la matriz
 * completa de los puntos de cada cálculo de rutas con el servicio /table, en bloques para no pasar el límite de
 * coordenadas del servidor ({@code --max-table-size}).
 * <p>
 * Solo se toma la distancia: el tiempo de viaje lo sigue poniendo {@code velocidad_promedio_kmh}, que está medido con
 * moto y tráfico; los tiempos de OSRM son de carro sin tráfico.
 * <p>
 * Si {@code OSRM_URL} no está configurada, o el servidor no responde, las rutas se arman como antes (línea recta ×
 * {@code factor_ruta_vial}) y queda un aviso en el log: generar rutas nunca falla por esto.
 */
@Service
public class DistanciasViales {

	private static final Logger log = LoggerFactory.getLogger(DistanciasViales.class);

	private final String url;
	private final int maxTabla;
	private final RestClient http;

	public DistanciasViales(AppProperties props) {
		var osrm = props.osrm();
		String u = osrm == null ? null : osrm.url();
		this.url = u == null || u.isBlank() ? null : u.replaceAll("/+$", "");
		this.maxTabla = osrm == null || osrm.maxTabla() == null || osrm.maxTabla() < 2 ? 100 : osrm.maxTabla();
		var fabrica = new SimpleClientHttpRequestFactory();
		fabrica.setConnectTimeout(Duration.ofSeconds(3));
		fabrica.setReadTimeout(Duration.ofSeconds(60));
		this.http = RestClient.builder().requestFactory(fabrica).build();
	}

	public boolean activo() {
		return url != null;
	}

	/**
	 * Matriz de km por carretera entre los puntos dados ({lat, lon}). Null si OSRM no está configurado (el optimizador
	 * usa entonces la línea recta × factor vial).
	 */
	public Optimizador.Distancias matriz(Collection<double[]> puntos, double factorVial) {
		if (url == null || puntos.isEmpty()) {
			return null;
		}
		MatrizVial m = new MatrizVial(puntos, factorVial);
		llenar(m);
		return m;
	}

	/** Llena la matriz por bloques de origen × destino; si OSRM falla deja el resto con la línea recta. */
	void llenar(MatrizVial m) {
		int n = m.tamano();
		if (n < 2) {
			return;
		}
		// Cada consulta lleva los orígenes y los destinos del bloque: a lo sumo maxTabla coordenadas.
		int bloque = Math.max(1, maxTabla / 2);
		long inicio = System.currentTimeMillis();
		int consultas = 0;
		try {
			for (int i0 = 0; i0 < n; i0 += bloque) {
				for (int j0 = 0; j0 < n; j0 += bloque) {
					tabla(m, i0, Math.min(n, i0 + bloque), j0, Math.min(n, j0 + bloque));
					consultas++;
				}
			}
			log.info("OSRM: {} puntos, {} consultas, {} pares por carretera en {} ms", n, consultas, m.paresConVia(),
				System.currentTimeMillis() - inicio);
		}
		catch (RuntimeException e) {
			log.warn("OSRM no respondió ({}): {} pares por carretera; el resto se calcula en línea recta × factor vial",
				e.getMessage(), m.paresConVia());
		}
	}

	@SuppressWarnings("unchecked")
	private void tabla(MatrizVial m, int i0, int i1, int j0, int j1) {
		// Coordenadas sin repetir (en el bloque diagonal orígenes y destinos son los mismos puntos).
		Map<Integer, Integer> posicion = new LinkedHashMap<>();
		List<Integer> fuentes = new ArrayList<>(), destinos = new ArrayList<>();
		for (int i = i0; i < i1; i++) {
			fuentes.add(posicion.computeIfAbsent(i, k -> posicion.size()));
		}
		for (int j = j0; j < j1; j++) {
			destinos.add(posicion.computeIfAbsent(j, k -> posicion.size()));
		}
		StringJoiner coords = new StringJoiner(";");
		for (int idx : posicion.keySet()) {
			double[] p = m.punto(idx);
			// OSRM recibe longitud,latitud
			coords.add(String.format(Locale.ROOT, "%.6f,%.6f", p[1], p[0]));
		}
		String uri = url + "/table/v1/driving/" + coords + "?annotations=distance&sources=" + unir(fuentes)
			+ "&destinations=" + unir(destinos);
		Map<String, Object> r = http.get().uri(java.net.URI.create(uri)).retrieve().body(Map.class);
		if (r == null || !"Ok".equals(r.get("code"))) {
			throw new IllegalStateException("respuesta " + (r == null ? "vacía" : r.get("code") + " " + r.get("message")));
		}
		List<List<Object>> distancias = (List<List<Object>>) r.get("distances");
		for (int a = 0; a < fuentes.size(); a++) {
			List<Object> fila = distancias.get(a);
			for (int b = 0; b < destinos.size(); b++) {
				Object metros = fila.get(b);
				if (metros instanceof Number num) {
					m.poner(i0 + a, j0 + b, num.doubleValue() / 1000.0);
				}
			}
		}
	}

	private static String unir(List<Integer> l) {
		StringJoiner s = new StringJoiner(";");
		l.forEach(i -> s.add(Integer.toString(i)));
		return s.toString();
	}
}
