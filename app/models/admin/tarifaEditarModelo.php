<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TarifaEditarModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerTarifaPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM tarifa WHERE id_tarifa = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarTarifa($id, $datos)
    {
        try {
            $sql = "UPDATE tarifa SET 
                    id_tipo_maquina = :maquina,
                    id_tipo_mantenimiento = :mantenimiento,
                    id_modalidad = :modalidad,
                    precio = :precio,
                    año_vigencia = :anio
                    WHERE id_tarifa = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':maquina', $datos['id_tipo_maquina'], PDO::PARAM_INT);
            $stmt->bindParam(':mantenimiento', $datos['id_tipo_mantenimiento'], PDO::PARAM_INT);
            $stmt->bindParam(':modalidad', $datos['id_modalidad'], PDO::PARAM_INT);
            $stmt->bindParam(':precio', $datos['precio']);
            $stmt->bindParam(':anio', $datos['año_vigencia'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Helpers (Reutilizados del crear)
    public function obtenerTiposMaquina() { return $this->conn->query("SELECT * FROM tipo_maquina WHERE estado = 1")->fetchAll(PDO::FETCH_ASSOC); }
    public function obtenerTiposMantenimiento() { return $this->conn->query("SELECT * FROM tipo_mantenimiento WHERE estado = 1")->fetchAll(PDO::FETCH_ASSOC); }
    public function obtenerModalidades() { return $this->conn->query("SELECT * FROM modalidad_operativa")->fetchAll(PDO::FETCH_ASSOC); }
}