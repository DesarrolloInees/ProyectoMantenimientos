<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
// Importamos el modelo de visualización que ya contiene la función de eliminar
require_once __DIR__ . '/../../models/admin/clienteVerModelo.php';

class clienteEliminarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ClienteVerModelo($this->db);
    }

    public function index() {
        // Obtenemos ID de la URL (Ej: clienteEliminar/8)
        $id = $_GET['id'] ?? null;

        if ($id && is_numeric($id)) {
            // Realizamos borrado lógico (UPDATE estado = 0)
            $this->modelo->eliminarClienteLogicamente($id);
        }

        // Siempre redirigimos a la tabla
        header("Location: " . BASE_URL . "clienteVer");
        exit();
    }
}
?>