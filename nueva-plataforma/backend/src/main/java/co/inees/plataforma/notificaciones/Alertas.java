package co.inees.plataforma.notificaciones;

import static co.inees.plataforma.comun.Html.esc;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.comun.Filas;
import co.inees.plataforma.comun.Html;
import co.inees.plataforma.comun.Notificador;
import co.inees.plataforma.comun.Parametros;

/**
 * Tareas automáticas que en PHP eran cron_alertas.php y cron_alertas_telegram.php:
 * <ul>
 * <li>Cada 5 minutos: aviso por Telegram de servicios que superan su tiempo estimado (una vez por servicio).</li>
 * <li>Cada día a las 18:30: correo con puntos visitados muchas veces en 7 días y desplazamientos urbanos largos.</li>
 * </ul>
 * La pantalla «Alertas logísticas» (permiso PERSONAL) muestra lo mismo y permite enviarlo a mano.
 */
@Component
@RestController
@RequestMapping("/api/personal/alertas")
public class Alertas {

	public record Logistica(List<Map<String, Object>> visitasFrecuentes, List<Map<String, Object>> desplazamientosLargos,
		String html, boolean correoConfigurado, boolean telegramConfigurado) {
	}

	private static final Logger log = LoggerFactory.getLogger(Alertas.class);

	private final JdbcClient jdbc;
	private final Parametros parametros;
	private final Notificador notificador;

	public Alertas(JdbcClient jdbc, Parametros parametros, Notificador notificador) {
		this.jdbc = jdbc;
		this.parametros = parametros;
		this.notificador = notificador;
	}

	// ================================================================== retrasos en servicio (Telegram)
	@Scheduled(cron = "0 */5 6-21 * * *", zone = "America/Bogota")
	public void avisarRetrasos() {
		if (!notificador.telegramConfigurado() || parametros.entero(Parametros.ALERTAS_RETRASO, 1) != 1) {
			return;
		}
		var estimados = parametros.tiemposMantenimiento();
		record EnCurso(int idOrden, Integer idTipo, String tipo, String tecnico, String punto, LocalDateTime ingreso) {
		}
		List<EnCurso> enCurso = jdbc.sql("""
			SELECT o.id_ordenes_servicio AS id_orden, o.id_tipo_mantenimiento AS id_tipo, tm.nombre_completo AS tipo,
			       t.nombre_tecnico AS tecnico, p.nombre_punto AS punto, o.hora_ingreso AS ingreso
			FROM ordenes_servicio o JOIN tecnico t ON t.id_tecnico = o.id_tecnico JOIN punto p ON p.id_punto = o.id_punto
			LEFT JOIN tipo_mantenimiento tm ON tm.id_tipo_mantenimiento = o.id_tipo_mantenimiento
			WHERE o.estado = 6 AND o.hora_ingreso IS NOT NULL
			  AND NOT EXISTS (SELECT 1 FROM orden_servicio_historial h WHERE h.id_orden_servicio = o.id_ordenes_servicio
			                   AND h.campo = 'alerta_retraso')""").query(EnCurso.class).list();
		for (EnCurso s : enCurso) {
			int meta = s.idTipo() == null ? 45 : estimados.getOrDefault(s.idTipo(), 45);
			long transcurrido = java.time.Duration.between(s.ingreso(), LocalDateTime.now()).toMinutes();
			if (transcurrido < meta) {
				continue;
			}
			String msg = "⚠️ <b>ALERTA DE RETRASO EN VIVO</b>\n\n<b>Técnico:</b> " + esc(s.tecnico()) + "\n<b>Servicio:</b> "
				+ esc(s.tipo()) + "\n<b>Punto:</b> " + esc(s.punto()) + "\n<b>Transcurrido:</b> " + transcurrido + " min (meta: " + meta + " min)";
			if (notificador.telegram(msg)) {
				jdbc.sql("""
					INSERT INTO orden_servicio_historial (id_orden_servicio, campo, valor_nuevo, motivo)
					VALUES (:o, 'alerta_retraso', :v, 'Aviso por Telegram')""")
					.param("o", s.idOrden()).param("v", transcurrido + " min").update();
			}
		}
	}

