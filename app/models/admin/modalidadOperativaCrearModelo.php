<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ModalidadOperativaCrearModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function crearModalidad($nombre)
    {
        try {
            $sql = "INSERT INTO modalidad_operativa (nombre_modalidad) VALUES (:nombre)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error crear modalidad: " . $e->getMessage());
            return false;
        }
    }
}