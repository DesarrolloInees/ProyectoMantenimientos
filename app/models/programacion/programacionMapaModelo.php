<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class programacionMapaModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerDelegaciones()
    {
        $stmt = $this->conn->prepare("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerZonasPorDelegacion($id_delegacion)
    {
        $sql = "SELECT DISTINCT zona FROM punto WHERE id_delegacion = :id_delegacion AND zona IS NOT NULL AND zona != '' ORDER BY zona ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_delegacion' => $id_delegacion]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Consulta adaptada para el mapa: Trae latitud y longitud
    public function obtenerPuntosMapa($id_delegacion, $zonas)
    {
        if (!is_array($zonas)) {
            $zonas = array_map('trim', explode(',', $zonas));
        }
        $zonas = array_values(array_filter($zonas));
        if (empty($zonas)) return [];

        $placeholdersZona = [];
        $params = [':delegacion' => $id_delegacion];

        foreach ($zonas as $k => $z) {
            $key = ':zona_' . $k;
            $placeholdersZona[] = $key;
            $params[$key] = $z;
        }

        // Se agregaron p.latitud y p.longitud a la consulta
        $sql = "SELECT p.id_punto, p.nombre_punto, p.direccion, p.zona, 
                        p.latitud, p.longitud, c.nombre_cliente
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                WHERE p.estado = 1 
                    AND p.id_delegacion = :delegacion
                    AND p.zona IN (" . implode(',', $placeholdersZona) . ")
                    AND NOT EXISTS (
                        SELECT 1 FROM ordenes_servicio os 
                        WHERE os.id_punto = p.id_punto AND os.estado = 2
                    )";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}