package co.inees.mantenimientos.modulos.inicio;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.ResumenTiposServicio;
import co.inees.mantenimientos.core.Sesion;
import co.inees.mantenimientos.modulos.turno.TurnoModelo;

/** Port de inicioControlador.php + inicioModelo.php */
@RestController
@RequestMapping("/api/inicio")
public class InicioController {

    private final Db db;
    private final TurnoModelo turnoModelo;

    public InicioController(Db db, TurnoModelo turnoModelo) {
        this.db = db;
        this.turnoModelo = turnoModelo;
    }

    @GetMapping(params = "accion=index")
    public Map<String, Object> index(Sesion s) {
        int nivel = s.nivel();
        Map<String, Object> r = new LinkedHashMap<>();
        Map<String, Object> turnoHoy = null;

        Map<String, Object> estadisticas = new LinkedHashMap<>();
        if (nivel == 1 || nivel == 2) {
            estadisticas.put("ordenes_mes", totalOrdenesMes());
            estadisticas.put("clientes", total("SELECT COUNT(*) as total FROM cliente WHERE estado = 1"));
            estadisticas.put("tecnicos", total("SELECT COUNT(*) as total FROM tecnico WHERE estado = 1"));
        }

        Map<String, Object> resumen = ResumenTiposServicio.vacio();
        Map<String, Object> rango = Map.of("inicio", F.primeroMes(), "fin", F.hoy());
        if (nivel == 3) {
            int idTecnico = obtenerIdTecnicoPorUsuario(s.usuarioId());
            if (idTecnico > 0) {
                Map<String, Object> t = turnoModelo.obtenerTurnoPorDia(idTecnico, F.hoy());
                if (t != null) {
                    turnoHoy = new LinkedHashMap<>();
                    turnoHoy.put("fecha", t.get("fecha"));
                    turnoHoy.put("fecha_texto", F.dmy(t.get("fecha")));
                    turnoHoy.put("hora_entrada", Conv.str(t.get("hora_entrada")).substring(0, 5));
                    turnoHoy.put("novedad", Conv.str(t.get("novedad")));
                }
                List<Map<String, Object>> filas = resumenServiciosPorTipo(idTecnico, F.primeroMes(), F.hoy());
                List<Map<String, Object>> exp = new ArrayList<>();
                for (Map<String, Object> f : filas) {
                    int n = Conv.toInt(f.get("total"));
                    for (int i = 0; i < n; i++) exp.add(Map.of("tipo_mantenimiento", Conv.str(f.get("tipo_mantenimiento"))));
                }
                resumen = ResumenTiposServicio.resumir(exp);
            }
        }

        // $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario'  (mismas claves que PHP)
        Object nombre = s.get("nombre") != null ? s.get("nombre") : (s.get("usuario") != null ? s.get("usuario") : "Usuario");
        Map<String, Object> usuario = new LinkedHashMap<>();
        usuario.put("nombre", nombre);
        usuario.put("rol_id", nivel);
        usuario.put("rol_nombre", rolNombre(nivel));

        LocalDateTime ahora = LocalDateTime.now();
        r.put("titulo", "Inicio");
        r.put("estadisticas", estadisticas);
        r.put("resumenTecnicoMes", resumen);
        r.put("rangoTecnicoMes", rango);
        r.put("turnoHoy", turnoHoy);
        r.put("usuario", usuario);
        // date('d \d\e F, Y') -> mes en inglés, igual que PHP
        r.put("fechaHoy", ahora.format(DateTimeFormatter.ofPattern("dd 'de' MMMM, yyyy", Locale.ENGLISH)));
        r.put("horaActual", ahora.format(DateTimeFormatter.ofPattern("HH:mm")));
        r.put("inicioMes", ahora.format(DateTimeFormatter.ofPattern("01/MM/yyyy")));
        r.put("hoyDmy", ahora.format(DateTimeFormatter.ofPattern("dd/MM/yyyy")));
        return r;
    }

    private int totalOrdenesMes() {
        try {
            return (int) db.scalarLong("""
                    SELECT COUNT(*) as total
                    FROM ordenes_servicio
                    WHERE fecha_visita BETWEEN :inicio AND :fin
                    AND estado = 1""", "inicio", F.primeroMes(), "fin", F.ultimoMes());
        } catch (Exception e) {
            return 0;
        }
    }

    private int total(String sql) {
        try {
            return (int) db.scalarLong(sql);
        } catch (Exception e) {
            return 0;
        }
    }

    private int obtenerIdTecnicoPorUsuario(int usuarioId) {
        if (usuarioId == 0) return 0;
        try {
            return (int) db.scalarLong("SELECT id_tecnico FROM tecnico WHERE usuario_id = :id_usuario LIMIT 1", "id_usuario", usuarioId);
        } catch (Exception e) {
            return 0;
        }
    }

    private List<Map<String, Object>> resumenServiciosPorTipo(int idTecnico, String inicio, String fin) {
        try {
            return db.list("""
                    SELECT tm.nombre_completo AS tipo_mantenimiento, COUNT(*) AS total
                    FROM ordenes_servicio os
                    LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.id_tecnico = :id_tecnico
                    AND os.fecha_visita BETWEEN :inicio AND :fin
                    AND (tm.nombre_completo IS NULL
                         OR (UPPER(tm.nombre_completo) NOT LIKE '%INSTALACIÓN%'
                             AND UPPER(tm.nombre_completo) NOT LIKE '%INSTALACION%'))
                    GROUP BY tm.nombre_completo""", "id_tecnico", idTecnico, "inicio", inicio, "fin", fin);
        } catch (Exception e) {
            return List.of();
        }
    }

    private static String rolNombre(int nivel) {
        return switch (nivel) {
            case 1 -> "Super Administrador";
            case 2 -> "Administrador";
            case 3 -> "Técnico de Campo";
            case 4 -> "Funcionario Prosegur";
            case 5 -> "Supervisor Motorizados";
            default -> "Usuario";
        };
    }
}
