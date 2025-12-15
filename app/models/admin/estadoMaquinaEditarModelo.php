<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class EstadoMaquinaEditarModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerEstadoPorId($id)
    {
        $sql = "SELECT * FROM estado_maquina WHERE id_estado = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarEstado($id, $nombre)
    {
        try {
            $sql = "UPDATE estado_maquina SET nombre_estado = :nombre WHERE id_estado = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}