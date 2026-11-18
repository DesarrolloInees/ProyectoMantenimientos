package co.inees.plataforma.catalogo;

import java.util.List;
import java.util.Map;

import org.springframework.core.io.Resource;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.comun.Almacenamiento;

@RestController
@RequestMapping("/api")
public class CatalogoController {

	public record Item(int id, String nombre) {
	}

	public record TecnicoItem(int id, String nombre, Integer idDelegacion, String delegacion, Double latitudPartida,
		Double longitudPartida) {
	}

	private final JdbcClient jdbc;
	private final Almacenamiento almacenamiento;

	public CatalogoController(JdbcClient jdbc, Almacenamiento almacenamiento) {
		this.jdbc = jdbc;
		this.almacenamiento = almacenamiento;
	}

	@GetMapping("/catalogos")
	public Map<String, List<Item>> catalogos() {
		return Map.of(
			"delegaciones", lista("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY 2"),
			"tiposMantenimiento", lista("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1 ORDER BY 1"),
			"estadosMaquina", lista("SELECT id_estado, nombre_estado FROM estado_maquina ORDER BY 1"),
			"tiposNovedad", lista("SELECT id_tipo_novedad, nombre_novedad FROM tipo_novedad WHERE estado = 1 ORDER BY 2"),
			"calificaciones", lista("SELECT id_calificacion, nombre_calificacion FROM calificacion_servicio ORDER BY 1"));
	}

	@GetMapping("/catalogos/tecnicos")
	public List<TecnicoItem> tecnicos(@RequestParam(required = false) Integer idDelegacion) {
		return jdbc.sql("""
			SELECT t.id_tecnico AS id, t.nombre_tecnico AS nombre, t.id_delegacion, d.nombre_delegacion AS delegacion,
			       t.latitud_partida, t.longitud_partida
			FROM tecnico t LEFT JOIN delegacion d ON d.id_delegacion = t.id_delegacion
			WHERE t.estado = 1 AND (:del IS NULL OR t.id_delegacion = :del)
			ORDER BY t.nombre_tecnico""")
			.param("del", idDelegacion).query(TecnicoItem.class).list();
	}

	/** Sirve fotos y firmas a usuarios autenticados. */
	@GetMapping("/archivos")
	public ResponseEntity<Resource> archivo(@RequestParam String ruta) {
		return ResponseEntity.ok().contentType(Almacenamiento.tipo(ruta)).body(almacenamiento.leer(ruta));
	}

	private List<Item> lista(String sql) {
		return jdbc.sql(sql).query((rs, i) -> new Item(rs.getInt(1), rs.getString(2))).list();
	}
}
