<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/planeacion/planeacionModelo.php';

class planeacionVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new planeacionModelo($this->db);
    }

    /**
     * Pantalla principal: dibuja el Gantt con todas las estrategias
     * y sus tareas/subtareas ya armadas en árbol.
     */
    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        $estados  = $this->modelo->obtenerEstados();
        $usuarios = $this->modelo->obtenerUsuariosActivos();
        $estrategias = $this->modelo->obtenerEstrategiasActivas();

        // Adjuntamos el árbol de tareas a cada estrategia
        foreach ($estrategias as &$estrategia) {
            $estrategia['tareas'] = $this->modelo->obtenerArbolTareas($estrategia['id_estrategia']);
        }
        unset($estrategia);

        $titulo = "Planeación Estratégica";
        $vistaContenido = "app/views/planeacion/planeacionVista.php";
        include "app/views/plantillaVista.php";
    }

    /**
     * Devuelve el JSON que consume frappe-gantt en el frontend.
     * Llamado vía $.getJSON desde la vista.
     */
    public function datosGantt()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        $estrategias = $this->modelo->obtenerEstrategiasActivas();
        $filasGantt = [];

        foreach ($estrategias as $estrategia) {
            $idGanttEstrategia = 'estrategia_' . $estrategia['id_estrategia'];

            $filasGantt[] = [
                'id'          => $idGanttEstrategia,
                'tipo'        => 'estrategia',
                'name'        => $estrategia['nombre_estrategia'],
                'start'       => $estrategia['fecha_inicial'],
                'end'         => $estrategia['fecha_final'],
                'progress'    => (int) $estrategia['avance_pct'],
                'estado'      => $estrategia['nombre_estado'] ?? 'Sin estado',
                'responsable' => $estrategia['nombre_responsable'] ?? 'Sin asignar',
            ];

            $tareas = $this->modelo->obtenerArbolTareas($estrategia['id_estrategia']);

            foreach ($tareas as $tarea) {
                $idPadre = $tarea['id_tarea_padre']
                    ? 'tarea_' . $tarea['id_tarea_padre']
                    : $idGanttEstrategia;

                $filasGantt[] = [
                    'id'           => 'tarea_' . $tarea['id_tarea'],
                    'tipo'         => 'tarea',
                    'name'         => $tarea['nombre_tarea'],
                    'start'        => $tarea['fecha_inicial'],
                    'end'          => $tarea['fecha_final'],
                    'progress'     => (int) $tarea['avance_pct'],
                    'dependencies' => $idPadre,
                    'estado'       => $tarea['nombre_estado'] ?? 'Sin estado',
                    'responsable'  => $tarea['nombre_responsable'] ?? 'Sin asignar',
                ];
            }
        }

        echo json_encode($filasGantt, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Crea una estrategia nueva desde el modal del frontend.
     */
    public function crearEstrategia()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }

        $nombre        = trim($_POST['nombre_estrategia'] ?? '');
        $idResponsable = !empty($_POST['id_responsable']) ? (int) $_POST['id_responsable'] : null;
        $idEstado      = !empty($_POST['id_estado']) ? (int) $_POST['id_estado'] : null;
        $fechaInicial  = $_POST['fecha_inicial'] ?? '';
        $fechaFinal    = $_POST['fecha_final'] ?? '';

        if ($nombre === '' || !$idEstado || !$fechaInicial || !$fechaFinal) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios.']);
            exit;
        }

        $idNuevo = $this->modelo->crearEstrategia([
            'nombre_estrategia' => $nombre,
            'id_responsable'    => $idResponsable,
            'id_estado'         => $idEstado,
            'fecha_inicial'     => $fechaInicial,
            'fecha_final'       => $fechaFinal,
        ]);

        if ($idNuevo) {
            echo json_encode(['status' => 'success', 'id_estrategia' => $idNuevo]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo crear la estrategia.']);
        }
        exit;
    }

    /**
     * Crea una tarea o subtarea (si viene id_tarea_padre) desde el frontend.
     */
    public function crearTarea()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }

        $idEstrategia  = !empty($_POST['id_estrategia']) ? (int) $_POST['id_estrategia'] : 0;
        $idTareaPadre  = !empty($_POST['id_tarea_padre']) ? (int) $_POST['id_tarea_padre'] : null;
        $nombreTarea   = trim($_POST['nombre_tarea'] ?? '');
        $idResponsable = !empty($_POST['id_responsable']) ? (int) $_POST['id_responsable'] : null;
        $idEstado      = !empty($_POST['id_estado']) ? (int) $_POST['id_estado'] : null;
        $fechaInicial  = $_POST['fecha_inicial'] ?? '';
        $fechaFinal    = $_POST['fecha_final'] ?? '';

        if (!$idEstrategia || $nombreTarea === '' || !$idEstado || !$fechaInicial || !$fechaFinal) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios.']);
            exit;
        }

        $idNuevo = $this->modelo->crearTarea([
            'id_estrategia'  => $idEstrategia,
            'id_tarea_padre' => $idTareaPadre,
            'nombre_tarea'   => $nombreTarea,
            'id_responsable' => $idResponsable,
            'id_estado'      => $idEstado,
            'fecha_inicial'  => $fechaInicial,
            'fecha_final'    => $fechaFinal,
        ]);

        if ($idNuevo) {
            echo json_encode(['status' => 'success', 'id_tarea' => $idNuevo]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo crear la tarea.']);
        }
        exit;
    }

    /**
     * Actualiza fechas cuando el usuario arrastra/redimensiona una barra del Gantt.
     */
    public function actualizarFechas()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        $tipo         = $_POST['tipo'] ?? '';
        $id           = !empty($_POST['id']) ? (int) $_POST['id'] : 0;
        $fechaInicial = $_POST['fecha_inicial'] ?? '';
        $fechaFinal   = $_POST['fecha_final'] ?? '';

        if (!$id || !$fechaInicial || !$fechaFinal || !in_array($tipo, ['tarea', 'estrategia'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
            exit;
        }

        $ok = $this->modelo->actualizarFechas($tipo, $id, $fechaInicial, $fechaFinal);
        echo json_encode(['status' => $ok ? 'success' : 'error']);
        exit;
    }

    /**
     * Actualiza el % de avance al mover el slider de progreso de una barra.
     */
    public function actualizarAvance()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        $tipo      = $_POST['tipo'] ?? '';
        $id        = !empty($_POST['id']) ? (int) $_POST['id'] : 0;
        $avancePct = isset($_POST['avance_pct']) ? max(0, min(100, (int) $_POST['avance_pct'])) : 0;

        if (!$id || !in_array($tipo, ['tarea', 'estrategia'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
            exit;
        }

        $ok = $this->modelo->actualizarAvance($tipo, $id, $avancePct);
        echo json_encode(['status' => $ok ? 'success' : 'error']);
        exit;
    }

    /**
     * Cambia el estado (Activo/Aplazado/Cancelado/Finalizado) de una tarea o estrategia,
     * por ejemplo desde un select dentro del modal de detalle.
     */
    public function actualizarEstado()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        $tipo     = $_POST['tipo'] ?? '';
        $id       = !empty($_POST['id']) ? (int) $_POST['id'] : 0;
        $idEstado = !empty($_POST['id_estado']) ? (int) $_POST['id_estado'] : 0;

        if (!$id || !$idEstado || !in_array($tipo, ['tarea', 'estrategia'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
            exit;
        }

        $ok = $this->modelo->actualizarEstado($tipo, $id, $idEstado);
        echo json_encode(['status' => $ok ? 'success' : 'error']);
        exit;
    }
}