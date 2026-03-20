<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
// Reutilizamos tu modelo de detalles que ya debe tener las consultas del servicio
require_once __DIR__ . '/../../models/orden/ordenDetalleModelo.php';
require_once __DIR__ . '/../../models/orden/serviciosPdfModelo.php';

use Spatie\Browsershot\Browsershot;

class PdfServicioControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();

        // CORRECCIÓN AQUÍ: Usamos el modelo que tiene las nuevas funciones
        $this->modelo = new serviciosPdfModelo($this->db);
    }

    public function generar()
    {
        // 1. Recibimos el ID de la orden por GET
        $idOrden = $_GET['id'] ?? null;

        if (!$idOrden) {
            die("Error: ID de orden no proporcionado.");
        }

        // 2. Aquí debes traer los datos de la orden (usa la función que ya tengas o creamos una)
        // OJO: Asumo que crearemos un método 'obtenerDatosCompletosOrden' en tu modelo
        $datosOrden = $this->modelo->obtenerDatosCompletosOrden($idOrden);

        // También traemos las fotos de la tabla evidencia_servicio
        $evidencias = $this->modelo->obtenerEvidenciasOrden($idOrden);
        // AGREGAR ESTA LÍNEA:
        $novedades = $this->modelo->obtenerNovedadesOrden($idOrden);

        if (!$datosOrden) {
            die("Error: La orden no existe.");
        }

        // 3. Capturamos el HTML de la vista
        if (ob_get_length()) ob_end_clean();
        ob_start();

        // CORRECCIÓN AQUÍ: Nombre exacto del archivo
        include __DIR__ . '/../../views/orden/plantillaPdfServicio.php';

        $html = ob_get_clean();


        // 4. Configuración de Browsershot (Copiada de tu código funcional)
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

            $numeroRemision = $datosOrden['numero_remision'] ?? $idOrden;

            $browsershot = Browsershot::html($html)
                ->setNodeBinary($nodePath)
                ->setNpmBinary($npmPath)
                ->setOption('args', ['--no-sandbox'])
                ->format('A4')
                ->margins(10, 10, 10, 10) // Márgenes para el PDF
                // ->showBrowserHeaderAndFooter() // Descomenta si quieres poner header/footer repetitivo
                ->timeout(120);

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();

            // 5. Mostrar el PDF en el navegador
            $nombreArchivo = "Servicio_{$numeroRemision}.pdf";

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfContent));

            echo $pdfContent;
            exit;
        } catch (Exception $e) {
            echo "<h1>Error generando PDF del Servicio</h1><p>" . $e->getMessage() . "</p>";
            die();
        }
    }
}
