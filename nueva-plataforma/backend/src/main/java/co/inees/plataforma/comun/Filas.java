package co.inees.plataforma.comun;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/** Convierte filas genéricas de JDBC a valores que el navegador entiende (fechas ISO, horas HH:mm:ss). */
public final class Filas {

	private Filas() {
	}

	public static Map<String, Object> normalizar(Map<String, Object> fila) {
		Map<String, Object> r = new LinkedHashMap<>();
		fila.forEach((k, v) -> r.put(k, valor(v)));
		return r;
	}

	public static List<Map<String, Object>> normalizar(List<Map<String, Object>> filas) {
		return filas.stream().map(Filas::normalizar).toList();
	}

	public static Object valor(Object v) {
		return switch (v) {
			case java.sql.Date d -> d.toLocalDate();
			case java.sql.Timestamp t -> t.toLocalDateTime();
			case java.sql.Time t -> t.toLocalTime();
			case null, default -> v;
		};
	}
}
