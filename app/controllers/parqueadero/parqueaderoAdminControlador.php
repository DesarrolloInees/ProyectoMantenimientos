<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/parqueadero/parqueaderoAdminModelo.php';

use Spatie\Browsershot\Browsershot;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ParqueaderoAdminControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ParqueaderoAdminModelo($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
        $fechaFin    = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');
        $idTecnico   = isset($_GET['id_tecnico']) ? $_GET['id_tecnico'] : '';

        $tecnicos = $this->modelo->obtenerTecnicos();
        $facturas = $this->modelo->obtenerFacturasAdmin($fechaInicio, $fechaFin, $idTecnico);

        $totalGastado = 0;
        $totalFacturas = count($facturas);
        foreach ($facturas as $fac) {
            $totalGastado += (float)$fac['valor_factura'];
        }

        $titulo = "Administración de Parqueaderos";
        $vistaContenido = "app/views/parqueadero/parqueaderoAdminVista.php";
        include "app/views/plantillaVista.php";
    }

    // Método para generar PDF con Browsershot usando exactamente tus mismas rutas del servidor
    public function exportarPdf()
    {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin    = $_GET['fecha_fin'] ?? date('Y-m-t');
        $idTecnico   = !empty($_GET['id_tecnico']) ? $_GET['id_tecnico'] : null;

        $datos = $this->modelo->obtenerReporteParqueaderosExportar($fechaInicio, $fechaFin, $idTecnico);

        $totalGeneral = 0;
        foreach ($datos as $d) {
            $totalGeneral += (float)$d['valor_factura'];
        }

        // Renderizar plantilla HTML limpia para Browsershot
        if (ob_get_length()) ob_end_clean();
        ob_start();
        include __DIR__ . '/../../views/parqueadero/plantillaPdfParqueaderos.php';
        $html = ob_get_clean();

        try {
            // MISMAS RUTAS CONFIGURADAS PARA TU SERVIDOR
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
                ->landscape()
                ->margins(8, 8, 8, 8)
                ->timeout(120);

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();

            $nombreArchivo = "Reporte_Parqueaderos_{$fechaInicio}_al_{$fechaFin}.pdf";

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfContent));

            echo $pdfContent;
            exit;
        } catch (Exception $e) {
            echo "<h1>Error generando PDF de Parqueaderos</h1><p>" . $e->getMessage() . "</p>";
            die();
        }
    }

    // Método para generar Excel con PhpSpreadsheet incrustando las fotos de comprobantes
    public function exportarExcel()
    {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin    = $_GET['fecha_fin'] ?? date('Y-m-t');
        $idTecnico   = !empty($_GET['id_tecnico']) ? $_GET['id_tecnico'] : null;

        $datos = $this->modelo->obtenerReporteParqueaderosExportar($fechaInicio, $fechaFin, $idTecnico);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Parqueaderos');

        // Encabezado Principal
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'REPORTE DE FACTURAS DE PARQUEADERO');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Subtítulo
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Período: ' . date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime($fechaFin)));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Encabezados de Columnas
        $headers = ['N°', 'Fecha Servicio', 'Técnico', 'Punto Visitado', 'Horario', 'N° Factura', 'Valor Pagado ($)', 'Foto Comprobante'];
        $sheet->fromArray($headers, NULL, 'A4');

        $sheet->getStyle('A4:H4')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fila = 5;
        $totalGeneral = 0;

        foreach ($datos as $index => $item) {
            $horario = date('H:i', strtotime($item['hora_inicio'])) . ' - ' . date('H:i', strtotime($item['hora_fin']));
            $valor = (float) $item['valor_factura'];
            $totalGeneral += $valor;

            $sheet->getRowDimension($fila)->setRowHeight(60);

            $sheet->setCellValue('A' . $fila, $index + 1);
            $sheet->setCellValue('B' . $fila, date('d/m/Y', strtotime($item['fecha_servicio'])));
            $sheet->setCellValue('C' . $fila, $item['nombre_tecnico']);
            $sheet->setCellValue('D' . $fila, $item['nombre_punto']);
            $sheet->setCellValue('E' . $fila, $horario);
            $sheet->setCellValue('F' . $fila, $item['numero_factura']);
            $sheet->setCellValue('G' . $fila, $valor);

            $sheet->getStyle('G' . $fila)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('A' . $fila . ':B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('C' . $fila . ':F' . $fila)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('E' . $fila . ':F' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Insertar imagen física en Excel
            $pos = strpos($item['ruta_foto'] ?? '', 'app/uploads/');
            $rutaLimpia = ($pos !== false) ? substr($item['ruta_foto'], $pos) : ltrim($item['ruta_foto'] ?? '', '/');
            $rutaFisicaFoto = realpath(__DIR__ . '/../../' . $rutaLimpia);

            if ($rutaFisicaFoto && file_exists($rutaFisicaFoto) && !is_dir($rutaFisicaFoto)) {
                $drawing = new Drawing();
                $drawing->setName('Factura_' . $item['numero_factura']);
                $drawing->setDescription('Comprobante');
                $drawing->setPath($rutaFisicaFoto);
                $drawing->setHeight(70);
                $drawing->setCoordinates('H' . $fila);
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(5);
                $drawing->setWorksheet($sheet);
            } else {
                $sheet->setCellValue('H' . $fila, 'Sin Foto');
                $sheet->getStyle('H' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }

            $fila++;
        }

        // Fila Total
        $sheet->getRowDimension($fila)->setRowHeight(25);
        $sheet->mergeCells('A' . $fila . ':F' . $fila);
        $sheet->setCellValue('A' . $fila, 'TOTAL GASTADO:');
        $sheet->setCellValue('G' . $fila, $totalGeneral);
        $sheet->getStyle('A' . $fila . ':H' . $fila)->getFont()->setBold(true);
        $sheet->getStyle('A' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('G' . $fila)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('G' . $fila)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('A' . $fila . ':H' . $fila)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E7FF');

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('H')->setWidth(20);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Reporte_Parqueaderos_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // Método para guardar la rotación físicamente en el servidor
public function guardarRotacion()
{
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido.']);
        exit;
    }

    $rutaFotoRelativa = $_POST['ruta_foto'] ?? '';
    $grados = (int)($_POST['grados'] ?? 0);

    if (empty($rutaFotoRelativa) || $grados === 0) {
        echo json_encode(['exito' => false, 'mensaje' => 'Parámetros inválidos.']);
        exit;
    }

    // 1. Limpiar ruta para ubicar el archivo físico desde la raíz del proyecto
    $pos = strpos($rutaFotoRelativa, 'app/uploads/');
    $rutaLimpia = ($pos !== false) ? substr($rutaFotoRelativa, $pos) : ltrim($rutaFotoRelativa, '/');
    
    // Subir 3 niveles desde app/controllers/parqueadero/ hasta la raíz
    $rutaFisica = realpath(__DIR__ . '/../../../' . $rutaLimpia);

    if (!$rutaFisica || !file_exists($rutaFisica)) {
        echo json_encode(['exito' => false, 'mensaje' => 'No se encontró la imagen en el servidor.']);
        exit;
    }

    ini_set('memory_limit', '256M');
    $info = @getimagesize($rutaFisica);
    if (!$info) {
        echo json_encode(['exito' => false, 'mensaje' => 'Archivo de imagen no válido.']);
        exit;
    }

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $imagen = @imagecreatefromjpeg($rutaFisica);
            break;
        case 'image/png':
            $imagen = @imagecreatefrompng($rutaFisica);
            break;
        case 'image/webp':
            $imagen = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($rutaFisica) : null;
            break;
        default:
            $imagen = null;
    }

    if (!$imagen) {
        echo json_encode(['exito' => false, 'mensaje' => 'Formato de imagen no soportado para rotación.']);
        exit;
    }

    // En GD, la rotación positiva va en sentido antihorario, por eso invertimos el ángulo
    $anguloGd = -$grados;
    $imagenRotada = imagerotate($imagen, $anguloGd, 0);

    if ($imagenRotada === false) {
        echo json_encode(['exito' => false, 'mensaje' => 'Fallo al rotar la imagen.']);
        exit;
    }

    // Guardar sobreescribiendo el archivo original en disco
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $exito = imagejpeg($imagenRotada, $rutaFisica, 90);
            break;
        case 'image/png':
            $exito = imagepng($imagenRotada, $rutaFisica, 8);
            break;
        case 'image/webp':
            $exito = imagewebp($imagenRotada, $rutaFisica, 90);
            break;
        default:
            $exito = false;
    }

    imagedestroy($imagen);
    imagedestroy($imagenRotada);

    if ($exito) {
        echo json_encode(['exito' => true, 'mensaje' => 'Rotación guardada correctamente.']);
    } else {
        echo json_encode(['exito' => false, 'mensaje' => 'No se pudo guardar la imagen rotada.']);
    }
    exit;
}
}