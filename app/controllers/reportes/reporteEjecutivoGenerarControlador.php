<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/ReporteEjecutivoModelo.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class generarReporteControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteEjecutivoModelo($this->db);
    }

    public function index()
    {
        // 1. Obtener Fechas
        $inicio = $_GET['inicio'] ?? date('Y-m-01');
        $fin    = $_GET['fin'] ?? date('Y-m-t');

        // 2. Obtener Datos
        $datosDia        = $this->modelo->getServiciosPorDia($inicio, $fin);
        $datosDelegacion = $this->modelo->getDelegacionesIntervenidas($inicio, $fin);
        $datosTipo       = $this->modelo->getPorTipoMantenimiento($inicio, $fin);
        $datosNovedad    = $this->modelo->getDistribucionNovedades($inicio, $fin);
        $datosTecnico    = $this->modelo->getHorasVsServicios($inicio, $fin);
        $datosEstado     = $this->modelo->getServiciosFallidos($inicio, $fin);
        $datosRepuestos  = $this->modelo->getComparativaRepuestos($inicio, $fin);

        // NUEVOS DATOS
        $datosPuntosMasVisitados = $this->modelo->getPuntosMasVisitados($inicio, $fin);
        $datosPuntosFallidos = $this->modelo->getPuntosConFallidos($inicio, $fin);
        $datosCalificaciones = $this->modelo->getCalificacionesServicio($inicio, $fin);
        $datosTiposMaquina = $this->modelo->getTiposMaquinaPorDelegacion($inicio, $fin);
        $datosFallidosPorDelegacion = $this->modelo->getServicesFallidosPorDelegacion($inicio, $fin);

        // 3. Generar Gráficas con diseño mejorado
        $graficas = [];

        // Gráfica de línea para evolución diaria (degradado azul)
        $graficas['dias'] = $this->generarGraficaLinea($datosDia);

        // Top 10 Delegaciones (más legible que todas)
        $topDelegaciones = array_slice($datosDelegacion, 0, 10);
        $graficas['delegaciones'] = $this->generarGraficaBarrasHorizontal($topDelegaciones);

        // Pie chart mejorado para tipos
        $graficas['tipo'] = $this->generarGraficaPie(
            $datosTipo,
            'tipo',
            'Distribución por Tipo de Mantenimiento'
        );

        // Top 15 Técnicos con colores degradados
        usort($datosTecnico, function ($a, $b) {
            return $b['total_servicios'] - $a['total_servicios'];
        });
        $topTecnicos = array_slice($datosTecnico, 0, 15);
        $graficas['tecnicos'] = $this->generarGraficaTecnicos($topTecnicos);

        // Estados con colores específicos
        $graficas['estados'] = $this->generarGraficaEstados($datosEstado);

        // Doughnut para repuestos
        $graficas['repuestos'] = $this->generarGraficaRepuestos($datosRepuestos);

        // NUEVAS GRÁFICAS
        $graficas['puntos_visitados'] = $this->generarGraficaPuntosVisitados($datosPuntosMasVisitados);
        $graficas['puntos_fallidos'] = $this->generarGraficaPuntosFallidos($datosPuntosFallidos);
        $graficas['calificaciones'] = $this->generarGraficaCalificaciones($datosCalificaciones);
        $graficas['fallidos_delegacion'] = $this->generarGraficaFallidosDelegacion($datosFallidosPorDelegacion);

        // 4. Calcular KPIs
        $totalServicios = array_sum(array_column($datosDia, 'total'));

        // Calcular días del período
        $fecha1 = new DateTime($inicio);
        $fecha2 = new DateTime($fin);
        $cantidadDias = $fecha1->diff($fecha2)->days + 1;

        $numTecnicos = count($datosTecnico);

        // Media Global por técnico
        $mediaServicios = $numTecnicos > 0 ? round($totalServicios / $numTecnicos, 1) : 0;

        // Media Diaria por técnico
        $mediaDiaria = ($numTecnicos > 0 && $cantidadDias > 0)
            ? round($totalServicios / ($numTecnicos * $cantidadDias), 2)
            : 0;

        // 5. Renderizar PDF
        ob_start();
        include __DIR__ . '/../../views/reportes/reporteEjecutivoGenerar.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $dompdf->stream("Reporte_Ejecutivo_" . date('Ymd_Hi') . ".pdf", ["Attachment" => false]);
    }

    private function generarGraficaLinea($datos)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_map(function ($d) {
            return date('d/m', strtotime($d['fecha_visita']));
        }, $datos);

        $data = array_map('intval', array_column($datos, 'total'));

        $config = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Servicios',
                    'data' => $data,
                    'backgroundColor' => 'rgba(102, 126, 234, 0.2)',
                    'borderColor' => 'rgb(102, 126, 234)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(102, 126, 234)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4
                ]]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => [
                        'display' => false
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => ['color' => 'rgba(0,0,0,0.05)']
                    ],
                    'x' => [
                        'grid' => ['display' => false]
                    ]
                ]
            ]
        ];

        return $this->enviarAQuickChart($config, 800, 300);
    }

    private function generarGraficaBarrasHorizontal($datos)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_column($datos, 'nombre_delegacion');
        $data = array_map('intval', array_column($datos, 'total'));

        // Colores degradados
        $colores = $this->generarGradienteColores(count($data), '#667eea', '#764ba2');

        $config = [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Servicios',
                    'data' => $data,
                    'backgroundColor' => $colores,
                    'borderWidth' => 0
                ]]
            ],
            'options' => [
                'indexAxis' => 'y',
                'plugins' => [
                    'legend' => ['display' => false],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#fff',
                        'anchor' => 'end',
                        'align' => 'start',
                        'offset' => 4,
                        'font' => ['weight' => 'bold', 'size' => 11]
                    ]
                ],
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'grid' => ['color' => 'rgba(0,0,0,0.05)']
                    ],
                    'y' => [
                        'grid' => ['display' => false]
                    ]
                ]
            ]
        ];

        return $this->enviarAQuickChart($config, 700, 400);
    }

    private function generarGraficaPie($datos, $campoLabel, $titulo)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_column($datos, $campoLabel);
        $data = array_map('intval', array_column($datos, 'total'));

        $colores = [
            'rgba(102, 126, 234, 0.8)',  // Azul
            'rgba(240, 147, 251, 0.8)',  // Rosa
            'rgba(250, 112, 154, 0.8)',  // Rosa fuerte
            'rgba(48, 207, 208, 0.8)',   // Cyan
            'rgba(254, 202, 87, 0.8)',   // Amarillo
            'rgba(72, 219, 251, 0.8)'    // Azul claro
        ];

        $config = [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $colores,
                    'borderColor' => '#fff',
                    'borderWidth' => 3
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'position' => 'right',
                        'labels' => ['padding' => 15, 'font' => ['size' => 12]]
                    ],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#fff',
                        'font' => ['weight' => 'bold', 'size' => 13],
                        'formatter' => '(value, ctx) => { 
                            let sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value * 100) / sum);
                            return percentage + "%";
                        }'
                    ]
                ]
            ]
        ];

        return $this->enviarAQuickChart($config, 600, 350);
    }

    private function generarGraficaTecnicos($datos)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_column($datos, 'nombre_tecnico');
        $data = array_map('intval', array_column($datos, 'total_servicios'));

        // Degradado de colores para técnicos
        $colores = $this->generarGradienteColores(count($data), '#667eea', '#764ba2');

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Servicios',
                    'data' => $data,
                    'backgroundColor' => $colores,
                    'borderWidth' => 0,
                    'borderRadius' => 6
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false],
                    'datalabels' => [
                        'display' => true,
                        'anchor' => 'end',
                        'align' => 'top',
                        'color' => '#34495e',
                        'font' => ['weight' => 'bold', 'size' => 11]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => ['color' => 'rgba(0,0,0,0.05)']
                    ],
                    'x' => [
                        'grid' => ['display' => false],
                        'ticks' => [
                            'maxRotation' => 45,
                            'minRotation' => 45,
                            'font' => ['size' => 10]
                        ]
                    ]
                ]
            ]
        ];

        return $this->enviarAQuickChart($config, 900, 400);
    }

    private function generarGraficaEstados($datos)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_column($datos, 'nombre_estado');
        $data = array_map('intval', array_column($datos, 'total'));

        // Colores específicos para estados
        $coloresEstados = [
            'rgba(46, 213, 115, 0.8)',   // Verde - Completado
            'rgba(255, 234, 167, 0.8)',  // Amarillo - Pendiente
            'rgba(255, 118, 117, 0.8)',  // Rojo - Fallido
            'rgba(162, 155, 254, 0.8)',  // Morado
            'rgba(253, 203, 110, 0.8)',  // Naranja
            'rgba(89, 98, 117, 0.8)'     // Gris
        ];

        $config = [
            'type' => 'pie',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $coloresEstados,
                    'borderColor' => '#fff',
                    'borderWidth' => 3
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'position' => 'right',
                        'labels' => ['padding' => 15, 'font' => ['size' => 12]]
                    ],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#fff',
                        'font' => ['weight' => 'bold', 'size' => 13],
                        'formatter' => '(value, ctx) => { 
                            let sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value * 100) / sum);
                            return percentage + "%";
                        }'
                    ]
                ]
            ]
        ];

        return $this->enviarAQuickChart($config, 600, 350);
    }

    private function generarGraficaRepuestos($datos)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_column($datos, 'origen');
        $data = array_map('intval', array_column($datos, 'total'));

        $colores = [
            'rgba(102, 126, 234, 0.8)',
            'rgba(240, 147, 251, 0.8)',
            'rgba(250, 112, 154, 0.8)',
            'rgba(48, 207, 208, 0.8)'
        ];

        $config = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $colores,
                    'borderColor' => '#fff',
                    'borderWidth' => 4
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                        'labels' => ['padding' => 20, 'font' => ['size' => 13]]
                    ],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#fff',
                        'font' => ['weight' => 'bold', 'size' => 14],
                        'formatter' => '(value, ctx) => { 
                            let sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value * 100) / sum);
                            return percentage + "%";
                        }'
                    ]
                ],
                'cutout' => '50%'
            ]
        ];

        return $this->enviarAQuickChart($config, 500, 400);
    }

    private function generarGradienteColores($cantidad, $colorInicio, $colorFin)
    {
        // Convierte hex a RGB
        $rgbInicio = sscanf($colorInicio, "#%02x%02x%02x");
        $rgbFin = sscanf($colorFin, "#%02x%02x%02x");

        $colores = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $ratio = $cantidad > 1 ? $i / ($cantidad - 1) : 0;
            $r = round($rgbInicio[0] + ($rgbFin[0] - $rgbInicio[0]) * $ratio);
            $g = round($rgbInicio[1] + ($rgbFin[1] - $rgbInicio[1]) * $ratio);
            $b = round($rgbInicio[2] + ($rgbFin[2] - $rgbInicio[2]) * $ratio);
            $colores[] = "rgba($r, $g, $b, 0.8)";
        }

        return $colores;
    }

    private function enviarAQuickChart($config, $width = 600, $height = 300)
    {
        $payload = json_encode([
            'chart' => $config,
            'width' => $width,
            'height' => $height,
            'devicePixelRatio' => 2.0,
            'backgroundColor' => 'white'
        ]);

        $ch = curl_init('https://quickchart.io/chart');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log('Error QuickChart: ' . curl_error($ch));
            curl_close($ch);
            return $this->imagenVacia();
        }

        curl_close($ch);

        if ($result) {
            return 'data:image/png;base64,' . base64_encode($result);
        }

        return $this->imagenVacia();
    }

    private function imagenVacia()
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    }

    private function generarGraficaPuntosVisitados($datos)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_map(function ($d) {
            return $d['nombre_punto'] . ' (' . $d['nombre_delegacion'] . ')';
        }, $datos);
        $data = array_map('intval', array_column($datos, 'total'));

        $colores = $this->generarGradienteColores(count($data), '#3498db', '#2ecc71');

        $config = [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Servicios',
                    'data' => $data,
                    'backgroundColor' => $colores,
                    'borderWidth' => 0
                ]]
            ],
            'options' => [
                'indexAxis' => 'y',
                'plugins' => [
                    'legend' => ['display' => false],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#fff',
                        'anchor' => 'end',
                        'align' => 'start',
                        'offset' => 4,
                        'font' => ['weight' => 'bold', 'size' => 11]
                    ]
                ],
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'grid' => ['color' => 'rgba(0,0,0,0.05)']
                    ],
                    'y' => [
                        'grid' => ['display' => false],
                        'ticks' => ['font' => ['size' => 9]]
                    ]
                ]
            ]
        ];

        return $this->enviarAQuickChart($config, 700, 450);
    }

    private function generarGraficaPuntosFallidos($datos)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_map(function ($d) {
            return $d['nombre_punto'] . ' (' . $d['nombre_delegacion'] . ')';
        }, $datos);
        $data = array_map('intval', array_column($datos, 'total_fallidos'));

        $colores = $this->generarGradienteColores(count($data), '#e74c3c', '#c0392b');

        $config = [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Fallidos',
                    'data' => $data,
                    'backgroundColor' => $colores,
                    'borderWidth' => 0
                ]]
            ],
            'options' => [
                'indexAxis' => 'y',
                'plugins' => [
                    'legend' => ['display' => false],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#fff',
                        'anchor' => 'end',
                        'align' => 'start',
                        'offset' => 4,
                        'font' => ['weight' => 'bold', 'size' => 11]
                    ]
                ],
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'grid' => ['color' => 'rgba(0,0,0,0.05)']
                    ],
                    'y' => [
                        'grid' => ['display' => false],
                        'ticks' => ['font' => ['size' => 9]]
                    ]
                ]
            ]
        ];

        return $this->enviarAQuickChart($config, 700, 450);
    }

    private function generarGraficaCalificaciones($datos)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_column($datos, 'calificacion');
        $data = array_map('intval', array_column($datos, 'total'));

        $colores = [
            'rgba(46, 213, 115, 0.8)',   // Excelente - Verde
            'rgba(26, 188, 156, 0.8)',   // Bueno - Verde agua
            'rgba(241, 196, 15, 0.8)',   // Regular - Amarillo
            'rgba(230, 126, 34, 0.8)',   // Malo - Naranja
            'rgba(231, 76, 60, 0.8)',    // Muy malo - Rojo
            'rgba(149, 165, 166, 0.8)'   // Sin calificar - Gris
        ];

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Cantidad',
                    'data' => $data,
                    'backgroundColor' => $colores,
                    'borderWidth' => 0,
                    'borderRadius' => 6
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false],
                    'datalabels' => [
                        'display' => true,
                        'anchor' => 'end',
                        'align' => 'top',
                        'color' => '#34495e',
                        'font' => ['weight' => 'bold', 'size' => 12]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => ['color' => 'rgba(0,0,0,0.05)']
                    ],
                    'x' => [
                        'grid' => ['display' => false]
                    ]
                ]
            ]
        ];

        return $this->enviarAQuickChart($config, 700, 350);
    }

    private function generarGraficaFallidosDelegacion($datos)
    {
        if (empty($datos)) return $this->imagenVacia();

        $labels = array_column($datos, 'nombre_delegacion');
        $data = array_map('intval', array_column($datos, 'total_fallidos'));

        $colores = $this->generarGradienteColores(count($data), '#e74c3c', '#c0392b');

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Fallidos',
                    'data' => $data,
                    'backgroundColor' => $colores,
                    'borderWidth' => 0,
                    'borderRadius' => 6
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false],
                    'datalabels' => [
                        'display' => true,
                        'anchor' => 'end',
                        'align' => 'top',
                        'color' => '#34495e',
                        'font' => ['weight' => 'bold', 'size' => 11]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => ['color' => 'rgba(0,0,0,0.05)']
                    ],
                    'x' => [
                        'grid' => ['display' => false],
                        'ticks' => [
                            'maxRotation' => 45,
                            'minRotation' => 45,
                            'font' => ['size' => 9]
                        ]
                    ]
                ]
            ]
        ];

        return $this->enviarAQuickChart($config, 700, 350);
    }
}
