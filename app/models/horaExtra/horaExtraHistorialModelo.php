<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class horaExtraHistorialModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerIdTecnicoPorUsuario($idUsuario)
    {
        try {
            $sql = "SELECT id_tecnico FROM tecnico WHERE usuario_id = :id_usuario AND estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? (int) $res['id_tecnico'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function obtenerDatosTecnicoPorUsuario($idUsuario)
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE usuario_id = :id_usuario AND estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerDatosTecnicoPorUsuario: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerHistorialHorasExtraPorTecnico($idTecnico)
    {
        try {
            $sql = "SELECT 
                    rhe.id_registro_he,
                    rhe.fecha_reporte,
                    rhe.hora_inicio,
                    rhe.hora_fin,
                    rhe.total_horas,
                    rhe.justificacion_tecnico,
                    rhe.observacion_supervisor,
                    ea.nombre_estado AS estado_nombre,
                    c.nombre_cliente,
                    p.nombre_punto,
                    (
                        SELECT GROUP_CONCAT(CONCAT(ehe.id_evidencia, '::', ehe.ruta_archivo) SEPARATOR '||')
                        FROM evidencia_horas_extra ehe
                        WHERE ehe.id_registro_he = rhe.id_registro_he
                    ) AS fotos_detalle
                FROM registro_horas_extra rhe
                INNER JOIN estados_aprobacion_he ea ON rhe.id_estado_aprobacion = ea.id_estado
                LEFT JOIN cliente c ON rhe.id_cliente = c.id_cliente
                LEFT JOIN punto p ON rhe.id_punto = p.id_punto
                WHERE rhe.id_tecnico = :id_tecnico
                ORDER BY rhe.fecha_reporte DESC, rhe.id_registro_he DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_tecnico' => $idTecnico]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo historial de horas extra: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerRegistroPorIdYTecnico($idRegistro, $idTecnico)
    {
        try {
            $sql = "SELECT * FROM registro_horas_extra 
                    WHERE id_registro_he = :id_registro AND id_tecnico = :id_tecnico LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_registro' => $idRegistro,
                ':id_tecnico' => $idTecnico
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function actualizarHoraExtraTecnico($idRegistro, $idTecnico, $fecha, $horaInicio, $horaFin, $totalHoras, $justificacion)
    {
        try {
            $sql = "UPDATE registro_horas_extra 
                    SET fecha_reporte = :fecha,
                        hora_inicio = :hora_inicio,
                        hora_fin = :hora_fin,
                        total_horas = :total_horas,
                        justificacion_tecnico = :justificacion,
                        id_estado_aprobacion = 1,
                        observacion_supervisor = NULL
                    WHERE id_registro_he = :id_registro AND id_tecnico = :id_tecnico";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':fecha' => $fecha,
                ':hora_inicio' => $horaInicio,
                ':hora_fin' => $horaFin,
                ':total_horas' => $totalHoras,
                ':justificacion' => $justificacion,
                ':id_registro' => $idRegistro,
                ':id_tecnico' => $idTecnico
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando hora extra: " . $e->getMessage());
            return false;
        }
    }

    public function guardarEvidenciaFoto($idRegistroHE, $rutaArchivo)
    {
        try {
            $sql = "INSERT INTO evidencia_horas_extra (id_registro_he, ruta_archivo) VALUES (:id_registro, :ruta)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_registro' => $idRegistroHE,
                ':ruta' => $rutaArchivo
            ]);
        } catch (PDOException $e) {
            error_log("Error guardarEvidenciaFoto HE: " . $e->getMessage());
            return false;
        }
    }

    // Obtener información de una evidencia específica
    public function obtenerEvidenciaPorId($idEvidencia, $idRegistro)
    {
        try {
            $sql = "SELECT id_evidencia, id_registro_he, ruta_archivo 
                FROM evidencia_horas_extra 
                WHERE id_evidencia = :id_evidencia AND id_registro_he = :id_registro LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_evidencia' => $idEvidencia,
                ':id_registro' => $idRegistro
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // Eliminar evidencia por ID
    public function eliminarEvidenciaFoto($idEvidencia, $idRegistro)
    {
        try {
            $sql = "DELETE FROM evidencia_horas_extra WHERE id_evidencia = :id_evidencia AND id_registro_he = :id_registro";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_evidencia' => $idEvidencia,
                ':id_registro' => $idRegistro
            ]);
        } catch (PDOException $e) {
            error_log("Error eliminarEvidenciaFoto HE: " . $e->getMessage());
            return false;
        }
    }
}