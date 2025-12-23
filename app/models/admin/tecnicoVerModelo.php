<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TecnicoVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerTecnicos()
    {
        // Solo traemos los activos (estado = 1)
        $sql = "SELECT * FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarTecnicoLogicamente($id)
    {
        // Borrado lógico (ocultar)
        $sql = "UPDATE tecnico SET estado = 0 WHERE id_tecnico = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
