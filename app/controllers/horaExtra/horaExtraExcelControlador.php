<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/horaExtra/horaExtraReporteModelo.php';

// Requiere: composer require phpoffice/phpspreadsheet
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class horaExtraExcelControlador
{
    private $modelo;
    private $db;

    // Carpeta física (de disco) que sirve de "raíz" para las rutas guardadas
    // en evidencia_horas_extra.ruta_archivo.
    //
    // Esas rutas vienen como: app/uploads/horas_extra/NOMBRE_TECNICO/2026-08/HE_1_FOTO_xxx.jpg
    // es decir, YA INCLUYEN "app/", por lo tanto son relativas a la raíz
    // del proyecto (la carpeta que contiene a "app/"), no a "public/".
    //
    // Este controlador vive en: app/controllers/horaExtra/horaExtraExcelControlador.php
    // Entonces __DIR__ = .../app/controllers/horaExtra
    // Subiendo 3 niveles llegamos a la raíz del proyecto (padre de "app/").
    // Si tu controlador está en otra profundidad, ajusta la cantidad de "/..".
    private $rutaFisicaBaseEvidencias = __DIR__ . '/../../..';

    // AJUSTA ESTO: ruta de disco del logo (no URL / BASE_URL).
    private $logoPath = __DIR__ . '/../../../app/logos/logoInees.jpg';

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
        $periodoRegistrado = date('d/m/Y', strtotime($fechaInicio)) . ' AL ' . date('d/m/Y', strtotime($fechaFin));

        // Agrupar por técnico (solo aparecen los que tuvieron registros en el periodo)
        $porTecnico = [];
        foreach ($reportes as $r) {
            $porTecnico[$r['nombre_tecnico']][] = $r;
        }
        ksort($porTecnico); // orden alfabético por nombre de técnico

        // Ordenar cada grupo internamente por fecha
        foreach ($porTecnico as &$grupo) {
            usort($grupo, function ($a, $b) {
                return strtotime($a['fecha_reporte']) <=> strtotime($b['fecha_reporte']);
            });
        }
        unset($grupo);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // quitamos la hoja en blanco por defecto

        $indiceHoja = 0;
        foreach ($porTecnico as $nombreTecnico => $registrosTecnico) {
            $sheet = $spreadsheet->createSheet($indiceHoja);
            $this->construirHojaAsistencia($sheet, $registrosTecnico, $nombreTecnico, $periodoRegistrado);
            $indiceHoja++;
        }

        // Si no hubo NINGÚN registro en el periodo, dejamos una hoja vacía informativa
        if ($indiceHoja === 0) {
            $sheet = $spreadsheet->createSheet(0);
            $this->construirHojaAsistencia($sheet, [], 'SIN REGISTROS', $periodoRegistrado);
        }

        $spreadsheet->setActiveSheetIndex(0);

        if (ob_get_length()) ob_end_clean();

        $nombreArchivo = "FORMATO_HORAS_EXTRAS_" . date('Ymd_His') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Sanitiza el nombre del técnico para usarlo como nombre de hoja de Excel.
     * Excel prohíbe: \ / ? * [ ] y limita a 31 caracteres.
     */
    private function nombreHojaValido(string $nombre): string
    {
        $limpio = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $nombre);
        return mb_substr($limpio, 0, 31);
    }

    private function construirHojaAsistencia(Worksheet $sheet, array $reportes, string $nombreTecnico, string $periodoRegistrado): void
    {
        $sheet->setTitle($this->nombreHojaValido($nombreTecnico ?: 'SIN NOMBRE'));

        // ---- ANCHOS DE COLUMNA (A-I) ----
        // A FECHA | B TECNICO | C ENTRADA | D SALIDA | E PUNTO | F TOTAL HRS | G-H DETALLE | I EVIDENCIA
        $anchos = ['A' => 12, 'B' => 20, 'C' => 10, 'D' => 10, 'E' => 22, 'F' => 10, 'G' => 20, 'H' => 20, 'I' => 26];
        foreach ($anchos as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $bordeFino = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
            ],
        ];
        $centrado = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
        $negrita  = ['font' => ['bold' => true]];
        $fondoGris = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']]];
        $fondoAzul = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']]];

        // ---- FILA 1: LOGO + TÍTULO ----
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('C1:I1');
        $sheet->getRowDimension(1)->setRowHeight(45);
        $sheet->setCellValue('C1', "COORDINACIÓN DE OPERACIONES TÉCNICAS\nFORMATO REGISTRO DE ASISTENCIA PARA EL RECONOCIMIENTO DE HORAS EXTRAS, RECARGOS NOCTURNOS, DOMINICALES FESTIVOS Y COMPENSATORIOS");
        $sheet->getStyle('C1')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(10);

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

        // ---- FILA 2: TÉCNICO / PERIODO ----
        $sheet->mergeCells('B2:D2');
        $sheet->mergeCells('H2:I2');
        $sheet->setCellValue('A2', 'TÉCNICO:');
        $sheet->setCellValue('B2', $nombreTecnico);
        $sheet->setCellValue('G2', 'PERIODO REGISTRO:');
        $sheet->setCellValue('H2', $periodoRegistrado);
        $sheet->getStyle('A2')->applyFromArray(array_merge($negrita, $fondoGris));
        $sheet->getStyle('G2')->applyFromArray(array_merge($negrita, $fondoGris));

        // ---- FILA 3: NOTA ----
        $sheet->mergeCells('A3:I3');
        $sheet->getRowDimension(3)->setRowHeight(50);
        $sheet->setCellValue('A3', 'NOTA: TOTALICE LAS HORAS ORDINARIAS DEL PERIODO, FESTIVAS O DOMINICALES EN SU RESPECTIVA CASILLA. RECUERDE QUE EL FORMATO MAL DILIGENCIADO SERÁ DEVUELTO. CONSERVE COPIA DE ESTE FORMATO.');
        $sheet->getStyle('A3')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A3')->getFont()->setSize(8);

        // ---- FILAS 4-5: CABECERA DE TABLA ----
        $sheet->mergeCells('A4:A5');
        $sheet->mergeCells('B4:B5');
        $sheet->mergeCells('C4:D4');
        $sheet->mergeCells('E4:E5');
        $sheet->mergeCells('F4:F5');
        $sheet->mergeCells('G4:H5');
        $sheet->mergeCells('I4:I5');
        $sheet->getRowDimension(4)->setRowHeight(20);
        $sheet->getRowDimension(5)->setRowHeight(20);

        $sheet->setCellValue('A4', 'FECHA D/M/A');
        $sheet->setCellValue('B4', 'TÉCNICO');
        $sheet->setCellValue('C4', 'HORA ENTRADA / SALIDA');
        $sheet->setCellValue('E4', 'NÚMERO MÁQUINA / PUNTO');
        $sheet->setCellValue('F4', 'TOTAL (HRS)');
        $sheet->setCellValue('G4', 'DETALLE DEL LUGAR Y ACTIVIDAD');
        $sheet->setCellValue('I4', 'EVIDENCIA FOTOGRÁFICA');
        $sheet->setCellValue('C5', 'ENTRADA');
        $sheet->setCellValue('D5', 'SALIDA');

        $sheet->getStyle('A4:I5')->applyFromArray(array_merge($negrita, $centrado, $fondoAzul));
        $sheet->getStyle('A4:I5')->getAlignment()->setWrapText(true);

        // ---- FILAS DE DATOS ----
        $fila = 6;
        $totalGeneralHoras = 0;
        $ALTO_FILA_CON_FOTOS = 60; // pt, suficiente para 1-2 miniaturas

        if (!empty($reportes)) {
            foreach ($reportes as $r) {
                $totalGeneralHoras += (float) $r['total_horas'];
                $timestamp = strtotime($r['fecha_reporte']);

                $sheet->mergeCells("G{$fila}:H{$fila}");
                $sheet->getRowDimension($fila)->setRowHeight($ALTO_FILA_CON_FOTOS);

                $sheet->setCellValue("A{$fila}", date('d/m/Y', $timestamp));
                $sheet->setCellValue("B{$fila}", $r['nombre_tecnico']);
                $sheet->setCellValue("C{$fila}", date('H:i', strtotime($r['hora_inicio'])));
                $sheet->setCellValue("D{$fila}", date('H:i', strtotime($r['hora_fin'])));
                $sheet->setCellValue("E{$fila}", $r['nombre_punto']);
                $sheet->setCellValue("F{$fila}", number_format((float) $r['total_horas'], 2));
                $sheet->setCellValue("G{$fila}", "Cliente: {$r['nombre_cliente']} - Detalle: {$r['justificacion_tecnico']}");
                $sheet->getStyle("G{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

                // ---- EVIDENCIAS FOTOGRÁFICAS ----
                if (!empty($r['rutas_fotos'])) {
                    $rutas = explode('||', $r['rutas_fotos']);
                    $offsetX = 3;
                    foreach ($rutas as $rutaRelativa) {
                        $rutaRelativa = trim($rutaRelativa);
                        if ($rutaRelativa === '') continue;

                        $rutaDisco = $this->resolverRutaFisica($rutaRelativa);
                        if ($rutaDisco === null) continue;

                        $img = new Drawing();
                        $img->setName('Evidencia');
                        $img->setDescription('Evidencia horas extra');
                        $img->setPath($rutaDisco);
                        $img->setHeight(52);
                        $img->setCoordinates("I{$fila}");
                        $img->setOffsetX($offsetX);
                        $img->setOffsetY(3);
                        $img->setWorksheet($sheet);

                        $offsetX += ($img->getWidth() + 4); // siguiente foto al lado de la anterior
                    }
                }

                $fila++;
            }
        }

        $filaFinDatos = $fila - 1;
        if ($filaFinDatos >= 6) {
            $sheet->getStyle("A4:I{$filaFinDatos}")->applyFromArray($bordeFino);
            $sheet->getStyle("A6:F{$filaFinDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        } else {
            $filaFinDatos = 5;
        }

        // ---- TOTALES Y FIRMAS ----
        $filaTotales1 = $filaFinDatos + 1;
        $filaTotales2 = $filaFinDatos + 2;
        $filaTotales3 = $filaFinDatos + 3;

        $sheet->mergeCells("A{$filaTotales1}:F{$filaTotales3}");
        $sheet->setCellValue("A{$filaTotales1}", "_______________________          _______________________          _______________________\nFIRMA FUNCIONARIO                          FIRMA JEFE                            FIRMA SUPERVISOR");
        $sheet->getStyle("A{$filaTotales1}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_BOTTOM)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$filaTotales1}")->getFont()->setBold(true);

        $sheet->mergeCells("G{$filaTotales1}:H{$filaTotales1}");
        $sheet->setCellValue("G{$filaTotales1}", 'TOTAL DOMINICALES:');
        $sheet->setCellValue("I{$filaTotales1}", '0.00');

        $sheet->mergeCells("G{$filaTotales2}:H{$filaTotales2}");
        $sheet->setCellValue("G{$filaTotales2}", 'TOTAL ORDINARIAS:');
        $sheet->setCellValue("I{$filaTotales2}", number_format($totalGeneralHoras, 2));

        $sheet->mergeCells("G{$filaTotales3}:H{$filaTotales3}");
        $sheet->setCellValue("G{$filaTotales3}", 'TOTAL CON RECARGO NOCT:');
        $sheet->setCellValue("I{$filaTotales3}", '0.00');

        $sheet->getStyle("G{$filaTotales1}:H{$filaTotales3}")->applyFromArray(array_merge($negrita, $fondoGris));
        $sheet->getStyle("I{$filaTotales1}:I{$filaTotales3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I{$filaTotales1}:I{$filaTotales3}")->getFont()->setBold(true);
        $sheet->getStyle("A{$filaTotales1}:I{$filaTotales3}")->applyFromArray($bordeFino);

        // ---- OBSERVACIONES ----
        $filaObs = $filaTotales3 + 1;
        $sheet->mergeCells("A{$filaObs}:I{$filaObs}");
        $sheet->getRowDimension($filaObs)->setRowHeight(50);
        $sheet->setCellValue("A{$filaObs}", 'OBSERVACIONES:');
        $sheet->getStyle("A{$filaObs}")->getFont()->setBold(true);
        $sheet->getStyle("A{$filaObs}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle("A{$filaObs}")->applyFromArray($bordeFino);
    }

    /**
     * Convierte la ruta guardada en BD (normalmente relativa a la carpeta
     * pública/web) en una ruta de disco real que PhpSpreadsheet pueda leer.
     * Devuelve null si el archivo no existe (para no romper la generación).
     */
    private function resolverRutaFisica(string $rutaGuardada): ?string
    {
        // Si ya es una ruta absoluta de disco y existe, úsala tal cual.
        if (is_file($rutaGuardada)) {
            return $rutaGuardada;
        }

        $baseReal = realpath($this->rutaFisicaBaseEvidencias);
        if ($baseReal === false) {
            error_log("Excel horas extra: la ruta base de evidencias no existe en disco: {$this->rutaFisicaBaseEvidencias}");
            return null;
        }

        $candidata = $baseReal . '/' . ltrim($rutaGuardada, '/');
        if (is_file($candidata)) {
            return $candidata;
        }

        // No se encontró en disco: se omite esta foto (no rompe el resto del Excel)
        // pero queda logueado para que puedas revisar rutas rotas.
        error_log("Excel horas extra: no se encontró la evidencia en disco -> {$candidata}");
        return null;
    }
}