<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class reporteTarifasModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerDatosPorcentajesMantenimiento($fechaInicio, $fechaFin)
    {
        try {
            // Agrupamos por tamaño (usando los IDs), tipo de máquina y mantenimiento.
            $sql = "SELECT 
                        CASE 
                            WHEN tmq.id_tipo_maquina IN (9, 15, 45) THEN 'Grandes' 
                            ELSE 'Pequeñas' 
                        END AS categoria_tamano,
                        tmq.nombre_tipo_maquina, 
                        tm.nombre_completo AS tipo_mantenimiento,
                        COUNT(os.id_ordenes_servicio) AS cantidad_servicios,
                        SUM(os.valor_servicio) AS precio_total
                    FROM ordenes_servicio os
                    INNER JOIN maquina m ON os.id_maquina = m.id_maquina
                    INNER JOIN tipo_maquina tmq ON m.id_tipo_maquina = tmq.id_tipo_maquina
                    INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.estado = 1
                        AND tm.nombre_completo NOT LIKE '%Instalacion%' 
                        AND tm.nombre_completo NOT LIKE '%Garantia%'
                        AND os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY categoria_tamano, tmq.id_tipo_maquina, tm.id_tipo_mantenimiento
                    ORDER BY categoria_tamano ASC, tmq.nombre_tipo_maquina ASC, precio_total DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fechaInicio);
            $stmt->bindParam(':fin', $fechaFin);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en exportación de porcentajes: " . $e->getMessage());
            return [];
        }
    }
}