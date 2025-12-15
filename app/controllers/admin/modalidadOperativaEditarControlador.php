<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/modalidadOperativaEditarModelo.php';

class modalidadOperativaEditarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ModalidadOperativaEditarModelo($this->db);
    }

    public function index() {
        $id = $_GET['id'] ?? $_POST['id_modalidad'] ?? null;
        if (!$id) { header("Location: " . BASE_URL . "modalidadOperativaVer"); exit(); }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre_modalidad'] ?? '');

            if (empty($nombre)) $errores[] = "El nombre es obligatorio.";

            if (empty($errores)) {
                if ($this->modelo->editarModalidad($id, $nombre)) {
                    header("Location: " . BASE_URL . "modalidadOperativaVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar.";
                }
            }
            $datos = ['nombre_modalidad' => $nombre];
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerModalidadPorId($id);
            if (!$datos) { header("Location: " . BASE_URL . "modalidadOperativaVer"); exit(); }
        }

        $titulo = "Editar Modalidad";
        $vistaContenido = "app/views/admin/modalidadOperativaEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}