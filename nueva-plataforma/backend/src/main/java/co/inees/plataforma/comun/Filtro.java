package co.inees.plataforma.comun;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.jdbc.core.simple.JdbcClient;

/**
 * Arma el WHERE de las consultas con filtros opcionales: una condición solo se agrega si su valor no es nulo ni vacío.
 *
 * <pre>
 * Filtro f = new Filtro().y("o.estado = 1").y("o.id_tecnico = :tec", "tec", idTecnico);
 * jdbc.sql("SELECT ... WHERE " + f.where()).params(f.params())
 * </pre>
 */
public class Filtro {

	private final List<String> condiciones = new ArrayList<>();
	private final Map<String, Object> params = new LinkedHashMap<>();

	/** Condición fija. */
	public Filtro y(String condicion) {
		condiciones.add(condicion);
		return this;
	}

	/** Condición con un parámetro; se omite si el valor está vacío. */
	public Filtro y(String condicion, String nombre, Object valor) {
		if (vacio(valor)) {
			return this;
		}
		condiciones.add(condicion);
		params.put(nombre, valor instanceof String s ? s.trim() : valor);
		return this;
	}

	/** Búsqueda de texto en varias columnas: (a LIKE :t OR b LIKE :t). */
	public Filtro texto(String nombre, String valor, String... columnas) {
		if (vacio(valor)) {
			return this;
		}
		List<String> partes = new ArrayList<>();
		for (String c : columnas) {
			partes.add(c + " LIKE :" + nombre);
		}
		condiciones.add("(" + String.join(" OR ", partes) + ")");
		params.put(nombre, "%" + valor.trim() + "%");
		return this;
	}

	public Filtro param(String nombre, Object valor) {
		params.put(nombre, valor);
		return this;
	}

	public String where() {
		return condiciones.isEmpty() ? "1 = 1" : String.join(" AND ", condiciones);
	}

	public Map<String, Object> params() {
		return params;
	}

	public JdbcClient.StatementSpec aplicar(JdbcClient.StatementSpec spec) {
		return spec.params(params);
	}

	private static boolean vacio(Object v) {
		return v == null || (v instanceof String s && s.isBlank());
	}
}
