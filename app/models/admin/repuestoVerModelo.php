<?php
// app/models/admin/repuestoVerModelo.php

if (!defined('ENTRADA_PRINCIPAL')) {
    die("Acceso denegado.");
}

class RepuestoVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerRepuestos()
    {
        try {
            // Seleccionamos solo los activos (estado = 1) para la lista principal
            // O si prefieres ver todos, quita el WHERE
            $sql = "SELECT 
                        id_repuesto, 
                        nombre_repuesto, 
                        codigo_referencia, 
                        estado 
                    FROM 
                        repuesto 
                    WHERE 
                        estado = 1 
                    ORDER BY 
                        nombre_repuesto ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener repuestos: " . $e->getMessage());
            return [];
        }
    }

    // Para el borrado lógico (botón rojo)
    public function eliminarRepuestoLogicamente($id)
    {
        $sql = "UPDATE repuesto SET estado = 0 WHERE id_repuesto = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar repuesto: " . $e->getMessage());
            return false;
        }
    }
}
?>