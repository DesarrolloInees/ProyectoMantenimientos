<?php
class ReporteFacturacionModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerDatosFiltrados($fechaInicio, $fechaFin, $categoria)
    {
        // Armamos la consulta dinámica según los filtros que lleguen
        $sql = "SELECT r.*, u.nombre 
                FROM registros_excel r
                LEFT JOIN usuarios u ON r.id_usuario = u.id
                WHERE u.estado = 'activo' "; // Siempre activos

        $params = [];

        if (!empty($fechaInicio) && !empty($fechaFin)) {
            // Sincronizado estrictamente con los parámetros del Excel
            $sql .= " AND r.fecha_realizacion BETWEEN :ini AND :fin";
            $params[':ini'] = $fechaInicio;
            $params[':fin'] = $fechaFin;
        }

        if (!empty($categoria)) {
            $sql .= " AND r.categoria = :cat";
            $params[':cat'] = $categoria;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>