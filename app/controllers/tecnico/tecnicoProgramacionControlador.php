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
}
?>