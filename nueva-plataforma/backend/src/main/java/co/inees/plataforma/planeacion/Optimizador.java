package co.inees.plataforma.planeacion;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import co.inees.plataforma.comun.Geo;

/**
 * Arma una ruta por motorizado a partir de la bolsa de servicios.
 *
 * <ol>
 * <li>Los servicios se recorren por prioridad (1 solicitud Prosegur, 2 arrastrado, 3 VIP, 4 normal), luego por veces
 * reprogramado y por días de atraso.</li>
 * <li>Cada servicio entra en la ruta y en la posición donde menos alarga el recorrido (inserción más barata), solo si
 * todavía cabe en la jornada del motorizado. Así las rutas quedan compactas y las prioridades altas entran primero.</li>
 * <li>Los servicios de relleno (mantenimientos vencidos que todavía no son orden, clave negativa) entran después de los
 * reales de su misma prioridad y no en orden estricto de atraso: en cada vuelta se elige el que menos alarga alguna
 * ruta, descontando {@code kmPorDiaAtraso} km por cada día de atraso (hasta {@value #TOPE_DIAS_ATRASO} días). Así se
 * llenan las rutas con lo cercano sin olvidar lo muy atrasado.</li>
 * <li>Cada ruta se mejora con 2-opt para recorrer la menor distancia.</li>
 * </ol>
 * Si un motorizado no tiene punto de partida se usa el centro de un grupo de servicios (k-means), de modo que cada uno
 * trabaje una zona distinta. Lo que no cabe queda en la bolsa para el día siguiente.
 */
public class Optimizador {

	public record Tecnico(int id, Double latPartida, Double lonPartida, int capacidadMin) {
	}

	public record Servicio(int clave, double lat, double lon, int minutosServicio, int prioridad, int vecesReprogramada,
		long diasAtraso, Integer tecnicoFijado) {

		/** Mantenimiento vencido que todavía no es una orden: solo se crea si le cabe a alguien. */
		public boolean esRelleno() {
			return clave < 0;
		}
	}

	public record Parada(Servicio servicio, double kmTramo, int minutosTramo, int minutoLlegada) {
	}

	public record Ruta(Tecnico tecnico, double latPartida, double lonPartida, List<Parada> paradas, double km, int minutos) {
	}

	public record Resultado(List<Ruta> rutas, List<Servicio> sinCupo) {
	}

	/** Km por carretera de un punto a otro (puede ser distinto de ida y de vuelta por las vías de un solo sentido). */
	@FunctionalInterface
	public interface Distancias {
		double km(double lat1, double lon1, double lat2, double lon2);
	}

	/** Días de atraso a partir de los cuales ya no suma más premio. */
	public static final int TOPE_DIAS_ATRASO = 120;

	private final double velocidadKmh;
	private final double factorVial;
	private final double kmPorDiaAtraso;
	/** Distancias reales por carretera (OSRM); null = línea recta × factor vial. */
	private final Distancias distancias;

	public Optimizador(double velocidadKmh, double factorVial) {
		this(velocidadKmh, factorVial, 0.25);
	}

	public Optimizador(double velocidadKmh, double factorVial, double kmPorDiaAtraso) {
		this(velocidadKmh, factorVial, kmPorDiaAtraso, null);
	}

	public Optimizador(double velocidadKmh, double factorVial, double kmPorDiaAtraso, Distancias distancias) {
		this.velocidadKmh = velocidadKmh;
		this.factorVial = factorVial;
		this.kmPorDiaAtraso = kmPorDiaAtraso;
		this.distancias = distancias;
	}

	public double kmVial(double lat1, double lon1, double lat2, double lon2) {
		return distancias != null ? distancias.km(lat1, lon1, lat2, lon2) : Geo.km(lat1, lon1, lat2, lon2) * factorVial;
	}

	public int minutosViaje(double km) {
		return (int) Math.ceil(km / velocidadKmh * 60.0);
	}

