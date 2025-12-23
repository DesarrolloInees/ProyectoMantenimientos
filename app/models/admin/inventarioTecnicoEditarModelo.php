<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class InventarioTecnicoEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Obtener datos actuales para el formulario (incluyendo nombres)
    public function obtenerPorId($id)
    {
        $sql = "SELECT i.*, t.nombre_tecnico, r.nombre_repuesto 
                FROM inventario_tecnico i
                INNER JOIN tecnico t ON i.id_tecnico = t.id_tecnico
                INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                WHERE i.id_inventario = :id AND i.estado = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar SOLO la cantidad (Ajuste de inventario)
    public function actualizarCantidad($id, $cantidad)
    {
        try {
            $sql = "UPDATE inventario_tecnico SET cantidad_actual = :cant WHERE id_inventario = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':cant', $cantidad);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizando stock: " . $e->getMessage());
            return false;
        }
    }
}
