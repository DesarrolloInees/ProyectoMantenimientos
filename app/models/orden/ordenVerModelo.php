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
        // 1. CONSULTA MODIFICADA: Agregamos COUNT(DISTINCT o.id_tecnico)
        // IMPORTANTE: Cambia 'o.id_tecnico' por el nombre real de tu columna de técnico/usuario
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

            // 2. PROCESAMIENTO
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

                // Agregamos el nuevo dato 'num_tecnicos' al array
                $datosPorDia[$fecha]['detalles_delegacion'][] = [
                    'nombre' => $row['nombre_delegacion'],
                    'cant'   => $row['cantidad'],
                    'valor'  => $row['valor'],
                    'num_tecnicos' => $row['cantidad_tecnicos'] // <--- NUEVO DATO
                ];
            }

            // 3. GENERACIÓN DE HTML (Aquí es donde lo mostramos visualmente)
            $salidaFinal = [];
            foreach ($datosPorDia as $dia) {

                $htmlDetalle = '<ul class="text-xs text-left space-y-1">';
                foreach ($dia['detalles_delegacion'] as $det) {
                    $precioFmt = number_format($det['valor'], 0, ',', '.');
                    
                    // LÓGICA VISUAL: Agregamos el span de técnicos antes del de servicios
                    // Usé un color naranja suave (orange-100/800) para distinguirlo de los servicios
                    $htmlDetalle .= "
                        <li class='flex justify-between border-b border-gray-100 pb-1 items-center'>
                            <span class='font-bold text-gray-600 w-1/3 truncate' title='{$det['nombre']}'>{$det['nombre']}:</span>
                            <span class='flex items-center space-x-1'>
                                
                                <span class='bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded border border-orange-200' title='Técnicos'>
                                    <i class='fas fa-user-wrench text-[10px] mr-1'></i>{$det['num_tecnicos']}
                                </span>

                                <span class='bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200' title='Servicios'>
                                    {$det['cant']} serv.
                                </span> 
                                
                                <span class='text-green-600 font-medium ml-1'>$$precioFmt</span>
                            </span>
                        </li>";
                }
                $htmlDetalle .= '</ul>';

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