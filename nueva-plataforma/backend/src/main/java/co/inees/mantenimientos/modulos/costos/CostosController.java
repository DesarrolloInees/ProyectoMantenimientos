package co.inees.mantenimientos.modulos.costos;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/**
 * Port de app/controllers/costos/* (costosVer, costosCrear, costosEditar,
 * costosAdministrativosVer, costosAdministrativosCrear, costosAdministrativosEditar) y sus modelos.
 */
@RestController
public class CostosController {

    private static final Logger log = LoggerFactory.getLogger(CostosController.class);
    private final Db db;

    public CostosController(Db db) {
        this.db = db;
    }

    private static String mesActual() {
        return LocalDate.now().toString().substring(0, 7);
    }

    @SuppressWarnings("unchecked")
    private static Map<String, Object> mapa(Object o) {
        return o instanceof Map<?, ?> m ? (Map<String, Object>) m : Map.of();
    }

    /** !empty($x) ? floatval($x) : 0 */
    private static double val(Map<String, Object> m, String k) {
        Object v = m.get(k);
        return Conv.empty(v) || "0".equals(Conv.str(v)) ? 0 : Conv.toDouble(v);
    }

    private static Map<String, Object> resp(String exito, List<String> errores) {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("mensajeExito", exito == null ? "" : exito);
        r.put("errores", errores == null ? List.of() : errores);
        return r;
    }

    // ================================================================ COSTOS MOTORIZADOS — VER

    @GetMapping(value = "/api/costosVer", params = "accion=index")
    public Map<String, Object> verIndex() {
        List<Map<String, Object>> meses;
        try {
            meses = db.list("""
                    SELECT
                        DATE_FORMAT(mes_reporte, '%Y-%m') AS mes_reporte,
                        COUNT(id_tecnico)   AS cantidad_tecnicos,
                        SUM(salario)        AS total_nomina,
                        SUM(auxilio_rodamiento + gasolina + bono_meta + horas_extra + auxilio_comunicacion) AS total_operativo,
                        SUM(salario + auxilio_rodamiento + gasolina + bono_meta + horas_extra + auxilio_comunicacion) AS total_general
                    FROM costos_operativos
                    WHERE id_tecnico IS NOT NULL
                      AND estado = 1
                    GROUP BY DATE_FORMAT(mes_reporte, '%Y-%m')
                    ORDER BY mes_reporte DESC""");
        } catch (Exception e) {
            log.error("Error obteniendo meses agrupados: {}", e.getMessage());
            meses = List.of();
        }
        return Map.of("titulo", "Resumen de Costos", "listaMeses", meses);
    }

    @GetMapping(value = "/api/costosVer", params = "accion=detalle")
    public Map<String, Object> verDetalle(Req req) {
        String mes = req.str("mes", "");
        List<Map<String, Object>> det;
        try {
            det = db.list("""
                    SELECT
                        c.id_costo,
                        t.nombre_tecnico,
                        c.salario,
                        c.auxilio_rodamiento,
                        c.gasolina,
                        c.bono_meta,
                        c.horas_extra,
                        c.auxilio_comunicacion,
                        (c.salario + c.auxilio_rodamiento + c.gasolina + c.bono_meta + c.horas_extra + c.auxilio_comunicacion) AS total_general
                    FROM costos_operativos c
                    INNER JOIN tecnico t ON c.id_tecnico = t.id_tecnico
                    WHERE DATE_FORMAT(c.mes_reporte, '%Y-%m') = :mes
                      AND c.id_tecnico  IS NOT NULL
                      AND c.estado = 1
                    ORDER BY t.nombre_tecnico ASC""", "mes", mes);
        } catch (Exception e) {
            det = List.of();
        }
        return Map.of("mes", mes, "detalles", det);
    }

