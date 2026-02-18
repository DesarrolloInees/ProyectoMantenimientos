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

        // Datos para la vista
        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaZonas = [];
        $conteoZonas = [];
        $propuesta = [];

        $delegacionSeleccionada = '';

        if (!empty($_POST['final'])) {
            $resultado = $this->modelo->guardarProgramacionDefinitiva($_POST['final']);
            if ($resultado['status']) {
                $mensajeExito = "¡Programación creada exitosamente! Se generaron " . $resultado['count'] . " órdenes de servicio.";
            } else {
                $errores[] = "Error al guardar: " . $resultado['msg'];
            }
        } else {
            $errores[] = "No hay datos para guardar.";
        }

        $titulo = "Programación de Rutas Semanales";

        // --- CORRECCIÓN ---
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
}