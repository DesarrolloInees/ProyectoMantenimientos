<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class MaquinaEditarModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerMaquinaPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM maquina WHERE id_maquina = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarMaquina($id, $datos)
    {
        try {
            $sql = "UPDATE maquina SET 
                    device_id = :dev, 
                    id_punto = :punto, 
                    id_tipo_maquina = :tipo, 
                    ultima_visita = :visita,
                    estado = :est
                    WHERE id_maquina = :id";
            
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindParam(':dev', $datos['device_id']);
            $stmt->bindParam(':punto', $datos['id_punto'], PDO::PARAM_INT);
            $stmt->bindParam(':tipo', $datos['id_tipo_maquina'], PDO::PARAM_INT);
            
            $visita = !empty($datos['ultima_visita']) ? $datos['ultima_visita'] : null;
            $stmt->bindParam(':visita', $visita);
            
            $stmt->bindParam(':est', $datos['estado'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtenerPuntos() { return $this->conn->query("SELECT p.id_punto, p.nombre_punto, c.nombre_cliente FROM punto p INNER JOIN cliente c ON p.id_cliente = c.id_cliente WHERE p.estado = 1 ORDER BY p.nombre_punto ASC")->fetchAll(PDO::FETCH_ASSOC); }
    public function obtenerTipos() { return $this->conn->query("SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC")->fetchAll(PDO::FETCH_ASSOC); }
}