    @RequestMapping(value = "/api/costosVer", params = "accion=eliminar", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> verEliminar(Req req) {
        if (!req.has("id")) return Map.of("success", false);
        try {
            db.update("UPDATE costos_operativos SET estado = 0, fecha_actualizacion = NOW() WHERE id_costo = :id", "id", req.intv("id", 0));
            return Map.of("success", true, "mes", req.str("mes", ""));
        } catch (Exception e) {
            log.error("Error en borrado lógico: {}", e.getMessage());
            return Map.of("success", false);
        }
    }

    @RequestMapping(value = "/api/costosVer", params = "accion=eliminarMes", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> verEliminarMes(Req req) {
        String mes = req.str("mes", "");
        if (!mes.isEmpty()) {
            try {
                db.update("UPDATE costos_operativos SET estado = 0 WHERE DATE_FORMAT(mes_reporte, '%Y-%m') = :mes", "mes", mes);
            } catch (Exception ignored) {
            }
        }
        return Map.of("success", true);
    }

    // ================================================================ COSTOS MOTORIZADOS — CREAR

    private List<Map<String, Object>> motorizados() {
        try {
            return db.list("""
                    SELECT
                        id_tecnico as id,
                        nombre_tecnico as nombre,
                        'Técnico' as cargo
                    FROM tecnico
                    WHERE estado = 1
                    ORDER BY nombre_tecnico ASC""");
        } catch (Exception e) {
            log.error("Error obteniendo motorizados: {}", e.getMessage());
            return List.of();
        }
    }

    @GetMapping(value = "/api/costosCrear", params = "accion=index")
    public Map<String, Object> crearIndex() {
        Map<String, Object> r = resp("", null);
        r.put("titulo", "Costos Motorizados");
        r.put("listaPersonal", motorizados());
        r.put("mesActual", mesActual());
        return r;
    }

    @PostMapping(value = "/api/costosCrear", params = "accion=index")
    public Map<String, Object> crearGuardar(Req req) {
        List<String> errores = new ArrayList<>();
        String mes = req.str("mes_reporte", "");
        Map<String, Object> datos = mapa(req.get("costos"));
        if (mes.isEmpty()) errores.add("Debes seleccionar el Mes de Reporte.");
        if (datos.isEmpty()) errores.add("No hay datos para guardar.");
        String exito = "";
        if (errores.isEmpty()) {
            String fecha = mes + "-01";
            try {
                db.txRun(() -> {
                    for (var en : datos.entrySet()) {
                        Map<String, Object> c = mapa(en.getValue());
                        double sal = val(c, "salario"), hor = val(c, "horas_extra"), bon = val(c, "bono_meta"), gas = val(c, "gasolina"),
                                com = val(c, "auxilio_comunicacion"), rod = val(c, "auxilio_rodamiento");
                        if (sal + hor + bon + gas + com + rod == 0) continue;
                        db.update("""
                                INSERT INTO costos_operativos (
                                    id_tecnico, mes_reporte, salario, horas_extra,
                                    bono_meta, gasolina, auxilio_comunicacion, auxilio_rodamiento, estado
                                ) VALUES (
                                    :id_tecnico, :mes_reporte, :salario, :horas_extra,
                                    :bono_meta, :gasolina, :auxilio_comunicacion, :auxilio_rodamiento, 1
                                ) ON DUPLICATE KEY UPDATE
                                    salario               = VALUES(salario),
                                    horas_extra           = VALUES(horas_extra),
                                    bono_meta             = VALUES(bono_meta),
                                    gasolina              = VALUES(gasolina),
                                    auxilio_comunicacion  = VALUES(auxilio_comunicacion),
                                    auxilio_rodamiento    = VALUES(auxilio_rodamiento),
                                    estado                = 1""",
                                "id_tecnico", en.getKey(), "mes_reporte", fecha, "salario", sal, "horas_extra", hor, "bono_meta", bon,
                                "gasolina", gas, "auxilio_comunicacion", com, "auxilio_rodamiento", rod);
                    }
                });
                exito = "Costos de motorizados guardados correctamente.";
            } catch (Exception e) {
                log.error("Error guardando costos técnicos: {}", e.getMessage());
                errores.add("Error al guardar en la base de datos.");
            }
        }
        Map<String, Object> r = resp(exito, errores);
        r.put("titulo", "Costos Motorizados");
        r.put("listaPersonal", motorizados());
        r.put("mesActual", mes.isEmpty() ? mesActual() : mes);
        return r;
    }

    // ================================================================ COSTOS MOTORIZADOS — EDITAR

    private List<Map<String, Object>> datosPorMes(String mes) {
        try {
            return db.list("""
                    SELECT
                        c.id_costo,
                        c.id_tecnico,
                        t.nombre_tecnico,
                        c.salario,
                        c.auxilio_rodamiento,
                        c.gasolina,
                        c.bono_meta,
                        c.horas_extra,
                        c.auxilio_comunicacion
                    FROM costos_operativos c
                    INNER JOIN tecnico t ON c.id_tecnico = t.id_tecnico
                    WHERE DATE_FORMAT(c.mes_reporte, '%Y-%m') = :mes
                      AND c.id_tecnico IS NOT NULL
                      AND c.estado = 1
                    ORDER BY t.nombre_tecnico ASC""", "mes", mes);
        } catch (Exception e) {
            log.error("Error obteniendo datos para editar: {}", e.getMessage());
            return List.of();
        }
    }

    @RequestMapping(value = "/api/costosEditar", params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> editarIndex(Req req) {
        String mes = req.str("mes", "");
        if (mes.isEmpty()) return Map.of("redirigir", "costosVer");
        List<String> errores = new ArrayList<>();
        String exito = "";
        if (req.isPost()) {
            Map<String, Object> datos = mapa(req.get("costos"));
            if (!datos.isEmpty()) {
                try {
                    db.txRun(() -> {
                        for (var en : datos.entrySet()) {
                            Map<String, Object> c = mapa(en.getValue());
                            db.update("""
                                    UPDATE costos_operativos SET
                                        salario               = :salario,
                                        horas_extra           = :horas_extra,
                                        bono_meta             = :bono_meta,
                                        gasolina              = :gasolina,
                                        auxilio_comunicacion  = :auxilio_comunicacion,
                                        auxilio_rodamiento    = :auxilio_rodamiento
                                    WHERE id_costo    = :id_costo
                                      AND id_tecnico IS NOT NULL
                                      AND estado = 1""",
                                    "salario", Conv.toDouble(c.get("salario")), "horas_extra", Conv.toDouble(c.get("horas_extra")),
                                    "bono_meta", Conv.toDouble(c.get("bono_meta")), "gasolina", Conv.toDouble(c.get("gasolina")),
                                    "auxilio_comunicacion", Conv.toDouble(c.get("auxilio_comunicacion")),
                                    "auxilio_rodamiento", Conv.toDouble(c.get("auxilio_rodamiento")), "id_costo", Conv.toInt(en.getKey()));
                        }
                    });
                    exito = "Costos actualizados correctamente.";
                } catch (Exception e) {
                    log.error("Error actualizando costos: {}", e.getMessage());
                    errores.add("Error al actualizar la base de datos.");
                }
            } else {
                errores.add("No se recibieron datos para actualizar.");
            }
        }
        var datos = datosPorMes(mes);
        if (datos.isEmpty()) return Map.of("redirigir", "costosVer");
        Map<String, Object> r = resp(exito, errores);
        r.put("titulo", "Editar Costos " + mes);
        r.put("mes", mes);
        r.put("datosExistentes", datos);
        return r;
    }

    // ================================================================ ADMINISTRATIVOS — VER

    @GetMapping(value = "/api/costosAdministrativosVer", params = "accion=index")
    public Map<String, Object> admVerIndex() {
        List<Map<String, Object>> rep;
        try {
            rep = db.list("""
                    SELECT
                        DATE_FORMAT(T.mes_reporte, '%Y-%m') AS mes_reporte,
                        (SELECT COALESCE(SUM(valor), 0)
                            FROM gastos_administrativos
                            WHERE mes_reporte = T.mes_reporte
                                AND estado = 1
                        ) as total_gastos,
                        (SELECT COALESCE(SUM(c.salario + c.horas_extra + c.bono_meta + c.gasolina + c.auxilio_comunicacion), 0)
                            FROM costos_operativos c
                            INNER JOIN usuarios u ON c.id_usuario = u.usuario_id
                            WHERE c.mes_reporte = T.mes_reporte
                                AND c.estado = 1
                                AND u.nivel_acceso != 3
                        ) as total_nomina
                    FROM (
                        SELECT DISTINCT mes_reporte FROM gastos_administrativos WHERE estado = 1
                        UNION
                        SELECT DISTINCT mes_reporte FROM costos_operativos
                        WHERE id_usuario IS NOT NULL AND estado = 1
                    ) AS T
                    ORDER BY T.mes_reporte DESC""");
        } catch (Exception e) {
            log.error("Error en obtenerResumenMensual: {}", e.getMessage());
            rep = List.of();
        }
        return Map.of("titulo", "Histórico Costos Administrativos", "reporteMensual", rep);
    }

    @RequestMapping(value = "/api/costosAdministrativosVer", params = "accion=eliminarMes", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> admVerEliminarMes(Req req) {
        String mes = req.str("mes", "");
        if (!mes.isEmpty()) {
            try {
                db.txRun(() -> {
                    db.update("UPDATE gastos_administrativos SET estado = 0 WHERE DATE_FORMAT(mes_reporte, '%Y-%m') = :mes", "mes", mes);
                    db.update("""
                            UPDATE costos_operativos
                            SET estado = 0,
                                fecha_actualizacion = NOW()
                            WHERE DATE_FORMAT(mes_reporte, '%Y-%m') = :mes
                              AND id_usuario IS NOT NULL""", "mes", mes);
                });
            } catch (Exception e) {
                log.error("Error eliminando mes administrativo completo: {}", e.getMessage());
            }
        }
        return Map.of("success", true);
    }

    // ================================================================ ADMINISTRATIVOS — CREAR

    private List<Map<String, Object>> gastosPorMes(String fecha) {
        try {
            return db.list("SELECT * FROM gastos_administrativos WHERE mes_reporte = :mes AND estado = 1 ORDER BY id_gasto DESC", "mes", fecha);
        } catch (Exception e) {
            return List.of();
        }
    }

    private boolean eliminarGasto(Object id) {
        try {
            db.update("UPDATE gastos_administrativos SET estado = 0 WHERE id_gasto = :id", "id", Conv.toInt(id));
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    private Map<String, Object> admCrearVista(String mes, String exito, List<String> errores) {
        List<Map<String, Object>> personal;
        try {
            personal = db.list("""
                    SELECT
                        usuario_id as id,
                        nombre,
                        IFNULL(cargo, 'Administrativo') as cargo
                    FROM usuarios
                    WHERE (estado = 'activo' OR estado = '1')
                      AND nivel_acceso != 3
                    ORDER BY nombre ASC""");
        } catch (Exception e) {
            log.error("Error obteniendo personal admin: {}", e.getMessage());
            personal = List.of();
        }
        var gastos = gastosPorMes(mes + "-01");
        double total = 0;
        for (var g : gastos) total += Conv.toDouble(g.get("valor"));
        Map<String, Object> r = resp(exito, errores);
        r.put("titulo", "Gestión de Costos Administrativos");
        r.put("mesSeleccionado", mes);
        r.put("listaPersonal", personal);
        r.put("listaGastos", gastos);
        r.put("totalGastos", total);
        return r;
    }

    @RequestMapping(value = "/api/costosAdministrativosCrear", params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> admCrearIndex(Req req) {
        String mes = req.str("mes_reporte", mesActual());
        String exito = "";
        if (req.has("eliminar_id") && eliminarGasto(req.get("eliminar_id"))) exito = "Gasto eliminado correctamente.";
        return admCrearVista(mes, exito, List.of());
    }

    @PostMapping(value = "/api/costosAdministrativosCrear", params = "accion=guardar_nomina")
    public Map<String, Object> admGuardarNomina(Req req) {
        String mes = req.str("mes_reporte", mesActual());
        String fecha = mes + "-01";
        Map<String, Object> datos = mapa(req.get("costos"));
        List<String> errores = new ArrayList<>();
        String exito = "";
        if (!datos.isEmpty()) {
            try {
                db.txRun(() -> {
                    for (var en : datos.entrySet()) {
                        Map<String, Object> c = mapa(en.getValue());
                        double sal = val(c, "salario"), hor = val(c, "horas_extra"), bon = val(c, "bono_meta"), gas = val(c, "gasolina"),
                                com = val(c, "auxilio_comunicacion");
                        if (sal + hor + bon + gas + com == 0) continue;
                        db.update("""
                                INSERT INTO costos_operativos (
                                    id_usuario, mes_reporte, salario, horas_extra,
                                    bono_meta, gasolina, auxilio_comunicacion, auxilio_rodamiento, estado
                                ) VALUES (
                                    :id_usuario, :mes_reporte, :salario, :horas_extra,
                                    :bono_meta, :gasolina, :auxilio_comunicacion, :auxilio_rodamiento, 1
                                ) ON DUPLICATE KEY UPDATE
                                    salario              = VALUES(salario),
                                    horas_extra          = VALUES(horas_extra),
                                    bono_meta            = VALUES(bono_meta),
                                    gasolina             = VALUES(gasolina),
                                    auxilio_comunicacion = VALUES(auxilio_comunicacion),
                                    auxilio_rodamiento   = VALUES(auxilio_rodamiento),
                                    estado               = 1""",
                                "id_usuario", en.getKey(), "mes_reporte", fecha, "salario", sal, "horas_extra", hor, "bono_meta", bon,
                                "gasolina", gas, "auxilio_comunicacion", com, "auxilio_rodamiento", 0);
                    }
                });
                exito = "Nómina administrativa actualizada correctamente.";
            } catch (Exception e) {
                log.error("Error guardarNominaAdmin: {}", e.getMessage());
                errores.add("Error al guardar la nómina en la base de datos.");
            }
        } else {
            errores.add("No se recibieron datos para guardar.");
        }
        return admCrearVista(mes, exito, errores);
    }

    @PostMapping(value = "/api/costosAdministrativosCrear", params = "accion=guardar_gasto")
    public Map<String, Object> admGuardarGasto(Req req) {
        String mes = req.str("mes_reporte", mesActual());
        String concepto = req.str("concepto", "").trim();
        String categoria = req.str("categoria", "").trim();
        double valor = Conv.toDouble(req.str("valor", "0"));
        List<String> errores = new ArrayList<>();
        String exito = "";
        if (concepto.isEmpty() || valor <= 0) {
            errores.add("Debes ingresar un concepto y un valor mayor a 0.");
        } else {
            try {
                db.update("""
                        INSERT INTO gastos_administrativos (mes_reporte, concepto, categoria, valor, estado)
                        VALUES (:mes, :concepto, :categoria, :valor, 1)""",
                        "mes", mes + "-01", "concepto", concepto, "categoria", categoria, "valor", valor);
                exito = "Gasto agregado exitosamente.";
            } catch (Exception e) {
                log.error("Error crearGasto: {}", e.getMessage());
                errores.add("Error al guardar el gasto.");
            }
        }
        return admCrearVista(mes, exito, errores);
    }

    // ================================================================ ADMINISTRATIVOS — EDITAR

    private Map<String, Object> admEditarVista(String mes, String exito, List<String> errores) {
        String fecha = mes + "-01";
        List<Map<String, Object>> nomina;
        try {
            nomina = db.list("""
                    SELECT
                        u.usuario_id as id,
                        u.nombre,
                        IFNULL(u.cargo, 'Administrativo') as cargo,
                        COALESCE(c.salario, 0)             as salario,
                        COALESCE(c.horas_extra, 0)         as horas_extra,
                        COALESCE(c.bono_meta, 0)           as bono_meta,
                        COALESCE(c.gasolina, 0)            as gasolina,
                        COALESCE(c.auxilio_comunicacion, 0)as auxilio_comunicacion
                    FROM usuarios u
                    LEFT JOIN costos_operativos c
                        ON u.usuario_id = c.id_usuario
                       AND c.mes_reporte = :mes
                       AND c.estado = 1
                    WHERE (u.estado = 'activo' OR u.estado = '1')
                      AND u.nivel_acceso != 3
                    ORDER BY u.nombre ASC""", "mes", fecha);
        } catch (Exception e) {
            log.error("Error obtenerNominaCompletaPorMes: {}", e.getMessage());
            nomina = List.of();
        }
        Map<String, Object> r = resp(exito, errores);
        r.put("titulo", "Editar Costos Administrativos");
        r.put("mesSeleccionado", mes);
        r.put("listaNomina", nomina);
        r.put("listaGastos", gastosPorMes(fecha));
        return r;
    }

    @RequestMapping(value = "/api/costosAdministrativosEditar", params = "accion=index", method = { RequestMethod.GET, RequestMethod.POST })
    public Map<String, Object> admEditarIndex(Req req) {
        String mes = req.str("mes_reporte", mesActual());
        String exito = "";
        if (req.has("eliminar_gasto_id") && eliminarGasto(req.get("eliminar_gasto_id"))) exito = "Gasto eliminado.";
        return admEditarVista(mes, exito, List.of());
    }

    @PostMapping(value = "/api/costosAdministrativosEditar", params = "accion=actualizar_nomina")
    public Map<String, Object> admActualizarNomina(Req req) {
        String mes = req.str("mes_reporte", "");
        String fecha = mes + "-01";
        Map<String, Object> datos = mapa(req.get("nomina"));
        if (datos.isEmpty()) return admEditarVista(mes, "", List.of("No se enviaron datos para actualizar."));
        try {
            db.txRun(() -> {
                for (var en : datos.entrySet()) {
                    Map<String, Object> c = mapa(en.getValue());
                    db.update("""
                            INSERT INTO costos_operativos (
                                id_usuario, mes_reporte, salario, horas_extra,
                                bono_meta, gasolina, auxilio_comunicacion, auxilio_rodamiento, estado
                            ) VALUES (
                                :id_usuario, :mes_reporte, :salario, :horas_extra,
                                :bono_meta, :gasolina, :auxilio_comunicacion, 0, 1
                            ) ON DUPLICATE KEY UPDATE
                                salario              = VALUES(salario),
                                horas_extra          = VALUES(horas_extra),
                                bono_meta            = VALUES(bono_meta),
                                gasolina             = VALUES(gasolina),
                                auxilio_comunicacion = VALUES(auxilio_comunicacion),
                                estado               = 1""",
                            "id_usuario", en.getKey(), "mes_reporte", fecha, "salario", Conv.toDouble(c.get("salario")),
                            "horas_extra", Conv.toDouble(c.get("horas_extra")), "bono_meta", Conv.toDouble(c.get("bono_meta")),
                            "gasolina", Conv.toDouble(c.get("gasolina")), "auxilio_comunicacion", Conv.toDouble(c.get("auxilio_comunicacion")));
                }
            });
            return admEditarVista(mes, "Nómina administrativa actualizada exitosamente.", List.of());
        } catch (Exception e) {
            log.error("Error actualizarNominaMasiva: {}", e.getMessage());
            return admEditarVista(mes, "", List.of("Error al actualizar la base de datos."));
        }
    }

    @PostMapping(value = "/api/costosAdministrativosEditar", params = "accion=actualizar_gastos_generales")
    public Map<String, Object> admActualizarGastos(Req req) {
        String mes = req.str("mes_reporte", "");
        Map<String, Object> datos = mapa(req.get("gastos_generales"));
        if (datos.isEmpty()) return admEditarVista(mes, "", List.of("No hay gastos para actualizar."));
        try {
            db.txRun(() -> {
                for (var en : datos.entrySet()) {
                    Map<String, Object> g = mapa(en.getValue());
                    db.update("""
                            UPDATE gastos_administrativos SET
                                concepto   = :concepto,
                                categoria  = :categoria,
                                valor      = :valor
                            WHERE id_gasto = :id
                              AND estado   = 1""",
                            "concepto", g.get("concepto"), "categoria", g.get("categoria"), "valor", g.get("valor"), "id", Conv.toInt(en.getKey()));
                }
            });
            return admEditarVista(mes, "Gastos generales actualizados correctamente.", List.of());
        } catch (Exception e) {
            log.error("Error actualizarGastosMasivos: {}", e.getMessage());
            return admEditarVista(mes, "", List.of("Error al actualizar gastos generales."));
        }
    }
}
