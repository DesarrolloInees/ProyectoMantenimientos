<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoUsuarioVerModelo.php';

class tipoUsuarioEliminarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoUsuarioVerModelo($this->db);
    }

    public function index()
    {
        $id = $_GET['id'] ?? null;
        // Evitamos borrar el ID 1 (Administrador) por seguridad básica
        if ($id && is_numeric($id) && $id != 1) {
            $this->modelo->eliminarTipo($id);
        }
        header("Location: " . BASE_URL . "tipoUsuarioVer");
        exit();
    }
}
