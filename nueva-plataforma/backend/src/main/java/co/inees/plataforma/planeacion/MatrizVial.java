package co.inees.plataforma.planeacion;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collection;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import co.inees.plataforma.comun.Geo;

/**
 * Km por carretera entre un conjunto fijo de puntos (la matriz que devuelve OSRM). Los puntos se identifican por sus
 * coordenadas redondeadas a 5 decimales (~1 m). Si un par no está en la matriz (punto que no se pidió, OSRM caído o
 * sin vía entre los dos) se usa la línea recta × factor vial, como antes.
 */
public final class MatrizVial implements Optimizador.Distancias {

	private final Map<Long, Integer> indice = new HashMap<>();
	private final List<double[]> distintos = new ArrayList<>();
	private final float[][] km;
	private final double factorVial;
	private long consultas, respaldos;

	MatrizVial(Collection<double[]> puntos, double factorVial) {
		this.factorVial = factorVial;
		for (double[] p : puntos) {
			if (indice.putIfAbsent(llave(p[0], p[1]), distintos.size()) == null) {
				distintos.add(p);
			}
		}
		km = new float[distintos.size()][distintos.size()];
		for (float[] fila : km) {
			Arrays.fill(fila, Float.NaN);
		}
	}

	/** Cantidad de puntos distintos. */
	int tamano() {
		return km.length;
	}

	/** Punto i como {lat, lon}. */
	double[] punto(int i) {
		return distintos.get(i);
	}

	void poner(int origen, int destino, double kmVia) {
		km[origen][destino] = (float) kmVia;
	}

	/** Pares que quedaron con distancia por carretera. */
	long paresConVia() {
		long n = 0;
		for (float[] fila : km) {
			for (float v : fila) {
				if (!Float.isNaN(v)) {
					n++;
				}
			}
		}
		return n;
	}

	/** Cuántas veces se pidió una distancia y cuántas tuvieron que usar la línea recta. */
	long[] uso() {
		return new long[] { consultas, respaldos };
	}

	@Override
	public double km(double lat1, double lon1, double lat2, double lon2) {
		consultas++;
		Integer a = indice.get(llave(lat1, lon1)), b = indice.get(llave(lat2, lon2));
		if (a != null && b != null) {
			if (a.intValue() == b.intValue()) {
				return 0;
			}
			float v = km[a][b];
			if (!Float.isNaN(v)) {
				return v;
			}
		}
		respaldos++;
		return Geo.km(lat1, lon1, lat2, lon2) * factorVial;
	}

	static long llave(double lat, double lon) {
		long la = Math.round(lat * 1e5) + 9_000_000L, lo = Math.round(lon * 1e5) + 18_000_000L;
		return la * 40_000_000L + lo;
	}
}
