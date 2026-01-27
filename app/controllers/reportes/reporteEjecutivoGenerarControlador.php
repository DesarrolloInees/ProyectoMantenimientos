<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/ReporteEjecutivoModelo.php';

use Spatie\Browsershot\Browsershot;

class generarReporteControlador
{
    private $modelo;
    private $db;
    private $secciones = [];

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteEjecutivoModelo($this->db);
    }

    private function getDiasHabiles($inicio, $fin) {
        $fechaInicio = new DateTime($inicio);
        $fechaFin = new DateTime($fin);
        $diasHabiles = 0;
        while ($fechaInicio <= $fechaFin) {
            if ($fechaInicio->format('w') != 0) $diasHabiles++;
            $fechaInicio->modify('+1 day');
        }
        return ($diasHabiles > 0) ? $diasHabiles : 1;
    }

    public function index()
    {
        if (isset($_GET['accion']) && $_GET['accion'] == 'configurar') {
            $this->configurar();
            return;
        }

        $inicio = $_GET['inicio'] ?? date('Y-m-01');
        $fin = $_GET['fin'] ?? date('Y-m-t');
        $this->secciones = $_GET['secciones'] ?? [];
        $diasHabilesPeriodo = $this->getDiasHabiles($inicio, $fin);

        // ---------------------------------------------
        // 1. EVOLUCIÓN DIARIA AGRUPADA POR SEMANAS
        // ---------------------------------------------
        $datosDia = $this->modelo->getServiciosPorDia($inicio, $fin);
        $semanasGroup = [];
        
        if ($datosDia) {
            foreach ($datosDia as $dia) {
                $fechaObj = new DateTime($dia['fecha_visita']);
                $semanaNum = $fechaObj->format('W'); // Número de semana del año
                $semanaLabel = "Semana " . $semanaNum;
                
                $semanasGroup[$semanaLabel][] = [
                    'fecha' => $dia['fecha_visita'],
                    'dia_nombre' => $this->traducirDia($fechaObj->format('D')),
                    'total' => $dia['total']
                ];
            }
        }

        // ---------------------------------------------
        // 2. MATRIZ: TIPO DE MÁQUINA POR DELEGACIÓN
        // ---------------------------------------------
        $tiposMaquinaCols = $this->modelo->getTiposMaquinaActivos($inicio, $fin);
        $datosRawMaquina = $this->modelo->getDatosMatrizTipoMaquina($inicio, $fin);
        
        $matrizMaquina = [];
        $delegacionesMaquinaNames = [];

        if ($datosRawMaquina) {
            foreach ($datosRawMaquina as $row) {
                $matrizMaquina[$row['nombre_delegacion']][$row['nombre_tipo_maquina']] = $row['total'];
                $delegacionesMaquinaNames[$row['nombre_delegacion']] = true;
            }
        }
        $delegacionesListaMaquina = array_keys($delegacionesMaquinaNames);
        sort($delegacionesListaMaquina);

        // ---------------------------------------------
        // 3. REPUESTOS (TOP 10 POR DELEGACIÓN)
        // ---------------------------------------------
        $rawRepuestos = $this->modelo->getRepuestosPorDelegacion($inicio, $fin);
        $repuestosPorDelegacion = [];

        if ($rawRepuestos) {
            foreach ($rawRepuestos as $r) {
                $del = $r['nombre_delegacion'];
                if (!isset($repuestosPorDelegacion[$del])) {
                    $repuestosPorDelegacion[$del] = ['total_gral' => 0, 'items' => []];
                }
                
                // Solo guardamos si tenemos menos de 10 para hacer el TOP 10
                if (count($repuestosPorDelegacion[$del]['items']) < 10) {
                    $repuestosPorDelegacion[$del]['items'][] = [
                        'nombre' => $r['descripcion_repuesto'],
                        'cantidad' => $r['cantidad_usada']
                    ];
                }
                $repuestosPorDelegacion[$del]['total_gral'] += $r['cantidad_usada'];
            }
        }

        // ---------------------------------------------
        // 4. OTROS DATOS (Matriz Mantenimiento, KPIs, etc)
        // ---------------------------------------------
        $datosTecnicoDetallado = $this->modelo->getProductividadDetallada($inicio, $fin);
        $topTecnicos = array_slice($datosTecnicoDetallado, 0, 15);
        $datosDelegacion = $this->modelo->getDelegacionesIntervenidas($inicio, $fin);
        $datosPuntosFallidos = $this->modelo->getPuntosConFallidos($inicio, $fin);
        $datosCalificaciones = $this->modelo->getCalificacionesServicio($inicio, $fin);
        $datosNovedad = $this->modelo->getDistribucionNovedades($inicio, $fin);
        
        // Matriz Mantenimiento (La que ya tenías)
        $todosTiposMant = $this->modelo->getAllTiposMantenimiento();
        $datosRawMant = $this->modelo->getDatosMatrizMantenimiento($inicio, $fin);
        $matrizMant = [];
        $delegacionesMantNames = [];
        if ($datosRawMant) {
            foreach ($datosRawMant as $row) {
                $matrizMant[$row['nombre_delegacion']][$row['tipo']] = $row['total'];
                $delegacionesMantNames[$row['nombre_delegacion']] = true;
            }
        }
        $delegacionesListaMant = array_keys($delegacionesMantNames);
        sort($delegacionesListaMant);

        // KPIs Portada
        $rawKpis = $this->modelo->getKpisPorDelegacion($inicio, $fin);
        $kpisDelegacion = [];
        $totalGlobalServicios = 0;
        
        if ($rawKpis) {
            foreach ($rawKpis as $kpi) $totalGlobalServicios += $kpi['total_servicios'];
            
            foreach ($rawKpis as $kpi) {
                // --- LÓGICA CORREGIDA ---
                // Si hubo días efectivos (para evitar división por cero), dividimos servicios / días reales trabajados
                $diasReales = $kpi['dias_efectivos'] > 0 ? $kpi['dias_efectivos'] : 1;
                $promedioDiario = $kpi['total_servicios'] / $diasReales;
                
                $kpisDelegacion[] = [
                    'delegacion' => $kpi['nombre_delegacion'],
                    'total' => $kpi['total_servicios'],
                    'porcentaje' => ($totalGlobalServicios > 0) ? round(($kpi['total_servicios'] / $totalGlobalServicios) * 100, 1) : 0,
                    'novedades' => $kpi['total_novedades'],
                    'promedio_diario' => round($promedioDiario, 2) // Ahora sí dará un número real (ej. 4.5 servicios/día)
                ];
            }
        }
        // ---------------------------------------------------------
        // 4. GENERACIÓN DE PDF
        // ---------------------------------------------------------
        
        // Limpiar buffer por si hay errores previos ocultos
        if (ob_get_length()) ob_end_clean();
        ob_start();
        
        // Incluir la vista (Aquí se usan las variables $matrizFinal, $todosTipos, etc.)
        include __DIR__ . '/../../views/reportes/reporteEjecutivoGenerar.php';
        
        $html = ob_get_clean();

        try {
            // Configuración rutas Node/Chrome (Ajusta según tu PC si es necesario)
            $nodePath = 'C:\\Program Files\\nodejs\\node.exe';
            $npmPath  = 'C:\\Program Files\\nodejs\\npm.cmd';
            
            // Buscar Chrome automáticamente
            $posiblesRutasChrome = [
                'C:\\Users\\User\\.cache\\puppeteer\\chrome\\win64-144.0.7559.96\\chrome-win64\\chrome.exe',
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
            ];
            $chromePath = null;
            foreach ($posiblesRutasChrome as $ruta) {
                if (file_exists($ruta)) { $chromePath = $ruta; break; }
            }

            $browsershot = Browsershot::html($html)
                ->setNodeBinary($nodePath)
                ->setNpmBinary($npmPath)
                ->setOption('args', ['--no-sandbox'])
                ->format('A4')
                ->landscape() // Horizontal
                ->margins(10, 10, 10, 10)
                ->scale(0.8) // Escala para que quepa más info
                ->timeout(120);

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Reporte_Ejecutivo.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            echo $pdfContent;
            exit;

        } catch (Exception $e) {
            // En caso de error, mostramos una página simple de error
            echo "<h1>Error generando PDF</h1><p>" . $e->getMessage() . "</p>";
            die();
        }
    }

    private function traducirDia($diaIngles) {
        $dias = ['Mon'=>'Lun', 'Tue'=>'Mar', 'Wed'=>'Mié', 'Thu'=>'Jue', 'Fri'=>'Vie', 'Sat'=>'Sáb', 'Sun'=>'Dom'];
        return $dias[$diaIngles] ?? $diaIngles;
    }

    public function configurar()
    {
        $filtros = ['fecha_inicio' => $_GET['inicio'] ?? date('Y-m-01'), 'fecha_fin' => $_GET['fin'] ?? date('Y-m-t')];
        require_once __DIR__ . '/../../views/reportes/configurarReporte.php';
    }

    public function seccionActiva($nombre)
    {
        return in_array($nombre, $this->secciones);
    }
}