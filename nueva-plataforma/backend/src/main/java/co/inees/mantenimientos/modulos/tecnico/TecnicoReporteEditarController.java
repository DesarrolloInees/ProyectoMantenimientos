package co.inees.mantenimientos.modulos.tecnico;

import java.util.Base64;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Req;

/** Port de tecnicoReporteEditarControlador.php */
@RestController
@RequestMapping("/api/tecnicoReporteEditar")
public class TecnicoReporteEditarController {

    private static final Logger log = LoggerFactory.getLogger(TecnicoReporteEditarController.class);
    private static final Pattern NOV_INI = Pattern.compile("(?is)^\\[ESTADO INICIAL:\\s*(.*?)\\]\\s*");
    private final TecnicoReporteModelo m;
    private final Archivos archivos;

    public TecnicoReporteEditarController(TecnicoReporteModelo m, Archivos archivos) {
        this.m = m;
        this.archivos = archivos;
    }

    private static int usuario(Req req) {
        return req.sesion().has("usuario_id") ? req.sesion().usuarioId() : 0;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> index(Req req) {
        int uid = usuario(req);
        int idOrden = req.intv("orden", 0);
        if (idOrden == 0 || uid == 0) return Map.of("error", "Orden no válida.");
        int idTec = m.obtenerIdTecnicoPorUsuario(uid);
        Map<String, Object> orden = m.obtenerDetalleOrdenParaEdicion(idOrden);
        if (orden == null) return Map.of("error", "La orden no existe en el sistema.");
        if (idTec == 0 || Conv.toInt(orden.get("id_tecnico")) != idTec) return Map.of("error", "La orden no pertenece a tu asignación.");
        Map<String, Object> g = m.obtenerReporteGuardado(idOrden);
        if (g == null) return Map.of("error", "Esta orden aún no tiene un reporte guardado para editar.");

        // Novedad del estado inicial: columna propia o extraída de las actividades
        String novPre = g.get("novedad_estado_inicial") == null ? null : Conv.str(g.get("novedad_estado_inicial"));
        if (novPre == null || novPre.isEmpty()) {
            Matcher mt = NOV_INI.matcher(Conv.str(g.get("actividades_realizadas")));
            if (mt.find()) novPre = mt.group(1).trim();
        }

        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Editar Reporte de Servicio");
        r.put("orden", orden);
        r.put("reporteGuardado", g);
        r.put("novedadPrevia", novPre == null ? "" : novPre);
        r.put("repuestosGuardados", m.obtenerRepuestosGuardados(idOrden));
        r.put("remisiones", m.obtenerRemisionesTecnico(idTec > 0 ? idTec : Conv.toInt(orden.get("id_tecnico"))));
        r.put("estados", m.obtenerEstadosMaquina());
        r.put("calificaciones", m.obtenerCalificaciones());
        r.put("modalidades", m.obtenerModalidades());
        r.put("tiposManto", m.obtenerTiposMantenimientoTecnico());
        r.put("inventario", m.obtenerTodosLosRepuestos());
        return r;
    }

    @PostMapping(params = "accion=actualizar")
    public Map<String, Object> actualizar(Req req) {
        int uid = usuario(req);
        int idTec = m.obtenerIdTecnicoPorUsuario(uid);
        int idOrden = req.intv("id_ordenes_servicio", 0);
        if (idOrden == 0 || uid == 0 || idTec == 0) return Map.of("success", false, "msj", "Sesion expirada o ID de orden no valido.");
        Map<String, Object> real = m.obtenerDetalleOrdenParaEdicion(idOrden);
        if (real == null || Conv.toInt(real.get("id_tecnico")) != idTec) return Map.of("success", false, "msj", "La orden no existe o no te pertenece.");

        String novIni = req.str("novedad_estado_inicial", "").trim();
        if (novIni.codePointCount(0, novIni.length()) > 500) novIni = novIni.substring(0, novIni.offsetByCodePoints(0, 500));
        String baseAct = req.str("actividades_realizadas", "").trim().replaceAll("(?is)^\\[ESTADO INICIAL:.*?\\]\\s*", "");
        String actividades = !novIni.isEmpty() ? "[ESTADO INICIAL: " + novIni + "] " + baseAct : baseAct;

        String novSel = req.str("novedades_seleccionadas", "").trim();
        boolean gestionada = "1".equals(req.str("novedades_gestionada", ""));
        if (gestionada || !novSel.isEmpty()) m.guardarNovedadesTecnico(idOrden, m.filtrarNovedadesValidas(TecnicoReporteModelo.normalizarIds(novSel)));

        Map<String, Object> d = new LinkedHashMap<>();
        d.put("id_ordenes_servicio", idOrden);
        d.put("id_tecnico", idTec);
        d.put("numero_remision", req.str("numero_remision", ""));
        d.put("hora_entrada", req.str("hora_entrada", ""));
        d.put("hora_salida", req.str("hora_salida", ""));
        d.put("tiempo_servicio", req.str("tiempo_servicio", ""));
        d.put("actividades_realizadas", actividades);
        d.put("id_estado_maquina", req.has("id_estado_maquina") ? req.get("id_estado_maquina") : null);
        d.put("id_calificacion", Conv.nullIfEmpty(req.get("id_calificacion")));
        d.put("id_modalidad", !Conv.empty(req.get("id_modalidad")) ? req.intv("id_modalidad") : null);
        d.put("id_tipo_mantenimiento", req.has("id_tipo_mantenimiento") ? req.get("id_tipo_mantenimiento") : null);
        d.put("soporte_remoto", Conv.nullIfEmpty(req.get("soporte_remoto")));
        d.put("tiene_novedad", m.resolverTieneNovedad(idOrden, req.get("tiene_novedad"), gestionada));
        d.put("detalle_novedad", Conv.nullIfEmpty(req.get("detalle_novedad")));
        d.put("repuestos_tecnico", Conv.nullIfEmpty(req.get("json_repuestos")));

        Map<String, Object> c = new LinkedHashMap<>();
        c.put("id_orden_servicio", idOrden);
        for (String k : List.of("numero_maquina", "serial_maquina", "serial_router", "serial_ups", "pendientes", "administrador_punto", "celular_encargado", "id_estado_inicial")) {
            c.put(k, req.has(k) ? req.get(k) : null);
        }
        c.put("novedad_estado_inicial", novIni.isEmpty() ? null : novIni);

        try {
            m.actualizarDatosComplementarios(c);
            m.actualizarFechaModificacion(idOrden);
            m.actualizarReporteTecnico(d);
        } catch (Exception e) {
            log.error("Error actualizando reporte técnico: {}", e.getMessage());
            return Map.of("success", false, "msj", "Error al actualizar la base de datos.");
        }

        Map<String, Object> edit = m.obtenerDetalleOrdenParaEdicion(idOrden);
        if (edit != null && !Conv.empty(edit.get("id_punto"))) m.actualizarTipoMantenimientoPunto(edit.get("id_punto"), d.get("id_tipo_mantenimiento"));

        String firma = req.str("firma_base64", "");
        if (!firma.isEmpty()) {
            String rem = Conv.str(d.get("numero_remision"));
            String carpeta = !rem.isEmpty() ? rem : "SIN_REMISION_" + idOrden;
            String[] partes = firma.split(",");
            if (partes.length == 2) {
                try {
                    byte[] bytes = Base64.getMimeDecoder().decode(partes[1]);
                    String nombre = "REM-" + carpeta + "_firma_EDIT_" + Archivos.uniqid() + ".png";
                    String rutaBD = "uploads/imagenes_servicios/" + carpeta + "/" + nombre;
                    archivos.guardar(bytes, "app/" + rutaBD);
                    m.eliminarEvidenciaPorTipo(idOrden, "firma");
                    m.guardarEvidenciaFoto(idOrden, "firma", rutaBD);
                } catch (Exception e) {
                    log.error("Error guardando firma editada: {}", e.getMessage());
                }
            }
        }

        String json = req.str("json_repuestos", "");
        if (!json.isEmpty()) {
            m.limpiarRepuestosOrden(idOrden);
            for (Object o : Conv.list(Req.jsonDecode(json))) {
                Map<String, Object> r = Conv.map(o);
                m.guardarRepuestoOrden(idOrden, Conv.toInt(r.get("id")), Conv.toInt(r.get("cantidad")), r.get("origen"));
            }
        }
        return Map.of("success", true, "msj", "Reporte editado y actualizado correctamente.");
    }
}
