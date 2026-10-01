<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/reporteTecnicoModelo.php';
require_once __DIR__ . '/../../models/orden/ordenReporteModelo.php';
require_once __DIR__ . '/../../helpers/resumenTiposServicio.php';

use Spatie\Browsershot\Browsershot;

class reporteTecnicoControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteTecnicoModelo($this->db);
        
        // Asegurarnos de que la sesión esté iniciada para leer el rol
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        $datosReporte = []; // Para mostrar en la Tabla (Filtrado)
        $datosExcel = [];   // Para enviar al JS (Todos los técnicos)
        $datosServiciosGlobal = []; // Copia tal cual de ordenReporte (todos los técnicos del rango)

        $filtros = [
            'id_tecnico' => '',
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin' => date('Y-m-d')
        ];
        $totalValor = 0;
        $mensaje = "";

        // 🔒 SEGURIDAD: Capturamos el rol actual
        $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int)$_SESSION['nivel_acceso'] : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['id_tecnico'] = $_POST['id_tecnico'] ?? '';
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? '';
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? '';

            if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {

                // 1. CONSULTA PARA LA VISTA (Tabla HTML)
                $datosReporte = $this->modelo->generarReporteServicios(
                    $filtros['id_tecnico'],
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                );

                // 2. CONSULTA PARA EL EXCEL (Javascript)
                $datosExcel = $this->modelo->generarReporteServicios(
                    '', // <--- TRUCO: Forzamos vacío para traer todo
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                );

                // 3. COPIA TAL CUAL DEL REPORTE DE SERVICIOS (ordenReporte).
                // Siempre con TODOS los técnicos del rango ('todos'), aunque en
                // pantalla se haya filtrado por un técnico puntual.
                try {
                    $modeloServicios = new ordenReporteModelo($this->db);
                    $datosServiciosGlobal = $modeloServicios->obtenerServiciosPorRango(
                        $filtros['fecha_inicio'],
                        $filtros['fecha_fin'],
                        'todos'
                    );
                    if (!is_array($datosServiciosGlobal)) {
                        $datosServiciosGlobal = [];
                    }
                } catch (Exception $e) {
                    error_log("Error obteniendo copia ordenReporte: " . $e->getMessage());
                    $datosServiciosGlobal = [];
                }

                // 🛡️ FILTRO DE SEGURIDAD PARA ROL 5
                if ($rolUsuario === 5) {
                    if (!empty($datosReporte)) {
                        foreach ($datosReporte as &$r) {
                            $r['valor_servicio'] = 0;
                        }
                    }
                    if (!empty($datosExcel)) {
                        foreach ($datosExcel as &$e) {
                            $e['valor_servicio'] = 0;
                        }
                    }
                    if (!empty($datosServiciosGlobal)) {
                        foreach ($datosServiciosGlobal as &$g) {
                            $g['valor_servicio'] = 0;
                            $g['valor_viaticos'] = 0;
                        }
                    }
                }

                // Calcular total (Solo de lo que se ve en pantalla)
                // Si es rol 5, como arriba pusimos todo en 0, el total dará 0 automáticamente
                foreach ($datosReporte as $row) {
                    $totalValor += floatval($row['valor_servicio']);
                }

                // Resumen por tipo de mantenimiento (respeta filtros de técnico + fechas).
                // Se clasifica sobre $datosReporte para que cuadre 1:1 con la tabla visible.
                $resumenTipos = ResumenTiposServicio::resumir($datosReporte);

                if (empty($datosReporte)) {
                    $mensaje = "No se encontraron servicios para la vista en ese rango.";
                }
            } else {
                $mensaje = "Por favor selecciona el rango de fechas.";
            }
        }
        
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaFestivos = $this->modelo->obtenerFestivos($filtros['fecha_inicio'], $filtros['fecha_fin']);

        // Si es GET inicial (sin POST), garantizamos que la vista tenga la variable definida.
        if (!isset($resumenTipos)) {
            $resumenTipos = ResumenTiposServicio::vacio();
        }
        $titulo = "Reporte de Servicios por Técnico";

        $vistaContenido = "app/views/reportes/reporteTecnicoVista.php";
        include "app/views/plantillaVista.php";
    }

    // ══════════════════════════════════════════════════════════════
    // PDF: Hoja 1 del Excel (Resumen por técnico + Tabla de fallidos)
    // Generado con Chromium (Node + Browsershot), mismo patrón de los
    // demás PDF del proyecto (Reporte Ejecutivo / Repuestos).
    // ══════════════════════════════════════════════════════════════
    public function generarPDF()
    {
        // ── REPORTE DIARIO: se usa UNA sola fecha (la de "Desde") ──
        $fecha = $_GET['fecha_inicio'] ?? date('Y-m-d');
        if (empty($_GET['fecha_inicio'])) {
            $fecha = $_GET['fecha_fin'] ?? date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }
        $id_tecnico = $_GET['id_tecnico'] ?? '';

        // Mismo dataset que usa el Excel, pero acotado a ese día
        $datos = $this->modelo->generarReporteServicios($id_tecnico, $fecha, $fecha);
        $resumenTipos = ResumenTiposServicio::resumir($datos);

        // ── Meta del día según el tipo de día ──
        // Sábado: 3 servicios = verde.  Día hábil: 6 servicios = verde.
        $diaNum = (int)date('N', strtotime($fecha)); // 1=Lunes ... 6=Sábado, 7=Domingo
        $esSabado = ($diaNum === 6);
        $esDomingo = ($diaNum === 7);
        $metaVerde = $esSabado ? 3 : 6;
        $metaAmarillo = $esSabado ? 2 : 4;

        // Columnas de tipo de mantenimiento detectadas (mismo criterio del Excel)
        $setTipos = [];
        foreach ($datos as $item) {
            $tipo = !empty($item['tipo_mantenimiento']) ? $item['tipo_mantenimiento'] : 'SIN ESPECIFICAR';
            $setTipos[$tipo] = true;
        }
        $tiposColumnas = array_keys($setTipos);
        sort($tiposColumnas);

        // Matriz: técnico -> [tipo => cantidad], con jornada del día
        $resumen = [];
        $jornada = [];
        foreach ($datos as $item) {
            $tec = !empty($item['nombre_tecnico']) ? trim($item['nombre_tecnico']) : 'Sin Nombre';
            $tipo = !empty($item['tipo_mantenimiento']) ? $item['tipo_mantenimiento'] : 'SIN ESPECIFICAR';

            if (!isset($resumen[$tec])) {
                foreach ($tiposColumnas as $t) {
                    $resumen[$tec][$t] = 0;
                }
                $jornada[$tec] = ['entrada' => null, 'salida' => null, 'minutos' => 0];
            }
            if (!isset($resumen[$tec][$tipo])) {
                $resumen[$tec][$tipo] = 0;
            }
            $resumen[$tec][$tipo]++;

            // Primera entrada / última salida del día (como el Excel)
            $he = isset($item['hora_entrada']) ? trim((string)$item['hora_entrada']) : '';
            $hs = isset($item['hora_salida']) ? trim((string)$item['hora_salida']) : '';

            if ($he !== '' && $he !== 'null' && strlen($he) >= 5) {
                $he = substr($he, 0, 5);
                if ($jornada[$tec]['entrada'] === null || $he < $jornada[$tec]['entrada']) {
                    $jornada[$tec]['entrada'] = $he;
                }
            }
            if ($hs !== '' && $hs !== 'null' && strlen($hs) >= 5) {
                $hs = substr($hs, 0, 5);
                if ($jornada[$tec]['salida'] === null || $hs > $jornada[$tec]['salida']) {
                    $jornada[$tec]['salida'] = $hs;
                }
            }

            // Tiempo servido (si viene HH:MM o HH:MM:SS)
            $ts = isset($item['tiempo_servicio']) ? trim((string)$item['tiempo_servicio']) : '';
            if ($ts !== '' && $ts !== 'null' && strpos($ts, ':') !== false) {
                $partes = explode(':', $ts);
                $min = ((int)$partes[0] * 60) + (int)$partes[1];
                if ($min > 0) {
                    $jornada[$tec]['minutos'] += $min;
                }
            }
        }

        // Si no hay tiempo_servicio, se calcula de entrada a salida
        foreach ($jornada as $tec => $j) {
            if ($j['minutos'] === 0 && $j['entrada'] !== null && $j['salida'] !== null) {
                $mE = ((int)substr($j['entrada'], 0, 2) * 60) + (int)substr($j['entrada'], 3, 2);
                $mS = ((int)substr($j['salida'], 0, 2) * 60) + (int)substr($j['salida'], 3, 2);
                $dif = $mS - $mE;
                if ($dif > 0) {
                    $jornada[$tec]['minutos'] = $dif;
                }
            }
        }

        // Totales por columna y total general
        $totalesColumna = [];
        foreach ($tiposColumnas as $t) {
            $totalesColumna[$t] = 0;
        }
        $totalGeneral = 0;
        foreach ($resumen as $fila) {
            foreach ($fila as $t => $c) {
                $totalesColumna[$t] = ($totalesColumna[$t] ?? 0) + $c;
                $totalGeneral += $c;
            }
        }

        $tecnicosOrdenados = $this->ordenarTecnicos(array_keys($resumen));

        // ── Rendimiento vs meta del día ──
        // La barra representa el % de la meta cumplida (verde = 100% o más).
        // Umbrales: sábado -> verde >=3, amarillo >=2, rojo <2
        //           hábil -> verde >=6, amarillo >=4, rojo <4
        $rendimiento = [];
        foreach ($resumen as $tec => $fila) {
            $servicios = array_sum($fila);
            $porcentajeMeta = ($metaVerde > 0) ? ($servicios / $metaVerde) * 100 : 0;

            if ($servicios >= $metaVerde) {
                $colorSem = 'bg-emerald-500';
                $txtSem = 'text-emerald-700';
                $semaforo = 'Cumple meta';
            } elseif ($servicios >= $metaAmarillo) {
                $colorSem = 'bg-amber-400';
                $txtSem = 'text-amber-700';
                $semaforo = 'Por debajo';
            } else {
                $colorSem = 'bg-rose-500';
                $txtSem = 'text-rose-700';
                $semaforo = 'Bajo meta';
            }

            $rendimiento[$tec] = [
                'servicios' => $servicios,
                'porcentaje' => min($porcentajeMeta, 100),
                'porcentaje_real' => $porcentajeMeta,
                'color' => $colorSem,
                'texto' => $txtSem,
                'semaforo' => $semaforo
            ];
        }

        // Tabla de fallidos: delegación -> lista de ['cliente'=>, 'total'=>]
        $fallidos = [];
        $totalFallidos = 0;
        foreach ($datos as $item) {
            $tipoUp = mb_strtoupper((string)($item['tipo_mantenimiento'] ?? ''), 'UTF-8');
            if (strpos($tipoUp, 'FALLID') === false) {
                continue;
            }
            $del = !empty($item['delegacion']) ? $item['delegacion'] : 'SIN DELEGACIÓN';
            $cli = !empty($item['nombre_cliente']) ? $item['nombre_cliente'] : 'Sin Cliente';

            if (!isset($fallidos[$del])) {
                $fallidos[$del] = [];
            }
            $fallidos[$del][$cli] = ($fallidos[$del][$cli] ?? 0) + 1;
            $totalFallidos++;
        }

        // Convertir a lista de items (la vista itera así)
        $fallidosLista = [];
        foreach ($fallidos as $del => $mapaClientes) {
            arsort($mapaClientes);
            $items = [];
            foreach ($mapaClientes as $cli => $cant) {
                $items[] = ['cliente' => $cli, 'total' => $cant];
            }
            $fallidosLista[$del] = $items;
        }
        $fallidos = $fallidosLista;
        ksort($fallidos);

        // Logo en base64
        $rutaLogo = __DIR__ . '/../../logos/logoIneesSinFondo.png';
        if (!file_exists($rutaLogo)) {
            $rutaLogo = __DIR__ . '/../../logos/logoInees.jpg';
        }
        $logoBase64 = '';
        if (file_exists($rutaLogo)) {
            $type = strtolower(pathinfo($rutaLogo, PATHINFO_EXTENSION));
            $type = ($type === 'jpg') ? 'jpeg' : $type;
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($rutaLogo));
        }

        // Etiquetas del día (ej. "Viernes, 10 de enero de 2026")
        $rangoInicio = date('d/m/Y', strtotime($fecha));
        $rangoFin = $rangoInicio;
        $diaSemana = $this->nombreDiaSemana($fecha);
        $esFestivo = in_array($fecha, $this->modelo->obtenerFestivos($fecha, $fecha));

        // Total de horas trabajadas en el día (para el KPI)
        $totalHorasDia = 0;
        foreach ($jornada as $j) {
            $totalHorasDia += $j['minutos'];
        }

        // Renderizar HTML
        if (ob_get_length()) {
            ob_end_clean();
        }
        ob_start();
        include __DIR__ . '/../../views/reportes/reporteTecnicoGenerar.php';
        $html = ob_get_clean();

        $this->enviarPdf($html, $rangoInicio . ' - ' . $diaSemana, $fecha);
    }

    private function nombreDiaSemana($fecha)
    {
        $dias = [
            'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        $dia = date('l', strtotime($fecha));
        $mes = date('F', strtotime($fecha));
        $meses = [
            'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
            'April' => 'abril', 'May' => 'mayo', 'June' => 'junio', 'July' => 'julio',
            'August' => 'agosto', 'September' => 'septiembre', 'October' => 'octubre',
            'November' => 'noviembre', 'December' => 'diciembre'
        ];
        return ($dias[$dia] ?? $dia) . ', ' . (int)date('j', strtotime($fecha)) . ' de '
            . ($meses[$mes] ?? $mes) . ' de ' . date('Y', strtotime($fecha));
    }
/**
     * Convierte el HTML en PDF usando Chromium (Node + Browsershot).
     */
    private function enviarPdf($html, $etiquetaPeriodo, $fechaIso)
    {
        $footerHtml = '
        <div style="width: 100%; font-size: 9px; padding-left: 10px; padding-right: 10px; padding-bottom: 5px; font-family: sans-serif; color: #64748b; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="width: 33%; text-transform: uppercase; letter-spacing: 2px; font-weight: bold; color: #94a3b8;">
                Documento Confidencial
            </div>
            <div style="width: 33%; text-align: center; font-weight: bold;">'
            . $etiquetaPeriodo . '</div>
            <div style="width: 33%; text-align: right;">
                <span style="background-color: #f8fafc; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 4px; font-weight: bold; color: #475569;">
                    Página <span class="pageNumber"></span>
                </span>
            </div>
        </div>';

        try {
            $nodePath = 'C:\Program Files\nodejs\node.exe';
            $npmPath = 'C:\Program Files\nodejs\npm.cmd';

            $posiblesRutasChrome = [
                'C:\Users\User\.cache\puppeteer\chrome\win64-144.0.7559.96\chrome-win64\chrome.exe',
                'C:\Program Files\Google\Chrome\Application\chrome.exe',
                'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe'
            ];
            $chromePath = null;
            foreach ($posiblesRutasChrome as $ruta) {
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
                ->margins(10, 10, 15, 10)
                ->scale(0.8)
                ->timeout(120)
                ->showBrowserHeaderAndFooter()
                ->headerHtml('<div></div>')
                ->footerHtml($footerHtml);

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();

            $nombreArchivo = "Reporte_Diario_Tecnicos_"
                . date('d-m-Y', strtotime($fechaIso)) . ".pdf";

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfContent));
            echo $pdfContent;
            exit;
        } catch (Exception $e) {
            echo "<h1>Error generando PDF de Técnicos</h1><p>" . $e->getMessage() . "</p>";
            exit;
        }
    }
/**
     * Orden personalizado de técnicos (idéntico al usado en el Excel).
     */
    private function ordenarTecnicos($nombres)
    {
        $ordenPersonalizado = [
            "MURGAS", "JHONY", "MAICOL", "RUIZ", "ORJUELA", "FORERO",
            "ESPINOSA", "MAURICIO", "JHONATAN", "ORTIZ", "CERVERA",
            "VILORIA", "SAAVEDRA", "BENAVIDES"
        ];

        $indice = function ($nombre) use ($ordenPersonalizado) {
            $norm = strtr($nombre . 'NFD', "\u{0300}-\u{036F}", '');
            $norm = mb_strtoupper($norm, 'UTF-8');
            foreach ($ordenPersonalizado as $i => $clave) {
                if (strpos($norm, mb_strtoupper($clave, 'UTF-8')) !== false) {
                    return $i;
                }
            }
            return 999;
        };

        usort($nombres, function ($a, $b) use ($indice) {
            $ia = $indice($a);
            $ib = $indice($b);
            if ($ia === $ib) {
                return strcmp($a, $b);
            }
            return ($ia < $ib) ? -1 : 1;
        });

        return $nombres;
    }
}