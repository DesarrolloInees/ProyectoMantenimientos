<?php
class AsistenciaModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // 1. OBTENER TODOS LOS EMPLEADOS ACTIVOS (Priorizando la tabla Tecnico)
    public function obtenerEmpleadosActivos()
    {
        try {
            // Priorizamos la tabla técnico para que el nombre oficial sea siempre el de los servicios
            $sql = "SELECT nombre_tecnico as nombre_bd, 'Técnico' as cargo FROM tecnico WHERE estado = 1
                    UNION
                    SELECT nombre as nombre_bd, cargo FROM usuarios WHERE estado = 'activo'";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fallback por si hay un error con la tabla usuarios
            $sql = "SELECT nombre_tecnico as nombre_bd, 'Técnico' as cargo FROM tecnico WHERE estado = 1";
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