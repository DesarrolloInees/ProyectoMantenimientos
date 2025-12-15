<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoMantenimientoEditarModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerTipoPorId($id)
    {
        $sql = "SELECT * FROM tipo_mantenimiento WHERE id_tipo_mantenimiento = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarTipo($id, $nombre, $estado)
    {
        try {
            $sql = "UPDATE tipo_mantenimiento SET nombre_completo = :nombre, estado = :estado WHERE id_tipo_mantenimiento = :id";
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