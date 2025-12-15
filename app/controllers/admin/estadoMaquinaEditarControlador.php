<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/estadoMaquinaEditarModelo.php';

class estadoMaquinaEditarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new EstadoMaquinaEditarModelo($this->db);
    }

    public function index() {
        $id = $_GET['id'] ?? $_POST['id_estado'] ?? null;
        if (!$id) { header("Location: " . BASE_URL . "estadoMaquinaVer"); exit(); }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre_estado'] ?? '');

            if (empty($nombre)) $errores[] = "El nombre es obligatorio.";

            if (empty($errores)) {
                if ($this->modelo->editarEstado($id, $nombre)) {
                    header("Location: " . BASE_URL . "estadoMaquinaVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar.";
                }
            }
            $datos = ['nombre_estado' => $nombre];
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerEstadoPorId($id);
            if (!$datos) { header("Location: " . BASE_URL . "estadoMaquinaVer"); exit(); }
        }

        $titulo = "Editar Estado";
        $vistaContenido = "app/views/admin/estadoMaquinaEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}