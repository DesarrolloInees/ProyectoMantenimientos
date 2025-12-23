<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class DelegacionEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerDelegacionPorId($id)
    {
        $sql = "SELECT * FROM delegacion WHERE id_delegacion = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarDelegacion($id, $nombre, $estado)
    {
        try {
            $sql = "UPDATE delegacion SET nombre_delegacion = :nombre, estado = :estado WHERE id_delegacion = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
