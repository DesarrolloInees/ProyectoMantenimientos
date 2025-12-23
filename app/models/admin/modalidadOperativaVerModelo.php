<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ModalidadOperativaVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerModalidades()
    {
        $sql = "SELECT * FROM modalidad_operativa ORDER BY id_modalidad ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarModalidad($id)
    {
        // BORRADO FÍSICO
        $sql = "DELETE FROM modalidad_operativa WHERE id_modalidad = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
