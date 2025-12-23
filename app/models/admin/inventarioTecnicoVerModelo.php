<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class InventarioTecnicoVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Listar todo el inventario activo
    public function obtenerInventarioCompleto()
    {
        try {
            $sql = "SELECT 
                        i.id_inventario,
                        i.cantidad_actual,
                        i.ultima_actualizacion,
                        t.nombre_tecnico,
                        r.nombre_repuesto,
                        r.codigo_referencia
                    FROM inventario_tecnico i
                    INNER JOIN tecnico t ON i.id_tecnico = t.id_tecnico
                    INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
                    WHERE i.estado = 1
                    ORDER BY t.nombre_tecnico ASC, r.nombre_repuesto ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error listar inventario: " . $e->getMessage());
            return [];
        }
    }

    // Borrado Lógico (Cambiar estado a 0)
    public function eliminarLogico($id)
    {
        try {
            $sql = "UPDATE inventario_tecnico SET estado = 0 WHERE id_inventario = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar inventario: " . $e->getMessage());
            return false;
        }
    }
}
