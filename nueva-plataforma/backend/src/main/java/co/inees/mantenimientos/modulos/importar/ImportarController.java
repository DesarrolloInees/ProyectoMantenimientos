package co.inees.mantenimientos.modulos.importar;

import java.util.ArrayList;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.ExcelLector;
import co.inees.mantenimientos.core.Req;
import co.inees.mantenimientos.core.Sesion;
import tools.jackson.databind.json.JsonMapper;

/**
 * Port de importarExcelControlador.php, importarEstadoMaquinaControlador.php e importarMunicipiosControlador.php
 * (con sus modelos). Las filas del Excel se guardan en la sesión en lugar de releer el archivo temporal en cada lote.
 */
@RestController
public class ImportarController {

    private static final Logger log = LoggerFactory.getLogger(ImportarController.class);
    private static final JsonMapper JSON = JsonMapper.builder().build();
    private static final String SES_EXCEL = "importar_excel_filas";
    private static final String SES_ESTADO = "lista_device_ids_estado";
    private static final Map<String, String> MAPA_TIPOS = Map.of("MINI MEI", "Mini Mei", "SDM-10", "SDM 10", "JH-600", "JH 600");
    private static final Map<String, String> CORRECCIONES = new LinkedHashMap<>();
    static {
        String[][] c = { { "MACH 6", "MATCH-6" }, { "MACH6", "MATCH-6" }, { "SDM 500", "SDM-500" }, { "SDM500", "SDM-500" }, { "SDM 100", "SDM-100" },
                { "SDM100", "SDM-100" }, { "SDM 10", "SDM-10" }, { "SDM10", "SDM-10" }, { "SDM 10 XS", "SDM-10-XS" }, { "SDM 10XS", "SDM-10-XS" },
                { "SDM10 XS", "SDM-10-XS" }, { "SDM-10XS", "SDM-10-XS" }, { "SDM- 10X", "SDM-10-XS" }, { "SDM 10-XS", "SDM-10-XS" }, { "SDM-10 XS", "SDM-10-XS" },
                { "KD-30 SDM 500", "KD30-SDM500" }, { "KD30 SDM 500", "KD30-SDM500" }, { "KD30-500", "KD30-SDM500" }, { "KD-30-SDM500", "KD30-SDM500" },
                { "KD30-SDM 100", "KD30-SDM100" }, { "KD30 SDM 100", "KD30-SDM100" }, { "KD30-100", "KD30-SDM100" }, { "JH 600", "JH-600" }, { "JH600", "JH-600" },
                { "JH 6000", "JH-6000" }, { "JH6000", "JH-6000" }, { "MINI MEI", "MINI MEI" }, { "MINIMEI", "MINI MEI" }, { "PS3000", "PS-3000" },
                { "SNBC", "PRO-EFECTIVO-X" }, { "SNBC-SDM500", "PRO-EFECTIVO-X" }, { "PRO EFECTIVO X", "PRO-EFECTIVO-X" }, { "PROEFECTIVO X", "PRO-EFECTIVO-X" },
                { "DF 4", "DF4" }, { "LOGY SAFE", "LOGY-SAFE" } };
        for (String[] x : c) CORRECCIONES.put(x[0], x[1]);
        CORRECCIONES.put("0", "");
    }

    private final Db db;

    public ImportarController(Db db) {
        this.db = db;
    }

    private static String c(Map<String, String> fila, String col) {
        String v = fila.get(col);
        return v == null ? "" : v;
    }

    @SuppressWarnings("unchecked")
    private static List<Map<String, String>> filasSesion(Sesion s, String clave) {
        Object o = s.get(clave);
        return o instanceof List<?> l ? (List<Map<String, String>>) l : null;
    }

    private static List<String> aprobados(Req req) {
        try {
            String j = req.str("aprobados", "");
            if (j.isEmpty()) return List.of();
            List<?> l = JSON.readValue(j, List.class);
            List<String> r = new ArrayList<>();
            for (Object x : l) r.add(Conv.str(x));
            return r;
        } catch (Exception e) {
            return List.of();
        }
    }

