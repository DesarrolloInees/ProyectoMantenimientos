<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteFacturacionPdfModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerDatosFiltrados($filtros)
    {
        // 1. Iniciamos la consulta base. 
        // Si cruzamos con la tabla de técnicos/usuarios que subieron la info, 
        // garantizamos que solo se traiga la data de usuarios activos.
        $sql = "SELECT r.* FROM control_cotizaciones r
                /* LEFT JOIN usuarios u ON r.id_usuario = u.usuario_id */
                WHERE 1=1 
                /* AND (u.estado = 'activo' OR u.estado IS NULL) */";

        $params = [];

        // 2. Filtros Dinámicos
        
        // Filtro de Fechas (Sincronizado estrictamente con los parámetros del Excel, 
        // descartando lógicas de días festivos para evitar saltos en el reporte)
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $sql .= " AND r.fecha_realizacion BETWEEN :ini AND :fin";
            $params[':ini'] = $filtros['fecha_inicio'];
            $params[':fin'] = $filtros['fecha_fin'];
        }

        // Filtro por Estado (ej: FACTURADO, PENDIENTE)
        if (!empty($filtros['estado'])) {
            $sql .= " AND r.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        // Filtro por Categoría (ej: MQ, RP)
        if (!empty($filtros['categoria'])) {
            $sql .= " AND r.categoria = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }

        // Filtro por Rango de Precio (Subtotal)
        if (!empty($filtros['precio_min'])) {
            $sql .= " AND r.subtotal >= :pmin";
            $params[':pmin'] = $filtros['precio_min'];
        }
        if (!empty($filtros['precio_max'])) {
            $sql .= " AND r.subtotal <= :pmax";
            $params[':pmax'] = $filtros['precio_max'];
        }

        // Filtro por Número de Máquina o Cotización (Búsqueda exacta o parcial)
        if (!empty($filtros['referencia'])) {
            $sql .= " AND (r.n_cotizacion LIKE :ref OR r.n_remision LIKE :ref)";
            $params[':ref'] = '%' . $filtros['referencia'] . '%';
        }

        $sql .= " ORDER BY r.fecha_realizacion DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error filtrando Excel: " . $e->getMessage());
            return [];
        }
    }
}
?>