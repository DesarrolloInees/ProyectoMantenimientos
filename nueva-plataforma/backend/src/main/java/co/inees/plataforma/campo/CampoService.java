package co.inees.plataforma.campo;

import java.math.BigDecimal;
import java.time.Duration;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Objects;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.catalogo.CatalogoController.Item;
import co.inees.plataforma.comun.Almacenamiento;
import co.inees.plataforma.comun.Geo;
import co.inees.plataforma.comun.Historial;
import co.inees.plataforma.comun.Parametros;
import co.inees.plataforma.comun.Valorizacion;
import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.inventario.Kardex;
import co.inees.plataforma.seguridad.Sesion;
import tools.jackson.databind.json.JsonMapper;

/** Jornada y flujo guiado del motorizado. Todas las horas las pone el servidor. */
@Service
public class CampoService {

	// ------------------------------------------------------------------ vistas
	public record Jornada(LocalTime horaEntrada, LocalTime horaSalida) {
	}

	public record ParadaCampo(int idParada, int secuencia, String estadoParada, LocalTime horaEstimada, int idOrden,
		int estadoOrden, String etapaActual, String cliente, String punto, String direccion, String municipio,
		String deviceId, String tipoMaquina, String tipoMantenimiento, Double latitud, Double longitud, boolean esSiguiente,
		String mapsUrl) {
	}

	public record MiDia(LocalDate fecha, Jornada jornada, Integer idRuta, String estadoRuta, List<ParadaCampo> paradas,
		int esperaMaxMinutos) {
	}

	public record Evidencia(int idEvidencia, String tipo, String ruta, LocalDateTime fecha) {
	}

	public record Complemento(String numeroMaquina, String serialMaquina, String serialRouter, String serialUps,
		Boolean serialesCoinciden, String administrador, String celular, Integer idEstadoInicial,
		String detalleEstadoInicial) {
	}

	public record OrdenCampo(int idOrden, int estado, String etapaActual, List<String> siguientes, String cliente,
		String punto, String direccion, String municipio, String deviceId, String tipoMaquina, String tipoMantenimiento,
		String modalidad, String referenciaProsegur, String descripcionSolicitud, Double latitud, Double longitud,
		LocalDateTime horaLlegada, LocalDateTime horaIngreso, Integer minutosEsperando, int esperaMaxMinutos,
		String actividades, Complemento complemento, List<Evidencia> evidencias, Map<String, String> fotoPorPaso,
		String mapsUrl) {
	}

	public record RepuestoStock(int idRepuesto, String nombre, String codigo, int cantidad) {
	}

	public record Formularios(List<Item> estadosMaquina, List<Item> tiposNovedad, List<Item> calificaciones,
		List<RepuestoStock> miStock, List<Item> repuestos, List<String> remisiones) {
	}

	public record RespuestaPaso(OrdenCampo orden, ParadaCampo siguiente, List<String> avisos) {
	}

	public record RepuestoUsado(int idRepuesto, int cantidad, String origen) {
	}

	/** Datos de cualquier paso; cada paso usa los suyos. */
	public record Paso(Double latitud, Double longitud, String numeroMaquina, String serialMaquina, String serialRouter,
		String serialUps, String administrador, String celular, Integer idEstadoInicial, String detalleEstadoInicial,
		String actividades, List<RepuestoUsado> repuestos, Integer idEstadoFinal, List<Integer> novedades,
		String detalleNovedad, String numeroRemision, String firma, Integer idCalificacion, String pendientes) {
	}

	/** Lo que el servicio necesita saber de una orden para validar un paso. */
	record Ctx(int idOrden, Integer idTecnico, int estado, String etapaActual, String origen, int prioridad,
		LocalDate fechaVisita, LocalDateTime horaInicioTrayecto, LocalDateTime horaLlegada, LocalDateTime horaIngreso,
		int idMaquina, int idPunto, Integer idTipoMantenimiento, Integer idModalidad, int idTipoMaquina, Double latitud,
		Double longitud, String serialMaquinaEsperado, String serialRouterEsperado, String serialUpsEsperado,
		Integer delegacionPunto) {
	}

	private static final JsonMapper JSON = JsonMapper.builder().build();

	private final JdbcClient jdbc;
	private final Parametros parametros;
	private final Historial historial;
	private final Almacenamiento almacenamiento;
	private final Valorizacion valorizacion;
	private final Kardex kardex;

	public CampoService(JdbcClient jdbc, Parametros parametros, Historial historial, Almacenamiento almacenamiento,
		Valorizacion valorizacion, Kardex kardex) {
		this.jdbc = jdbc;
		this.parametros = parametros;
		this.historial = historial;
		this.almacenamiento = almacenamiento;
		this.valorizacion = valorizacion;
		this.kardex = kardex;
	}

