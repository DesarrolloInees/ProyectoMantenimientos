<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TipoMantenimientoVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerTipos()
    {
        $sql = "SELECT * FROM tipo_mantenimiento WHERE estado = 1 ORDER BY nombre_completo ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarTipoLogicamente($id)
    {
        $sql = "UPDATE tipo_mantenimiento SET estado = 0 WHERE id_tipo_mantenimiento = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
