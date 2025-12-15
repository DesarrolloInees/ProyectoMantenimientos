<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
// Reutilizamos el modelo de Ver
require_once __DIR__ . '/../../models/admin/tipoMaquinaVerModelo.php';

class tipoMaquinaEliminarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoMaquinaVerModelo($this->db);
    }

    public function index() {
        $id = $_GET['id'] ?? null;

        if ($id && is_numeric($id)) {
            $this->modelo->eliminarTipoLogicamente($id);
        }

        header("Location: " . BASE_URL . "tipoMaquinaVer");
        exit();
    }
}