	// ================================================================== mi día
	public MiDia miDia() {
		int tecnico = Sesion.idTecnico();
		LocalDate hoy = LocalDate.now();
		Jornada jornada = jdbc.sql("""
			SELECT hora_entrada, hora_salida FROM turno_tecnico WHERE id_tecnico = :t AND fecha = :hoy
			ORDER BY id_turno DESC LIMIT 1""").param("t", tecnico).param("hoy", hoy).query(Jornada.class).optional().orElse(null);
		record RutaFila(int idRuta, String estado) {
		}
		RutaFila ruta = jdbc.sql("""
			SELECT id_ruta, estado FROM ruta_diaria
			WHERE id_tecnico = :t AND fecha = :hoy AND estado IN ('PUBLICADA', 'EN_CURSO', 'CERRADA')""")
			.param("t", tecnico).param("hoy", hoy).query(RutaFila.class).optional().orElse(null);
		List<ParadaCampo> paradas = ruta == null ? List.of() : paradas(ruta.idRuta(), null, null);
		return new MiDia(hoy, jornada, ruta == null ? null : ruta.idRuta(), ruta == null ? null : ruta.estado(), paradas,
			parametros.entero(Parametros.ESPERA_MAX, 45));
	}

	@Transactional
	public MiDia iniciarJornada(Double lat, Double lon, String novedad) {
		int tecnico = Sesion.idTecnico();
		if (lat == null || lon == null) {
			throw new NegocioException("Active el GPS del celular para iniciar la jornada.");
		}
		LocalDate hoy = LocalDate.now();
		boolean yaInicio = jdbc.sql("SELECT COUNT(*) FROM turno_tecnico WHERE id_tecnico = :t AND fecha = :hoy")
			.param("t", tecnico).param("hoy", hoy).query(Integer.class).single() > 0;
		if (yaInicio) {
			throw new NegocioException("La jornada de hoy ya está iniciada.");
		}
		Integer idRuta = jdbc.sql("SELECT id_ruta FROM ruta_diaria WHERE id_tecnico = :t AND fecha = :hoy AND estado IN ('PUBLICADA','EN_CURSO')")
			.param("t", tecnico).param("hoy", hoy).query(Integer.class).optional().orElse(null);
		jdbc.sql("""
			INSERT INTO turno_tecnico (id_tecnico, fecha, hora_entrada, novedad, latitud, longitud, estado, id_ruta)
			VALUES (:t, :hoy, :hora, :nov, :lat, :lon, 1, :ruta)""")
			.param("t", tecnico).param("hoy", hoy).param("hora", LocalTime.now().withNano(0))
			.param("nov", novedad == null || novedad.isBlank() ? null : novedad.trim()).param("lat", lat)
			.param("lon", lon).param("ruta", idRuta).update();
		if (idRuta != null) {
			jdbc.sql("UPDATE ruta_diaria SET estado = 'EN_CURSO' WHERE id_ruta = :r").param("r", idRuta).update();
		}
		return miDia();
	}

	@Transactional
	public MiDia finalizarJornada(Double lat, Double lon) {
		int tecnico = Sesion.idTecnico();
		int enCurso = jdbc.sql("SELECT COUNT(*) FROM ordenes_servicio WHERE id_tecnico = :t AND estado IN (4, 5, 6)")
			.param("t", tecnico).query(Integer.class).single();
		if (enCurso > 0) {
			throw new NegocioException("Tiene un servicio en curso: ciérrelo antes de terminar la jornada.");
		}
		int n = jdbc.sql("""
			UPDATE turno_tecnico SET hora_salida = :hora, latitud_fin = :lat, longitud_fin = :lon
			WHERE id_tecnico = :t AND fecha = :hoy AND hora_salida IS NULL""")
			.param("hora", LocalTime.now().withNano(0)).param("lat", lat).param("lon", lon).param("t", tecnico)
			.param("hoy", LocalDate.now()).update();
		if (n == 0) {
			throw new NegocioException("No hay una jornada abierta hoy.");
		}
		return miDia();
	}

	// ================================================================== orden
	public OrdenCampo orden(int idOrden) {
		Ctx c = ctx(idOrden);
		return vista(c);
	}

	public Formularios formularios() {
		int tecnico = Sesion.idTecnico();
		return new Formularios(
			items("SELECT id_estado, nombre_estado FROM estado_maquina ORDER BY 1"),
			items("SELECT id_tipo_novedad, nombre_novedad FROM tipo_novedad WHERE estado = 1 ORDER BY 2"),
			items("SELECT id_calificacion, nombre_calificacion FROM calificacion_servicio ORDER BY 1"),
			jdbc.sql("""
				SELECT r.id_repuesto, r.nombre_repuesto AS nombre, r.codigo_referencia AS codigo, i.cantidad_actual AS cantidad
				FROM inventario_tecnico i JOIN repuesto r ON r.id_repuesto = i.id_repuesto
				WHERE i.id_tecnico = :t AND i.estado = 1 AND i.cantidad_actual > 0 ORDER BY r.nombre_repuesto""")
				.param("t", tecnico).query(RepuestoStock.class).list(),
			items("SELECT id_repuesto, nombre_repuesto FROM repuesto WHERE estado = 1 ORDER BY 2"),
			jdbc.sql("""
				SELECT numero_remision FROM control_remisiones
				WHERE id_tecnico = :t AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)
				ORDER BY CAST(numero_remision AS UNSIGNED)""").param("t", tecnico).query(String.class).list());
	}

