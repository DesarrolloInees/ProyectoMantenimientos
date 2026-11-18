package co.inees.plataforma.admin;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Pattern;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.admin.Entidad.Campo;
import co.inees.plataforma.comun.Filas;
import co.inees.plataforma.comun.Filtro;
import co.inees.plataforma.comun.Parametros;
import co.inees.plataforma.config.NegocioException;

/** CRUD de todos los catálogos de Administración (permiso ADMINISTRACION). */
@RestController
@RequestMapping("/api/admin")
public class CrudController {

	public record Resumen(String id, String titulo) {
	}

	public record Pagina(List<Map<String, Object>> items, long total) {
	}

	private static final Pattern EMAIL = Pattern.compile("^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$");

	private final JdbcClient jdbc;
	private final Entidades entidades;
	private final Parametros parametros;

	public CrudController(JdbcClient jdbc, Entidades entidades, Parametros parametros) {
		this.jdbc = jdbc;
		this.entidades = entidades;
		this.parametros = parametros;
	}

	@GetMapping("/catalogos")
	public List<Resumen> catalogos() {
		return entidades.todas().stream().map(e -> new Resumen(e.id(), e.titulo())).toList();
	}

	@GetMapping("/{entidad}/meta")
	public Entidad meta(@PathVariable String entidad) {
		return entidades.obtener(entidad);
	}

	@GetMapping("/{entidad}")
	public Pagina listar(@PathVariable String entidad, @RequestParam(required = false) String texto,
		@RequestParam(defaultValue = "false") boolean inactivos, @RequestParam(defaultValue = "0") int pagina,
		@RequestParam(defaultValue = "50") int tamano) {
		Entidad e = entidades.obtener(entidad);
		Filtro f = new Filtro().texto("texto", texto, e.busqueda().toArray(String[]::new));
		if (e.activo() != null && !inactivos) {
			f.y("t." + e.activo() + " = 1");
		}
		long total = jdbc.sql("SELECT COUNT(*) FROM " + e.from() + " WHERE " + f.where()).params(f.params())
			.query(Long.class).single();
		int t = Math.min(Math.max(tamano, 1), 500);
		List<Map<String, Object>> filas = jdbc.sql(select(e) + " WHERE " + f.where() + " ORDER BY " + e.orden()
			+ " LIMIT " + t + " OFFSET " + Math.max(pagina, 0) * t).params(f.params()).query().listOfRows();
		return new Pagina(filas.stream().map(r -> salida(e, r)).toList(), total);
	}

	@GetMapping("/{entidad}/{id}")
	public Map<String, Object> obtener(@PathVariable String entidad, @PathVariable int id) {
		Entidad e = entidades.obtener(entidad);
		Map<String, Object> fila = jdbc.sql(select(e) + " WHERE t." + e.pk() + " = :id").param("id", id).query()
			.listOfRows().stream().findFirst().orElseThrow(() -> NegocioException.noEncontrado("El registro"));
		return salida(e, fila);
	}

	@PostMapping("/{entidad}")
	@Transactional
	public Map<String, Object> crear(@PathVariable String entidad, @RequestBody Map<String, Object> datos) {
		Entidad e = entidades.obtener(entidad);
		if (!e.permiteCrear()) {
			throw new NegocioException("No se pueden crear registros en " + e.titulo() + ".");
		}
		Map<String, Object> valores = validar(e, datos, true);
		if (e.activo() != null) {
			valores.putIfAbsent(e.activo(), 1);
		}
		if (e.id().equals("maquinas")) {
			valores.putIfAbsent("activo_operativo", 1);
		}
		String columnas = String.join(", ", valores.keySet());
		String marcas = String.join(", ", valores.keySet().stream().map(c -> ":" + c).toList());
		var llave = new GeneratedKeyHolder();
		jdbc.sql("INSERT INTO " + e.tabla() + " (" + columnas + ") VALUES (" + marcas + ")").params(valores).update(llave);
		despuesDeGuardar(e);
		return obtener(entidad, llave.getKey().intValue());
	}

	@PutMapping("/{entidad}/{id}")
	@Transactional
	public Map<String, Object> actualizar(@PathVariable String entidad, @PathVariable int id,
		@RequestBody Map<String, Object> datos) {
		Entidad e = entidades.obtener(entidad);
		Map<String, Object> valores = validar(e, datos, false);
		if (valores.isEmpty()) {
			return obtener(entidad, id);
		}
		String set = String.join(", ", valores.keySet().stream().map(c -> c + " = :" + c).toList());
		valores.put("__id", id);
		int n = jdbc.sql("UPDATE " + e.tabla() + " SET " + set + " WHERE " + e.pk() + " = :__id").params(valores).update();
		if (n == 0) {
			throw NegocioException.noEncontrado("El registro");
		}
		despuesDeGuardar(e);
		return obtener(entidad, id);
	}

