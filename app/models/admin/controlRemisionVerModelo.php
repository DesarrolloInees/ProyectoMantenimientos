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
        // CAMBIO: Hacemos JOIN con 'estados_remision' (alias 'e')
        // Seleccionamos 'e.nombre_estado' para poder mostrar el texto en la tabla
        $sql = "SELECT 
                    cr.id_control,
                    cr.numero_remision,
                    cr.id_estado,            -- Traemos el ID por si acaso
                    e.nombre_estado,         -- Traemos el NOMBRE (Ej: DISPONIBLE)
                    cr.fecha_asignacion,
                    cr.fecha_uso, 
                    t.nombre_tecnico
                FROM control_remisiones cr
                INNER JOIN tecnico t ON cr.id_tecnico = t.id_tecnico
                INNER JOIN estados_remision e ON cr.id_estado = e.id_estado
                WHERE e.nombre_estado != 'ELIMINADO' 
                ORDER BY cr.id_control DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerSalteadasSandwich()
    {
        // CAMBIO: Para filtrar por 'USADA' o 'DISPONIBLE', usamos subconsultas
        // Esto es más limpio que hacer 3 JOINs extra a la tabla de estados
        
        $sql = "SELECT 
                    curr.id_control,
                    curr.numero_remision,
                    e_curr.nombre_estado,
                    curr.fecha_asignacion,
                    t.nombre_tecnico,
                    prev.numero_remision as anterior,
                    next.numero_remision as siguiente
                FROM control_remisiones curr
                INNER JOIN tecnico t ON curr.id_tecnico = t.id_tecnico
                INNER JOIN estados_remision e_curr ON curr.id_estado = e_curr.id_estado
                
                -- JOIN para asegurar que existe la ANTERIOR usada
                INNER JOIN control_remisiones prev 
                    ON curr.id_tecnico = prev.id_tecnico 
                    AND CAST(prev.numero_remision AS UNSIGNED) = CAST(curr.numero_remision AS UNSIGNED) - 1
                    AND prev.id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1)
                
                -- JOIN para asegurar que existe la SIGUIENTE usada
                INNER JOIN control_remisiones next 
                    ON curr.id_tecnico = next.id_tecnico 
                    AND CAST(next.numero_remision AS UNSIGNED) = CAST(curr.numero_remision AS UNSIGNED) + 1
                    AND next.id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'USADA' LIMIT 1)
                
                WHERE e_curr.nombre_estado = 'DISPONIBLE'
                ORDER BY t.nombre_tecnico ASC, CAST(curr.numero_remision AS UNSIGNED) ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para actualizar el estado (usado por el botón)
   // Método para actualizar el estado (usado por el botón de Remisiones Pendientes/Salteadas)
    public function actualizarEstadoRapido($idRemision, $nombreEstado)
    {
        try {
            // CAMBIO: En lugar de guardar el valor directo, hacemos una SUBCONSULTA
            // Buscamos cuál es el ID que corresponde al texto (Ej: 'ANULADA' -> ID 3)
            $sql = "UPDATE control_remisiones 
                    SET id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = :estado LIMIT 1) 
                    WHERE id_control = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':estado', $nombreEstado); // Pasamos 'ANULADA', 'USADA', etc.
            $stmt->bindParam(':id', $idRemision);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}