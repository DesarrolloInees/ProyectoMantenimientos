<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/tecnico/tecnicoProgramacionModelo.php';

class tecnicoProgramacionControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new tecnicoProgramacionModelo($this->db);
    }

    public function index()
    {
        $titulo = "Mi Programación Diaria";
        $vistaContenido = "app/views/tecnico/tecnicoProgramacionVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxObtenerProgramacion()
    {
        // Limpiamos cualquier output buffer previo para no contaminar el JSON
        while (ob_get_level()) ob_end_clean();
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            // La sesión ya está iniciada por index.php, no hace falta session_start() aquí.
            // ─────────────────────────────────────────────────────────────
            // OBTENER ID DEL USUARIO LOGUEADO DESDE LA SESIÓN
            // Confirmado en index.php: la clave es $_SESSION['usuario_id']
            // ─────────────────────────────────────────────────────────────
            $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

            if ($idUsuarioLogueado === 0) {
                echo json_encode([
                    "data"  => [],
                    "error" => "No hay sesión de usuario activa."
                ]);
                ob_end_flush();
                exit;
            }

            // Fecha enviada desde el frontend (por defecto hoy)
            $fecha = isset($_POST['fecha']) && !empty($_POST['fecha'])
                ? $_POST['fecha']
                : date('Y-m-d');

            // Validar formato de fecha para evitar inyecciones
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                echo json_encode(["data" => [], "error" => "Formato de fecha inválido."]);
                ob_end_flush();
                exit;
            }

            // Consultamos el modelo
            $datos = $this->modelo->obtenerServiciosProgramadosTecnico($idUsuarioLogueado, $fecha);

            echo json_encode([
                "data"        => $datos,
                "debug_id"    => $idUsuarioLogueado,
                "debug_fecha" => $fecha
            ]);
        } catch (Exception $e) {
            error_log("[tecnicoProgramacion] Error en ajaxObtenerProgramacion: " . $e->getMessage());
            echo json_encode(["data" => [], "error" => "Error interno del servidor."]);
        }

        ob_end_flush();
        exit;
    }
    public function ajaxEliminarServicio()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        $idOrden = isset($_POST['id_orden']) ? (int) $_POST['id_orden'] : 0;

        if ($idUsuarioLogueado === 0 || $idOrden === 0) {
            echo json_encode(["success" => false, "msj" => "Datos inválidos o sesión expirada."]);
            exit;
        }

        $eliminado = $this->modelo->eliminarOrdenServicio($idOrden, $idUsuarioLogueado);

        if ($eliminado) {
            echo json_encode(["success" => true, "msj" => "Servicio eliminado correctamente."]);
        } else {
            echo json_encode(["success" => false, "msj" => "No se pudo eliminar. Verifique el estado del servicio."]);
        }
        exit;
    }


    // ── ENDPOINTS PARA SELECTS EN CASCADA ──

    public function ajaxObtenerClientes()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($this->modelo->obtenerClientesActivos());
        exit;
    }

    public function ajaxObtenerPuntos()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
        echo json_encode($this->modelo->obtenerPuntosPorCliente($id));
        exit;
    }

    public function ajaxObtenerMaquinas()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = isset($_POST['id_punto']) ? (int)$_POST['id_punto'] : 0;
        echo json_encode($this->modelo->obtenerMaquinasPorPunto($id));
        exit;
    }

    // ── ENDPOINT PARA INICIAR SERVICIO (GPS) ──
    public function ajaxIniciarServicio()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $idUsuario = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
        $idOrden = isset($_POST['id_orden']) ? (int)$_POST['id_orden'] : 0;
        $lat = isset($_POST['latitud_inicio']) ? $_POST['latitud_inicio'] : null;
        $lon = isset($_POST['longitud_inicio']) ? $_POST['longitud_inicio'] : null;

        if ($idUsuario === 0 || $idOrden === 0 || !$lat || !$lon) {
            echo json_encode(["success" => false, "msj" => "Faltan permisos de GPS o la sesión expiró."]);
            exit;
        }

        $guardado = $this->modelo->iniciarServicioGPS($idOrden, $lat, $lon);

        if ($guardado) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "msj" => "Error al guardar ubicación de inicio en la base de datos."]);
        }
        exit;
    }

    public function ajaxObtenerTiposMantenimiento()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($this->modelo->obtenerTiposMantenimiento());
        exit;
    }

    // ── ENDPOINT PARA GUARDAR ──

    public function ajaxGuardarExtra()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $idUsuario = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;

        if ($idUsuario === 0 || empty($_POST['id_cliente']) || empty($_POST['id_punto']) || empty($_POST['id_maquina']) || empty($_POST['id_tipo_mantenimiento']) || empty($_POST['fecha_visita'])) {
            echo json_encode(["success" => false, "msj" => "Faltan datos o sesión expirada."]);
            exit;
        }

        $datos = [
            'id_cliente' => (int)$_POST['id_cliente'],
            'id_punto' => (int)$_POST['id_punto'],
            'id_maquina' => (int)$_POST['id_maquina'],
            'id_tipo_mantenimiento' => (int)$_POST['id_tipo_mantenimiento'],
            'fecha_visita' => $_POST['fecha_visita']
        ];

        $guardado = $this->modelo->guardarServicioExtra($idUsuario, $datos);

        if ($guardado) {
            echo json_encode(["success" => true, "msj" => "Servicio extra agendado con éxito."]);
        } else {
            echo json_encode(["success" => false, "msj" => "Error al guardar en la base de datos."]);
        }
        exit;
    }



}
