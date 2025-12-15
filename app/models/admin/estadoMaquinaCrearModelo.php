<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class EstadoMaquinaCrearModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function crearEstado($nombre)
    {
        try {
            $sql = "INSERT INTO estado_maquina (nombre_estado) VALUES (:nombre)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error crear estado máquina: " . $e->getMessage());
            return false;
        }
    }
}