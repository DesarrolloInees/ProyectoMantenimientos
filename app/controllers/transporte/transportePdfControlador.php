<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/transporte/transportePdfModelo.php';

use Spatie\Browsershot\Browsershot;

class transportePdfControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new transportePdfModelo($this->db);
    }

    public function index()
    {
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            die("Error: ID de instalación no proporcionado.");
        }

        $id = intval($_GET['id']);
        $instalacion = $this->modelo->getDetalleInstalacionPdf($id);

        if (!$instalacion) {
            die("Error: No se encontró el registro o fue eliminado.");
        }

        // Cargar logo en base64 para que el PDF lo pueda incrustar sin problemas
        $rutaLogo = __DIR__ . '/../../logos/logoInees.jpg';
        $logoBase64 = "";
        if (file_exists($rutaLogo)) {
            $type = pathinfo($rutaLogo, PATHINFO_EXTENSION);
            $data = file_get_contents($rutaLogo);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // Capturar el HTML de la vista
        if (ob_get_length()) ob_end_clean();
        ob_start();
        include __DIR__ . '/../../views/transporte/transportePdfVista.php';
        $html = ob_get_clean();

        // Generar PDF con Browsershot
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
                ->format('Letter') // Formato carta, ideal para estas constancias
                ->margins(10, 10, 10, 10)
                ->showBackground();

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();

            // Configurar headers para mostrar en el navegador
            $nombreArchivo = "Reporte Remision" . $instalacion['numero_remision'] . ".pdf";
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfContent));
            echo $pdfContent;
            exit;
        } catch (Exception $e) {
            echo "<h1>Error generando PDF</h1><p>" . $e->getMessage() . "</p>";
            die();
        }
    }
}
