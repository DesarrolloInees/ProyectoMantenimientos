<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/ReporteEjecutivoModelo.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ReporteEjecutivoControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteEjecutivoModelo($this->db);
    }

    public function index()
    {
        // Inicializar variables
        $datosDia          = [];
        $datosDelegacion   = [];
        $datosHoras        = [];
        $datosTipo         = [];
        $datosNovedad      = [];
        $datosPuntosCriticos = []; // Nueva variable para los fallidos
        $datosRepuestos    = [];

        $datosNovedadesPorDelegacion = [];
        $datosNovedadesFallidasPorDelegacion = [];
        $topNovedadesGlobales = [];
        $clientesNovedades = [];

        $puntosVisitados = [];
        $detallePuntosHover = [];
        $detalleNovedadesHover = [];
        $detalleNovedadesFallidasHover = [];
        $detalleClientesHover = [];
        $detalleClienteNovedadHover = [];

        $metaServicios = [
            'esperados' => 0,
            'dias_laborables' => 0,
            'tecnicos_dias' => 0,
            'esperados_normal' => 0,
            'esperados_nueva_arquitectura' => 0,
        ];
        $cumplimientoMeta = 0;

        // Inicializar KPIs
        $totalServicios = 0;
        $mediaGlobal    = 0;
        $mediaDiaria    = 0;

        // Filtros por defecto
        $filtros = [
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin'    => date('Y-m-d')
        ];

        $mensaje = "";

        // POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? $filtros['fecha_inicio'];
            $filtros['fecha_fin']    = $_POST['fecha_fin'] ?? $filtros['fecha_fin'];
        }

        // Ejecutar consultas
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {

            $inicio = $filtros['fecha_inicio'];
            $fin    = $filtros['fecha_fin'];

            // 1. OBTENEMOS LOS DATOS (Usando las funciones corregidas del PDF)
            $datosDia          = $this->modelo->getServiciosPorDia($inicio, $fin);

            // KPI Delegaciones (Usamos la misma del PDF para consistencia)
            $rawKpis           = $this->modelo->getKpisPorDelegacion($inicio, $fin);
            // Transformamos para gráfico simple de barras
            foreach ($rawKpis as $k) {
                $datosDelegacion[] = ['nombre_delegacion' => $k['nombre_delegacion'], 'total' => $k['total_servicios']];
            }

            $datosHoras        = $this->modelo->getHorasVsServicios($inicio, $fin);
            $datosTipo         = $this->modelo->getPorTipoMantenimiento($inicio, $fin);
            $datosNovedad      = $this->modelo->getDistribucionNovedades($inicio, $fin);

            // AQUI ESTA LA CLAVE: Usamos la nueva función de > 2 fallidos
            $datosPuntosCriticos = $this->modelo->getPuntosConMasFallidos($inicio, $fin);

            // AQUI TAMBIÉN: Usamos la de origen (INEES vs PROSEGUR)
            $datosRepuestos    = $this->modelo->getOrigenRepuestos($inicio, $fin);

            // Novedades separadas: generales (sin fallidos) y de servicios fallidos.
            $rawNovedades = $this->modelo->getNovedadesPorDelegacion($inicio, $fin, false);
            $rawNovedadesFallidas = $this->modelo->getNovedadesPorDelegacion($inicio, $fin, true);

            $datosNovedadesPorDelegacion = $this->agruparTopNovedades($rawNovedades, 3);
            $datosNovedadesFallidasPorDelegacion = $this->agruparTopNovedades($rawNovedadesFallidas, 6);
            $topNovedadesGlobales = $this->obtenerTopNovedadesGlobales($rawNovedades, 3);

            // Top 3 de clientes con más novedades por delegación + sus 3 novedades principales.
            $rawClientesNovedades = $this->modelo->getClientesConMasNovedades($inicio, $fin);
            $clientesNovedades = $this->agruparTopClientesNovedades($rawClientesNovedades, 3, 3);

            // Puntos más visitados (nuevo gráfico web) y detalle para hover/modal.
            $puntosVisitados = $this->modelo->getPuntosMasVisitadosWeb($inicio, $fin, 2, 20);
            $detallePuntosHover = $this->agruparDetallePorPunto(
                $this->modelo->getDetalleServiciosPorPunto($inicio, $fin),
                6
            );

            $rawDetalleNovedades = $this->modelo->getDetalleNovedades($inicio, $fin, null);
            $rawDetalleNovedadesNoFallidas = [];
            $rawDetalleNovedadesFallidas = [];

            foreach ($rawDetalleNovedades as $fila) {
                if (stripos((string) ($fila['tipo_servicio'] ?? ''), 'FALLID') !== false) {
                    $rawDetalleNovedadesFallidas[] = $fila;
                } else {
                    $rawDetalleNovedadesNoFallidas[] = $fila;
                }
            }

            $detalleNovedadesHover = $this->agruparDetalleNovedades($rawDetalleNovedadesNoFallidas, 6);
            $detalleNovedadesFallidasHover = $this->agruparDetalleNovedades($rawDetalleNovedadesFallidas, 6);

            $detalleClientesHover = $this->agruparDetalleClientes($rawDetalleNovedades, 6);
            $detalleClienteNovedadHover = $this->agruparDetalleClienteNovedad($rawDetalleNovedades, 6);

            // Meta de servicios esperados para el período.
            $metaServicios = $this->modelo->getMetaServiciosEsperados($inicio, $fin);

            // 2. CALCULOS KPI
            foreach ($datosDia as $d) $totalServicios += $d['total'];

            $fecha1 = new DateTime($inicio);
            $fecha2 = new DateTime($fin);
            $cantidadDias = $fecha1->diff($fecha2)->days + 1;
            $numTecnicos = count($datosHoras);

            $mediaGlobal = $numTecnicos > 0 ? round($totalServicios / $numTecnicos, 1) : 0;
            $mediaDiaria = ($numTecnicos > 0 && $cantidadDias > 0)
                ? round(($totalServicios / $numTecnicos) / $cantidadDias, 2)
                : 0;

            $cumplimientoMeta = $metaServicios['esperados'] > 0
                ? round(($totalServicios / $metaServicios['esperados']) * 100, 1)
                : 0;

            if (empty($datosDia) && empty($datosDelegacion)) {
                $mensaje = "No se encontraron datos para el rango de fechas seleccionado.";
            }
        } else {
            $mensaje = "Por favor selecciona un rango de fechas válido.";
        }

        // Configuración de la vista
        $titulo = "Tablero Ejecutivo de Servicios";
        $vistaContenido = "app/views/reportes/reporteEjecutivoVista.php";

        include "app/views/plantillaVista.php";
    }

    private function agruparTopNovedades($filas, $limite = 6)
    {
        $agrupado = [];

        foreach ($filas as $fila) {
            $delegacion = $fila['nombre_delegacion'] ?? 'SIN DELEGACIÓN';
            if (!isset($agrupado[$delegacion])) {
                $agrupado[$delegacion] = [];
            }

            $agrupado[$delegacion][] = [
                'nombre' => $fila['nombre_novedad'] ?? 'Sin novedad',
                'total' => (int) ($fila['total'] ?? 0),
            ];
        }

        foreach ($agrupado as &$items) {
            usort($items, function ($a, $b) {
                if ($a['total'] === $b['total']) {
                    return strcmp($a['nombre'], $b['nombre']);
                }
                return ($a['total'] < $b['total']) ? 1 : -1;
            });
            $items = array_slice($items, 0, $limite);
        }
        unset($items);

        ksort($agrupado);

        return $agrupado;
    }

    private function obtenerTopNovedadesGlobales($filas, $limite = 6)
    {
        $totales = [];

        foreach ($filas as $fila) {
            $nombre = $fila['nombre_novedad'] ?? 'Sin novedad';
            $totales[$nombre] = ($totales[$nombre] ?? 0) + (int) ($fila['total'] ?? 0);
        }

        arsort($totales);

        return array_slice(array_keys($totales), 0, $limite);
    }

    /**
     * Agrupa las novedades por delegación y cliente, deja el Top N de clientes
     * por delegación y, para cada cliente, su Top N de novedades principales.
     */
    private function agruparTopClientesNovedades($datos, $limiteClientes = 3, $limiteNovedades = 3)
    {
        $totales = $datos['totales'] ?? [];
        $novedades = $datos['novedades'] ?? [];

        $chipsPorCliente = [];
        foreach ($novedades as $nov) {
            $clave = $this->claveClienteDelegacion(
                $nov['nombre_delegacion'] ?? 'SIN DELEGACIÓN',
                $nov['id_cliente'] ?? ''
            );
            $chipsPorCliente[$clave][] = [
                'nombre' => $nov['nombre_novedad'] ?? 'Sin novedad',
                'total' => (int) ($nov['total'] ?? 0),
            ];
        }

        $agrupado = [];
        foreach ($totales as $fila) {
            $delegacion = $fila['nombre_delegacion'] ?: 'SIN DELEGACIÓN';
            $cliente = $fila['nombre_cliente'] ?: 'Sin Cliente';
            $clave = $this->claveClienteDelegacion($delegacion, $fila['id_cliente'] ?? '');

            $items = $chipsPorCliente[$clave] ?? [];
            usort($items, function ($a, $b) {
                if ($a['total'] === $b['total']) {
                    return strcmp($a['nombre'], $b['nombre']);
                }
                return ($a['total'] < $b['total']) ? 1 : -1;
            });

            $agrupado[$delegacion][] = [
                'id_cliente' => (int) ($fila['id_cliente'] ?? 0),
                'cliente' => $cliente,
                'total' => (int) ($fila['total'] ?? 0),
                'novedades' => array_slice($items, 0, $limiteNovedades),
            ];
        }

        foreach ($agrupado as &$clientes) {
            usort($clientes, function ($a, $b) {
                if ($a['total'] === $b['total']) {
                    return strcmp($a['cliente'], $b['cliente']);
                }
                return ($a['total'] < $b['total']) ? 1 : -1;
            });
            $clientes = array_slice($clientes, 0, $limiteClientes);
        }
        unset($clientes);

        ksort($agrupado);

        return $agrupado;
    }

    private function claveClienteDelegacion($delegacion, $idCliente)
    {
        return ($delegacion ?: 'SIN DELEGACIÓN') . '||' . ($idCliente ?: '');
    }

    // ==========================================
    // AGRUPADORES PARA EL DETALLE (HOVER / MODAL)
    // ==========================================

    private function agruparDetallePorPunto($filas, $limite = 6)
    {
        $grupos = [];

        foreach ($filas as $fila) {
            $idPunto = (string) ($fila['id_punto'] ?? '');
            if ($idPunto === '') {
                continue;
            }

            if (!isset($grupos[$idPunto])) {
                $grupos[$idPunto] = [];
            }

            if (count($grupos[$idPunto]) >= $limite) {
                continue;
            }

            $grupos[$idPunto][] = $this->formatearFilaDetalle($fila, true);
        }

        return $grupos;
    }

    private function agruparDetalleNovedades($filas, $limite = 6)
    {
        $grupos = [];

        foreach ($filas as $fila) {
            $delegacion = $fila['nombre_delegacion'] ?? 'SIN DELEGACIÓN';
            $novedad = $fila['nombre_novedad'] ?? 'Sin novedad';
            $clave = $delegacion . '||' . $novedad;

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [];
            }

            if (count($grupos[$clave]) >= $limite) {
                continue;
            }

            $item = $this->formatearFilaDetalle($fila, false);
            $item['novedades'] = (string) ($fila['nombre_novedad'] ?? '');
            $grupos[$clave][] = $item;
        }

        return $grupos;
    }

    private function agruparDetalleClientes($filas, $limite = 6)
    {
        $grupos = [];
        $indices = [];

        foreach ($filas as $fila) {
            $delegacion = $fila['nombre_delegacion'] ?? 'SIN DELEGACIÓN';
            $idCliente = (string) ($fila['id_cliente'] ?? '');
            $clave = $delegacion . '||' . $idCliente;
            $idOrden = (string) ($fila['id_ordenes_servicio'] ?? '');

            if (!isset($indices[$clave][$idOrden])) {
                if (!isset($grupos[$clave])) {
                    $grupos[$clave] = [];
                }

                if (count($grupos[$clave]) >= $limite) {
                    continue;
                }

                $grupos[$clave][] = [
                    'fecha' => $this->fechaTexto($fila['fecha_visita'] ?? ''),
                    'cliente' => $fila['nombre_cliente'] ?? 'Sin Cliente',
                    'punto' => $fila['nombre_punto'] ?? '',
                    'tecnico' => $fila['nombre_tecnico'] ?? 'SIN TÉCNICO',
                    'tipo' => $fila['tipo_servicio'] ?? 'SIN TIPO',
                    'novedades' => [],
                ];

                $indices[$clave][$idOrden] = count($grupos[$clave]) - 1;
            }

            $idx = $indices[$clave][$idOrden] ?? null;
            if ($idx !== null) {
                $nov = trim((string) ($fila['nombre_novedad'] ?? ''));
                if ($nov !== '' && !in_array($nov, $grupos[$clave][$idx]['novedades'], true)) {
                    $grupos[$clave][$idx]['novedades'][] = $nov;
                }
            }
        }

        foreach ($grupos as &$items) {
            foreach ($items as &$item) {
                $item['novedades'] = implode(', ', $item['novedades']);
            }
        }
        unset($items, $item);

        return $grupos;
    }

    private function agruparDetalleClienteNovedad($filas, $limite = 6)
    {
        $grupos = [];
        $vistos = [];

        foreach ($filas as $fila) {
            $delegacion = $fila['nombre_delegacion'] ?? 'SIN DELEGACIÓN';
            $idCliente = (string) ($fila['id_cliente'] ?? '');
            $novedad = $fila['nombre_novedad'] ?? 'Sin novedad';
            $idOrden = (string) ($fila['id_ordenes_servicio'] ?? '');
            $clave = $delegacion . '||' . $idCliente . '||' . $novedad;

            if (isset($vistos[$clave][$idOrden])) {
                continue;
            }

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [];
            }

            if (count($grupos[$clave]) >= $limite) {
                continue;
            }

            $item = $this->formatearFilaDetalle($fila, false);
            $item['novedades'] = $novedad;
            $grupos[$clave][] = $item;
            $vistos[$clave][$idOrden] = true;
        }

        return $grupos;
    }

    private function formatearFilaDetalle($fila, $incluirNovedades = true)
    {
        return [
            'fecha' => $this->fechaTexto($fila['fecha_visita'] ?? ''),
            'cliente' => $fila['nombre_cliente'] ?? 'Sin Cliente',
            'punto' => $fila['nombre_punto'] ?? '',
            'tecnico' => $fila['nombre_tecnico'] ?? 'SIN TÉCNICO',
            'tipo' => $fila['tipo_servicio'] ?? 'SIN TIPO',
            'novedades' => $incluirNovedades ? (string) ($fila['novedades'] ?? '') : '',
        ];
    }

    // ==========================================
    // EXPORTACIÓN A EXCEL
    // ==========================================

    public function descargarExcel()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $fechaInicio = $this->validarFecha($_GET['fecha_inicio'] ?? '', date('Y-m-01'));
        $fechaFin    = $this->validarFecha($_GET['fecha_fin'] ?? '', date('Y-m-d'));

        $tiposValidos = ['puntos', 'novedades', 'novedades_fallidas', 'clientes'];
        $tipo = strtolower(trim((string) ($_GET['tipo'] ?? 'puntos')));
        if (!in_array($tipo, $tiposValidos, true)) {
            $tipo = 'puntos';
        }

        $filtros = [
            'id_punto'        => isset($_GET['id_punto']) && is_numeric($_GET['id_punto']) ? (int) $_GET['id_punto'] : null,
            'id_cliente'      => isset($_GET['id_cliente']) && is_numeric($_GET['id_cliente']) ? (int) $_GET['id_cliente'] : null,
            'id_tipo_novedad' => isset($_GET['id_tipo_novedad']) && is_numeric($_GET['id_tipo_novedad']) ? (int) $_GET['id_tipo_novedad'] : null,
            'delegacion'      => isset($_GET['delegacion']) ? trim((string) $_GET['delegacion']) : '',
            'novedad'         => isset($_GET['novedad']) ? trim((string) $_GET['novedad']) : '',
        ];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $textosFiltros = [];
        if (!empty($filtros['delegacion'])) {
            $textosFiltros[] = 'Delegación: ' . $filtros['delegacion'];
        }
        if (!empty($filtros['novedad'])) {
            $textosFiltros[] = 'Novedad: ' . $filtros['novedad'];
        }
        if (!empty($filtros['id_cliente'])) {
            $textosFiltros[] = 'Cliente ID: ' . $filtros['id_cliente'];
        }
        if (!empty($filtros['id_punto'])) {
            $textosFiltros[] = 'Punto ID: ' . $filtros['id_punto'];
        }

        $subtitulo = 'Período: ' . $this->fechaTexto($fechaInicio) . ' al ' . $this->fechaTexto($fechaFin);
        if (!empty($textosFiltros)) {
            $subtitulo .= ' | ' . implode(' · ', $textosFiltros);
        }

        if ($tipo === 'puntos') {
            $this->hojaPuntos($spreadsheet, $fechaInicio, $fechaFin, $filtros, $subtitulo);
        } elseif ($tipo === 'clientes') {
            $this->hojaClientes($spreadsheet, $fechaInicio, $fechaFin, $filtros, $subtitulo);
        } else {
            $this->hojaNovedades($spreadsheet, $fechaInicio, $fechaFin, $filtros, $subtitulo, $tipo === 'novedades_fallidas');
        }

        $spreadsheet->setActiveSheetIndex(0);

        $nombre = 'Reporte_Ejecutivo_' . $tipo . '_' . $fechaInicio . '_al_' . $fechaFin . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function hojaPuntos(Spreadsheet $spreadsheet, $inicio, $fin, $filtros, $subtitulo)
    {
        $resumen = $this->modelo->getPuntosMasVisitadosWeb($inicio, $fin, 1, null, $filtros);
        $detalle = $this->modelo->getDetalleServiciosPorPunto($inicio, $fin, $filtros);

        $hoja = $spreadsheet->createSheet(0);
        $hoja->setTitle('Resumen');
        $this->prepararHoja(
            $hoja,
            'Reporte Ejecutivo - Puntos Más Visitados',
            $subtitulo,
            ['Delegación', 'Punto', 'Tipos de máquina', 'Total visitas']
        );

        $fila = 5;
        foreach ($resumen as $r) {
            $hoja->setCellValue('A' . $fila, $r['nombre_delegacion']);
            $hoja->setCellValue('B' . $fila, $r['nombre_punto']);
            $hoja->setCellValue('C' . $fila, $r['tipos'] ?: 'S/D');
            $hoja->setCellValue('D' . $fila, (int) $r['total_visitas']);
            $fila++;
        }
        $this->finalizarHoja($hoja, 4, ['A' => 28, 'B' => 40, 'C' => 34, 'D' => 15]);

        $hoja2 = $spreadsheet->createSheet(1);
        $hoja2->setTitle('Detalle servicios');
        $this->prepararHoja(
            $hoja2,
            'Detalle de servicios por punto',
            $subtitulo,
            ['Fecha', 'Cliente', 'Delegación', 'Punto', 'Técnico', 'Tipo de servicio', 'Novedades']
        );

        $f2 = 5;
        foreach ($detalle as $d) {
            $hoja2->setCellValue('A' . $f2, $this->fechaTexto($d['fecha_visita']));
            $hoja2->setCellValue('B' . $f2, $d['nombre_cliente']);
            $hoja2->setCellValue('C' . $f2, $d['nombre_delegacion']);
            $hoja2->setCellValue('D' . $f2, $d['nombre_punto']);
            $hoja2->setCellValue('E' . $f2, $d['nombre_tecnico']);
            $hoja2->setCellValue('F' . $f2, $d['tipo_servicio']);
            $hoja2->setCellValue('G' . $f2, $d['novedades'] ?: '');
            $f2++;
        }
        $this->finalizarHoja($hoja2, 4, ['A' => 13, 'B' => 34, 'C' => 26, 'D' => 36, 'E' => 28, 'F' => 30, 'G' => 48]);
    }

    private function hojaNovedades(Spreadsheet $spreadsheet, $inicio, $fin, $filtros, $subtitulo, $soloFallidos)
    {
        $detalle = $this->modelo->getDetalleNovedades($inicio, $fin, $soloFallidos, $filtros);

        $resumen = [];
        foreach ($detalle as $d) {
            $clave = $d['nombre_delegacion'] . '||' . $d['nombre_novedad'];
            if (!isset($resumen[$clave])) {
                $resumen[$clave] = [
                    'delegacion' => $d['nombre_delegacion'],
                    'novedad' => $d['nombre_novedad'],
                    'ordenes' => [],
                ];
            }
            $resumen[$clave]['ordenes'][$d['id_ordenes_servicio']] = true;
        }

        uasort($resumen, function ($a, $b) {
            $cmp = strcmp($a['delegacion'], $b['delegacion']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return count($b['ordenes']) <=> count($a['ordenes']);
        });

        $hoja = $spreadsheet->createSheet(0);
        $hoja->setTitle('Resumen');
        $this->prepararHoja(
            $hoja,
            $soloFallidos
                ? 'Reporte Ejecutivo - Novedades en Servicios Fallidos'
                : 'Reporte Ejecutivo - Principales Novedades por Delegación',
            $subtitulo,
            ['Delegación', 'Novedad', 'Total servicios']
        );

        $fila = 5;
        foreach ($resumen as $r) {
            $hoja->setCellValue('A' . $fila, $r['delegacion']);
            $hoja->setCellValue('B' . $fila, $r['novedad']);
            $hoja->setCellValue('C' . $fila, count($r['ordenes']));
            $fila++;
        }
        $this->finalizarHoja($hoja, 4, ['A' => 30, 'B' => 50, 'C' => 16]);

        $hoja2 = $spreadsheet->createSheet(1);
        $hoja2->setTitle('Detalle servicios');
        $this->prepararHoja(
            $hoja2,
            'Detalle de servicios con novedad',
            $subtitulo,
            ['Fecha', 'Delegación', 'Cliente', 'Punto', 'Técnico', 'Tipo de servicio', 'Novedad']
        );

        $f2 = 5;
        foreach ($detalle as $d) {
            $hoja2->setCellValue('A' . $f2, $this->fechaTexto($d['fecha_visita']));
            $hoja2->setCellValue('B' . $f2, $d['nombre_delegacion']);
            $hoja2->setCellValue('C' . $f2, $d['nombre_cliente']);
            $hoja2->setCellValue('D' . $f2, $d['nombre_punto']);
            $hoja2->setCellValue('E' . $f2, $d['nombre_tecnico']);
            $hoja2->setCellValue('F' . $f2, $d['tipo_servicio']);
            $hoja2->setCellValue('G' . $f2, $d['nombre_novedad']);
            $f2++;
        }
        $this->finalizarHoja($hoja2, 4, ['A' => 13, 'B' => 28, 'C' => 34, 'D' => 36, 'E' => 28, 'F' => 30, 'G' => 40]);
    }

    private function hojaClientes(Spreadsheet $spreadsheet, $inicio, $fin, $filtros, $subtitulo)
    {
        $detalle = $this->modelo->getDetalleNovedades($inicio, $fin, null, $filtros);

        $servicios = [];
        $resumen = [];

        foreach ($detalle as $d) {
            $idOrden = (string) $d['id_ordenes_servicio'];

            if (!isset($servicios[$idOrden])) {
                $servicios[$idOrden] = [
                    'fecha' => $d['fecha_visita'],
                    'delegacion' => $d['nombre_delegacion'],
                    'cliente' => $d['nombre_cliente'],
                    'punto' => $d['nombre_punto'],
                    'tecnico' => $d['nombre_tecnico'],
                    'tipo' => $d['tipo_servicio'],
                    'novedades' => [],
                ];
            }

            $nov = trim((string) $d['nombre_novedad']);
            if ($nov !== '' && !in_array($nov, $servicios[$idOrden]['novedades'], true)) {
                $servicios[$idOrden]['novedades'][] = $nov;
            }

            $clave = $d['nombre_delegacion'] . '||' . $d['id_cliente'];
            if (!isset($resumen[$clave])) {
                $resumen[$clave] = [
                    'delegacion' => $d['nombre_delegacion'],
                    'cliente' => $d['nombre_cliente'],
                    'ordenes' => [],
                    'novedades' => [],
                ];
            }
            $resumen[$clave]['ordenes'][$idOrden] = true;
            $resumen[$clave]['novedades'][$nov] = true;
        }

        foreach ($servicios as &$s) {
            $s['novedades'] = implode(', ', $s['novedades']);
        }
        unset($s);

        uasort($resumen, function ($a, $b) {
            $cmp = strcmp($a['delegacion'], $b['delegacion']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return count($b['ordenes']) <=> count($a['ordenes']);
        });

        $hoja = $spreadsheet->createSheet(0);
        $hoja->setTitle('Resumen');
        $this->prepararHoja(
            $hoja,
            'Reporte Ejecutivo - Clientes con Más Novedades por Delegación',
            $subtitulo,
            ['Delegación', 'Cliente', 'Total servicios con novedad', 'Novedades']
        );

        $fila = 5;
        foreach ($resumen as $r) {
            $hoja->setCellValue('A' . $fila, $r['delegacion']);
            $hoja->setCellValue('B' . $fila, $r['cliente']);
            $hoja->setCellValue('C' . $fila, count($r['ordenes']));
            $hoja->setCellValue('D' . $fila, implode(', ', array_keys($r['novedades'])));
            $fila++;
        }
        $this->finalizarHoja($hoja, 4, ['A' => 28, 'B' => 38, 'C' => 22, 'D' => 60]);

        $hoja2 = $spreadsheet->createSheet(1);
        $hoja2->setTitle('Detalle servicios');
        $this->prepararHoja(
            $hoja2,
            'Detalle de servicios con novedad por cliente',
            $subtitulo,
            ['Fecha', 'Delegación', 'Cliente', 'Punto', 'Técnico', 'Tipo de servicio', 'Novedades']
        );

        $f2 = 5;
        foreach ($servicios as $s) {
            $hoja2->setCellValue('A' . $f2, $this->fechaTexto($s['fecha']));
            $hoja2->setCellValue('B' . $f2, $s['delegacion']);
            $hoja2->setCellValue('C' . $f2, $s['cliente']);
            $hoja2->setCellValue('D' . $f2, $s['punto']);
            $hoja2->setCellValue('E' . $f2, $s['tecnico']);
            $hoja2->setCellValue('F' . $f2, $s['tipo']);
            $hoja2->setCellValue('G' . $f2, $s['novedades']);
            $f2++;
        }
        $this->finalizarHoja($hoja2, 4, ['A' => 13, 'B' => 28, 'C' => 34, 'D' => 36, 'E' => 28, 'F' => 30, 'G' => 50]);
    }

    private function prepararHoja($hoja, $titulo, $subtitulo, $headers)
    {
        $ultimaColumna = Coordinate::stringFromColumnIndex(count($headers));

        $hoja->setCellValue('A1', $titulo);
        $hoja->mergeCells('A1:' . $ultimaColumna . '1');
        $hoja->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F4E78']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $hoja->getRowDimension(1)->setRowHeight(24);

        $hoja->setCellValue('A2', $subtitulo);
        $hoja->mergeCells('A2:' . $ultimaColumna . '2');
        $hoja->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
        ]);

        $hoja->fromArray($headers, null, 'A4');
        $this->estiloHead($hoja, 'A4:' . $ultimaColumna . '4');
    }

    private function estiloHead($hoja, $rango)
    {
        $hoja->getStyle($rango)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B0B0']]],
        ]);
        $hoja->getRowDimension(4)->setRowHeight(30);
    }

    private function finalizarHoja($hoja, $filaHeader, $anchos)
    {
        $this->protegerTextoHoja($hoja);

        foreach ($anchos as $col => $ancho) {
            $hoja->getColumnDimension($col)->setWidth($ancho);
        }

        $ultimaFila = $hoja->getHighestRow();
        $ultimaCol = $hoja->getHighestColumn();

        if ($ultimaFila > $filaHeader) {
            $hoja->setAutoFilter('A' . $filaHeader . ':' . $ultimaCol . $ultimaFila);
            $hoja->getStyle('A' . ($filaHeader + 1) . ':' . $ultimaCol . $ultimaFila)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        $hoja->freezePane('A' . ($filaHeader + 1));
    }

    private function protegerTextoHoja($hoja)
    {
        $ultimaFila = $hoja->getHighestRow();
        $ultimaColumna = Coordinate::columnIndexFromString($hoja->getHighestColumn());

        for ($fila = 1; $fila <= $ultimaFila; $fila++) {
            for ($columna = 1; $columna <= $ultimaColumna; $columna++) {
                $celda = $hoja->getCell(Coordinate::stringFromColumnIndex($columna) . $fila);
                $valor = $celda->getValue();

                if (is_string($valor) && $valor !== '' && $valor[0] === '=') {
                    $celda->setValueExplicit($valor, DataType::TYPE_STRING);
                }
            }
        }
    }

    private function validarFecha($fecha, $fallback)
    {
        if (!empty($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }
        return $fallback;
    }

    private function fechaTexto($fecha)
    {
        if (empty($fecha)) {
            return '';
        }
        $timestamp = strtotime($fecha);
        return $timestamp ? date('d/m/Y', $timestamp) : (string) $fecha;
    }
}
