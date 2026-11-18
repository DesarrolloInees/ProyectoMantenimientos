package co.inees.plataforma.comun;

import java.util.Locale;

/** Cálculos de distancia y enlaces a Google Maps. */
public final class Geo {

	private static final double RADIO_TIERRA_KM = 6371.0;

	private Geo() {
	}

	/** Distancia en línea recta (fórmula de haversine), en km. */
	public static double km(double lat1, double lon1, double lat2, double lon2) {
		double dLat = Math.toRadians(lat2 - lat1);
		double dLon = Math.toRadians(lon2 - lon1);
		double a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
			+ Math.cos(Math.toRadians(lat1)) * Math.cos(Math.toRadians(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
		return 2 * RADIO_TIERRA_KM * Math.asin(Math.sqrt(a));
	}

	/** Enlace que abre Google Maps con el recorrido en moto desde la ubicación actual (o un origen) hasta el destino. */
	public static String enlaceMaps(Double latOrigen, Double lonOrigen, double latDestino, double lonDestino) {
		StringBuilder url = new StringBuilder("https://www.google.com/maps/dir/?api=1");
		if (latOrigen != null && lonOrigen != null) {
			url.append(String.format(Locale.ROOT, "&origin=%.6f,%.6f", latOrigen, lonOrigen));
		}
		url.append(String.format(Locale.ROOT, "&destination=%.6f,%.6f", latDestino, lonDestino));
		url.append("&travelmode=two-wheeler");
		return url.toString();
	}
}
