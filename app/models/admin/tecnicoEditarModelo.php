<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TecnicoEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerTecnicoPorId($id)
    {
        $sql = "SELECT * FROM tecnico WHERE id_tecnico = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarTecnico($id, $nombre, $estado)
    {
        try {
            $sql = "UPDATE tecnico SET nombre_tecnico = :nombre, estado = :estado WHERE id_tecnico = :id";
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
