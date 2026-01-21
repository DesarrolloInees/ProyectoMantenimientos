<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ControlRemisionVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function listarRemisiones()
    {
        // Agregamos cr.fecha_uso a la selección
        $sql = "SELECT 
                    cr.id_control,
                    cr.numero_remision,
                    cr.estado,
                    cr.fecha_asignacion,
                    cr.fecha_uso, 
                    t.nombre_tecnico
                FROM control_remisiones cr
                INNER JOIN tecnico t ON cr.id_tecnico = t.id_tecnico
                WHERE cr.estado != 'ELIMINADO' 
                ORDER BY cr.id_control DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ... dentro de class ControlRemisionVerModelo ...

    public function obtenerSalteadasSandwich()
    {
        // LÓGICA STRICT SANDWICH:
        // Busca una remisión DISPONIBLE que tenga:
        // - Inmediatamente atrás (-1): Una USADA
        // - Inmediatamente adelante (+1): Una USADA
        
        $sql = "SELECT 
                    curr.id_control,
                    curr.numero_remision,
                    curr.estado,
                    curr.fecha_asignacion,
                    t.nombre_tecnico,
                    prev.numero_remision as anterior,
                    next.numero_remision as siguiente
                FROM control_remisiones curr
                INNER JOIN tecnico t ON curr.id_tecnico = t.id_tecnico
                
                -- JOIN para asegurar que existe la ANTERIOR usada
                INNER JOIN control_remisiones prev 
                    ON curr.id_tecnico = prev.id_tecnico 
                    AND CAST(prev.numero_remision AS UNSIGNED) = CAST(curr.numero_remision AS UNSIGNED) - 1
                    AND prev.estado = 'USADA'
                
                -- JOIN para asegurar que existe la SIGUIENTE usada
                INNER JOIN control_remisiones next 
                    ON curr.id_tecnico = next.id_tecnico 
                    AND CAST(next.numero_remision AS UNSIGNED) = CAST(curr.numero_remision AS UNSIGNED) + 1
                    AND next.estado = 'USADA'
                
                WHERE curr.estado = 'DISPONIBLE'
                ORDER BY t.nombre_tecnico ASC, CAST(curr.numero_remision AS UNSIGNED) ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para actualizar el estado (usado por el botón)
    public function actualizarEstadoRapido($id, $nuevoEstado)
    {
        try {
            $sql = "UPDATE control_remisiones SET estado = :estado WHERE id_control = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':estado', $nuevoEstado);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}

