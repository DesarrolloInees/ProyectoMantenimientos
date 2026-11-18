package co.inees.plataforma.planeacion;

import java.time.LocalDate;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import co.inees.plataforma.comun.Historial;
import co.inees.plataforma.comun.Modalidad;
import co.inees.plataforma.comun.Parametros;
import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.seguridad.Sesion;

/**
 * Bolsa de servicios y rutas diarias. El Supervisor y el Auxiliar pueden cambiar cualquier cosa en cualquier momento;
 * cada cambio queda en orden_servicio_historial.
 */
@Service
public class PlaneacionService {

	private static final Logger log = LoggerFactory.getLogger(PlaneacionService.class);

	// ------------------------------------------------------------------ vistas
	public record Orden(int idOrden, int idMaquina, String deviceId, String tipoMaquina, int idPunto, String punto,
		String direccion, String cliente, String municipio, Double latitud, Double longitud, String origen, int prioridad,
		int vecesReprogramada, LocalDate fechaVisita, Integer idTipoMantenimiento, String tipoMantenimiento, int estado,
		String etapaActual, boolean esVip, String referenciaProsegur) {
	}

	public record Parada(int idParada, int secuencia, String estadoParada, boolean fijada, Double kmTramo,
		Integer minutosTramo, LocalTime horaEstimada, Orden orden) {
	}

	public record Ruta(int idRuta, int idTecnico, String tecnico, LocalDate fecha, String estado, Double latitudPartida,
		Double longitudPartida, Double kmEstimados, Integer minutosEstimados, int capacidadMinutos, List<Parada> paradas) {
	}

	public record Plan(LocalDate fecha, int idDelegacion, List<Ruta> rutas, List<Orden> bolsa, List<Orden> sinUbicacion,
		Map<Integer, Long> bolsaPorPrioridad) {
	}

	public record ResultadoGeneracion(Plan plan, int asignados, int preventivosCreados, int sinCupo) {
	}

	// ------------------------------------------------------------------ dependencias
	private final JdbcClient jdbc;
	private final Parametros parametros;
	private final Historial historial;
	private final Modalidad modalidad;
	private final DistanciasViales distanciasViales;

	public PlaneacionService(JdbcClient jdbc, Parametros parametros, Historial historial, Modalidad modalidad,
		DistanciasViales distanciasViales) {
		this.jdbc = jdbc;
		this.parametros = parametros;
		this.historial = historial;
		this.modalidad = modalidad;
		this.distanciasViales = distanciasViales;
	}

	/** Optimizador con línea recta × factor vial (para lo que no son tramos de ruta, como las zonas). */
	private Optimizador optimizador() {
		return optimizador(List.of());
	}

	/** Optimizador con la distancia por carretera entre estos puntos ({lat, lon}) si hay OSRM configurado. */
	private Optimizador optimizador(List<double[]> puntos) {
		double factor = parametros.decimal(Parametros.FACTOR_VIAL, 1.35);
		return new Optimizador(parametros.decimal(Parametros.VELOCIDAD, 25), factor,
			parametros.decimal(Parametros.KM_POR_DIA_ATRASO, 0.25), distanciasViales.matriz(puntos, factor));
	}

	// ================================================================== consultar
	public Plan plan(LocalDate fecha, int idDelegacion) {
		List<Ruta> rutas = jdbc.sql("""
			SELECT r.id_ruta FROM ruta_diaria r JOIN tecnico t ON t.id_tecnico = r.id_tecnico
			WHERE r.fecha = :fecha AND t.id_delegacion = :del ORDER BY t.nombre_tecnico""")
			.param("fecha", fecha).param("del", idDelegacion).query(Integer.class).list()
			.stream().map(this::ruta).toList();

		List<Orden> bolsa = jdbc.sql(SELECT_ORDEN + """
			WHERE os.estado = 3 AND os.fecha_visita <= :fecha
			  AND COALESCE(p.id_delegacion, mu.id_delegacion) = :del
			  AND NOT EXISTS (SELECT 1 FROM ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta
			                   WHERE rp.id_orden_servicio = os.id_ordenes_servicio AND r.fecha = :fecha
			                     AND rp.estado = 'PENDIENTE')
			ORDER BY os.prioridad, os.veces_reprogramada DESC, os.fecha_visita, p.nombre_punto""")
			.param("fecha", fecha).param("del", idDelegacion).query(Orden.class).list();

		List<Orden> sinUbicacion = bolsa.stream().filter(o -> o.latitud() == null).toList();
		Map<Integer, Long> porPrioridad = bolsa.stream().collect(Collectors.groupingBy(Orden::prioridad, Collectors.counting()));
		return new Plan(fecha, idDelegacion, rutas, bolsa, sinUbicacion, porPrioridad);
	}

