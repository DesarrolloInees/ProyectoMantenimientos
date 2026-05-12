<?php
class AsistenciaModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // 1. OBTENER TODOS LOS EMPLEADOS ACTIVOS (Priorizando la tabla Tecnico y verificando Usuarios)
    public function obtenerEmpleadosActivos()
    {
        try {
            // 🔥 CORRECCIÓN: Cruzamos técnico con usuarios. 
            // Si el técnico tiene un usuario asignado, ESTE DEBE ESTAR ACTIVO.
            // Si no tiene usuario asignado (IS NULL), solo verificamos que el técnico sea 1.
            $sql = "SELECT t.nombre_tecnico as nombre_bd, 'Técnico' as cargo 
                    FROM tecnico t
                    LEFT JOIN usuarios u ON t.usuario_id = u.usuario_id
                    WHERE t.estado = 1 AND (u.estado = 'activo' OR u.estado IS NULL)
                    
                    UNION
                    
                    SELECT nombre as nombre_bd, cargo 
                    FROM usuarios 
                    WHERE estado = 'activo'";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fallback por si hay un error de sintaxis o conexión
            $sql = "SELECT t.nombre_tecnico as nombre_bd, 'Técnico' as cargo 
                    FROM tecnico t 
                    WHERE t.estado = 1";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // 2. OBTENER EL RESUMEN DE LOS SERVICIOS Y HORARIOS EN UN RANGO DE FECHAS
    public function obtenerResumenServicios($fechaInicio, $fechaFin)
    {
        try {
            // Se agrupa por técnico y día para extraer su primer servicio y último servicio
            $sql = "SELECT t.nombre_tecnico as nombre_bd, 
                            DATE(os.fecha_visita) as fecha_ymd, 
                            MIN(os.hora_entrada) as entrada_srv, 
                            MAX(os.hora_salida) as salida_srv, 
                            COUNT(os.id_ordenes_servicio) as cant_servicios
                    FROM ordenes_servicio os
                    INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    WHERE os.fecha_visita BETWEEN :ini AND :fin
                        AND os.estado != 0
                    GROUP BY t.id_tecnico, DATE(os.fecha_visita)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':ini' => $fechaInicio, ':fin' => $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>