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
        $sql = "SELECT * FROM control_remisiones WHERE id_control = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
            $sql = "UPDATE control_remisiones SET 
                        numero_remision = :numero,
                        id_tecnico = :id_tecnico,
                        estado = :estado
                    WHERE id_control = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':numero', $datos['numero_remision']);
            $stmt->bindParam(':id_tecnico', $datos['id_tecnico']);
            $stmt->bindParam(':estado', $datos['estado']);
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
