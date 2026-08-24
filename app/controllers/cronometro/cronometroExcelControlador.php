<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/cronometro/cronometroReportesModelo.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class cronometroExcelControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new cronometroReportesModelo($this->db);
    }

    public function generar()
    {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

        $tipos = $this->modelo->obtenerTiposMantenimiento();

        if (isset($_GET['meta_total']) && is_numeric($_GET['meta_total']) && (int) $_GET['meta_total'] > 0) {
            $metaTotalServicios = (int) $_GET['meta_total'];
        } else {
            $metaTotalServicios = $this->calcularMetaRango($fechaInicio, $fechaFin, 6, 3);
        }
        $rendimientoRaw = $this->modelo->obtenerRendimientoTecnicos($fechaInicio, $fechaFin, $tipos);

        // Crear nuevo libro de Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ========== TÍTULO Y PERIODO ==========
        $sheet->setCellValue('A1', 'Reporte de Rendimiento - Flota Motorizada');
        $sheet->mergeCells('A1:' . $this->letraColumna(1 + count($tipos) + 5) . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', "Periodo: {$fechaInicio} al {$fechaFin} | Meta del Periodo: {$metaTotalServicios} servicios");
        $sheet->mergeCells('A2:' . $this->letraColumna(1 + count($tipos) + 5) . '2');
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ========== ENCABEZADOS ==========
        $columna = 1;
        $sheet->setCellValue($this->letraColumna($columna) . '3', 'Técnico');
        $columna++;

        foreach ($tipos as $tipo) {
            $sheet->setCellValue($this->letraColumna($columna) . '3', $tipo['nombre_completo']);
            $columna++;
        }

        $sheet->setCellValue($this->letraColumna($columna) . '3', 'Finalizados');
        $columna++;
        $sheet->setCellValue($this->letraColumna($columna) . '3', 'En Progreso');
        $columna++;
        $sheet->setCellValue($this->letraColumna($columna) . '3', 'Meta Esperada');
        $columna++;
        $sheet->setCellValue($this->letraColumna($columna) . '3', '% Cumplimiento');
        $columna++;
        $sheet->setCellValue($this->letraColumna($columna) . '3', 'Estado');

        // Estilo de encabezados
        $ultimaColumna = $this->letraColumna($columna);
        $sheet->getStyle("A3:{$ultimaColumna}3")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1']
                ]
            ]
        ]);

        // ========== DATOS ==========
        $fila = 4;
        $totalTecnicos = 0;
        $totalFinalizadosGlobal = 0;
        $tecnicosMetaCumplida = 0;

        foreach ($rendimientoRaw as $r) {
            $finalizados = (int) $r['total_finalizados'];
            $totalFinalizadosGlobal += $finalizados;
            $totalTecnicos++;

            $pct = ($metaTotalServicios > 0) ? round(($finalizados / $metaTotalServicios) * 100, 1) : 0;

            if ($pct >= 100) {
                $estadoTxt = 'Meta Cumplida';
                $colorFondo = 'DCFCE7';
            } elseif ($pct >= 70) {
                $estadoTxt = 'Aceptable';
                $colorFondo = 'FEF3C7';
            } else {
                $estadoTxt = 'Crítico';
                $colorFondo = 'FEE2E2';
            }

            if ($pct >= 100)
                $tecnicosMetaCumplida++;

            $columna = 1;
            $sheet->setCellValue($this->letraColumna($columna) . $fila, $r['nombre_tecnico']);
            $columna++;

            foreach ($tipos as $tipo) {
                $alias = 'tipo_' . $tipo['id_tipo_mantenimiento'];
                $valor = isset($r[$alias]) ? (int) $r[$alias] : 0;
                $sheet->setCellValue($this->letraColumna($columna) . $fila, $valor);
                $columna++;
            }

            $sheet->setCellValue($this->letraColumna($columna) . $fila, $finalizados);
            $columna++;
            $sheet->setCellValue($this->letraColumna($columna) . $fila, (int) $r['total_en_progreso']);
            $columna++;
            $sheet->setCellValue($this->letraColumna($columna) . $fila, $metaTotalServicios);
            $columna++;
            $sheet->setCellValue($this->letraColumna($columna) . $fila, $pct . '%');
            $columna++;
            $sheet->setCellValue($this->letraColumna($columna) . $fila, $estadoTxt);

            // Estilo de la fila
            $ultimaColumnaFila = $this->letraColumna($columna);
            $sheet->getStyle("A{$fila}:{$ultimaColumnaFila}{$fila}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);

            // Color de fondo para la columna "Estado"
            $sheet->getStyle($this->letraColumna($columna) . $fila)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $colorFondo]
                ]
            ]);

            // Alinear a la izquierda el nombre del técnico
            $sheet->getStyle('A' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $fila++;
        }

        // ========== FILA DE RESUMEN ==========
        $sheet->setCellValue('A' . $fila, 'RESUMEN');
        $sheet->mergeCells('A' . $fila . ':' . $this->letraColumna(1 + count($tipos)) . $fila);
        $sheet->getStyle('A' . $fila)->getFont()->setBold(true);
        $sheet->getStyle('A' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $columna = 2 + count($tipos);
        $sheet->setCellValue($this->letraColumna($columna) . $fila, $totalFinalizadosGlobal);
        $columna++;
        $sheet->setCellValue($this->letraColumna($columna) . $fila, '');
        $columna++;
        $sheet->setCellValue($this->letraColumna($columna) . $fila, '');
        $columna++;

        $pctGlobal = ($totalTecnicos > 0) ? round(($tecnicosMetaCumplida / $totalTecnicos) * 100, 1) : 0;
        $sheet->setCellValue($this->letraColumna($columna) . $fila, $pctGlobal . '%');
        $columna++;
        $sheet->setCellValue($this->letraColumna($columna) . $fila, $tecnicosMetaCumplida . ' de ' . $totalTecnicos . ' técnicos cumplieron la meta');

        // Estilo de la fila resumen
        $ultimaColumnaFila = $this->letraColumna($columna);
        $sheet->getStyle("A{$fila}:{$ultimaColumnaFila}{$fila}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F1F5F9']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1']
                ]
            ]
        ]);

        // ========== AUTO AJUSTAR ANCHO DE COLUMNAS ==========
        $ultimaColumnaLetra = $this->letraColumna($columna);
        for ($i = 1; $i <= $columna; $i++) {
            $sheet->getColumnDimension($this->letraColumna($i))->setAutoSize(true);
        }

        // ========== GENERAR Y DESCARGAR ==========
        $nombreArchivo = "Reporte_Rendimiento_{$fechaInicio}_al_{$fechaFin}.xlsx";

        // Limpiar cualquier salida previa
        if (ob_get_length())
            ob_end_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Convierte número de columna a letra (1=A, 2=B, 27=AA)
     */
    private function letraColumna($num)
    {
        $letra = '';
        while ($num > 0) {
            $num--;
            $letra = chr(65 + ($num % 26)) . $letra;
            $num = intval($num / 26);
        }
        return $letra;
    }

    private function calcularMetaRango($fechaInicio, $fechaFin, $metaLV, $metaSab)
    {
        $inicio = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);
        $fin->modify('+1 day');
        $intervalo = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $intervalo, $fin);

        $metaTotal = 0;
        foreach ($periodo as $dt) {
            $diaSemana = (int) $dt->format('N');
            if ($diaSemana >= 1 && $diaSemana <= 5) {
                $metaTotal += $metaLV;
            } elseif ($diaSemana === 6) {
                $metaTotal += $metaSab;
            }
        }
        return $metaTotal;
    }
}