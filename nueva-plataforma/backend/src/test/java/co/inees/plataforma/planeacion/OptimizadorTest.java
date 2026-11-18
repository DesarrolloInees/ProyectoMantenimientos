package co.inees.plataforma.planeacion;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

import java.util.ArrayList;
import java.util.List;
import java.util.Set;
import java.util.stream.Collectors;

import org.junit.jupiter.api.Test;

class OptimizadorTest {

	private final Optimizador opt = new Optimizador(25, 1.35);

	private static Optimizador.Servicio s(int id, double lat, double lon, int prioridad) {
		return new Optimizador.Servicio(id, lat, lon, 45, prioridad, 0, 0, null);
	}

	@Test
	void separaDosZonasEnDosMotorizados() {
		// Zona norte de Barranquilla y zona sur, a ~10 km.
		List<Optimizador.Servicio> servicios = List.of(
			s(1, 11.010, -74.810, 4), s(2, 11.015, -74.815, 4), s(3, 11.012, -74.805, 4),
			s(4, 10.920, -74.790, 4), s(5, 10.925, -74.795, 4), s(6, 10.918, -74.785, 4));
		var r = opt.optimizar(List.of(new Optimizador.Tecnico(1, null, null, 480), new Optimizador.Tecnico(2, null, null, 480)),
			servicios);
		assertTrue(r.sinCupo().isEmpty());
		Set<Set<Integer>> grupos = r.rutas().stream()
			.map(ruta -> ruta.paradas().stream().map(p -> p.servicio().clave()).collect(Collectors.toSet()))
			.collect(Collectors.toSet());
		assertEquals(Set.of(Set.of(1, 2, 3), Set.of(4, 5, 6)), grupos);
	}

	@Test
	void respetaLaJornadaYDejaSinCupoLaMenorPrioridad() {
		// 6 servicios de 45 min en una jornada de 200 min: caben 3 o 4; los de prioridad 1 deben entrar.
		List<Optimizador.Servicio> servicios = new ArrayList<>();
		for (int i = 1; i <= 6; i++) {
			servicios.add(s(i, 11.0 + i * 0.002, -74.8, i <= 2 ? 1 : 4));
		}
		var r = opt.optimizar(List.of(new Optimizador.Tecnico(1, 11.0, -74.8, 200)), servicios);
		Set<Integer> asignados = r.rutas().get(0).paradas().stream().map(p -> p.servicio().clave()).collect(Collectors.toSet());
		assertTrue(asignados.containsAll(Set.of(1, 2)), "las solicitudes de Prosegur siempre entran primero");
		assertTrue(r.rutas().get(0).minutos() <= 200);
		assertEquals(6, asignados.size() + r.sinCupo().size());
	}

	/** Mantenimiento vencido que aún no es orden (clave negativa) con sus días de atraso. */
	private static Optimizador.Servicio vencida(int id, double lat, double lon, int prioridad, long diasAtraso) {
		return new Optimizador.Servicio(-id, lat, lon, 45, prioridad, 0, diasAtraso, null);
	}

	@Test
	void elRellenoPrefiereLoCercanoAunqueLoLejanoTengaAlgoMasDeAtraso() {
		// Jornada para ~3 servicios. Tres vencidas a 1 km (40 días) y tres a ~25 km (60 días): deben entrar las cercanas.
		List<Optimizador.Servicio> servicios = List.of(
			vencida(1, 4.651, -74.060, 4, 40), vencida(2, 4.655, -74.062, 4, 40), vencida(3, 4.649, -74.058, 4, 40),
			vencida(4, 4.860, -74.030, 4, 60), vencida(5, 4.862, -74.032, 4, 60), vencida(6, 4.858, -74.028, 4, 60));
		var r = opt.optimizar(List.of(new Optimizador.Tecnico(1, 4.650, -74.060, 200)), servicios);
		Set<Integer> asignados = r.rutas().get(0).paradas().stream().map(p -> p.servicio().clave()).collect(Collectors.toSet());
		assertEquals(Set.of(-1, -2, -3), asignados);
	}

	@Test
	void laVipVencidaEntraAntesQueLaNormalAunqueEsteMasLejos() {
		// Cupo para un solo servicio: la VIP (prioridad 3) a 5 km gana a la normal (prioridad 4) a 300 m.
		List<Optimizador.Servicio> servicios = List.of(vencida(1, 4.6527, -74.0600, 4, 30), vencida(2, 4.6950, -74.0600, 3, 5));
		var r = opt.optimizar(List.of(new Optimizador.Tecnico(1, 4.650, -74.060, 70)), servicios);
		List<Integer> asignados = r.rutas().get(0).paradas().stream().map(p -> p.servicio().clave()).toList();
		assertEquals(List.of(-2), asignados);
	}

	@Test
	void loMuyAtrasadoNoSeOlvidaPorEstarUnPocoMasLejos() {
		// Cupo para uno: una vencida con 120 días a ~6 km gana a una de 3 días a 500 m (el premio compensa el desvío).
		List<Optimizador.Servicio> servicios = List.of(vencida(1, 4.6545, -74.060, 4, 3), vencida(2, 4.700, -74.075, 4, 120));
		var r = opt.optimizar(List.of(new Optimizador.Tecnico(1, 4.650, -74.060, 90)), servicios);
		List<Integer> asignados = r.rutas().get(0).paradas().stream().map(p -> p.servicio().clave()).toList();
		assertEquals(List.of(-2), asignados);
	}

	@Test
	void lasOrdenesRealesEntranAntesQueElRellenoDeSuMismaPrioridad() {
		// Una orden real normal lejos y una vencida de relleno cerca; cupo para una: entra la orden real.
		List<Optimizador.Servicio> servicios = List.of(vencida(1, 4.6505, -74.060, 4, 90), s(7, 4.700, -74.060, 4));
		var r = opt.optimizar(List.of(new Optimizador.Tecnico(1, 4.650, -74.060, 70)), servicios);
		List<Integer> asignados = r.rutas().get(0).paradas().stream().map(p -> p.servicio().clave()).toList();
		assertEquals(List.of(7), asignados);
	}

	@Test
	void dosOptOrdenaUnRecorridoCruzado() {
		double[] partida = { 11.0, -74.8 };
		List<Optimizador.Servicio> cruzado = List.of(s(1, 11.03, -74.8, 4), s(2, 11.01, -74.8, 4), s(3, 11.02, -74.8, 4));
		List<Integer> orden = opt.dosOpt(cruzado, partida).stream().map(Optimizador.Servicio::clave).toList();
		assertEquals(List.of(2, 3, 1), orden);
	}
}
