<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

/**
 * Modelo del módulo "Marcar Entrada" (turno_tecnico).
 * Una sola tabla: el técnico registra el día, la hora en que entró,
 * la novedad (texto libre) y la ubicación desde donde marcó.
 */
class turnoModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /** Trae el id del técnico vinculado al usuario logueado. */
    public function obtenerDatosTecnicoPorUsuario($idUsuario)
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico, codigo_ruta
                    FROM tecnico
                    WHERE usuario_id = :id_usuario AND estado = 1
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerDatosTecnicoPorUsuario (turno): " . $e->getMessage());
            return false;
        }
    }

    /** Registro de turno de un técnico en una fecha exacta (o null si no existe). */
    public function obtenerTurnoPorDia($idTecnico, $fecha)
    {
        try {
            $sql = "SELECT id_turno, id_tecnico, fecha, hora_entrada, novedad, latitud, longitud, fecha_registro
                    FROM turno_tecnico
                    WHERE id_tecnico = :id_tecnico AND fecha = :fecha AND estado = 1
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_tecnico' => $idTecnico,
                ':fecha'      => $fecha
            ]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            return $fila ?: null;
        } catch (PDOException $e) {
            error_log("Error obtenerTurnoPorDia: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Guarda (o actualiza) la entrada del día.
     * Gracias al UNIQUE (id_tecnico, fecha) el ON DUPLICATE evita duplicados:
     * si el técnico vuelve a marcar el mismo día, se corrige el registro.
     */
    public function guardarEntrada($datos)
    {
        try {
            $sql = "INSERT INTO turno_tecnico
                        (id_tecnico, fecha, hora_entrada, novedad, latitud, longitud, estado)
                    VALUES
                        (:id_tecnico, :fecha, :hora_entrada, :novedad, :latitud, :longitud, 1)
                    ON DUPLICATE KEY UPDATE
                        hora_entrada   = VALUES(hora_entrada),
                        novedad        = VALUES(novedad),
                        latitud        = VALUES(latitud),
                        longitud       = VALUES(longitud),
                        fecha_registro = CURRENT_TIMESTAMP,
                        estado         = 1";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_tecnico'   => $datos['id_tecnico'],
                ':fecha'        => $datos['fecha'],
                ':hora_entrada' => $datos['hora_entrada'],
                ':novedad'      => $datos['novedad'],
                ':latitud'      => $datos['latitud'],
                ':longitud'     => $datos['longitud']
            ]);
        } catch (PDOException $e) {
            error_log("Error guardarEntrada (turno): " . $e->getMessage());
            return false;
        }
    }

    /** Técnicos activos para el filtro del reporte. */
    public function obtenerTecnicosActivos()
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico
                    FROM tecnico
                    WHERE estado = 1
                    ORDER BY nombre_tecnico ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Registros para el reporte / Excel / PDF. */
    public function obtenerTurnos($fechaInicio, $fechaFin, $idTecnico = null)
    {
        try {
            $sql = "SELECT tt.id_turno,
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
                      AND tt.fecha BETWEEN :fecha_inicio AND :fecha_fin";

            $params = [
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin'    => $fechaFin
            ];

            if (!empty($idTecnico)) {
                $sql .= " AND tt.id_tecnico = :id_tecnico";
                $params[':id_tecnico'] = $idTecnico;
            }

            $sql .= " ORDER BY tt.fecha DESC, t.nombre_tecnico ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerTurnos: " . $e->getMessage());
            return [];
        }
    }
}
