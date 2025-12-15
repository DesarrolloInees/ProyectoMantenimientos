<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TarifaCrearModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    // Función principal para guardar
    public function crearTarifa($datos)
    {
        try {
            $sql = "INSERT INTO tarifa (id_tipo_maquina, id_tipo_mantenimiento, id_modalidad, precio, año_vigencia) 
                    VALUES (:maquina, :mantenimiento, :modalidad, :precio, :anio)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':maquina', $datos['id_tipo_maquina'], PDO::PARAM_INT);
            $stmt->bindParam(':mantenimiento', $datos['id_tipo_mantenimiento'], PDO::PARAM_INT);
            $stmt->bindParam(':modalidad', $datos['id_modalidad'], PDO::PARAM_INT);
            $stmt->bindParam(':precio', $datos['precio']);
            $stmt->bindParam(':anio', $datos['año_vigencia'], PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error crear tarifa: " . $e->getMessage());
            return false;
        }
    }

    // --- Helpers para llenar los Selects ---
    public function obtenerTiposMaquina() {
        return $this->conn->query("SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerTiposMantenimiento() {
        return $this->conn->query("SELECT * FROM tipo_mantenimiento WHERE estado = 1 ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerModalidades() {
        return $this->conn->query("SELECT * FROM modalidad_operativa ORDER BY nombre_modalidad ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}