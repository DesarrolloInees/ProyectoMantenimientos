<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoMaquinaCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearTipo($nombre)
    {
        try {
            $sql = "INSERT INTO tipo_maquina (nombre_tipo_maquina, estado) VALUES (:nombre, 1)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                error_log("Duplicado en tipo maquina: " . $e->getMessage());
            }
            return false;
        }
    }
}
