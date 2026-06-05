<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ordenVerModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function listarOrdenesPorFecha()
    {
        $sql = "SELECT 
                    o.fecha_visita,
                    COALESCE(d.nombre_delegacion, 'SIN ASIGNAR') as nombre_delegacion,
                    COUNT(o.id_ordenes_servicio) as cantidad,
                    IFNULL(SUM(o.valor_servicio), 0) as valor,
                    COUNT(DISTINCT o.id_tecnico) as cantidad_tecnicos
                FROM ordenes_servicio o
                LEFT JOIN punto p ON o.id_punto = p.id_punto
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                GROUP BY o.fecha_visita, d.nombre_delegacion
                ORDER BY o.fecha_visita DESC, d.nombre_delegacion ASC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $datosPorDia = [];

            foreach ($resultados as $row) {
                $fecha = $row['fecha_visita'];

                if (!isset($datosPorDia[$fecha])) {
                    $datosPorDia[$fecha] = [
                        'fecha_visita' => $fecha,
                        'cantidad_total' => 0,
                        'valor_total_dia' => 0,
                        'detalles_delegacion' => [] 
                    ];
                }

                $datosPorDia[$fecha]['cantidad_total'] += $row['cantidad'];
                $datosPorDia[$fecha]['valor_total_dia'] += $row['valor'];

                // Guardamos solo los datos crudos, nada de HTML
                $datosPorDia[$fecha]['detalles_delegacion'][] = [
                    'nombre' => $row['nombre_delegacion'],
                    'cant'   => $row['cantidad'],
                    'valor'  => $row['valor'],
                    'num_tecnicos' => $row['cantidad_tecnicos']
                ];
            }

            // Usamos array_values para reindexar el array (DataTables requiere un array indexado, no asociativo por llaves)
            return array_values($datosPorDia);

        } catch (PDOException $e) {
            error_log("Error SQL: " . $e->getMessage());
            return [];
        }
    }
}