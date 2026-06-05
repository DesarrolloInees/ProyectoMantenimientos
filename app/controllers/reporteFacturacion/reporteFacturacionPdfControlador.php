<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reporteFacturacion/reporteFacturacionPdfModelo.php';

use Spatie\Browsershot\Browsershot;

class ReportesPdfControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        date_default_timezone_set('America/Bogota');
        
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteFacturacionPdfModelo($this->db);
    }

    public function generar()
    {
        // 1. Recolectar todos los filtros posibles (vienen por GET o POST según como mandes el form)
        $filtros = [
            'fecha_inicio' => $_GET['f_ini'] ?? null,
            'fecha_fin'    => $_GET['f_fin'] ?? null,
            'estado'       => $_GET['estado'] ?? null,
            'categoria'    => $_GET['categoria'] ?? null,
            'precio_min'   => $_GET['p_min'] ?? null,
            'precio_max'   => $_GET['p_max'] ?? null,
            'referencia'   => $_GET['ref'] ?? null,
        ];

        // 2. Obtener la data filtrada
        $datosReporte = $this->modelo->obtenerDatosFiltrados($filtros);

        if (empty($datosReporte)) {
            die("<h2 style='text-align:center; margin-top:50px; font-family:sans-serif;'>No se encontraron registros con esos filtros.</h2>");
        }

        // 3. Capturar el HTML de la plantilla
        if (ob_get_length()) ob_end_clean();
        ob_start();
        include __DIR__ . '/../../views/reportes/plantillaPdfExcel.php';
        $html = ob_get_clean();

        // 4. Generar el PDF con Browsershot (Adaptable Win/Ubuntu)
        try {
            $nodePath = 'C:\\Program Files\\nodejs\\node.exe';
            $npmPath  = 'C:\\Program Files\\nodejs\\npm.cmd';

            $posiblesRutasChrome = [
                'C:\\Users\\User\\.cache\\puppeteer\\chrome\\win64-144.0.7559.96\\chrome-win64\\chrome.exe',
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
            ];

            $chromePath = null;
            foreach ($posiblesRutasChrome as $ruta) {
                if (file_exists($ruta)) {
                    $chromePath = $ruta;
                    break;
                }
            }

            $browsershot = Browsershot::html($html)
                ->setNodeBinary($nodePath)
                ->setNpmBinary($npmPath)
                ->setOption('args', ['--no-sandbox'])
                ->format('A4')
                ->landscape() // Lo ponemos en horizontal para que quepan más columnas del Excel
                ->margins(10, 10, 10, 10)
                ->timeout(120);

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();

            // 5. Salida al navegador
            $fechaFiltro = $filtros['fecha_inicio'] ? $filtros['fecha_inicio'] : date('Y-m-d');
            $nombreArchivo = "Reporte_Cotizaciones_{$fechaFiltro}.pdf";

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfContent));

            echo $pdfContent;
            exit;

        } catch (Exception $e) {
            echo "<h1>Error generando PDF del Reporte</h1><p>" . $e->getMessage() . "</p>";
            die();
        }
    }
}
?>