    // ================================================================ IMPORTAR EXCEL (C2D)

    @GetMapping(value = "/api/importarExcel", params = "accion=index")
    public Map<String, Object> excelIndex() {
        return Map.of("titulo", "Importación Masiva de Instalaciones");
    }

    @PostMapping(value = "/api/importarExcel", params = "accion=subirArchivo")
    public Map<String, Object> excelSubir(Req req) {
        MultipartFile f = req.file("archivo_excel");
        if (f == null || f.isEmpty()) return Map.of("exito", false, "error", "No se pudo mover el archivo temporal.");
        try (var in = f.getInputStream()) {
            var filas = ExcelLector.leer(in, "INSTALADAS CT");
            req.sesion().set(SES_EXCEL, filas);
            return Map.of("exito", true, "total_filas", filas.size(), "mensaje", "Archivo cargado. Iniciando procesamiento...");
        } catch (Exception e) {
            return Map.of("exito", false, "error", String.valueOf(e.getMessage()));
        }
    }

    @PostMapping(value = "/api/importarExcel", params = "accion=procesarLote")
    public Map<String, Object> excelLote(Req req) {
        int inicio = req.intv("inicio", 2), cantidad = req.intv("cantidad", 200);
        String modo = req.str("modo", "simular");
        var todas = filasSesion(req.sesion(), SES_EXCEL);
        if (todas == null) return Map.of("exito", false, "error", "No hay datos cargados. Vuelve a subir el archivo.");
        Map<String, Integer> stats = new LinkedHashMap<>(Map.of("insertados", 0, "actualizados", 0, "errores", 0));
        List<Map<String, Object>> detalles = new ArrayList<>();
        int conDatos = 0, procesados = 0;
        for (int numFila = Math.max(2, inicio); numFila < inicio + cantidad && numFila <= todas.size(); numFila++) {
            procesados++;
            var fila = todas.get(numFila - 1);
            String cliente = c(fila, "A").trim(), device = c(fila, "C").trim();
            if (cliente.isEmpty() && device.isEmpty()) continue;
            conDatos++;
            String codCliente = c(fila, "A"), nombreCliente = c(fila, "B").trim(), cod1 = c(fila, "D"), cod2 = c(fila, "E");
            String nombrePunto = c(fila, "F").trim(), delegacion = c(fila, "I"), tipoMaquina = c(fila, "K"), direccion = c(fila, "AK");
            if (device.isEmpty()) continue;
            if (modo.equals("simular")) {
                Map<String, Object> actual = null;
                try {
                    actual = db.one("""
                            SELECT c.nombre_cliente, p.nombre_punto
                            FROM maquina m
                            INNER JOIN punto p ON m.id_punto = p.id_punto
                            INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                            WHERE m.device_id = :id LIMIT 1""", "id", device);
                } catch (Exception ignored) {
                }
                String acc = actual != null ? "ACTUALIZAR" : "NUEVO";
                Map<String, Object> it = new LinkedHashMap<>();
                it.put("device", device);
                it.put("cliente", nombreCliente);
                it.put("punto", nombrePunto);
                it.put("estado", acc);
                if (actual != null) {
                    it.put("cliente_antiguo", actual.get("nombre_cliente") == null ? "Desconocido" : actual.get("nombre_cliente"));
                    it.put("punto_antiguo", actual.get("nombre_punto") == null ? "Desconocido" : actual.get("nombre_punto"));
                }
                detalles.add(it);
                continue;
            }
            // Igual que el PHP: en modo importar se procesan todas las filas del lote (la lista de aprobados no se usa)
            try {
                String resultado = db.tx(() -> {
                    Object idCliente = gestionarCliente(nombreCliente, codCliente);
                    Object idDel = idDelegacion(delegacion);
                    Object idPunto = gestionarPunto(nombrePunto, idCliente, cod1, cod2, idDel, direccion);
                    db.update("UPDATE punto SET fecha_actualizacion = NOW(), estado = 1 WHERE id_punto = :id", "id", idPunto);
                    String tipoFinal = MAPA_TIPOS.getOrDefault(tipoMaquina.trim(), tipoMaquina);
                    Object idTipo = idTipoMaquina(tipoFinal);
                    long existe = db.scalarLong("SELECT COUNT(*) as total FROM maquina WHERE device_id = :id", "id", device);
                    if (existe > 0) {
                        db.update("""
                                UPDATE maquina
                                SET id_punto = :pto,
                                    id_tipo_maquina = :tipo,
                                    fecha_actualizacion = NOW(),
                                    estado = 1
                                WHERE device_id = :dev""", "pto", idPunto, "tipo", idTipo, "dev", device);
                        db.update("UPDATE maquina SET fecha_actualizacion = NOW(), estado = 1 WHERE device_id = :dev", "dev", device);
                        return "act";
                    }
                    db.update("INSERT INTO maquina (device_id, id_punto, id_tipo_maquina, estado) VALUES (:dev, :pto, :tipo, 1)", "dev", device, "pto", idPunto, "tipo", idTipo);
                    db.update("UPDATE maquina SET fecha_actualizacion = NOW(), estado = 1 WHERE device_id = :dev", "dev", device);
                    return "ins";
                });
                stats.merge(resultado.equals("act") ? "actualizados" : "insertados", 1, Integer::sum);
            } catch (Exception e) {
                log.warn("Importar fila {}: {}", numFila, e.getMessage());
                stats.merge("errores", 1, Integer::sum);
            }
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("exito", true);
        r.put("procesados", procesados);
        r.put("stats", stats);
        r.put("detalles", detalles);
        r.put("detener", conDatos == 0);
        return r;
    }

    private Object gestionarCliente(String nombre, String codigo) {
        if (!Conv.empty(codigo)) {
            var r = db.one("SELECT id_cliente FROM cliente WHERE codigo_cliente = :cod LIMIT 1", "cod", codigo);
            if (r != null) return r.get("id_cliente");
        }
        var r = db.one("SELECT id_cliente FROM cliente WHERE nombre_cliente = :nom LIMIT 1", "nom", nombre.trim());
        if (r != null) return r.get("id_cliente");
        return db.insert("INSERT INTO cliente (nombre_cliente, codigo_cliente, estado) VALUES (:nom, :cod, 1)", "nom", nombre.trim(), "cod", Conv.empty(codigo) ? null : codigo);
    }

    private Object gestionarPunto(String nombrePunto, Object idCliente, String cod1, String cod2, Object idDel, String direccion) {
        String nom = nombrePunto.trim(), c1 = cod1.trim();
        if (!c1.isEmpty()) {
            var r = db.one("SELECT id_punto, nombre_punto FROM punto WHERE codigo_1 = :c1 AND id_cliente = :cli LIMIT 1", "c1", c1, "cli", idCliente);
            if (r != null) {
                db.update("""
                        UPDATE punto
                        SET nombre_punto = :nom,
                            id_delegacion = :del,
                            estado = 1
                        WHERE id_punto = :id""", "nom", nom, "del", idDel, "id", r.get("id_punto"));
                return r.get("id_punto");
            }
        }
        var r = db.one("SELECT id_punto FROM punto WHERE nombre_punto = :nom AND id_cliente = :cli LIMIT 1", "nom", nom, "cli", idCliente);
        if (r != null) return r.get("id_punto");
        return db.insert("""
                INSERT INTO punto (nombre_punto, codigo_1, codigo_2, id_municipio, id_delegacion, id_cliente, direccion, estado, id_modalidad)
                VALUES (:nom, :c1, :c2, :mun, :del, :cli, :dir, 1, 1)""",
                "nom", nom, "c1", c1, "c2", cod2, "mun", 1, "del", idDel, "cli", idCliente, "dir", direccion.trim());
    }

    private Object idDelegacion(String nombre) {
        try {
            var r = db.one("SELECT id_delegacion FROM delegacion WHERE nombre_delegacion LIKE :nom LIMIT 1", "nom", nombre.trim().toUpperCase());
            return r == null ? null : r.get("id_delegacion");
        } catch (Exception e) {
            return null;
        }
    }

    private Object idTipoMaquina(String nombreTipo) {
        try {
            String n = nombreTipo.toUpperCase().trim();
            if (CORRECCIONES.containsKey(n)) n = CORRECCIONES.get(n);
            var r = db.one("SELECT id_tipo_maquina FROM tipo_maquina WHERE nombre_tipo_maquina = :nom LIMIT 1", "nom", n);
            if (r != null) return r.get("id_tipo_maquina");
            return db.insert("INSERT INTO tipo_maquina (nombre_tipo_maquina, estado) VALUES (:nom, 1)", "nom", n);
        } catch (Exception e) {
            return 1;
        }
    }

    @PostMapping(value = "/api/importarExcel", params = "accion=finalizarImportacion")
    public Map<String, Object> excelFinalizar(Req req) {
        String modo = req.str("modo", "simular");
        if (modo.equals("importar")) {
            String fecha = req.str("fecha_inicio", "");
            Map<String, Object> bajas = new LinkedHashMap<>(Map.of("maquinas", 0, "puntos", 0));
            try {
                bajas.put("maquinas", db.update("UPDATE maquina SET estado = 0 WHERE estado = 1 AND (fecha_actualizacion < :fecha OR fecha_actualizacion IS NULL)", "fecha", fecha));
                bajas.put("puntos", db.update("UPDATE punto SET estado = 0 WHERE estado = 1 AND (fecha_actualizacion < :fecha OR fecha_actualizacion IS NULL)", "fecha", fecha));
            } catch (Exception e) {
                log.error("desactivarFantasmas: {}", e.getMessage());
            }
            req.sesion().remove(SES_EXCEL);
            return Map.of("exito", true, "mensaje", "Importación finalizada", "bajas", bajas);
        }
        List<String> devices = new ArrayList<>();
        try {
            String j = req.str("devices_excel", "");
            if (!j.isEmpty()) for (Object x : JSON.readValue(j, List.class)) devices.add(Conv.str(x));
        } catch (Exception ignored) {
        }
        List<Map<String, Object>> fantasmas;
        try {
            String base = """
                    SELECT m.device_id, c.nombre_cliente, p.nombre_punto
                    FROM maquina m
                    INNER JOIN punto p ON m.id_punto = p.id_punto
                    INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                    WHERE m.estado = 1""";
            if (devices.isEmpty()) {
                fantasmas = db.list(base);
            } else {
                Set<String> enExcel = new HashSet<>(devices);
                fantasmas = new ArrayList<>();
                for (var m : db.list(base)) if (!enExcel.contains(Conv.str(m.get("device_id")))) fantasmas.add(m);
            }
        } catch (Exception e) {
            fantasmas = List.of();
        }
        return Map.of("exito", true, "mensaje", "Simulación finalizada.", "fantasmas", fantasmas);
    }

    // ================================================================ IMPORTAR ESTADO DE MÁQUINA

    @GetMapping(value = "/api/importarEstadoMaquina", params = "accion=index")
    public Map<String, Object> estadoIndex() {
        return Map.of("titulo", "Importar Estado de Maquinas");
    }

    @PostMapping(value = "/api/importarEstadoMaquina", params = "accion=subirArchivo")
    public Map<String, Object> estadoSubir(Req req) {
        MultipartFile f = req.file("archivo_excel");
        if (f == null || f.isEmpty()) return Map.of("exito", false, "error", "Error al subir el archivo.");
        try (var in = f.getInputStream()) {
            var filas = ExcelLector.leer(in, null);
            List<String> ids = new ArrayList<>();
            for (int i = 1; i < filas.size(); i++) {
                String d = c(filas.get(i), "A").trim();
                if (!d.isEmpty()) ids.add(d.length() < 12 ? "0".repeat(12 - d.length()) + d : d);
            }
            if (ids.isEmpty()) return Map.of("exito", false, "error", "El archivo no contiene Device ID en la columna A.");
            req.sesion().set(SES_ESTADO, ids);
            req.sesion().set("total_device_ids_estado", ids.size());
            return Map.of("exito", true, "total_filas", ids.size(), "mensaje", "Archivo cargado con " + ids.size() + " Device IDs. Iniciando analisis...");
        } catch (Exception e) {
            return Map.of("exito", false, "error", String.valueOf(e.getMessage()));
        }
    }

    @SuppressWarnings("unchecked")
    @PostMapping(value = "/api/importarEstadoMaquina", params = "accion=procesarLote")
    public Map<String, Object> estadoLote(Req req) {
        int inicio = req.intv("inicio", 2), cantidad = req.intv("cantidad", 200);
        String modo = req.str("modo", "simular");
        List<String> aprobados = aprobados(req);
        Object o = req.sesion().get(SES_ESTADO);
        List<String> ids = o instanceof List<?> ? (List<String>) o : List.of();
        if (ids.isEmpty()) return Map.of("exito", false, "error", "No hay datos cargados. Vuelve a subir el archivo.");
        int desde = Math.max(0, inicio - 2);
        List<String> lote = ids.subList(Math.min(desde, ids.size()), Math.min(desde + cantidad, ids.size()));
        Map<String, Integer> stats = new LinkedHashMap<>(Map.of("marcados", 0, "errores", 0, "no_encontrados", 0));
        List<Map<String, Object>> detalles = new ArrayList<>();
        for (String d0 : lote) {
            String dev = d0.trim();
            if (dev.isEmpty()) continue;
            if (modo.equals("simular")) {
                Map<String, Object> m = null;
                try {
                    m = db.one("""
                            SELECT m.id_maquina, m.device_id, m.activo_operativo,
                                p.id_punto, p.nombre_punto, p.zona,
                                c.nombre_cliente,
                                tm.nombre_tipo_maquina
                            FROM maquina m
                            INNER JOIN punto p ON m.id_punto = p.id_punto
                            INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                            INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                            WHERE m.device_id = :id AND m.estado = 1 LIMIT 1""", "id", dev);
                } catch (Exception ignored) {
                }
                Map<String, Object> it = new LinkedHashMap<>();
                it.put("device", dev);
                if (m != null) {
                    boolean ya = Conv.toInt(m.get("activo_operativo")) == 0;
                    it.put("cliente", m.get("nombre_cliente"));
                    it.put("punto", m.get("nombre_punto"));
                    it.put("tipo_maquina", m.get("nombre_tipo_maquina"));
                    it.put("zona", m.get("zona") == null ? "" : m.get("zona"));
                    it.put("estado_actual", Conv.toInt(m.get("activo_operativo")) == 1 ? "Operativo" : "Inactivo");
                    it.put("estado", ya ? "YA_INACTIVA" : "MARCAR_INACTIVO");
                } else {
                    it.put("cliente", "---");
                    it.put("punto", "---");
                    it.put("tipo_maquina", "---");
                    it.put("zona", "");
                    it.put("estado_actual", "---");
                    it.put("estado", "NO_ENCONTRADO");
                }
                detalles.add(it);
                continue;
            }
            if (aprobados.contains(dev)) {
                int n;
                try {
                    n = db.update("UPDATE maquina SET activo_operativo = 0, fecha_actualizacion = NOW() WHERE device_id = :dev AND estado = 1", "dev", dev);
                } catch (Exception e) {
                    n = 0;
                }
                stats.merge(n > 0 ? "marcados" : "errores", 1, Integer::sum);
            }
        }
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("exito", true);
        r.put("procesados", lote.size());
        r.put("stats", stats);
        r.put("detalles", detalles);
        r.put("detener", desde + lote.size() >= ids.size());
        return r;
    }

    @PostMapping(value = "/api/importarEstadoMaquina", params = "accion=finalizarImportacion")
    public Map<String, Object> estadoFinalizar(Req req) {
        if ("importar".equals(req.str("modo", "simular"))) {
            req.sesion().remove(SES_ESTADO);
            req.sesion().remove("total_device_ids_estado");
        }
        return Map.of("exito", true, "mensaje", "Fase finalizada.");
    }

    // ================================================================ IMPORTAR MUNICIPIOS (ZONAS)

    @GetMapping(value = "/api/importarMunicipios", params = "accion=index")
    public Map<String, Object> municipiosIndex() {
        return Map.of("titulo", "Actualización Masiva de Municipios");
    }

    @PostMapping(value = "/api/importarMunicipios", params = "accion=generarSimulacion")
    public Map<String, Object> municipiosSimular(Req req) {
        MultipartFile f = req.file("archivo_excel");
        if (f == null || f.isEmpty()) return Map.of("exito", false, "error", "Error al mover archivo temporal.");
        try (var in = f.getInputStream()) {
            var filas = ExcelLector.leer(in, null);
            List<Map<String, Object>> sim = new ArrayList<>();
            Map<String, Integer> stats = new LinkedHashMap<>(Map.of("leidos", 0, "con_cambios", 0, "no_encontrados", 0));
            List<String> basura = List.of(".", "-", "_", "*", "N/A", "na", "?");
            for (int i = 1; i < filas.size(); i++) {
                var fila = filas.get(i);
                String dev = c(fila, "C").trim(), zona = c(fila, "F").trim(), del = c(fila, "E").trim();
                if (dev.isEmpty()) continue;
                stats.merge("leidos", 1, Integer::sum);
                if (zona.isEmpty() || basura.contains(zona) || zona.getBytes(java.nio.charset.StandardCharsets.UTF_8).length < 2) zona = del;
                if (zona.isEmpty()) continue;
                Map<String, Object> act = null;
                try {
                    act = db.one("""
                            SELECT p.id_punto, p.nombre_punto, p.zona as zona_actual
                            FROM maquina m
                            INNER JOIN punto p ON m.id_punto = p.id_punto
                            WHERE m.device_id = :dev LIMIT 1""", "dev", dev);
                } catch (Exception ignored) {
                }
                if (act != null) {
                    String zonaActual = act.get("zona_actual") == null ? "SIN ZONA" : Conv.str(act.get("zona_actual"));
                    String nueva = zona.toUpperCase();
                    Map<String, Object> it = new LinkedHashMap<>();
                    it.put("id_punto", act.get("id_punto"));
                    it.put("device_id", dev);
                    it.put("nombre_punto", act.get("nombre_punto"));
                    it.put("zona_actual", zonaActual);
                    it.put("zona_nueva", nueva);
                    it.put("es_diferente", !zonaActual.equals(nueva));
                    sim.add(it);
                    stats.merge("con_cambios", 1, Integer::sum);
                } else {
                    stats.merge("no_encontrados", 1, Integer::sum);
                }
            }
            return Map.of("exito", true, "datos", sim, "stats", stats);
        } catch (Exception e) {
            return Map.of("exito", false, "error", String.valueOf(e.getMessage()));
        }
    }

    @PostMapping(value = "/api/importarMunicipios", params = "accion=ejecutarActualizacion")
    public Map<String, Object> municipiosEjecutar(Req req) {
        List<?> datos;
        try {
            datos = JSON.readValue(req.str("datos_seleccionados", "[]"), List.class);
        } catch (Exception e) {
            datos = List.of();
        }
        if (datos.isEmpty()) return Map.of("exito", false, "error", "No se recibieron datos para actualizar.");
        int ok = 0, err = 0;
        for (Object o : datos) {
            Map<?, ?> it = (Map<?, ?>) o;
            int id = Conv.toInt(it.get("id_punto"));
            String zona = Conv.str(it.get("zona_nueva"));
            if (id > 0) {
                try {
                    db.update("UPDATE punto SET zona = :zon, fecha_actualizacion = NOW() WHERE id_punto = :id", "zon", zona.isEmpty() ? null : zona.toUpperCase(), "id", id);
                    ok++;
                } catch (Exception e) {
                    err++;
                }
            }
        }
        return Map.of("exito", true, "mensaje", "Proceso completado. Actualizados: " + ok + ", Errores: " + err + ".");
    }
}
