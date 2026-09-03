<?php
class importarEstadoMaquinaModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Buscar maquina por device_id con info completa
     */
    public function buscarMaquinaPorDevice($deviceId)
    {
        try {
            $deviceId = trim($deviceId);
            $sql = "SELECT m.id_maquina, m.device_id, m.activo_operativo,
                           p.id_punto, p.nombre_punto, p.zona,
                           c.nombre_cliente,
                           tm.nombre_tipo_maquina
                    FROM maquina m
                    INNER JOIN punto p ON m.id_punto = p.id_punto
                    INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE m.device_id = :id AND m.estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $deviceId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Marcar maquina como fuera de servicio (activo_operativo = 0)
     */
    public function marcarFueraDeServicio($deviceId)
    {
        try {
            $deviceId = trim($deviceId);
            $sql = "UPDATE maquina SET activo_operativo = 0, fecha_actualizacion = NOW() WHERE device_id = :dev AND estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':dev' => $deviceId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Restaurar maquina a operativo (activo_operativo = 1)
     */
    public function restaurarOperativo($deviceId)
    {
        try {
            $deviceId = trim($deviceId);
            $sql = "UPDATE maquina SET activo_operativo = 1, fecha_actualizacion = NOW() WHERE device_id = :dev";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':dev' => $deviceId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Obtener todas las maquinas fuera de servicio con info del punto
     */
    public function obtenerMaquinasFueraDeServicio()
    {
        try {
            $sql = "SELECT m.id_maquina, m.device_id, 
                           p.id_punto, p.nombre_punto, p.zona, p.direccion,
                           c.nombre_cliente,
                           tm.nombre_tipo_maquina
                    FROM maquina m
                    INNER JOIN punto p ON m.id_punto = p.id_punto
                    INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE m.activo_operativo = 0 AND m.estado = 1 AND p.estado = 1
                    ORDER BY p.zona ASC, c.nombre_cliente ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
