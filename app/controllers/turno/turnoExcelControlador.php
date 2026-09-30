<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/turno/turnoModelo.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel del reporte de turnos: hoja "TURNOS" con el periodo, la tabla de
 * marcaciones, el total de registros y el espacio para firmas.
 */
class turnoExcelControlador
{
    private $modelo;
    private $db;
    private $logoPath = __DIR__ . '/../../../app/logos/logoInees.jpg';

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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $this->construirHoja($sheet, $registros, $periodo);

        $nombreArchivo = "Reporte_Turnos_{$fechaInicio}_al_{$fechaFin}.xlsx";

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function construirHoja(Worksheet $sheet, array $registros, string $periodo): void
    {
        $sheet->setTitle('TURNOS');

        // ---- ANCHOS DE COLUMNA ----
        $anchos = ['A' => 24, 'B' => 12, 'C' => 12, 'D' => 52, 'E' => 20, 'F' => 13, 'G' => 13];
        foreach ($anchos as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $bordeFino = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
            ],
        ];
        $centrado  = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
        $negrita   = ['font' => ['bold' => true]];
        $fondoAzul = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']]];
        $fondoGris = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']]];

        // ---- FILA 1: LOGO + TÍTULO ----
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('C1:G1');
        $sheet->getRowDimension(1)->setRowHeight(45);
        $sheet->setCellValue('C1', "COORDINACIÓN DE OPERACIONES TÉCNICAS\nFORMATO DE REGISTRO DE ENTRADA DE TURNOS - MOTORIZADOS");
        $sheet->getStyle('C1')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(11);

        if (is_file($this->logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo INEES');
            $drawing->setPath($this->logoPath);
            $drawing->setHeight(50);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        }

        // ---- FILA 2: PERIODO ----
        $sheet->mergeCells('B2:C2');
        $sheet->setCellValue('A2', 'PERIODO:');
        $sheet->setCellValue('B2', $periodo);
        $sheet->getStyle('A2')->applyFromArray(array_merge($negrita, $fondoGris));
        $sheet->getStyle('B2')->getFont()->setBold(true);

        // ---- FILA 3: NOTA ----
        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'NOTA: LA "HORA ENTRADA" ES LA REPORTADA POR EL TÉCNICO. LA COLUMNA "REGISTRADO EL" ES LA FECHA Y HORA REAL DEL SISTEMA CUANDO SE GUARDÓ LA MARCACIÓN.');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A3')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(26);

        // ---- FILA 4: ENCABEZADOS ----
        $encabezados = [
            'A4' => 'TÉCNICO',
            'B4' => 'FECHA',
            'C4' => 'HORA ENTRADA',
            'D4' => 'NOVEDAD',
            'E4' => 'REGISTRADO EL',
            'F4' => 'LATITUD',
            'G4' => 'LONGITUD'
        ];
        foreach ($encabezados as $celda => $texto) {
            $sheet->setCellValue($celda, $texto);
        }
        $sheet->getRowDimension(4)->setRowHeight(22);
        $sheet->getStyle('A4:G4')->applyFromArray(array_merge($negrita, $centrado, $fondoAzul));
        $sheet->getStyle('A4:G4')->getAlignment()->setWrapText(true);

        $this->escribirDatos($sheet, $registros, $bordeFino, $centrado, $negrita, $fondoGris);
    }

    /** Filas de datos, fila de total y bloque de firmas. */
    private function escribirDatos(Worksheet $sheet, array $registros, array $bordeFino, array $centrado, array $negrita, array $fondoGris): void
    {
        $fila = 5;

        foreach ($registros as $r) {
            $sheet->setCellValue("A{$fila}", $r['nombre_tecnico']);
            $sheet->setCellValue("B{$fila}", date('d/m/Y', strtotime($r['fecha'])));
            $sheet->setCellValue("C{$fila}", date('H:i', strtotime($r['hora_entrada'])));
            $sheet->setCellValue("D{$fila}", (string) $r['novedad']);
            $sheet->setCellValue("E{$fila}", date('d/m/Y H:i', strtotime($r['fecha_registro'])));
            $sheet->setCellValue("F{$fila}", $r['latitud'] !== null ? (float) $r['latitud'] : '');
            $sheet->setCellValue("G{$fila}", $r['longitud'] !== null ? (float) $r['longitud'] : '');
            $sheet->getStyle("D{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $fila++;
        }

        $filaFinDatos = $fila - 1;

        if ($filaFinDatos >= 5) {
            $sheet->getStyle("A4:G{$filaFinDatos}")->applyFromArray($bordeFino);
            $sheet->getStyle("A5:C{$filaFinDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("E5:G{$filaFinDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        } else {
            $filaFinDatos = 4;
        }

        // ---- TOTAL DE REGISTROS ----
        $filaTotal = $filaFinDatos + 1;
        $sheet->mergeCells("A{$filaTotal}:C{$filaTotal}");
        $sheet->setCellValue("A{$filaTotal}", 'TOTAL REGISTROS: ' . count($registros));
        $sheet->getStyle("A{$filaTotal}")->applyFromArray(array_merge($negrita, $fondoGris));
        $sheet->getStyle("A{$filaTotal}:G{$filaTotal}")->applyFromArray($bordeFino);

        // ---- FIRMAS ----
        $filaFirmas = $filaTotal + 3;
        $sheet->mergeCells("A{$filaFirmas}:C{$filaFirmas}");
        $sheet->mergeCells("E{$filaFirmas}:G{$filaFirmas}");
        $sheet->setCellValue("A{$filaFirmas}", '_____________________________');
        $sheet->setCellValue("E{$filaFirmas}", '_____________________________');
        $sheet->setCellValue("A" . ($filaFirmas + 1), 'FIRMA SUPERVISOR');
        $sheet->setCellValue("E" . ($filaFirmas + 1), 'FIRMA COORDINACIÓN');
        $sheet->getStyle("A{$filaFirmas}:G" . ($filaFirmas + 1))->applyFromArray($centrado);
        $sheet->getStyle("A" . ($filaFirmas + 1))->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle("E" . ($filaFirmas + 1))->getFont()->setBold(true)->setSize(9);
    }
}
