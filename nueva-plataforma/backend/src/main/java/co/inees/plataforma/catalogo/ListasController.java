package co.inees.plataforma.catalogo;

import java.util.List;
import java.util.Map;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.config.NegocioException;

/**
 * Opciones de los selectores de todas las pantallas: /api/listas/{nombre}?q=texto&padre=id.
 * "padre" filtra en cascada: puntos de un cliente, máquinas de un punto, municipios de una delegación.
 */
@RestController
@RequestMapping("/api/listas")
public class ListasController {

	public record Opcion(int id, String nombre, String detalle) {
	}

	/** SQL de cada lista: devuelve id, nombre, detalle; :q (texto con %) y :padre pueden ser NULL. */
	private static final Map<String, String> LISTAS = Map.ofEntries(
		Map.entry("clientes", """
			SELECT id_cliente, nombre_cliente, codigo_cliente FROM cliente
			WHERE estado = 1 AND (:q IS NULL OR nombre_cliente LIKE :q OR codigo_cliente LIKE :q) ORDER BY nombre_cliente"""),
		Map.entry("puntos", """
			SELECT p.id_punto, p.nombre_punto, CONCAT(c.nombre_cliente, IFNULL(CONCAT(' · ', mu.nombre_municipio), ''))
			FROM punto p JOIN cliente c ON c.id_cliente = p.id_cliente LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
			WHERE p.estado = 1 AND (:padre IS NULL OR p.id_cliente = :padre)
			  AND (:q IS NULL OR p.nombre_punto LIKE :q OR c.nombre_cliente LIKE :q OR p.codigo_1 LIKE :q)
			ORDER BY p.nombre_punto"""),
		Map.entry("maquinas", """
			SELECT m.id_maquina, m.device_id, CONCAT(tm.nombre_tipo_maquina, ' · ', p.nombre_punto)
			FROM maquina m JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina JOIN punto p ON p.id_punto = m.id_punto
			WHERE m.estado = 1 AND (:padre IS NULL OR m.id_punto = :padre) AND (:q IS NULL OR m.device_id LIKE :q OR p.nombre_punto LIKE :q)
			ORDER BY m.device_id"""),
		Map.entry("tecnicos", """
			SELECT t.id_tecnico, t.nombre_tecnico, d.nombre_delegacion FROM tecnico t
			LEFT JOIN delegacion d ON d.id_delegacion = t.id_delegacion
			WHERE t.estado = 1 AND (:padre IS NULL OR t.id_delegacion = :padre) AND (:q IS NULL OR t.nombre_tecnico LIKE :q)
			ORDER BY t.nombre_tecnico"""),
		Map.entry("usuarios", """
			SELECT u.usuario_id, CONCAT(u.nombre, ' (', u.usuario, ')'), r.nombre FROM usuarios u JOIN rol r ON r.id_rol = u.id_rol
			WHERE u.estado = 'activo' AND (:q IS NULL OR u.nombre LIKE :q OR u.usuario LIKE :q) ORDER BY u.nombre"""),
		Map.entry("delegaciones", """
			SELECT id_delegacion, nombre_delegacion, NULL FROM delegacion WHERE estado = 1 AND (:q IS NULL OR nombre_delegacion LIKE :q)
			ORDER BY nombre_delegacion"""),
		Map.entry("municipios", """
			SELECT mu.id_municipio, mu.nombre_municipio, d.nombre_delegacion FROM municipio mu
			LEFT JOIN delegacion d ON d.id_delegacion = mu.id_delegacion
			WHERE mu.estado = 1 AND (:padre IS NULL OR mu.id_delegacion = :padre) AND (:q IS NULL OR mu.nombre_municipio LIKE :q)
			ORDER BY mu.nombre_municipio"""),
		Map.entry("modalidades", "SELECT id_modalidad, nombre_modalidad, NULL FROM modalidad_operativa WHERE :q IS NULL OR nombre_modalidad LIKE :q ORDER BY 1"),
		Map.entry("tipos-maquina", """
			SELECT id_tipo_maquina, nombre_tipo_maquina, NULL FROM tipo_maquina WHERE estado = 1 AND (:q IS NULL OR nombre_tipo_maquina LIKE :q)
			ORDER BY nombre_tipo_maquina"""),
		Map.entry("tipos-mantenimiento", """
			SELECT id_tipo_mantenimiento, nombre_completo, NULL FROM tipo_mantenimiento WHERE estado = 1 AND (:q IS NULL OR nombre_completo LIKE :q)
			ORDER BY id_tipo_mantenimiento"""),
		Map.entry("estados-maquina", "SELECT id_estado, nombre_estado, NULL FROM estado_maquina WHERE :q IS NULL OR nombre_estado LIKE :q ORDER BY 1"),
		Map.entry("calificaciones", "SELECT id_calificacion, nombre_calificacion, NULL FROM calificacion_servicio WHERE :q IS NULL OR nombre_calificacion LIKE :q ORDER BY 1"),
		Map.entry("tipos-novedad", """
			SELECT id_tipo_novedad, nombre_novedad, NULL FROM tipo_novedad WHERE estado = 1 AND (:q IS NULL OR nombre_novedad LIKE :q)
			ORDER BY nombre_novedad"""),
		Map.entry("repuestos", """
			SELECT id_repuesto, nombre_repuesto, codigo_referencia FROM repuesto
			WHERE estado = 1 AND (:q IS NULL OR nombre_repuesto LIKE :q OR codigo_referencia LIKE :q) ORDER BY nombre_repuesto"""),
		Map.entry("estados-remision", "SELECT id_estado, nombre_estado, NULL FROM estados_remision WHERE activo = 1 AND (:q IS NULL OR nombre_estado LIKE :q) ORDER BY nombre_estado"),
		Map.entry("estados-horas-extra", "SELECT id_estado, nombre_estado, NULL FROM estados_aprobacion_he WHERE :q IS NULL OR nombre_estado LIKE :q ORDER BY 1"),
		Map.entry("roles", "SELECT id_rol, nombre, NULL FROM rol WHERE estado = 1 AND (:q IS NULL OR nombre LIKE :q) ORDER BY orden, nombre"),
		Map.entry("zonas", """
			SELECT MIN(id_punto), zona, NULL FROM punto WHERE estado = 1 AND zona IS NOT NULL AND zona <> ''
			  AND (:padre IS NULL OR id_delegacion = :padre) AND (:q IS NULL OR zona LIKE :q) GROUP BY zona ORDER BY zona"""));

	private final JdbcClient jdbc;

	public ListasController(JdbcClient jdbc) {
		this.jdbc = jdbc;
	}

	@GetMapping("/{nombre}")
	public List<Opcion> lista(@PathVariable String nombre, @RequestParam(required = false) String q,
		@RequestParam(required = false) Integer padre, @RequestParam(defaultValue = "500") int limite) {
		String sql = LISTAS.get(nombre);
		if (sql == null) {
			throw NegocioException.noEncontrado("La lista " + nombre);
		}
		String texto = q == null || q.isBlank() ? null : "%" + q.trim() + "%";
		return jdbc.sql(sql + " LIMIT " + Math.min(Math.max(limite, 1), 5000)).param("q", texto).param("padre", padre)
			.query((rs, i) -> new Opcion(rs.getInt(1), rs.getString(2), rs.getString(3))).list();
	}
}
