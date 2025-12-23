<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class DiasFestivosVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Función para listar (VER)
    public function obtenerTodos()
    {
        try {
            $sql = "SELECT * FROM dias_festivos ORDER BY fecha DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al listar festivos: " . $e->getMessage());
            return [];
        }
    }

    // Función para borrar (ELIMINAR) - Agregada aquí
    public function eliminarFestivo($id)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM dias_festivos WHERE id_festivo = :id");
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar festivo: " . $e->getMessage());
            return false;
        }
    }
}