	public Resultado optimizar(List<Tecnico> tecnicos, List<Servicio> servicios) {
		if (tecnicos.isEmpty()) {
			return new Resultado(List.of(), List.copyOf(servicios));
		}
		double[][] partidas = partidas(tecnicos, servicios);
		List<List<Servicio>> asignadas = new ArrayList<>();
		for (int i = 0; i < tecnicos.size(); i++) {
			asignadas.add(new ArrayList<>());
		}
		Map<Integer, Integer> indicePorTecnico = new HashMap<>();
		for (int i = 0; i < tecnicos.size(); i++) {
			indicePorTecnico.put(tecnicos.get(i).id(), i);
		}

		List<Servicio> ordenados = new ArrayList<>(servicios);
		ordenados.sort(Comparator.comparingInt(Servicio::prioridad)
			.thenComparing(Comparator.comparingInt(Servicio::vecesReprogramada).reversed())
			.thenComparing(Comparator.comparingLong(Servicio::diasAtraso).reversed()));

		List<Servicio> sinCupo = new ArrayList<>();
		// Primero los fijados a mano: van con su técnico aunque no quepan.
		for (Servicio s : ordenados) {
			Integer idx = s.tecnicoFijado() == null ? null : indicePorTecnico.get(s.tecnicoFijado());
			if (idx != null) {
				insertarMasBarato(asignadas.get(idx), partidas[idx], s);
			}
		}
		// Por cada prioridad: primero los servicios reales en orden estricto, luego el relleno por cercanía.
		java.util.TreeMap<Integer, List<Servicio>> rellenoPorPrioridad = new java.util.TreeMap<>();
		for (Servicio s : ordenados) {
			if (s.esRelleno() && (s.tecnicoFijado() == null || !indicePorTecnico.containsKey(s.tecnicoFijado()))) {
				rellenoPorPrioridad.computeIfAbsent(s.prioridad(), k -> new ArrayList<>()).add(s);
			}
		}
		for (Servicio s : ordenados) {
			if (s.tecnicoFijado() != null && indicePorTecnico.containsKey(s.tecnicoFijado())) {
				continue;
			}
			if (s.esRelleno()) {
				continue;
			}
			// Antes del primer real de esta prioridad se completa el relleno de las prioridades más altas.
			var altas = rellenoPorPrioridad.headMap(s.prioridad());
			for (var e : new ArrayList<>(altas.entrySet())) {
				rellenar(e.getValue(), tecnicos, asignadas, partidas, sinCupo);
				rellenoPorPrioridad.remove(e.getKey());
			}
			int mejor = -1, mejorPos = -1;
			double mejorCosto = Double.MAX_VALUE;
			for (int t = 0; t < tecnicos.size(); t++) {
				List<Servicio> ruta = asignadas.get(t);
				int[] pos = new int[1];
				double delta = costoInsercion(ruta, partidas[t], s, pos);
				int minutosConNuevo = minutosRuta(ruta, partidas[t]) + minutosViaje(delta) + s.minutosServicio();
				if (minutosConNuevo <= tecnicos.get(t).capacidadMin() && delta < mejorCosto) {
					mejorCosto = delta;
					mejor = t;
					mejorPos = pos[0];
				}
			}
			if (mejor < 0) {
				sinCupo.add(s);
			}
			else {
				asignadas.get(mejor).add(mejorPos, s);
			}
		}
		for (List<Servicio> relleno : rellenoPorPrioridad.values()) {
			rellenar(relleno, tecnicos, asignadas, partidas, sinCupo);
		}

		List<Ruta> rutas = new ArrayList<>();
		for (int t = 0; t < tecnicos.size(); t++) {
			List<Servicio> ordenRuta = dosOpt(asignadas.get(t), partidas[t]);
			rutas.add(construir(tecnicos.get(t), partidas[t], ordenRuta));
		}
		return new Resultado(rutas, sinCupo);
	}

