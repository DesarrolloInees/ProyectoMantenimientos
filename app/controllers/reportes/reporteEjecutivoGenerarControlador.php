<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/ReporteEjecutivoModelo.php';

use Spatie\Browsershot\Browsershot;

// --- BORRÉ TODO EL CÓDIGO SUELTO QUE TENÍAS AQUÍ ---
// No necesitas definir $url ni llamar a Browsershot aquí afuera.
// Lo haremos correctamente dentro de la clase.

class generarReporteControlador
{
    private $modelo;
    private $db;
    private $secciones = [];

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteEjecutivoModelo($this->db);
    }

    public function index()
    {
        if (isset($_GET['accion']) && $_GET['accion'] == 'configurar') {
            $this->configurar();
            return;
        }

        // --- TUS DATOS (Sin cambios) ---
        $inicio = $_GET['inicio'] ?? date('Y-m-01');
        $fin = $_GET['fin'] ?? date('Y-m-t');
        $this->secciones = $_GET['secciones'] ?? [];

        $datosDia = $this->modelo->getServiciosPorDia($inicio, $fin);
        $datosDelegacion = $this->modelo->getDelegacionesIntervenidas($inicio, $fin);
        $datosTipo = $this->modelo->getPorTipoMantenimiento($inicio, $fin);
        $datosNovedad = $this->modelo->getDistribucionNovedades($inicio, $fin);
        $datosTecnico = $this->modelo->getHorasVsServicios($inicio, $fin);
        $datosEstado = $this->modelo->getServiciosFallidos($inicio, $fin);
        $datosRepuestos = $this->modelo->getComparativaRepuestos($inicio, $fin);
        $datosPuntosFallidos = $this->modelo->getPuntosConFallidos($inicio, $fin);
        $datosCalificaciones = $this->modelo->getCalificacionesServicio($inicio, $fin);

        $totalServicios = array_sum(array_column($datosDia, 'total'));
        $fecha1 = new DateTime($inicio); $fecha2 = new DateTime($fin);
        $cantidadDias = $fecha1->diff($fecha2)->days + 1;
        $numTecnicos = count($datosTecnico);
        $mediaDiaria = ($numTecnicos > 0 && $cantidadDias > 0) ? round($totalServicios / ($numTecnicos * $cantidadDias), 2) : 0;
        
        usort($datosTecnico, function ($a, $b) { return $b['total_servicios'] - $a['total_servicios']; });
        $topTecnicos = array_slice($datosTecnico, 0, 15);

        // --- RENDERIZADO DEL HTML ---
        if (ob_get_length()) ob_end_clean();
        ob_start();
        include __DIR__ . '/../../views/reportes/reporteEjecutivoGenerar.php';
        $html = ob_get_clean();

        try {
            // 1. RUTAS DE NODE (Generalmente iguales en todos los Windows)
            $nodePath = 'C:\\Program Files\\nodejs\\node.exe'; 
            $npmPath  = 'C:\\Program Files\\nodejs\\npm.cmd';
            
            // 2. DETECCIÓN AUTOMÁTICA DE CHROME
            // Definimos una lista de "candidatos" donde podría estar Chrome
            $posiblesRutasChrome = [
                // Opción A: La ruta específica de tu SERVIDOR
                'C:\\Users\\User\\.cache\\puppeteer\\chrome\\win64-144.0.7559.96\\chrome-win64\\chrome.exe',
                
                // Opción B: Ruta estándar de Google Chrome en Windows (Para tu PC "HP")
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                
                // Opción C: Ruta estándar en sistemas de 32 bits
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
            ];

            $chromePathEncontrado = null;
            foreach ($posiblesRutasChrome as $ruta) {
                if (file_exists($ruta)) {
                    $chromePathEncontrado = $ruta;
                    break; // ¡Encontramos uno! Dejamos de buscar.
                }
            }

            // Configuración inicial de Browsershot
            $browsershot = Browsershot::html($html)
                ->setNodeBinary($nodePath) 
                ->setNpmBinary($npmPath)
                ->setOption('args', ['--no-sandbox'])
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->scale(0.8) 
                ->timeout(120);

            // SOLO si encontramos una ruta válida, se la asignamos.
            // Si no, dejamos que Browsershot intente buscar por su cuenta.
            if ($chromePathEncontrado) {
                $browsershot->setChromePath($chromePathEncontrado);
            }

            $pdfContent = $browsershot->pdf();

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Reporte_Ejecutivo.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            echo $pdfContent;
            exit;

        } catch (Exception $e) {
            echo "<div style='font-family:sans-serif; padding:20px; background:#fee; border:1px solid red; color:red;'>";
            echo "<h1>⚠️ Error generando PDF</h1>";
            echo "<p><strong>Detalle:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>Debug:</strong> No encontré Chrome en ninguna de estas rutas:</p>";
            echo "<ul>";
            // Mostramos dónde buscamos para ayudar a depurar si falla
            if (isset($posiblesRutasChrome)) {
                foreach ($posiblesRutasChrome as $r) echo "<li>$r</li>";
            }
            echo "</ul>";
            echo "</div>";
            die();
        }
    }

    public function configurar() {
        $filtros = ['fecha_inicio' => $_GET['inicio'] ?? date('Y-m-01'), 'fecha_fin' => $_GET['fin'] ?? date('Y-m-t')];
        require_once __DIR__ . '/../../views/reportes/configurarReporte.php';
    }

    public function seccionActiva($nombre) {
        return in_array($nombre, $this->secciones);
    }
}