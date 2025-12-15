<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoUsuarioEditarModelo.php';

class tipoUsuarioEditarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoUsuarioEditarModelo($this->db);
    }

    public function index() {
        $id = $_GET['id'] ?? $_POST['idTipoUsuario'] ?? null;
        if (!$id) { header("Location: " . BASE_URL . "tipoUsuarioVer"); exit(); }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombreTipoUsuario'] ?? '');

            if (empty($nombre)) $errores[] = "El nombre es obligatorio.";

            if (empty($errores)) {
                if ($this->modelo->editarTipo($id, $nombre)) {
                    header("Location: " . BASE_URL . "tipoUsuarioVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar.";
                }
            }
            $datos = ['nombreTipoUsuario' => $nombre];
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerTipoPorId($id);
            if (!$datos) { header("Location: " . BASE_URL . "tipoUsuarioVer"); exit(); }
        }

        $titulo = "Editar Rol";
        $vistaContenido = "app/views/admin/tipoUsuarioEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}