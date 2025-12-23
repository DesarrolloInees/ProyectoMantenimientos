<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class CalificacionServicioCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearCalificacion($nombre)
    {
        try {
            $sql = "INSERT INTO calificacion_servicio (nombre_calificacion) VALUES (:nombre)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error crear calificación: " . $e->getMessage());
            return false;
        }
    }
}