	/**
	 * Agrega servicios de relleno de una misma prioridad: en cada vuelta entra el que menos alarga alguna ruta (con el
	 * premio por atraso) y que todavía cabe en la jornada; lo que no cabe en ninguna queda sin cupo.
	 */
	private void rellenar(List<Servicio> candidatos, List<Tecnico> tecnicos, List<List<Servicio>> asignadas, double[][] partidas,
		List<Servicio> sinCupo) {
		List<Servicio> pendientes = new ArrayList<>(candidatos);
		while (!pendientes.isEmpty()) {
			int[] minutosActuales = new int[tecnicos.size()];
			for (int t = 0; t < tecnicos.size(); t++) {
				minutosActuales[t] = minutosRuta(asignadas.get(t), partidas[t]);
			}
			Servicio elegido = null;
			int tElegido = -1, posElegida = -1;
			double mejorPuntaje = Double.MAX_VALUE;
			int[] pos = new int[1];
			for (Servicio s : pendientes) {
				double premio = kmPorDiaAtraso * Math.min(Math.max(s.diasAtraso(), 0), TOPE_DIAS_ATRASO);
				for (int t = 0; t < tecnicos.size(); t++) {
					double delta = costoInsercion(asignadas.get(t), partidas[t], s, pos);
					if (minutosActuales[t] + minutosViaje(delta) + s.minutosServicio() > tecnicos.get(t).capacidadMin()) {
						continue;
					}
					double puntaje = delta - premio;
					if (puntaje < mejorPuntaje) {
						mejorPuntaje = puntaje;
						elegido = s;
						tElegido = t;
						posElegida = pos[0];
					}
				}
			}
			if (elegido == null) {
				sinCupo.addAll(pendientes);
				return;
			}
			asignadas.get(tElegido).add(posElegida, elegido);
			pendientes.remove(elegido);
		}
	}

	/** Recalcula distancias y horas de una ruta en el orden dado (para cambios manuales). */
	public Ruta construir(Tecnico tecnico, double[] partida, List<Servicio> orden) {
		List<Parada> paradas = new ArrayList<>();
		double lat = partida[0], lon = partida[1], kmTotal = 0;
		int minuto = 0;
		for (Servicio s : orden) {
			double km = kmVial(lat, lon, s.lat(), s.lon());
			int viaje = minutosViaje(km);
			minuto += viaje;
			paradas.add(new Parada(s, km, viaje, minuto));
			minuto += s.minutosServicio();
			kmTotal += km;
			lat = s.lat();
			lon = s.lon();
		}
		return new Ruta(tecnico, partida[0], partida[1], paradas, kmTotal, minuto);
	}

	/** Reordena con 2-opt (recorrido abierto que sale de la partida) para acortar la distancia. */
	public List<Servicio> dosOpt(List<Servicio> ruta, double[] partida) {
		List<Servicio> mejor = new ArrayList<>(ruta);
		boolean mejoro = mejor.size() > 2;
		int vueltas = 0;
		while (mejoro && vueltas++ < 50) {
			mejoro = false;
			for (int i = 0; i < mejor.size() - 1; i++) {
				for (int k = i + 1; k < mejor.size(); k++) {
					double antes = tramo(mejor, partida, i - 1, i) + (k + 1 < mejor.size() ? tramo(mejor, partida, k, k + 1) : 0);
					double despues = tramo(mejor, partida, i - 1, k) + (k + 1 < mejor.size() ? tramo(mejor, partida, i, k + 1) : 0);
					if (despues + 1e-9 < antes) {
						java.util.Collections.reverse(mejor.subList(i, k + 1));
						mejoro = true;
					}
				}
			}
		}
		return mejor;
	}

	private double tramo(List<Servicio> r, double[] partida, int a, int b) {
		double latA = a < 0 ? partida[0] : r.get(a).lat(), lonA = a < 0 ? partida[1] : r.get(a).lon();
		return kmVial(latA, lonA, r.get(b).lat(), r.get(b).lon());
	}

	private void insertarMasBarato(List<Servicio> ruta, double[] partida, Servicio s) {
		int[] pos = new int[1];
		costoInsercion(ruta, partida, s, pos);
		ruta.add(pos[0], s);
	}

