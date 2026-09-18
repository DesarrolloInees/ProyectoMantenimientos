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

    // ==================================================================
    // TASAS DE RECARGO / HORA EXTRA (Ley 2466 — valores vigentes 2026)
    // Son SOLO los valores POR DEFECTO: la vista permite editarlos por
    // empleado y el Excel los escribe en el bloque de tarifas (Y/Z/AA),
    // de modo que la plantilla queda 100% editable y "viva".
    // ==================================================================
    const TASA_HED = 0.25;    // Recargo H.E. diurna          (base 100% + 25%  = 125%)
    const TASA_HEN = 0.75;    // Recargo H.E. nocturna        (base 100% + 75%  = 175%)
    const TASA_RN = 0.35;     // Recargo nocturno ordinario
    const TASA_RDF = 0.90;    // Recargo dom/fest ordinario (Ley 2466: 90% en 2026)
    const TASA_HEDDF = 1.15;  // Recargo H.E. diurna dom/fest  (base 100% + 115% = 215%)
    const TASA_HENDF = 1.65;  // Recargo H.E. nocturna dom/fest(base 100% + 165% = 265%)
    const TASA_RNDF = 1.25;   // Recargo nocturno dom/fest     (90% + 35% = 125%)
    const DIVISOR_HORAS_MES = 210; // Divisor de horas del mes (editable en la vista)

    // Horarios / parámetros por defecto (editables por empleado en la vista)
    const HORARIO_DEFECTO = [
        'lv' => '09:00',       // jornada L-V
        'sab' => '04:00',      // jornada sábado
        'tope' => '02:00',     // tope de extras por día
        'nocturna' => '19:00', // inicio de la franja nocturna
        'turno' => 'REAL',     // REAL = arranca con la primera marcación
    ];

    // Columnas manuales (se digitan/auto-calculan y viajan al Excel)
    const CAMPOS_RECARGOS = ['rec_nocturno', 'rec_domfest', 'hed_df', 'hen_df', 'rec_noct_df'];

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new AsistenciaModelo($this->db);
    }

    public function index()
    {
        $titulo = "Procesador de Asistencias (Huellero + Servicios)";

        // Valores por defecto que la vista usa para pintar los controles de tasas.
        // Si el usuario los cambia, viajan al backend en guardarEdicion() y se
        // escriben dentro del Excel (bloque Y/Z/AA de cada hoja).
        $tasasDefecto = $this->tasasPorDefecto();
        $horarioDefecto = self::HORARIO_DEFECTO;

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
                $_SESSION['salarios_empleados'] = [];   // se llena en guardarEdicion()
                $_SESSION['config_empleados'] = [];     // se llena en guardarEdicion()

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

                // 🔥 NUEVO: columnas manuales de recargos (llegan como "HH:MM")
                foreach (self::CAMPOS_RECARGOS as $campo) {
                    $_SESSION['datos_asistencia_procesados'][$i][$campo] = $this->normalizarHoraManual($edit[$campo] ?? '');
                }
            }

            // 🔥 NUEVO: configuración por empleado (salario, horarios, tasas
            // editables, divisor y datos del acta para la hoja "Resumen Extras")
            $configEmpleados = [];
            if (isset($payload['empleados']) && is_array($payload['empleados'])) {
                foreach ($payload['empleados'] as $nombre => $info) {
                    if (!is_array($info))
                        continue;
                    $configEmpleados[(string) $nombre] = $info;
                }
            }

            // Compatibilidad con el formato anterior (que enviaba solo "salarios")
            $salarios = [];
            if (isset($payload['salarios']) && is_array($payload['salarios'])) {
                foreach ($payload['salarios'] as $nombre => $valor) {
                    $salarios[$nombre] = (float) $valor;
                    if (!isset($configEmpleados[$nombre])) {
                        $configEmpleados[$nombre] = ['salario' => (float) $valor];
                    }
                }
            }
            foreach ($configEmpleados as $nombre => $info) {
                if (isset($info['salario']) && is_numeric($info['salario'])) {
                    $salarios[$nombre] = (float) $info['salario'];
                }
            }
            $_SESSION['salarios_empleados'] = $salarios;
            $_SESSION['config_empleados'] = $configEmpleados;

            $respuestaArray = ['exito' => true, 'mensaje' => 'Cambios guardados. Ya puedes descargar el reporte.'];

        } catch (\Throwable $e) {
            $respuestaArray = ['exito' => false, 'error' => $e->getMessage()];
        }

        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuestaArray, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================================================================
    //  DESCARGA DEL EXCEL — réplica de la plantilla "Consolidado Nómina
    //  Mensual (con recargos)":
    //    * 23 columnas de datos por día (A..W)
    //    * Bloque de tarifas 100% editable (Y/Z/AA) con las 8 tarifas
    //    * 5 filas de totales por semana (incluye $ recargos)
    //    * 7 filas de consolidado del mes (incluye $ recargos y total
    //      general a pagar)
    //    * Hoja final "Resumen Extras" de 8 columnas
    // ==================================================================
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
        $configEmpleados = $_SESSION['config_empleados'] ?? [];
        $salariosViejos = $_SESSION['salarios_empleados'] ?? [];

        // Festivos del rango real de los datos
        $fechasRaw = array_column($datos, 'fecha_raw');
        $fechaInicioStr = !empty($fechasRaw) ? min($fechasRaw) : date('Y-m-d');
        $fechaFinStr = !empty($fechasRaw) ? max($fechasRaw) : date('Y-m-d');
        $festivos = $this->modelo->obtenerFestivos($fechaInicioStr, $fechaFinStr);

        // Agrupar registros por empleado
        $empleados = [];
        foreach ($datos as $reg) {
            $empleados[$reg['nombre']][] = $reg;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $sheetIndex = 0;
        $resumenHojaFinal = [];

        foreach ($empleados as $nombre => $registros) {
            $cargoEmpleado = (string) ($registros[0]['cargo'] ?? '');
            $config = $this->configEmpleado($nombre, $configEmpleados, $salariosViejos, $cargoEmpleado);

            $sheet = $spreadsheet->createSheet($sheetIndex);

            $tituloHoja = mb_substr(preg_replace('/[^a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚ]/u', '', $nombre), 0, 30, 'UTF-8');
            $sheet->setTitle($tituloHoja);

            // 1. Encabezado del trabajador + bloque de tarifas (Y/Z/AA)
            $this->escribirEncabezadoHoja($sheet, $nombre, $cargoEmpleado, $config);

            // 2. Bloques semanales (días + 5 filas de totales por semana)
            $semana = $this->escribirSemanasHoja($sheet, $registros, $config, $festivos);

            // 3. Consolidado final del mes (7 filas)
            $celdasMes = $this->escribirConsolidadoMes($sheet, $semana['bloques'], $semana['fila'] + 1);

            // 4. Formatos generales y anchos de columna
            $sheet->getStyle("A1:W{$celdasMes['fila_final']}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A1:W{$celdasMes['fila_final']}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $this->escribirAnchosColumna($sheet);

            $resumenHojaFinal[] = [
                'nombre' => $nombre,
                'hoja' => $tituloHoja,
                'actividad' => $config['actividad'],
                'autoriza' => $config['autoriza'],
                'celda_horas_extras' => $celdasMes['horas_extras'],
                'celda_valor_extras' => $celdasMes['valor_extras'],
                'celda_valor_recargos' => $celdasMes['valor_recargos'],
                'celda_total_general' => $celdasMes['total_general'],
            ];

            $sheetIndex++;
        }

        // Hoja resumen general
        if (!empty($resumenHojaFinal)) {
            $this->escribirHojaResumen($spreadsheet, $sheetIndex, $resumenHojaFinal);
        }

        if ($sheetIndex > 0) {
            $spreadsheet->setActiveSheetIndex(0);
        }

        ob_end_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Consolidado_Nomina_Mensual.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        // Deja los valores calculados dentro del archivo (además de las fórmulas,
        // que siguen funcionando si cambias un salario o una tarifa en Excel).
        $writer->setPreCalculateFormulas(true);
        $writer->save('php://output');
        exit;
    }

    /**
     * Tasas por defecto (las mismas que pinta la vista en los controles).
     */
    private function tasasPorDefecto()
    {
        return [
            'hed' => self::TASA_HED,
            'hen' => self::TASA_HEN,
            'rn' => self::TASA_RN,
            'rdf' => self::TASA_RDF,
            'heddf' => self::TASA_HEDDF,
            'hendf' => self::TASA_HENDF,
            'rndf' => self::TASA_RNDF,
        ];
    }

    /**
     * Combina lo que llegó desde la vista con los valores por defecto.
     */
    private function configEmpleado($nombre, array $configEmpleados, array $salariosViejos, $cargo = '')
    {
        $base = [
            'salario' => (float) ($salariosViejos[$nombre] ?? 0),
            'lv' => self::HORARIO_DEFECTO['lv'],
            'sab' => self::HORARIO_DEFECTO['sab'],
            'tope' => self::HORARIO_DEFECTO['tope'],
            'nocturna' => self::HORARIO_DEFECTO['nocturna'],
            // 🔥 Regla del negocio: los TÉCNICOS (motorizados) arrancan con su
            // primera marcación (REAL) y los administrativos a las 07:00 en
            // punto. Si llegan antes de las 07:00, esos minutos NO cuentan
            // (no empiezan a trabajar hasta las 7:00).
            'turno' => $this->esCargoTecnico($cargo) ? 'REAL' : '07:00',
            'actividad' => '',
            'autoriza' => '',
            'divisor' => self::DIVISOR_HORAS_MES,
            'tasas' => $this->tasasPorDefecto(),
        ];

        if (!isset($configEmpleados[$nombre]) || !is_array($configEmpleados[$nombre])) {
            return $base;
        }

        $c = $configEmpleados[$nombre];

        if (isset($c['salario']) && is_numeric($c['salario'])) {
            $base['salario'] = (float) $c['salario'];
        }

        foreach (['lv', 'sab', 'tope', 'nocturna'] as $campo) {
            $hora = $this->normalizarHoraManual($c[$campo] ?? '');
            if ($hora !== '') {
                $base[$campo] = $hora;
            }
        }

        if (!empty($c['turno'])) {
            $turno = trim((string) $c['turno']);
            $base['turno'] = (mb_strtoupper($turno, 'UTF-8') === 'REAL')
                ? 'REAL'
                : ($this->normalizarHoraManual($turno) ?: $base['turno']);
        }

        if (isset($c['actividad'])) {
            $base['actividad'] = trim((string) $c['actividad']);
        }
        if (isset($c['autoriza'])) {
            $base['autoriza'] = trim((string) $c['autoriza']);
        }
        if (isset($c['divisor']) && (float) $c['divisor'] > 0) {
            $base['divisor'] = (float) $c['divisor'];
        }

        if (isset($c['tasas']) && is_array($c['tasas'])) {
            foreach ($base['tasas'] as $clave => $valorDefecto) {
                if (isset($c['tasas'][$clave]) && is_numeric($c['tasas'][$clave])) {
                    $base['tasas'][$clave] = (float) $c['tasas'][$clave];
                }
            }
        }

        return $base;
    }

/**
     * Devuelve "HH:MM" o "" (acepta 7:00, 07:00, 07:00:00 y descarta "FALTA ...").
     */
    private function normalizarHoraManual($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '' || stripos($valor, 'falta') !== false) {
            return '';
        }
        if (preg_match('/^(\d{1,2}):(\d{2})/', $valor, $m)) {
            $h = ((int) $m[1]) % 24;
            return str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':' . $m[2];
        }
        return '';
    }

    /**
     * Convierte "HH:MM" en la fracción de día que entiende Excel (null si falta).
     */
    private function fraccionHora($timeStr)
    {
        if (!$timeStr || stripos((string) $timeStr, 'falta') !== false) {
            return null;
        }
        $p = explode(':', (string) $timeStr);
        $h = isset($p[0]) ? (int) $p[0] : 0;
        $m = isset($p[1]) ? (int) $p[1] : 0;

        return ($h + ($m / 60)) / 24;
    }

    /**
     * ¿El cargo corresponde a un técnico (motorizado)? Espejo de esTecnico()
     * en calculo.js: los técnicos arrancan con su primera marcación (REAL) y
     * los demás con turno fijo a las 07:00.
     */
    private function esCargoTecnico($cargo)
    {
        $c = mb_strtoupper(trim((string) $cargo), 'UTF-8');
        return mb_strpos($c, 'TÉCNICO') !== false || mb_strpos($c, 'TECNICO') !== false;
    }

    /**
     * Definición de las 23 columnas de la tabla semanal (mismo orden del Excel).
     */
    private function mapaColumnas()
    {
        return [
            'A' => ['titulo' => 'FECHA', 'tipo' => 'fecha'],
            'B' => ['titulo' => 'H. ENTRADA', 'tipo' => 'hora'],
            'C' => ['titulo' => 'H. SALIDA', 'tipo' => 'hora'],
            'D' => ['titulo' => 'TOTAL TRABAJADO', 'tipo' => 'hora'],
            'E' => ['titulo' => 'H. EXTRA DIURNA', 'tipo' => 'hora'],
            'F' => ['titulo' => 'H. EXTRA NOCTURNA', 'tipo' => 'hora'],
            'G' => ['titulo' => 'DOM/FEST', 'tipo' => 'dia'],
            'H' => ['titulo' => 'VALOR BASE H.E. DIURNA (100%)', 'tipo' => 'dinero'],
            'I' => ['titulo' => 'RECARGO H.E. DIURNA', 'tipo' => 'dinero'],
            'J' => ['titulo' => 'VALOR BASE H.E. NOCTURNA (100%)', 'tipo' => 'dinero'],
            'K' => ['titulo' => 'RECARGO H.E. NOCTURNA', 'tipo' => 'dinero'],
            'L' => ['titulo' => '# RECARGO NOCTURNO', 'tipo' => 'hora'],
            'M' => ['titulo' => 'VALOR RECARGO NOCTURNO', 'tipo' => 'dinero'],
            'N' => ['titulo' => '# RECARGO DOM/FEST', 'tipo' => 'hora'],
            'O' => ['titulo' => 'VALOR RECARGO DOM/FEST', 'tipo' => 'dinero'],
            'P' => ['titulo' => '# H.E. DIURNA DOM/FEST', 'tipo' => 'hora'],
            'Q' => ['titulo' => 'VALOR H.E. DIURNA DOM/FEST', 'tipo' => 'dinero'],
            'R' => ['titulo' => '# H.E. NOCTURNA DOM/FEST', 'tipo' => 'hora'],
            'S' => ['titulo' => 'VALOR H.E. NOCTURNA DOM/FEST', 'tipo' => 'dinero'],
            'T' => ['titulo' => '# RECARGO NOCTURNO DOM/FEST', 'tipo' => 'hora'],
            'U' => ['titulo' => 'VALOR RECARGO NOCTURNO DOM/FEST', 'tipo' => 'dinero'],
            'V' => ['titulo' => 'SERVICIOS', 'tipo' => 'numero'],
            'W' => ['titulo' => 'NOVEDADES', 'tipo' => 'texto'],
        ];
    }

    /**
     * Encabezado: datos del trabajador (A1:I2) + bloque de tarifas (Y/Z/AA).
     */
    private function escribirEncabezadoHoja($sheet, $nombre, $cargo, array $config)
    {
        $sheet->setCellValue('A1', 'NOMBRE DEL TRABAJADOR');
        $sheet->setCellValue('C1', $nombre);
        $sheet->setCellValue('A2', 'CARGO');
        $sheet->setCellValue('C2', $cargo);

        $sheet->setCellValue('E1', 'L-V');
        $sheet->setCellValue('F1', 'S');
        $sheet->setCellValue('G1', 'LÍMITE EXTRAS');
        $sheet->setCellValue('H1', 'INICIO TURNO');
        $sheet->setCellValue('I1', 'INICIO NOCTURNA');

        $sheet->setCellValue('E2', $this->fraccionHora($config['lv']));
        $sheet->setCellValue('F2', $this->fraccionHora($config['sab']));
        $sheet->setCellValue('G2', $this->fraccionHora($config['tope']));
        $sheet->setCellValue('I2', $this->fraccionHora($config['nocturna']));

        $sheet->getStyle('E2:G2')->getNumberFormat()->setFormatCode('hh:mm');
        $sheet->getStyle('I2')->getNumberFormat()->setFormatCode('hh:mm');

        // 🔥 CRÍTICO: el INICIO TURNO (H2) debe ir como FRACCIÓN DE DÍA
        // (número), nunca como texto "07:00". La fórmula de TOTAL TRABAJADO
        // es: MAX(0, C - IF(ISTEXT($H$2), B, MAX(B, $H$2))). Si H2 queda como
        // texto, ISTEXT() es verdadero y la hoja entiende que el turno es
        // "REAL", contando los minutos desde la hora de llegada aunque el
        // administrativo haya llegado antes de las 7:00 (que no trabajan
        // hasta las 7:00 en punto).
        if ($config['turno'] !== 'REAL') {
            $fraccionTurno = $this->fraccionHora($config['turno']);
            $sheet->setCellValue('H2', $fraccionTurno !== null ? $fraccionTurno : $config['turno']);
            $sheet->getStyle('H2')->getNumberFormat()->setFormatCode('hh:mm');
        } else {
            $sheet->setCellValue('H2', 'REAL');
        }

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF548235');

        $this->escribirBloqueTarifas($sheet, $config);
    }

    /**
     * Bloque de tarifas editable (columnas Y/Z/AA).
     *   Z2      = salario básico (lo único que se digita)
     *   Z3      = Z2 / AA3 (AA3 = divisor de horas, 210 por defecto)
     *   Z4..Z10 = $Z$3 * AA(fila)  → recargos y horas extra
     * Cambiar cualquier celda recalcula TODA la hoja (plantilla "viva").
     */
    private function escribirBloqueTarifas($sheet, array $config)
    {
        $t = $config['tasas'];

        $sheet->setCellValue('Y1', 'CONCEPTO');
        $sheet->setCellValue('Z1', 'VALOR $');
        $sheet->setCellValue('AA1', 'TASA / BASE');

        $sheet->setCellValue('Y2', 'SALARIO BÁSICO');
        $sheet->setCellValue('Z2', (float) $config['salario']);

        $sheet->setCellValue('Y3', 'VALOR HORA BÁSICA');
        $sheet->setCellValue('Z3', '=Z2/AA3');
        $sheet->setCellValue('AA3', (float) $config['divisor']);

        $filas = [
            4 => ['REC. H.E. DIURNA', $t['hed']],
            5 => ['REC. H.E. NOCTURNA', $t['hen']],
            6 => ['REC. NOCTURNO ORDINARIO', $t['rn']],
            7 => ['REC. DOM/FEST ORDINARIO', $t['rdf']],
            8 => ['REC. H.E. DIURNA DOM/FEST', $t['heddf']],
            9 => ['REC. H.E. NOCTURNA DOM/FEST', $t['hendf']],
            10 => ['REC. NOCTURNO DOM/FEST', $t['rndf']],
        ];

        foreach ($filas as $fila => $info) {
            $sheet->setCellValue("Y{$fila}", $info[0]);
            $sheet->setCellValue("Z{$fila}", "=\$Z\$3*AA{$fila}");
            $sheet->setCellValue("AA{$fila}", (float) $info[1]);
        }

        $sheet->getStyle('Y1:AA1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('Y1:AA1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');
        $sheet->getStyle('Y2:Y10')->getFont()->setBold(true);
        $sheet->getStyle('Z2:Z10')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AA3')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('AA4:AA10')->getNumberFormat()->setFormatCode('0%');
        $sheet->getStyle('Z2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('Z2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');

        $sheet->getColumnDimension('Y')->setWidth(28);
        $sheet->getColumnDimension('Z')->setWidth(14);
        $sheet->getColumnDimension('AA')->setWidth(12);
    }

    /**
     * Anchos de columna calcados de la plantilla original.
     */
    private function escribirAnchosColumna($sheet)
    {
        $anchos = [
            'A' => 25, 'B' => 13, 'C' => 22, 'D' => 18, 'E' => 14, 'F' => 14,
            'G' => 10, 'H' => 18, 'I' => 16, 'J' => 20, 'K' => 16, 'L' => 16,
            'M' => 18, 'N' => 16, 'O' => 18, 'P' => 16, 'Q' => 18, 'R' => 16,
            'S' => 18, 'T' => 18, 'U' => 20, 'V' => 12, 'W' => 24,
        ];

        foreach ($anchos as $letra => $ancho) {
            $sheet->getColumnDimension($letra)->setWidth($ancho);
        }
    }

/**
     * Bloques semanales: banner + encabezados + días + REPORTE SEMANAL +
     * 4 filas de totales (incluida la de recargos). Devuelve las celdas
     * clave de cada semana para armar el consolidado del mes.
     */
    private function escribirSemanasHoja($sheet, array $registros, array $config, array $festivos)
    {
        $horas = ['D', 'E', 'F', 'L', 'N', 'P', 'R', 'T'];
        $dinero = ['H', 'I', 'J', 'K', 'M', 'O', 'Q', 'S', 'U'];

        // Agrupar por semana ISO (igual que la plantilla: date('W'))
        $semanas = [];
        foreach ($registros as $reg) {
            $ts = strtotime((string) ($reg['fecha_raw'] ?? ''));
            if ($ts) {
                $semanas[date('W', $ts)][] = $reg;
            } else {
                $semanas['Extra'][] = $reg;
            }
        }
        ksort($semanas);

        $bloques = [];
        $fila = 4; // La fila 4 es el primer "REPORTE SEMANA nn"

        foreach ($semanas as $numSemana => $registrosSemana) {
            // --- Banner de la semana ---
            $sheet->setCellValue("A{$fila}", "REPORTE SEMANA {$numSemana}");
            $sheet->mergeCells("A{$fila}:W{$fila}");
            $sheet->getStyle("A{$fila}:W{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle("A{$fila}:W{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');
            $fila++;

            // --- Encabezados de las 23 columnas ---
            foreach ($this->mapaColumnas() as $letra => $info) {
                $sheet->setCellValue("{$letra}{$fila}", $info['titulo']);
            }
            $sheet->getStyle("A{$fila}:W{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle("A{$fila}:W{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF385D22');
            $sheet->getStyle("A{$fila}:W{$fila}")->getAlignment()->setWrapText(true);
            $fila++;

            // --- Días de la semana ---
            $filaInicioDatos = $fila;
            usort($registrosSemana, function ($a, $b) {
                return strtotime($a['fecha_raw']) <=> strtotime($b['fecha_raw']);
            });
            foreach ($registrosSemana as $reg) {
                $this->escribirFilaDia($sheet, $fila, $reg, $festivos);
                $fila++;
            }
            $filaFinDatos = $fila - 1;

            // --- REPORTE SEMANAL (sumas de todas las columnas) ---
            $filaResumen = $fila;
            $sheet->setCellValue("C{$filaResumen}", 'REPORTE SEMANAL');
            foreach (array_merge($horas, $dinero) as $letra) {
                $sheet->setCellValue("{$letra}{$filaResumen}", "=SUM({$letra}{$filaInicioDatos}:{$letra}{$filaFinDatos})");
            }
            $sheet->setCellValue("V{$filaResumen}", "=SUM(V{$filaInicioDatos}:V{$filaFinDatos})");
            $sheet->getStyle("A{$filaResumen}:W{$filaResumen}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF996600');
            $sheet->getStyle("A{$filaResumen}:W{$filaResumen}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            foreach ($horas as $letra) {
                $sheet->getStyle("{$letra}{$filaResumen}")->getNumberFormat()->setFormatCode('[h]:mm');
            }
            foreach ($dinero as $letra) {
                $sheet->getStyle("{$letra}{$filaResumen}")->getNumberFormat()->setFormatCode('#,##0');
            }
            $fila++;

            // --- TOTAL HORAS EXTRAS A PAGAR (horas) ---
            $filaHorasExtras = $fila;
            $this->escribirFilaTotal($sheet, $fila, 'TOTAL HORAS EXTRAS A PAGAR (horas)', "=E{$filaResumen}+F{$filaResumen}", 'FF2F5597', '[h]:mm');
            $fila++;

            // --- TOTAL $ HORAS EXTRAS (base + recargo, diurna y nocturna) ---
            $filaValorExtras = $fila;
            $this->escribirFilaTotal($sheet, $fila, 'TOTAL $ HORAS EXTRAS A PAGAR', "=H{$filaResumen}+I{$filaResumen}+J{$filaResumen}+K{$filaResumen}", 'FF107C41', '#,##0');
            $fila++;

            // --- TOTAL HORAS DOMINICALES / RECARGOS ORDINARIOS (horas) ---
            $filaHorasRecargos = $fila;
            $this->escribirFilaTotal($sheet, $fila, 'TOTAL HORAS DOMINICALES/RECARGOS ORD. (horas)', "=N{$filaResumen}+T{$filaResumen}", 'FFC55A11', '[h]:mm');
            $fila++;

            // --- TOTAL $ RECARGOS Y DOM/FEST (semana) ---
            $filaValorRecargos = $fila;
            $this->escribirFilaTotal($sheet, $fila, 'TOTAL $ RECARGOS Y DOM/FEST (semana)', "=M{$filaResumen}+O{$filaResumen}+Q{$filaResumen}+S{$filaResumen}+U{$filaResumen}", 'FF7030A0', '#,##0');
            $fila += 3; // deja dos filas de aire antes del siguiente bloque

            $bloques[] = [
                'horas_trabajadas' => "D{$filaResumen}",
                'servicios' => "V{$filaResumen}",
                'horas_extras' => "D{$filaHorasExtras}",
                'valor_extras' => "D{$filaValorExtras}",
                'horas_recargos' => "D{$filaHorasRecargos}",
                'valor_recargos' => "D{$filaValorRecargos}",
            ];
        }

        return ['bloques' => $bloques, 'fila' => $fila];
    }

    /**
     * Escribe una fila de totales: etiqueta en C, valor en D, con su color.
     */
    private function escribirFilaTotal($sheet, $fila, $etiqueta, $formula, $color, $formato)
    {
        $sheet->setCellValue("C{$fila}", $etiqueta);
        $sheet->setCellValue("D{$fila}", $formula);
        $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
        $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("D{$fila}")->getNumberFormat()->setFormatCode($formato);
    }

/**
     * Escribe una fila de día con TODAS las fórmulas de la plantilla:
     * horas trabajadas, extras con tope, base 100% + recargo (diurna y
     * nocturna) y los recargos ordinarios manuales/automáticos.
     */
    private function escribirFilaDia($sheet, $fila, array $reg, array $festivos)
    {
        // Referencias absolutas del encabezado
        $refLv = '$E$2';
        $refSab = '$F$2';
        $refTope = '$G$2';
        $refTurno = '$H$2';
        $refNocturna = '$I$2';

        // Referencias absolutas del bloque de tarifas (Y/Z/AA)
        $refHora = '$Z$3';
        $refHed = '$Z$4';
        $refHen = '$Z$5';
        $refRn = '$Z$6';
        $refRdf = '$Z$7';
        $refHedDf = '$Z$8';
        $refHenDf = '$Z$9';
        $refRndf = '$Z$10';

        $timestamp = strtotime((string) ($reg['fecha_raw'] ?? ''));
        if ($timestamp) {
            $sheet->setCellValue("A{$fila}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($timestamp));
            $sheet->getStyle("A{$fila}")->getNumberFormat()->setFormatCode('[$-es-ES]dddd dd-mm-yyyy;@');
        } else {
            $sheet->setCellValue("A{$fila}", $reg['fecha_formateada'] ?? '');
        }

        // Domingo automático + tabla dias_festivos
        $esDomFest = ($timestamp && (date('N', $timestamp) == 7 || isset($festivos[$reg['fecha_raw']]))) ? 1 : 0;
        $sheet->setCellValue("G{$fila}", $esDomFest);

        $valEntrada = $this->fraccionHora($reg['entrada_valor'] ?? ($reg['entrada'] ?? ''));
        $valSalida = $this->fraccionHora($reg['salida_valor'] ?? ($reg['salida'] ?? ''));

        if ($valEntrada !== null) {
            $sheet->setCellValue("B{$fila}", $valEntrada);
            $sheet->getStyle("B{$fila}")->getNumberFormat()->setFormatCode('hh:mm');
        } else {
            $sheet->setCellValue("B{$fila}", 'FALTA ENT');
        }

        if ($valSalida !== null) {
            $sheet->setCellValue("C{$fila}", $valSalida);
            $sheet->getStyle("C{$fila}")->getNumberFormat()->setFormatCode('hh:mm');

            // 1. Total trabajado
            $sheet->setCellValue("D{$fila}", "=IF(AND(ISNUMBER(B{$fila}), ISNUMBER(C{$fila})), MAX(0, C{$fila} - IF(ISTEXT({$refTurno}), B{$fila}, MAX(B{$fila}, {$refTurno}))), \"\")");

            // 2. Límite del día + extras con tope
            $limite = "IF(WEEKDAY(A{$fila},2)<6, {$refLv}, {$refSab})";
            $extrasBruto = "MAX(0, D{$fila}-{$limite})";
            $extrasTope = "IF({$extrasBruto} > {$refTope}, {$refTope}, {$extrasBruto})";

            $sheet->setCellValue("F{$fila}", "=IF(ISNUMBER(C{$fila}), MAX(0, MIN({$extrasTope}, MAX(0, C{$fila}-{$refNocturna}))), \"\")");
            $sheet->setCellValue("E{$fila}", "=IF(ISNUMBER(D{$fila}), MAX(0, {$extrasTope} - F{$fila}), \"\")");

            // 3. 🔥 VALOR BASE (100%) + RECARGO, diurna y nocturna
            //    Diurna  = 100% + 25%  (dom/fest 100% + 115% = 215%)
            //    Nocturna= 100% + 75%  (dom/fest 100% + 165% = 265%)
            $sheet->setCellValue("H{$fila}", "=IF(ISNUMBER(E{$fila}), E{$fila}*24*{$refHora}, \"\")");
            $sheet->setCellValue("I{$fila}", "=IF(ISNUMBER(E{$fila}), E{$fila}*24*IF(G{$fila}=1, {$refHedDf}, {$refHed}), \"\")");
            $sheet->setCellValue("J{$fila}", "=IF(ISNUMBER(F{$fila}), F{$fila}*24*{$refHora}, \"\")");
            $sheet->setCellValue("K{$fila}", "=IF(ISNUMBER(F{$fila}), F{$fila}*24*IF(G{$fila}=1, {$refHenDf}, {$refHen}), \"\")");
        } else {
            $sheet->setCellValue("C{$fila}", 'FALTA SALIDA');
        }

        // 4. Columnas de recargos: # de horas (se escriben como hora) + su valor $
        $manuales = [
            'L' => ['campo' => 'rec_nocturno', 'ref' => $refRn, 'destino' => 'M'],
            'N' => ['campo' => 'rec_domfest', 'ref' => $refRdf, 'destino' => 'O'],
            'P' => ['campo' => 'hed_df', 'ref' => $refHedDf, 'destino' => 'Q'],
            'R' => ['campo' => 'hen_df', 'ref' => $refHenDf, 'destino' => 'S'],
            'T' => ['campo' => 'rec_noct_df', 'ref' => $refRndf, 'destino' => 'U'],
        ];

        foreach ($manuales as $letra => $info) {
            $fraccion = $this->fraccionHora($reg[$info['campo']] ?? '');
            if ($fraccion !== null) {
                $sheet->setCellValue("{$letra}{$fila}", $fraccion);
                $sheet->getStyle("{$letra}{$fila}")->getNumberFormat()->setFormatCode('[h]:mm');
            }
            $refTasa = $info['ref'];
            $destino = $info['destino'];
            $sheet->setCellValue("{$destino}{$fila}", "=IF(ISNUMBER({$letra}{$fila}), {$letra}{$fila}*24*{$refTasa}, 0)");
            $sheet->getStyle("{$destino}{$fila}")->getNumberFormat()->setFormatCode('#,##0');
        }

        $sheet->setCellValue("V{$fila}", !empty($reg['servicios']) ? (int) $reg['servicios'] : '');
        $sheet->setCellValue("W{$fila}", $reg['novedades'] ?? '');

        // 5. Formatos de la fila
        foreach (['D', 'E', 'F'] as $letra) {
            $sheet->getStyle("{$letra}{$fila}")->getNumberFormat()->setFormatCode('[h]:mm');
        }
        foreach (['H', 'I', 'J', 'K'] as $letra) {
            $sheet->getStyle("{$letra}{$fila}")->getNumberFormat()->setFormatCode('#,##0');
        }

        if ($esDomFest) {
            $sheet->getStyle("A{$fila}:W{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');
        }
    }

/**
     * Consolidado final del mes: 7 filas con las sumas de cada semana
     * (incluye $ recargos y el gran total general a pagar).
     */
    private function escribirConsolidadoMes($sheet, array $bloques, $fila)
    {
        $sheet->setCellValue("B{$fila}", 'CONSOLIDADO FINAL DEL MES');
        $sheet->mergeCells("B{$fila}:D{$fila}");
        $sheet->getStyle("B{$fila}:D{$fila}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("B{$fila}:D{$fila}")->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("B{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF000000');
        $fila++;

        $filas = [
            'horas_trabajadas' => ['GRAN TOTAL HORAS TRABAJADAS', 'FF996600', '[h]:mm'],
            'horas_extras' => ['GRAN TOTAL HORAS EXTRAS (horas)', 'FF2F5597', '[h]:mm'],
            'valor_extras' => ['GRAN TOTAL $ HORAS EXTRAS A PAGAR', 'FF107C41', '#,##0'],
            'horas_recargos' => ['GRAN TOTAL DOMINICALES/RECARGOS ORD. (horas)', 'FFC55A11', '[h]:mm'],
            'servicios' => ['GRAN TOTAL SERVICIOS (TICKETS)', 'FF1F4E78', '#,##0'],
            'valor_recargos' => ['GRAN TOTAL $ RECARGOS Y DOM/FEST (mes)', 'FF7030A0', '#,##0'],
        ];

        $celdas = [];
        foreach ($filas as $clave => $info) {
            $refs = [];
            foreach ($bloques as $bloque) {
                if (!empty($bloque[$clave])) {
                    $refs[] = $bloque[$clave];
                }
            }

            $sheet->setCellValue("C{$fila}", $info[0]);
            $sheet->setCellValue("D{$fila}", empty($refs) ? 0 : '=SUM(' . implode(',', $refs) . ')');
            $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($info[1]);
            $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle("D{$fila}")->getNumberFormat()->setFormatCode($info[2]);

            $celdas[$clave] = "D{$fila}";
            $fila++;
        }

        // GRAN TOTAL GENERAL A PAGAR = $ extras + $ recargos
        $celdaValorExtras = $celdas['valor_extras'];
        $celdaValorRecargos = $celdas['valor_recargos'];

        $sheet->setCellValue("C{$fila}", 'GRAN TOTAL GENERAL A PAGAR (EXTRAS + RECARGOS)');
        $sheet->setCellValue("D{$fila}", "={$celdaValorExtras}+{$celdaValorRecargos}");
        $sheet->getStyle("C{$fila}:D{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF000000');
        $sheet->getStyle("C{$fila}:D{$fila}")->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("D{$fila}")->getNumberFormat()->setFormatCode('#,##0');

        $celdas['total_general'] = "D{$fila}";
        $celdas['fila_final'] = (int) $fila;

        return $celdas;
    }

/**
     * Hoja final "Resumen Extras" (8 columnas, con recargos y total general).
     */
    private function escribirHojaResumen(Spreadsheet $spreadsheet, $sheetIndex, array $resumen)
    {
        $sheet = $spreadsheet->createSheet($sheetIndex);
        $sheet->setTitle('Resumen Extras');

        $encabezados = [
            'A1' => 'NOMBRE DE LA PERSONA',
            'B1' => 'SALARIO BÁSICO',
            'C1' => 'HORAS EXTRA (TOTAL)',
            'D1' => 'VALOR TOTAL A PAGAR ($)',
            'E1' => 'ACTIVIDAD REALIZADA',
            'F1' => 'QUIEN AUTORIZA',
            'G1' => 'RECARGOS Y DOM/FEST ($)',
            'H1' => 'TOTAL GENERAL A PAGAR ($)',
        ];

        foreach ($encabezados as $celda => $texto) {
            $sheet->setCellValue($celda, $texto);
        }

        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:H1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:H1')->getAlignment()->setWrapText(true);

        $fila = 2;
        foreach ($resumen as $res) {
            $hoja = "'" . $res['hoja'] . "'";
            $cHorasExtras = $res['celda_horas_extras'];
            $cValorExtras = $res['celda_valor_extras'];
            $cValorRecargos = $res['celda_valor_recargos'];
            $cTotalGeneral = $res['celda_total_general'];

            $sheet->setCellValue("A{$fila}", $res['nombre']);
            $sheet->setCellValue("B{$fila}", "={$hoja}!Z2");
            $sheet->setCellValue("C{$fila}", "=IF({$hoja}!{$cHorasExtras}=0, \"\", {$hoja}!{$cHorasExtras})");
            $sheet->setCellValue("D{$fila}", "=IF({$hoja}!{$cValorExtras}=0, \"\", {$hoja}!{$cValorExtras})");
            $sheet->setCellValue("E{$fila}", $res['actividad']);
            $sheet->setCellValue("F{$fila}", $res['autoriza']);
            $sheet->setCellValue("G{$fila}", "=IF({$hoja}!{$cValorRecargos}=0, \"\", {$hoja}!{$cValorRecargos})");
            $sheet->setCellValue("H{$fila}", "=IF({$hoja}!{$cTotalGeneral}=0, \"\", {$hoja}!{$cTotalGeneral})");

            $sheet->getStyle("B{$fila}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("C{$fila}")->getNumberFormat()->setFormatCode('[h]:mm;;;');
            $sheet->getStyle("D{$fila}")->getNumberFormat()->setFormatCode('#,##0;;;');
            $sheet->getStyle("G{$fila}")->getNumberFormat()->setFormatCode('#,##0;;;');
            $sheet->getStyle("H{$fila}")->getNumberFormat()->setFormatCode('#,##0;;;');
            $fila++;
        }

        $anchos = ['A' => 35, 'B' => 18, 'C' => 20, 'D' => 24, 'E' => 35, 'F' => 25, 'G' => 20, 'H' => 22];
        foreach ($anchos as $letra => $ancho) {
            $sheet->getColumnDimension($letra)->setWidth($ancho);
        }

        $styleArrayBordes = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $ultima = $fila - 1;
        if ($ultima >= 1) {
            $sheet->getStyle("A1:H{$ultima}")->applyFromArray($styleArrayBordes);
            $sheet->getStyle("B2:H{$ultima}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A2:H{$ultima}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
    }
}