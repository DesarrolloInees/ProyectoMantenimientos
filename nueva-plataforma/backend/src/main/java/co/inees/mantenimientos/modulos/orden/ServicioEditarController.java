package co.inees.mantenimientos.modulos.orden;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.transaction.support.TransactionTemplate;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/** Port de servicioEditarControlador.php + servicioEditarModelo.php */
@RestController
@RequestMapping("/api/servicioEditar")
public class ServicioEditarController {

    private static final Logger log = LoggerFactory.getLogger(ServicioEditarController.class);
    private final Db db;
    private final Archivos archivos;
    private final TransactionTemplate tx;

    public ServicioEditarController(Db db, Archivos archivos, TransactionTemplate tx) {
        this.db = db;
        this.archivos = archivos;
        this.tx = tx;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> index(Req req) {
        int idOrden = req.intv("id", 0);
        if (idOrden == 0) return Map.of("error", "ID de orden no válido.");
        Map<String, Object> datos;
        try {
            datos = db.one("""
                    SELECT o.id_ordenes_servicio, o.soporte_remoto, o.numero_remision,
                            osc.numero_maquina, osc.serial_maquina, osc.serial_router,
                            osc.serial_ups, osc.pendientes, osc.administrador_punto,
                            osc.celular_encargado, osc.id_estado_inicial
                    FROM ordenes_servicio o
                    LEFT JOIN ordenes_servicio_complemento osc ON o.id_ordenes_servicio = osc.id_orden_servicio
                    WHERE o.id_ordenes_servicio = :id""", "id", idOrden);
        } catch (Exception e) {
            log.error("Error obteniendo datos edición: {}", e.getMessage());
            datos = null;
        }
        if (datos == null) return Map.of("error", "La orden no existe.");
        List<Map<String, Object>> evidencias;
        try {
            evidencias = db.list("""
                    SELECT id_evidencia, tipo_evidencia, ruta_archivo, fecha_subida
                    FROM evidencia_servicio
                    WHERE id_orden_servicio = :id
                    ORDER BY FIELD(tipo_evidencia, 'antes', 'remision', 'despues', 'firma'), fecha_subida ASC""", "id", idOrden);
        } catch (Exception e) {
            evidencias = List.of();
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Editar Complementos de Orden");
        r.put("datosOrden", datos);
        r.put("estados", db.list("SELECT id_estado, nombre_estado FROM estado_maquina"));
        r.put("evidencias", evidencias);
        return r;
    }

    @PostMapping(params = "accion=guardar")
    public Map<String, Object> guardar(Req req) {
        int idOrden = req.intv("id_ordenes_servicio", 0);
        String numeroRemision = req.str("numero_remision", "");
        boolean ok;
        try {
            tx.executeWithoutResult(s -> {
                db.update("UPDATE ordenes_servicio SET soporte_remoto = :soporte_remoto WHERE id_ordenes_servicio = :id_orden",
                        "soporte_remoto", Conv.nullIfEmpty(req.get("soporte_remoto")), "id_orden", idOrden);
                db.update("""
                        INSERT INTO ordenes_servicio_complemento (
                            id_orden_servicio, numero_maquina, serial_maquina, serial_router,
                            serial_ups, pendientes, administrador_punto, celular_encargado, id_estado_inicial, estado
                        ) VALUES (
                            :id_orden, :num_maq, :ser_maq, :ser_rout, :ser_ups, :pendientes, :admin, :celular, :est_ini, 1
                        ) ON DUPLICATE KEY UPDATE
                            numero_maquina = VALUES(numero_maquina),
                            serial_maquina = VALUES(serial_maquina),
                            serial_router = VALUES(serial_router),
                            serial_ups = VALUES(serial_ups),
                            pendientes = VALUES(pendientes),
                            administrador_punto = VALUES(administrador_punto),
                            celular_encargado = VALUES(celular_encargado),
                            id_estado_inicial = VALUES(id_estado_inicial)""",
                        "id_orden", idOrden,
                        "num_maq", Conv.nullIfEmpty(req.get("numero_maquina")),
                        "ser_maq", Conv.nullIfEmpty(req.get("serial_maquina")),
                        "ser_rout", Conv.nullIfEmpty(req.get("serial_router")),
                        "ser_ups", Conv.nullIfEmpty(req.get("serial_ups")),
                        "pendientes", Conv.nullIfEmpty(req.get("pendientes")),
                        "admin", Conv.nullIfEmpty(req.get("administrador_punto")),
                        "celular", Conv.nullIfEmpty(req.get("celular_encargado")),
                        "est_ini", Conv.nullIfEmpty(req.get("id_estado_inicial")));
            });
            ok = true;
        } catch (Exception e) {
            log.error("Error actualizando complemento: {}", e.getMessage());
            ok = false;
        }
        if (!ok) return Map.of("status", "error", "msg", "❌ Error al actualizar la información.");

        String carpeta = !numeroRemision.isEmpty() ? numeroRemision : "SIN_REMISION_" + idOrden;
        String numeroParaNombre = !numeroRemision.isEmpty() ? numeroRemision : "ORDEN-" + idOrden;
        Map<String, String> tipos = new LinkedHashMap<>();
        tipos.put("fotos_antes", "antes");
        tipos.put("foto_remision", "remision");
        tipos.put("fotos_despues", "despues");
        for (var t : tipos.entrySet()) {
            for (MultipartFile f : req.files(t.getKey())) {
                String nombre = "REM-" + numeroParaNombre + "_ADMIN_" + t.getValue() + "_" + Archivos.uniqid() + ".jpg";
                String rutaBD = "uploads/imagenes_servicios/" + carpeta + "/" + nombre;
                // __DIR__/../../uploads => app/uploads
                if (archivos.guardarImagenOptimizada(f, "app/" + rutaBD, 800, 70)) {
                    try {
                        db.update("INSERT INTO evidencia_servicio (id_orden_servicio, tipo_evidencia, ruta_archivo) VALUES (:id_orden, :tipo, :ruta)",
                                "id_orden", idOrden, "tipo", t.getValue(), "ruta", rutaBD);
                    } catch (Exception e) {
                        log.error("Error guardando foto desde admin: {}", e.getMessage());
                    }
                }
            }
        }
        return Map.of("status", "ok", "msg", "✅ Información y/o imágenes guardadas exitosamente.", "redirect", "serviciosPdf");
    }
}
