<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/ReporteRepuestosModelo.php';

use Spatie\Browsershot\Browsershot;

class ReporteRepuestosControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteRepuestosModelo($this->db);
    }

    public function index()
    {
        // Obtener fechas de GET (opcionales)
        $fechaDesde = isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
        $fechaHasta = isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;

        // Validar formato (evitar inyección)
        if ($fechaDesde && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde))
            $fechaDesde = null;
        if ($fechaHasta && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta))
            $fechaHasta = null;

        // Obtener datos del modelo con filtro
        $kpis = $this->modelo->getKpisInventario($fechaDesde, $fechaHasta);
        // Forzar enteros (por si acaso)
        $kpis['total_piezas'] = (int) ($kpis['total_piezas'] ?? 0);
        $kpis['tecnicos_con_stock'] = (int) ($kpis['tecnicos_con_stock'] ?? 0);
        $kpis['referencias_distintas'] = (int) ($kpis['referencias_distintas'] ?? 0);
        $kpis['repuestos_agotados'] = (int) ($kpis['repuestos_agotados'] ?? 0);
        $consolidado = $this->modelo->getConsolidadoRepuestos($fechaDesde, $fechaHasta);
        if (!is_array($consolidado))
            $consolidado = [];
        $mitad = ceil(count($consolidado) / 2);
        $consolidado_izq = array_slice($consolidado, 0, $mitad);
        $consolidado_der = array_slice($consolidado, $mitad);
        $inventarioTecnicos = $this->modelo->getInventarioPorTecnico($fechaDesde, $fechaHasta);
        if (!is_array($inventarioTecnicos))
            $inventarioTecnicos = [];

        // Fecha de corte para mostrar en el reporte
        if ($fechaDesde && $fechaHasta) {
            $fechaReporte = "Desde " . date('d/m/Y', strtotime($fechaDesde)) . " hasta " . date('d/m/Y', strtotime($fechaHasta));
        } else {
            $fechaReporte = date('d/m/Y H:i');
        }

        // Logo (igual que antes)
        $rutaLogo = __DIR__ . '/../../logos/logoInees.jpg';
        $logoBase64 = "";
        if (file_exists($rutaLogo)) {
            $type = pathinfo($rutaLogo, PATHINFO_EXTENSION);
            $data = file_get_contents($rutaLogo);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // Renderizar HTML
        if (ob_get_length())
            ob_end_clean();
        ob_start();
        include __DIR__ . '/../../views/reportes/reporteRepuestosGenerar.php';
        $html = ob_get_clean();

        // Footer (igual)
        $footerHtml = '
    <div style="width: 100%; font-size: 9px; padding: 0 15px 10px 15px; font-family: sans-serif; color: #64748b; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <div style="width: 33%; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;">
            Control de Inventario
        </div>
        <div style="width: 33%; text-align: center; font-weight: bold;">
            Generado: ' . $fechaReporte . '
        </div>
        <div style="width: 33%; text-align: right;">
            <span style="background-color: #f8fafc; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 4px; font-weight: bold; color: #475569;">
                Página <span class="pageNumber"></span>
            </span>
        </div>
    </div>';

        // Generar PDF (igual que antes)
        try {
            $nodePath = 'C:\\Program Files\\nodejs\\node.exe';
            $npmPath = 'C:\\Program Files\\nodejs\\npm.cmd';

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
                ->landscape()
                ->margins(10, 10, 15, 10)
                ->scale(0.8)
                ->timeout(120)
                ->showBrowserHeaderAndFooter()
                ->headerHtml('<div></div>')
                ->footerHtml($footerHtml);

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();
            $nombreArchivo = "Reporte_Repuestos_" . date('d-m-Y_H-i') . ".pdf";

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfContent));
            echo $pdfContent;
            exit;

        } catch (Exception $e) {
            echo "<h1>Error generando PDF de Repuestos</h1><p>" . $e->getMessage() . "</p>";
            die();
        }
    }
}