<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
// Reutilizamos usuarioVerModelo ya que ahí suele estar la función de borrar, 
// o puedes crear usuarioEliminarModelo si prefieres separar lógica.
require_once __DIR__ . '/../../models/usuario/usuarioVerModelo.php'; 

class usuarioEliminarControlador {

    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new UsuarioVerModelo($this->db);
    }

    public function index() {
        // El Router pasa el parámetro ID en $_GET['id']
        // URL: /usuarioEliminar/5  --> $_GET['id'] = 5
        $id_usuario = $_GET['id'] ?? null;

        if ($id_usuario && is_numeric($id_usuario)) {
            // Borrado lógico
            $this->modelo->eliminarUsuarioLogicamente($id_usuario);
        }

        // Redirección
        header("Location: " . BASE_URL . "usuarioVer");
        exit();
    }
}
?>