	/** Km adicionales al insertar s en la mejor posición; deja la posición en pos[0]. */
	private double costoInsercion(List<Servicio> ruta, double[] partida, Servicio s, int[] pos) {
		double mejor = Double.MAX_VALUE;
		for (int i = 0; i <= ruta.size(); i++) {
			double latA = i == 0 ? partida[0] : ruta.get(i - 1).lat(), lonA = i == 0 ? partida[1] : ruta.get(i - 1).lon();
			double costo = kmVial(latA, lonA, s.lat(), s.lon());
			if (i < ruta.size()) {
				Servicio b = ruta.get(i);
				costo += kmVial(s.lat(), s.lon(), b.lat(), b.lon()) - kmVial(latA, lonA, b.lat(), b.lon());
			}
			if (costo < mejor) {
				mejor = costo;
				pos[0] = i;
			}
		}
		return mejor;
	}

	private int minutosRuta(List<Servicio> ruta, double[] partida) {
		double lat = partida[0], lon = partida[1];
		int minutos = 0;
		for (Servicio s : ruta) {
			minutos += minutosViaje(kmVial(lat, lon, s.lat(), s.lon())) + s.minutosServicio();
			lat = s.lat();
			lon = s.lon();
		}
		return minutos;
	}

	/** Partida de cada técnico: la suya o el centro de un grupo de servicios (k-means). */
	private double[][] partidas(List<Tecnico> tecnicos, List<Servicio> servicios) {
		double[][] p = new double[tecnicos.size()][];
		List<Integer> sinPartida = new ArrayList<>();
		for (int i = 0; i < tecnicos.size(); i++) {
			Tecnico t = tecnicos.get(i);
			if (t.latPartida() != null && t.lonPartida() != null) {
				p[i] = new double[] { t.latPartida(), t.lonPartida() };
			}
			else {
				sinPartida.add(i);
			}
		}
		if (!sinPartida.isEmpty()) {
			double[][] centros = kMeans(servicios, sinPartida.size());
			for (int j = 0; j < sinPartida.size(); j++) {
				p[sinPartida.get(j)] = centros[j];
			}
		}
		return p;
	}

	double[][] kMeans(List<Servicio> servicios, int k) {
		double[][] centros = new double[k][];
		if (servicios.isEmpty()) {
			for (int i = 0; i < k; i++) {
				centros[i] = new double[] { 4.65, -74.1 };
			}
			return centros;
		}
		// k-means++ determinístico: el primero es el de mayor prioridad; luego el más lejano a los elegidos.
		Servicio primero = servicios.stream()
			.min(Comparator.comparingInt(Servicio::prioridad).thenComparingInt(Servicio::clave)).orElseThrow();
		centros[0] = new double[] { primero.lat(), primero.lon() };
		for (int c = 1; c < k; c++) {
			Servicio lejano = null;
			double maxDist = -1;
			for (Servicio s : servicios) {
				double d = Double.MAX_VALUE;
				for (int j = 0; j < c; j++) {
					d = Math.min(d, Geo.km(s.lat(), s.lon(), centros[j][0], centros[j][1]));
				}
				if (d > maxDist) {
					maxDist = d;
					lejano = s;
				}
			}
			centros[c] = new double[] { lejano.lat(), lejano.lon() };
		}
		for (int iter = 0; iter < 25; iter++) {
			double[][] suma = new double[k][3];
			for (Servicio s : servicios) {
				int mejor = 0;
				double d = Double.MAX_VALUE;
				for (int j = 0; j < k; j++) {
					double dj = Geo.km(s.lat(), s.lon(), centros[j][0], centros[j][1]);
					if (dj < d) {
						d = dj;
						mejor = j;
					}
				}
				suma[mejor][0] += s.lat();
				suma[mejor][1] += s.lon();
				suma[mejor][2]++;
			}
			for (int j = 0; j < k; j++) {
				if (suma[j][2] > 0) {
					centros[j] = new double[] { suma[j][0] / suma[j][2], suma[j][1] / suma[j][2] };
				}
			}
		}
		return centros;
	}
}
