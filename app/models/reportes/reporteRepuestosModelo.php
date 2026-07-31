<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class ReporteRepuestosModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Método para KPIs
    public function getKpisInventario($fechaDesde = null, $fechaHasta = null)
    {
        $sql = "SELECT 
                COALESCE(SUM(i.cantidad_actual), 0) as total_piezas,
                COALESCE(COUNT(DISTINCT i.id_tecnico), 0) as tecnicos_con_stock,
                COALESCE(COUNT(DISTINCT i.id_repuesto), 0) as referencias_distintas,
                COALESCE(SUM(CASE WHEN i.cantidad_actual = 0 THEN 1 ELSE 0 END), 0) as repuestos_agotados
            FROM inventario_tecnico i
            WHERE i.estado = 1";

        if ($fechaDesde) {
            $sql .= " AND i.ultima_actualizacion >= :fecha_desde";
        }
        if ($fechaHasta) {
            $sql .= " AND i.ultima_actualizacion <= :fecha_hasta";
        }

        $stmt = $this->conn->prepare($sql);
        if ($fechaDesde)
            $stmt->bindParam(':fecha_desde', $fechaDesde);
        if ($fechaHasta)
            $stmt->bindParam(':fecha_hasta', $fechaHasta);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Método para consolidado
    public function getConsolidadoRepuestos($fechaDesde = null, $fechaHasta = null)
    {
        $sql = "SELECT 
                r.nombre_repuesto,
                r.codigo_referencia,
                COUNT(DISTINCT i.id_tecnico) as tecnicos_lo_tienen,
                SUM(i.cantidad_actual) as total_asignado
            FROM inventario_tecnico i
            INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
            WHERE i.estado = 1";

        if ($fechaDesde) {
            $sql .= " AND i.ultima_actualizacion >= :fecha_desde";
        }
        if ($fechaHasta) {
            $sql .= " AND i.ultima_actualizacion <= :fecha_hasta";
        }

        $sql .= " GROUP BY r.id_repuesto
              ORDER BY total_asignado DESC, r.nombre_repuesto ASC";

        $stmt = $this->conn->prepare($sql);
        if ($fechaDesde)
            $stmt->bindParam(':fecha_desde', $fechaDesde);
        if ($fechaHasta)
            $stmt->bindParam(':fecha_hasta', $fechaHasta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para inventario por técnico
    public function getInventarioPorTecnico($fechaDesde = null, $fechaHasta = null)
    {
        $sql = "SELECT 
                t.nombre_tecnico,
                r.nombre_repuesto,
                r.codigo_referencia,
                i.cantidad_actual
            FROM inventario_tecnico i
            INNER JOIN tecnico t ON i.id_tecnico = t.id_tecnico
            INNER JOIN repuesto r ON i.id_repuesto = r.id_repuesto
            WHERE i.estado = 1";

        if ($fechaDesde) {
            $sql .= " AND i.ultima_actualizacion >= :fecha_desde";
        }
        if ($fechaHasta) {
            $sql .= " AND i.ultima_actualizacion <= :fecha_hasta";
        }

        $sql .= " ORDER BY t.nombre_tecnico ASC, r.nombre_repuesto ASC";

        $stmt = $this->conn->prepare($sql);
        if ($fechaDesde)
            $stmt->bindParam(':fecha_desde', $fechaDesde);
        if ($fechaHasta)
            $stmt->bindParam(':fecha_hasta', $fechaHasta);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por técnico
        $agrupado = [];
        foreach ($resultados as $fila) {
            $tecnico = $fila['nombre_tecnico'];
            if (!isset($agrupado[$tecnico])) {
                $agrupado[$tecnico] = [];
            }
            $agrupado[$tecnico][] = [
                'nombre_repuesto' => $fila['nombre_repuesto'],
                'codigo_referencia' => $fila['codigo_referencia'],
                'cantidad_actual' => $fila['cantidad_actual']
            ];
        }
        return $agrupado;
    }
}