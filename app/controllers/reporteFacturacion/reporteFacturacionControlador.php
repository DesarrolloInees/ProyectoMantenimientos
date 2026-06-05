<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// 🔥 ESTAS DOS LÍNEAS MATARÁN LA PANTALLA BLANCA 🔥
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Revisa exhaustivamente que estas rutas sean EXACTAS a tus carpetas
require_once __DIR__ . '/../../config/conexion.php';

// ¿Tu modelo está dentro de una carpeta "reporteFacturacion" o está suelto en "models"?
require_once __DIR__ . '/../../models/reporteFacturacion/reporteFacturacionModelo.php'; 
require_once __DIR__ . '/../../../vendor/autoload.php';

class ReporteFacturacionControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        date_default_timezone_set('America/Bogota');
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteFacturacionModelo($this->db);
    }

    public function index()
    {
        $titulo = "Generador de Reportes de Facturación";
        
        // ⚠️ VERIFICACIÓN CRÍTICA: Asegúrate de que creaste esta carpeta y este archivo
        // Si el archivo se llama distinto, la vista fallará.
        $vistaContenido = "app/views/reporteFacturacion/reporteFacturacionVista.php"; 
        
        include "app/views/plantillaVista.php";
    }

    // 1. PROCESAR EL EXCEL (SOLO LA PRIMERA HOJA)
    public function procesarExcelCotizaciones()
    {
        ob_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
            try {
                $rutaTemporal = $_FILES['archivo_excel']['tmp_name'];
                
                // Cargamos el archivo con PhpSpreadsheet
                $spreadsheet = IOFactory::load($rutaTemporal);
                
                // Seleccionamos ESTRICTAMENTE la primera hoja (índice 0)
                $hoja = $spreadsheet->getSheet(0); 
                
                // Convertimos la hoja a un array de PHP
                $datosExcel = $hoja->toArray(null, true, true, true);
                
                // Aquí recorres $datosExcel e insertas en la base de datos (HeidiSQL)
                // para poder hacer los filtros SQL posteriormente.
                // ... (Lógica de inserción usando $this->modelo)
                
                $respuestaArray = [
                    'exito' => true,
                    'mensaje' => 'Primera hoja procesada y lista para filtrar.',
                    'total_filas' => count($datosExcel)
                ];

            } catch (\Throwable $e) {
                $respuestaArray = ['exito' => false, 'error' => 'Error: ' . $e->getMessage()];
            }
        }
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuestaArray);
        exit;
    }

    // 2. GENERAR PDF FILTRADO
    public function generarPdfFiltrado()
    {
        // Recibimos los filtros desde la vista (AJAX)
        $fechaInicio = $_POST['fecha_inicio'] ?? null;
        $fechaFin = $_POST['fecha_fin'] ?? null;
        $categoria = $_POST['categoria'] ?? null;

        // Obtenemos los datos filtrados del modelo
        $datosFiltrados = $this->modelo->obtenerDatosFiltrados($fechaInicio, $fechaFin, $categoria);

        // 1. Construimos el HTML de la tabla con los datos
        $html = $this->construirVistaHtml($datosFiltrados);

        // 2. Renderizamos el PDF con Browsershot
        $pdfPath = __DIR__ . '/../../public/temp/reporte_' . time() . '.pdf';
        
        Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            // Se adapta perfectamente a tus entornos Windows/Ubuntu
            ->save($pdfPath);

        // Retornamos la URL para que el usuario descargue o vea el PDF
        echo json_encode(['exito' => true, 'url_pdf' => 'public/temp/' . basename($pdfPath)]);
        exit;
    }

    private function construirVistaHtml($datos)
    {
        // Aquí armas un HTML con CSS básico que Browsershot convertirá a PDF
        $html = "<h1>Reporte de Máquinas</h1><table border='1'><tr><th>Cotización</th><th>Fecha</th></tr>";
        foreach($datos as $row) {
            $html .= "<tr><td>{$row['cotizacion']}</td><td>{$row['fecha']}</td></tr>";
        }
        $html .= "</table>";
        return $html;
    }
}
?>