<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/reporteTrazabilidadModelo.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class reporteTrazabilidadControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteTrazabilidadModelo($this->db);
    }

    private function validarFecha($fecha, $fallback)
    {
        if (!empty($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }
        return $fallback;
    }

    public function index()
    {
        $filtros = [
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin'    => date('Y-m-d')
        ];

        $datosTrazabilidad = [];
        $datosTop = [];
        $totalPiezas = 0;
        $mensaje = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['fecha_inicio'] = $this->validarFecha($_POST['fecha_inicio'] ?? '', $filtros['fecha_inicio']);
            $filtros['fecha_fin']    = $this->validarFecha($_POST['fecha_fin'] ?? '', $filtros['fecha_fin']);

            $datosTrazabilidad = $this->modelo->getTrazabilidadPorPunto($filtros['fecha_inicio'], $filtros['fecha_fin']);
            $datosTop = $this->modelo->getTopPorTipoMaquina($filtros['fecha_inicio'], $filtros['fecha_fin']);

            foreach ($datosTrazabilidad as $row) {
                $totalPiezas += (int)$row['cantidad'];
            }

            if (empty($datosTrazabilidad) && empty($datosTop)) {
                $mensaje = "No se encontraron repuestos suministrados en ese rango de fechas.";
            }
        }

        $titulo = "Trazabilidad de Repuestos Suministrados";
        $vistaContenido = "app/views/reportes/reporteTrazabilidadVista.php";
        include "app/views/plantillaVista.php";
    }
    public function descargarExcel()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        while (ob_get_level()) { ob_end_clean(); }
        $fechaInicio = $this->validarFecha($_GET['fecha_inicio'] ?? '', date('Y-m-01'));
        $fechaFin = $this->validarFecha($_GET['fecha_fin'] ?? '', date('Y-m-d'));
        $detalle = $this->modelo->getTrazabilidadPorPunto($fechaInicio, $fechaFin);
        $top = $this->modelo->getTopPorTipoMaquina($fechaInicio, $fechaFin);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $sheet1 = $spreadsheet->createSheet(0);
        $sheet1->setTitle('Trazabilidad Punto');
        $sheet1->fromArray(['Cliente','Punto','Tipo de Maquina','Device ID','Nombre del Repuesto','Origen del Repuesto','Cantidad','Fecha de Cambio'], null, 'A1');
        $this->estiloHead($sheet1, 'A1:H1');
        $fila = 2;
        foreach ($detalle as $d) {
            $sheet1->setCellValue('A'.$fila, $d['cliente']);
            $sheet1->setCellValue('B'.$fila, $d['punto']);
            $sheet1->setCellValue('C'.$fila, $d['tipo_maquina']);
            $sheet1->setCellValue('D'.$fila, $d['device_id']);
            $sheet1->setCellValue('E'.$fila, $d['repuesto']);
            $sheet1->setCellValue('F'.$fila, $d['origen']);
            $sheet1->setCellValue('G'.$fila, (int)$d['cantidad']);
            $sheet1->setCellValue('H'.$fila, $d['fecha_cambio']);
            $sheet1->getStyle('H'.$fila)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            $fila++;
        }
        $this->ajustes($sheet1, 'A1:H'.max($fila-1,1), ['A'=>30,'B'=>30,'C'=>24,'D'=>16,'E'=>35,'F'=>16,'G'=>12,'H'=>16]);
        $sheet2 = $spreadsheet->createSheet(1);
        $sheet2->setTitle('Top Repuestos Tipo Maq');
        $sheet2->fromArray(['Tipo de Maquina','Nombre del Repuesto','Origen del Repuesto','Total Cambiados'], null, 'A1');
        $this->estiloHead($sheet2, 'A1:D1');
        $f2 = 2;
        foreach ($top as $t) {
            $sheet2->setCellValue('A'.$f2, $t['tipo_maquina']);
            $sheet2->setCellValue('B'.$f2, $t['repuesto']);
            $sheet2->setCellValue('C'.$f2, $t['origen']);
            $sheet2->setCellValue('D'.$f2, (int)$t['total_cambiados']);
            $f2++;
        }
        $this->ajustes($sheet2, 'A1:D'.max($f2-1,1), ['A'=>28,'B'=>40,'C'=>18,'D'=>17]);
        $spreadsheet->setActiveSheetIndex(0);
        $nombre = 'Trazabilidad_Repuestos_'.$fechaInicio.'_al_'.$fechaFin.'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$nombre.'"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function estiloHead($sheet, $rango)
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B0B0']]]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
    }

    private function ajustes($sheet, $rango, array $anchos)
    {
        foreach ($anchos as $col => $ancho) { $sheet->getColumnDimension($col)->setWidth($ancho); }
        if ($sheet->getHighestRow() > 1) { $sheet->setAutoFilter($sheet->calculateWorksheetDimension()); }
        $sheet->freezePane('A2');
        $sheet->getStyle($rango)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ]);
    }
}