	@Transactional
	public Evidencia subirEvidencia(int idOrden, String tipo, MultipartFile archivo, Double lat, Double lon) {
		Ctx c = ctx(idOrden);
		List<String> permitidos = Arrays.stream(Etapa.values()).map(Etapa::evidencia).filter(Objects::nonNull).toList();
		if (!permitidos.contains(tipo) && !"novedad".equals(tipo)) {
			throw new NegocioException("Tipo de foto no válido: " + tipo);
		}
		if (c.estado() < 4 || c.estado() > 6) {
			throw new NegocioException("Solo se suben fotos mientras el servicio está en curso.");
		}
		String ruta = almacenamiento.guardarImagen(idOrden, tipo, archivo);
		var llave = new GeneratedKeyHolder();
		LocalDateTime ahora = LocalDateTime.now().withNano(0);
		jdbc.sql("""
			INSERT INTO evidencia_servicio (id_orden_servicio, tipo_evidencia, ruta_archivo, fecha_subida, latitud, longitud)
			VALUES (:o, :tipo, :ruta, :fecha, :lat, :lon)""")
			.param("o", idOrden).param("tipo", tipo).param("ruta", ruta).param("fecha", ahora).param("lat", lat)
			.param("lon", lon).update(llave);
		return new Evidencia(llave.getKey().intValue(), tipo, ruta, ahora);
	}

