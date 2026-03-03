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
        $vistaContenido = "app/views/tecnico/programacionTecnicoVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxObtenerProgramacion()
    {
        while (ob_get_level()) ob_end_clean();
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 1. Aseguramos que la sesión esté iniciada para leer al usuario
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // 2. Tomamos la fecha del frontend
            $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');
            
            // 3. 🔥 EL TRUCO ESTÁ AQUÍ 🔥
            // Tomamos el ID del USUARIO LOGUEADO directamente de la sesión (Seguridad máxima)
            // Ajusta "id_usuario" según cómo se llame tu variable de sesión al hacer login
            $idUsuarioLogueado = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 0; 
            
            // Alternativa: Si guardas un array en sesión, podría ser $_SESSION['usuario']['id']
            // $idUsuarioLogueado = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;

            if ($idUsuarioLogueado === 0) {
                // Si no hay sesión, devolvemos vacío por seguridad
                echo json_encode(["data" => [], "error" => "No hay sesión de usuario activa"]);
                exit;
            }

            // 4. Consultamos el modelo pasándole el ID del Usuario
            $datos = $this->modelo->obtenerServiciosProgramadosTecnico($idUsuarioLogueado, $fecha);

            echo json_encode(["data" => $datos]);
        } catch (Exception $e) {
            echo json_encode(["data" => [], "error" => $e->getMessage()]);
        }

        ob_end_flush();
        exit;
    }
}
?>