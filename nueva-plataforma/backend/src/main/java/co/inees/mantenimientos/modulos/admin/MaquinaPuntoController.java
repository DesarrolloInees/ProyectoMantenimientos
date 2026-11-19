package co.inees.mantenimientos.modulos.admin;

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

/** Port de maquina{Ver,Crear,Editar,Eliminar} y punto{Ver,Crear,Editar,Eliminar}. */
@RestController
@RequestMapping("/api")
public class MaquinaPuntoController {

    private static final Logger log = LoggerFactory.getLogger(MaquinaPuntoController.class);
    private final Db db;

    public MaquinaPuntoController(Db db) {
        this.db = db;
    }

    // ================================================================ MÁQUINAS

    private List<Map<String, Object>> puntosMaquina() {
        return db.list("""
                SELECT p.id_punto, p.nombre_punto, c.nombre_cliente
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                WHERE p.estado = 1
                ORDER BY p.nombre_punto ASC""");
    }

    private List<Map<String, Object>> tiposMaquina() {
        return db.list("SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC");
    }

    @GetMapping("/maquinaVer")
    public Map<String, Object> maquinaVer() {
        return Map.of("maquinas", db.list("""
                SELECT
                    m.id_maquina, m.device_id, m.ultima_visita, m.estado, m.activo_operativo,
                    p.nombre_punto,
                    tm.nombre_tipo_maquina
                FROM maquina m
                INNER JOIN punto p ON m.id_punto = p.id_punto
                INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE m.estado = 1
                ORDER BY m.id_maquina DESC"""));
    }

    @GetMapping("/maquinaCrear")
    public Map<String, Object> maquinaCrearForm() {
        return Map.of("listaPuntos", puntosMaquina(), "listaTipos", tiposMaquina());
    }

