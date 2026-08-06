<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class horaExtraReporteModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerReporteHorasExtra($fechaInicio, $fechaFin, $idTecnico = null, $idEstado = null)
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
                        ea.nombre_estado AS estado_nombre,
                        t.nombre_tecnico,
                        COALESCE(c.nombre_cliente, 'SIN CLIENTE') AS nombre_cliente,
                        COALESCE(p.nombre_punto, 'SIN PUNTO') AS nombre_punto,
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
                    WHERE rhe.fecha_reporte BETWEEN :fecha_inicio AND :fecha_fin";

            $params = [
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin'    => $fechaFin
            ];

            if (!empty($idTecnico)) {
                $sql .= " AND rhe.id_tecnico = :id_tecnico";
                $params[':id_tecnico'] = $idTecnico;
            }

            if (!empty($idEstado)) {
                $sql .= " AND rhe.id_estado_aprobacion = :id_estado";
                $params[':id_estado'] = $idEstado;
            }

            $sql .= " ORDER BY rhe.fecha_reporte ASC, rhe.hora_inicio ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerReporteHorasExtra: " . $e->getMessage());
            return [];
        }
    }
}