	public Ruta ruta(int idRuta) {
		record Cabecera(int idRuta, int idTecnico, String tecnico, LocalDate fecha, String estado, Double latitudPartida,
			Double longitudPartida, Double kmEstimados, Integer minutosEstimados, Integer minutosJornada) {
		}
		Cabecera c = jdbc.sql("""
			SELECT r.id_ruta, r.id_tecnico, t.nombre_tecnico AS tecnico, r.fecha, r.estado, r.latitud_partida,
			       r.longitud_partida, r.km_estimados, r.minutos_estimados, t.minutos_jornada
			FROM ruta_diaria r JOIN tecnico t ON t.id_tecnico = r.id_tecnico WHERE r.id_ruta = :id""")
			.param("id", idRuta).query(Cabecera.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("La ruta"));

		record FilaParada(int idParada, int secuencia, String estadoParada, boolean fijada, Double kmTramo,
			Integer minutosTramo, LocalTime horaEstimada, int idOrden) {
		}
		List<FilaParada> filas = jdbc.sql("""
			SELECT id_parada, secuencia, estado AS estado_parada, fijada, km_tramo, minutos_tramo, hora_estimada,
			       id_orden_servicio AS id_orden
			FROM ruta_parada WHERE id_ruta = :id ORDER BY secuencia""").param("id", idRuta).query(FilaParada.class).list();
		Map<Integer, Orden> ordenes = ordenes(filas.stream().map(FilaParada::idOrden).toList());
		List<Parada> paradas = filas.stream().map(f -> new Parada(f.idParada(), f.secuencia(), f.estadoParada(), f.fijada(),
			f.kmTramo(), f.minutosTramo(), f.horaEstimada(), ordenes.get(f.idOrden()))).toList();
		int capacidad = c.minutosJornada() != null ? c.minutosJornada() : parametros.entero(Parametros.JORNADA_MIN, 480);
		return new Ruta(c.idRuta(), c.idTecnico(), c.tecnico(), c.fecha(), c.estado(), c.latitudPartida(),
			c.longitudPartida(), c.kmEstimados(), c.minutosEstimados(), capacidad, paradas);
	}

