<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoMaquinaVerModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerTipos()
    {
        // Solo activos
        $sql = "SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarTipoLogicamente($id)
    {
        $sql = "UPDATE tipo_maquina SET estado = 0 WHERE id_tipo_maquina = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}