<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class horaExtraCrearModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
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

    public function obtenerClientesActivos()
    {
        try {
            $sql = "SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerPuntosActivos()
    {
        try {
            $sql = "SELECT id_punto, nombre_punto, id_cliente FROM punto WHERE estado = 1 ORDER BY nombre_punto ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function guardarHoraExtra($datos)
    {
        try {
            $sql = "INSERT INTO registro_horas_extra 
                    (id_tecnico, id_cliente, id_punto, fecha_reporte, hora_inicio, hora_fin, total_horas, justificacion_tecnico, id_estado_aprobacion) 
                    VALUES (:id_tecnico, :id_cliente, :id_punto, :fecha, :inicio, :fin, :total, :justificacion, 1)";
            
            $stmt = $this->conn->prepare($sql);
            $exito = $stmt->execute([
                ':id_tecnico' => $datos['id_tecnico'],
                ':id_cliente' => $datos['id_cliente'],
                ':id_punto'   => $datos['id_punto'],
                ':fecha'      => $datos['fecha_reporte'],
                ':inicio'     => $datos['hora_inicio'],
                ':fin'        => $datos['hora_fin'],
                ':total'      => $datos['total_horas'],
                ':justificacion' => $datos['justificacion_tecnico']
            ]);

            if ($exito) {
                return (int) $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error guardarHoraExtra: " . $e->getMessage());
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
                ':ruta'        => $rutaArchivo
            ]);
        } catch (PDOException $e) {
            error_log("Error guardarEvidenciaFoto HE: " . $e->getMessage());
            return false;
        }
    }
}