	// ================================================================== generar
	@Transactional
	public ResultadoGeneracion generar(LocalDate fecha, int idDelegacion) {
		if (fecha.isBefore(LocalDate.now())) {
			throw new NegocioException("Solo se pueden armar rutas para hoy o días futuros.");
		}
		reprogramarPendientes();
		int usuario = Sesion.actual().usuarioId();

		record TecnicoFila(int idTecnico, Double latitudPartida, Double longitudPartida, Integer minutosJornada) {
		}
		List<TecnicoFila> tecnicos = jdbc.sql("""
			SELECT t.id_tecnico, t.latitud_partida, t.longitud_partida, t.minutos_jornada FROM tecnico t
			WHERE t.estado = 1 AND t.id_delegacion = :del
			  AND NOT EXISTS (SELECT 1 FROM ruta_diaria r WHERE r.id_tecnico = t.id_tecnico AND r.fecha = :fecha
			                   AND r.estado <> 'BORRADOR')""")
			.param("del", idDelegacion).param("fecha", fecha).query(TecnicoFila.class).list();
		if (tecnicos.isEmpty()) {
			throw new NegocioException("No hay motorizados disponibles en esta delegación (o todas sus rutas ya están publicadas).");
		}
		List<Integer> idsTecnicos = tecnicos.stream().map(TecnicoFila::idTecnico).toList();

		// Lo fijado a mano en el borrador anterior se respeta.
		Map<Integer, Integer> fijadas = new HashMap<>();
		jdbc.sql("""
			SELECT rp.id_orden_servicio, r.id_tecnico FROM ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta
			WHERE r.fecha = :fecha AND r.estado = 'BORRADOR' AND rp.fijada = 1 AND r.id_tecnico IN (:tecnicos)""")
			.param("fecha", fecha).param("tecnicos", idsTecnicos)
			.query((rs, i) -> fijadas.put(rs.getInt(1), rs.getInt(2))).list();
		jdbc.sql("DELETE FROM ruta_diaria WHERE fecha = :fecha AND estado = 'BORRADOR' AND id_tecnico IN (:tecnicos)")
			.param("fecha", fecha).param("tecnicos", idsTecnicos).update();

		// 1) Bolsa actual: solicitudes de Prosegur, arrastrados y preventivos ya creados.
		Plan antes = plan(fecha, idDelegacion);
		List<Optimizador.Servicio> servicios = new ArrayList<>();
		int minutosPool = 0;
		for (Orden o : antes.bolsa()) {
			if (o.latitud() == null) {
				continue;
			}
			int min = parametros.minutosServicio(o.idTipoMantenimiento());
			minutosPool += min + 20;
			servicios.add(new Optimizador.Servicio(o.idOrden(), o.latitud(), o.longitud(), min, o.prioridad(),
				o.vecesReprogramada(), 0, fijadas.get(o.idOrden())));
		}

		// 2) Si sobra jornada, se completa con máquinas VIP y luego normales con mantenimiento vencido.
		int jornadaDefault = parametros.entero(Parametros.JORNADA_MIN, 480);
		int capacidad = tecnicos.stream().mapToInt(t -> t.minutosJornada() != null ? t.minutosJornada() : jornadaDefault).sum();
		int tipoPreventivo = parametros.entero(Parametros.TIPO_PREVENTIVO, 1);
		int minPreventivo = parametros.minutosServicio(tipoPreventivo);
		record Candidata(int idMaquina, double latitud, double longitud, int prioridad, Long diasAtraso) {
		}
		Map<Integer, Candidata> candidatas = new HashMap<>();
		int sobrante = capacidad - minutosPool;
		if (sobrante > 0) {
			// Varias veces lo que cabe: el optimizador elige entre ellas las más cercanas (con premio por atraso).
			int limite = Math.min(2000, (int) Math.ceil(sobrante / (double) (minPreventivo + 20) * 8) + 50);
			jdbc.sql("""
				SELECT v.id_maquina, v.latitud, v.longitud, v.prioridad, DATEDIFF(:fecha, v.fecha_vencimiento) AS dias_atraso
				FROM v_mantenimiento_pendiente v
				WHERE v.id_delegacion = :del AND v.latitud IS NOT NULL
				  AND (v.fecha_vencimiento IS NULL OR v.fecha_vencimiento <= :fecha)
				  AND NOT EXISTS (SELECT 1 FROM ordenes_servicio o WHERE o.id_maquina = v.id_maquina AND o.estado IN (2,3,4,5,6))
				ORDER BY v.prioridad, v.activo_operativo, v.fecha_vencimiento IS NULL, v.fecha_vencimiento
				LIMIT :limite""")
				.param("fecha", fecha).param("del", idDelegacion).param("limite", limite)
				.query(Candidata.class).list()
				.forEach(c -> {
					candidatas.put(c.idMaquina(), c);
					// Clave negativa: todavía no es una orden; solo se crea si le cabe a alguien.
					servicios.add(new Optimizador.Servicio(-c.idMaquina(), c.latitud(), c.longitud(), minPreventivo,
						c.prioridad(), 0, c.diasAtraso() == null ? 0 : c.diasAtraso(), null));
				});
		}

		// 3) Optimizar. Quien no tiene punto de partida sale del centro de una zona de la ciudad base de la delegación
		// (los motorizados salen de la ciudad, no de un municipio vecino); cada uno recibe una zona distinta.
		double[][] zonas = zonasCiudadBase(idDelegacion, (int) tecnicos.stream().filter(t -> t.latitudPartida() == null
			|| t.longitudPartida() == null).count());
		List<Optimizador.Tecnico> tecs = new ArrayList<>();
		int z = 0;
		for (TecnicoFila t : tecnicos) {
			Double lat = t.latitudPartida(), lon = t.longitudPartida();
			if ((lat == null || lon == null) && zonas != null) {
				lat = zonas[z][0];
				lon = zonas[z][1];
				z++;
			}
			tecs.add(new Optimizador.Tecnico(t.idTecnico(), lat, lon, t.minutosJornada() != null ? t.minutosJornada() : jornadaDefault));
		}
		List<double[]> puntos = new ArrayList<>();
		tecs.stream().filter(t -> t.latPartida() != null && t.lonPartida() != null)
			.forEach(t -> puntos.add(new double[] { t.latPartida(), t.lonPartida() }));
		servicios.forEach(s -> puntos.add(new double[] { s.lat(), s.lon() }));
		Optimizador.Resultado res = optimizador(puntos).optimizar(tecs, servicios);

		// 4) Guardar borradores
		int asignados = 0, preventivos = 0;
		for (Optimizador.Ruta r : res.rutas()) {
			LocalTime inicio = horaInicio(r.tecnico().id());
			var llave = new GeneratedKeyHolder();
			jdbc.sql("""
				INSERT INTO ruta_diaria (id_tecnico, fecha, estado, latitud_partida, longitud_partida, km_estimados, minutos_estimados)
				VALUES (:tec, :fecha, 'BORRADOR', :lat, :lon, :km, :min)""")
				.param("tec", r.tecnico().id()).param("fecha", fecha).param("lat", r.latPartida()).param("lon", r.lonPartida())
				.param("km", Math.round(r.km() * 100) / 100.0).param("min", r.minutos()).update(llave);
			int idRuta = llave.getKey().intValue();
			int sec = 1;
			for (Optimizador.Parada p : r.paradas()) {
				int idOrden = p.servicio().clave();
				if (idOrden < 0) {
					Candidata c = candidatas.get(-idOrden);
					idOrden = crearPreventiva(c.idMaquina(), c.prioridad(), tipoPreventivo, fecha, usuario);
					preventivos++;
				}
				jdbc.sql("""
					INSERT INTO ruta_parada (id_ruta, id_orden_servicio, secuencia, km_tramo, minutos_tramo, hora_estimada, fijada)
					VALUES (:ruta, :orden, :sec, :km, :min, :hora, :fijada)""")
					.param("ruta", idRuta).param("orden", idOrden).param("sec", sec++)
					.param("km", Math.round(p.kmTramo() * 100) / 100.0).param("min", p.minutosTramo())
					.param("hora", inicio.plusMinutes(p.minutoLlegada()))
					.param("fijada", p.servicio().tecnicoFijado() != null).update();
				asignados++;
			}
		}
		int sinCupo = (int) res.sinCupo().stream().filter(s -> s.clave() > 0).count();
		log.info("Rutas generadas {} delegación {}: {} paradas, {} preventivos nuevos, {} sin cupo", fecha, idDelegacion,
			asignados, preventivos, sinCupo);
		return new ResultadoGeneracion(plan(fecha, idDelegacion), asignados, preventivos, sinCupo);
	}

