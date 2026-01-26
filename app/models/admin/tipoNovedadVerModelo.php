<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoNovedadVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerTodos()
    {
        try {
            // CAMBIO: Agregamos WHERE estado = 1 para traer solo los activos
            $sql = "SELECT id_tipo_novedad, nombre_novedad, estado 
                    FROM tipo_novedad 
                    WHERE estado = 1 
                    ORDER BY id_tipo_novedad DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener tipos de novedad: " . $e->getMessage());
            return [];
        }
    }
}