    @PostMapping("/maquinaCrear")
    public Map<String, Object> maquinaCrear(Req req) {
        String dev = req.trim("device_id");
        String punto = req.str("id_punto");
        String tipo = req.str("id_tipo_maquina");
        String visita = req.str("ultima_visita");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(dev)) errores.add("El Device ID es obligatorio.");
        if (Conv.empty(punto)) errores.add("Debes seleccionar un Punto.");
        if (Conv.empty(tipo)) errores.add("Debes seleccionar un Tipo.");
        if (errores.isEmpty()) {
            try {
                db.update("""
                        INSERT INTO maquina (
                            device_id, id_punto, id_tipo_maquina, ultima_visita, estado
                        ) VALUES (
                            :dev, :punto, :tipo, :visita, 1
                        )""", "dev", dev, "punto", Conv.toInt(punto), "tipo", Conv.toInt(tipo),
                        "visita", Conv.empty(visita) ? null : visita);
                return Map.of("success", true, "redirect", "maquinaVer");
            } catch (Exception e) {
                log.error("Device ID duplicado: {}", e.getMessage());
                errores.add("Error: El Device ID ya existe o hubo un fallo en BD.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    @GetMapping("/maquinaEditar")
    public Map<String, Object> maquinaEditarForm(Req req) {
        String id = req.str("id");
        if (id.isEmpty()) return Map.of("redirect", "maquinaVer");
        Map<String, Object> d = db.one("SELECT * FROM maquina WHERE id_maquina = :id", "id", Conv.toInt(id));
        if (d == null) return Map.of("redirect", "maquinaVer");
        return Map.of("datos", d, "listaPuntos", puntosMaquina(), "listaTipos", tiposMaquina());
    }

    @PostMapping("/maquinaEditar")
    public Map<String, Object> maquinaEditar(Req req) {
        String id = req.str("id_maquina", req.str("id"));
        if (id.isEmpty()) return Map.of("success", false, "redirect", "maquinaVer");
        String visita = req.str("ultima_visita");
        try {
            db.update("""
                    UPDATE maquina SET
                    device_id = :dev,
                    id_punto = :punto,
                    id_tipo_maquina = :tipo,
                    ultima_visita = :visita,
                    estado = :est
                    WHERE id_maquina = :id""", "dev", req.trim("device_id"), "punto", req.intv("id_punto"),
                    "tipo", req.intv("id_tipo_maquina"), "visita", Conv.empty(visita) ? null : visita,
                    "est", req.intv("estado"), "id", Conv.toInt(id));
            return Map.of("success", true, "redirect", "maquinaVer");
        } catch (Exception e) {
            return Map.of("success", false, "errores", List.of("Error al actualizar (¿Device ID duplicado?)."));
        }
    }

    @PostMapping("/maquinaEliminar")
    public Map<String, Object> maquinaEliminar(Req req) {
        String id = req.str("id");
        if (!id.isEmpty() && id.matches("^\\d+$")) {
            db.update("UPDATE maquina SET estado = 0 WHERE id_maquina = :id", "id", Conv.toInt(id));
        }
        return Map.of("success", true, "redirect", "maquinaVer");
    }

    // ================================================================ PUNTOS

    private Map<String, Object> listasPunto(boolean ordenadas) {
        Map<String, Object> r = new LinkedHashMap<>();
        if (ordenadas) {
            r.put("listaClientes", db.list("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC"));
            r.put("listaMunicipios", db.list("SELECT id_municipio, nombre_municipio FROM municipio ORDER BY nombre_municipio ASC"));
            r.put("listaDelegaciones", db.list("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC"));
            r.put("listaModalidades", db.list("SELECT id_modalidad, nombre_modalidad FROM modalidad_operativa ORDER BY nombre_modalidad ASC"));
        } else {
            r.put("listaClientes", db.list("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1"));
            r.put("listaMunicipios", db.list("SELECT id_municipio, nombre_municipio FROM municipio"));
            r.put("listaDelegaciones", db.list("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1"));
            r.put("listaModalidades", db.list("SELECT id_modalidad, nombre_modalidad FROM modalidad_operativa"));
        }
        return r;
    }

    @GetMapping("/puntoVer")
    public Map<String, Object> puntoVer() {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("puntos", db.list("""
                SELECT
                    p.id_punto, p.nombre_punto, p.direccion,
                    c.nombre_cliente,
                    mun.nombre_municipio,
                    del.nombre_delegacion,
                    mo.nombre_modalidad
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                INNER JOIN municipio mun ON p.id_municipio = mun.id_municipio
                INNER JOIN modalidad_operativa mo ON p.id_modalidad = mo.id_modalidad
                LEFT JOIN delegacion del ON p.id_delegacion = del.id_delegacion
                WHERE p.estado = 1
                ORDER BY p.id_punto DESC"""));
        r.put("datosExcel", db.list("""
                SELECT
                    p.id_punto,
                    c.nombre_cliente AS 'Cliente',
                    p.nombre_punto AS 'Punto',
                    tm.nombre_tipo_maquina AS 'Tipo_Maquina',
                    m.device_id AS 'Device_ID',
                    p.direccion AS 'Direccion'
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                LEFT JOIN maquina m ON p.id_punto = m.id_punto AND m.estado = 1
                LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE p.estado = 1
                ORDER BY c.nombre_cliente ASC, p.nombre_punto ASC"""));
        return r;
    }

    @GetMapping("/puntoCrear")
    public Map<String, Object> puntoCrearForm() {
        return listasPunto(true);
    }

    @PostMapping("/puntoCrear")
    public Map<String, Object> puntoCrear(Req req) {
        String nombre = req.trim("nombre_punto");
        String cli = req.str("id_cliente");
        String mun = req.str("id_municipio");
        String mod = req.str("id_modalidad");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(nombre)) errores.add("El nombre del punto es obligatorio.");
        if (Conv.empty(cli)) errores.add("Debes asignar un cliente.");
        if (Conv.empty(mun)) errores.add("Debes seleccionar un municipio.");
        if (Conv.empty(mod)) errores.add("La modalidad es obligatoria.");
        if (errores.isEmpty()) {
            try {
                db.update("""
                        INSERT INTO punto (
                            nombre_punto, direccion, codigo_1, codigo_2,
                            id_municipio, id_delegacion, zona, id_modalidad, id_cliente, estado
                        ) VALUES (
                            :nombre, :dir, :cod1, :cod2,
                            :mun, :del, :zona, :mod, :cli, 1
                        )""", "nombre", nombre, "dir", req.trim("direccion"), "cod1", req.trim("codigo_1"),
                        "cod2", req.trim("codigo_2"), "mun", Conv.toInt(mun),
                        "del", Conv.empty(req.str("id_delegacion")) ? null : req.str("id_delegacion"),
                        "zona", Conv.empty(req.trim("zona")) ? null : req.trim("zona"),
                        "mod", Conv.toInt(mod), "cli", Conv.toInt(cli));
                return Map.of("success", true, "redirect", "puntoVer");
            } catch (Exception e) {
                log.error("Error crear punto: {}", e.getMessage());
                errores.add("Error al guardar en BD.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    @GetMapping("/puntoEditar")
    public Map<String, Object> puntoEditarForm(Req req) {
        String id = req.str("id");
        if (id.isEmpty()) return Map.of("redirect", "puntoVer");
        Map<String, Object> d = db.one("SELECT * FROM punto WHERE id_punto = :id", "id", Conv.toInt(id));
        if (d == null) return Map.of("redirect", "puntoVer");
        Map<String, Object> r = listasPunto(false);
        r.put("datos", d);
        return r;
    }

    @PostMapping("/puntoEditar")
    public Map<String, Object> puntoEditar(Req req) {
        String id = req.str("id_punto", req.str("id"));
        if (id.isEmpty()) return Map.of("success", false, "redirect", "puntoVer");
        try {
            db.update("""
                    UPDATE punto SET
                        nombre_punto = :nombre,
                        direccion = :dir,
                        codigo_1 = :cod1,
                        codigo_2 = :cod2,
                        id_municipio = :mun,
                        id_delegacion = :del,
                        zona = :zona,
                        id_modalidad = :mod,
                        id_cliente = :cli,
                        estado = :est
                        WHERE id_punto = :id""", "nombre", req.trim("nombre_punto"), "dir", req.trim("direccion"),
                    "cod1", req.trim("codigo_1"), "cod2", req.trim("codigo_2"), "mun", req.intv("id_municipio"),
                    "del", Conv.empty(req.str("id_delegacion")) ? null : req.str("id_delegacion"),
                    "zona", Conv.empty(req.trim("zona")) ? null : req.trim("zona"),
                    "mod", req.intv("id_modalidad"), "cli", req.intv("id_cliente"), "est", req.intv("estado"),
                    "id", Conv.toInt(id));
            return Map.of("success", true, "redirect", "puntoVer");
        } catch (Exception e) {
            log.error("Error editar punto: {}", e.getMessage());
            return Map.of("success", false, "errores", List.of("Error al actualizar."));
        }
    }

    @PostMapping("/puntoEliminar")
    public Map<String, Object> puntoEliminar(Req req) {
        String id = req.str("id");
        if (!id.isEmpty() && id.matches("^\\d+$")) {
            db.update("UPDATE punto SET estado = 0 WHERE id_punto = :id", "id", Conv.toInt(id));
        }
        return Map.of("success", true, "redirect", "puntoVer");
    }
}
