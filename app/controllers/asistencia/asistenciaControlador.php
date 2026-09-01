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
use PhpOffice\PhpSpreadsheet\Style\Border;

class AsistenciaControlador
{
    private $modelo;
    private $db;

    // Tasas de recargo/hora extra según legislación colombiana (igual a tu plantilla de Excel)
    const TASA_HED = 0.25;    // Hora extra diurna
    const TASA_HEN = 0.75;    // Hora extra nocturna
    const TASA_RN = 0.35;     // Recargo nocturno ordinario
    const TASA_RDF = 0.75;    // Recargo dominical/festivo ordinario
    const TASA_HEDDF = 1.00;  // Hora extra diurna dominical/festivo
    const TASA_HENDF = 1.50;  // Hora extra nocturna dominical/festivo
    const TASA_RNDF = 1.10;   // Recargo nocturno dominical/festivo
    const DIVISOR_HORAS_MES = 210; // Ajusta si tu jornada legal cambia (Ley 2101, etc.)

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

                        if (!$keyOficial && $mejorPorcentaje >= 85) {
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
                            if ($perc > 85) {
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

                // Festivos del rango, para marcar visualmente en la vista editable también
                $festivosRango = $this->modelo->obtenerFestivos($fechaInicioStr, $fechaFinStr);

                foreach ($datosOficiales as $keyName => $personaInfo) {
                    foreach ($personaInfo['fechas'] as $fechaYmd => $data) {

                        $numeroDia = date('N', strtotime($fechaYmd));
                        $nombreDiaStr = $diasES[$numeroDia] . ' ' . date('d/m/Y', strtotime($fechaYmd));
                        $esDomFest = ($numeroDia == 7 || isset($festivosRango[$fechaYmd])) ? 1 : 0;

                        // Valores "en crudo" (HH:MM o vacío) listos para <input type="time">
                        $entradaValor = !empty($data['entrada']) ? date('H:i', strtotime($data['entrada'])) : '';
                        $salidaValor = !empty($data['salida']) ? date('H:i', strtotime($data['salida'])) : '';

                        $resultadoFinal[] = [
                            'id' => md5($keyName . '|' . $fechaYmd),
                            'nombre' => $personaInfo['nombre_original'],
                            'cargo' => $personaInfo['cargo'],
                            'fecha_raw' => $fechaYmd,
                            'fecha_formateada' => $nombreDiaStr,
                            'dom_fest' => $esDomFest,
                            'entrada' => $entradaValor !== '' ? $entradaValor : 'Falta Entrada',
                            'salida' => (!empty($data['salida']) && $data['salida'] !== $data['entrada'])
                                ? $salidaValor
                                : (empty($data['servicios']) ? 'Falta Salida' : $entradaValor),
                            'entrada_valor' => $entradaValor,
                            'salida_valor' => (!empty($data['salida']) && $data['salida'] !== $data['entrada']) ? $salidaValor : ($entradaValor && !empty($data['servicios']) ? $entradaValor : ''),
                            'servicios' => $data['servicios'],
                            'novedades' => ''
                        ];
                    }
                }

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['datos_asistencia_procesados'] = $resultadoFinal;
                $_SESSION['salarios_empleados'] = []; // se llena en guardarEdicion()

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

    // 🔥 NUEVO: GUARDA LOS CAMBIOS HECHOS EN LA TABLA EDITABLE (entrada/salida/servicios/novedades/salario)
    // ANTES de generar el Excel final. Este es el paso que faltaba entre "procesar" y "descargar".
    public function guardarEdicion()
    {
        ob_start();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            if (!isset($_SESSION['datos_asistencia_procesados'])) {
                throw new Exception('No hay una sesión de procesamiento activa. Vuelve a subir el archivo.');
            }

            $crudo = $_POST['datos'] ?? null;
            if (!$crudo) {
                throw new Exception('No se recibieron datos para guardar.');
            }

            $payload = json_decode($crudo, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($payload['registros'])) {
                throw new Exception('Formato de datos inválido.');
            }

            // Indexamos lo que ya teníamos en sesión por id, para actualizar solo lo editado
            $porId = [];
            foreach ($_SESSION['datos_asistencia_procesados'] as $i => $reg) {
                $porId[$reg['id']] = $i;
            }

            foreach ($payload['registros'] as $edit) {
                if (!isset($edit['id']) || !isset($porId[$edit['id']]))
                    continue;
                $i = $porId[$edit['id']];

                $entradaValor = trim($edit['entrada_valor'] ?? '');
                $salidaValor = trim($edit['salida_valor'] ?? '');

                $_SESSION['datos_asistencia_procesados'][$i]['entrada_valor'] = $entradaValor;
                $_SESSION['datos_asistencia_procesados'][$i]['salida_valor'] = $salidaValor;
                $_SESSION['datos_asistencia_procesados'][$i]['entrada'] = $entradaValor !== '' ? $entradaValor : 'Falta Entrada';
                $_SESSION['datos_asistencia_procesados'][$i]['salida'] = $salidaValor !== '' ? $salidaValor : 'Falta Salida';
                $_SESSION['datos_asistencia_procesados'][$i]['servicios'] = (int) ($edit['servicios'] ?? 0);
                $_SESSION['datos_asistencia_procesados'][$i]['novedades'] = trim((string) ($edit['novedades'] ?? ''));
            }

            $salarios = [];
            if (isset($payload['salarios']) && is_array($payload['salarios'])) {
                foreach ($payload['salarios'] as $nombre => $valor) {
                    $salarios[$nombre] = (float) $valor;
                }
            }
            $_SESSION['salarios_empleados'] = $salarios;

            $respuestaArray = ['exito' => true, 'mensaje' => 'Cambios guardados. Ya puedes descargar el reporte.'];

        } catch (\Throwable $e) {
            $respuestaArray = ['exito' => false, 'error' => $e->getMessage()];
        }

        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuestaArray, JSON_UNESCAPED_UNICODE);
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
        $salariosEmpleados = $_SESSION['salarios_empleados'] ?? [];

        // Festivos del rango real de los datos (por si el usuario editó fechas o volvió a entrar a la sesión)
        $fechasRaw = array_column($datos, 'fecha_raw');
        $fechaInicioStr = !empty($fechasRaw) ? min($fechasRaw) : date('Y-m-d');
        $fechaFinStr = !empty($fechasRaw) ? max($fechasRaw) : date('Y-m-d');
        $festivos = $this->modelo->obtenerFestivos($fechaInicioStr, $fechaFinStr);

        $empleados = [];
        foreach ($datos as $reg) {
            $empleados[$reg['nombre']][] = $reg;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $sheetIndex = 0;

        $resumenParaHojaFinal = [];

        $timeToFraction = function ($timeStr) {
            if (!$timeStr || stripos($timeStr, 'Falta') !== false)
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

            // El turno dura 9 horas de corrido (8 horas de trabajo + 1 de almuerzo)
            $sheet->setCellValue('E2', $timeToFraction('09:00'));
            $sheet->setCellValue('F2', $timeToFraction('04:00'));
            $sheet->setCellValue('G2', $timeToFraction('02:00'));

            // Lógica dinámica para el Excel
            $cargoEvaluar = mb_strtoupper($registros[0]['cargo'], 'UTF-8');
            if (strpos($cargoEvaluar, 'TÉCNICO') !== false || strpos($cargoEvaluar, 'TECNICO') !== false) {
                // Motorizados: Arrancan desde su primer servicio ("REAL")
                $sheet->setCellValue('H2', 'REAL');
            } else {
                // Administrativos: Su turno SIEMPRE arranca a las 07:00
                $sheet->setCellValue('H2', $timeToFraction('07:00'));
            }

            $sheet->setCellValue('I2', $timeToFraction('19:00'));

            // Dar formato
            $sheet->getStyle('E2:G2')->getNumberFormat()->setFormatCode('hh:mm');
            $sheet->getStyle('I2')->getNumberFormat()->setFormatCode('hh:mm');
            if (!is_string($sheet->getCell('H2')->getValue())) {
                $sheet->getStyle('H2')->getNumberFormat()->setFormatCode('hh:mm');
            }

            $sheet->getStyle('A1:A2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('A1:A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF548235');

            // 🔥 BLOQUE DE SALARIO / TASAS (columnas N-O) — esto es lo que hace "vivas" las fórmulas de pesos.
            // Cambiar el valor de O1 (Salario Básico) recalcula automáticamente TODA la hoja, igual que tu plantilla.
            $salarioBase = $salariosEmpleados[$nombre] ?? 0;

            $sheet->setCellValue('N1', 'SALARIO BÁSICO');
            $sheet->setCellValue('O1', $salarioBase);
            $sheet->setCellValue('N2', 'VALOR HORA BÁSICA');
            $sheet->setCellValue('O2', "=O1/" . self::DIVISOR_HORAS_MES);
            $sheet->setCellValue('N3', 'REC. H.E. DIURNA (25%)');
            $sheet->setCellValue('O3', "=\$O\$2*" . self::TASA_HED);
            $sheet->setCellValue('N4', 'REC. H.E. NOCTURNA (75%)');
            $sheet->setCellValue('O4', "=\$O\$2*" . self::TASA_HEN);
            $sheet->setCellValue('N5', 'REC. NOCTURNO ORDINARIO (35%)');
            $sheet->setCellValue('O5', "=\$O\$2*" . self::TASA_RN);
            $sheet->setCellValue('N6', 'REC. DOM/FEST ORDINARIO (75%)');
            $sheet->setCellValue('O6', "=\$O\$2*" . self::TASA_RDF);
            $sheet->setCellValue('N7', 'REC. H.E. DIURNA DOM/FEST (100%)');
            $sheet->setCellValue('O7', "=\$O\$2*" . self::TASA_HEDDF);
            $sheet->setCellValue('N8', 'REC. H.E. NOCTURNA DOM/FEST (150%)');
            $sheet->setCellValue('O8', "=\$O\$2*" . self::TASA_HENDF);
            $sheet->setCellValue('N9', 'REC. NOCTURNO DOM/FEST (110%)');
            $sheet->setCellValue('O9', "=\$O\$2*" . self::TASA_RNDF);

            $sheet->getStyle('N1:N9')->getFont()->setBold(true);
            $sheet->getStyle('O1:O9')->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('O1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('O1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');
            $sheet->getColumnDimension('N')->setWidth(28);
            $sheet->getColumnDimension('O')->setWidth(14);

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
            $celdasTotalesHorasExtras = [];
            $celdasTotalesValorExtras = [];
            $celdasTotalesDominicales = [];
            $celdasTotalesServicios = [];

            foreach ($semanas as $numSemana => $registrosSemana) {
                $sheet->setCellValue('A' . $fila, "REPORTE SEMANA " . $numSemana);
                $sheet->mergeCells("A{$fila}:K{$fila}");
                $sheet->getStyle("A{$fila}:K{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle("A{$fila}:K{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');

                $fila++;

                $sheet->setCellValue('A' . $fila, 'FECHA');
                $sheet->setCellValue('B' . $fila, 'H. ENTRADA');
                $sheet->setCellValue('C' . $fila, 'H. SALIDA');
                $sheet->setCellValue('D' . $fila, 'TOTAL TRABAJADO');
                $sheet->setCellValue('E' . $fila, 'H. EXTRA DIURNA');
                $sheet->setCellValue('F' . $fila, 'H. EXTRA NOCTURNA');
                $sheet->setCellValue('G' . $fila, 'DOM/FEST');
                $sheet->setCellValue('H' . $fila, 'VALOR H.E. DIURNA');
                $sheet->setCellValue('I' . $fila, 'VALOR H.E. NOCTURNA');
                $sheet->setCellValue('J' . $fila, 'SERVICIOS');
                $sheet->setCellValue('K' . $fila, 'NOVEDADES');

                $sheet->getStyle("A{$fila}:K{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle("A{$fila}:K{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF385D22');

                $fila++;
                $startRow = $fila;

                usort($registrosSemana, function ($a, $b) {
                    return strtotime($a['fecha_raw']) - strtotime($b['fecha_raw']);
                });

                foreach ($registrosSemana as $reg) {
                    $timestamp = strtotime($reg['fecha_raw']);

                    if ($timestamp) {
                        $sheet->setCellValue('A' . $fila, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($timestamp));
                        // Obliga a Excel a mostrar "lunes 15-05-2026" pero conservando la fórmula de fechas
                        $sheet->getStyle('A' . $fila)->getNumberFormat()->setFormatCode('[$-es-ES]dddd dd-mm-yyyy;@');
                    } else {
                        $sheet->setCellValue('A' . $fila, $reg['fecha_formateada']);
                    }

                    // Días festivos: domingo automático + tabla de festivos de la BD
                    $esDomFest = ($timestamp && (date('N', $timestamp) == 7 || isset($festivos[$reg['fecha_raw']]))) ? 1 : 0;
                    $sheet->setCellValue('G' . $fila, $esDomFest);

                    $entradaTxt = $reg['entrada_valor'] ?? $reg['entrada'];
                    $salidaTxt = $reg['salida_valor'] ?? $reg['salida'];
                    $valEntrada = $timeToFraction($entradaTxt);
                    $valSalida = $timeToFraction($salidaTxt);

                    if ($valEntrada !== null) {
                        $sheet->setCellValue('B' . $fila, $valEntrada);
                        $sheet->getStyle('B' . $fila)->getNumberFormat()->setFormatCode('hh:mm');
                    } else {
                        $sheet->setCellValue('B' . $fila, 'FALTA ENT');
                    }

                    if ($valSalida !== null) {
                        $sheet->setCellValue('C' . $fila, $valSalida);
                        $sheet->getStyle('C' . $fila)->getNumberFormat()->setFormatCode('hh:mm');

                        // 1. FÓRMULA DE TOTAL TRABAJADO
                        $formulaTrabajado = "=IF(AND(ISNUMBER(B{$fila}), ISNUMBER(C{$fila})), MAX(0, C{$fila} - IF(ISTEXT(\$H\$2), B{$fila}, MAX(B{$fila}, \$H\$2))), \"\")";
                        $sheet->setCellValue('D' . $fila, $formulaTrabajado);
                        $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');

                        // 2. LÍMITE DEL DÍA (9h L-V, 4h Sábados) y extras totales con tope
                        $limiteHoras = "IF(WEEKDAY(A{$fila},2)<6, \$E\$2, \$F\$2)";
                        $totalExtrasBruto = "MAX(0, D{$fila} - $limiteHoras)";
                        $totalExtrasConTope = "IF($totalExtrasBruto > \$G\$2, \$G\$2, $totalExtrasBruto)";

                        // 3. HORAS EXTRA NOCTURNA (después de $I$2, sin pasarse del tope de extras)
                        $formulaNocturnas = "=IF(ISNUMBER(C{$fila}), MAX(0, MIN($totalExtrasConTope, MAX(0, C{$fila}-\$I\$2))), \"\")";
                        $sheet->setCellValue('F' . $fila, $formulaNocturnas);
                        $sheet->getStyle('F' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');

                        // 4. HORAS EXTRA DIURNA (extras totales menos las que ya se pagan como nocturnas)
                        $formulaExtrasDiurnas = "=IF(ISNUMBER(D{$fila}), MAX(0, $totalExtrasConTope - F{$fila}), \"\")";
                        $sheet->setCellValue('E' . $fila, $formulaExtrasDiurnas);
                        $sheet->getStyle('E' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');

                        // 5. 🔥 VALOR EN PESOS: si el día es Dom/Fest usa la tasa DF, si no, la tasa normal.
                        //    Referencian el bloque de salario (O3/O4/O7/O8) -> "vivo" ante cualquier cambio de salario.
                        $formulaValorDiurna = "=IF(ISNUMBER(E{$fila}), E{$fila}*24*IF(G{$fila}=1, \$O\$7, \$O\$3), \"\")";
                        $sheet->setCellValue('H' . $fila, $formulaValorDiurna);
                        $sheet->getStyle('H' . $fila)->getNumberFormat()->setFormatCode('#,##0');

                        $formulaValorNocturna = "=IF(ISNUMBER(F{$fila}), F{$fila}*24*IF(G{$fila}=1, \$O\$8, \$O\$4), \"\")";
                        $sheet->setCellValue('I' . $fila, $formulaValorNocturna);
                        $sheet->getStyle('I' . $fila)->getNumberFormat()->setFormatCode('#,##0');
                    } else {
                        $sheet->setCellValue('C' . $fila, 'FALTA SALIDA');
                    }

                    $sheet->setCellValue('J' . $fila, $reg['servicios'] > 0 ? $reg['servicios'] : '');
                    $sheet->setCellValue('K' . $fila, $reg['novedades'] ?? '');

                    if ($esDomFest) {
                        $sheet->getStyle('A' . $fila . ':K' . $fila)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');
                    }

                    $fila++;
                }

                $endRow = $fila - 1;

                $sheet->setCellValue('C' . $fila, 'REPORTE SEMANAL');
                $sheet->setCellValue('D' . $fila, "=SUM(D{$startRow}:D{$endRow})");
                $sheet->setCellValue('E' . $fila, "=SUM(E{$startRow}:E{$endRow})");
                $sheet->setCellValue('F' . $fila, "=SUM(F{$startRow}:F{$endRow})");
                $sheet->setCellValue('H' . $fila, "=SUM(H{$startRow}:H{$endRow})");
                $sheet->setCellValue('I' . $fila, "=SUM(I{$startRow}:I{$endRow})");
                $sheet->setCellValue('J' . $fila, "=SUM(J{$startRow}:J{$endRow})");

                $sheet->getStyle("A{$fila}:K{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF996600');
                $sheet->getStyle("A{$fila}:K{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle("D{$fila}:F{$fila}")->getNumberFormat()->setFormatCode('[h]:mm');
                $sheet->getStyle("H{$fila}:I{$fila}")->getNumberFormat()->setFormatCode('#,##0');

                $filaResumenSemana = $fila;
                $celdasTotalesTrabajado[] = "D" . $filaResumenSemana;
                $celdasTotalesServicios[] = "J" . $filaResumenSemana;

                $fila++;
                $sheet->setCellValue('C' . $fila, 'TOTAL HORAS EXTRAS A PAGAR (horas)');
                $sheet->setCellValue('D' . $fila, "=E{$filaResumenSemana}+F{$filaResumenSemana}");
                $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2F5597');
                $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');
                $celdasTotalesHorasExtras[] = "D" . $fila;

                $fila++;
                $sheet->setCellValue('C' . $fila, 'TOTAL $ HORAS EXTRAS A PAGAR');
                $sheet->setCellValue('D' . $fila, "=H{$filaResumenSemana}+I{$filaResumenSemana}");
                $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF107C41');
                $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('#,##0');
                $celdasTotalesValorExtras[] = "D" . $fila;

                $fila++;
                $sheet->setCellValue('C' . $fila, 'TOTAL HORAS DOMINICALES/RECARGOS ORD. (manual)');
                $sheet->setCellValue('D' . $fila, 0);
                $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC55A11');
                $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $celdasTotalesDominicales[] = "D" . $fila;

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
            $sheet->setCellValue('C' . $fila, 'GRAN TOTAL HORAS EXTRAS (horas)');
            $sheet->setCellValue('D' . $fila, empty($celdasTotalesHorasExtras) ? 0 : "=SUM(" . implode(',', $celdasTotalesHorasExtras) . ")");
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2F5597');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('[h]:mm');
            $filaTotalHorasExtras = $fila;

            $fila++;
            $sheet->setCellValue('C' . $fila, 'GRAN TOTAL $ HORAS EXTRAS A PAGAR');
            $sheet->setCellValue('D' . $fila, empty($celdasTotalesValorExtras) ? 0 : "=SUM(" . implode(',', $celdasTotalesValorExtras) . ")");
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF107C41');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('#,##0');
            $filaTotalValorExtras = $fila;

            $fila++;
            $sheet->setCellValue('C' . $fila, 'GRAN TOTAL DOMINICALES/RECARGOS ORD. (manual)');
            $sheet->setCellValue('D' . $fila, empty($celdasTotalesDominicales) ? 0 : "=SUM(" . implode(',', $celdasTotalesDominicales) . ")");
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC55A11');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');

            $fila++;
            $sheet->setCellValue('C' . $fila, 'GRAN TOTAL SERVICIOS (TICKETS)');
            $sheet->setCellValue('D' . $fila, empty($celdasTotalesServicios) ? 0 : "=SUM(" . implode(',', $celdasTotalesServicios) . ")");
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');

            $sheet->getStyle("A1:K{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A1:K{$fila}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(13);
            $sheet->getColumnDimension('C')->setWidth(22);
            $sheet->getColumnDimension('D')->setWidth(18);
            $sheet->getColumnDimension('E')->setWidth(14);
            $sheet->getColumnDimension('F')->setWidth(14);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(15);
            $sheet->getColumnDimension('J')->setWidth(12);
            $sheet->getColumnDimension('K')->setWidth(22);

            $resumenParaHojaFinal[] = [
                'nombre' => $nombre,
                'hoja' => $tituloHoja,
                'celda_horas_extras' => 'D' . $filaTotalHorasExtras,
                'celda_valor_extras' => 'D' . $filaTotalValorExtras,
                'celda_salario' => 'O1'
            ];

            $sheetIndex++;
        }

        // CREAR LA HOJA DE RESUMEN FINAL
        if (!empty($resumenParaHojaFinal)) {
            $sheetResumen = $spreadsheet->createSheet($sheetIndex);
            $sheetResumen->setTitle('Resumen Extras');

            $sheetResumen->setCellValue('A1', 'NOMBRE DE LA PERSONA');
            $sheetResumen->setCellValue('B1', 'SALARIO BÁSICO');
            $sheetResumen->setCellValue('C1', 'HORAS EXTRA (TOTAL)');
            $sheetResumen->setCellValue('D1', 'VALOR TOTAL A PAGAR ($)');
            $sheetResumen->setCellValue('E1', 'ACTIVIDAD REALIZADA');
            $sheetResumen->setCellValue('F1', 'QUIEN AUTORIZA');

            $sheetResumen->getStyle('A1:F1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheetResumen->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');
            $sheetResumen->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheetResumen->getStyle('A1:F1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            $filaResumen = 2;
            foreach ($resumenParaHojaFinal as $res) {
                $sheetResumen->setCellValue('A' . $filaResumen, $res['nombre']);

                $hoja = "'" . $res['hoja'] . "'";
                $celdaHoras = $res['celda_horas_extras'];
                $celdaValor = $res['celda_valor_extras'];
                $celdaSalario = $res['celda_salario'];

                $sheetResumen->setCellValue('B' . $filaResumen, "={$hoja}!{$celdaSalario}");
                // Condicional para que quede en blanco si es 0
                $sheetResumen->setCellValue('C' . $filaResumen, "=IF({$hoja}!{$celdaHoras}=0, \"\", {$hoja}!{$celdaHoras})");
                $sheetResumen->setCellValue('D' . $filaResumen, "=IF({$hoja}!{$celdaValor}=0, \"\", {$hoja}!{$celdaValor})");

                $sheetResumen->getStyle('B' . $filaResumen)->getNumberFormat()->setFormatCode('#,##0');
                $sheetResumen->getStyle('C' . $filaResumen)->getNumberFormat()->setFormatCode('[h]:mm;;');
                $sheetResumen->getStyle('D' . $filaResumen)->getNumberFormat()->setFormatCode('#,##0;;');
                $sheetResumen->getStyle("A{$filaResumen}:F{$filaResumen}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheetResumen->getStyle("B{$filaResumen}:D{$filaResumen}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $filaResumen++;
            }

            $sheetResumen->getColumnDimension('A')->setWidth(35);
            $sheetResumen->getColumnDimension('B')->setWidth(18);
            $sheetResumen->getColumnDimension('C')->setWidth(20);
            $sheetResumen->getColumnDimension('D')->setWidth(22);
            $sheetResumen->getColumnDimension('E')->setWidth(35);
            $sheetResumen->getColumnDimension('F')->setWidth(25);

            $styleArrayBordes = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ];
            $sheetResumen->getStyle('A1:F' . ($filaResumen - 1))->applyFromArray($styleArrayBordes);
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