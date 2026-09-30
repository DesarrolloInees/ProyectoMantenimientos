<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/turno/turnoModelo.php';

use Spatie\Browsershot\Browsershot;

/**
 * PDF del reporte de turnos.
 * Se genera con Browsershot (Node.js + Chrome), igual que el PDF de órdenes de
 * servicio (app/controllers/orden/pdfServicioControlador.php) y el de horas
 * extra: se arma el HTML de la plantilla y Chrome lo convierte a A4.
 */
class turnoPdfControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new turnoModelo($this->db);
    }

    public function generar()
    {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin    = $_GET['fecha_fin'] ?? date('Y-m-d');
        $idTecnico   = !empty($_GET['id_tecnico']) ? (int) $_GET['id_tecnico'] : null;

        $registros = $this->modelo->obtenerTurnos($fechaInicio, $fechaFin, $idTecnico);
        $periodo = date('d/m/Y', strtotime($fechaInicio)) . ' AL ' . date('d/m/Y', strtotime($fechaFin));

        // El logo va embebido en base64 para que Chrome no dependa de rutas del servidor
        $logoBase64 = '';
        $logoPath = __DIR__ . '/../../../app/logos/logoInees.jpg';
        if (is_file($logoPath)) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents($logoPath));
        }

        // ---- 1. Capturamos el HTML de la plantilla ----
        if (ob_get_length()) {
            ob_end_clean();
        }
        ob_start();
        include __DIR__ . '/../../views/turno/plantillaPdfTurnos.php';
        $html = ob_get_clean();

        // ---- 2. Configuración de Browsershot (Node + Chrome) ----
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
                ->margins(10, 10, 10, 10)
                ->timeout(120);

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();

            // ---- 3. Se muestra el PDF en el navegador ----
            $nombreArchivo = "Reporte_Turnos_{$fechaInicio}_al_{$fechaFin}.pdf";

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfContent));

            echo $pdfContent;
            exit;
        } catch (Exception $e) {
            echo "<h1>Error generando el PDF de turnos</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
            die();
        }
    }
}