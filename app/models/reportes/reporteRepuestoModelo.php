<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteRepuestosModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function generarReporteRepuesto($origen, $fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT 
                        r.codigo_referencia,
                        r.nombre_repuesto,
                        osr.origen,
                        SUM(osr.cantidad) as total_cantidad,
                        COUNT(os.id_ordenes_servicio) as veces_usado
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                    INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin";

            // Si el usuario filtró por origen (INEES/PROSEGUR), agregamos la condición
            if (!empty($origen)) {
                $sql .= " AND osr.origen = :origen";
            }

            $sql .= " GROUP BY r.id_repuesto, osr.origen
                    ORDER BY total_cantidad DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            
            if (!empty($origen)) {
                $stmt->bindParam(':origen', $origen);
            }

            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en reporte de repuestos: " . $e->getMessage());
            return [];
        }
    }
}