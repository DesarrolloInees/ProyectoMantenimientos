<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class informacionBaseDatosModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // 1. Resumen General (KPIs rápidos)
    public function getResumenGeneral()
    {
        try {
            // Hacemos varias subconsultas en una sola llamada para ser eficientes
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM maquina) as total_maquinas,
                        (SELECT COUNT(*) FROM maquina WHERE estado = 1) as maquinas_activas,
                        (SELECT COUNT(*) FROM punto) as total_puntos,
                        (SELECT COUNT(*) FROM delegacion) as total_delegaciones
                    ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['total_maquinas' => 0, 'maquinas_activas' => 0];
        }
    }

    // 2. Inventario: Máquinas por Delegación y Tipo
    public function getInventarioPorDelegacionYTipo()
    {
        try {
            $sql = "SELECT 
                        d.nombre_delegacion, 
                        tm.nombre_tipo_maquina, 
                        COUNT(m.id_maquina) as total
                    FROM maquina m
                    INNER JOIN punto p ON m.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE m.estado = 1  -- Solo contamos las activas
                    GROUP BY d.nombre_delegacion, tm.nombre_tipo_maquina
                    ORDER BY d.nombre_delegacion ASC, total DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getInventario: " . $e->getMessage());
            return [];
        }
    }
    
    // 3. Top Municipios con más máquinas (Extra: para ver 'más cositas')
    // NUEVO: Top Clientes con más máquinas
    // Asumo que tu tabla se llama 'cliente' y tiene un campo 'nombre_cliente' o 'nombre'
    public function getTopClientes()
    {
        try {
            $sql = "SELECT c.nombre_cliente, COUNT(m.id_maquina) as total
                    FROM maquina m
                    INNER JOIN punto p ON m.id_punto = p.id_punto
                    INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                    WHERE m.estado = 1
                    GROUP BY c.nombre_cliente
                    ORDER BY total DESC 
                    LIMIT 10";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Si falla (ej. si el campo no es nombre_cliente), devuelve array vacío
            return []; 
        }
    }

    // NUEVO: Estado de Salud (Antigüedad de la última visita)
    public function getAntiguedadVisitas()
    {
        try {
            $sql = "SELECT 
                        CASE 
                            WHEN fecha_ultima_visita IS NULL THEN 'Sin Visita Registrada'
                            WHEN fecha_ultima_visita >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 'Al día (< 30 días)'
                            WHEN fecha_ultima_visita >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 'Hace 1-3 meses'
                            ELSE 'Olvidada (> 3 meses)'
                        END as rango,
                        COUNT(*) as total
                    FROM punto
                    WHERE estado = 1
                    GROUP BY rango
                    ORDER BY total DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}