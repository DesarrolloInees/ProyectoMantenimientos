<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class programacionPrevisualizarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // 1. TRAER MÁQUINAS REALES SEGÚN FILTROS
    public function obtenerPuntosCandidatos($filtros)
    {
        try {
            // Seleccionamos ID máquina, info del punto y ubicación
            $sql = "SELECT m.id_maquina, m.device_id, p.nombre_punto, p.direccion, 
                           mun.nombre_municipio, p.zona 
                    FROM maquina m
                    JOIN punto p ON m.id_punto = p.id_punto
                    JOIN municipio mun ON p.id_municipio = mun.id_municipio
                    WHERE p.id_delegacion = :id_delegacion 
                    AND m.estado = 1 
                    AND p.estado = 1";

            $params = [':id_delegacion' => $filtros['id_delegacion']];

            // Filtro Zonas (Si seleccionaron algunas específicas)
            if (!empty($filtros['zonas'])) {
                // Truco para IN clause con array
                $inQuery = implode(',', array_fill(0, count($filtros['zonas']), '?'));
                $sql .= " AND p.zona IN ($inQuery)";
                // Agregamos las zonas al array de parámetros (sin claves para el execute)
                // Nota: Esto requiere manejar el array de params con cuidado, 
                // pero para simplificar, PDO permite bindear arrays indexados si la query tiene ?
                // Reescribimos para usar ? en todo si hay zonas:
                
                $sql = "SELECT m.id_maquina, m.device_id, p.nombre_punto, p.direccion, 
                           mun.nombre_municipio, p.zona 
                    FROM maquina m
                    JOIN punto p ON m.id_punto = p.id_punto
                    JOIN municipio mun ON p.id_municipio = mun.id_municipio
                    WHERE p.id_delegacion = ? 
                    AND m.estado = 1 
                    AND p.estado = 1";
                
                $params = [$filtros['id_delegacion']];
                $params = array_merge($params, $filtros['zonas']);
                $sql .= " AND p.zona IN ($inQuery)";
            }

            // Ordenar por cercanía lógica (Zona -> Municipio -> Dirección)
            $sql .= " ORDER BY p.zona ASC, mun.nombre_municipio ASC, p.direccion ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }

    // 2. OBTENER LISTA DE TÉCNICOS (Para el Select Editable)
    public function obtenerNombresTecnicos($ids)
    {
        if (empty($ids)) return [];
        $in = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE id_tecnico IN ($in) ORDER BY nombre_tecnico ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Devuelve [id => "Nombre"]
    }
}