<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/ReporteEjecutivoModelo.php';

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
}
