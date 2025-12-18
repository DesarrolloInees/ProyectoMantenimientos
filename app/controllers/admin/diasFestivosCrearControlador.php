<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/diasFestivosCrearModelo.php';

class DiasFestivosCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new DiasFestivosCrearModelo($this->db);
    }

    public function index() {
        $errores = [];
        $fecha = "";
        $descripcion = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fecha = $_POST['fecha'] ?? '';
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (empty($fecha)) {
                $errores[] = "La fecha es obligatoria.";
            }

            if (empty($errores)) {
                $resultado = $this->modelo->crearFestivo($fecha, $descripcion);
                
                if ($resultado === true) {
                    header("Location: " . BASE_URL . "diasFestivosVer");
                    exit();
                } elseif ($resultado === "DUPLICADO") {
                    $errores[] = "Ya existe un festivo registrado con esa fecha.";
                } else {
                    $errores[] = "Error al guardar en base de datos.";
                }
            }
        }

        $titulo = "Crear Día Festivo";
        $vistaContenido = "app/views/admin/diasFestivosCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}