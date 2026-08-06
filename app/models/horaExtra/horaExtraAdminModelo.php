<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class horaExtraAdminModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Obtener la lista de todos los técnicos activos para los filtros
    public function obtenerTecnicos()
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo técnicos: " . $e->getMessage());
            return [];
        }
    }

    // Obtener todos los estados de aprobación disponibles
    public function obtenerEstadosAprobacion()
    {
        try {
            $sql = "SELECT id_estado, nombre_estado FROM estados_aprobacion_he ORDER BY id_estado ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Obtener registros con filtros dinámicos
    public function obtenerHorasExtraAdmin($fechaInicio = null, $fechaFin = null, $idTecnico = null, $idEstado = null)
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
                        rhe.fecha_registro,
                        rhe.id_estado_aprobacion,
                        ea.nombre_estado AS estado_nombre,
                        t.nombre_tecnico,
                        c.nombre_cliente,
                        p.nombre_punto,
                        (
                            SELECT GROUP_CONCAT(ehe.ruta_archivo SEPARATOR '||')
                            FROM evidencia_horas_extra ehe
                            WHERE ehe.id_registro_he = rhe.id_registro_he
                        ) AS rutas_fotos
                    FROM registro_horas_extra rhe
                    INNER JOIN tecnico t ON rhe.id_tecnico = t.id_tecnico
                    INNER JOIN estados_aprobacion_he ea ON rhe.id_estado_aprobacion = ea.id_estado
                    LEFT JOIN cliente c ON rhe.id_cliente = c.id_cliente
                    LEFT JOIN punto p ON rhe.id_punto = p.id_punto
                    WHERE 1=1";

            $params = [];

            if (!empty($fechaInicio)) {
                $sql .= " AND rhe.fecha_reporte >= :fecha_inicio";
                $params[':fecha_inicio'] = $fechaInicio;
            }

            if (!empty($fechaFin)) {
                $sql .= " AND rhe.fecha_reporte <= :fecha_fin";
                $params[':fecha_fin'] = $fechaFin;
            }

            if (!empty($idTecnico)) {
                $sql .= " AND rhe.id_tecnico = :id_tecnico";
                $params[':id_tecnico'] = $idTecnico;
            }

            if (!empty($idEstado)) {
                $sql .= " AND rhe.id_estado_aprobacion = :id_estado";
                $params[':id_estado'] = $idEstado;
            }

            $sql .= " ORDER BY rhe.fecha_reporte DESC, rhe.id_registro_he DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo horas extra para admin: " . $e->getMessage());
            return [];
        }
    }

    // Actualizar el estado de aprobación y guardar nota del supervisor
    public function actualizarEstadoHorasExtra($idRegistro, $idEstado, $observacion)
    {
        try {
            $sql = "UPDATE registro_horas_extra 
                    SET id_estado_aprobacion = :id_estado, 
                        observacion_supervisor = :observacion 
                    WHERE id_registro_he = :id_registro";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_estado'   => $idEstado,
                ':observacion' => $observacion,
                ':id_registro' => $idRegistro
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizando estado de horas extra: " . $e->getMessage());
            return false;
        }
    }
}