<?php
// app/controllers/programacion/programacionCrearControlador.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/programacion/programacionCrearModelo.php';

class programacionCrearControlador
{
    private $modelo;

    public function __construct()
    {
        $db = (new Conexion())->getConexion();
        $this->modelo = new programacionCrearModelo($db);
    }

    /**
     * VISTA PRINCIPAL - Configuración de rutas semanales
     */
    public function index()
    {
        $errores = [];
        $mensajeExito = "";

        // Datos para la vista
        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaZonas = [];
        $listaClientes = [];
        $conteoZonas = [];
        $propuesta = [];
        $listaMaquinasInactivas = $this->modelo->obtenerMaquinasFueraDeServicio();

        // Filtros
        $delegacionSeleccionada = $_REQUEST['delegacion'] ?? '';
        $clientesSeleccionados = $_REQUEST['clientes'] ?? [];

        // Cargar clientes y zonas si hay delegación
        if (!empty($delegacionSeleccionada)) {
            $listaClientes = $this->modelo->obtenerClientesPorDelegacion($delegacionSeleccionada);
            $listaZonas = $this->modelo->obtenerZonasPorDelegacion($delegacionSeleccionada);
            
            // Si no hay clientes seleccionados, seleccionar todos por defecto
            if (empty($clientesSeleccionados)) {
                $clientesSeleccionados = array_column($listaClientes, 'id_cliente');
            }
            
            // Contar puntos con el filtro de clientes
            $conteoZonas = $this->modelo->contarPuntosPorZona($delegacionSeleccionada, $clientesSeleccionados);
        }

        $titulo = "Programación de Rutas Semanales";
        
        // --- ERROR ANTERIOR ---
        // require_once "app/views/programacion/programacionCrearVista.php"; // <--- ESTO IMPRIME ANTES DE TIEMPO
        // include "app/views/plantillaVista.php";

        // --- CORRECCIÓN ---
        // Definimos la ruta de la vista interna
        $vistaContenido = "app/views/programacion/programacionCrearVista.php";
        
        // Cargamos SOLO la plantilla (ella se encarga de incluir $vistaContenido)
        include "app/views/plantillaVista.php";
    }

