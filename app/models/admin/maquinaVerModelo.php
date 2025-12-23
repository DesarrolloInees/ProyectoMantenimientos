<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class MaquinaVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerMaquinas()
    {
        // JOIN para obtener el nombre del punto y el tipo
        $sql = "SELECT 
                    m.id_maquina, m.device_id, m.ultima_visita, m.estado,
                    p.nombre_punto,
                    tm.nombre_tipo_maquina
                FROM maquina m
                INNER JOIN punto p ON m.id_punto = p.id_punto
                INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE m.estado = 1
                ORDER BY m.id_maquina DESC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarMaquinaLogicamente($id)
    {
        $stmt = $this->conn->prepare("UPDATE maquina SET estado = 0 WHERE id_maquina = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
