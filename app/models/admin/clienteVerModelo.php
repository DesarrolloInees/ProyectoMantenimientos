<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ClienteVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerClientes()
    {
        try {
            // Solo activos
            $sql = "SELECT * FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener clientes: " . $e->getMessage());
            return [];
        }
    }

    public function eliminarClienteLogicamente($id)
    {
        try {
            $sql = "UPDATE cliente SET estado = 0 WHERE id_cliente = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}