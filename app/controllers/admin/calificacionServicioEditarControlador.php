<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/calificacionServicioEditarModelo.php';

class calificacionServicioEditarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new CalificacionServicioEditarModelo($this->db);
    }

    public function index()
    {
        $id = $_GET['id'] ?? $_POST['id_calificacion'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "calificacionServicioVer");
            exit();
        }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre_calificacion'] ?? '');

            if (empty($nombre)) $errores[] = "El nombre es obligatorio.";

            if (empty($errores)) {
                if ($this->modelo->editarCalificacion($id, $nombre)) {
                    header("Location: " . BASE_URL . "calificacionServicioVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar.";
                }
            }
            $datos = ['nombre_calificacion' => $nombre];
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerCalificacionPorId($id);
            if (!$datos) {
                header("Location: " . BASE_URL . "calificacionServicioVer");
                exit();
            }
        }

        $titulo = "Editar Calificación";
        $vistaContenido = "app/views/admin/calificacionServicioEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
