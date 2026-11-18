package co.inees.plataforma.comun;

import java.time.Duration;
import java.time.Instant;
import java.time.LocalTime;
import java.util.HashMap;
import java.util.Map;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.stereotype.Component;

/** Lee la tabla parametros (clave, valor) con una caché de un minuto. Las reglas se cambian sin tocar código. */
@Component
public class Parametros {

	public static final String ESPERA_MAX = "tiempo_max_espera_ingreso_min";
	public static final String JORNADA_MIN = "jornada_minutos_default";
	public static final String HORA_INICIO = "hora_inicio_jornada_default";
	public static final String VELOCIDAD = "velocidad_promedio_kmh";
	public static final String FACTOR_VIAL = "factor_ruta_vial";
	public static final String KM_POR_DIA_ATRASO = "km_por_dia_atraso";
	public static final String TIPO_SOLICITUD = "tipo_mantenimiento_solicitud_default";
	public static final String TIPO_PREVENTIVO = "tipo_mantenimiento_preventivo";
	public static final String SERIALES_BLOQUEAN = "seriales_distintos_bloquean";
	public static final String COLUMNAS_EXCEL = "excel_prosegur_columnas";
	public static final String TIEMPOS_MANT = "tiempos_mantenimiento_minutos";
	public static final String RECARGO_VIATICOS = "Recargo_Servicios_Interurbanos";
	public static final String DELEGACIONES_PRINCIPALES = "delegaciones_principales";
	public static final String CORREOS_LOGISTICA = "correos_alertas_logisticas";
	public static final String ALERTA_VISITAS = "alerta_visitas_7_dias";
	public static final String ALERTA_DESPLAZAMIENTO = "alerta_desplazamiento_min";
	public static final String META_DIA = "meta_servicios_dia";
	public static final String META_SABADO = "meta_servicios_sabado";
	public static final String META_NA_DIA = "meta_nueva_arquitectura_dia";
	public static final String META_NA_SABADO = "meta_nueva_arquitectura_sabado";
	public static final String MINUTOS_ALMUERZO = "minutos_almuerzo";
	public static final String ALERTAS_RETRASO = "alertas_retraso_telegram";
	public static final String VIGENCIA_CLAVE = "password_vigencia_dias";

	private final JdbcClient jdbc;
	private volatile Map<String, String> cache = Map.of();
	private volatile Instant leido = Instant.EPOCH;

	public Parametros(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	public String texto(String clave, String porDefecto) {
		String v = valores().get(clave);
		return v == null || v.isBlank() ? porDefecto : v.trim();
	}

	public int entero(String clave, int porDefecto) {
		try {
			return Integer.parseInt(texto(clave, String.valueOf(porDefecto)));
		}
		catch (NumberFormatException e) {
			return porDefecto;
		}
	}

	public double decimal(String clave, double porDefecto) {
		try {
			return Double.parseDouble(texto(clave, String.valueOf(porDefecto)));
		}
		catch (NumberFormatException e) {
			return porDefecto;
		}
	}

	public LocalTime hora(String clave, LocalTime porDefecto) {
		try {
			return LocalTime.parse(texto(clave, porDefecto.toString()));
		}
		catch (RuntimeException e) {
			return porDefecto;
		}
	}

	/** Duración estimada de un servicio según su tipo (parámetro tiempos_mantenimiento_minutos, en JSON). */
	public int minutosServicio(Integer idTipoMantenimiento) {
		String json = texto(TIEMPOS_MANT, "");
		if (idTipoMantenimiento != null) {
			var m = java.util.regex.Pattern.compile("\"" + idTipoMantenimiento + "\"\\s*:\\s*(\\d+)").matcher(json);
			if (m.find()) {
				return Integer.parseInt(m.group(1));
			}
		}
		return 45;
	}

	/** Lista de números separados por coma (p. ej. "1,2,3"). */
	public java.util.List<Integer> enteros(String clave, String porDefecto) {
		java.util.List<Integer> r = new java.util.ArrayList<>();
		for (String p : texto(clave, porDefecto).split("[,;\\s]+")) {
			try {
				r.add(Integer.parseInt(p.trim()));
			}
			catch (NumberFormatException e) {
				// se ignora el valor mal escrito
			}
		}
		return r;
	}

	/** Lista de textos separados por coma o punto y coma. */
	public java.util.List<String> lista(String clave, String porDefecto) {
		return java.util.Arrays.stream(texto(clave, porDefecto).split("[,;]+")).map(String::trim)
			.filter(x -> !x.isEmpty()).toList();
	}

	/** Todos los tiempos estimados por tipo de mantenimiento. */
	public java.util.Map<Integer, Integer> tiemposMantenimiento() {
		java.util.Map<Integer, Integer> r = new java.util.HashMap<>();
		var m = java.util.regex.Pattern.compile("\"(\\d+)\"\\s*:\\s*(\\d+)").matcher(texto(TIEMPOS_MANT, ""));
		while (m.find()) {
			r.put(Integer.parseInt(m.group(1)), Integer.parseInt(m.group(2)));
		}
		return r;
	}

	public void invalidar() {
		leido = Instant.EPOCH;
	}

	private Map<String, String> valores() {
		if (Duration.between(leido, Instant.now()).toSeconds() > 60) {
			Map<String, String> nuevo = new HashMap<>();
			jdbc.sql("SELECT clave, valor FROM parametros").query((rs, i) -> nuevo.put(rs.getString(1), rs.getString(2))).list();
			cache = nuevo;
			leido = Instant.now();
		}
		return cache;
	}
}