	// ================================================================== pasos
	@Transactional
	public RespuestaPaso registrarPaso(int idOrden, Etapa etapa, Paso p) {
		var usuario = Sesion.actual();
		Ctx c = ctx(idOrden);
		Etapa actual = Etapa.de(c.etapaActual());
		if (!Etapa.siguientes(actual).contains(etapa)) {
			String esperado = Etapa.siguientes(actual).stream().map(Etapa::nombre).reduce((a, b) -> a + " o " + b)
				.orElse("ninguno: el servicio ya terminó");
			throw new NegocioException("No puede registrar «" + etapa.nombre() + "» ahora. El siguiente paso es: " + esperado + ".");
		}
		if (etapa.evidencia() != null && etapa != Etapa.REPUESTOS) {
			exigirFoto(c, etapa.evidencia(), etapa.nombre());
		}

		LocalDateTime ahora = LocalDateTime.now().withNano(0);
		List<String> avisos = new ArrayList<>();
		ParadaCampo siguiente = null;
		int espera = parametros.entero(Parametros.ESPERA_MAX, 45);

		switch (etapa) {
			case INICIO_TRAYECTO -> {
				exigirJornada(c.idTecnico());
				Integer primera = primeraPendiente(c.idTecnico());
				if (primera != null && primera != idOrden) {
					throw new NegocioException("Debe seguir el orden de su ruta. El siguiente punto es otro; si hay que "
						+ "cambiarlo, pídaselo al Supervisor.");
				}
				int otras = jdbc.sql("SELECT COUNT(*) FROM ordenes_servicio WHERE id_tecnico = :t AND estado IN (4,5,6) AND id_ordenes_servicio <> :o")
					.param("t", c.idTecnico()).param("o", idOrden).query(Integer.class).single();
				if (otras > 0) {
					throw new NegocioException("Ya tiene otro servicio en curso.");
				}
				actualizar(idOrden, "estado = 4, hora_inicio_trayecto = :ahora", ahora);
			}
			case LLEGADA -> {
				exigirGps(p);
				if (c.latitud() != null) {
					double metros = Geo.km(p.latitud(), p.longitud(), c.latitud(), c.longitud()) * 1000;
					if (metros > 500) {
						avisos.add(String.format(Locale.ROOT, "Está a %.0f m del punto registrado; se avisó al Supervisor.", metros));
					}
				}
				actualizar(idOrden, "estado = 5, hora_llegada = :ahora", ahora);
			}
			case INGRESO -> {
				long esperado = Duration.between(c.horaLlegada(), ahora).toMinutes();
				if (esperado > espera) {
					throw new NegocioException("Pasaron " + esperado + " minutos desde la llegada (máximo " + espera
						+ "). Registre «No le dieron ingreso» y siga al siguiente punto.");
				}
				jdbc.sql("""
					UPDATE ordenes_servicio SET estado = 6, hora_ingreso = :ahora, hora_entrada = :hora, minutos_espera = :esp
					WHERE id_ordenes_servicio = :o""")
					.param("ahora", ahora).param("hora", ahora.toLocalTime()).param("esp", esperado).param("o", idOrden).update();
				jdbc.sql("""
					INSERT INTO ordenes_servicio_complemento (id_orden_servicio, latitud_inicio, longitud_inicio, estado)
					VALUES (:o, :lat, :lon, 1)
					ON DUPLICATE KEY UPDATE latitud_inicio = VALUES(latitud_inicio), longitud_inicio = VALUES(longitud_inicio)""")
					.param("o", idOrden).param("lat", p == null ? null : p.latitud()).param("lon", p == null ? null : p.longitud())
					.update();
			}
			case NO_INGRESO -> {
				long esperado = Duration.between(c.horaLlegada(), ahora).toMinutes();
				if (esperado < espera) {
					throw new NegocioException("Debe esperar hasta " + espera + " minutos antes de pasar al siguiente punto "
						+ "(lleva " + esperado + ").");
				}
				int prioridad = "SOLICITUD_PROSEGUR".equals(c.origen()) ? 1 : Math.min(c.prioridad(), 2);
				jdbc.sql("""
					UPDATE ordenes_servicio SET estado = 3, id_tecnico = NULL, minutos_espera = :esp, prioridad = :prio,
					       veces_reprogramada = veces_reprogramada + 1, fecha_visita = :manana
					WHERE id_ordenes_servicio = :o""")
					.param("esp", esperado).param("prio", prioridad).param("manana", LocalDate.now().plusDays(1))
					.param("o", idOrden).update();
				marcarParada(idOrden, "NO_INGRESO");
				historial.registrar(idOrden, "estado", "EN_PUNTO", "PENDIENTE",
					"Sin ingreso después de " + esperado + " min: pasa al día siguiente con prioridad", usuario.usuarioId());
				avisos.add("El servicio pasa al día siguiente con prioridad. Siga al siguiente punto de su ruta.");
				siguiente = siguienteParada(c.idTecnico(), p);
			}
			case SERIALES -> pasoSeriales(c, p, avisos);
			case ADMINISTRADOR -> {
				String admin = texto(p.administrador(), 3, "el nombre del administrador del punto");
				String cel = p.celular() == null ? "" : p.celular().replaceAll("\\D", "");
				if (cel.length() < 7 || cel.length() > 12) {
					throw new NegocioException("Escriba un celular válido del administrador (7 a 12 dígitos).");
				}
				jdbc.sql("UPDATE ordenes_servicio_complemento SET administrador_punto = :a, celular_encargado = :c WHERE id_orden_servicio = :o")
					.param("a", recortar(admin, 40)).param("c", cel).param("o", idOrden).update();
			}
			case ESTADO_INICIAL -> {
				if (p.idEstadoInicial() == null) {
					throw new NegocioException("Indique cómo encontró la máquina.");
				}
				String detalle = texto(p.detalleEstadoInicial(), 5, "el detalle de lo que encontró");
				jdbc.sql("UPDATE ordenes_servicio_complemento SET id_estado_inicial = :e, novedad_estado_inicial = :d WHERE id_orden_servicio = :o")
					.param("e", p.idEstadoInicial()).param("d", detalle).param("o", idOrden).update();
			}
			case TRABAJO -> {
				String actividades = texto(p.actividades(), 10, "qué le hizo a la máquina");
				jdbc.sql("UPDATE ordenes_servicio SET actividades_realizadas = :a WHERE id_ordenes_servicio = :o")
					.param("a", actividades).param("o", idOrden).update();
			}
			case REPUESTOS -> pasoRepuestos(c, p, usuario.usuarioId());
			case ESTADO_FINAL -> {
				if (p.idEstadoFinal() == null) {
					throw new NegocioException("Indique si la máquina queda en operación o fuera de servicio.");
				}
				List<Integer> novedades = p.novedades() == null ? List.of() : p.novedades();
				if (!novedades.isEmpty() && (p.detalleNovedad() == null || p.detalleNovedad().isBlank())) {
					throw new NegocioException("Describa la novedad que reporta.");
				}
				jdbc.sql("""
					UPDATE ordenes_servicio SET id_estado_maquina = :e, tiene_novedad = :tn, detalle_novedad = :dn
					WHERE id_ordenes_servicio = :o""")
					.param("e", p.idEstadoFinal()).param("tn", !novedades.isEmpty())
					.param("dn", novedades.isEmpty() ? null : p.detalleNovedad().trim()).param("o", idOrden).update();
				for (int n : novedades) {
					jdbc.sql("INSERT IGNORE INTO orden_servicio_novedad (id_orden_servicio, id_tipo_novedad) VALUES (:o, :n)")
						.param("o", idOrden).param("n", n).update();
				}
				jdbc.sql("UPDATE maquina SET activo_operativo = :a, fecha_actualizacion = NOW() WHERE id_maquina = :m")
					.param("a", p.idEstadoFinal() == 1).param("m", c.idMaquina()).update();
			}
			case CIERRE -> {
				pasoCierre(c, p, ahora, usuario.usuarioId());
				siguiente = siguienteParada(c.idTecnico(), p);
				if (siguiente == null) {
					avisos.add("Terminó todos los servicios de su ruta.");
				}
			}
		}

		// Bitácora del paso y enlace de sus fotos
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO orden_servicio_evento (id_orden_servicio, etapa, fecha_hora, latitud, longitud, id_usuario, datos)
			VALUES (:o, :etapa, :ahora, :lat, :lon, :u, :datos)""")
			.param("o", idOrden).param("etapa", etapa.name()).param("ahora", ahora)
			.param("lat", p == null ? null : p.latitud()).param("lon", p == null ? null : p.longitud())
			.param("u", usuario.usuarioId()).param("datos", datos(p, avisos)).update(llave);
		if (etapa.evidencia() != null) {
			jdbc.sql("UPDATE evidencia_servicio SET id_evento = :e WHERE id_orden_servicio = :o AND tipo_evidencia = :t AND id_evento IS NULL")
				.param("e", llave.getKey().intValue()).param("o", idOrden).param("t", etapa.evidencia()).update();
		}
		jdbc.sql("UPDATE ordenes_servicio SET etapa_actual = :e WHERE id_ordenes_servicio = :o")
			.param("e", etapa.name()).param("o", idOrden).update();

		return new RespuestaPaso(vista(ctx(idOrden, false)), siguiente, avisos);
	}

	// ------------------------------------------------------------------ pasos con más reglas
	private void pasoSeriales(Ctx c, Paso p, List<String> avisos) {
		String maquina = texto(p.serialMaquina(), 3, "el serial de la máquina");
		List<String> diferencias = new ArrayList<>();
		comparar("máquina", c.serialMaquinaEsperado(), maquina, diferencias);
		comparar("router", c.serialRouterEsperado(), p.serialRouter(), diferencias);
		comparar("UPS", c.serialUpsEsperado(), p.serialUps(), diferencias);
		Boolean coinciden = c.serialMaquinaEsperado() == null ? null : diferencias.isEmpty();
		if (Boolean.FALSE.equals(coinciden) && parametros.entero(Parametros.SERIALES_BLOQUEAN, 0) == 1) {
			throw new NegocioException("Los seriales no coinciden con los registrados (" + String.join(", ", diferencias)
				+ "). Verifíquelos o comuníquese con el Supervisor.");
		}
		if (Boolean.FALSE.equals(coinciden)) {
			avisos.add("Los seriales no coinciden con los registrados (" + String.join(", ", diferencias)
				+ "). Se avisó al Supervisor; puede continuar.");
		}
		jdbc.sql("""
			UPDATE ordenes_servicio_complemento SET numero_maquina = :num, serial_maquina = :sm, serial_router = :sr,
			       serial_ups = :su, seriales_coinciden = :co, detalle_diferencia_serial = :det
			WHERE id_orden_servicio = :o""")
			.param("num", recortar(p.numeroMaquina(), 30)).param("sm", recortar(maquina, 30))
			.param("sr", recortar(p.serialRouter(), 30)).param("su", recortar(p.serialUps(), 30)).param("co", coinciden)
			.param("det", diferencias.isEmpty() ? null : recortar(String.join("; ", diferencias), 255))
			.param("o", c.idOrden()).update();
		if (c.serialMaquinaEsperado() == null) {
			// Primera vez que se registran: quedan como los esperados de la máquina.
			jdbc.sql("UPDATE maquina SET serial_maquina = :sm, serial_router = :sr, serial_ups = :su WHERE id_maquina = :m")
				.param("sm", recortar(maquina, 30)).param("sr", recortar(vacioANull(p.serialRouter()), 30))
				.param("su", recortar(vacioANull(p.serialUps()), 30)).param("m", c.idMaquina()).update();
		}
	}

	private void pasoRepuestos(Ctx c, Paso p, int usuario) {
		List<RepuestoUsado> usados = p == null || p.repuestos() == null ? List.of() : p.repuestos();
		if (!usados.isEmpty()) {
			exigirFoto(c, "repuestos", "Repuestos usados");
		}
		Map<String, Integer> agrupados = new LinkedHashMap<>();
		for (RepuestoUsado r : usados) {
			String origen = r.origen() == null ? "" : r.origen().trim().toUpperCase(Locale.ROOT);
			if (origen.equals("STOCK") || origen.equals("PROPIO")) {
				origen = "INEES";
			}
			if (!origen.equals("INEES") && !origen.equals("PROSEGUR")) {
				throw new NegocioException("Indique si cada repuesto es de su stock o lo aportó Prosegur.");
			}
			if (r.cantidad() <= 0) {
				throw new NegocioException("La cantidad de cada repuesto debe ser mayor que cero.");
			}
			agrupados.merge(r.idRepuesto() + "|" + origen, r.cantidad(), Integer::sum);
		}
		for (var e : agrupados.entrySet()) {
			int idRepuesto = Integer.parseInt(e.getKey().split("\\|")[0]);
			String origen = e.getKey().split("\\|")[1];
			int cantidad = e.getValue();
			Integer idMovimiento = null;
			if (origen.equals("INEES")) {
				idMovimiento = kardex.mover(c.idTecnico(), idRepuesto, Kardex.Tipo.CONSUMO, -cantidad, c.idOrden(), usuario,
					"Usado en el servicio");
			}
			jdbc.sql("""
				INSERT INTO orden_servicio_repuesto (id_orden_servicio, id_repuesto, origen, cantidad, valor_unitario, id_movimiento)
				SELECT :o, id_repuesto, :origen, :c, valor_venta, :mov FROM repuesto WHERE id_repuesto = :r""")
				.param("o", c.idOrden()).param("origen", origen).param("c", cantidad).param("mov", idMovimiento)
				.param("r", idRepuesto).update();
			// Igual que hoy: las piezas que se devuelven quedan pendientes de devolución.
			jdbc.sql("""
				INSERT INTO control_devolucion_repuestos (id_tecnico, id_orden_servicio, id_repuesto, cantidad, estado_devolucion)
				SELECT :t, :o, id_repuesto, :c, 'Pendiente' FROM repuesto WHERE id_repuesto = :r AND requiere_devolucion = 1""")
				.param("t", c.idTecnico()).param("o", c.idOrden()).param("c", cantidad).param("r", idRepuesto).update();
		}
	}

	private void pasoCierre(Ctx c, Paso p, LocalDateTime ahora, int usuario) {
		String remision = texto(p.numeroRemision(), 1, "el número de la remisión");
		Integer idControl = jdbc.sql("""
			SELECT id_control FROM control_remisiones
			WHERE id_tecnico = :t AND numero_remision = :n
			  AND id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE' LIMIT 1)""")
			.param("t", c.idTecnico()).param("n", remision).query(Integer.class).optional().orElse(null);
		if (idControl == null) {
			throw new NegocioException("La remisión " + remision + " no está disponible en su talonario.");
		}
		String firma = almacenamiento.guardarFirma(c.idOrden(), p.firma());
		jdbc.sql("INSERT INTO evidencia_servicio (id_orden_servicio, tipo_evidencia, ruta_archivo, fecha_subida) VALUES (:o, 'firma', :r, :f)")
			.param("o", c.idOrden()).param("r", firma).param("f", ahora).update();

		long minutos = Duration.between(c.horaIngreso(), ahora).toMinutes();
		BigDecimal valor = valorizacion.tarifa(c.idTipoMaquina(), c.idTipoMantenimiento(), c.idModalidad(), c.fechaVisita());
		var viaticos = valorizacion.viaticos(c.idTecnico(), c.fechaVisita(), c.idModalidad(), c.idPunto(), c.idOrden());

		jdbc.sql("""
			UPDATE ordenes_servicio SET estado = 1, hora_salida = :hs, minutos_servicio = :min,
			       numero_remision = :rem, valor_servicio = :valor, es_fuera_delegacion = :fuera, dias_viaticos = :dias,
			       valor_viaticos = :viat, id_calificacion = :cal
			WHERE id_ordenes_servicio = :o""")
			.param("hs", ahora.toLocalTime()).param("min", minutos)
			.param("rem", remision).param("valor", valor == null ? BigDecimal.ZERO : valor)
			.param("fuera", viaticos.fueraDelegacion()).param("dias", viaticos.dias()).param("viat", viaticos.valor())
			.param("cal", p.idCalificacion()).param("o", c.idOrden()).update();
		jdbc.sql("""
			UPDATE ordenes_servicio_complemento SET pendientes = :pen, latitud_fin = :lat, longitud_fin = :lon
			WHERE id_orden_servicio = :o""")
			.param("pen", vacioANull(p.pendientes())).param("lat", p.latitud()).param("lon", p.longitud())
			.param("o", c.idOrden()).update();
		jdbc.sql("""
			UPDATE control_remisiones SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1),
			       fecha_uso = :f, id_orden_servicio = :o WHERE id_control = :c""")
			.param("f", ahora).param("o", c.idOrden()).param("c", idControl).update();
		valorizacion.actualizarUltimaVisita(c.idPunto(), c.idMaquina());
		marcarParada(c.idOrden(), "HECHA");
		historial.registrar(c.idOrden(), "estado", "EN_SERVICIO", "FINALIZADA", "Cerrado en campo", usuario);
	}