	// ================================================================== alertas logísticas (correo diario)
	@GetMapping
	public Logistica logistica() {
		int visitas = parametros.entero(Parametros.ALERTA_VISITAS, 3);
		int desplazamiento = parametros.entero(Parametros.ALERTA_DESPLAZAMIENTO, 40);
		var frecuentes = Filas.normalizar(jdbc.sql("""
			SELECT p.nombre_punto AS punto, c.nombre_cliente AS cliente, COUNT(*) AS visitas,
			       GROUP_CONCAT(o.fecha_visita ORDER BY o.fecha_visita SEPARATOR ' | ') AS fechas
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto JOIN cliente c ON c.id_cliente = p.id_cliente
			WHERE o.fecha_visita >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND o.estado = 1
			GROUP BY o.id_punto, p.nombre_punto, c.nombre_cliente HAVING visitas >= :n ORDER BY visitas DESC""")
			.param("n", visitas).query().listOfRows());
		var largos = Filas.normalizar(jdbc.sql("""
			SELECT tecnico, punto, salida_anterior, llegada, TIMESTAMPDIFF(MINUTE, salida_anterior, llegada) AS minutos FROM (
			  SELECT t.nombre_tecnico AS tecnico, p.nombre_punto AS punto, TIMESTAMP(o.fecha_visita, o.hora_entrada) AS llegada,
			         TIMESTAMP(o.fecha_visita, LAG(o.hora_salida) OVER (PARTITION BY o.id_tecnico ORDER BY o.hora_entrada)) AS salida_anterior
			  FROM ordenes_servicio o JOIN tecnico t ON t.id_tecnico = o.id_tecnico JOIN punto p ON p.id_punto = o.id_punto
			  WHERE o.fecha_visita = CURDATE() AND o.id_modalidad = 1 AND o.estado = 1 AND o.hora_entrada IS NOT NULL) x
			WHERE salida_anterior IS NOT NULL AND TIMESTAMPDIFF(MINUTE, salida_anterior, llegada) > :m
			ORDER BY minutos DESC""").param("m", desplazamiento).query().listOfRows());
		StringBuilder h = new StringBuilder();
		if (!frecuentes.isEmpty() || !largos.isEmpty()) {
			h.append("<h2 style='color:#1F4E78'>Reporte de alertas logísticas diarias</h2>");
			if (!frecuentes.isEmpty()) {
				h.append("<h3 style='color:#C55A11'>1. Puntos visitados ").append(visitas).append(" o más veces (últimos 7 días)</h3>")
					.append(tablaCorreo(List.of("Punto", "Cliente", "Visitas", "Fechas"), frecuentes, List.of("punto", "cliente", "visitas", "fechas")));
			}
			if (!largos.isEmpty()) {
				h.append("<h3 style='color:#C55A11'>2. Desplazamientos urbanos de más de ").append(desplazamiento).append(" min hoy</h3>")
					.append(tablaCorreo(List.of("Técnico", "Punto destino", "Salida anterior", "Llegada", "Minutos"), largos,
						List.of("tecnico", "punto", "salida_anterior", "llegada", "minutos")));
			}
		}
		return new Logistica(frecuentes, largos, h.toString(), notificador.correoConfigurado(), notificador.telegramConfigurado());
	}

	@PostMapping("/enviar")
	public Logistica enviar() {
		Logistica l = logistica();
		if (!l.html().isEmpty()) {
			notificador.correo(parametros.lista(Parametros.CORREOS_LOGISTICA, "operaciones@inees.co"),
				"[Alertas operativas] Reporte logístico - " + Html.fecha(java.time.LocalDate.now()), l.html());
		}
		return l;
	}

	@Scheduled(cron = "0 30 18 * * MON-SAT", zone = "America/Bogota")
	public void correoDiario() {
		if (!notificador.correoConfigurado()) {
			return;
		}
		try {
			enviar();
		}
		catch (RuntimeException e) {
			log.warn("No se envió el correo de alertas logísticas: {}", e.getMessage());
		}
	}

	private static String tablaCorreo(List<String> cab, List<Map<String, Object>> filas, List<String> claves) {
		StringBuilder b = new StringBuilder("<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;width:100%'><thead style='background:#f2f2f2'><tr>");
		cab.forEach(c -> b.append("<th>").append(esc(c)).append("</th>"));
		b.append("</tr></thead><tbody>");
		for (var f : filas) {
			b.append("<tr>");
			claves.forEach(k -> b.append("<td>").append(esc(f.get(k))).append("</td>"));
			b.append("</tr>");
		}
		return b.append("</tbody></table><br/>").toString();
	}
}