	/** Borrado lógico (estado = 0) cuando la tabla lo permite; si no, borrado físico. */
	@DeleteMapping("/{entidad}/{id}")
	@Transactional
	public void eliminar(@PathVariable String entidad, @PathVariable int id) {
		Entidad e = entidades.obtener(entidad);
		if (!e.permiteBorrar()) {
			throw new NegocioException("Los registros de " + e.titulo() + " no se eliminan.");
		}
		String sql = e.activo() != null
			? "UPDATE " + e.tabla() + " SET " + e.activo() + " = 0 WHERE " + e.pk() + " = :id"
			: "DELETE FROM " + e.tabla() + " WHERE " + e.pk() + " = :id";
		jdbc.sql(sql).param("id", id).update();
	}

	@PostMapping("/{entidad}/{id}/activar")
	public Map<String, Object> activar(@PathVariable String entidad, @PathVariable int id) {
		Entidad e = entidades.obtener(entidad);
		if (e.activo() == null) {
			throw new NegocioException("Este catálogo no maneja estado.");
		}
		jdbc.sql("UPDATE " + e.tabla() + " SET " + e.activo() + " = 1 WHERE " + e.pk() + " = :id").param("id", id).update();
		return obtener(entidad, id);
	}

	// ------------------------------------------------------------------ apoyo
	private static String select(Entidad e) {
		List<String> cols = new ArrayList<>();
		cols.add("t." + e.pk() + " AS id");
		e.campos().forEach(c -> cols.add("t." + c.nombre()));
		cols.addAll(e.extras());
		return "SELECT " + String.join(", ", cols) + " FROM " + e.from();
	}

	/** Normaliza los valores que salen de la base: booleanos como true/false y fechas como texto ISO. */
	private static Map<String, Object> salida(Entidad e, Map<String, Object> fila) {
		Map<String, Object> r = Filas.normalizar(fila);
		for (Campo c : e.campos()) {
			if (c.tipo() == Entidad.Tipo.BOOLEANO) {
				Object v = r.get(c.nombre());
				r.put(c.nombre(), v instanceof Boolean b ? b : v instanceof Number n && n.intValue() != 0);
			}
		}
		return r;
	}

	private Map<String, Object> validar(Entidad e, Map<String, Object> datos, boolean creando) {
		Map<String, Object> valores = new LinkedHashMap<>();
		for (Campo c : e.campos()) {
			if (!datos.containsKey(c.nombre())) {
				if (creando && c.requerido()) {
					throw new NegocioException("Falta «" + c.etiqueta() + "».");
				}
				continue;
			}
			if (c.soloCrear() && !creando) {
				continue;
			}
			Object v = convertir(c, datos.get(c.nombre()));
			if (v == null && c.requerido()) {
				throw new NegocioException("Falta «" + c.etiqueta() + "».");
			}
			valores.put(c.nombre(), v);
		}
		return valores;
	}

	private static Object convertir(Campo c, Object v) {
		if (v == null || (v instanceof String s && s.isBlank())) {
			return c.tipo() == Entidad.Tipo.BOOLEANO ? 0 : null;
		}
		try {
			return switch (c.tipo()) {
				case TEXTO, TEXTO_LARGO -> {
					String s = v.toString().trim();
					if (c.max() != null && s.length() > c.max()) {
						throw new NegocioException("«" + c.etiqueta() + "» admite máximo " + c.max() + " caracteres.");
					}
					yield s;
				}
				case EMAIL -> {
					String s = v.toString().trim();
					if (!EMAIL.matcher(s).matches()) {
						throw new NegocioException("«" + c.etiqueta() + "» no es un correo válido.");
					}
					yield s;
				}
				case ENTERO, SELECCION -> v instanceof Number n ? n.intValue() : Integer.parseInt(v.toString().trim());
				case DECIMAL, PESOS -> v instanceof Number n ? new BigDecimal(n.toString())
					: new BigDecimal(v.toString().trim().replace("$", "").replace(" ", ""));
				case FECHA -> LocalDate.parse(v.toString().substring(0, 10));
				case HORA -> LocalTime.parse(v.toString().length() == 5 ? v + ":00" : v.toString());
				case BOOLEANO -> (v instanceof Boolean b ? b : "1".equals(v.toString()) || "true".equalsIgnoreCase(v.toString()))
					? 1 : 0;
			};
		}
		catch (NegocioException ex) {
			throw ex;
		}
		catch (RuntimeException ex) {
			throw new NegocioException("El valor de «" + c.etiqueta() + "» no es válido.");
		}
	}

	private void despuesDeGuardar(Entidad e) {
		if (e.id().equals("parametros")) {
			parametros.invalidar();
		}
	}
}