	// ================================================================== publicar
	@Transactional
	public Plan publicar(LocalDate fecha, int idDelegacion) {
		List<Integer> borradores = jdbc.sql("""
			SELECT r.id_ruta FROM ruta_diaria r JOIN tecnico t ON t.id_tecnico = r.id_tecnico
			WHERE r.fecha = :fecha AND t.id_delegacion = :del AND r.estado = 'BORRADOR'""")
			.param("fecha", fecha).param("del", idDelegacion).query(Integer.class).list();
		if (borradores.isEmpty()) {
			throw new NegocioException("No hay rutas en borrador para publicar.");
		}
		borradores.forEach(this::publicarRuta);
		return plan(fecha, idDelegacion);
	}

	@Transactional
	public Ruta publicarRuta(int idRuta) {
		Ruta r = ruta(idRuta);
		if (!"BORRADOR".equals(r.estado())) {
			throw new NegocioException("La ruta ya está publicada.");
		}
		int usuario = Sesion.actual().usuarioId();
		jdbc.sql("UPDATE ruta_diaria SET estado = 'PUBLICADA', id_usuario_publica = :u, fecha_publicacion = NOW() WHERE id_ruta = :id")
			.param("u", usuario).param("id", idRuta).update();
		for (Parada p : r.paradas()) {
			if ("PENDIENTE".equals(p.estadoParada())) {
				programar(p.orden().idOrden(), r.idTecnico(), r.fecha(), usuario, "Ruta publicada");
			}
		}
		return ruta(idRuta);
	}

