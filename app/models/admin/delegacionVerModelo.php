<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class DelegacionVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerDelegaciones()
    {
        $sql = "SELECT * FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarDelegacionLogicamente($id)
    {
        $sql = "UPDATE delegacion SET estado = 0 WHERE id_delegacion = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
