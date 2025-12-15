<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ModalidadOperativaEditarModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerModalidadPorId($id)
    {
        $sql = "SELECT * FROM modalidad_operativa WHERE id_modalidad = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarModalidad($id, $nombre)
    {
        try {
            $sql = "UPDATE modalidad_operativa SET nombre_modalidad = :nombre WHERE id_modalidad = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}