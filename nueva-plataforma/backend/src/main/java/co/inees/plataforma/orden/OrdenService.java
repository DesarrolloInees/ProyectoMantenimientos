package co.inees.plataforma.orden;

import java.math.BigDecimal;
import java.time.Duration;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Objects;
import java.util.Set;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.comun.Almacenamiento;
import co.inees.plataforma.comun.Historial;
import co.inees.plataforma.comun.Valorizacion;
import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.inventario.Kardex;
import co.inees.plataforma.remision.Talonario;
import co.inees.plataforma.seguridad.Sesion;

/**
 * Registro y corrección de servicios desde la oficina (ordenCrear, ordenDetalle y servicioEditar de PHP).
 * El precio y los viáticos los calcula el servidor; cada cambio queda en el historial del servicio.
 */
@Service
public class OrdenService {

	public record RepuestoFila(int idRepuesto, int cantidad, String origen) {
	}

	/** Un servicio a registrar o corregir. */
	public record DatosServicio(Integer idPunto, Integer idMaquina, Integer idModalidad, Integer idTecnico,
		Integer idTipoMantenimiento, LocalDate fechaVisita, String numeroRemision, LocalTime horaEntrada,
		LocalTime horaSalida, Integer idEstadoMaquina, Integer idCalificacion, String actividades, BigDecimal valorManual,
		Integer diasViaticos, List<RepuestoFila> repuestos, List<Integer> novedades, String detalleNovedad) {
	}

	public record ResultadoLote(int guardados, List<String> errores, List<Integer> ids) {
	}

	public record Complemento(String soporteRemoto, String numeroMaquina, String serialMaquina, String serialRouter,
		String serialUps, String pendientes, String administrador, String celular, Integer idEstadoInicial,
		String detalleEstadoInicial) {
	}

	public record Evidencia(int idEvidencia, String tipo, String ruta, LocalDateTime fecha) {
	}

	public record Novedades(List<Integer> ids, String detalle) {
	}

	public record HistorialFila(String campo, String valorAnterior, String valorNuevo, String motivo, String usuario,
		LocalDateTime fecha) {
	}

	public static final Set<String> TIPOS_EVIDENCIA = Set.of("antes", "componentes", "despues", "remision", "firma",
		"llegada", "seriales", "estado_inicial", "trabajo", "repuestos", "estado_final", "novedad", "no_ingreso");

	private final JdbcClient jdbc;
	private final ServicioConsultas consultas;
	private final Valorizacion valorizacion;
	private final Talonario talonario;
	private final Kardex kardex;
	private final Historial historial;
	private final Almacenamiento almacenamiento;

	public OrdenService(JdbcClient jdbc, ServicioConsultas consultas, Valorizacion valorizacion, Talonario talonario,
		Kardex kardex, Historial historial, Almacenamiento almacenamiento) {
		this.jdbc = jdbc;
		this.consultas = consultas;
		this.valorizacion = valorizacion;
		this.talonario = talonario;
		this.kardex = kardex;
		this.historial = historial;
		this.almacenamiento = almacenamiento;
	}

	// ================================================================== registro manual (por lote)
	/** Registra varios servicios de una fecha; cada fila se guarda o falla por separado, como en PHP. */
	public ResultadoLote registrarLote(LocalDate fecha, List<DatosServicio> filas,
		java.util.function.Function<DatosServicio, Integer> guardarUno) {
		List<String> errores = new ArrayList<>();
		List<Integer> ids = new ArrayList<>();
		int i = 0;
		for (DatosServicio d : filas) {
			i++;
			try {
				ids.add(guardarUno.apply(d));
			}
			catch (NegocioException e) {
				errores.add("Fila " + i + ": " + e.getMessage());
			}
		}
		return new ResultadoLote(ids.size(), errores, ids);
	}

