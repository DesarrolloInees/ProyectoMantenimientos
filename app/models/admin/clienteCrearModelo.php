<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ClienteCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearCliente($datos)
    {
        try {
            // El estado por defecto en tu tabla es 1 (Activo), pero lo aseguramos aquí.
            $sql = "INSERT INTO cliente (
                        nombre_cliente, 
                        codigo_cliente, 
                        estado
                    ) VALUES (
                        :nombre, 
                        :codigo, 
                        1
                    )";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':nombre', $datos['nombre_cliente']);
            
            // Si el código está vacío, enviamos NULL para evitar conflictos únicos si la columna lo permite
            // Pero como tienes UNIQUE INDEX, lo ideal es que siempre tenga un código único.
            $codigo = !empty($datos['codigo_cliente']) ? $datos['codigo_cliente'] : null;
            $stmt->bindParam(':codigo', $codigo);

            return $stmt->execute();

        } catch (PDOException $e) {
            // Error 23000 es violación de restricción única (código repetido)
            if ($e->getCode() == '23000') {
                error_log("Intento de duplicar código cliente: " . $e->getMessage());
            } else {
                error_log("Error al crear cliente: " . $e->getMessage());
            }
            return false;
        }
    }
}