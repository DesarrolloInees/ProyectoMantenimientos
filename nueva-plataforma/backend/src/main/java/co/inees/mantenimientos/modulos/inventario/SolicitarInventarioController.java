package co.inees.mantenimientos.modulos.inventario;

import java.nio.file.Files;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Correo;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;
import co.inees.mantenimientos.core.Sesion;

/** Port de solicitarInventarioControlador.php + solicitarInventarioModelo.php */
@RestController
public class SolicitarInventarioController {

    private static final Logger log = LoggerFactory.getLogger(SolicitarInventarioController.class);
    static final List<String> DESTINATARIOS = List.of("almacen@inees.co", "administrativo@inees.co", "desarrollo@inees.co",
            "desarrollo2@inees.co", "operaciones@inees.co", "auxadministrativo@inees.co");
    private final Db db;
    private final Correo correo;
    private final Archivos archivos;

    public SolicitarInventarioController(Db db, Correo correo, Archivos archivos) {
        this.db = db;
        this.correo = correo;
        this.archivos = archivos;
    }

    private Map<String, Object> tecnico(int usuarioId) {
        try {
            return db.one("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE usuario_id = :uid LIMIT 1", "uid", usuarioId);
        } catch (Exception e) {
            return null;
        }
    }

    private List<Map<String, Object>> repuestos() {
        try {
            return db.list("SELECT id_repuesto, nombre_repuesto, codigo_referencia FROM repuesto ORDER BY nombre_repuesto ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    private String nombreSolicitante(Sesion s) {
        String nombre = !Conv.empty(s.get("nombre")) ? Conv.str(s.get("nombre")) : !Conv.empty(s.get("usuario")) ? Conv.str(s.get("usuario")) : "Usuario del Sistema";
        if (s.usuarioId() > 0) {
            var t = tecnico(s.usuarioId());
            if (t != null && !Conv.empty(t.get("nombre_tecnico"))) nombre = Conv.str(t.get("nombre_tecnico"));
        }
        return nombre;
    }

    private Map<String, Object> vista(Req req, List<String> errores, String exito) {
        Sesion s = req.sesion();
        boolean esTecnico = s.nivel() == 3;
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("errores", errores);
        r.put("mensajeExito", exito);
        r.put("esTecnico", esTecnico);
        if (!esTecnico && !"crear".equals(req.str("vista", ""))) {
            r.put("vista", "tablero");
            r.put("titulo", "Tablero de Solicitudes de Inventario");
            r.put("solicitudes", solicitudes());
        } else {
            r.put("vista", "crear");
            r.put("titulo", "Solicitar Inventario");
            r.put("listaRepuestos", repuestos());
            r.put("nombreUsuarioLogueado", nombreSolicitante(s));
            r.put("destinatarios", DESTINATARIOS);
        }
        return r;
    }

    private List<Map<String, Object>> solicitudes() {
        List<Map<String, Object>> sols = new ArrayList<>();
        try {
            for (var s : db.list("""
                    SELECT s.*,
                    (SELECT COUNT(*) FROM solicitud_inventario_detalle d WHERE d.id_solicitud = s.id_solicitud) as total_items
                    FROM solicitudes_inventario s  ORDER BY s.fecha_solicitud DESC""")) {
                var m = new LinkedHashMap<>(s);
                m.put("detalles", db.list("""
                        SELECT d.*, r.nombre_repuesto, r.codigo_referencia
                        FROM solicitud_inventario_detalle d
                        JOIN repuesto r ON d.id_repuesto = r.id_repuesto
                        WHERE d.id_solicitud = :id_sol""", "id_sol", s.get("id_solicitud")));
                sols.add(m);
            }
        } catch (Exception e) {
            log.error("Error al obtener solicitudes: {}", e.getMessage());
        }
        return sols;
    }

    @GetMapping(value = "/api/solicitarInventario", params = "accion=index")
    public Map<String, Object> index(Req req) {
        return vista(req, List.of(), "");
    }

    @PostMapping(value = "/api/solicitarInventario", params = "accion=cambiarEstado")
    public Map<String, Object> cambiarEstado(Req req) {
        List<String> errores = new ArrayList<>();
        String exito = "";
        int id = req.intv("id_solicitud", 0);
        String estado = req.str("nuevo_estado", "").trim();
        if (id > 0 && !estado.isEmpty()) {
            boolean ok = false;
            if (List.of("Pendiente", "En gestión", "Finalizado").contains(estado)) {
                try {
                    db.update("UPDATE solicitudes_inventario SET estado = :estado WHERE id_solicitud = :id", "estado", estado, "id", id);
                    ok = true;
                } catch (Exception e) {
                    log.error("Error al actualizar estado de solicitud: {}", e.getMessage());
                }
            }
            if (ok) exito = "La solicitud #" + id + " ha cambiado su estado a '" + estado + "' con éxito.";
            else errores.add("No se pudo actualizar el estado de la solicitud #" + id + ".");
        } else {
            errores.add("Datos de cambio de estado no válidos.");
        }
        return vista(req, errores, exito);
    }

    @PostMapping(value = "/api/solicitarInventario", params = "accion=crearSolicitud")
    public Map<String, Object> crearSolicitud(Req req) {
        List<String> errores = new ArrayList<>();
        String exito = "";
        List<Object> reps = Req.asList(req.get("repuestos")), cants = Req.asList(req.get("cantidades"));
        String obs = req.str("observaciones", "").trim();
        if (reps.isEmpty()) errores.add("Debes seleccionar al menos un repuesto para realizar la solicitud.");
        if (errores.isEmpty()) {
            Sesion s = req.sesion();
            Integer uid = s.usuarioId() > 0 ? s.usuarioId() : null;
            String solicitante = nombreSolicitante(s);
            Long id = null;
            try {
                id = db.tx(() -> {
                    long nuevo = db.insert("""
                            INSERT INTO solicitudes_inventario (id_usuario, solicitante_nombre, estado, observaciones)
                            VALUES (:uid, :nombre, 'Pendiente', :obs)""", "uid", uid, "nombre", solicitante, "obs", obs);
                    for (int i = 0; i < reps.size(); i++) {
                        int cant = i < cants.size() && !Conv.empty(cants.get(i)) ? Conv.toInt(cants.get(i)) : 1;
                        if (!Conv.empty(reps.get(i)) && cant > 0) {
                            db.update("INSERT INTO solicitud_inventario_detalle (id_solicitud, id_repuesto, cantidad) VALUES (:id_sol, :id_rep, :cant)",
                                    "id_sol", nuevo, "id_rep", reps.get(i), "cant", cant);
                        }
                    }
                    return nuevo;
                });
            } catch (Exception e) {
                log.error("Error al guardar solicitud de inventario: {}", e.getMessage());
            }
            if (id != null) {
                boolean enviado = notificar(id, solicitante, reps, cants, obs);
                exito = enviado
                        ? "¡Éxito! Tu solicitud de repuestos (#" + id + ") ha sido registrada en el tablero y enviada a Logística por correo."
                        : "Tu solicitud de repuestos (#" + id + ") fue registrada en el tablero, pero hubo un problema al enviar el correo de notificación. Contacta al administrador.";
            } else {
                errores.add("Error al guardar la solicitud en la base de datos.");
            }
        }
        return vista(req, errores, exito);
    }

    private boolean notificar(long id, String solicitante, List<Object> reps, List<Object> cants, String obs) {
        try {
            Map<String, String> mapa = new LinkedHashMap<>();
            for (var r : repuestos()) {
                String ref = Conv.empty(r.get("codigo_referencia")) ? "" : " (Ref: " + r.get("codigo_referencia") + ")";
                mapa.put(Conv.str(r.get("id_repuesto")), r.get("nombre_repuesto") + ref);
            }
            StringBuilder filas = new StringBuilder();
            for (int i = 0; i < reps.size(); i++) {
                int cant = i < cants.size() ? Conv.toInt(cants.get(i)) : 0;
                String idr = Conv.str(reps.get(i));
                if (!idr.isEmpty() && cant > 0 && mapa.containsKey(idr)) {
                    filas.append("<tr><td style='padding: 10px; border-bottom: 1px solid #e2e8f0; color: #334155;'>").append(Conv.h(mapa.get(idr)))
                            .append("</td><td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center; font-weight: bold; color: #0284c7;'>").append(cant).append("</td></tr>");
                }
            }
            String fecha = LocalDateTime.now().format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm"));
            String html = "<div style='font-family: sans-serif; max-width: 650px; margin: 0 auto; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; background-color: #ffffff;'>"
                    + "<div style='background-color: #4f46e5; padding: 20px; text-align: center; color: #ffffff;'><h2 style='margin: 0; font-size: 22px;'>Nueva Solicitud de Inventario #" + id + "</h2>"
                    + "<p style='margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;'>Sistema I-Nexis Mantenimientos</p></div>"
                    + "<div style='padding: 25px; color: #1e293b;'><p style='font-size: 15px; margin-top: 0;'>Se ha registrado una nueva solicitud de repuestos con el siguiente detalle:</p>"
                    + "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: #f8fafc; border-radius: 8px;'>"
                    + "<tr><td style='padding: 10px; font-weight: bold; width: 35%; color: #475569;'>N° Solicitud:</td><td style='padding: 10px; color: #0f172a; font-weight: bold;'>#" + id + "</td></tr>"
                    + "<tr><td style='padding: 10px; font-weight: bold; color: #475569;'>Solicitado Por:</td><td style='padding: 10px; color: #0f172a; font-weight: bold;'>" + Conv.h(solicitante) + "</td></tr>"
                    + "<tr><td style='padding: 10px; font-weight: bold; color: #475569;'>Fecha y Hora:</td><td style='padding: 10px; color: #0f172a;'>" + fecha + "</td></tr>"
                    + "<tr><td style='padding: 10px; font-weight: bold; color: #475569;'>Observaciones:</td><td style='padding: 10px; color: #0f172a;'>" + (obs.isEmpty() ? "Ninguna" : Conv.h(obs)) + "</td></tr></table>"
                    + "<h3 style='font-size: 16px; color: #334155; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px;'>Detalle de Repuestos Solicitados</h3>"
                    + "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;'><thead><tr style='background-color: #f1f5f9; text-align: left; color: #475569;'>"
                    + "<th style='padding: 10px; border-bottom: 2px solid #cbd5e1;'>Repuesto / Material</th><th style='padding: 10px; border-bottom: 2px solid #cbd5e1; text-align: center;'>Cantidad Solicitada</th></tr></thead>"
                    + "<tbody>" + filas + "</tbody></table>"
                    + "<div style='background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px; margin-top: 20px; border-radius: 4px; font-size: 12px; color: #1d4ed8;'>Este es un mensaje automático generado por el sistema I-Nexis. Por favor no responda directamente a este correo.</div></div></div>";
            correo.enviar(DESTINATARIOS, null, "Nueva Solicitud de Inventario #" + id + " - " + solicitante, html, null, null);
            return true;
        } catch (Exception e) {
            log.error("Error enviando notificación de solicitud de inventario: {}", e.getMessage());
            try {
                var f = archivos.resolver("app/temp/error_smtp_inventario.log");
                Files.createDirectories(f.getParent());
                Files.writeString(f, "[" + LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")) + "] " + e.getMessage() + "\n",
                        java.nio.file.StandardOpenOption.CREATE, java.nio.file.StandardOpenOption.APPEND);
            } catch (Exception ignored) {
            }
            return false;
        }
    }
}
