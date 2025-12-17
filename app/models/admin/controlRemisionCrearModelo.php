<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ControlRemisionCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Para llenar el Select en la vista
    public function obtenerTecnicos()
    {
        $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear una sola remisión
    public function crearRemision($datos)
    {
        try {
            $sql = "INSERT INTO control_remisiones (
                        numero_remision, 
                        id_tecnico, 
                        estado, 
                        fecha_asignacion
                    ) VALUES (
                        :numero, 
                        :id_tecnico, 
                        'DISPONIBLE', 
                        NOW()
                    )";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':numero', $datos['numero_remision']);
            $stmt->bindParam(':id_tecnico', $datos['id_tecnico']);

            return $stmt->execute();

        } catch (PDOException $e) {
            // Error 23000: Violación de restricción única (Número repetido)
            if ($e->getCode() == '23000') {
                return "DUPLICADO"; 
            } else {
                error_log("Error al crear remisión: " . $e->getMessage());
                return false;
            }
        }
    }

    // Obtener la última remisión registrada de un técnico (para sugerir la siguiente)
    public function obtenerUltimaRemision($id_tecnico)
    {
        try {
            $sql = "SELECT numero_remision 
                    FROM control_remisiones 
                    WHERE id_tecnico = :id_tecnico 
                    -- Ordenamos convirtiendo a número para evitar que '9' sea mayor que '10'
                    ORDER BY CAST(numero_remision AS UNSIGNED) DESC 
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_tecnico', $id_tecnico);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si encuentra algo devuelve el número, si no, devuelve 0
            return $resultado ? $resultado['numero_remision'] : 0;

        } catch (PDOException $e) {
            return 0;
        }
    }
}