	// ================================================================== cambios manuales
	/** Mueve un servicio a otra ruta (en la posición indicada) o lo devuelve a la bolsa (idRutaDestino = null). */
	@Transactional
	public void mover(int idOrden, Integer idRutaDestino, Integer posicion, String motivo) {
		int usuario = Sesion.actual().usuarioId();
		Orden o = orden(idOrden);
		if (o.estado() != 2 && o.estado() != 3) {
			throw new NegocioException("El servicio ya está en curso o terminado; no se puede mover.");
		}
		Integer rutaOrigen = jdbc.sql("""
			SELECT rp.id_ruta FROM ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta
			WHERE rp.id_orden_servicio = :o AND rp.estado = 'PENDIENTE' AND r.estado <> 'CERRADA' LIMIT 1""")
			.param("o", idOrden).query(Integer.class).optional().orElse(null);
		if (rutaOrigen != null) {
			jdbc.sql("DELETE FROM ruta_parada WHERE id_ruta = :r AND id_orden_servicio = :o")
				.param("r", rutaOrigen).param("o", idOrden).update();
		}
		if (idRutaDestino == null) {
			jdbc.sql("UPDATE ordenes_servicio SET estado = 3, id_tecnico = NULL WHERE id_ordenes_servicio = :o")
				.param("o", idOrden).update();
			historial.registrar(idOrden, "ruta", rutaOrigen, "bolsa", motivo == null ? "Devuelto a la bolsa" : motivo, usuario);
		}
		else {
			Ruta destino = ruta(idRutaDestino);
			if ("CERRADA".equals(destino.estado())) {
				throw new NegocioException("La ruta destino ya está cerrada.");
			}
			if (o.latitud() == null) {
				throw new NegocioException("La máquina no tiene ubicación: póngale el pin antes de meterla en una ruta.");
			}
			List<Integer> pendientes = destino.paradas().stream().filter(p -> "PENDIENTE".equals(p.estadoParada()))
				.map(p -> p.orden().idOrden()).collect(Collectors.toCollection(ArrayList::new));
			int pos = posicion == null ? pendientes.size() : Math.max(0, Math.min(posicion, pendientes.size()));
			pendientes.add(pos, idOrden);
			jdbc.sql("DELETE FROM ruta_parada WHERE id_ruta = :r AND id_orden_servicio = :o")
				.param("r", idRutaDestino).param("o", idOrden).update();
			jdbc.sql("INSERT INTO ruta_parada (id_ruta, id_orden_servicio, secuencia) VALUES (:r, :o, 9999)")
				.param("r", idRutaDestino).param("o", idOrden).update();
			resecuenciar(idRutaDestino, pendientes);
			if (!"BORRADOR".equals(destino.estado())) {
				programar(idOrden, destino.idTecnico(), destino.fecha(), usuario, motivo == null ? "Movido a otra ruta" : motivo);
			}
			historial.registrar(idOrden, "ruta", rutaOrigen == null ? "bolsa" : rutaOrigen, idRutaDestino, motivo, usuario);
		}
		if (rutaOrigen != null) {
			recalcularTramos(rutaOrigen);
		}
	}

	/** Cambia el orden de los servicios pendientes de una ruta. */
	@Transactional
	public Ruta reordenar(int idRuta, List<Integer> idsOrden) {
		Ruta r = ruta(idRuta);
		List<Integer> actuales = r.paradas().stream().filter(p -> "PENDIENTE".equals(p.estadoParada()))
			.map(p -> p.orden().idOrden()).toList();
		if (actuales.size() != idsOrden.size() || !actuales.containsAll(idsOrden)) {
			throw new NegocioException("El nuevo orden debe incluir exactamente los servicios pendientes de la ruta.");
		}
		resecuenciar(idRuta, idsOrden);
		for (int i = 0; i < idsOrden.size(); i++) {
			if (!idsOrden.get(i).equals(actuales.get(i))) {
				historial.registrar(idsOrden.get(i), "secuencia", actuales.indexOf(idsOrden.get(i)) + 1, i + 1,
					"Reordenado a mano", Sesion.actual().usuarioId());
			}
		}
		return ruta(idRuta);
	}

	/** Recalcula el mejor orden de los servicios pendientes (2-opt) desde el último punto atendido. */
	@Transactional
	public Ruta recalcular(int idRuta) {
		Ruta r = ruta(idRuta);
		double[] desde = { r.latitudPartida() == null ? 0 : r.latitudPartida(), r.longitudPartida() == null ? 0 : r.longitudPartida() };
		List<Optimizador.Servicio> pendientes = new ArrayList<>();
		for (Parada p : r.paradas()) {
			if ("PENDIENTE".equals(p.estadoParada())) {
				pendientes.add(new Optimizador.Servicio(p.orden().idOrden(), p.orden().latitud(), p.orden().longitud(),
					parametros.minutosServicio(p.orden().idTipoMantenimiento()), p.orden().prioridad(), 0, 0, null));
			}
			else if (p.orden().latitud() != null) {
				desde = new double[] { p.orden().latitud(), p.orden().longitud() };
			}
		}
		List<double[]> puntos = new ArrayList<>();
		puntos.add(desde);
		pendientes.forEach(s -> puntos.add(new double[] { s.lat(), s.lon() }));
		List<Integer> orden = optimizador(puntos).dosOpt(pendientes, desde).stream().map(Optimizador.Servicio::clave).toList();
		resecuenciar(idRuta, orden);
		return ruta(idRuta);
	}

	@Transactional
	public Ruta fijar(int idParada, boolean fijada) {
		Integer idRuta = jdbc.sql("SELECT id_ruta FROM ruta_parada WHERE id_parada = :p").param("p", idParada)
			.query(Integer.class).optional().orElseThrow(() -> NegocioException.noEncontrado("La parada"));
		jdbc.sql("UPDATE ruta_parada SET fijada = :f WHERE id_parada = :p").param("f", fijada).param("p", idParada).update();
		return ruta(idRuta);
	}

