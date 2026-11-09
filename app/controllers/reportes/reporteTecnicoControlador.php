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
        // ── REPORTE POR RANGO DE FECHAS ──
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin    = $_GET['fecha_fin'] ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
            $fechaInicio = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
            $fechaFin = date('Y-m-d');
        }
        $id_tecnico = $_GET['id_tecnico'] ?? '';

        // Mismo dataset que usa el Excel, pero acotado al rango de fechas
        $datos = $this->modelo->generarReporteServicios($id_tecnico, $fechaInicio, $fechaFin);

        // Agrupar datos por fecha (YYYY-MM-DD)
        $datosPorDia = [];
        foreach ($datos as $item) {
            $fecha = !empty($item['fecha_visita']) ? substr($item['fecha_visita'], 0, 10) : date('Y-m-d');
            if (!isset($datosPorDia[$fecha])) {
                $datosPorDia[$fecha] = [];
            }
            $datosPorDia[$fecha][] = $item;
        }

        // Ordenar fechas ascendente
        ksort($datosPorDia);

        // Procesar cada día de forma independiente
        $diasProcesados = [];
        foreach ($datosPorDia as $fecha => $datosDia) {
            $diasProcesados[] = $this->procesarDiaPDF($datosDia, $fecha);
        }

        // Logo en base64 (compartido para todos los días)
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

        // Renderizar HTML pasando todos los días procesados
        if (ob_get_length()) {
            ob_end_clean();
        }
        ob_start();
        include __DIR__ . '/../../views/reportes/reporteTecnicoGenerar.php';
        $html = ob_get_clean();

        $this->enviarPdf($html, $fechaInicio . ' al ' . $fechaFin, $fechaInicio, $fechaFin);
    }

    /**
     * Procesa los servicios de un solo día y devuelve todas las variables
     * que necesita la vista para renderizar la "hoja" de ese día.
     */
    private function procesarDiaPDF($datosDia, $fecha)
    {
        $resumenTipos = ResumenTiposServicio::resumir($datosDia);

        // ── Meta del día según el tipo de día ──
        $diaNum = (int)date('N', strtotime($fecha)); // 1=Lunes ... 6=Sábado, 7=Domingo
        $esSabado = ($diaNum === 6);
        $esDomingo = ($diaNum === 7);
        $esFestivo = in_array($fecha, $this->modelo->obtenerFestivos($fecha, $fecha));
        $esNoLaboral = ($esDomingo || $esFestivo);

        // Día hábil: 6 | Sábado: 3 | Nueva Arquitectura (por técnico): 3 (2 en sábado)
        $metaVerde = $esSabado ? 3 : 6;
        $metaAmarillo = (int)ceil($metaVerde * 2 / 3); // 6->4 | 3->2
        $metaNuevaArquitectura = $esSabado ? 2 : 3;
        $metaNuevaArquitecturaAmarillo = (int)ceil($metaNuevaArquitectura * 2 / 3); // 3->2 | 2->2

        $hayNuevaArquitectura = false;
        $nuevaArquitecturaPorTecnico = [];
        $fallidosPorTecnico = [];

        // Columnas de tipo de mantenimiento detectadas (mismo criterio del Excel)
        $setTipos = [];
        foreach ($datosDia as $item) {
            $tipo = !empty($item['tipo_mantenimiento']) ? $item['tipo_mantenimiento'] : 'SIN ESPECIFICAR';
            $setTipos[$tipo] = true;
        }
        $tiposColumnas = array_keys($setTipos);
        sort($tiposColumnas);

        // Matriz: técnico -> [tipo => cantidad], con jornada del día
        $resumen = [];
        $jornada = [];
        foreach ($datosDia as $item) {
            $tec = !empty($item['nombre_tecnico']) ? trim($item['nombre_tecnico']) : 'Sin Nombre';
            $tipo = !empty($item['tipo_mantenimiento']) ? $item['tipo_mantenimiento'] : 'SIN ESPECIFICAR';

            if (!isset($resumen[$tec])) {
                foreach ($tiposColumnas as $t) {
                    $resumen[$tec][$t] = 0;
                }
                $jornada[$tec] = ['entrada' => null, 'salida' => null, 'minutos' => 0];
                $fallidosPorTecnico[$tec] = 0;
                $nuevaArquitecturaPorTecnico[$tec] = false;
            }
            if (!isset($resumen[$tec][$tipo])) {
                $resumen[$tec][$tipo] = 0;
            }
            $resumen[$tec][$tipo]++;

            if ($this->esNuevaArquitectura($tipo)) {
                $nuevaArquitecturaPorTecnico[$tec] = true;
                $hayNuevaArquitectura = true;
            }
            if (ResumenTiposServicio::clasificar($tipo) === 'fallido') {
                $fallidosPorTecnico[$tec]++;
            }

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

        // QUITAMOS LA HORA DE ALMUERZO (1 hora = 60 minutos) de cada técnico
        foreach ($jornada as $tec => $j) {
            if ($j['minutos'] > 60) {
                $jornada[$tec]['minutos'] -= 60;
            } elseif ($j['minutos'] > 0) {
                $jornada[$tec]['minutos'] = 0;
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

        // ── Rendimiento vs meta del día (por técnico) ──
        // La barra muestra los servicios SIN fallidos (color sólido) y los
        // fallidos (tono claro). El % se calcula sobre los servicios sin fallidos.
        // Reglas de meta: hábil 6 | sábado 3 | Nueva Arquitectura 3 (2 en sábado).
        // Domingos y festivos: sin meta.
        $rendimiento = [];
        foreach ($resumen as $tec => $fila) {
            $servicios = array_sum($fila); // incluye fallidos
            $fallidosTec = $fallidosPorTecnico[$tec] ?? 0;
            $sinFallidos = max(0, $servicios - $fallidosTec);
            $esNA = !empty($nuevaArquitecturaPorTecnico[$tec]);

            if ($esNoLaboral) {
                $metaTec = 0;
                $metaAmarilloTec = 0;
            } elseif ($esNA) {
                $metaTec = $metaNuevaArquitectura;
                $metaAmarilloTec = $metaNuevaArquitecturaAmarillo;
            } else {
                $metaTec = $metaVerde;
                $metaAmarilloTec = $metaAmarillo;
            }

            $porcentajeMeta = ($metaTec > 0) ? ($sinFallidos / $metaTec) * 100 : 0;
            $porcentajeFallidos = ($metaTec > 0) ? ($fallidosTec / $metaTec) * 100 : 0;
            $anchoSolido = min($porcentajeMeta, 100);
            $anchoFallidos = min(max(0, 100 - $anchoSolido), $porcentajeFallidos);

            if ($metaTec <= 0) {
                $colorSem = 'bg-slate-300';
                $txtSem = 'text-slate-500';
                $semaforo = 'Sin meta';
            } elseif ($sinFallidos >= $metaTec) {
                $colorSem = 'bg-emerald-500';
                $txtSem = 'text-emerald-700';
                $semaforo = 'Cumple meta';
            } elseif ($sinFallidos >= $metaAmarilloTec) {
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
                'sin_fallidos' => $sinFallidos,
                'fallidos' => $fallidosTec,
                'meta' => $metaTec,
                'meta_amarillo' => $metaAmarilloTec,
                'es_na' => $esNA,
                'porcentaje' => $anchoSolido,
                'porcentaje_real' => $porcentajeMeta,
                'porcentaje_fallidos' => $porcentajeFallidos,
                'ancho_fallidos' => $anchoFallidos,
                'color' => $colorSem,
                'texto' => $txtSem,
                'semaforo' => $semaforo
            ];
        }

        // Tabla de fallidos: delegacion -> lista de ['cliente'=>, 'punto'=>, 'total'=>, 'novedad'=>]
        $fallidos = [];
        $puntosPorCliente = [];
        $novedadesPorCliente = [];
        $totalFallidos = 0;
        foreach ($datosDia as $item) {
            $tipoUp = mb_strtoupper((string)($item['tipo_mantenimiento'] ?? ''), 'UTF-8');
            if (strpos($tipoUp, 'FALLID') === false) {
                continue;
            }
            $del = !empty($item['delegacion']) ? $item['delegacion'] : 'SIN DELEGACIÓN';
            $cli = !empty($item['nombre_cliente']) ? $item['nombre_cliente'] : 'Sin Cliente';
            $punto = !empty($item['nombre_punto']) ? $item['nombre_punto'] : 'Sin Punto';

            if (!isset($fallidos[$del])) {
                $fallidos[$del] = [];
            }
            $fallidos[$del][$cli] = ($fallidos[$del][$cli] ?? 0) + 1;
            if (!isset($puntosPorCliente[$del][$cli])) {
                $puntosPorCliente[$del][$cli] = [];
            }
            if (!in_array($punto, $puntosPorCliente[$del][$cli], true)) {
                $puntosPorCliente[$del][$cli][] = $punto;
            }

            $novTxt = trim((string)($item['novedades'] ?? ''));
            if ($novTxt !== '') {
                if (!isset($novedadesPorCliente[$del][$cli])) {
                    $novedadesPorCliente[$del][$cli] = [];
                }
                foreach (explode(',', $novTxt) as $nov) {
                    $nov = trim($nov);
                    if ($nov !== '') {
                        $novedadesPorCliente[$del][$cli][$nov] = true;
                    }
                }
            }

            $totalFallidos++;
        }

        // Convertir a lista de items (la vista itera así)
        $fallidosLista = [];
        foreach ($fallidos as $del => $mapaClientes) {
            arsort($mapaClientes);
            $items = [];
            foreach ($mapaClientes as $cli => $cant) {
                $puntos = $puntosPorCliente[$del][$cli] ?? [];
                $novedades = $novedadesPorCliente[$del][$cli] ?? [];
                $items[] = [
                    'cliente' => $cli,
                    'punto' => $puntos ? implode(', ', $puntos) : 'Sin Punto',
                    'total' => $cant,
                    'novedad' => $novedades ? implode(', ', array_keys($novedades)) : ''
                ];
            }
            $fallidosLista[$del] = $items;
        }
        $fallidos = $fallidosLista;
        ksort($fallidos);

        // Total de horas trabajadas en el día (para el KPI)
        $totalHorasDia = 0;
        foreach ($jornada as $j) {
            $totalHorasDia += $j['minutos'];
        }

        // ── Desplazamientos entre servicios (por técnico, mismo día) ──
        // Tiempo que tardó el técnico en ir de la salida de un servicio a la
        // entrada del siguiente (hora_entrada actual - hora_salida anterior).
        $desplazamientos = $this->calcularDesplazamientos($datosDia);
        $totalDesplazamientoDia = 0;
        foreach ($desplazamientos as $dsp) {
            $totalDesplazamientoDia += (int)$dsp['total_min'];
        }

        return [
            'fecha' => $fecha,
            'rangoInicio' => date('d/m/Y', strtotime($fecha)),
            'diaSemana' => $this->nombreDiaSemana($fecha),
            'esFestivo' => $esFestivo,
            'esSabado' => $esSabado,
            'esDomingo' => $esDomingo,
            'esNoLaboral' => $esNoLaboral,
            'metaVerde' => $metaVerde,
            'metaAmarillo' => $metaAmarillo,
            'metaNuevaArquitectura' => $metaNuevaArquitectura,
            'hayNuevaArquitectura' => $hayNuevaArquitectura,
            'resumenTipos' => $resumenTipos,
            'resumen' => $resumen,
            'jornada' => $jornada,
            'rendimiento' => $rendimiento,
            'tiposColumnas' => $tiposColumnas,
            'tecnicosOrdenados' => $tecnicosOrdenados,
            'totalesColumna' => $totalesColumna,
            'totalGeneral' => $totalGeneral,
            'totalHorasDia' => $totalHorasDia,
            'desplazamientos' => $desplazamientos,
            'totalDesplazamientoDia' => $totalDesplazamientoDia,
            'fallidos' => $fallidos,
            'totalFallidos' => $totalFallidos,
        ];
    }

    /**
     * Calcula el tiempo de desplazamiento entre servicios consecutivos de cada
     * técnico durante el día (hora_entrada actual - hora_salida anterior).
     *
     * Retorna: [tecnico => [
     *     'total_min' => int,
     *     'total_txt' => 'Hh Mm',
     *     'tramos'    => ['12m', '1h 5m', ...]
     * ]]
     */
    private function calcularDesplazamientos($datosDia)
    {
        $porTecnico = [];
        foreach ($datosDia as $item) {
            $tec = !empty($item['nombre_tecnico']) ? trim($item['nombre_tecnico']) : 'Sin Nombre';

            $he = isset($item['hora_entrada']) ? trim((string)$item['hora_entrada']) : '';
            $hs = isset($item['hora_salida']) ? trim((string)$item['hora_salida']) : '';
            $he = ($he !== '' && $he !== 'null' && strlen($he) >= 5) ? substr($he, 0, 5) : null;
            $hs = ($hs !== '' && $hs !== 'null' && strlen($hs) >= 5) ? substr($hs, 0, 5) : null;

            $porTecnico[$tec][] = ['entrada' => $he, 'salida' => $hs];
        }

        $resultado = [];
        foreach ($porTecnico as $tec => $servicios) {
            // Orden cronológico por hora de entrada (los sin hora van al final).
            usort($servicios, function ($a, $b) {
                return strcmp($a['entrada'] ?? '99:99', $b['entrada'] ?? '99:99');
            });

            $tramos = []; // ['min' => int, 'inicio' => min, 'fin' => min]
            $prevSalida = null;

            foreach ($servicios as $s) {
                if ($prevSalida !== null && $s['entrada'] !== null) {
                    $mS = ((int)substr($prevSalida, 0, 2) * 60) + (int)substr($prevSalida, 3, 2);
                    $mE = ((int)substr($s['entrada'], 0, 2) * 60) + (int)substr($s['entrada'], 3, 2);
                    $dif = $mE - $mS;
                    if ($dif > 0) {
                        $tramos[] = ['min' => $dif, 'inicio' => $mS, 'fin' => $mE];
                    }
                }
                $prevSalida = $s['salida'];
            }

            // Descontar el almuerzo (1 hora) igual que en la duración de jornada.
            $this->descontarAlmuerzo($tramos);

            $total = 0;
            $tramosTxt = [];
            foreach ($tramos as $t) {
                if ($t['min'] <= 0) {
                    continue;
                }
                $total += $t['min'];
                $tramosTxt[] = $this->formatearMinutos($t['min']);
            }

            $resultado[$tec] = [
                'total_min' => $total,
                'total_txt' => $this->formatearMinutos($total),
                'tramos' => $tramosTxt,
            ];
        }

        return $resultado;
    }

    /**
     * Descuenta 1 hora de almuerzo del tramo que cruza la franja 12:00–14:00.
     * Si ningún tramo la cruza, descuenta del tramo más largo (> 60 min).
     * Así el desplazamiento no queda inflado por el almuerzo (Opción A).
     */
    private function descontarAlmuerzo(&$tramos)
    {
        $inicioAlmuerzo = 12 * 60; // 12:00
        $finAlmuerzo = 14 * 60;    // 14:00

        $objetivo = null;
        $mejorSolape = 0;
        foreach ($tramos as $i => $t) {
            $solape = min($t['fin'], $finAlmuerzo) - max($t['inicio'], $inicioAlmuerzo);
            if ($solape > $mejorSolape) {
                $mejorSolape = $solape;
                $objetivo = $i;
            }
        }

        // Si ningún tramo cruza el mediodía, usar el tramo más largo (> 60 min).
        if ($objetivo === null) {
            $mejorMin = 60;
            foreach ($tramos as $i => $t) {
                if ($t['min'] > $mejorMin) {
                    $mejorMin = $t['min'];
                    $objetivo = $i;
                }
            }
        }

        if ($objetivo !== null) {
            $tramos[$objetivo]['min'] = max(0, $tramos[$objetivo]['min'] - 60);
        }
    }

    /**
     * Formatea una cantidad de minutos como texto corto: 45m / 1h / 1h 5m.
     */
    private function formatearMinutos($min)
    {
        $min = (int)$min;
        if ($min <= 0) {
            return '--';
        }
        $h = intdiv($min, 60);
        $m = $min % 60;
        if ($h > 0 && $m > 0) {
            return $h . 'h ' . $m . 'm';
        }
        return ($h > 0) ? ($h . 'h') : ($m . 'm');
    }

    /**
     * Detecta si el tipo de mantenimiento corresponde a Nueva Arquitectura.
     * Para ese tipo la meta diaria baja de 6 a 3 servicios.
     */
    private function esNuevaArquitectura($tipo)
    {
        $t = mb_strtoupper(trim((string)$tipo), 'UTF-8');
        $t = strtr($t, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ü' => 'U', 'Ñ' => 'N'
        ]);
        return strpos($t, 'NUEVA ARQUITECTURA') !== false
            || strpos($t, 'NUEVAARQUITECTURA') !== false;
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
    private function enviarPdf($html, $etiquetaPeriodo, $fechaIso, $fechaFin = null)
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

            $nombreArchivo = "Reporte_Tecnico_"
                . date('d-m-Y', strtotime($fechaIso)) . "_al_" . date('d-m-Y', strtotime($fechaFin ?? $fechaIso)) . ".pdf";

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