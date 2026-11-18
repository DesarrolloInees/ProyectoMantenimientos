package co.inees.plataforma.admin;

import static co.inees.plataforma.admin.Entidad.Campo.decimal;
import static co.inees.plataforma.admin.Entidad.Campo.entero;
import static co.inees.plataforma.admin.Entidad.Campo.fecha;
import static co.inees.plataforma.admin.Entidad.Campo.hora;
import static co.inees.plataforma.admin.Entidad.Campo.largo;
import static co.inees.plataforma.admin.Entidad.Campo.pesos;
import static co.inees.plataforma.admin.Entidad.Campo.sel;
import static co.inees.plataforma.admin.Entidad.Campo.si;
import static co.inees.plataforma.admin.Entidad.Campo.texto;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.stereotype.Component;

import co.inees.plataforma.config.NegocioException;

/** Catálogos de la pantalla de Administración. */
@Component
public class Entidades {

	private final Map<String, Entidad> porId = new LinkedHashMap<>();

	public Entidades() {
		agregar(new Entidad("clientes", "Clientes", "cliente", "cliente", "id_cliente",
			List.of(texto("nombre_cliente", "Nombre", 150, true), texto("codigo_cliente", "Código Prosegur", 15, false),
				si("estado", "Activo")),
			"cliente t", List.of(), "t.nombre_cliente", "estado", List.of("t.nombre_cliente", "t.codigo_cliente"), true, true));

		agregar(new Entidad("puntos", "Puntos", "punto", "punto", "id_punto",
			List.of(texto("nombre_punto", "Nombre del punto", 90, true), sel("id_cliente", "Cliente", "clientes", "cliente", true),
				largo("direccion", "Dirección"), sel("id_municipio", "Municipio", "municipios", "municipio", true),
				sel("id_delegacion", "Delegación", "delegaciones", "delegacion", false), texto("zona", "Zona", 50, false),
				sel("id_modalidad", "Modalidad", "modalidades", "modalidad", true), texto("codigo_1", "Código 1", 15, false).oculto(),
				texto("codigo_2", "Código 2", 15, false).oculto(), decimal("latitud", "Latitud"), decimal("longitud", "Longitud"),
				si("estado", "Activo")),
			"punto t JOIN cliente c ON c.id_cliente = t.id_cliente LEFT JOIN municipio mu ON mu.id_municipio = t.id_municipio "
				+ "LEFT JOIN delegacion d ON d.id_delegacion = t.id_delegacion LEFT JOIN modalidad_operativa mo ON mo.id_modalidad = t.id_modalidad",
			List.of("c.nombre_cliente AS cliente", "mu.nombre_municipio AS municipio", "d.nombre_delegacion AS delegacion",
				"mo.nombre_modalidad AS modalidad"),
			"t.id_punto DESC", "estado", List.of("t.nombre_punto", "c.nombre_cliente", "t.direccion", "t.codigo_1"), true, true));

		agregar(new Entidad("maquinas", "Máquinas", "máquina", "maquina", "id_maquina",
			List.of(texto("device_id", "Device ID", 30, true), sel("id_punto", "Punto", "puntos", "punto", true),
				sel("id_tipo_maquina", "Tipo de máquina", "tipos-maquina", "tipo_maquina", true),
				fecha("ultima_visita", "Última visita", false), si("activo_operativo", "Operativa"), si("es_vip", "VIP"),
				entero("frecuencia_dias", "Frecuencia propia (días)", false).oculto(),
				texto("serial_maquina", "Serial máquina", 30, false).oculto(), texto("serial_router", "Serial router", 30, false).oculto(),
				texto("serial_ups", "Serial UPS", 30, false).oculto(), si("estado", "Activa")),
			"maquina t JOIN punto p ON p.id_punto = t.id_punto JOIN cliente c ON c.id_cliente = p.id_cliente "
				+ "JOIN tipo_maquina tm ON tm.id_tipo_maquina = t.id_tipo_maquina",
			List.of("CONCAT(p.nombre_punto, ' · ', c.nombre_cliente) AS punto", "tm.nombre_tipo_maquina AS tipo_maquina"),
			"t.id_maquina DESC", "estado", List.of("t.device_id", "p.nombre_punto", "c.nombre_cliente"), true, true));

		agregar(new Entidad("tecnicos", "Técnicos motorizados", "técnico", "tecnico", "id_tecnico",
			List.of(texto("nombre_tecnico", "Nombre", 60, true), texto("codigo_ruta", "Código de ruta", 20, false),
				sel("usuario_id", "Usuario de la plataforma", "usuarios", "usuario", false),
				sel("id_delegacion", "Delegación base", "delegaciones", "delegacion", false),
				hora("hora_inicio_jornada", "Inicio de jornada"), entero("minutos_jornada", "Minutos de jornada", false).oculto(),
				decimal("latitud_partida", "Latitud de partida"), decimal("longitud_partida", "Longitud de partida"),
				si("estado", "Activo")),
			"tecnico t LEFT JOIN usuarios u ON u.usuario_id = t.usuario_id LEFT JOIN delegacion d ON d.id_delegacion = t.id_delegacion",
			List.of("u.usuario AS usuario", "d.nombre_delegacion AS delegacion", "t.ruta_firma AS ruta_firma"),
			"t.nombre_tecnico", "estado", List.of("t.nombre_tecnico", "t.codigo_ruta"), true, true));

		agregar(simple("tipos-maquina", "Tipos de máquina", "tipo de máquina", "tipo_maquina", "id_tipo_maquina",
			List.of(texto("nombre_tipo_maquina", "Nombre", 40, true), entero("frecuencia_base_dias", "Frecuencia de mantenimiento (días)", false),
				si("es_grande", "Máquina grande"), si("estado", "Activo")),
			"nombre_tipo_maquina", "estado"));
		agregar(simple("tipos-mantenimiento", "Tipos de mantenimiento", "tipo de mantenimiento", "tipo_mantenimiento",
			"id_tipo_mantenimiento", List.of(texto("nombre_completo", "Nombre", 50, true), si("estado", "Activo")),
			"id_tipo_mantenimiento", "estado"));
		agregar(simple("calificaciones", "Calificaciones del servicio", "calificación", "calificacion_servicio",
			"id_calificacion", List.of(texto("nombre_calificacion", "Nombre", 20, true)), "id_calificacion", null));
		agregar(simple("delegaciones", "Delegaciones", "delegación", "delegacion", "id_delegacion",
			List.of(texto("nombre_delegacion", "Nombre", 35, true), si("estado", "Activa")), "nombre_delegacion", "estado"));
		agregar(new Entidad("municipios", "Municipios", "municipio", "municipio", "id_municipio",
			List.of(texto("nombre_municipio", "Nombre", 40, true), sel("id_delegacion", "Delegación", "delegaciones", "delegacion", false),
				si("interurbano", "Interurbano (fuera de la ciudad base de la delegación)"), si("estado", "Activo")),
			"municipio t LEFT JOIN delegacion d ON d.id_delegacion = t.id_delegacion", List.of("d.nombre_delegacion AS delegacion"),
			"t.nombre_municipio", "estado", List.of("t.nombre_municipio", "d.nombre_delegacion"), true, true));
		agregar(simple("estados-maquina", "Estados de máquina", "estado de máquina", "estado_maquina", "id_estado",
			List.of(texto("nombre_estado", "Nombre", 30, true)), "id_estado", null));
		agregar(simple("modalidades", "Modalidades operativas", "modalidad", "modalidad_operativa", "id_modalidad",
			List.of(texto("nombre_modalidad", "Nombre", 30, true)), "id_modalidad", null));
		agregar(simple("tipos-novedad", "Tipos de novedad", "tipo de novedad", "tipo_novedad", "id_tipo_novedad",
			List.of(texto("nombre_novedad", "Nombre", 100, true), si("estado", "Activo")), "nombre_novedad", "estado"));
		agregar(simple("festivos", "Días festivos", "día festivo", "dias_festivos", "id_festivo",
			List.of(fecha("fecha", "Fecha", true), texto("descripcion", "Descripción", 60, false)), "fecha DESC", null));
		agregar(simple("repuestos", "Repuestos", "repuesto", "repuesto", "id_repuesto",
			List.of(texto("nombre_repuesto", "Nombre", 100, true), texto("codigo_referencia", "Código de referencia", 20, false),
				pesos("valor_venta", "Valor de venta", false), si("requiere_devolucion", "Requiere devolución"), si("estado", "Activo")),
			"nombre_repuesto", "estado"));
		agregar(simple("estados-remision", "Estados de remisión", "estado de remisión", "estados_remision", "id_estado",
			List.of(texto("nombre_estado", "Nombre", 30, true), si("activo", "Activo")), "nombre_estado", "activo"));
		agregar(new Entidad("tarifas", "Tarifas", "tarifa", "tarifa", "id_tarifa",
			List.of(sel("id_tipo_maquina", "Tipo de máquina", "tipos-maquina", "tipo_maquina", true),
				sel("id_tipo_mantenimiento", "Tipo de mantenimiento", "tipos-mantenimiento", "tipo_mantenimiento", true),
				sel("id_modalidad", "Modalidad", "modalidades", "modalidad", true), pesos("precio", "Precio", true),
				entero("anio_vigencia", "Año de vigencia", true)),
			"tarifa t JOIN tipo_maquina tm ON tm.id_tipo_maquina = t.id_tipo_maquina "
				+ "JOIN tipo_mantenimiento tt ON tt.id_tipo_mantenimiento = t.id_tipo_mantenimiento "
				+ "JOIN modalidad_operativa mo ON mo.id_modalidad = t.id_modalidad",
			List.of("tm.nombre_tipo_maquina AS tipo_maquina", "tt.nombre_completo AS tipo_mantenimiento", "mo.nombre_modalidad AS modalidad"),
			"t.anio_vigencia DESC, tm.nombre_tipo_maquina, tt.id_tipo_mantenimiento", null,
			List.of("tm.nombre_tipo_maquina", "tt.nombre_completo", "CAST(t.anio_vigencia AS CHAR)"), true, true));
		agregar(new Entidad("parametros", "Parámetros", "parámetro", "parametros", "id",
			List.of(texto("clave", "Clave", 40, true).fijoAlCrear(), largo("valor", "Valor"),
				texto("descripcion", "Descripción", 150, false)),
			"parametros t", List.of("t.valor AS valor_lista"), "t.clave", null, List.of("t.clave", "t.descripcion"), true, false));
	}

	private static Entidad simple(String id, String titulo, String singular, String tabla, String pk, List<Entidad.Campo> campos,
		String orden, String activo) {
		List<String> busqueda = campos.stream().filter(c -> c.tipo() == Entidad.Tipo.TEXTO).map(c -> "t." + c.nombre()).toList();
		return new Entidad(id, titulo, singular, tabla, pk, campos, tabla + " t", List.of(), "t." + orden, activo,
			busqueda.isEmpty() ? List.of("t." + campos.get(0).nombre()) : busqueda, true, true);
	}

	private void agregar(Entidad e) {
		porId.put(e.id(), e);
	}

	public Entidad obtener(String id) {
		Entidad e = porId.get(id);
		if (e == null) {
			throw NegocioException.noEncontrado("El catálogo " + id);
		}
		return e;
	}

	public List<Entidad> todas() {
		return List.copyOf(porId.values());
	}
}
