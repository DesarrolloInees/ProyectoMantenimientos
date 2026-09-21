<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteTrazabilidadModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * HOJA 1: Trazabilidad por Punto.
     * Una fila por cada repuesto cambiado en una orden de servicio.
     * Orden: Punto ASC, Fecha de cambio DESC.
     */
    public function getTrazabilidadPorPunto($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT
                        c.nombre_cliente       AS cliente,
                        p.nombre_punto         AS punto,
                        tm.nombre_tipo_maquina AS tipo_maquina,
                        m.device_id            AS device_id,
                        r.nombre_repuesto      AS repuesto,
                        osr.origen             AS origen,
                        osr.cantidad           AS cantidad,
                        os.fecha_visita        AS fecha_cambio
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os ON os.id_ordenes_servicio = osr.id_orden_servicio
                    INNER JOIN repuesto r         ON r.id_repuesto = osr.id_repuesto
                    INNER JOIN maquina m          ON m.id_maquina = os.id_maquina
                    INNER JOIN tipo_maquina tm    ON tm.id_tipo_maquina = m.id_tipo_maquina
                    INNER JOIN punto p            ON p.id_punto = os.id_punto
                    INNER JOIN cliente c          ON c.id_cliente = os.id_cliente
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    ORDER BY p.nombre_punto ASC, os.fecha_visita DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error ReporteTrazabilidad Hoja1: " . $e->getMessage());
            return [];
        }
    }

    /**
     * HOJA 2: Top Repuestos por Tipo de Máquina.
     * Agrupado por Tipo de Máquina + Repuesto + Origen, con suma de cantidades.
     * Orden: Tipo de Máquina ASC, Total DESC.
     */
    public function getTopPorTipoMaquina($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT
                        tm.nombre_tipo_maquina AS tipo_maquina,
                        r.nombre_repuesto      AS repuesto,
                        osr.origen             AS origen,
                        SUM(osr.cantidad)      AS total_cambiados
                    FROM orden_servicio_repuesto osr
                    INNER JOIN ordenes_servicio os ON os.id_ordenes_servicio = osr.id_orden_servicio
                    INNER JOIN repuesto r         ON r.id_repuesto = osr.id_repuesto
                    INNER JOIN maquina m          ON m.id_maquina = os.id_maquina
                    INNER JOIN tipo_maquina tm    ON tm.id_tipo_maquina = m.id_tipo_maquina
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY tm.nombre_tipo_maquina, r.nombre_repuesto, osr.origen
                    ORDER BY tm.nombre_tipo_maquina ASC, total_cambiados DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error ReporteTrazabilidad Hoja2: " . $e->getMessage());
            return [];
        }
    }
}