	/** Servicio pedido directamente por la oficina (antes «servicio extra» del técnico). */
	@Transactional
	public Orden crearManual(int idMaquina, int idTipoMantenimiento, LocalDate fecha, String motivo) {
		int usuario = Sesion.actual().usuarioId();
		Integer abierta = jdbc.sql("SELECT id_ordenes_servicio FROM ordenes_servicio WHERE id_maquina = :m AND estado IN (2,3,4,5,6) LIMIT 1")
			.param("m", idMaquina).query(Integer.class).optional().orElse(null);
		if (abierta != null) {
			throw new NegocioException("La máquina ya tiene un servicio abierto (orden " + abierta + ").");
		}
		var llave = new GeneratedKeyHolder();
		int n = jdbc.sql("""
			INSERT INTO ordenes_servicio (id_cliente, id_punto, id_modalidad, id_maquina, id_tipo_mantenimiento, fecha_visita,
			       estado, origen, prioridad, tiene_novedad, created_at)
			SELECT p.id_cliente, p.id_punto, p.id_modalidad, m.id_maquina, :tipo, :fecha, 3, 'MANUAL', 1, 0, NOW()
			FROM maquina m JOIN punto p ON p.id_punto = m.id_punto WHERE m.id_maquina = :m AND m.estado = 1""")
			.param("tipo", idTipoMantenimiento).param("fecha", fecha).param("m", idMaquina).update(llave);
		if (n == 0) {
			throw NegocioException.noEncontrado("La máquina");
		}
		int id = llave.getKey().intValue();
		historial.registrar(id, "estado", null, "PENDIENTE", motivo == null ? "Creado por la oficina" : motivo, usuario);
		return orden(id);
	}

	@Transactional
	public void cancelar(int idOrden, String motivo) {
		Orden o = orden(idOrden);
		if (o.estado() != 2 && o.estado() != 3) {
			throw new NegocioException("Solo se cancelan servicios que no han empezado.");
		}
		if (motivo == null || motivo.isBlank()) {
			throw new NegocioException("Indique el motivo de la cancelación.");
		}
		List<Integer> rutas = jdbc.sql("SELECT id_ruta FROM ruta_parada WHERE id_orden_servicio = :o AND estado = 'PENDIENTE'")
			.param("o", idOrden).query(Integer.class).list();
		jdbc.sql("DELETE FROM ruta_parada WHERE id_orden_servicio = :o AND estado = 'PENDIENTE'").param("o", idOrden).update();
		jdbc.sql("UPDATE ordenes_servicio SET estado = 9 WHERE id_ordenes_servicio = :o").param("o", idOrden).update();
		historial.registrar(idOrden, "estado", o.estado(), 9, motivo, Sesion.actual().usuarioId());
		rutas.forEach(this::recalcularTramos);
	}

	/**
	 * Centros de {@code k} zonas de la ciudad base de la delegación (k-means sobre las máquinas ubicadas en municipios no
	 * interurbanos). Null si no hace falta o si la delegación no tiene máquinas ubicadas en su ciudad base.
	 */
	private double[][] zonasCiudadBase(int idDelegacion, int k) {
		if (k == 0) {
			return null;
		}
		List<Optimizador.Servicio> puntos = new ArrayList<>();
		jdbc.sql("""
			SELECT m.latitud, m.longitud FROM maquina m JOIN punto p ON p.id_punto = m.id_punto
			LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
			WHERE COALESCE(p.id_delegacion, mu.id_delegacion) = :del AND m.estado = 1 AND p.estado = 1
			  AND m.latitud IS NOT NULL AND COALESCE(mu.interurbano, 0) = 0""")
			.param("del", idDelegacion)
			.query((rs, i) -> puntos.add(new Optimizador.Servicio(i + 1, rs.getDouble(1), rs.getDouble(2), 0, 4, 0, 0, null))).list();
		return puntos.size() < k ? null : optimizador().kMeans(puntos, k);
	}

