<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoUsuarioCrearModelo.php';

class tipoUsuarioCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoUsuarioCrearModelo($this->db);
    }

    public function index() {
        $errores = [];
        $nombrePrevio = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombrePrevio = trim($_POST['nombreTipoUsuario'] ?? '');

            if (empty($nombrePrevio)) {
                $errores[] = "El nombre del rol es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->crearTipo($nombrePrevio)) {
                    header("Location: " . BASE_URL . "tipoUsuarioVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar (posible duplicado).";
                }
            }
        }

        $titulo = "Crear Rol de Usuario";
        $vistaContenido = "app/views/admin/tipoUsuarioCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}