	// ------------------------------------------------------------------ apoyo
	private Ctx ctx(int idOrden) {
		return ctx(idOrden, true);
	}

	private Ctx ctx(int idOrden, boolean exigirPropia) {
		Ctx c = jdbc.sql("""
			SELECT os.id_ordenes_servicio AS id_orden, os.id_tecnico, os.estado, os.etapa_actual, os.origen, os.prioridad,
			       os.fecha_visita, os.hora_inicio_trayecto, os.hora_llegada, os.hora_ingreso, os.id_maquina, os.id_punto,
			       os.id_tipo_mantenimiento, os.id_modalidad, m.id_tipo_maquina, m.latitud, m.longitud,
			       m.serial_maquina AS serial_maquina_esperado, m.serial_router AS serial_router_esperado,
			       m.serial_ups AS serial_ups_esperado, COALESCE(p.id_delegacion, mu.id_delegacion) AS delegacion_punto
			FROM ordenes_servicio os
			JOIN maquina m ON m.id_maquina = os.id_maquina
			JOIN punto p ON p.id_punto = os.id_punto
			LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
			WHERE os.id_ordenes_servicio = :o""").param("o", idOrden).query(Ctx.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("El servicio"));
		if (exigirPropia && !Objects.equals(c.idTecnico(), Sesion.idTecnico())) {
			throw new NegocioException("Este servicio no está asignado a usted.");
		}
		return c;
	}

