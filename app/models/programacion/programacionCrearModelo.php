<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class programacionCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerDelegaciones()
    {
        return $this->conn->query("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTiposMantenimiento()
    {
        return $this->conn->query("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento WHERE estado = 1")->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- NUEVO: Obtener las zonas/localidades que existen REALMENTE en los puntos de esa delegación ---
    public function obtenerZonasPorDelegacion($id_delegacion)
    {
        try {
            // Buscamos las zonas distintas (DISTINCT) que ya están escritas en la tabla punto
            $sql = "SELECT DISTINCT zona FROM punto WHERE id_delegacion = :id AND zona IS NOT NULL AND zona != '' ORDER BY zona ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $id_delegacion]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // --- MODIFICADO: Obtener Técnicos (Opcionalmente filtrados, pero no estricto) ---
    // Si tu tabla técnico no tiene campo 'ruta', usaremos el ID como ejemplo.
    // Lo ideal sería: ALTER TABLE tecnico ADD COLUMN codigo_ruta VARCHAR(50);
    public function obtenerTecnicos()
    {
        try {
            // SIN id_delegacion - usa COALESCE o quita el campo
            $sql = "SELECT id_tecnico, nombre_tecnico, 
                /* Si no hay delegación, usa 0 o NULL */
                0 as id_delegacion,
                CONCAT('Ruta ', id_tecnico, ' - ', nombre_tecnico) as etiqueta_tecnico
                FROM tecnico 
                WHERE estado = 1
                ORDER BY nombre_tecnico ASC";

            return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerTecnicos: " . $e->getMessage());
            return [];
        }
    }

    // Método único para traer máquinas reales
    public function obtenerPuntosParaAsignar($id_delegacion, $zonas = []) {
        $sql = "SELECT m.id_maquina, m.device_id, p.nombre_punto, p.direccion, 
                       mun.nombre_municipio, p.zona 
                FROM maquina m
                JOIN punto p ON m.id_punto = p.id_punto
                JOIN municipio mun ON p.id_municipio = mun.id_municipio
                WHERE p.id_delegacion = ? AND m.estado = 1 AND p.estado = 1";

        $params = [$id_delegacion];

        // Filtro opcional por zonas (checkboxes)
        if (!empty($zonas)) {
            $in = str_repeat('?,', count($zonas) - 1) . '?';
            $sql .= " AND p.zona IN ($in)";
            $params = array_merge($params, $zonas);
        }

        // Ordenar geográficamente para que el técnico no salte de lado a lado
        $sql .= " ORDER BY p.zona ASC, mun.nombre_municipio ASC, p.direccion ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Método auxiliar para recuperar nombres de técnicos (para el select editable)
    public function obtenerNombresTecnicos($ids) {
        if(empty($ids)) return [];
        $in = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->conn->prepare("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE id_tecnico IN ($in)");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna array [id => nombre]
    }
}

