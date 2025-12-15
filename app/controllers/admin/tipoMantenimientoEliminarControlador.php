<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoMantenimientoVerModelo.php';

class tipoMantenimientoEliminarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoMantenimientoVerModelo($this->db);
    }

    public function index() {
        $id = $_GET['id'] ?? null;
        if ($id && is_numeric($id)) {
            $this->modelo->eliminarTipoLogicamente($id);
        }
        header("Location: " . BASE_URL . "tipoMantenimientoVer");
        exit();
    }
}