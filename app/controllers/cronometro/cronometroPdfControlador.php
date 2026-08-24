<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/cronometro/cronometroReportesModelo.php';

use Spatie\Browsershot\Browsershot;

class cronometroPdfControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new cronometroReportesModelo($this->db);
    }

    public function generar()
    {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

        $tipos = $this->modelo->obtenerTiposMantenimiento();

        $metaLunesViernes = 6;
        $metaSabado = 3;

        if (isset($_GET['meta_total']) && is_numeric($_GET['meta_total']) && (int) $_GET['meta_total'] > 0) {
            $metaTotalServicios = (int) $_GET['meta_total'];
        } else {
            $metaTotalServicios = $this->calcularMetaRango($fechaInicio, $fechaFin, $metaLunesViernes, $metaSabado);
        }

        $rendimientoRaw = $this->modelo->obtenerRendimientoTecnicos($fechaInicio, $fechaFin, $tipos);

        $tecnicosNombres = [];
        $serviciosRealizados = [];
        $coloresBarras = [];

        foreach ($rendimientoRaw as $r) {
            $finalizados = (int) $r['total_finalizados'];
            $pct = ($metaTotalServicios > 0) ? round(($finalizados / $metaTotalServicios) * 100, 1) : 0;

            $tecnicosNombres[] = $r['nombre_tecnico'];
            $serviciosRealizados[] = $finalizados;

            if ($pct >= 100) {
                $coloresBarras[] = '#10b981';
            } elseif ($pct >= 70) {
                $coloresBarras[] = '#f59e0b';
            } else {
                $coloresBarras[] = '#ef4444';
            }
        }

        // ========== GENERAR SVG ==========
        $svgGrafica = $this->generarGraficoSVG(
            $tecnicosNombres,
            $serviciosRealizados,
            $coloresBarras,
            $metaTotalServicios
        );

        if (ob_get_length())
            ob_end_clean();
        ob_start();
        include __DIR__ . '/../../views/cronometro/plantillaPdfCronometro.php';
        $html = ob_get_clean();

        try {
            $nodePath = 'C:\\Program Files\\nodejs\\node.exe';
            $npmPath = 'C:\\Program Files\\nodejs\\npm.cmd';

            $posiblesChrome = [
                'C:\\Users\\User\\.cache\\puppeteer\\chrome\\win64-144.0.7559.96\\chrome-win64\\chrome.exe',
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
            ];

            $chromePath = null;
            foreach ($posiblesChrome as $ruta) {
                if (file_exists($ruta)) {
                    $chromePath = $ruta;
                    break;
                }
            }

            $browsershot = Browsershot::html($html)
                ->setNodeBinary($nodePath)
                ->setNpmBinary($npmPath)
                ->setOption('args', ['--no-sandbox'])
                ->waitUntilNetworkIdle()
                ->format('A4')
                ->landscape() // 🔥 ESTE ES EL MÉTODO CORRECTO PARA HORIZONTAL
                ->margins(10, 10, 10, 10)
                ->timeout(120);

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();
            $nombreArchivo = "Rendimiento_Cronometro_{$fechaInicio}_al_{$fechaFin}.pdf";

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfContent));

            echo $pdfContent;
            exit;
        } catch (Exception $e) {
            echo "<h1>Error generando PDF de Rendimiento</h1><p>" . $e->getMessage() . "</p>";
            die();
        }
    }

    /**
     * Genera un gráfico de barras en formato SVG (más ancho para landscape)
     */
    private function generarGraficoSVG($nombres, $datos, $colores, $meta)
    {
        $cantidad = count($nombres);
        $ancho = 950;
        $alto = 350; // Más alto para dar espacio a los textos rotados

        $paddingTop = 30;
        $paddingBottom = 90; // Espacio extra abajo
        $paddingLeft = 50;
        $paddingRight = 30;

        $baseBarWidth = 35;
        $gap = 15;

        // Calcular ancho dinámico
        $totalAncho = ($ancho - $paddingLeft - $paddingRight);
        $espacioTotal = $cantidad * ($baseBarWidth + $gap);

        if ($espacioTotal > $totalAncho) {
            $gap = max(4, ($totalAncho - $cantidad * $baseBarWidth) / ($cantidad - 1));
            if ($gap < 4) {
                $baseBarWidth = max(12, ($totalAncho - ($cantidad - 1) * 4) / $cantidad);
                $gap = 4;
            }
        }

        $maxValue = max($datos) * 1.2;
        if ($maxValue == 0)
            $maxValue = 1;
        if ($maxValue < 5)
            $maxValue = 5;

        $svg = '<svg width="' . $ancho . '" height="' . $alto . '" xmlns="http://www.w3.org/2000/svg" style="background:#fafcff; border-radius:12px; font-family: Inter, sans-serif; border: 1px solid #e9edf4;">';

        // Eje Y
        $svg .= '<line x1="' . $paddingLeft . '" y1="' . $paddingTop . '" x2="' . $paddingLeft . '" y2="' . ($alto - $paddingBottom) . '" stroke="#cbd5e1" stroke-width="1.5"/>';
        // Eje X
        $svg .= '<line x1="' . $paddingLeft . '" y1="' . ($alto - $paddingBottom) . '" x2="' . ($ancho - $paddingRight) . '" y2="' . ($alto - $paddingBottom) . '" stroke="#cbd5e1" stroke-width="1.5"/>';

        // Marcar valores en Y (5 pasos)
        $steps = 5;
        for ($i = 0; $i <= $steps; $i++) {
            $value = round(($maxValue / $steps) * $i, 0);
            $y = ($alto - $paddingBottom) - ($value / $maxValue) * ($alto - $paddingTop - $paddingBottom);
            $svg .= '<text x="' . ($paddingLeft - 10) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="10" fill="#64748b" font-weight="600">' . $value . '</text>';
            if ($i > 0) {
                $svg .= '<line x1="' . $paddingLeft . '" y1="' . $y . '" x2="' . ($ancho - $paddingRight) . '" y2="' . $y . '" stroke="#e2e8f0" stroke-dasharray="4,4" stroke-width="1"/>';
            }
        }

        // Línea de meta
        if ($meta > 0) {
            $metaHeight = ($meta / $maxValue) * ($alto - $paddingTop - $paddingBottom);
            $metaY = ($alto - $paddingBottom) - $metaHeight;
            $svg .= '<line x1="' . $paddingLeft . '" y1="' . $metaY . '" x2="' . ($ancho - $paddingRight) . '" y2="' . $metaY . '" stroke="#ef4444" stroke-dasharray="8,6" stroke-width="2"/>';
            $svg .= '<text x="' . ($ancho - $paddingRight - 5) . '" y="' . ($metaY - 6) . '" text-anchor="end" font-size="10" fill="#ef4444" font-weight="700">Meta (' . $meta . ')</text>';
        }

        $x = $paddingLeft + ($gap * 1.5);
        $baseY = $alto - $paddingBottom;

        foreach ($nombres as $i => $nombre) {
            $barHeight = ($datos[$i] / $maxValue) * ($alto - $paddingTop - $paddingBottom);
            $y = $baseY - $barHeight;
            $color = $colores[$i] ?? '#4f46e5';

            // Barra
            if ($barHeight > 0) {
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $baseBarWidth . '" height="' . $barHeight . '" fill="' . $color . '" rx="2"/>';
            }

            // Valor sobre la barra
            $svg .= '<text x="' . ($x + $baseBarWidth / 2) . '" y="' . ($y - 6) . '" text-anchor="middle" font-size="11" font-weight="800" fill="#0f172a">' . $datos[$i] . '</text>';

            // Acortar nombre (Ej: "JUAN CARLOS PEREZ" -> "JUAN C.")
            $partes = explode(' ', trim($nombre));
            $nombreCorto = $partes[0];
            if (isset($partes[1]) && strlen($partes[1]) > 2) {
                $nombreCorto .= ' ' . substr($partes[1], 0, 1) . '.';
            }

            // Texto inclinado -45 grados
            $cx = $x + ($baseBarWidth / 2);
            $cy = $baseY + 12;
            $svg .= '<text x="' . $cx . '" y="' . $cy . '" transform="rotate(-45 ' . $cx . ' ' . $cy . ')" text-anchor="end" font-size="9" fill="#475569" font-weight="700">' . htmlspecialchars($nombreCorto) . '</text>';

            $x += $baseBarWidth + $gap;
        }

        $svg .= '</svg>';
        return $svg;
    }

    private function calcularMetaRango($fechaInicio, $fechaFin, $metaLV, $metaSab)
    {
        $inicio = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);
        $fin->modify('+1 day');
        $intervalo = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $intervalo, $fin);

        $metaTotal = 0;
        foreach ($periodo as $dt) {
            $diaSemana = (int) $dt->format('N');
            if ($diaSemana >= 1 && $diaSemana <= 5) {
                $metaTotal += $metaLV;
            } elseif ($diaSemana === 6) {
                $metaTotal += $metaSab;
            }
        }
        return $metaTotal;
    }
}