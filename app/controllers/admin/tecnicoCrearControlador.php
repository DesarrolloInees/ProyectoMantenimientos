<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tecnicoCrearModelo.php';

class tecnicoCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TecnicoCrearModelo($this->db);
    }

    public function index() {
        $errores = [];
        $nombrePrevio = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombrePrevio = trim($_POST['nombre_tecnico'] ?? '');

            if (empty($nombrePrevio)) {
                $errores[] = "El nombre del técnico es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->crearTecnico($nombrePrevio)) {
                    header("Location: " . BASE_URL . "tecnicoVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar en base de datos.";
                }
            }
        }

        $titulo = "Crear Técnico";
        $vistaContenido = "app/views/admin/tecnicoCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}