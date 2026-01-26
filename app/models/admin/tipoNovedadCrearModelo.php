<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoNovedadCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearTipoNovedad($nombre)
    {
        try {
            // Insertamos nombre_novedad y forzamos estado a 1
            $sql = "INSERT INTO tipo_novedad (nombre_novedad, estado) VALUES (:nombre, 1)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                error_log("Duplicado en tipo_novedad: " . $e->getMessage());
            }
            return false;
        }
    }
}