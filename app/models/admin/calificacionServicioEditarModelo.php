<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class CalificacionServicioEditarModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerCalificacionPorId($id)
    {
        $sql = "SELECT * FROM calificacion_servicio WHERE id_calificacion = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarCalificacion($id, $nombre)
    {
        try {
            $sql = "UPDATE calificacion_servicio SET nombre_calificacion = :nombre WHERE id_calificacion = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}