	private OrdenCampo vista(Ctx c) {
		record Info(String cliente, String punto, String direccion, String municipio, String deviceId, String tipoMaquina,
			String tipoMantenimiento, String modalidad, String referenciaProsegur, String descripcionSolicitud,
			String actividades) {
		}
		Info i = jdbc.sql("""
			SELECT cl.nombre_cliente AS cliente, p.nombre_punto AS punto, p.direccion, mu.nombre_municipio AS municipio,
			       m.device_id, tmq.nombre_tipo_maquina AS tipo_maquina, tm.nombre_completo AS tipo_mantenimiento,
			       mo.nombre_modalidad AS modalidad, ss.referencia_externa AS referencia_prosegur,
			       ss.descripcion AS descripcion_solicitud, os.actividades_realizadas AS actividades
			FROM ordenes_servicio os
			JOIN maquina m ON m.id_maquina = os.id_maquina
			JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = m.id_tipo_maquina
			JOIN punto p ON p.id_punto = os.id_punto
			JOIN cliente cl ON cl.id_cliente = p.id_cliente
			LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
			LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = os.id_tipo_mantenimiento
			LEFT JOIN modalidad_operativa mo ON mo.id_modalidad = os.id_modalidad
			LEFT JOIN solicitud_servicio ss ON ss.id_solicitud = os.id_solicitud
			WHERE os.id_ordenes_servicio = :o""").param("o", c.idOrden()).query(Info.class).single();
		Complemento comp = jdbc.sql("""
			SELECT numero_maquina, serial_maquina, serial_router, serial_ups, seriales_coinciden,
			       administrador_punto AS administrador, celular_encargado AS celular, id_estado_inicial,
			       novedad_estado_inicial AS detalle_estado_inicial
			FROM ordenes_servicio_complemento WHERE id_orden_servicio = :o""").param("o", c.idOrden())
			.query(Complemento.class).optional().orElse(null);
		List<Evidencia> evidencias = jdbc.sql("""
			SELECT id_evidencia, tipo_evidencia AS tipo, ruta_archivo AS ruta, fecha_subida AS fecha FROM evidencia_servicio
			WHERE id_orden_servicio = :o AND fecha_subida >= :desde ORDER BY id_evidencia""")
			.param("o", c.idOrden()).param("desde", c.horaInicioTrayecto() == null ? LocalDateTime.of(1900, 1, 1, 0, 0) : c.horaInicioTrayecto())
			.query(Evidencia.class).list();
		Map<String, String> fotoPorPaso = new LinkedHashMap<>();
		for (Etapa e : Etapa.values()) {
			if (e.evidencia() != null) {
				fotoPorPaso.put(e.name(), e.evidencia());
			}
		}
		Integer esperando = c.horaLlegada() != null && c.estado() == 5
			? (int) Duration.between(c.horaLlegada(), LocalDateTime.now()).toMinutes() : null;
		String maps = c.latitud() == null ? null : Geo.enlaceMaps(null, null, c.latitud(), c.longitud());
		return new OrdenCampo(c.idOrden(), c.estado(), c.etapaActual(),
			Etapa.siguientes(Etapa.de(c.etapaActual())).stream().map(Enum::name).toList(), i.cliente(), i.punto(),
			i.direccion(), i.municipio(), i.deviceId(), i.tipoMaquina(), i.tipoMantenimiento(), i.modalidad(),
			i.referenciaProsegur(), i.descripcionSolicitud(), c.latitud(), c.longitud(), c.horaLlegada(), c.horaIngreso(),
			esperando, parametros.entero(Parametros.ESPERA_MAX, 45), i.actividades(), comp, evidencias, fotoPorPaso, maps);
	}

