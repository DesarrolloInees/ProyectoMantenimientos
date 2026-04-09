<?php
class importarMunicipiosModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- 1. OBTENER DATOS ACTUALES DEL PUNTO PARA LA SIMULACIÓN ---
    public function obtenerDatosPuntoPorDevice($deviceId)
    {
        try {
            $deviceId = trim($deviceId);
            // Hacemos JOIN para traer el nombre del punto y su zona actual
            $sql = "SELECT p.id_punto, p.nombre_punto, p.zona as zona_actual 
                    FROM maquina m
                    INNER JOIN punto p ON m.id_punto = p.id_punto
                    WHERE m.device_id = :dev LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':dev' => $deviceId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    // --- 2. ACTUALIZAR ÚNICAMENTE LA ZONA ---
    public function actualizarSoloZona($idPunto, $nombreZona)
    {
        try {
            $nombreZona = !empty($nombreZona) ? mb_strtoupper($nombreZona, 'UTF-8') : null;

            $sql = "UPDATE punto 
                    SET zona = :zon,
                        fecha_actualizacion = NOW() 
                    WHERE id_punto = :id";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':zon' => $nombreZona,
                ':id'  => $idPunto
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>