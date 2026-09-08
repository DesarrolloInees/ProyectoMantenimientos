<?php
// app/controllers/programacion/programacionCrearControlador.php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");
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
     * VISTA PRINCIPAL - Gestión de Contingencias / Maquinas Fuera de Servicio
     */
    public function index()
    {
        $errores = [];
        $mensajeExito = isset($_GET['exito']) ? urldecode($_GET['exito']) : "";

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

            if (empty($clientesSeleccionados)) {
                $clientesSeleccionados = array_column($listaClientes, 'id_cliente');
            }

            $conteoZonas = $this->modelo->contarPuntosPorZona($delegacionSeleccionada, $clientesSeleccionados);
        }

        $titulo = "Programación de Rutas Diarias - Contingencias";
        $vistaContenido = "app/views/programacion/programacionCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    /**
     * PREVISUALIZAR DIARIO - Recibe asignaciones directas de fecha y técnico desde el modal
     */
    public function previsualizar_diario()
    {
        $errores = [];
        $mensajeExito = "";

        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaMaquinasInactivas = $this->modelo->obtenerMaquinasFueraDeServicio();

        $serviciosDiarios = $_POST['servicios_diarios'] ?? [];
        $propuesta = [];

        if (!empty($serviciosDiarios)) {
            $idsPuntos = array_column($serviciosDiarios, 'id_punto');
            $infoPuntos = $this->modelo->obtenerInfoPuntos($idsPuntos);

            foreach ($serviciosDiarios as $item) {
                $idPunto = $item['id_punto'];
                $info = $infoPuntos[$idPunto] ?? null;

                if ($info) {
                    $propuesta[] = [
                        'id_punto' => $idPunto,
                        'id_tecnico' => $item['id_tecnico'],
                        'fecha_visita' => $item['fecha_visita'],
                        'zona' => $info['zona'] ?? '',
                        'nombre_punto' => $info['nombre_punto'] ?? '',
                        'nombre_cliente' => $info['nombre_cliente'] ?? '',
                        'es_fuera_servicio' => true
                    ];
                }
            }
        } else {
            $errores[] = "No se recibieron puntos para programar desde el modal.";
        }

        $titulo = "Previsualización de Programación Diaria";
        $vistaContenido = "app/views/programacion/programacionCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    /**
     * PREVISUALIZAR SEMANAL - Mantenido para retrocompatibilidad
     */
    public function previsualizar()
    {
        $errores = [];
        $mensajeExito = "";

        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaZonas = [];
        $listaClientes = [];
        $conteoZonas = [];
        $propuesta = [];
        $listaMaquinasInactivas = $this->modelo->obtenerMaquinasFueraDeServicio();

        $delegacionSeleccionada = $_POST['delegacion'] ?? '';
        $clientesSeleccionados = $_POST['clientes'] ?? [];

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

            $configuracion = [
                'id_delegacion' => $delegacionSeleccionada,
                'clientes_ids' => $clientesSeleccionados,
                'calendario' => $calendario,
                'fecha_inicio' => $_POST['fecha_inicio'],
                'semanas' => intval($_POST['semanas']),
                'max_servicios_dia' => intval($_POST['max_servicios'] ?? 5),
                'incluir_sabado_fallidos' => isset($_POST['sabado_fallidos'])
            ];

            $propuesta = $this->modelo->generarProgramacionSemanal($configuracion);

            $puntosExtra = $_POST['puntos_aledanios_extra'] ?? [];
            if (!empty($puntosExtra)) {
                $puntosExtra = array_unique(array_map('intval', $puntosExtra));
                $infoExtra = $this->modelo->obtenerInfoPuntos($puntosExtra);

                $primerDia = array_key_first($calendario);
                $primerTecnico = $calendario[$primerDia]['id_tecnico'] ?? null;
                $fechaPrimerDia = !empty($configuracion['fecha_inicio']) ? date('Y-m-d', strtotime($configuracion['fecha_inicio'])) : date('Y-m-d');

                foreach ($puntosExtra as $idPunto) {
                    $yaExiste = false;
                    foreach ($propuesta as $item) {
                        if ($item['id_punto'] == $idPunto) {
                            $yaExiste = true;
                            break;
                        }
                    }
                    if ($yaExiste)
                        continue;

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

        if (!empty($delegacionSeleccionada)) {
            $listaClientes = $this->modelo->obtenerClientesPorDelegacion($delegacionSeleccionada);
            $listaZonas = $this->modelo->obtenerZonasPorDelegacion($delegacionSeleccionada);

            if (empty($clientesSeleccionados)) {
                $clientesSeleccionados = array_column($listaClientes, 'id_cliente');
            }

            $conteoZonas = $this->modelo->contarPuntosPorZona($delegacionSeleccionada, $clientesSeleccionados);
        }

        $titulo = "Programación de Rutas Semanales";
        $vistaContenido = "app/views/programacion/programacionCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    /**
     * GUARDAR - Aprobar y crear órdenes de servicio
     */
    public function guardar_definitivo()
    {
        $errores = [];

        if (!empty($_POST['final'])) {
            $resultado = $this->modelo->guardarProgramacionDefinitiva($_POST['final']);

            if ($resultado['status']) {
                // SE RETIRA restaurarOperativoNoProgramados() de esta fase.
                // Las máquinas fuera de servicio se mantendrán inactivas hasta 
                // la descarga del Consolidado en Excel.

                $msg = urlencode("¡Programación guardada exitosamente! Se crearon " . $resultado['count'] . " órdenes.");
                header("Location: index.php?pagina=programacionCrear&exito=" . $msg);
                exit;
            } else {
                $errores[] = "Error al guardar: " . $resultado['msg'];
                $this->index();
            }
        } else {
            header("Location: index.php?pagina=programacionCrear");
            exit;
        }
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
     * AJAX - Obtener puntos aledaños de una o varias zonas
     */
    public function puntos_aledanios()
    {
        header('Content-Type: application/json');

        $id_punto = isset($_GET['id_punto']) ? intval($_GET['id_punto']) : 0;
        $zonaRaw = trim($_GET['zona'] ?? '');
        $id_delegacion = $_GET['delegacion'] ?? null;

        if (empty($zonaRaw)) {
            echo json_encode(['error' => 'No se especificó ninguna zona.']);
            exit;
        }

        $puntos = $this->modelo->obtenerPuntosAledanios($id_punto, $zonaRaw, $id_delegacion);
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