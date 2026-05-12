<?php
class NotificacionesLogisticaModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // 1. OBTENER PUNTOS VISITADOS MÁS DE 2 VECES EN LOS ÚLTIMOS 7 DÍAS
    public function obtenerVisitasFrecuentes()
    {
        try {
            $sql = "SELECT 
                        p.nombre_punto,
                        c.nombre_cliente,
                        COUNT(os.id_ordenes_servicio) AS total_visitas,
                        GROUP_CONCAT(os.fecha_visita ORDER BY os.fecha_visita ASC SEPARATOR ' | ') AS fechas_visitadas
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                    WHERE os.fecha_visita >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                        AND os.estado = 1
                    GROUP BY os.id_punto, p.nombre_punto, c.nombre_cliente
                    HAVING total_visitas > 2";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerVisitasFrecuentes: " . $e->getMessage());
            return [];
        }
    }

    // 2. OBTENER DESPLAZAMIENTOS URBANOS MAYORES A 40 MINUTOS (DEL DÍA ACTUAL)
    public function obtenerDesplazamientosLargos()
    {
        try {
            // Usamos LAG() para ver la hora de salida del servicio anterior del mismo técnico en el mismo día
            $sql = "WITH ViajesTecnicos AS (
                        SELECT 
                            os.id_ordenes_servicio,
                            t.nombre_tecnico,
                            p.nombre_punto,
                            mo.nombre_modalidad,
                            os.fecha_visita,
                            os.hora_entrada,
                            LAG(os.hora_salida) OVER (PARTITION BY os.id_tecnico, os.fecha_visita ORDER BY os.hora_entrada) AS salida_servicio_anterior
                        FROM ordenes_servicio os
                        INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                        INNER JOIN punto p ON os.id_punto = p.id_punto
                        INNER JOIN modalidad_operativa mo ON os.id_modalidad = mo.id_modalidad
                        WHERE os.fecha_visita = CURDATE()
                            AND mo.nombre_modalidad LIKE '%Urbano%'
                            AND os.estado = 1
                    )
                    SELECT 
                        nombre_tecnico,
                        nombre_punto AS destino,
                        salida_servicio_anterior AS inicio_desplazamiento,
                        hora_entrada AS llegada_destino,
                        TIMESTAMPDIFF(MINUTE, salida_servicio_anterior, hora_entrada) AS minutos_viaje
                    FROM ViajesTecnicos
                    WHERE salida_servicio_anterior IS NOT NULL 
                        AND TIMESTAMPDIFF(MINUTE, salida_servicio_anterior, hora_entrada) > 40";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerDesplazamientosLargos: " . $e->getMessage());
            return [];
        }
    }
}
?>