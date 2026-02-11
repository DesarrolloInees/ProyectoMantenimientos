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

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteEjecutivoModelo($this->db);
    }

    private function getDiasHabiles($inicio, $fin)
    {
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
                $semanaNum = $fechaObj->format('W');
                $semanaLabel = "Semana " . $semanaNum;

                $semanasGroup[$semanaLabel][] = [
                    'fecha' => $dia['fecha_visita'],
                    'dia_nombre' => $this->traducirDia($fechaObj->format('D')),
                    'total' => $dia['total'],
                    // AGREGAMOS ESTA LÍNEA:
                    'num_tecnicos' => $dia['num_tecnicos']
                ];
            }
        }

        // Ajusta esta ruta a donde tengas tu carpeta de logos real
        // __DIR__ te ubica en la carpeta actual, sube niveles con /../ según necesites
        $rutaLogo = __DIR__ . '/../../logos/logoInees.jpg';
        $logoBase64 = "";

        if (file_exists($rutaLogo)) {
            $type = pathinfo($rutaLogo, PATHINFO_EXTENSION);
            $data = file_get_contents($rutaLogo);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        } else {
            // Un placeholder por si no encuentra la imagen (opcional)
            $logoBase64 = "https://via.placeholder.com/150";
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
        // 3. REPUESTOS (TOP 5 POR DELEGACIÓN)
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

        // 1. Obtener el número del formulario (si no envían nada, usamos 2 por defecto)
        $minVisitas = isset($_GET['min_visitas']) && is_numeric($_GET['min_visitas']) ? (int)$_GET['min_visitas'] : 2;

        // 2. Llamar al modelo PASANDO la variable $minVisitas
        // Nota: Usamos getPuntosMasVisitados que es la lógica de frecuencia
        $rawVisitados = $this->modelo->getPuntosMasVisitados($inicio, $fin, $minVisitas);

        $puntosVisitadosAgrupados = [];
        $totalPuntosUnicosGlobal = 0;

        if ($rawVisitados) {
            // ---------------------------------------------------------
            // AQUÍ ESTÁ EL CAMBIO:
            // En vez de contar filas (count), sumamos la columna 'total_visitas'
            // ---------------------------------------------------------
            $totalPuntosUnicosGlobal = array_sum(array_column($rawVisitados, 'total_visitas'));

            foreach ($rawVisitados as $pv) {
                $del = $pv['nombre_delegacion'];

                if (!isset($puntosVisitadosAgrupados[$del])) {
                    $puntosVisitadosAgrupados[$del] = [];
                }

                $puntosVisitadosAgrupados[$del][] = [
                    'punto' => $pv['nombre_punto'],
                    'tipo'  => $pv['nombre_tipo_maquina'] ?? 'S/D',
                    'total' => $pv['total_visitas']
                ];
            }
        }

        // 1. Obtener datos crudos
        $rawCalificaciones = $this->modelo->getCalificacionesServicio($inicio, $fin);

        // 2. Agrupar por Delegación
        $calificacionesAgrupadas = [];

        if ($rawCalificaciones) {
            foreach ($rawCalificaciones as $c) {
                $del = $c['nombre_delegacion'];

                if (!isset($calificacionesAgrupadas[$del])) {
                    $calificacionesAgrupadas[$del] = [
                        'total_zona' => 0,
                        'items' => []
                    ];
                }

                $calificacionesAgrupadas[$del]['items'][] = [
                    'nombre' => $c['nombre_calificacion'],
                    'total'  => $c['total']
                ];

                // Sumamos al total de la zona para calcular % después
                $calificacionesAgrupadas[$del]['total_zona'] += $c['total'];
            }
        }



        // ---------------------------------------------
        // 4. OTROS DATOS (Matriz Mantenimiento, KPIs, etc)
        // ---------------------------------------------

        // 1. Obtenemos datos básicos
        $datosTecnicoDetallado = $this->modelo->getProductividadDetallada($inicio, $fin);
        $topTecnicos = array_slice($datosTecnicoDetallado, 0, 15); // Top 15

        // 2. Obtenemos las columnas (Tipos de Mantenimiento)
        $todosTiposMant = $this->modelo->getAllTiposMantenimiento();

        // 3. Obtenemos el desglose (Quién hizo qué)
        $rawDesglose = $this->modelo->getDesgloseMantenimientoPorTecnico($inicio, $fin);

        // 4. Mapeamos el desglose para acceso rápido: $mapa['NombreTecnico']['TipoMant'] = Cantidad
        $mapaDesglose = [];
        foreach ($rawDesglose as $d) {
            $mapaDesglose[$d['nombre_tecnico']][$d['tipo']] = $d['cantidad'];
        }

        // 5. Inyectamos los datos en $topTecnicos para que la vista lo tenga fácil
        // & (ampersand) es importante para modificar el array original
        foreach ($topTecnicos as &$t) {
            $t['desglose'] = $mapaDesglose[$t['nombre_tecnico']] ?? [];
        }
        unset($t); // Romper referencia
        $datosDelegacion = $this->modelo->getDelegacionesIntervenidas($inicio, $fin);
        $datosCalificaciones = $this->modelo->getCalificacionesServicio($inicio, $fin);
        $datosNovedad = $this->modelo->getDistribucionNovedades($inicio, $fin);


        // Matriz Mantenimiento (La que ya tenías)
        $todosTiposMant = $this->modelo->getAllTiposMantenimiento();
        // REPUESTOS POR ORIGEN
        $datosOrigenRepuestos = $this->modelo->getOrigenRepuestos($inicio, $fin);

        // ---------------------------------------------
        // ESTADOS FINALES (OPERATIVIDAD)
        // ---------------------------------------------
        $datosEstadosFinales = $this->modelo->getDistribucionEstados($inicio, $fin);
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

        // Reusamos $tiposMaquinaCols del paso anterior porque las columnas son iguales
        $datosRawPuntosTipo = $this->modelo->getDatosMatrizPuntosPorTipo($inicio, $fin);

        $matrizPuntosTipo = [];
        // Usamos la misma lista de delegaciones o creamos una nueva si quieres ser estricto
        // Reusaremos $delegacionesListaMaquina para que las tablas se vean alineadas

        if ($datosRawPuntosTipo) {
            foreach ($datosRawPuntosTipo as $row) {
                $matrizPuntosTipo[$row['nombre_delegacion']][$row['nombre_tipo_maquina']] = $row['total'];
            }
        }


        // 1. Llamamos a la función corregida del modelo
        // Ya el filtro de "> 2" está en el SQL, así que aquí llega limpio
        $rawFallidos = $this->modelo->getPuntosConMasFallidos($inicio, $fin);

        // 2. Agrupamos por Delegación (Lógica estándar)
        $puntosFallidosPorDelegacion = [];

        if ($rawFallidos) {
            foreach ($rawFallidos as $pf) {
                $del = $pf['nombre_delegacion'];

                if (!isset($puntosFallidosPorDelegacion[$del])) {
                    $puntosFallidosPorDelegacion[$del] = [
                        'total_zona' => 0, // Suma total de fallos de la zona
                        'items' => []
                    ];
                }

                $puntosFallidosPorDelegacion[$del]['items'][] = [
                    'nombre' => $pf['nombre_punto'],
                    'cantidad' => $pf['total_fallidos']
                ];

                $puntosFallidosPorDelegacion[$del]['total_zona'] += $pf['total_fallidos'];
            }
            ksort($puntosFallidosPorDelegacion); // Ordenar delegaciones A-Z
        }





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
                    'num_tecnicos' => $kpi['total_tecnicos'],

                    'porcentaje' => ($totalGlobalServicios > 0) ? round(($kpi['total_servicios'] / $totalGlobalServicios) * 100, 1) : 0,
                    'novedades' => $kpi['total_novedades'],
                    'promedio_diario' => round($promedioDiario, 2) // Ahora sí dará un número real (ej. 4.5 servicios/día)
                ];
            }
        }
        // =========================================================
        // 5. CÁLCULOS FINANCIEROS - DESGLOSE POR TIPO
        // =========================================================

        // A. INGRESOS DESGLOSADOS: Llamamos a la nueva función
        // ---------------------------------------------------------
        $desgloseTipos = $this->modelo->getDesgloseIngresosPorTipo($inicio, $fin);
        $ingresoRepuestos = $this->modelo->getTotalIngresosRepuestos($inicio, $fin);

        // Extraemos los valores individuales (asumiendo IDs estándar)
        // Ajusta los IDs según tu base de datos
        $ingresoPreventivo = $desgloseTipos[1]['total'] ?? 0;  // ID 1
        $ingresoPreventivoProf = $desgloseTipos[2]['total'] ?? 0;  // ID 2
        $ingresoCorrectivo = $desgloseTipos[3]['total'] ?? 0;  // ID 3
        $ingresoFallido = $desgloseTipos[4]['total'] ?? 0;  // ID 4
        $ingresoGarantia = $desgloseTipos[5]['total'] ??0; // ID 5


        // NUEVO: Extraemos la cantidad de servicios
        $cantPrev  = $desgloseTipos[1]['cantidad'] ?? 0;
        $cantProf  = $desgloseTipos[2]['cantidad'] ?? 0;
        $cantCorr  = $desgloseTipos[3]['cantidad'] ?? 0;
        $cantFall  = $desgloseTipos[4]['cantidad'] ?? 0;
        $cantGaran = $desgloseTipos[5]['cantidad'] ?? 0;
        

        // Calculamos el total de servicios 
        $ingresoServicios = $ingresoPreventivo + $ingresoPreventivoProf +
            $ingresoCorrectivo + $ingresoFallido + $ingresoGarantia;

        // B. EGRESOS: (Tu código existente)
        // ---------------------------------------------------------
        $listaCostos = $this->modelo->getListadoCostosOperativos($inicio, $fin);

        $listaMotorizados = [];
        $listaNominaAdmin = [];
        $totalMotorizados = 0;
        $totalNominaAdmin = 0;

        if (!empty($listaCostos)) {
            foreach ($listaCostos as $c) {
                $valor = isset($c['subtotal']) ? (float)$c['subtotal'] : 0;

                if (strpos($c['rol'], 'Técnico') !== false) {
                    $listaMotorizados[] = $c;
                    $totalMotorizados += $valor;
                } else {
                    $listaNominaAdmin[] = $c;
                    $totalNominaAdmin += $valor;
                }
            }
        }

        $listaGastosGenerales = $this->modelo->getListadoGastosGenerales($inicio, $fin);
        $totalGastosGral = 0;

        if (!empty($listaGastosGenerales)) {
            foreach ($listaGastosGenerales as $g) {
                $totalGastosGral += isset($g['valor']) ? (float)$g['valor'] : 0;
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


        // 2. Preparar el HTML del Footer para Browsershot
        // NOTA: Chrome inyecta clases especiales: date, title, url, pageNumber, totalPages
        // Usamos estilos inline porque este fragmento no lee el CSS del body

        $fechaInicio = date('d/m/Y', strtotime($inicio));
        $fechaFin = date('d/m/Y', strtotime($fin));

        $footerHtml = '
        <div style="width: 100%; font-size: 9px; padding-left: 10px; padding-right: 10px; padding-bottom: 5px; font-family: sans-serif; color: #64748b; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            
            <div style="width: 33%; text-transform: uppercase; letter-spacing: 2px; font-weight: bold; color: #94a3b8;">
                Documento Confidencial
            </div>
            
            <div style="width: 33%; text-align: center; font-weight: bold;">
                ' . $fechaInicio . ' - ' . $fechaFin . '
            </div>
            
            <div style="width: 33%; text-align: right;">
                <span style="background-color: #f8fafc; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 4px; font-weight: bold; color: #475569;">
                    Página <span class="pageNumber"></span>
                </span>
            </div>
        </div>';

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
                // IMPORTANTE: Ajustamos márgenes. El inferior debe ser mayor para que quepa el footer
                ->margins(10, 10, 15, 10)
                ->scale(0.8)
                ->timeout(120)

                // ACTIVAMOS HEADER Y FOOTER
                ->showBrowserHeaderAndFooter()
                ->headerHtml('<div></div>') // Header vacío para no descuadrar
                ->footerHtml($footerHtml);  // Aquí pasamos nuestro footer dinámico

            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $pdfContent = $browsershot->pdf();

            // -----------------------------------------------------
            // CAMBIO: Generar nombre dinámico con fechas
            // -----------------------------------------------------
            // Formateamos a d-m-Y para evitar las barras '/' que rompen el nombre de archivo
            $fInicioNombre = date('d-m-Y', strtotime($inicio));
            $fFinNombre = date('d-m-Y', strtotime($fin));

            // Creamos el nombre: Reporte_Ejecutivo_01-01-2026_al_31-01-2026.pdf
            $nombreArchivo = "Reporte_Ejecutivo_{$fInicioNombre}_al_{$fFinNombre}.pdf";

            header('Content-Type: application/pdf');

            // Aquí inyectamos la variable $nombreArchivo
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');

            header('Content-Length: ' . strlen($pdfContent));
            echo $pdfContent;
            exit;
        } catch (Exception $e) {
            // En caso de error, mostramos una página simple de error
            echo "<h1>Error generando PDF</h1><p>" . $e->getMessage() . "</p>";
            die();
        }
    }

    private function traducirDia($diaIngles)
    {
        $dias = ['Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb', 'Sun' => 'Dom'];
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
