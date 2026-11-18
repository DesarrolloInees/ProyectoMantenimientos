package co.inees.plataforma.planeacion;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.junit.jupiter.api.Assertions.assertTrue;

import java.io.IOException;
import java.net.InetSocketAddress;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.atomic.AtomicInteger;

import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import com.sun.net.httpserver.HttpServer;

import co.inees.plataforma.comun.Geo;
import co.inees.plataforma.config.AppProperties;

/**
 * Distancias por carretera contra un OSRM simulado que responde el mismo JSON que el real (/table/v1/driving). En el
 * simulado hay un «río» en la longitud -74.075: cruzarlo obliga a ir hasta un puente y suma 8 km.
 */
class DistanciasVialesTest {

	private static final double RIO = -74.075, DESVIO_KM = 8;

	private HttpServer servidor;
	private final AtomicInteger consultas = new AtomicInteger();
	private int maxCoordenadasVistas;

	@BeforeEach
	void arrancar() throws IOException {
		servidor = HttpServer.create(new InetSocketAddress("127.0.0.1", 0), 0);
		servidor.createContext("/table/v1/driving/", ex -> {
			consultas.incrementAndGet();
			String ruta = ex.getRequestURI().getPath().substring("/table/v1/driving/".length());
			String[] coords = ruta.split(";");
			maxCoordenadasVistas = Math.max(maxCoordenadasVistas, coords.length);
			String query = ex.getRequestURI().getQuery();
			List<Integer> fuentes = indices(query, "sources"), destinos = indices(query, "destinations");
			StringBuilder json = new StringBuilder("{\"code\":\"Ok\",\"distances\":[");
			for (int a = 0; a < fuentes.size(); a++) {
				json.append(a == 0 ? "[" : ",[");
				for (int b = 0; b < destinos.size(); b++) {
					double[] p = lonLat(coords[fuentes.get(a)]), q = lonLat(coords[destinos.get(b)]);
					json.append(b == 0 ? "" : ",").append(metros(p, q));
				}
				json.append("]");
			}
			byte[] cuerpo = json.append("]}").toString().getBytes(StandardCharsets.UTF_8);
			ex.getResponseHeaders().add("Content-Type", "application/json");
			ex.sendResponseHeaders(200, cuerpo.length);
			ex.getResponseBody().write(cuerpo);
			ex.close();
		});
		servidor.start();
	}

	@AfterEach
	void parar() {
		servidor.stop(0);
	}

	/** Metros por "carretera" en el simulado: 1.2 × línea recta, más el desvío si cruza el río. */
	private static double metros(double[] lonLatA, double[] lonLatB) {
		double km = Geo.km(lonLatA[1], lonLatA[0], lonLatB[1], lonLatB[0]) * 1.2;
		if ((lonLatA[0] < RIO) != (lonLatB[0] < RIO)) {
			km += DESVIO_KM;
		}
		return Math.round(km * 1000);
	}

	private static double[] lonLat(String c) {
		String[] p = c.split(",");
		return new double[] { Double.parseDouble(p[0]), Double.parseDouble(p[1]) };
	}

	private static List<Integer> indices(String query, String nombre) {
		for (String par : query.split("&")) {
			if (par.startsWith(nombre + "=")) {
				return java.util.Arrays.stream(par.substring(nombre.length() + 1).split(";")).map(Integer::valueOf).toList();
			}
		}
		throw new IllegalArgumentException("falta " + nombre);
	}

	private DistanciasViales distancias(String url, int maxTabla) {
		return new DistanciasViales(new AppProperties(null, null, null, null, null, null, null, null,
			new AppProperties.Osrm(url, maxTabla)));
	}

	private String url() {
		return "http://127.0.0.1:" + servidor.getAddress().getPort();
	}

	@Test
	void sinOsrmConfiguradoSigueLaLineaRecta() {
		assertNull(distancias("", 100).matriz(List.of(new double[] { 4.6, -74.07 }), 1.35));
	}

