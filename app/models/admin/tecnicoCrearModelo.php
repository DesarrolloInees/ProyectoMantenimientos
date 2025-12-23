<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TecnicoCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearTecnico($nombre)
    {
        try {
            // Insertamos siempre activo (estado = 1)
            $sql = "INSERT INTO tecnico (nombre_tecnico, estado) VALUES (:nombre, 1)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error crear técnico: " . $e->getMessage());
            return false;
        }
    }
}
