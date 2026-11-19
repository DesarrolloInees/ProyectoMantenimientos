package co.inees.mantenimientos.modulos.tecnico;

import java.util.ArrayList;
import java.util.Base64;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.mantenimientos.core.Archivos;
import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/** Port de tecnicoReporteControlador.php (el técnico atiende y finaliza el servicio desde el celular) */
@RestController
@RequestMapping("/api/tecnicoReporte")
public class TecnicoReporteController {

    private static final Logger log = LoggerFactory.getLogger(TecnicoReporteController.class);
    private final TecnicoReporteModelo m;
    private final Archivos archivos;
    private final Db db;

    public TecnicoReporteController(TecnicoReporteModelo m, Archivos archivos, Db db) {
        this.m = m;
        this.archivos = archivos;
        this.db = db;
    }

    private static int usuario(Req req) {
        return req.sesion().has("usuario_id") ? req.sesion().usuarioId() : 0;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> index(Req req) {
        int uid = usuario(req);
        int idOrden = req.intv("orden", 0);
        if (idOrden == 0 || uid == 0) return Map.of("error", "Orden no válida o sesión expirada.");
        int idTec = m.obtenerIdTecnicoPorUsuario(uid);
        if (idTec == 0) return Map.of("error", "Tu usuario no está vinculado a un perfil de técnico.");
        Map<String, Object> orden = m.obtenerDetalleOrden(idOrden, idTec);
        if (orden == null) return Map.of("error", "La orden no existe o ya fue ejecutada.", "redirect", "tecnicoProgramacion");

        String horaEntradaInicial = "";
        String inicio;
        String he = Conv.str(orden.get("hora_entrada"));
        if (!he.isEmpty()) {
            horaEntradaInicial = he.length() >= 5 ? he.substring(0, 5) : he;
            String conSeg = he.length() == 5 ? he + ":00" : he;
            String fv = Conv.str(orden.get("fecha_visita"));
            inicio = (fv.isEmpty() ? F.hoy() : fv.substring(0, 10)) + " " + conSeg;
        } else {
            inicio = F.ahora();
        }
        String clave = "fecha_apertura_orden_" + idOrden;
        if (Conv.empty(req.sesion().get(clave))) req.sesion().set(clave, inicio);

        Map<String, Object> r = new LinkedHashMap<>();
        r.put("titulo", "Atender Servicio");
        r.put("orden", orden);
        r.put("remisiones", m.obtenerRemisionesTecnico(idTec));
        r.put("estados", m.obtenerEstadosMaquina());
        r.put("calificaciones", m.obtenerCalificaciones());
        r.put("modalidades", m.obtenerModalidades());
        r.put("tiposManto", m.obtenerTiposMantenimientoTecnico());
        r.put("inventario", m.obtenerInventarioTecnico(idTec));
        r.put("todosLosRepuestos", m.obtenerTodosLosRepuestos());
        r.put("horaEntradaInicial", horaEntradaInicial);
        r.put("fechaApertura", req.sesion().get(clave));
        return r;
    }

    @PostMapping(params = "accion=guardar")
    public Map<String, Object> guardar(Req req) {
        int uid = usuario(req);
        int idTec = m.obtenerIdTecnicoPorUsuario(uid);
        int idOrden = req.intv("id_ordenes_servicio", 0);
        if (idOrden == 0 || uid == 0 || idTec == 0) return Map.of("success", false, "msj", "Sesión expirada o ID de orden no válido.");
        Map<String, Object> real = m.obtenerDetalleOrden(idOrden, idTec);
        if (real == null) return Map.of("success", false, "msj", "La orden no existe, no te pertenece o ya fue gestionada.");

        String apertura = req.str("fecha_apertura", null);
        String ultima = m.obtenerUltimaModificacion(idOrden);
        if (apertura != null && ultima != null && TecnicoReporteModelo.posterior(ultima, apertura)) {
            return Map.of("success", false, "msj", "El servicio fue modificado por un coordinador mientras estabas en campo.");
        }
        m.actualizarFechaModificacion(idOrden);

        String novIni = req.str("novedad_estado_inicial", "").trim();
        if (novIni.codePointCount(0, novIni.length()) > 500) novIni = novIni.substring(0, novIni.offsetByCodePoints(0, 500));
        String baseAct = req.str("actividades_realizadas", "").trim().replaceAll("(?is)^\\[ESTADO INICIAL:.*?\\]\\s*", "");
        String actividades = !novIni.isEmpty() ? "[ESTADO INICIAL: " + novIni + "] " + baseAct : baseAct;

        String novSel = req.str("novedades_seleccionadas", "").trim();
        boolean gestionada = "1".equals(req.str("novedades_gestionada", ""));
        if (gestionada || !novSel.isEmpty()) {
            m.guardarNovedadesTecnico(idOrden, m.filtrarNovedadesValidas(TecnicoReporteModelo.normalizarIds(novSel)));
        }

        Map<String, Object> d = new LinkedHashMap<>();
        d.put("id_ordenes_servicio", idOrden);
        d.put("id_tecnico", idTec);
        d.put("id_cliente", real.get("id_cliente"));
        d.put("id_punto", real.get("id_punto"));
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
        for (String k : List.of("numero_maquina", "serial_maquina", "serial_router", "serial_ups", "pendientes", "administrador_punto", "celular_encargado",
                "id_estado_inicial", "latitud_fin", "longitud_fin")) {
            c.put(k, req.has(k) ? req.get(k) : null);
        }
        c.put("novedad_estado_inicial", novIni.isEmpty() ? null : novIni);
        m.guardarDatosComplementarios(c);

        if (!m.guardarReporteTecnico(d)) return Map.of("success", false, "msj", "Error al registrar el reporte en la Base de Datos.");

        String fv = Conv.str(real.get("fecha_visita"));
        m.actualizarUltimaVisitaPunto(real.get("id_punto"), fv.isEmpty() ? F.hoy() : fv, d.get("id_tipo_mantenimiento"));
        String rem = Conv.str(d.get("numero_remision"));
        if (!rem.isEmpty()) m.marcarRemisionComoUsada(rem, idOrden, idTec);

        String carpeta = !rem.isEmpty() ? rem : "SIN_REMISION_" + idOrden;
        String firma = req.str("firma_base64", "");
        if (!firma.isEmpty()) {
            String[] partes = firma.split(",");
            if (partes.length == 2) {
                try {
                    byte[] bytes = Base64.getMimeDecoder().decode(partes[1]);
                    String num = !rem.isEmpty() ? rem : "ORDEN-" + idOrden;
                    String nombre = "REM-" + num + "_firma_" + Archivos.uniqid() + ".png";
                    String rutaBD = "uploads/imagenes_servicios/" + carpeta + "/" + nombre;
                    archivos.guardar(bytes, "app/" + rutaBD);
                    m.guardarEvidenciaFoto(idOrden, "firma", rutaBD);
                } catch (Exception e) {
                    log.error("Error guardando firma: {}", e.getMessage());
                }
            }
        }
        m.guardarRepuestosJson(idOrden, req.str("json_repuestos", ""));
        return Map.of("success", true, "msj", "Reporte finalizado exitosamente.");
    }

    @PostMapping(params = "accion=ajaxObtenerEvidencias")
    public Map<String, Object> ajaxObtenerEvidencias(Req req) {
        List<Map<String, Object>> evs = new ArrayList<>();
        for (var e : m.obtenerEvidenciasPorOrden(req.intv("id_orden", 0))) {
            Map<String, Object> x = new LinkedHashMap<>(e);
            x.put("ruta_archivo", "/app/" + Conv.str(e.get("ruta_archivo")));
            evs.add(x);
        }
        return Map.of("success", true, "data", evs);
    }

    @PostMapping(params = "accion=ajaxVerificarModificacion")
    public Map<String, Object> ajaxVerificarModificacion(Req req) {
        int id = req.intv("id_orden", 0);
        String apertura = req.str("fecha_apertura", null);
        if (id != 0 && !Conv.empty(apertura)) {
            String ultima = m.obtenerUltimaModificacion(id);
            return Map.of("fue_modificado", ultima != null && TecnicoReporteModelo.posterior(ultima, apertura));
        }
        return Map.of("fue_modificado", false);
    }

    @PostMapping(params = "accion=ajaxSubirFotoUnica")
    public Map<String, Object> ajaxSubirFotoUnica(Req req) {
        return subirFoto(req, m, archivos);
    }

    /** Compartido con tecnicoReporteEditar */
    static Map<String, Object> subirFoto(Req req, TecnicoReporteModelo m, Archivos archivos) {
        int idOrden = req.intv("id_orden", 0);
        String tipo = req.str("tipo_evidencia", "");
        String rem = !Conv.empty(req.get("numero_remision")) ? req.str("numero_remision") : "SIN_REMISION_" + idOrden;
        MultipartFile foto = req.file("foto");
        if (foto == null) return Map.of("success", false, "msj", "No se recibió la foto. Verifica los límites de php.ini (post_max_size).");
        if (idOrden > 0) {
            String nombre = "REM-" + rem + "_TEC_" + tipo + "_" + Archivos.uniqid() + ".jpg";
            String rutaBD = "uploads/imagenes_servicios/" + rem + "/" + nombre;
            if (archivos.guardarImagenOptimizada(foto, "app/" + rutaBD, 800, 70, true)) {
                if (m.guardarEvidenciaFoto(idOrden, tipo, rutaBD)) return Map.of("success", true, "msj", "Foto subida correctamente", "ruta", rutaBD);
                return Map.of("success", false, "msj", "La foto se guardó en el servidor, pero falló el insert en la Base de Datos.");
            }
            return Map.of("success", false, "msj", "Fallo al comprimir o mover la imagen a la carpeta final.");
        }
        return Map.of("success", false, "msj", "ID de orden no válido.");
    }

    @PostMapping(params = "accion=ajaxEliminarFotoUnica")
    public Map<String, Object> ajaxEliminarFotoUnica(Req req) {
        return eliminarFoto(req, m, archivos);
    }

    static Map<String, Object> eliminarFoto(Req req, TecnicoReporteModelo m, Archivos archivos) {
        int id = req.intv("id_evidencia", 0);
        if (id > 0) {
            Map<String, Object> r = m.eliminarEvidencia(id);
            if (Boolean.TRUE.equals(r.get("success"))) {
                archivos.borrar("app/" + Conv.str(r.get("ruta")));
                return Map.of("success", true);
            }
            return Map.of("success", false, "msj", r.get("msj"));
        }
        return Map.of("success", false, "msj", "El ID de la evidencia llegó vacío al servidor.");
    }

    @PostMapping(params = "accion=ajaxLimpiarEvidenciasOrden")
    public Map<String, Object> ajaxLimpiarEvidenciasOrden(Req req) {
        int id = req.intv("id_orden", 0);
        if (id > 0) {
            for (var e : m.obtenerEvidenciasPorOrden(id)) archivos.borrar("app/" + Conv.str(e.get("ruta_archivo")));
            Map<String, Object> r = m.eliminarTodasEvidenciasOrden(id);
            return Boolean.TRUE.equals(r.get("success")) ? Map.of("success", true) : Map.of("success", false, "msj", r.get("msj"));
        }
        return Map.of("success", false, "msj", "ID de orden inválido.");
    }

    @PostMapping(params = "accion=ajaxObtenerCatalogoNovedades")
    public Object ajaxObtenerCatalogoNovedades() {
        try {
            return db.list("SELECT id_tipo_novedad, nombre_novedad, estado FROM tipo_novedad WHERE estado = 1 ORDER BY nombre_novedad ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    /** La orden debe pertenecer al técnico (pendiente o, si no, en edición). */
    static boolean ordenDelTecnico(TecnicoReporteModelo m, int idOrden, int idTec) {
        if (m.obtenerDetalleOrden(idOrden, idTec) != null) return true;
        Map<String, Object> e = m.obtenerDetalleOrdenParaEdicion(idOrden);
        return e != null && Conv.toInt(e.get("id_tecnico")) == idTec;
    }

    @PostMapping(params = "accion=ajaxGuardarNovedadTecnico")
    public Map<String, Object> ajaxGuardarNovedadTecnico(Req req) {
        return guardarNovedad(req, m);
    }

    static Map<String, Object> guardarNovedad(Req req, TecnicoReporteModelo m) {
        int uid = usuario(req);
        int idOrden = req.intv("id_orden", 0);
        if (uid == 0 || idOrden == 0) return Map.of("success", false, "msj", "Sesion expirada o ID de orden invalido.");
        List<Object> crudas = new ArrayList<>(Req.asList(req.get("novedades")));
        int idTec = m.obtenerIdTecnicoPorUsuario(uid);
        if (!ordenDelTecnico(m, idOrden, idTec)) return Map.of("success", false, "msj", "La orden no existe o no te pertenece.");
        List<Integer> novs = crudas.stream().map(Conv::toInt).filter(v -> v > 0).toList();
        List<Integer> validas = new ArrayList<>();
        for (Integer n : novs) if (Boolean.TRUE.equals(m.verificarNovedad(n).get("valido"))) validas.add(n);
        if (validas.isEmpty() && !novs.isEmpty()) return Map.of("success", false, "msj", "Ninguna novedad seleccionada es valida en el catalogo.");
        boolean ok = m.guardarNovedadesTecnico(idOrden, validas);
        return Map.of("success", ok, "msj", ok ? "Novedad guardada correctamente." : "Error al guardar la novedad.");
    }

    @PostMapping(params = "accion=ajaxObtenerNovedadesTecnico")
    public Map<String, Object> ajaxObtenerNovedadesTecnico(Req req) {
        return obtenerNovedades(req, m);
    }

    static Map<String, Object> obtenerNovedades(Req req, TecnicoReporteModelo m) {
        int uid = usuario(req);
        int idOrden = req.intv("id_orden", 0);
        if (uid == 0 || idOrden == 0) return Map.of("success", false, "ids", List.of(), "msj", "Sesion expirada o ID de orden invalido.");
        int idTec = m.obtenerIdTecnicoPorUsuario(uid);
        if (!ordenDelTecnico(m, idOrden, idTec)) return Map.of("success", false, "ids", List.of(), "msj", "La orden no existe o no te pertenece.");
        List<Integer> ids = m.obtenerNovedadesOrden(idOrden);
        return Map.of("success", true, "ids", ids, "msj", ids.isEmpty() ? "Sin novedades registradas." : ids.size() + " novedad(es) registradas.");
    }

    @PostMapping(params = "accion=ajaxVerificarNovedad")
    public Map<String, Object> ajaxVerificarNovedad(Req req) {
        return m.verificarNovedad(req.intv("id_novedad", 0));
    }
}
