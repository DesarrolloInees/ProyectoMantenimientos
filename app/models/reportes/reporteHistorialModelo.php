<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteHistorialModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Esta es para la tabla web (Detallada, solo los que tuvieron visitas)
    public function obtenerHistorialMantenimientos($fecha_inicio, $fecha_fin)
    {
        try {
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
                AND (os.id_tipo_mantenimiento != 4 OR os.id_tipo_mantenimiento IS NULL)
                AND p.estado = 1 /* NUEVO: Solo trae puntos activos */
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

    // NUEVA FUNCIÓN PARA EL EXCEL: Trae TODOS los puntos y cuenta correctamente
    public function obtenerCumplimientoPuntos($fecha_inicio, $fecha_fin)
    {
        try {
            $sql = "SELECT 
                m.device_id,
                tmaq.nombre_tipo_maquina AS tipo_maquina,
                c.nombre_cliente AS cliente,
                p.nombre_punto AS punto,
                d.nombre_delegacion AS dele,
                p.frecuencia_mantenimiento_dias AS frecuencia,
                p.fecha_ultima_visita AS fecha_ultima,
                
                -- TOTAL MANTENIMIENTOS: Suma todas las órdenes, EXCEPTO si es 4 (Fallido)
                SUM(CASE WHEN os.id_ordenes_servicio IS NOT NULL AND (os.id_tipo_mantenimiento != 4 OR os.id_tipo_mantenimiento IS NULL) THEN 1 ELSE 0 END) AS total_mantenimientos,
                
                -- TOTAL PREVENTIVOS: Suma SOLO los que son 1 o 2 (Para hacer la resta de faltantes)
                SUM(CASE WHEN os.id_tipo_mantenimiento IN (1, 2) THEN 1 ELSE 0 END) AS total_preventivos
                
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                LEFT JOIN maquina m ON p.id_punto = m.id_punto
                LEFT JOIN tipo_maquina tmaq ON m.id_tipo_maquina = tmaq.id_tipo_maquina
                
                -- El JOIN se deja LIMPIO, solo uniendo con la fecha. Las condiciones las hace el SUM de arriba.
                LEFT JOIN ordenes_servicio os ON p.id_punto = os.id_punto 
                    AND m.id_maquina = os.id_maquina 
                    AND os.fecha_visita BETWEEN :inicio AND :fin 
                
                WHERE p.estado = 1 /* NUEVO: Filtra para que no salgan los puntos con estado 0 */
                
                GROUP BY p.id_punto, m.id_maquina
                ORDER BY c.nombre_cliente ASC, p.nombre_punto ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':inicio', $fecha_inicio);
            $stmt->bindParam(':fin', $fecha_fin);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en cumplimiento excel: " . $e->getMessage());
            return [];
        }
    }
}