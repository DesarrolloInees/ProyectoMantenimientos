package co.inees.mantenimientos.modulos.turno;

import java.util.List;
import java.util.Map;

import org.springframework.stereotype.Service;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;

/** Port de app/models/turno/turnoModelo.php */
@Service
public class TurnoModelo {

    private final Db db;

    public TurnoModelo(Db db) {
        this.db = db;
    }

    public Map<String, Object> obtenerDatosTecnicoPorUsuario(int idUsuario) {
        try {
            return db.one("""
                    SELECT id_tecnico, nombre_tecnico, codigo_ruta
                    FROM tecnico
                    WHERE usuario_id = :id_usuario AND estado = 1
                    LIMIT 1""", "id_usuario", idUsuario);
        } catch (Exception e) {
            return null;
        }
    }

    public Map<String, Object> obtenerTurnoPorDia(int idTecnico, String fecha) {
        try {
            return db.one("""
                    SELECT id_turno, id_tecnico, fecha, hora_entrada, novedad, latitud, longitud, fecha_registro
                    FROM turno_tecnico
                    WHERE id_tecnico = :id_tecnico AND fecha = :fecha AND estado = 1
                    LIMIT 1""", "id_tecnico", idTecnico, "fecha", fecha);
        } catch (Exception e) {
            return null;
        }
    }

    public boolean guardarEntrada(Map<String, Object> d) {
        try {
            db.update("""
                    INSERT INTO turno_tecnico
                        (id_tecnico, fecha, hora_entrada, novedad, latitud, longitud, estado)
                    VALUES
                        (:id_tecnico, :fecha, :hora_entrada, :novedad, :latitud, :longitud, 1)
                    ON DUPLICATE KEY UPDATE
                        hora_entrada   = VALUES(hora_entrada),
                        novedad        = VALUES(novedad),
                        latitud        = VALUES(latitud),
                        longitud       = VALUES(longitud),
                        fecha_registro = CURRENT_TIMESTAMP,
                        estado         = 1""", d);
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    public List<Map<String, Object>> obtenerTecnicosActivos() {
        try {
            return db.list("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> obtenerTurnos(String fechaInicio, String fechaFin, Object idTecnico) {
        try {
            String sql = """
                    SELECT tt.id_turno,
                           tt.fecha,
                           tt.hora_entrada,
                           tt.novedad,
                           tt.latitud,
                           tt.longitud,
                           tt.fecha_registro,
                           t.nombre_tecnico,
                           t.codigo_ruta
                    FROM turno_tecnico tt
                    INNER JOIN tecnico t ON tt.id_tecnico = t.id_tecnico
                    WHERE tt.estado = 1
                      AND tt.fecha BETWEEN :fecha_inicio AND :fecha_fin""";
            Map<String, Object> p = Db.p("fecha_inicio", fechaInicio, "fecha_fin", fechaFin);
            if (!Conv.empty(idTecnico)) {
                sql += " AND tt.id_tecnico = :id_tecnico";
                p.put("id_tecnico", idTecnico);
            }
            sql += " ORDER BY tt.fecha DESC, t.nombre_tecnico ASC";
            return db.list(sql, p);
        } catch (Exception e) {
            return List.of();
        }
    }
}
