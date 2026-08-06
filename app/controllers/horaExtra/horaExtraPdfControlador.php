<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/horaExtra/horaExtraReporteModelo.php';

use Spatie\Browsershot\Browsershot;

class horaExtraGenerarControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new horaExtraReporteModelo($this->db);
    }

    public function generar()
    {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin    = $_GET['fecha_fin'] ?? date('Y-m-t');
        $idTecnico   = !empty($_GET['id_tecnico']) ? $_GET['id_tecnico'] : null;
        $idEstado    = !empty($_GET['id_estado']) ? $_GET['id_estado'] : null;

        $reportes = $this->modelo->obtenerReporteHorasExtra($fechaInicio, $fechaFin, $idTecnico, $idEstado);

        // Procesar fotos a Base64 para que Puppeteer/Browsershot las renderice sin fallar
        foreach ($reportes as &$rep) {
            $rep['fotos_base64'] = [];
            if (!empty($rep['rutas_fotos'])) {
                $rutas = explode('||', $rep['rutas_fotos']);
                foreach ($rutas as $r) {
                    $pos = strpos($r, 'app/uploads/');
                    $rutaLimpia = ($pos !== false) ? substr($r, $pos) : ltrim($r, '/');
                    $rutaFisica = realpath(__DIR__ . '/../../' . $rutaLimpia);

                    if ($rutaFisica && file_exists($rutaFisica) && !is_dir($rutaFisica)) {
                        $ext = strtolower(pathinfo($rutaFisica, PATHINFO_EXTENSION));
                        $mime = ($ext === 'jpg') ? 'jpeg' : $ext;
                        $data = file_get_contents($rutaFisica);
                        $rep['fotos_base64'][] = 'data:image/' . $mime . ';base64,' . base64_encode($data);
                    }
                }
            }
        }
        unset($rep);

        // Capturar HTML de la plantilla
        if (ob_get_length()) ob_end_clean();
        ob_start();
        include __DIR__ . '/../../views/horaExtra/plantillaPdfHoraExtra.php';
        $html = ob_get_clean();

        try {
            $nodePath = 'C:\\Program Files\\nodejs\\node.exe';
            $npmPath  = 'C:\\Program Files\\nodejs\\npm.cmd';

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
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->timeout(120);

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();

            $nombreArchivo = "Reporte_Horas_Extra_{$fechaInicio}_al_{$fechaFin}.pdf";

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfContent));

            echo $pdfContent;
            exit;
        } catch (Exception $e) {
            echo "<h1>Error generando PDF de Horas Extra</h1><p>" . $e->getMessage() . "</p>";
            die();
        }
    }
}