	private List<ParadaCampo> paradas(int idRuta, Double latActual, Double lonActual) {
		record Fila(int idParada, int secuencia, String estadoParada, LocalTime horaEstimada, int idOrden, int estadoOrden,
			String etapaActual, String cliente, String punto, String direccion, String municipio, String deviceId,
			String tipoMaquina, String tipoMantenimiento, Double latitud, Double longitud) {
		}
		List<Fila> filas = jdbc.sql("""
			SELECT rp.id_parada, rp.secuencia, rp.estado AS estado_parada, rp.hora_estimada, os.id_ordenes_servicio AS id_orden,
			       os.estado AS estado_orden, os.etapa_actual, c.nombre_cliente AS cliente, p.nombre_punto AS punto,
			       p.direccion, mu.nombre_municipio AS municipio, m.device_id, tmq.nombre_tipo_maquina AS tipo_maquina,
			       tm.nombre_completo AS tipo_mantenimiento, m.latitud, m.longitud
			FROM ruta_parada rp
			JOIN ordenes_servicio os ON os.id_ordenes_servicio = rp.id_orden_servicio
			JOIN maquina m ON m.id_maquina = os.id_maquina
			JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = m.id_tipo_maquina
			JOIN punto p ON p.id_punto = os.id_punto
			JOIN cliente c ON c.id_cliente = p.id_cliente
			LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
			LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = os.id_tipo_mantenimiento
			WHERE rp.id_ruta = :r ORDER BY rp.secuencia""").param("r", idRuta).query(Fila.class).list();
		List<ParadaCampo> lista = new ArrayList<>();
		boolean siguienteMarcada = false;
		for (Fila f : filas) {
			boolean esSiguiente = !siguienteMarcada && "PENDIENTE".equals(f.estadoParada());
			siguienteMarcada |= esSiguiente;
			String maps = f.latitud() == null ? null : Geo.enlaceMaps(latActual, lonActual, f.latitud(), f.longitud());
			lista.add(new ParadaCampo(f.idParada(), f.secuencia(), f.estadoParada(), f.horaEstimada(), f.idOrden(),
				f.estadoOrden(), f.etapaActual(), f.cliente(), f.punto(), f.direccion(), f.municipio(), f.deviceId(),
				f.tipoMaquina(), f.tipoMantenimiento(), f.latitud(), f.longitud(), esSiguiente, maps));
		}
		return lista;
	}

