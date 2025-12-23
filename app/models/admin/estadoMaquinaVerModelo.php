<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class EstadoMaquinaVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerEstados()
    {
        $sql = "SELECT * FROM estado_maquina ORDER BY id_estado ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarEstado($id)
    {
        // Borrado físico directo
        $sql = "DELETE FROM estado_maquina WHERE id_estado = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
