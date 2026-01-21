<?php
class importarMunicipiosModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- 1. BUSCAR PUNTO POR DEVICE ID (LA LLAVE MAESTRA) ---
    public function obtenerPuntoPorDevice($deviceId)
    {
        try {
            $deviceId = trim($deviceId);
            // Buscamos en la tabla maquina que relacionaste
            $sql = "SELECT id_punto FROM maquina WHERE device_id = :dev LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':dev' => $deviceId]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? $res['id_punto'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    // --- 2. ACTUALIZAR UBICACIÓN Y ZONA ---
    // Agregamos el parámetro $nombreZona
    public function actualizarUbicacionPunto($idPunto, $idMunicipio, $nombreZona)
    {
        try {
            // Aseguramos que si la zona viene vacía, se guarde NULL
            $nombreZona = !empty($nombreZona) ? mb_strtoupper($nombreZona, 'UTF-8') : null;

            // ACTUALIZAMOS id_municipio Y TAMBIÉN LA COLUMNA zona
            $sql = "UPDATE punto 
                    SET id_municipio = :mun, 
                        zona = :zon,
                        fecha_actualizacion = NOW() 
                    WHERE id_punto = :id";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':mun' => $idMunicipio, 
                ':zon' => $nombreZona,  // <--- ¡Aquí guardamos el dato que faltaba!
                ':id'  => $idPunto
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // --- (MANTENEMOS LAS OTRAS FUNCIONES DE CATALOGO) ---
    public function obtenerIdDelegacion($nombreDelegacion)
    {
        try {
            $nombre = trim(mb_strtoupper($nombreDelegacion, 'UTF-8'));
            if(empty($nombre)) return null;
            $sql = "SELECT id_delegacion FROM delegacion WHERE nombre_delegacion LIKE :nom LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => $nombre]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? $res['id_delegacion'] : null;
        } catch (PDOException $e) { return null; }
    }

    

    public function gestionarMunicipio($nombreMunicipio, $idDelegacion)
    {
        try {
            $nombre = trim(mb_strtoupper($nombreMunicipio, 'UTF-8'));
            if (empty($nombre) || empty($idDelegacion)) return false;

            $sql = "SELECT id_municipio FROM municipio 
                    WHERE nombre_municipio = :nom AND id_delegacion = :del LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => $nombre, ':del' => $idDelegacion]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res) return $res['id_municipio'];

            $sqlInsert = "INSERT INTO municipio (nombre_municipio, id_delegacion, estado) VALUES (:nom, :del, 1)";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->execute([':nom' => $nombre, ':del' => $idDelegacion]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) { return false; }
    }
}

?>