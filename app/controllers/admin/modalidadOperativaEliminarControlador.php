<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/modalidadOperativaVerModelo.php';

class modalidadOperativaEliminarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ModalidadOperativaVerModelo($this->db);
    }

    public function index()
    {
        $id = $_GET['id'] ?? null;
        if ($id && is_numeric($id)) {
            $this->modelo->eliminarModalidad($id);
        }
        header("Location: " . BASE_URL . "modalidadOperativaVer");
        exit();
    }
}