	// ================================================================== tarea diaria
	/**
	 * Lo que no se hizo pasa al día siguiente con mayor prioridad: órdenes programadas que nunca empezaron y órdenes que
	 * siguen en la bolsa con fecha pasada. Corre a las 00:05 y también antes de generar rutas.
	 */
	@Scheduled(cron = "0 5 0 * * *", zone = "America/Bogota")
	@Transactional
	public int reprogramarPendientes() {
		List<Integer> noAlcanzadas = jdbc.sql(
			"SELECT id_ordenes_servicio FROM ordenes_servicio WHERE estado IN (2, 4) AND fecha_visita < CURDATE()")
			.query(Integer.class).list();
		for (int id : noAlcanzadas) {
			jdbc.sql("""
				UPDATE ruta_parada rp JOIN ruta_diaria r ON r.id_ruta = rp.id_ruta SET rp.estado = 'NO_ALCANZADA'
				WHERE rp.id_orden_servicio = :o AND rp.estado = 'PENDIENTE' AND r.fecha < CURDATE()""").param("o", id).update();
			historial.registrar(id, "estado", "PROGRAMADA", "PENDIENTE", "No se alcanzó: pasa al día siguiente con prioridad", null);
		}
		int n = jdbc.sql("""
			UPDATE ordenes_servicio
			   SET estado = 3, id_tecnico = NULL, etapa_actual = NULL, hora_inicio_trayecto = NULL,
			       veces_reprogramada = veces_reprogramada + 1,
			       prioridad = IF(origen = 'SOLICITUD_PROSEGUR', 1, LEAST(prioridad, 2)), fecha_visita = CURDATE()
			 WHERE estado IN (2, 4) AND fecha_visita < CURDATE()""").update();
		n += jdbc.sql("""
			UPDATE ordenes_servicio
			   SET veces_reprogramada = veces_reprogramada + 1,
			       prioridad = IF(origen = 'SOLICITUD_PROSEGUR', 1, LEAST(prioridad, 2)), fecha_visita = CURDATE()
			 WHERE estado = 3 AND fecha_visita < CURDATE()""").update();
		jdbc.sql("UPDATE ruta_diaria SET estado = 'CERRADA' WHERE fecha < CURDATE() AND estado IN ('PUBLICADA', 'EN_CURSO')").update();
		jdbc.sql("DELETE FROM ruta_diaria WHERE fecha < CURDATE() AND estado = 'BORRADOR'").update();
		if (n > 0) {
			log.info("Reprogramados {} servicios no realizados", n);
		}
		return n;
	}

	// ================================================================== apoyo
	static final String SELECT_ORDEN = """
		SELECT os.id_ordenes_servicio AS id_orden, m.id_maquina, m.device_id, tmq.nombre_tipo_maquina AS tipo_maquina,
		       p.id_punto, p.nombre_punto AS punto, p.direccion, c.nombre_cliente AS cliente, mu.nombre_municipio AS municipio,
		       m.latitud, m.longitud, os.origen, os.prioridad, os.veces_reprogramada, os.fecha_visita,
		       os.id_tipo_mantenimiento, tm.nombre_completo AS tipo_mantenimiento, os.estado, os.etapa_actual, m.es_vip,
		       ss.referencia_externa AS referencia_prosegur
		FROM ordenes_servicio os
		JOIN maquina m ON m.id_maquina = os.id_maquina
		JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = m.id_tipo_maquina
		JOIN punto p ON p.id_punto = os.id_punto
		JOIN cliente c ON c.id_cliente = p.id_cliente
		LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
		LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = os.id_tipo_mantenimiento
		LEFT JOIN solicitud_servicio ss ON ss.id_solicitud = os.id_solicitud
		""";

	public Orden orden(int idOrden) {
		return jdbc.sql(SELECT_ORDEN + " WHERE os.id_ordenes_servicio = :id").param("id", idOrden).query(Orden.class)
			.optional().orElseThrow(() -> NegocioException.noEncontrado("El servicio"));
	}

	private Map<Integer, Orden> ordenes(List<Integer> ids) {
		if (ids.isEmpty()) {
			return Map.of();
		}
		return jdbc.sql(SELECT_ORDEN + " WHERE os.id_ordenes_servicio IN (:ids)").param("ids", ids).query(Orden.class).list()
			.stream().collect(Collectors.toMap(Orden::idOrden, o -> o));
	}

