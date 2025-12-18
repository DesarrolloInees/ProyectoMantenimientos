<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class DiasFestivosEditarModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM dias_festivos WHERE id_festivo = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarFestivo($id, $fecha, $descripcion)
    {
        try {
            $sql = "UPDATE dias_festivos SET fecha = :fecha, descripcion = :desc WHERE id_festivo = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':desc', $descripcion);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) return "DUPLICADO";
            return false;
        }
    }
}