<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ClienteEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Buscar datos para llenar el formulario
    public function obtenerClientePorId($id)
    {
        try {
            $sql = "SELECT * FROM cliente WHERE id_cliente = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener cliente: " . $e->getMessage());
            return false;
        }
    }

    // Guardar cambios
    public function editarCliente($id, $datos)
    {
        try {
            $sql = "UPDATE cliente SET 
                        nombre_cliente = :nombre, 
                        codigo_cliente = :codigo, 
                        estado = :estado
                    WHERE 
                        id_cliente = :id";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':nombre', $datos['nombre_cliente']);
            
            // Manejo de NULL para código
            $codigo = !empty($datos['codigo_cliente']) ? $datos['codigo_cliente'] : null;
            $stmt->bindParam(':codigo', $codigo);
            
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            // Manejo de error si intentan poner un código que ya usa otro cliente
            if ($e->getCode() == '23000') {
                error_log("Error duplicado al editar cliente: " . $e->getMessage());
            }
            return false;
        }
    }
}
?>