<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoMantenimientoCrearModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function crearTipo($nombre)
    {
        try {
            $sql = "INSERT INTO tipo_mantenimiento (nombre_completo, estado) VALUES (:nombre, 1)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error crear tipo mantenimiento: " . $e->getMessage());
            return false;
        }
    }
}