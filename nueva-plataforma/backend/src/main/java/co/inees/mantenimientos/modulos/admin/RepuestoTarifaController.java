package co.inees.mantenimientos.modulos.admin;

import java.time.Year;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/** Port de repuesto{Ver,Crear,Editar,Eliminar}, tarifa{Ver,Crear,Editar,Eliminar} y tarifaMasiva. */
@RestController
@RequestMapping("/api")
public class RepuestoTarifaController {

    private static final Logger log = LoggerFactory.getLogger(RepuestoTarifaController.class);
    private final Db db;

    public RepuestoTarifaController(Db db) {
        this.db = db;
    }

    // ================================================================ REPUESTOS

    @GetMapping("/repuestoVer")
    public Map<String, Object> repuestoVer() {
        try {
            return Map.of("repuestos", db.list("""
                    SELECT
                        id_repuesto,
                        nombre_repuesto,
                        codigo_referencia,
                        estado
                    FROM
                        repuesto
                    WHERE
                        estado = 1
                    ORDER BY
                        nombre_repuesto ASC"""));
        } catch (Exception e) {
            return Map.of("repuestos", List.of());
        }
    }

    @PostMapping("/repuestoCrear")
    public Map<String, Object> repuestoCrear(Req req) {
        String nombre = req.trim("nombre_repuesto");
        String codigo = req.trim("codigo_referencia");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(nombre)) errores.add("El nombre del repuesto es obligatorio.");
        if (errores.isEmpty()) {
            try {
                db.update("""
                        INSERT INTO repuesto (
                            nombre_repuesto,
                            codigo_referencia,
                            estado,
                            requiere_devolucion
                        ) VALUES (
                            :nombre,
                            :codigo,
                            :estado,
                            :requiere_devolucion
                        )""", "nombre", nombre, "codigo", Conv.empty(codigo) ? null : codigo,
                        "estado", Conv.toInt(req.str("estado", "1")), "requiere_devolucion", Conv.toInt(req.str("requiere_devolucion", "0")));
                return Map.of("success", true, "redirect", "repuestoVer");
            } catch (Exception e) {
                log.error("Error Base de Datos (crearRepuesto): {}", e.getMessage());
                errores.add("Error al guardar en BD. Verifica que no exista un código de referencia duplicado.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    @GetMapping("/repuestoEditar")
    public Map<String, Object> repuestoEditarForm(Req req) {
        String id = req.str("id");
        if (id.isEmpty()) return Map.of("redirect", "repuestoVer");
        Map<String, Object> d = db.one("SELECT * FROM repuesto WHERE id_repuesto = :id", "id", Conv.toInt(id));
        if (d == null) return Map.of("redirect", "repuestoVer");
        return Map.of("datos", d);
    }

    @PostMapping("/repuestoEditar")
    public Map<String, Object> repuestoEditar(Req req) {
        String id = req.str("id_repuesto", req.str("id"));
        if (id.isEmpty()) return Map.of("success", false, "redirect", "repuestoVer");
        String nombre = req.trim("nombre_repuesto");
        String codigo = req.trim("codigo_referencia");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(nombre)) errores.add("El nombre es obligatorio.");
        if (errores.isEmpty()) {
            try {
                db.update("""
                        UPDATE repuesto SET
                            nombre_repuesto = :nombre,
                            codigo_referencia = :codigo,
                            estado = :estado,
                            requiere_devolucion = :requiere_devolucion
                        WHERE
                            id_repuesto = :id""", "nombre", nombre, "codigo", Conv.empty(codigo) ? null : codigo,
                        "estado", Conv.toInt(req.str("estado", "1")), "requiere_devolucion", Conv.toInt(req.str("requiere_devolucion", "0")),
                        "id", Conv.toInt(id));
                return Map.of("success", true, "redirect", "repuestoVer");
            } catch (Exception e) {
                log.error("Error actualizando repuesto: {}", e.getMessage());
                errores.add("Error al actualizar en la BD.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    @PostMapping("/repuestoEliminar")
    public Map<String, Object> repuestoEliminar(Req req) {
        String id = req.str("id");
        if (!id.isEmpty() && id.matches("^\\d+$")) {
            try {
                db.update("UPDATE repuesto SET estado = 0 WHERE id_repuesto = :id", "id", Conv.toInt(id));
            } catch (Exception e) {
                log.error("Error al eliminar repuesto: {}", e.getMessage());
            }
        }
        return Map.of("success", true, "redirect", "repuestoVer");
    }

    // ================================================================ TARIFAS

    @GetMapping("/tarifaVer")
    public Map<String, Object> tarifaVer() {
        return Map.of("tarifas", db.list("""
                SELECT
                    t.id_tarifa,
                    t.precio,
                    t.año_vigencia,
                    tm.nombre_tipo_maquina,
                    tmt.nombre_completo AS nombre_mantenimiento,
                    mo.nombre_modalidad
                FROM tarifa t
                INNER JOIN tipo_maquina tm ON t.id_tipo_maquina = tm.id_tipo_maquina
                INNER JOIN tipo_mantenimiento tmt ON t.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                INNER JOIN modalidad_operativa mo ON t.id_modalidad = mo.id_modalidad
                ORDER BY t.año_vigencia DESC, tm.nombre_tipo_maquina ASC"""));
    }

    @GetMapping(value = "/tarifaCrear", params = "accion=index")
    public Map<String, Object> tarifaCrearForm() {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("listaMaquinas", db.list("SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC"));
        r.put("listaMantenimientos", db.list("SELECT * FROM tipo_mantenimiento WHERE estado = 1 ORDER BY nombre_completo ASC"));
        r.put("listaModalidades", db.list("SELECT * FROM modalidad_operativa ORDER BY id_modalidad ASC"));
        r.put("anio", Year.now().getValue());
        return r;
    }

    @GetMapping(value = "/tarifaCrear", params = "accion=verificarMaquinas")
    public List<Object> verificarMaquinas(Req req) {
        return db.column("SELECT DISTINCT id_tipo_maquina FROM tarifa WHERE año_vigencia = :anio", "anio", req.intv("anio"));
    }

    @PostMapping("/tarifaCrear")
    public Map<String, Object> tarifaCrear(Req req) {
        List<Object> ids = req.arr("ids_maquinas");
        String anio = req.str("año_vigencia", String.valueOf(Year.now().getValue()));
        Map<String, Object> precios = req.mapa("precios");
        List<String> errores = new ArrayList<>();
        if (ids.isEmpty()) errores.add("Debes seleccionar al menos una Máquina.");
        if (precios.isEmpty()) errores.add("No has ingresado ningún precio.");
        if (errores.isEmpty()) {
            try {
                db.txRun(() -> {
                    for (Object idMaquina : ids) {
                        for (Map.Entry<String, Object> manto : precios.entrySet()) {
                            for (Map.Entry<String, Object> mod : Conv.map(manto.getValue()).entrySet()) {
                                String precio = Conv.str(mod.getValue()).trim();
                                if (esNumerico(precio) && Conv.toDouble(precio) > 0) {
                                    db.update("""
                                            INSERT INTO tarifa (id_tipo_maquina, id_tipo_mantenimiento, id_modalidad, precio, año_vigencia)
                                            VALUES (:maquina, :mantenimiento, :modalidad, :precio, :anio)""",
                                            "maquina", idMaquina, "mantenimiento", manto.getKey(), "modalidad", mod.getKey(),
                                            "precio", precio, "anio", anio);
                                }
                            }
                        }
                    }
                });
                return Map.of("success", true, "mensaje", "¡Tarifas guardadas correctamente para " + ids.size() + " máquinas!", "redirect", "tarifaVer");
            } catch (Exception e) {
                log.error("Error masivo tarifa: {}", e.getMessage());
                errores.add("Hubo un error al guardar. Revisa el log de errores.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    static boolean esNumerico(String s) {
        if (s == null || s.isBlank()) return false;
        try {
            Double.parseDouble(s);
            return true;
        } catch (NumberFormatException e) {
            return false;
        }
    }

    @GetMapping("/tarifaEditar")
    public Map<String, Object> tarifaEditarForm(Req req) {
        String id = req.str("id");
        if (id.isEmpty()) return Map.of("redirect", "tarifaVer");
        Map<String, Object> d = db.one("SELECT * FROM tarifa WHERE id_tarifa = :id", "id", Conv.toInt(id));
        if (d == null) return Map.of("redirect", "tarifaVer");
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("datos", d);
        r.put("listaMaquinas", db.list("SELECT * FROM tipo_maquina WHERE estado = 1"));
        r.put("listaMantenimientos", db.list("SELECT * FROM tipo_mantenimiento WHERE estado = 1"));
        r.put("listaModalidades", db.list("SELECT * FROM modalidad_operativa"));
        return r;
    }

    @PostMapping("/tarifaEditar")
    public Map<String, Object> tarifaEditar(Req req) {
        String id = req.str("id_tarifa", req.str("id"));
        if (id.isEmpty()) return Map.of("success", false, "redirect", "tarifaVer");
        try {
            db.update("""
                    UPDATE tarifa SET
                    id_tipo_maquina = :maquina,
                    id_tipo_mantenimiento = :mantenimiento,
                    id_modalidad = :modalidad,
                    precio = :precio,
                    año_vigencia = :anio
                    WHERE id_tarifa = :id""", "maquina", req.intv("id_tipo_maquina"), "mantenimiento", req.intv("id_tipo_mantenimiento"),
                    "modalidad", req.intv("id_modalidad"), "precio", req.str("precio"), "anio", req.intv("año_vigencia"), "id", Conv.toInt(id));
            return Map.of("success", true, "redirect", "tarifaVer");
        } catch (Exception e) {
            return Map.of("success", false, "errores", List.of("Error al actualizar la tarifa."));
        }
    }

    @PostMapping("/tarifaEliminar")
    public Map<String, Object> tarifaEliminar(Req req) {
        String id = req.str("id");
        if (!id.isEmpty() && id.matches("^\\d+$")) {
            db.update("DELETE FROM tarifa WHERE id_tarifa = :id", "id", Conv.toInt(id));
        }
        return Map.of("success", true, "redirect", "tarifaVer");
    }

    // ================================================================ TARIFA MASIVA

    @GetMapping("/tarifaMasiva")
    public Map<String, Object> tarifaMasiva(Req req) {
        String idMaquina = req.str("id_maquina");
        String anio = req.str("año", String.valueOf(Year.now().getValue()));
        return masivaRespuesta(idMaquina, anio, null, "");
    }

    @PostMapping("/tarifaMasiva")
    public Map<String, Object> tarifaMasivaGuardar(Req req) {
        String mensaje = null;
        String tipo = "";
        Map<String, Object> precios = req.mapa("precios");
        if (!precios.isEmpty()) {
            try {
                db.txRun(() -> {
                    for (Map.Entry<String, Object> e : precios.entrySet()) {
                        String precio = Conv.str(e.getValue()).trim();
                        if (esNumerico(precio) && Conv.toDouble(precio) >= 0) {
                            db.update("UPDATE tarifa SET precio = :precio WHERE id_tarifa = :id", "precio", precio, "id", Conv.toInt(e.getKey()));
                        }
                    }
                });
                mensaje = "¡Tarifas actualizadas correctamente!";
                tipo = "success";
            } catch (Exception e) {
                mensaje = "Hubo un error al guardar los cambios.";
                tipo = "error";
            }
        }
        return masivaRespuesta(req.str("filtro_maquina"), req.str("filtro_anio"), mensaje, tipo);
    }

    private Map<String, Object> masivaRespuesta(String idMaquina, String anio, String mensaje, String tipo) {
        Map<String, Object> r = new LinkedHashMap<>();
        List<Map<String, Object>> tarifas = List.of();
        if (!Conv.empty(idMaquina) && !Conv.empty(anio)) {
            tarifas = db.list("""
                    SELECT
                        t.id_tarifa,
                        t.precio,
                        COALESCE(tmt.nombre_completo, '--- MANTENIMIENTO BORRADO ---') AS nombre_mantenimiento,
                        COALESCE(mo.nombre_modalidad, '--- MODALIDAD BORRADA ---') AS nombre_modalidad
                    FROM tarifa t
                    LEFT JOIN tipo_mantenimiento tmt ON t.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                    LEFT JOIN modalidad_operativa mo ON t.id_modalidad = mo.id_modalidad
                    WHERE t.id_tipo_maquina = :maquina
                      AND t.año_vigencia = :anio
                    ORDER BY tmt.nombre_completo ASC, mo.nombre_modalidad ASC""", "maquina", Conv.toInt(idMaquina), "anio", Conv.toInt(anio));
        }
        r.put("mensaje", mensaje);
        r.put("tipo_mensaje", tipo);
        r.put("id_maquina", idMaquina);
        r.put("anio", anio);
        r.put("tarifas", tarifas);
        r.put("listaMaquinas", db.list("SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC"));
        return r;
    }
}
