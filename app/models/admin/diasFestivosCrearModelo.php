<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class DiasFestivosCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearFestivo($fecha, $descripcion)
    {
        try {
            $sql = "INSERT INTO dias_festivos (fecha, descripcion) VALUES (:fecha, :desc)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':desc', $descripcion);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Error 1062 es entrada duplicada (por el UNIQUE INDEX de fecha)
            if ($e->errorInfo[1] == 1062) {
                error_log("Intento de duplicar fecha festiva: " . $fecha);
                return "DUPLICADO";
            }
            error_log("Error crear festivo: " . $e->getMessage());
            return false;
        }
    }
}
