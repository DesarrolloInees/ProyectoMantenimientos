<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteRepuestosModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Función 1: Reporte General (La que ya funciona)
    public function generarReporteRepuestos($origen, $fecha_inicio, $fecha_fin)
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

            if (!empty($origen)) {
                $sql .= " AND osr.origen = :origen";
            }

            $sql .= " GROUP BY r.id_repuesto, osr.origen ORDER BY total_cantidad DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            if (!empty($origen)) $stmt->bindParam(':origen', $origen);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error reporte agrupado: " . $e->getMessage());
            return [];
        }
    }

    // Función 2: DETALLADO INEES (CORREGIDA Y BLINDADA)
    public function obtenerDetalleInees($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT 
                        c.nombre_cliente,
                        p.nombre_punto,
                        m.device_id,
                        tm.nombre_tipo_maquina, /* Asegúrate que la tabla se llame 'tipo_maquina' y la columna 'nombre_tipo_maquina' */
                        os.numero_remision,
                        os.actividades_realizadas as observacion,
                        r.nombre_repuesto,
                        osr.cantidad,
                        os.fecha_visita
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                    INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                    INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN maquina m ON os.id_maquina = m.id_maquina
                    LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE osr.origen = 'INEES' 
                    AND os.fecha_visita BETWEEN :inicio AND :fin
                    ORDER BY os.fecha_visita DESC, c.nombre_cliente ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Esto guardará el error en el log de PHP (busca el archivo error.log si falla)
            error_log("Error reporte detalle INEES: " . $e->getMessage());
            return [];
        }
    }
}
