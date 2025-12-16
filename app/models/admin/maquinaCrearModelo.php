<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class MaquinaCrearModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function crearMaquina($datos)
    {
        try {
            $sql = "INSERT INTO maquina (
                        device_id, id_punto, id_tipo_maquina, ultima_visita, estado
                    ) VALUES (
                        :dev, :punto, :tipo, :visita, 1
                    )";
            
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindParam(':dev', $datos['device_id']);
            $stmt->bindParam(':punto', $datos['id_punto'], PDO::PARAM_INT);
            $stmt->bindParam(':tipo', $datos['id_tipo_maquina'], PDO::PARAM_INT);
            
            // Si fecha viene vacía, enviamos NULL
            $visita = !empty($datos['ultima_visita']) ? $datos['ultima_visita'] : null;
            $stmt->bindParam(':visita', $visita);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            // Código 23000 = Violación de índice único (Device ID repetido)
            if ($e->getCode() == '23000') {
                error_log("Device ID duplicado: " . $e->getMessage());
            }
            return false;
        }
    }

    // Helpers para llenar listas
    public function obtenerPuntos() {
        // Traemos nombre punto y cliente para que sea fácil identificar
        $sql = "SELECT p.id_punto, p.nombre_punto, c.nombre_cliente 
                FROM punto p 
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente 
                WHERE p.estado = 1 
                ORDER BY p.nombre_punto ASC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTipos() {
        return $this->conn->query("SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}