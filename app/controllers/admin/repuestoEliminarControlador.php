<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
// Reutilizamos el modelo de Ver porque ahí está la función de eliminar
require_once __DIR__ . '/../../models/admin/repuestoVerModelo.php';

class repuestoEliminarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new RepuestoVerModelo($this->db);
    }

    public function index()
    {
        // Obtenemos el ID de la URL (repuestoEliminar/10)
        $id = $_GET['id'] ?? null;

        if ($id && is_numeric($id)) {
            $this->modelo->eliminarRepuestoLogicamente($id);
        }

        // Redirigir siempre a la lista
        header("Location: " . BASE_URL . "repuestoVer");
        exit();
    }
}