	@Test
	void laRutaEvitaCruzarElRioAunqueEnLineaRectaSeaMasCerca() {
		// Partida al oeste del río. A está a 1,1 km pero al otro lado; B y C están del mismo lado, a 2 y 2,5 km.
		double[] partida = { 4.650, -74.080 };
		var a = new Optimizador.Servicio(1, 4.650, -74.070, 45, 4, 0, 0, null);
		var b = new Optimizador.Servicio(2, 4.668, -74.080, 45, 4, 0, 0, null);
		var c = new Optimizador.Servicio(3, 4.672, -74.082, 45, 4, 0, 0, null);
		List<Optimizador.Servicio> servicios = List.of(a, b, c);
		List<double[]> puntos = new ArrayList<>(List.of(partida));
		servicios.forEach(s -> puntos.add(new double[] { s.lat(), s.lon() }));

		var tec = List.of(new Optimizador.Tecnico(1, partida[0], partida[1], 480));
		var recta = new Optimizador(25, 1.35).optimizar(tec, servicios).rutas().get(0);
		var vial = new Optimizador(25, 1.35, 0.25, distancias(url(), 100).matriz(puntos, 1.35)).optimizar(tec, servicios)
			.rutas().get(0);

		// En línea recta va primero a A (el más cercano) y cruza el río dos veces; por carretera lo deja para el final.
		assertEquals(1, recta.paradas().get(0).servicio().clave());
		List<Integer> ordenVial = vial.paradas().stream().map(p -> p.servicio().clave()).toList();
		assertEquals(1, ordenVial.get(2), "por carretera A debe quedar de última: " + ordenVial);
		// Los km de la ruta son los de la carretera (incluyen el desvío al puente una sola vez).
		double esperado = 0;
		double[] desde = partida;
		for (var p : vial.paradas()) {
			double[] hasta = { p.servicio().lat(), p.servicio().lon() };
			esperado += metros(new double[] { desde[1], desde[0] }, new double[] { hasta[1], hasta[0] }) / 1000.0;
			desde = hasta;
		}
		assertEquals(esperado, vial.km(), 0.01);
		assertTrue(vial.km() > DESVIO_KM && vial.km() < 2 * DESVIO_KM);
	}

	@Test
	void pideLaMatrizEnBloquesSinPasarElLimiteDelServidor() {
		List<double[]> puntos = new ArrayList<>();
		for (int i = 0; i < 7; i++) {
			puntos.add(new double[] { 4.60 + i * 0.01, -74.06 - i * 0.005 });
		}
		var m = (MatrizVial) distancias(url(), 4).matriz(puntos, 1.35);
		assertEquals(49, m.paresConVia());
		assertTrue(maxCoordenadasVistas <= 4, "una consulta llevó " + maxCoordenadasVistas + " coordenadas");
		assertEquals(16, consultas.get()); // bloques de 2 × 2 sobre 7 puntos: 4 × 4
		for (double[] p : puntos) {
			for (double[] q : puntos) {
				double km = m.km(p[0], p[1], q[0], q[1]);
				assertEquals(p == q ? 0 : metros(new double[] { p[1], p[0] }, new double[] { q[1], q[0] }) / 1000.0, km, 0.001);
			}
		}
		assertEquals(0, m.uso()[1]); // ninguna usó la línea recta
	}

	@Test
	void siOsrmNoRespondeUsaLaLineaRectaPorElFactor() {
		servidor.stop(0);
		double[] p = { 4.60, -74.06 }, q = { 4.62, -74.07 };
		var d = distancias(url(), 100).matriz(List.of(p, q), 1.35);
		assertEquals(Geo.km(p[0], p[1], q[0], q[1]) * 1.35, d.km(p[0], p[1], q[0], q[1]), 1e-9);
	}

	@Test
	void unPuntoQueNoSePidioUsaLaLineaRecta() {
		double[] p = { 4.60, -74.06 }, q = { 4.62, -74.07 }, otro = { 4.70, -74.05 };
		var d = distancias(url(), 100).matriz(List.of(p, q), 1.35);
		assertEquals(Geo.km(p[0], p[1], otro[0], otro[1]) * 1.35, d.km(p[0], p[1], otro[0], otro[1]), 1e-9);
	}
}
