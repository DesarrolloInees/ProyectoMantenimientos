package co.inees.mantenimientos.modulos.tareas;

import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Telegram;
import co.inees.mantenimientos.modulos.supervisor.SupervisorController;
import tools.jackson.databind.json.JsonMapper;

/**
 * Reemplazo de los scripts que en PHP corría el Programador de tareas de Windows:
 *  - cron_alertas.php          → {@link #alertasLogisticas()} (correo de visitas frecuentes / desplazamientos)
 *  - cron_alertas_telegram.php → {@link #alertasTelegram()} (servicios en vivo que superan su tiempo meta)
 * Se activan con CRON_ALERTAS=true / CRON_TELEGRAM=true y la frecuencia se ajusta con
 * CRON_ALERTAS_EXPR / CRON_TELEGRAM_EXPR (expresiones cron de Spring, zona America/Bogota).
 */
@Component
public class TareasProgramadas {

    private static final Logger log = LoggerFactory.getLogger(TareasProgramadas.class);
    private static final JsonMapper JSON = JsonMapper.builder().build();

    private final Db db;
    private final Telegram telegram;
    private final SupervisorController supervisor;
    private final boolean alertasHabilitado;
    private final boolean telegramHabilitado;

    public TareasProgramadas(Db db, Telegram telegram, SupervisorController supervisor,
            @Value("${inees.cron-alertas-habilitado:false}") boolean alertasHabilitado,
            @Value("${inees.cron-telegram-habilitado:false}") boolean telegramHabilitado) {
        this.db = db;
        this.telegram = telegram;
        this.supervisor = supervisor;
        this.alertasHabilitado = alertasHabilitado;
        this.telegramHabilitado = telegramHabilitado;
    }

    // ================================================================ cron_alertas.php

    @Scheduled(cron = "${inees.cron-alertas-expresion:0 0 7 * * *}", zone = "America/Bogota")
    public void alertasLogisticas() {
        if (!alertasHabilitado) return;
        ejecutarAlertasLogisticas();
    }

    public Map<String, Object> ejecutarAlertasLogisticas() {
        log.info("Iniciando Cron de Alertas Logísticas");
        Map<String, Object> r = supervisor.procesar();
        if (Boolean.TRUE.equals(r.get("exito"))) {
            log.info("EXITO: {} | Visitas frecuentes: {} | Desplazamientos largos: {} | Correo enviado: {}", r.get("mensaje"),
                    Conv.list(r.get("datos_visitas")).size(), Conv.list(r.get("datos_desplazamientos")).size(),
                    Boolean.TRUE.equals(r.get("correo_enviado")) ? "SI" : "NO");
        } else {
            log.warn("ERROR: {}", r.getOrDefault("error", "Respuesta desconocida"));
        }
        log.info("Cron de Alertas Logísticas finalizado.");
        return r;
    }

    // ================================================================ cron_alertas_telegram.php

    @Scheduled(cron = "${inees.cron-telegram-expresion:0 */5 * * * *}", zone = "America/Bogota")
    public void alertasTelegram() {
        if (!telegramHabilitado) return;
        ejecutarAlertasTelegram();
    }

    private Map<String, Object> catalogoTiempos() {
        try {
            Map<String, Object> f = db.one("SELECT valor FROM parametros WHERE clave = 'tiempos_mantenimiento_minutos' LIMIT 1");
            if (f != null && !Conv.empty(f.get("valor"))) {
                Object j = JSON.readValue(Conv.str(f.get("valor")), Map.class);
                if (j instanceof Map<?, ?> m && !m.isEmpty()) {
                    @SuppressWarnings("unchecked")
                    Map<String, Object> r = (Map<String, Object>) m;
                    return r;
                }
            }
        } catch (Exception e) {
            log.warn("Error obtenerCatalogoTiempos: {}", e.getMessage());
        }
        return Map.of("1", 45, "2", 60, "3", 80, "4", 20);
    }

    public int ejecutarAlertasTelegram() {
        log.info("Executing cron (alertas Telegram)...");
        int enviadas = 0;
        try {
            Map<String, Object> catalogo = catalogoTiempos();
            List<Map<String, Object>> enProgreso = db.list("""
                    SELECT  ml.id_monitoreo,
                            ml.id_tipo_mantenimiento,
                            ml.hora_inicio,
                            TIMESTAMPDIFF(MINUTE, ml.hora_inicio, NOW()) AS minutos_transcurridos,
                            t.nombre_tecnico,
                            p.nombre_punto,
                            tm.nombre_completo AS tipo_mantenimiento
                    FROM monitoreo_motorizados_live ml
                    LEFT JOIN tecnico t ON ml.id_tecnico = t.id_tecnico
                    LEFT JOIN punto p ON ml.id_punto = p.id_punto
                    LEFT JOIN tipo_mantenimiento tm ON ml.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE ml.estado_actual = 'En Progreso'
                    AND (ml.alerta_telegram_enviada IS NULL OR ml.alerta_telegram_enviada = 0)""");
            for (Map<String, Object> s : enProgreso) {
                int id = Conv.toInt(s.get("id_monitoreo"));
                String idTipo = Conv.str(s.get("id_tipo_mantenimiento"));
                int min = Conv.toInt(s.get("minutos_transcurridos"));
                int meta = catalogo.containsKey(idTipo) ? Conv.toInt(catalogo.get(idTipo)) : 45;
                log.info("Evaluando ID: {} | Transcurrido: {}m | Meta: {}m", id, min, meta);
                if (min < meta) continue;

                String tecnico = Conv.empty(s.get("nombre_tecnico")) ? "Técnico Desconocido" : Conv.str(s.get("nombre_tecnico"));
                String punto = Conv.empty(s.get("nombre_punto")) ? "Punto Desconocido" : Conv.str(s.get("nombre_punto"));
                String tipo = Conv.empty(s.get("tipo_mantenimiento")) ? "Servicio General" : Conv.str(s.get("tipo_mantenimiento"));
                String msg = "⚠️ <b>ALERTA DE RETRASO EN VIVO</b>\n\n"
                        + "<b>Técnico:</b> " + Conv.h(tecnico) + "\n"
                        + "<b>Servicio:</b> " + Conv.h(tipo) + "\n"
                        + "<b>Punto:</b> " + Conv.h(punto) + "\n"
                        + "<b>Transcurrido:</b> " + min + " min (Meta: " + meta + " min)\n";
                log.info("Telegram Response: {}", telegram.enviar(msg));
                int filas = db.update("UPDATE monitoreo_motorizados_live SET alerta_telegram_enviada = 1 WHERE id_monitoreo = :id", "id", id);
                log.info("DB Update Filas Afectadas: {}", filas);
                enviadas++;
            }
            log.info("Finished. Total processed: {}", enviadas);
        } catch (Exception e) {
            log.error("Database Error: {}", e.getMessage());
        }
        return enviadas;
    }
}
