<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class RepuestoEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Obtener un solo repuesto para llenar el formulario
    public function obtenerRepuestoPorId($id)
    {
        try {
            $sql = "SELECT * FROM repuesto WHERE id_repuesto = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo repuesto por ID: " . $e->getMessage());
            return false;
        }
    }

    // Actualizar datos
    public function editarRepuesto($id, $datos)
    {
        try {
            $sql = "UPDATE repuesto SET 
                        nombre_repuesto = :nombre, 
                        codigo_referencia = :codigo, 
                        estado = :estado 
                    WHERE 
                        id_repuesto = :id";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':nombre', $datos['nombre_repuesto']);
            
            // Manejo de NULL para código
            $codigo = !empty($datos['codigo_referencia']) ? $datos['codigo_referencia'] : null;
            $stmt->bindParam(':codigo', $codigo);
            
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error actualizando repuesto: " . $e->getMessage());
            return false;
        }
    }
}
?>