<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/calificacionServicioCrearModelo.php';

class calificacionServicioCrearControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new CalificacionServicioCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $nombrePrevio = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombrePrevio = trim($_POST['nombre_calificacion'] ?? '');

            if (empty($nombrePrevio)) {
                $errores[] = "El nombre de la calificación es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->crearCalificacion($nombrePrevio)) {
                    header("Location: " . BASE_URL . "calificacionServicioVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar en base de datos.";
                }
            }
        }

        $titulo = "Crear Calificación";
        $vistaContenido = "app/views/admin/calificacionServicioCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}
