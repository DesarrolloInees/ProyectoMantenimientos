<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class DelegacionCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearDelegacion($nombre)
    {
        try {
            $sql = "INSERT INTO delegacion (nombre_delegacion, estado) VALUES (:nombre, 1)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                error_log("Duplicado en delegación: " . $e->getMessage());
            }
            return false;
        }
    }
}
