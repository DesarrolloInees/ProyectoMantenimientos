package co.inees.mantenimientos.modulos.admin;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Req;

/** Port de cliente{Ver,Crear,Editar,Eliminar}Controlador + modelos. */
@RestController
@RequestMapping("/api")
public class ClienteController {

    private final Db db;

    public ClienteController(Db db) {
        this.db = db;
    }

    @GetMapping("/clienteVer")
    public Map<String, Object> ver() {
        try {
            return Map.of("clientes", db.list("SELECT * FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC"));
        } catch (Exception e) {
            return Map.of("clientes", List.of());
        }
    }

    @PostMapping("/clienteCrear")
    public Map<String, Object> crear(Req req) {
        String nombre = req.trim("nombre_cliente");
        String codigo = req.trim("codigo_cliente");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(nombre)) errores.add("El nombre del cliente es obligatorio.");
        if (errores.isEmpty()) {
            try {
                db.update("""
                        INSERT INTO cliente (
                            nombre_cliente,
                            codigo_cliente,
                            estado
                        ) VALUES (
                            :nombre,
                            :codigo,
                            1
                        )""", "nombre", nombre, "codigo", Conv.empty(codigo) ? null : codigo);
                return Map.of("success", true, "redirect", "clienteVer");
            } catch (Exception e) {
                errores.add("Error al guardar. Verifica que el Código del Cliente no esté repetido.");
            }
        }
        return Map.of("success", false, "errores", errores);
    }

    @GetMapping("/clienteEditar")
    public Map<String, Object> obtener(Req req) {
        String id = req.str("id");
        if (id.isEmpty()) return Map.of("redirect", "clienteVer");
        Map<String, Object> d = null;
        try {
            d = db.one("SELECT * FROM cliente WHERE id_cliente = :id", "id", Conv.toInt(id));
        } catch (Exception ignored) {
        }
        if (d == null) return Map.of("redirect", "clienteVer");
        return Map.of("datos", d);
    }

    @PostMapping("/clienteEditar")
    public Map<String, Object> editar(Req req) {
        String id = req.str("id", req.str("id_cliente"));
        if (id.isEmpty()) return Map.of("success", false, "redirect", "clienteVer");
        String nombre = req.trim("nombre_cliente");
        String codigo = req.trim("codigo_cliente");
        String estado = req.str("estado", "1");
        List<String> errores = new ArrayList<>();
        if (Conv.empty(nombre)) errores.add("El nombre es obligatorio.");
        if (errores.isEmpty()) {
            try {
                db.update("""
                        UPDATE cliente SET
                            nombre_cliente = :nombre,
                            codigo_cliente = :codigo,
                            estado = :estado
                        WHERE
                            id_cliente = :id""", "nombre", nombre, "codigo", Conv.empty(codigo) ? null : codigo,
                        "estado", Conv.toInt(estado), "id", Conv.toInt(id));
                return Map.of("success", true, "redirect", "clienteVer");
            } catch (Exception e) {
                errores.add("Error al actualizar. Verifica que el código no pertenezca a otro cliente.");
            }
        }
        Map<String, Object> datos = new LinkedHashMap<>();
        datos.put("nombre_cliente", nombre);
        datos.put("codigo_cliente", codigo);
        datos.put("estado", estado);
        return Map.of("success", false, "errores", errores, "datos", datos);
    }

    @PostMapping("/clienteEliminar")
    public Map<String, Object> eliminar(Req req) {
        String id = req.str("id");
        if (!id.isEmpty() && id.matches("^\\d+$")) {
            try {
                db.update("UPDATE cliente SET estado = 0 WHERE id_cliente = :id", "id", Conv.toInt(id));
            } catch (Exception ignored) {
            }
        }
        return Map.of("success", true, "redirect", "clienteVer");
    }
}
