<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/diasFestivosEditarModelo.php';

class DiasFestivosEditarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new DiasFestivosEditarModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $datos = [];

        // Cargar datos GET
        if (isset($_GET['id'])) {
            $datos = $this->modelo->obtenerPorId($_GET['id']);
            if (!$datos) {
                header("Location: " . BASE_URL . "diasFestivosVer");
                exit();
            }
        }

        // Procesar POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id_festivo'];
            $fecha = $_POST['fecha'];
            $descripcion = trim($_POST['descripcion']);

            // Actualizar arreglo para la vista si falla
            $datos = ['id_festivo' => $id, 'fecha' => $fecha, 'descripcion' => $descripcion];

            if (empty($fecha)) $errores[] = "La fecha es obligatoria.";

            if (empty($errores)) {
                $res = $this->modelo->actualizarFestivo($id, $fecha, $descripcion);
                if ($res === true) {
                    header("Location: " . BASE_URL . "diasFestivosVer");
                    exit();
                } elseif ($res === "DUPLICADO") {
                    $errores[] = "Ya existe otro festivo con esa fecha.";
                } else {
                    $errores[] = "Error al actualizar.";
                }
            }
        }

        $titulo = "Editar Festivo";
        $vistaContenido = "app/views/admin/diasFestivosEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