    /**
     * PREVISUALIZAR - Generar calendario semanal
     */
    public function previsualizar()
    {
        $errores = [];
        $mensajeExito = "";

        // Datos para la vista
        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaZonas = [];
        $listaClientes = [];
        $conteoZonas = [];
        $propuesta = [];
        $listaMaquinasInactivas = $this->modelo->obtenerMaquinasFueraDeServicio();

        $delegacionSeleccionada = $_POST['delegacion'] ?? '';
        $clientesSeleccionados = $_POST['clientes'] ?? [];

        // Validaciones
        if (empty($delegacionSeleccionada)) {
            $errores[] = "Debe seleccionar una delegación.";
        }
        if (empty($clientesSeleccionados)) {
            $errores[] = "Debe seleccionar al menos un cliente para programar.";
        }
        if (empty($_POST['fecha_inicio'])) {
            $errores[] = "Debe ingresar la fecha de inicio.";
        }
        if (empty($_POST['semanas']) || $_POST['semanas'] < 1) {
            $errores[] = "Debe ingresar el número de semanas a programar.";
        }

        // Validar que al menos un día tenga configuración
        $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        $hayConfiguracion = false;
        
        foreach ($diasSemana as $dia) {
            if (!empty($_POST['tecnico_' . $dia]) && !empty($_POST['zonas_' . $dia])) {
                $hayConfiguracion = true;
                break;
            }
        }

        if (!$hayConfiguracion) {
            $errores[] = "Debe configurar al menos un día de la semana con técnico y zonas.";
        }

        if (empty($errores)) {
            // Construir calendario
            $calendario = [];
            
            foreach ($diasSemana as $dia) {
                $tecnico = $_POST['tecnico_' . $dia] ?? null;
                $zonas = $_POST['zonas_' . $dia] ?? [];
                
                if (!empty($tecnico) && !empty($zonas)) {
                    $calendario[$dia] = [
                        'id_tecnico' => $tecnico,
                        'zonas' => $zonas
                    ];
                }
            }

            // Configuración completa CON CLIENTES
            $configuracion = [
                'id_delegacion' => $delegacionSeleccionada,
                'clientes_ids' => $clientesSeleccionados,  // ✅ FILTRO DE CLIENTES
                'calendario' => $calendario,
                'fecha_inicio' => $_POST['fecha_inicio'],
                'semanas' => intval($_POST['semanas']),
                'max_servicios_dia' => intval($_POST['max_servicios'] ?? 5),
                'incluir_sabado_fallidos' => isset($_POST['sabado_fallidos'])
            ];

            // Generar propuesta
            $propuesta = $this->modelo->generarProgramacionSemanal($configuracion);

            // Agregar puntos extra seleccionados (de maquinas fuera de servicio)
            $puntosExtra = $_POST['puntos_aledanios_extra'] ?? [];
            if (!empty($puntosExtra)) {
                $puntosExtra = array_unique(array_map('intval', $puntosExtra));
                $infoExtra = $this->modelo->obtenerInfoPuntos($puntosExtra);

                // Asignar el primer dia configurado en el calendario
                $primerDia = array_key_first($calendario);
                $primerTecnico = $calendario[$primerDia]['id_tecnico'] ?? null;
                $fechaPrimerDia = !empty($configuracion['fecha_inicio']) ? date('Y-m-d', strtotime($configuracion['fecha_inicio'])) : date('Y-m-d');

                foreach ($puntosExtra as $idPunto) {
                    // Evitar duplicados en la propuesta
                    $yaExiste = false;
                    foreach ($propuesta as $item) {
                        if ($item['id_punto'] == $idPunto) {
                            $yaExiste = true;
                            break;
                        }
                    }
                    if ($yaExiste) continue;

                    $info = $infoExtra[$idPunto] ?? null;
                    if ($info) {
                        $propuesta[] = [
                            'id_punto' => $idPunto,
                            'id_tecnico' => $primerTecnico,
                            'fecha_visita' => $fechaPrimerDia,
                            'zona' => $info['zona'] ?? '',
                            'nombre_punto' => $info['nombre_punto'] ?? '',
                            'nombre_cliente' => $info['nombre_cliente'] ?? '',
                            'es_sabado_fallido' => false,
                            'es_fuera_servicio' => true
                        ];
                    }
                }
            }

            if (empty($propuesta)) {
                $errores[] = "No se pudo generar la programación. Verifique que haya puntos pendientes en las zonas y clientes seleccionados.";
            }
        }

        // Recargar datos para mantener la vista
        if (!empty($delegacionSeleccionada)) {
            $listaClientes = $this->modelo->obtenerClientesPorDelegacion($delegacionSeleccionada);
            $listaZonas = $this->modelo->obtenerZonasPorDelegacion($delegacionSeleccionada);
            
            // Si no hay clientes seleccionados, seleccionar todos
            if (empty($clientesSeleccionados)) {
                $clientesSeleccionados = array_column($listaClientes, 'id_cliente');
            }
            
            $conteoZonas = $this->modelo->contarPuntosPorZona($delegacionSeleccionada, $clientesSeleccionados);
        }

        $titulo = "Programación de Rutas Semanales";

        // --- CORRECCIÓN ---
        $vistaContenido = "app/views/programacion/programacionCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    /**
     * GUARDAR - Aprobar y crear órdenes
     */
    public function guardar_definitivo()
    {
        $errores = [];
        $mensajeExito = "";
        $datosParaExcel = null; 
        $maquinasRestauradas = 0;

        // Datos para la vista
        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaZonas = [];
        $conteoZonas = [];
        $propuesta = [];
        $listaMaquinasInactivas = $this->modelo->obtenerMaquinasFueraDeServicio();

        $delegacionSeleccionada = '';

        if (!empty($_POST['final'])) {
            $resultado = $this->modelo->guardarProgramacionDefinitiva($_POST['final']);
            if ($resultado['status']) {
                // Recopilar los IDs de puntos que SI se programaron
                $puntosProgramados = [];
                foreach ($_POST['final'] as $servicio) {
                    if (!empty($servicio['id_punto'])) {
                        $puntosProgramados[] = intval($servicio['id_punto']);
                    }
                }

                // Restaurar maquinas fuera de servicio que NO se programaron
                $restaurar = $this->modelo->restaurarOperativoNoProgramados($puntosProgramados);
                if ($restaurar['status']) {
                    $maquinasRestauradas = $restaurar['restauradas'];
                }

                $mensajeExito = "Programacion creada exitosamente! Se generaron " . $resultado['count'] . " ordenes de servicio.";
                if ($maquinasRestauradas > 0) {
                    $mensajeExito .= " {$maquinasRestauradas} maquina(s) fuera de servicio sin programar fueron restauradas a Operativo automaticamente.";
                }
                
                $datosParaExcel = $this->modelo->obtenerDatosProgramacionExcel($_POST['final']);
            } else {
                $errores[] = "Error al guardar: " . $resultado['msg'];
            }
        } else {
            $errores[] = "No hay datos para guardar.";
        }

        $titulo = "Programacion de Rutas Semanales";

        $vistaContenido = "app/views/programacion/programacionCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    /**
     * AJAX - Obtener puntos de una zona específica
     */
    public function obtener_puntos_zona()
    {
        header('Content-Type: application/json');
        
        $delegacion = $_GET['delegacion'] ?? '';
        $zona = $_GET['zona'] ?? '';
        
        if (empty($delegacion) || empty($zona)) {
            echo json_encode(['error' => 'Parámetros incompletos']);
            exit;
        }
        
        $puntos = $this->modelo->obtenerPuntosPorZona($delegacion, $zona);
        echo json_encode(['puntos' => $puntos]);
        exit;
    }

    /**
     * AJAX - Obtener puntos aledaños de una zona específica
     */
    public function puntos_aledanios()
    {
        header('Content-Type: application/json');
        
        $id_punto = intval($_GET['id_punto'] ?? 0);
        $zona = $_GET['zona'] ?? '';
        $id_delegacion = $_GET['delegacion'] ?? null;
        
        if (empty($id_punto) || empty($zona)) {
            echo json_encode(['error' => 'Parámetros incompletos']);
            exit;
        }
        
        $puntos = $this->modelo->obtenerPuntosAledanios($id_punto, $zona, $id_delegacion);
        echo json_encode(['puntos' => $puntos]);
        exit;
    }

    /**
     * AJAX - Restaurar maquinas a operativo para puntos no programados
     */
    public function restaurarOperativo()
    {
        header('Content-Type: application/json');
        
        $puntosSeleccionados = isset($_POST['puntos_seleccionados']) ? json_decode($_POST['puntos_seleccionados'], true) : [];
        
        $resultado = $this->modelo->restaurarOperativoNoProgramados($puntosSeleccionados);
        echo json_encode($resultado);
        exit;
    }

    /**
     * AJAX - Restaurar una maquina individual a operativo por device_id
     */
    public function restaurarMaquinaIndividual()
    {
        header('Content-Type: application/json');
        
        $deviceId = $_POST['device_id'] ?? '';
        
        if (empty($deviceId)) {
            echo json_encode(['status' => false, 'msg' => 'Device ID vacio']);
            exit;
        }
        
        $ok = $this->modelo->restaurarMaquinaOperativo($deviceId);
        echo json_encode(['status' => $ok, 'msg' => $ok ? 'Maquina restaurada a Operativo' : 'No se pudo restaurar']);
        exit;
    }
}