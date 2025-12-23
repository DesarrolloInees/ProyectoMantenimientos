<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
// Requerimos el modelo de VER, porque ahí metimos la lógica de borrado lógico
require_once __DIR__ . '/../../models/admin/inventarioTecnicoVerModelo.php';

class InventarioTecnicoEliminarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new InventarioTecnicoVerModelo($this->db);
    }

    public function index()
    {
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $this->modelo->eliminarLogico($id);
        }

        // Redirigir al listado
        header("Location: " . BASE_URL . "inventarioTecnicoVer");
        exit();
    }
}