	/** Siguiente parada pendiente, con el enlace de Google Maps desde la ubicación actual. */
	private ParadaCampo siguienteParada(int idTecnico, Paso p) {
		Integer idRuta = jdbc.sql("SELECT id_ruta FROM ruta_diaria WHERE id_tecnico = :t AND fecha = :hoy AND estado IN ('PUBLICADA','EN_CURSO')")
			.param("t", idTecnico).param("hoy", LocalDate.now()).query(Integer.class).optional().orElse(null);
		if (idRuta == null) {
			return null;
		}
		return paradas(idRuta, p == null ? null : p.latitud(), p == null ? null : p.longitud()).stream()
			.filter(ParadaCampo::esSiguiente).findFirst().orElse(null);
	}

	private Integer primeraPendiente(int idTecnico) {
		return jdbc.sql("""
			SELECT rp.id_orden_servicio FROM ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta
			WHERE r.id_tecnico = :t AND r.fecha = :hoy AND r.estado IN ('PUBLICADA','EN_CURSO') AND rp.estado = 'PENDIENTE'
			ORDER BY rp.secuencia LIMIT 1""").param("t", idTecnico).param("hoy", LocalDate.now()).query(Integer.class)
			.optional().orElse(null);
	}

	private void exigirJornada(int idTecnico) {
		int n = jdbc.sql("SELECT COUNT(*) FROM turno_tecnico WHERE id_tecnico = :t AND fecha = :hoy AND hora_salida IS NULL")
			.param("t", idTecnico).param("hoy", LocalDate.now()).query(Integer.class).single();
		if (n == 0) {
			throw new NegocioException("Primero inicie la jornada.");
		}
	}

	private void exigirFoto(Ctx c, String tipo, String paso) {
		LocalDateTime desde = c.horaInicioTrayecto() == null ? LocalDateTime.of(1900, 1, 1, 0, 0) : c.horaInicioTrayecto();
		int n = jdbc.sql("SELECT COUNT(*) FROM evidencia_servicio WHERE id_orden_servicio = :o AND tipo_evidencia = :t AND fecha_subida >= :d")
			.param("o", c.idOrden()).param("t", tipo).param("d", desde).query(Integer.class).single();
		if (n == 0) {
			throw new NegocioException("Tome la foto de «" + paso + "» antes de continuar.");
		}
	}

	private static void exigirGps(Paso p) {
		if (p == null || p.latitud() == null || p.longitud() == null) {
			throw new NegocioException("Active el GPS del celular para reportar la llegada.");
		}
	}

	private void actualizar(int idOrden, String set, LocalDateTime ahora) {
		jdbc.sql("UPDATE ordenes_servicio SET " + set + " WHERE id_ordenes_servicio = :o")
			.param("ahora", ahora).param("o", idOrden).update();
	}

	private void marcarParada(int idOrden, String estado) {
		jdbc.sql("""
			UPDATE ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta SET rp.estado = :e
			WHERE rp.id_orden_servicio = :o AND rp.estado = 'PENDIENTE' AND r.estado IN ('PUBLICADA','EN_CURSO')""")
			.param("e", estado).param("o", idOrden).update();
	}

	private static void comparar(String que, String esperado, String recibido, List<String> diferencias) {
		if (esperado == null || esperado.isBlank()) {
			return;
		}
		if (!normalizarSerial(esperado).equals(normalizarSerial(recibido))) {
			diferencias.add(que + ": registrado " + esperado + ", leído " + (recibido == null || recibido.isBlank() ? "(vacío)" : recibido));
		}
	}

	static String normalizarSerial(String s) {
		return s == null ? "" : s.toUpperCase(Locale.ROOT).replaceAll("[^A-Z0-9]", "");
	}

	private static String texto(String valor, int minimo, String que) {
		if (valor == null || valor.trim().length() < minimo) {
			throw new NegocioException("Escriba " + que + ".");
		}
		return valor.trim();
	}

	private static String vacioANull(String s) {
		return s == null || s.isBlank() ? null : s.trim();
	}

	private static String recortar(String s, int max) {
		return s == null || s.length() <= max ? s : s.substring(0, max);
	}

	private String datos(Paso p, List<String> avisos) {
		if (p == null) {
			return null;
		}
		Map<String, Object> m = JSON.convertValue(p, Map.class);
		m.values().removeIf(Objects::isNull);
		m.remove("firma");
		if (!avisos.isEmpty()) {
			m.put("avisos", avisos);
		}
		return m.isEmpty() ? null : JSON.writeValueAsString(m);
	}

	private List<Item> items(String sql) {
		return jdbc.sql(sql).query((rs, i) -> new Item(rs.getInt(1), rs.getString(2))).list();
	}
}