	private int crearPreventiva(int idMaquina, int prioridad, int tipo, LocalDate fecha, int usuario) {
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO ordenes_servicio (id_cliente, id_punto, id_modalidad, id_maquina, id_tipo_mantenimiento, fecha_visita,
			       estado, origen, prioridad, tiene_novedad, created_at)
			SELECT p.id_cliente, p.id_punto, p.id_modalidad, m.id_maquina, :tipo, :fecha, 3, :origen, :prioridad, 0, NOW()
			FROM maquina m JOIN punto p ON p.id_punto = m.id_punto WHERE m.id_maquina = :m""")
			.param("tipo", tipo).param("fecha", fecha).param("origen", prioridad == 3 ? "PREVENTIVO_VIP" : "PREVENTIVO")
			.param("prioridad", prioridad).param("m", idMaquina).update(llave);
		int id = llave.getKey().intValue();
		historial.registrar(id, "estado", null, "PENDIENTE", "Mantenimiento vencido agregado al armar rutas", usuario);
		return id;
	}

	/** Deja la orden programada para el técnico y la fecha; la modalidad la decide el sistema. */
	private void programar(int idOrden, int idTecnico, LocalDate fecha, int usuario, String motivo) {
		record Antes(Integer idTecnico, int estado, int idPunto) {
		}
		Antes a = jdbc.sql("SELECT id_tecnico, estado, id_punto FROM ordenes_servicio WHERE id_ordenes_servicio = :o")
			.param("o", idOrden).query(Antes.class).single();
		// Urbano o interurbano según el municipio del punto y la ciudad base del motorizado (p. ej. sale de Bogotá).
		int modalidad = this.modalidad.decidir(a.idPunto(), idTecnico);
		jdbc.sql("""
			UPDATE ordenes_servicio SET estado = 2, id_tecnico = :tec, fecha_visita = :fecha, id_modalidad = :mod,
			       etapa_actual = NULL, hora_inicio_trayecto = NULL, hora_llegada = NULL, hora_ingreso = NULL
			WHERE id_ordenes_servicio = :o""")
			.param("tec", idTecnico).param("fecha", fecha).param("mod", modalidad).param("o", idOrden).update();
		historial.registrar(idOrden, "id_tecnico", a.idTecnico(), idTecnico, motivo, usuario);
		historial.registrar(idOrden, "estado", a.estado(), 2, motivo, usuario);
	}

	private void resecuenciar(int idRuta, List<Integer> idsPendientes) {
		int hechas = jdbc.sql("SELECT COUNT(*) FROM ruta_parada WHERE id_ruta = :r AND estado <> 'PENDIENTE'")
			.param("r", idRuta).query(Integer.class).single();
		int sec = hechas + 1;
		for (int id : idsPendientes) {
			jdbc.sql("UPDATE ruta_parada SET secuencia = :s WHERE id_ruta = :r AND id_orden_servicio = :o")
				.param("s", sec++).param("r", idRuta).param("o", id).update();
		}
		recalcularTramos(idRuta);
	}

	/** Recalcula km, minutos y horas estimadas de cada tramo según el orden actual. */
	void recalcularTramos(int idRuta) {
		Ruta r = ruta(idRuta);
		List<double[]> puntos = new ArrayList<>();
		if (r.latitudPartida() != null && r.longitudPartida() != null) {
			puntos.add(new double[] { r.latitudPartida(), r.longitudPartida() });
		}
		r.paradas().stream().map(Parada::orden).filter(o -> o.latitud() != null)
			.forEach(o -> puntos.add(new double[] { o.latitud(), o.longitud() }));
		Optimizador opt = optimizador(puntos);
		LocalTime inicio = horaInicio(r.idTecnico());
		Double lat = r.latitudPartida(), lon = r.longitudPartida();
		int minuto = 0;
		double km = 0;
		for (Parada p : r.paradas()) {
			Orden o = p.orden();
			double kmTramo = 0;
			int minTramo = 0;
			if (o.latitud() != null && lat != null) {
				kmTramo = opt.kmVial(lat, lon, o.latitud(), o.longitud());
				minTramo = opt.minutosViaje(kmTramo);
			}
			minuto += minTramo;
			jdbc.sql("UPDATE ruta_parada SET km_tramo = :km, minutos_tramo = :min, hora_estimada = :hora WHERE id_parada = :p")
				.param("km", Math.round(kmTramo * 100) / 100.0).param("min", minTramo).param("hora", inicio.plusMinutes(minuto))
				.param("p", p.idParada()).update();
			minuto += parametros.minutosServicio(o.idTipoMantenimiento());
			km += kmTramo;
			if (o.latitud() != null) {
				lat = o.latitud();
				lon = o.longitud();
			}
		}
		jdbc.sql("UPDATE ruta_diaria SET km_estimados = :km, minutos_estimados = :min WHERE id_ruta = :r")
			.param("km", Math.round(km * 100) / 100.0).param("min", minuto).param("r", idRuta).update();
	}

	private LocalTime horaInicio(int idTecnico) {
		LocalTime propia = jdbc.sql("SELECT hora_inicio_jornada FROM tecnico WHERE id_tecnico = :t").param("t", idTecnico)
			.query(LocalTime.class).optional().orElse(null);
		return propia != null ? propia : parametros.hora(Parametros.HORA_INICIO, LocalTime.of(7, 0));
	}
}
