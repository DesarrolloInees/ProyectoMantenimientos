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
        // 1. CONSULTA DETALLADA: Agrupamos por Fecha Y Delegación
        $sql = "SELECT 
                    o.fecha_visita,
                    COALESCE(d.nombre_delegacion, 'SIN ASIGNAR') as nombre_delegacion,
                    COUNT(o.id_ordenes_servicio) as cantidad,
                    IFNULL(SUM(o.valor_servicio), 0) as valor
                FROM ordenes_servicio o
                LEFT JOIN punto p ON o.id_punto = p.id_punto
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                GROUP BY o.fecha_visita, d.nombre_delegacion
                ORDER BY o.fecha_visita DESC, d.nombre_delegacion ASC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. PROCESAMIENTO EN PHP (Agrupar por días para el DataTable)
            $datosPorDia = [];

            foreach ($resultados as $row) {
                $fecha = $row['fecha_visita'];

                // Si la fecha no existe en el array, la inicializamos
                if (!isset($datosPorDia[$fecha])) {
                    $datosPorDia[$fecha] = [
                        'fecha_visita' => $fecha,
                        'cantidad_total' => 0,
                        'valor_total_dia' => 0,
                        'detalles_delegacion' => [] // Aquí guardaremos el desglose
                    ];
                }

                // Acumulamos los totales generales
                $datosPorDia[$fecha]['cantidad_total'] += $row['cantidad'];
                $datosPorDia[$fecha]['valor_total_dia'] += $row['valor'];

                // Agregamos el detalle de esta delegación específica
                $datosPorDia[$fecha]['detalles_delegacion'][] = [
                    'nombre' => $row['nombre_delegacion'],
                    'cant' => $row['cantidad'],
                    'valor' => $row['valor']
                ];
            }

            // 3. CONVERTIR A ARRAY INDEXADO (Para que JSON lo lea bien) y formatear HTML
            $salidaFinal = [];
            foreach ($datosPorDia as $dia) {

                // Creamos un HTML bonito para el detalle
                $htmlDetalle = '<ul class="text-xs text-left space-y-1">';
                foreach ($dia['detalles_delegacion'] as $det) {
                    $precioFmt = number_format($det['valor'], 0, ',', '.');
                    $htmlDetalle .= "
                        <li class='flex justify-between border-b border-gray-100 pb-1'>
                            <span class='font-bold text-gray-600'>{$det['nombre']}:</span>
                            <span>
                                <span class='bg-gray-100 px-1 rounded'>{$det['cant']} serv.</span> 
                                <span class='text-green-600 ml-1'>$$precioFmt</span>
                            </span>
                        </li>";
                }
                $htmlDetalle .= '</ul>';

                // Agregamos el campo html_detalle al objeto
                $dia['html_detalle'] = $htmlDetalle;

                $salidaFinal[] = $dia;
            }

            return $salidaFinal;
        } catch (PDOException $e) {
            error_log("Error SQL: " . $e->getMessage());
            return [];
        }
    }
}
