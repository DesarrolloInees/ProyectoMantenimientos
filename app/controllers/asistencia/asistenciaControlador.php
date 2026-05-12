<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/asistencia/asistenciaModelo.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AsistenciaControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new AsistenciaModelo($this->db);
    }

    public function index()
    {
        $titulo = "Procesador de Asistencias (Huellero + Servicios)";
        $vistaContenido = "app/views/asistencia/asistenciaVista.php";
        include "app/views/plantillaVista.php";
    }

    // Normaliza limpiando tildes, pero respetando la Ñ
    private function normalizarTexto($str)
    {
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1, Windows-1252, auto');
        }
        $str = trim($str);

        $unwanted_array = array(
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'à' => 'a',
            'è' => 'e',
            'ì' => 'i',
            'ò' => 'o',
            'ù' => 'u',
            'À' => 'A',
            'È' => 'E',
            'Ì' => 'I',
            'Ò' => 'O',
            'Ù' => 'U',
            'ä' => 'a',
            'ë' => 'e',
            'ï' => 'i',
            'ö' => 'o',
            'ü' => 'u',
            'Ä' => 'A',
            'Ë' => 'E',
            'Ï' => 'I',
            'Ö' => 'O',
            'Ü' => 'U'
        );
        $str = strtr($str, $unwanted_array);

        return mb_strtoupper($str, 'UTF-8');
    }

    public function procesarArchivo()
    {
        ob_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_huellero'])) {
            try {
                $rutaTemporal = $_FILES['archivo_huellero']['tmp_name'];
                $nombreArchivo = $_FILES['archivo_huellero']['name'];
                $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

                $datosAgrupadosCSV = [];
                $minTs = PHP_INT_MAX;
                $maxTs = 0;

                // 🔥 1. LEER EL CSV PRIMERO PARA AUTO-DETECTAR LAS FECHAS 🔥
                if ($extension === 'csv') {
                    if (($gestor = fopen($rutaTemporal, "r")) !== FALSE) {
                        while (($fila = fgetcsv($gestor, 10000, ",")) !== FALSE) {
                            if (count($fila) === 1 && strpos($fila[0], ',') !== false) {
                                $fila = explode(',', $fila[0]);
                            }

                            if (count($fila) >= 3) {
                                $nombreRaw = trim($fila[0]);
                                $nombre = mb_convert_encoding($nombreRaw, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                                $fecha = trim($fila[1]);
                                $hora = trim($fila[2]);

                                if (stripos($nombre, 'empleado') !== false || stripos($fecha, 'fecha') !== false)
                                    continue;
                                if (empty($nombre) || empty($fecha) || empty($hora))
                                    continue;

                                $fechaFormat = str_replace('/', '-', $fecha);
                                $ts = strtotime($fechaFormat);
                                if (!$ts)
                                    continue;

                                $fechaYmd = date('Y-m-d', $ts);

                                // Detectar límites de fecha automáticamente
                                if ($ts < $minTs)
                                    $minTs = $ts;
                                if ($ts > $maxTs)
                                    $maxTs = $ts;

                                if (!isset($datosAgrupadosCSV[$nombre])) {
                                    $datosAgrupadosCSV[$nombre] = [];
                                }
                                if (!isset($datosAgrupadosCSV[$nombre][$fechaYmd])) {
                                    $datosAgrupadosCSV[$nombre][$fechaYmd] = ['entrada' => $hora, 'salida' => $hora];
                                } else {
                                    if (strtotime($hora) < strtotime($datosAgrupadosCSV[$nombre][$fechaYmd]['entrada'])) {
                                        $datosAgrupadosCSV[$nombre][$fechaYmd]['entrada'] = $hora;
                                    }
                                    if (strtotime($hora) > strtotime($datosAgrupadosCSV[$nombre][$fechaYmd]['salida'])) {
                                        $datosAgrupadosCSV[$nombre][$fechaYmd]['salida'] = $hora;
                                    }
                                }
                            }
                        }
                        fclose($gestor);
                    }
                }

                if ($minTs === PHP_INT_MAX || $maxTs === 0) {
                    throw new Exception("El archivo CSV está vacío o tiene un formato de fecha irreconocible.");
                }

                // 🔥 2. CREAR EL RANGO DE FECHAS AUTO-DETECTADO 🔥
                $fechaInicioStr = date('Y-m-d', $minTs);
                $fechaFinStr = date('Y-m-d', $maxTs);

                $rangoFechas = [];
                for ($i = $minTs; $i <= $maxTs; $i += 86400) {
                    $rangoFechas[] = date('Y-m-d', $i);
                }

                // 🔥 3. CREAR MATRIZ PARA TODOS LOS EMPLEADOS CON EL RANGO ENCONTRADO 🔥
                $empleadosBd = $this->modelo->obtenerEmpleadosActivos();
                $datosOficiales = [];

                foreach ($empleadosBd as $emp) {
                    $nombreReal = $this->normalizarTexto($emp['nombre_bd']);
                    $datosOficiales[$nombreReal] = [
                        'nombre_original' => $emp['nombre_bd'],
                        'cargo' => $emp['cargo'],
                        'fechas' => []
                    ];
                    foreach ($rangoFechas as $f) {
                        $datosOficiales[$nombreReal]['fechas'][$f] = [
                            'entrada' => null,
                            'salida' => null,
                            'servicios' => 0
                        ];
                    }
                }

                // Match Difuso del CSV hacia los nombres oficiales
                foreach ($datosAgrupadosCSV as $nomCsv => $fechasCsv) {
                    $nomLimpio = $this->normalizarTexto($nomCsv);
                    $partes = explode(' ', $nomLimpio);

                    $keyOficial = null;

                    if (isset($datosOficiales[$nomLimpio])) {
                        $keyOficial = $nomLimpio;
                    } else {
                        $mejorPorcentaje = 0;
                        $mejorCandidato = null;
                        foreach (array_keys($datosOficiales) as $empClave) {
                            $todasLasPartes = true;
                            foreach ($partes as $p) {
                                if (empty($p))
                                    continue;
                                if (strpos($empClave, $p) === false) {
                                    $todasLasPartes = false;
                                    break;
                                }
                            }
                            if ($todasLasPartes) {
                                $keyOficial = $empClave;
                                break;
                            }

                            similar_text($nomLimpio, $empClave, $porcentaje);
                            if ($porcentaje > $mejorPorcentaje) {
                                $mejorPorcentaje = $porcentaje;
                                $mejorCandidato = $empClave;
                            }
                        }

                        if (!$keyOficial && $mejorPorcentaje >= 65) {
                            $keyOficial = $mejorCandidato;
                        }
                    }

                    if (!$keyOficial) {
                        $keyOficial = $nomLimpio;
                        $datosOficiales[$keyOficial] = [
                            'nombre_original' => $nomCsv,
                            'cargo' => 'No registrado en BD',
                            'fechas' => []
                        ];
                        foreach ($rangoFechas as $f) {
                            $datosOficiales[$keyOficial]['fechas'][$f] = ['entrada' => null, 'salida' => null, 'servicios' => 0];
                        }
                    }

                    foreach ($fechasCsv as $fechaYmd => $horas) {
                        $datosOficiales[$keyOficial]['fechas'][$fechaYmd]['entrada'] = $horas['entrada'];
                        $datosOficiales[$keyOficial]['fechas'][$fechaYmd]['salida'] = $horas['salida'];
                    }
                }

                // 🔥 4. CONSULTAR SERVICIOS EN EL INTERVALO DETECTADO 🔥
                $serviciosApp = $this->modelo->obtenerResumenServicios($fechaInicioStr, $fechaFinStr);

                foreach ($serviciosApp as $srv) {
                    $nomBdSrv = $this->normalizarTexto($srv['nombre_bd']);
                    $fechaYmd = $srv['fecha_ymd'];

                    $keyOficial = null;
                    if (isset($datosOficiales[$nomBdSrv])) {
                        $keyOficial = $nomBdSrv;
                    } else {
                        foreach (array_keys($datosOficiales) as $empClave) {
                            similar_text($empClave, $nomBdSrv, $perc);
                            if ($perc > 80) {
                                $keyOficial = $empClave;
                                break;
                            }
                        }
                    }

                    if (!$keyOficial)
                        continue;

                    // Ignorar servicios que caigan por fuera del rango del CSV (por seguridad)
                    if (!isset($datosOficiales[$keyOficial]['fechas'][$fechaYmd])) {
                        continue;
                    }

                    $curEntrada = $datosOficiales[$keyOficial]['fechas'][$fechaYmd]['entrada'];
                    $curSalida = $datosOficiales[$keyOficial]['fechas'][$fechaYmd]['salida'];

                    if (!empty($srv['entrada_srv'])) {
                        if (empty($curEntrada) || strtotime($srv['entrada_srv']) < strtotime($curEntrada)) {
                            $datosOficiales[$keyOficial]['fechas'][$fechaYmd]['entrada'] = $srv['entrada_srv'];
                        }
                    }

                    if (!empty($srv['salida_srv'])) {
                        if (empty($curSalida) || strtotime($srv['salida_srv']) > strtotime($curSalida) || $curSalida === $curEntrada) {
                            $datosOficiales[$keyOficial]['fechas'][$fechaYmd]['salida'] = $srv['salida_srv'];
                        }
                    }

                    $datosOficiales[$keyOficial]['fechas'][$fechaYmd]['servicios'] = $srv['cant_servicios'];
                }

                $diasES = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
                $resultadoFinal = [];

                foreach ($datosOficiales as $keyName => $personaInfo) {
                    foreach ($personaInfo['fechas'] as $fechaYmd => $data) {

                        $numeroDia = date('N', strtotime($fechaYmd));
                        $nombreDiaStr = $diasES[$numeroDia] . ' ' . date('d/m/Y', strtotime($fechaYmd));

                        $resultadoFinal[] = [
                            'nombre' => $personaInfo['nombre_original'],
                            'cargo' => $personaInfo['cargo'],
                            'fecha_raw' => $fechaYmd,
                            'fecha_formateada' => $nombreDiaStr,
                            'entrada' => !empty($data['entrada']) ? date('H:i', strtotime($data['entrada'])) : 'Falta Entrada',
                            'salida' => !empty($data['salida']) && $data['salida'] !== $data['entrada'] ? date('H:i', strtotime($data['salida'])) : (empty($data['servicios']) ? 'Falta Salida' : date('H:i', strtotime($data['entrada']))),
                            'servicios' => $data['servicios']
                        ];
                    }
                }

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['datos_asistencia_procesados'] = $resultadoFinal;

                // 🔥 AQUÍ ESTÁ LA MAGIA: Mandamos las fechas detectadas en el JSON 🔥
                $respuestaArray = [
                    'exito' => true,
                    'mensaje' => 'Generado automáticamente del ' . date('d/m/Y', $minTs) . ' al ' . date('d/m/Y', $maxTs),
                    'datos' => $resultadoFinal,
                    'fecha_inicio_detectada' => $fechaInicioStr,
                    'fecha_fin_detectada' => $fechaFinStr
                ];

            } catch (\Throwable $e) {
                $respuestaArray = ['exito' => false, 'error' => 'Error: ' . $e->getMessage() . ' Línea: ' . $e->getLine()];
            }
        } else {
            $respuestaArray = ['exito' => false, 'error' => 'No se recibió archivo.'];
        }

        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuestaArray, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function descargarExcel()
    {
        ob_start();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['datos_asistencia_procesados'])) {
            die("No hay datos procesados para descargar.");
        }

        $datos = $_SESSION['datos_asistencia_procesados'];

        $empleados = [];
        foreach ($datos as $reg) {
            $empleados[$reg['nombre']][] = $reg;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $sheetIndex = 0;

        $timeToFraction = function ($timeStr) {
            if (!$timeStr || strpos($timeStr, 'Falta') !== false)
                return null;
            $p = explode(':', $timeStr);
            $h = isset($p[0]) ? (int) $p[0] : 0;
            $m = isset($p[1]) ? (int) $p[1] : 0;
            return ($h + ($m / 60)) / 24;
        };

        foreach ($empleados as $nombre => $registros) {
            $sheet = $spreadsheet->createSheet($sheetIndex);

            $tituloHoja = mb_substr(preg_replace('/[^a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚ]/u', '', $nombre), 0, 30, 'UTF-8');
            $sheet->setTitle($tituloHoja);

            $sheet->setCellValue('A1', 'NOMBRE DEL TRABAJADOR');
            $sheet->setCellValue('C1', $nombre);
            $sheet->setCellValue('A2', 'CARGO');
            $sheet->setCellValue('C2', $registros[0]['cargo']);

            $sheet->setCellValue('E1', 'L-V');
            $sheet->setCellValue('F1', 'S');
            $sheet->setCellValue('G1', 'LÍMITE EXTRAS');
            $sheet->setCellValue('H1', 'INICIO TURNO');
            $sheet->setCellValue('I1', 'INICIO NOCTURNA');

            $sheet->setCellValue('E2', $timeToFraction('08:00'));
            $sheet->setCellValue('F2', $timeToFraction('04:00'));
            $sheet->setCellValue('G2', $timeToFraction('02:00'));
            $sheet->setCellValue('H2', $timeToFraction('07:00'));
            $sheet->setCellValue('I2', $timeToFraction('19:00'));

            $sheet->getStyle('E2:I2')->getNumberFormat()->setFormatCode('hh:mm');
            $sheet->getStyle('A1:A2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('A1:A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF548235');

            $semanas = [];
            foreach ($registros as $reg) {
                // USAR LA FECHA RAW (Y-m-d) PARA LOS CÁLCULOS MATEMÁTICOS DEL EXCEL
                $timestamp = strtotime($reg['fecha_raw']);

                if ($timestamp) {
                    $numeroSemana = date('W', $timestamp);
                    $semanas[$numeroSemana][] = $reg;
                } else {
                    $semanas['Extra'][] = $reg;
                }
            }

            ksort($semanas);
            $fila = 4;

            $celdasTotalesTrabajado = [];
            $celdasTotalesExtras = [];
            $celdasTotalesDominicales = [];
            $celdasTotalesNocturnas = [];
            $celdasTotalesServicios = [];

            foreach ($semanas as $numSemana => $registrosSemana) {
                $sheet->setCellValue('A' . $fila, "REPORTE SEMANA " . $numSemana);
                $sheet->mergeCells("A{$fila}:H{$fila}");
                $sheet->getStyle("A{$fila}:H{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle("A{$fila}:H{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');

                $fila++;

                $sheet->setCellValue('A' . $fila, 'FECHA');
                $sheet->setCellValue('B' . $fila, 'H. ENTRADA');
                $sheet->setCellValue('C' . $fila, 'H. SALIDA');
                $sheet->setCellValue('D' . $fila, 'TOTAL TRABAJADO');
                $sheet->setCellValue('E' . $fila, 'H. EXTRAS');
                $sheet->setCellValue('F' . $fila, 'NOCTURNA');
                $sheet->setCellValue('G' . $fila, 'SERVICIOS');
                $sheet->setCellValue('H' . $fila, 'NOVEDADES');

                $sheet->getStyle("A{$fila}:H{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle("A{$fila}:H{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF385D22');

                $fila++;
                $startRow = $fila;

                usort($registrosSemana, function ($a, $b) {
                    return strtotime($a['fecha_raw']) - strtotime($b['fecha_raw']);
                });

                foreach ($registrosSemana as $reg) {
                    $timestamp = strtotime($reg['fecha_raw']);

                    if ($timestamp) {
                        $sheet->setCellValue('A' . $fila, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($timestamp));
                        // 🔥 MAGIA AQUÍ: Obliga a Excel a mostrar "lunes 15-05-2026" pero conservando la fórmula de fechas
                        $sheet->getStyle('A' . $fila)->getNumberFormat()->setFormatCode('[$-es-ES]dddd dd-mm-yyyy;@');
                    } else {
                        $sheet->setCellValue('A' . $fila, $reg['fecha_formateada']);
                    }

                    $valEntrada = $timeToFraction($reg['entrada']);
                    $valSalida = $timeToFraction($reg['salida']);

                    if ($valEntrada !== null) {
                        $sheet->setCellValue('B' . $fila, $valEntrada);
                        $sheet->getStyle('B' . $fila)->getNumberFormat()->setFormatCode('hh:mm');
                    } else {
                        $sheet->setCellValue('B' . $fila, 'FALTA ENT');
                    }

                    if ($valSalida !== null) {
                        $sheet->setCellValue('C' . $fila, $valSalida);
                        $sheet->getStyle('C' . $fila)->getNumberFormat()->setFormatCode('hh:mm');

                        // 🔥 CORRECCIÓN: DE LUNES A VIERNES RESTA 1 HORA (1/24), SÁBADOS (O DOMINGOS) RESTA 0 🔥
                        $formulaTrabajado = "=IF(ISNUMBER(C{$fila}), MAX(0, C{$fila}-MAX(B{$fila}, \$H\$2) - IF(WEEKDAY(A{$fila},2)<6, 1/24, 0)), \"\")";
                        $sheet->setCellValue('D' . $fila, $formulaTrabajado);
                        $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');

                        $formulaExtras = "=IF(ISNUMBER(D{$fila}), IF((D{$fila}-IF(WEEKDAY(A{$fila},2)<6, \$E\$2, \$F\$2))>\$G\$2, \$G\$2, IF(D{$fila}>IF(WEEKDAY(A{$fila},2)<6, \$E\$2, \$F\$2), D{$fila}-IF(WEEKDAY(A{$fila},2)<6, \$E\$2, \$F\$2), 0)), \"\")";
                        $sheet->setCellValue('E' . $fila, $formulaExtras);
                        $sheet->getStyle('E' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');

                        $sheet->setCellValue('F' . $fila, "=IF(ISNUMBER(C{$fila}), MAX(0, C{$fila}-\$I\$2), \"\")");
                        $sheet->getStyle('F' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');
                    } else {
                        $sheet->setCellValue('C' . $fila, 'FALTA SALIDA');

                    }

                    $sheet->setCellValue('G' . $fila, $reg['servicios'] > 0 ? $reg['servicios'] : '');
                    $fila++;
                }

                $endRow = $fila - 1;

                $sheet->setCellValue('C' . $fila, 'REPORTE SEMANAL');
                $sheet->setCellValue('D' . $fila, "=SUM(D{$startRow}:D{$endRow})");
                $sheet->setCellValue('E' . $fila, "=SUM(E{$startRow}:E{$endRow})");
                $sheet->setCellValue('F' . $fila, "=SUM(F{$startRow}:F{$endRow})");
                $sheet->setCellValue('G' . $fila, "=SUM(G{$startRow}:G{$endRow})");

                $sheet->getStyle("A{$fila}:H{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF996600');
                $sheet->getStyle("A{$fila}:H{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle("D{$fila}:F{$fila}")->getNumberFormat()->setFormatCode('[h]:mm');

                $celdasTotalesTrabajado[] = "D" . $fila;
                $celdasTotalesServicios[] = "G" . $fila;

                $fila++;
                $sheet->setCellValue('C' . $fila, 'TOTAL HORAS EXTRAS A PAGAR');
                $sheet->setCellValue('D' . $fila, "=E" . ($fila - 1));
                $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2F5597');
                $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');
                $celdasTotalesExtras[] = "D" . $fila;

                $fila++;
                $sheet->setCellValue('C' . $fila, 'TOTAL HORAS DOMINICALES A PAGAR');
                $sheet->setCellValue('D' . $fila, 0);
                $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC55A11');
                $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $celdasTotalesDominicales[] = "D" . $fila;

                $fila++;
                $sheet->setCellValue('C' . $fila, 'TOTAL EXTRAS NOCTURNAS A PAGAR');
                $sheet->setCellValue('D' . $fila, "=F" . ($fila - 3));
                $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF7F7F7F');
                $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');
                $celdasTotalesNocturnas[] = "D" . $fila;

                $fila += 3;
            }

            $sheet->setCellValue('B' . $fila, 'CONSOLIDADO FINAL DEL MES');
            $sheet->mergeCells("B{$fila}:D{$fila}");

            $sheet->getStyle("B{$fila}:D{$fila}")->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle("B{$fila}:D{$fila}")->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle("B{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF000000');

            $fila++;
            $sheet->setCellValue('C' . $fila, 'GRAN TOTAL HORAS TRABAJADAS');
            $sheet->setCellValue('D' . $fila, empty($celdasTotalesTrabajado) ? 0 : "=SUM(" . implode(',', $celdasTotalesTrabajado) . ")");
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF996600');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');

            $fila++;
            $sheet->setCellValue('C' . $fila, 'GRAN TOTAL HORAS EXTRAS');
            $sheet->setCellValue('D' . $fila, empty($celdasTotalesExtras) ? 0 : "=SUM(" . implode(',', $celdasTotalesExtras) . ")");
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2F5597');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');

            $fila++;
            $sheet->setCellValue('C' . $fila, 'GRAN TOTAL DOMINICALES');
            $sheet->setCellValue('D' . $fila, empty($celdasTotalesDominicales) ? 0 : "=SUM(" . implode(',', $celdasTotalesDominicales) . ")");
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC55A11');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');

            $fila++;
            $sheet->setCellValue('C' . $fila, 'GRAN TOTAL NOCTURNAS');
            $sheet->setCellValue('D' . $fila, empty($celdasTotalesNocturnas) ? 0 : "=SUM(" . implode(',', $celdasTotalesNocturnas) . ")");
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF7F7F7F');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');

            $fila++;
            $sheet->setCellValue('C' . $fila, 'GRAN TOTAL SERVICIOS (TICKETS)');
            $sheet->setCellValue('D' . $fila, empty($celdasTotalesServicios) ? 0 : "=SUM(" . implode(',', $celdasTotalesServicios) . ")");
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');

            $sheet->getStyle("A1:H{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A1:H{$fila}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getColumnDimension('A')->setWidth(25); // <--- Más ancho para que quepa "Miércoles 15-05-2026"
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(15);

            $sheetIndex++;
        }

        if ($sheetIndex > 0) {
            $spreadsheet->setActiveSheetIndex(0);
        }

        ob_end_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Consolidado_Nomina_Mensual.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        exit;
    }
}
?>