<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteHistorialModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerHistorialMantenimientos($fecha_inicio, $fecha_fin)
    {
        try {
            // Se agregaron los JOINs de tipo_maquina y se filtró el mantenimiento 5 (Garantía)
            $sql = "SELECT 
                os.id_ordenes_servicio,
                os.fecha_visita,
                os.id_tipo_mantenimiento,
                c.nombre_cliente AS cliente,
                p.nombre_punto AS punto,
                d.nombre_delegacion AS dele,
                m.device_id,
                tmaq.nombre_tipo_maquina AS tipo_maquina,
                tm.nombre_completo AS tipo_mantenimiento
                FROM ordenes_servicio os
                INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                INNER JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN maquina m ON os.id_maquina = m.id_maquina
                LEFT JOIN tipo_maquina tmaq ON m.id_tipo_maquina = tmaq.id_tipo_maquina
                LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                AND (os.id_tipo_mantenimiento != 5 OR os.id_tipo_mantenimiento IS NULL) /* Excluir Garantía */
                ORDER BY os.fecha_visita DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en historial de puntos: " . $e->getMessage());
            return [];
        }
    }
}