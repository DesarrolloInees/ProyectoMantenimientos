<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class tipoNovedadEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Buscar datos actuales para llenar el formulario
    public function obtenerPorId($id)
    {
        try {
            $sql = "SELECT * FROM tipo_novedad WHERE id_tipo_novedad = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Guardar los cambios
    public function actualizarTipoNovedad($id, $nombre, $estado)
    {
        try {
            $sql = "UPDATE tipo_novedad 
                    SET nombre_novedad = :nombre, estado = :estado 
                    WHERE id_tipo_novedad = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Capturamos error si intentan poner un nombre duplicado (si la BD tiene UNIQUE)
            return false;
        }
    }
}