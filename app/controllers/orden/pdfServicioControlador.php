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

        // =======================================================
        // BLOQUEO DE SEGURIDAD POR URL (Para que no copien el link)
        // =======================================================
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idRolUsuario = isset($_SESSION['nivel_acceso']) ? $_SESSION['nivel_acceso'] : null;

        if ($idRolUsuario == 4 && $idOrden) {
            $paramValor = $this->modelo->obtenerParametro('meses_restriccion_prosegur');
            $mesesRestriccion = $paramValor ? (int)$paramValor : 1;

            if (!$this->modelo->puedeVerOrden($idOrden, $idRolUsuario, $mesesRestriccion)) {
                die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
                        <h2 style='color:red;'>Acceso Denegado</h2>
                        <p>No tienes los permisos suficientes para ver esta orden de servicio porque aún está en periodo de restricción.</p>
                     </div>");
            }
        }
        // =======================================================

        // 2. Traer los datos de la orden
        $datosOrden = $this->modelo->obtenerDatosCompletosOrden($idOrden);

        // También traemos las fotos de la tabla evidencia_servicio
        $evidencias = $this->modelo->obtenerEvidenciasOrden($idOrden);
        // AGREGAR ESTA LÍNEA:
        $novedades = $this->modelo->obtenerNovedadesOrden($idOrden);
        $repuestos = $this->modelo->obtenerRepuestosOrden($idOrden);

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
            // 1. Extraer los datos necesarios
            $numeroRemision = $datosOrden['numero_remision'] ?? $idOrden;
            $puntoAtendido  = $datosOrden['nombre_punto'] ?? 'Sin_Punto';
            $fechaServicio  = date('Y-m-d', strtotime($datosOrden['fecha_visita']));

            // 2. Limpiar el nombre del punto (quitar acentos, espacios o caracteres raros para evitar errores de archivo)
            $puntoLimpio = str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $puntoAtendido);

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

            // 5. Mostrar el PDF en el navegador
            // 3. Construir el nuevo nombre del archivo
            // Ejemplo: Servicio_1025_Punto_Venta_Norte_2026-04-17.pdf
            $nombreArchivo = "Servicio_{$numeroRemision}_{$puntoLimpio}_{$fechaServicio}.pdf";

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
