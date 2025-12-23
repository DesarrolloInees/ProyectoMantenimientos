<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class CalificacionServicioVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerCalificaciones()
    {
        $sql = "SELECT * FROM calificacion_servicio ORDER BY id_calificacion ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarCalificacion($id)
    {
        // BORRADO FÍSICO PORQUE NO HAY CAMPO 'ESTADO'
        $sql = "DELETE FROM calificacion_servicio WHERE id_calificacion = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
