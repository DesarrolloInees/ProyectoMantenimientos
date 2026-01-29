<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ControlRemisionEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Para llenar el formulario con los datos actuales
    public function obtenerRemisionPorId($id)
    {
        // CAMBIO: Hacemos JOIN para traer también el nombre del estado (texto)
        // Esto sirve para que la vista sepa si el estado actual es "USADA" u otro.
        $sql = "SELECT c.*, e.nombre_estado 
                FROM control_remisiones c
                LEFT JOIN estados_remision e ON c.id_estado = e.id_estado
                WHERE c.id_control = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // NUEVO: Para el Select de estados (ya no es un array fijo)
    public function obtenerEstados()
    {
        // Traemos todos los estados activos ordenados alfabéticamente
        $sql = "SELECT id_estado, nombre_estado FROM estados_remision WHERE activo = 1 ORDER BY nombre_estado ASC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Para el Select de técnicos
    public function obtenerTecnicos()
    {
        $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Guardar cambios
    public function actualizarRemision($datos)
    {
        try {
            // CAMBIO: Ahora actualizamos 'id_estado' con el número que viene del formulario
            $sql = "UPDATE control_remisiones SET 
                        numero_remision = :numero,
                        id_tecnico = :id_tecnico,
                        id_estado = :id_estado
                    WHERE id_control = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':numero', $datos['numero_remision']);
            $stmt->bindParam(':id_tecnico', $datos['id_tecnico']);
            $stmt->bindParam(':id_estado', $datos['id_estado']); // Guardamos el ID
            $stmt->bindParam(':id', $datos['id_control']);

            return $stmt->execute();
        } catch (PDOException $e) {
            // Si intenta poner un número que ya existe en otro registro
            if ($e->getCode() == '23000') {
                return "DUPLICADO";
            }
            return false;
        }
    }
}