	@Transactional
	public int registrar(LocalDate fecha, DatosServicio d) {
		if (d.idMaquina() == null || d.idPunto() == null) {
			throw new NegocioException("Falta el punto o la máquina.");
		}
		if (d.idTecnico() == null) {
			throw new NegocioException("Falta el técnico.");
		}
		if (d.idTipoMantenimiento() == null) {
			throw new NegocioException("Falta el tipo de servicio.");
		}
		Ubicacion u = ubicacion(d.idMaquina(), d.idPunto());
		int modalidad = d.idModalidad() != null ? d.idModalidad() : u.modalidadPunto();
		String remision = vacio(d.numeroRemision());
		if (remision != null) {
			talonario.exigir(remision, d.idTecnico(), null);
		}
		BigDecimal valor = precio(d, u.idTipoMaquina(), modalidad, fecha);
		var viaticos = valorizacion.viaticos(d.idTecnico(), fecha, modalidad, d.idPunto(), null);
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO ordenes_servicio (id_cliente, id_punto, id_modalidad, numero_remision, fecha_visita, id_maquina,
			       id_tecnico, id_tipo_mantenimiento, valor_servicio, es_fuera_delegacion, dias_viaticos, valor_viaticos,
			       hora_entrada, hora_salida, minutos_servicio, id_estado_maquina, id_calificacion, actividades_realizadas,
			       estado, origen, prioridad, created_at)
			VALUES (:cliente, :punto, :modalidad, :remision, :fecha, :maquina, :tecnico, :tipo, :valor, :fuera, :dias, :viaticos,
			        :entrada, :salida, :minutos, :estadoMaq, :calif, :actividades, 1, 'MANUAL', 4, NOW())""")
			.param("cliente", u.idCliente()).param("punto", d.idPunto()).param("modalidad", modalidad)
			.param("remision", remision).param("fecha", fecha).param("maquina", d.idMaquina()).param("tecnico", d.idTecnico())
			.param("tipo", d.idTipoMantenimiento()).param("valor", valor).param("fuera", viaticos.fueraDelegacion())
			.param("dias", viaticos.dias()).param("viaticos", viaticos.valor()).param("entrada", d.horaEntrada())
			.param("salida", d.horaSalida()).param("minutos", minutos(d.horaEntrada(), d.horaSalida()))
			.param("estadoMaq", d.idEstadoMaquina()).param("calif", d.idCalificacion())
			.param("actividades", vacio(d.actividades())).update(llave);
		int id = llave.getKey().intValue();
		if (remision != null) {
			talonario.ocupar(remision, d.idTecnico(), id, LocalDateTime.of(fecha, d.horaEntrada() == null ? LocalTime.MIDNIGHT : d.horaEntrada()));
		}
		for (RepuestoFila r : agrupar(d.repuestos())) {
			agregarRepuestoInterno(id, d.idTecnico(), r);
		}
		if (d.novedades() != null && !d.novedades().isEmpty()) {
			guardarNovedadesInterno(id, d.novedades(), d.detalleNovedad());
		}
		valorizacion.actualizarUltimaVisita(d.idPunto(), d.idMaquina());
		historial.registrar(id, "estado", null, "FINALIZADA", "Registrado en oficina", Sesion.actual().usuarioId());
		return id;
	}

	// ================================================================== corrección
	@Transactional
	public ServicioConsultas.Servicio actualizar(int idOrden, DatosServicio d, String motivo) {
		ServicioConsultas.Servicio a = consultas.uno(idOrden);
		int usuario = Sesion.actual().usuarioId();
		int idPunto = d.idPunto() != null ? d.idPunto() : a.idPunto();
		int idMaquina = d.idMaquina() != null ? d.idMaquina() : a.idMaquina();
		Integer idTecnico = d.idTecnico() != null ? d.idTecnico() : a.idTecnico();
		Integer idTipo = d.idTipoMantenimiento() != null ? d.idTipoMantenimiento() : a.idTipoMantenimiento();
		LocalDate fecha = d.fechaVisita() != null ? d.fechaVisita() : a.fechaVisita();
		Ubicacion u = ubicacion(idMaquina, idPunto);
		int modalidad = d.idModalidad() != null ? d.idModalidad() : a.idModalidad() != null ? a.idModalidad() : u.modalidadPunto();
		String remision = d.numeroRemision() == null ? a.numeroRemision() : vacio(d.numeroRemision());

		boolean cambioRemision = !Objects.equals(remision, a.numeroRemision()) || !Objects.equals(idTecnico, a.idTecnico());
		if (cambioRemision) {
			if (remision != null && idTecnico != null) {
				talonario.exigir(remision, idTecnico, idOrden);
			}
			talonario.liberarDeOrden(idOrden);
			if (remision != null && idTecnico != null) {
				LocalTime h = d.horaEntrada() != null ? d.horaEntrada() : a.horaEntrada();
				talonario.ocupar(remision, idTecnico, idOrden, LocalDateTime.of(fecha, h == null ? LocalTime.MIDNIGHT : h));
			}
		}

		// Precio: se recalcula con la tarifa si cambió algo que lo define; si no hay tarifa se respeta el valor manual.
		boolean cambioValor = idPunto != a.idPunto() || idMaquina != a.idMaquina() || !Objects.equals(idTipo, a.idTipoMantenimiento())
			|| modalidad != (a.idModalidad() == null ? -1 : a.idModalidad()) || !fecha.equals(a.fechaVisita());
		BigDecimal valor = a.valorServicio();
		if (d.valorManual() != null) {
			valor = d.valorManual();
		}
		else if (cambioValor) {
			BigDecimal t = valorizacion.tarifa(u.idTipoMaquina(), idTipo, modalidad, fecha);
			valor = t != null ? t : BigDecimal.ZERO;
		}
		var viaticos = valorizacion.viaticos(idTecnico, fecha, modalidad, idPunto, idOrden);
		LocalTime entrada = d.horaEntrada() != null ? d.horaEntrada() : a.horaEntrada();
		LocalTime salida = d.horaSalida() != null ? d.horaSalida() : a.horaSalida();

		jdbc.sql("""
			UPDATE ordenes_servicio SET id_cliente = :cliente, id_punto = :punto, id_maquina = :maquina, id_modalidad = :modalidad,
			       numero_remision = :remision, id_tecnico = :tecnico, id_tipo_mantenimiento = :tipo, id_estado_maquina = :estadoMaq,
			       id_calificacion = :calif, hora_entrada = :entrada, hora_salida = :salida, minutos_servicio = :minutos,
			       valor_servicio = :valor, actividades_realizadas = :actividades, es_fuera_delegacion = :fuera,
			       dias_viaticos = :dias, valor_viaticos = :viaticos, fecha_visita = :fecha
			WHERE id_ordenes_servicio = :id""")
			.param("cliente", u.idCliente()).param("punto", idPunto).param("maquina", idMaquina).param("modalidad", modalidad)
			.param("remision", remision).param("tecnico", idTecnico).param("tipo", idTipo)
			.param("estadoMaq", d.idEstadoMaquina() != null ? d.idEstadoMaquina() : a.idEstadoMaquina())
			.param("calif", d.idCalificacion() != null ? d.idCalificacion() : a.idCalificacion())
			.param("entrada", entrada).param("salida", salida).param("minutos", minutos(entrada, salida))
			.param("valor", valor).param("actividades", d.actividades() != null ? vacio(d.actividades()) : a.actividades())
			.param("fuera", viaticos.fueraDelegacion()).param("dias", viaticos.dias()).param("viaticos", viaticos.valor())
			.param("fecha", fecha).param("id", idOrden).update();

		if (!Objects.equals(idTecnico, a.idTecnico())) {
			jdbc.sql("UPDATE control_devolucion_repuestos SET id_tecnico = :t WHERE id_orden_servicio = :o")
				.param("t", idTecnico).param("o", idOrden).update();
		}
		valorizacion.actualizarUltimaVisita(idPunto, idMaquina);
		if (idPunto != a.idPunto() || idMaquina != a.idMaquina()) {
			valorizacion.actualizarUltimaVisita(a.idPunto(), a.idMaquina());
		}

		String porque = motivo == null || motivo.isBlank() ? "Corrección en oficina" : motivo.trim();
		historial.registrar(idOrden, "punto", a.punto(), nombre("punto", "nombre_punto", "id_punto", idPunto), porque, usuario);
		historial.registrar(idOrden, "maquina", a.deviceId(), nombre("maquina", "device_id", "id_maquina", idMaquina), porque, usuario);
		historial.registrar(idOrden, "tecnico", a.tecnico(), idTecnico == null ? null
			: nombre("tecnico", "nombre_tecnico", "id_tecnico", idTecnico), porque, usuario);
		historial.registrar(idOrden, "tipo_mantenimiento", a.tipoMantenimiento(), idTipo == null ? null
			: nombre("tipo_mantenimiento", "nombre_completo", "id_tipo_mantenimiento", idTipo), porque, usuario);
		historial.registrar(idOrden, "fecha_visita", a.fechaVisita(), fecha, porque, usuario);
		historial.registrar(idOrden, "numero_remision", a.numeroRemision(), remision, porque, usuario);
		historial.registrar(idOrden, "valor_servicio", a.valorServicio() == null ? null : a.valorServicio().stripTrailingZeros().toPlainString(),
			valor == null ? null : valor.stripTrailingZeros().toPlainString(), porque, usuario);
		historial.registrar(idOrden, "horas", a.horaEntrada() + "-" + a.horaSalida(), entrada + "-" + salida, porque, usuario);
		return consultas.uno(idOrden);
	}

	/** Anula un servicio registrado por error: libera la remisión y devuelve al stock los repuestos INEES. */
	@Transactional
	public void anular(int idOrden, String motivo) {
		if (motivo == null || motivo.isBlank()) {
			throw new NegocioException("Indique el motivo de la anulación.");
		}
		ServicioConsultas.Servicio a = consultas.uno(idOrden);
		if (a.estado() == 9) {
			throw new NegocioException("El servicio ya está anulado.");
		}
		for (ServicioConsultas.Repuesto r : a.repuestos()) {
			quitarRepuesto(idOrden, r.idRepuesto(), r.origen());
		}
		talonario.liberarDeOrden(idOrden);
		jdbc.sql("UPDATE ordenes_servicio SET estado = 9, numero_remision = NULL WHERE id_ordenes_servicio = :o").param("o", idOrden).update();
		jdbc.sql("""
			UPDATE ruta_parada SET estado = 'RETIRADA' WHERE id_orden_servicio = :o AND estado = 'PENDIENTE'""").param("o", idOrden).update();
		valorizacion.actualizarUltimaVisita(a.idPunto(), a.idMaquina());
		historial.registrar(idOrden, "estado", "FINALIZADA", "CANCELADA", motivo.trim(), Sesion.actual().usuarioId());
	}

	// ================================================================== repuestos
	@Transactional
	public List<ServicioConsultas.Repuesto> agregarRepuesto(int idOrden, RepuestoFila r) {
		ServicioConsultas.Servicio a = consultas.uno(idOrden);
		if (a.idTecnico() == null) {
			throw new NegocioException("Asigne primero el técnico del servicio.");
		}
		agregarRepuestoInterno(idOrden, a.idTecnico(), normalizar(r));
		historial.registrar(idOrden, "repuestos", null, "+" + r.cantidad() + " repuesto " + r.idRepuesto() + " (" + r.origen() + ")",
			"Corrección en oficina", Sesion.actual().usuarioId());
		return consultas.uno(idOrden).repuestos();
	}

	@Transactional
	public List<ServicioConsultas.Repuesto> quitarRepuesto(int idOrden, int idRepuesto, String origen) {
		ServicioConsultas.Servicio a = consultas.uno(idOrden);
		String o = origen(origen);
		Integer cantidad = jdbc.sql("""
			SELECT SUM(cantidad) FROM orden_servicio_repuesto WHERE id_orden_servicio = :o AND id_repuesto = :r AND origen = :origen""")
			.param("o", idOrden).param("r", idRepuesto).param("origen", o).query(Integer.class).single();
		if (cantidad == null || cantidad == 0) {
			throw new NegocioException("Ese repuesto no está en el servicio.");
		}
		if (o.equals("INEES") && a.idTecnico() != null) {
			kardex.mover(a.idTecnico(), idRepuesto, Kardex.Tipo.DEVOLUCION, cantidad, idOrden, Sesion.actual().usuarioId(),
				"Retirado del servicio #" + idOrden);
		}
		jdbc.sql("DELETE FROM orden_servicio_repuesto WHERE id_orden_servicio = :o AND id_repuesto = :r AND origen = :origen")
			.param("o", idOrden).param("r", idRepuesto).param("origen", o).update();
		jdbc.sql("""
			UPDATE control_devolucion_repuestos SET cantidad = cantidad - :c
			WHERE id_orden_servicio = :o AND id_repuesto = :r AND estado_devolucion = 'Pendiente'""")
			.param("c", cantidad).param("o", idOrden).param("r", idRepuesto).update();
		jdbc.sql("DELETE FROM control_devolucion_repuestos WHERE id_orden_servicio = :o AND id_repuesto = :r AND cantidad <= 0")
			.param("o", idOrden).param("r", idRepuesto).update();
		if (a.estado() != 9) {
			historial.registrar(idOrden, "repuestos", cantidad + " repuesto " + idRepuesto + " (" + o + ")", null,
				"Corrección en oficina", Sesion.actual().usuarioId());
		}
		return consultas.uno(idOrden).repuestos();
	}

	private void agregarRepuestoInterno(int idOrden, int idTecnico, RepuestoFila r) {
		Integer idMovimiento = null;
		if (r.origen().equals("INEES")) {
			idMovimiento = kardex.mover(idTecnico, r.idRepuesto(), Kardex.Tipo.CONSUMO, -r.cantidad(), idOrden,
				Sesion.actual().usuarioId(), "Usado en el servicio #" + idOrden);
		}
		int n = jdbc.sql("""
			UPDATE orden_servicio_repuesto SET cantidad = cantidad + :c
			WHERE id_orden_servicio = :o AND id_repuesto = :r AND origen = :origen""")
			.param("c", r.cantidad()).param("o", idOrden).param("r", r.idRepuesto()).param("origen", r.origen()).update();
		if (n == 0) {
			jdbc.sql("""
				INSERT INTO orden_servicio_repuesto (id_orden_servicio, id_repuesto, origen, cantidad, valor_unitario, id_movimiento)
				SELECT :o, id_repuesto, :origen, :c, valor_venta, :mov FROM repuesto WHERE id_repuesto = :r""")
				.param("o", idOrden).param("origen", r.origen()).param("c", r.cantidad()).param("mov", idMovimiento)
				.param("r", r.idRepuesto()).update();
		}
		boolean devuelve = jdbc.sql("SELECT requiere_devolucion = 1 FROM repuesto WHERE id_repuesto = :r").param("r", r.idRepuesto())
			.query(Boolean.class).optional().orElse(false);
		if (devuelve) {
			int m = jdbc.sql("""
				UPDATE control_devolucion_repuestos SET cantidad = cantidad + :c
				WHERE id_orden_servicio = :o AND id_repuesto = :r AND estado_devolucion = 'Pendiente'""")
				.param("c", r.cantidad()).param("o", idOrden).param("r", r.idRepuesto()).update();
			if (m == 0) {
				jdbc.sql("""
					INSERT INTO control_devolucion_repuestos (id_tecnico, id_orden_servicio, id_repuesto, cantidad, estado_devolucion)
					VALUES (:t, :o, :r, :c, 'Pendiente')""")
					.param("t", idTecnico).param("o", idOrden).param("r", r.idRepuesto()).param("c", r.cantidad()).update();
			}
		}
	}

	// ================================================================== novedades
	@Transactional
	public ServicioConsultas.Servicio guardarNovedades(int idOrden, Novedades n) {
		consultas.uno(idOrden);
		guardarNovedadesInterno(idOrden, n.ids() == null ? List.of() : n.ids(), n.detalle());
		historial.registrar(idOrden, "novedades", null, n.ids() == null ? "" : n.ids().toString(), "Corrección en oficina",
			Sesion.actual().usuarioId());
		return consultas.uno(idOrden);
	}

	private void guardarNovedadesInterno(int idOrden, List<Integer> ids, String detalle) {
		jdbc.sql("DELETE FROM orden_servicio_novedad WHERE id_orden_servicio = :o").param("o", idOrden).update();
		for (Integer id : ids.stream().distinct().toList()) {
			jdbc.sql("INSERT INTO orden_servicio_novedad (id_orden_servicio, id_tipo_novedad) VALUES (:o, :n)")
				.param("o", idOrden).param("n", id).update();
		}
		jdbc.sql("UPDATE ordenes_servicio SET tiene_novedad = :t, detalle_novedad = :d WHERE id_ordenes_servicio = :o")
			.param("t", !ids.isEmpty()).param("d", ids.isEmpty() ? null : vacio(detalle)).param("o", idOrden).update();
	}

	// ================================================================== complemento y evidencias (servicioEditar)
	public Complemento complemento(int idOrden) {
		return jdbc.sql("""
			SELECT o.soporte_remoto, c.numero_maquina, c.serial_maquina, c.serial_router, c.serial_ups, c.pendientes,
			       c.administrador_punto AS administrador, c.celular_encargado AS celular, c.id_estado_inicial,
			       c.novedad_estado_inicial AS detalle_estado_inicial
			FROM ordenes_servicio o LEFT JOIN ordenes_servicio_complemento c ON c.id_orden_servicio = o.id_ordenes_servicio
			WHERE o.id_ordenes_servicio = :o""").param("o", idOrden).query(Complemento.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("El servicio"));
	}

	@Transactional
	public Complemento guardarComplemento(int idOrden, Complemento c) {
		consultas.uno(idOrden);
		jdbc.sql("UPDATE ordenes_servicio SET soporte_remoto = :s WHERE id_ordenes_servicio = :o")
			.param("s", recortar(vacio(c.soporteRemoto()), 40)).param("o", idOrden).update();
		jdbc.sql("""
			INSERT INTO ordenes_servicio_complemento (id_orden_servicio, numero_maquina, serial_maquina, serial_router, serial_ups,
			       pendientes, administrador_punto, celular_encargado, id_estado_inicial, novedad_estado_inicial, estado)
			VALUES (:o, :num, :sm, :sr, :su, :pen, :adm, :cel, :ei, :dei, 1)
			ON DUPLICATE KEY UPDATE numero_maquina = VALUES(numero_maquina), serial_maquina = VALUES(serial_maquina),
			       serial_router = VALUES(serial_router), serial_ups = VALUES(serial_ups), pendientes = VALUES(pendientes),
			       administrador_punto = VALUES(administrador_punto), celular_encargado = VALUES(celular_encargado),
			       id_estado_inicial = VALUES(id_estado_inicial), novedad_estado_inicial = VALUES(novedad_estado_inicial)""")
			.param("o", idOrden).param("num", recortar(vacio(c.numeroMaquina()), 30)).param("sm", recortar(vacio(c.serialMaquina()), 30))
			.param("sr", recortar(vacio(c.serialRouter()), 30)).param("su", recortar(vacio(c.serialUps()), 30))
			.param("pen", vacio(c.pendientes())).param("adm", recortar(vacio(c.administrador()), 40))
			.param("cel", recortar(vacio(c.celular()), 12)).param("ei", c.idEstadoInicial())
			.param("dei", vacio(c.detalleEstadoInicial())).update();
		return complemento(idOrden);
	}

	public List<Evidencia> evidencias(int idOrden) {
		return jdbc.sql("""
			SELECT id_evidencia, tipo_evidencia AS tipo, ruta_archivo AS ruta, fecha_subida AS fecha FROM evidencia_servicio
			WHERE id_orden_servicio = :o
			ORDER BY FIELD(tipo_evidencia, 'llegada', 'antes', 'seriales', 'estado_inicial', 'componentes', 'trabajo', 'repuestos',
			               'despues', 'estado_final', 'novedad', 'no_ingreso', 'remision', 'firma'), fecha_subida""")
			.param("o", idOrden).query(Evidencia.class).list();
	}

	@Transactional
	public Evidencia subirEvidencia(int idOrden, String tipo, MultipartFile archivo) {
		if (!TIPOS_EVIDENCIA.contains(tipo)) {
			throw new NegocioException("Tipo de evidencia no válido: " + tipo);
		}
		consultas.uno(idOrden);
		String ruta = almacenamiento.guardarImagen(idOrden, tipo, archivo);
		var llave = new GeneratedKeyHolder();
		LocalDateTime ahora = LocalDateTime.now().withNano(0);
		jdbc.sql("INSERT INTO evidencia_servicio (id_orden_servicio, tipo_evidencia, ruta_archivo, fecha_subida) VALUES (:o, :t, :r, :f)")
			.param("o", idOrden).param("t", tipo).param("r", ruta).param("f", ahora).update(llave);
		return new Evidencia(llave.getKey().intValue(), tipo, ruta, ahora);
	}

	@Transactional
	public void eliminarEvidencia(int idOrden, int idEvidencia) {
		String ruta = jdbc.sql("SELECT ruta_archivo FROM evidencia_servicio WHERE id_evidencia = :e AND id_orden_servicio = :o")
			.param("e", idEvidencia).param("o", idOrden).query(String.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("La foto"));
		jdbc.sql("DELETE FROM evidencia_servicio WHERE id_evidencia = :e").param("e", idEvidencia).update();
		almacenamiento.eliminar(ruta);
		historial.registrar(idOrden, "evidencia", ruta, null, "Foto eliminada en oficina", Sesion.actual().usuarioId());
	}

	public List<HistorialFila> historial(int idOrden) {
		return jdbc.sql("""
			SELECT h.campo, h.valor_anterior, h.valor_nuevo, h.motivo, COALESCE(u.nombre, 'Sistema') AS usuario, h.fecha
			FROM orden_servicio_historial h LEFT JOIN usuarios u ON u.usuario_id = h.id_usuario
			WHERE h.id_orden_servicio = :o ORDER BY h.fecha DESC, h.id_historial DESC""")
			.param("o", idOrden).query(HistorialFila.class).list();
	}

	// ------------------------------------------------------------------ apoyo
	record Ubicacion(int idCliente, int modalidadPunto, int idTipoMaquina, int idPuntoMaquina) {
	}

	private Ubicacion ubicacion(int idMaquina, int idPunto) {
		Ubicacion u = jdbc.sql("""
			SELECT p.id_cliente, p.id_modalidad AS modalidad_punto, m.id_tipo_maquina, m.id_punto AS id_punto_maquina
			FROM maquina m JOIN punto p ON p.id_punto = :p WHERE m.id_maquina = :m""")
			.param("m", idMaquina).param("p", idPunto).query(Ubicacion.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("La máquina o el punto"));
		if (u.idPuntoMaquina() != idPunto) {
			throw new NegocioException("La máquina no pertenece al punto seleccionado.");
		}
		return u;
	}

	private BigDecimal precio(DatosServicio d, int idTipoMaquina, int modalidad, LocalDate fecha) {
		BigDecimal t = valorizacion.tarifa(idTipoMaquina, d.idTipoMantenimiento(), modalidad, fecha);
		if (t != null) {
			return t;
		}
		return d.valorManual() != null ? d.valorManual() : BigDecimal.ZERO;
	}

	static Integer minutos(LocalTime entrada, LocalTime salida) {
		if (entrada == null || salida == null) {
			return 0;
		}
		long m = Duration.between(entrada, salida).toMinutes();
		return (int) (m < 0 ? m + 24 * 60 : m);
	}

	private List<RepuestoFila> agrupar(List<RepuestoFila> lista) {
		Map<String, Integer> suma = new LinkedHashMap<>();
		if (lista != null) {
			for (RepuestoFila r : lista) {
				RepuestoFila n = normalizar(r);
				suma.merge(n.idRepuesto() + "|" + n.origen(), n.cantidad(), Integer::sum);
			}
		}
		return suma.entrySet().stream().map(e -> new RepuestoFila(Integer.parseInt(e.getKey().split("\\|")[0]), e.getValue(),
			e.getKey().split("\\|")[1])).toList();
	}

	private static RepuestoFila normalizar(RepuestoFila r) {
		if (r.cantidad() <= 0) {
			throw new NegocioException("La cantidad de cada repuesto debe ser mayor que cero.");
		}
		return new RepuestoFila(r.idRepuesto(), r.cantidad(), origen(r.origen()));
	}

	private static String origen(String o) {
		String s = o == null ? "INEES" : o.trim().toUpperCase(Locale.ROOT);
		if (!s.equals("INEES") && !s.equals("PROSEGUR")) {
			throw new NegocioException("El origen del repuesto debe ser INEES o PROSEGUR.");
		}
		return s;
	}

	private String nombre(String tabla, String columna, String pk, int id) {
		return jdbc.sql("SELECT " + columna + " FROM " + tabla + " WHERE " + pk + " = :id").param("id", id)
			.query(String.class).optional().orElse(String.valueOf(id));
	}

	private static String vacio(String s) {
		return s == null || s.isBlank() ? null : s.trim();
	}

	private static String recortar(String s, int max) {
		return s == null || s.length() <= max ? s : s.substring(0, max);
	}
}
