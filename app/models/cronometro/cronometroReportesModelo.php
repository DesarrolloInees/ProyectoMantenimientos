<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class cronometroReportesModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerRendimientoTecnicos($fechaInicio, $fechaFin, $tipos = [])
    {
        try {
            $selectTipos = '';
            foreach ($tipos as $tipo) {
                $alias = 'tipo_' . $tipo['id_tipo_mantenimiento'];
                $selectTipos .= ", SUM(CASE 
                                WHEN ml.id_tipo_mantenimiento = :id_{$tipo['id_tipo_mantenimiento']} 
                                        AND ml.estado_actual = 'Finalizado' 
                                THEN 1 ELSE 0 END) AS $alias";
            }

            $sql = "SELECT 
                    t.id_tecnico,
                    t.nombre_tecnico,
                    COUNT(ml.id_monitoreo) AS total_registrados,
                    SUM(CASE WHEN ml.estado_actual = 'Finalizado' THEN 1 ELSE 0 END) AS total_finalizados,
                    SUM(CASE WHEN ml.estado_actual = 'En Progreso' THEN 1 ELSE 0 END) AS total_en_progreso
                    $selectTipos
                FROM tecnico t
                LEFT JOIN monitoreo_motorizados_live ml 
                    ON t.id_tecnico = ml.id_tecnico 
                    AND ml.fecha_servicio BETWEEN :fecha_inicio AND :fecha_fin
                WHERE t.estado = 1
                GROUP BY t.id_tecnico, t.nombre_tecnico
                ORDER BY total_finalizados DESC, t.nombre_tecnico ASC";

            $stmt = $this->conn->prepare($sql);
            $params = [
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ];
            foreach ($tipos as $tipo) {
                $params[':id_' . $tipo['id_tipo_mantenimiento']] = $tipo['id_tipo_mantenimiento'];
            }
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerRendimientoTecnicos: " . $e->getMessage());
            return [];
        }
    }


    public function obtenerTiposMantenimiento()
    {
        try {
            $sql = "SELECT id_tipo_mantenimiento, nombre_completo 
                FROM tipo_mantenimiento 
                WHERE estado = 1 
                ORDER BY id_tipo_mantenimiento";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerTiposMantenimiento: " . $e->getMessage());
            return [];
        }
    }
}