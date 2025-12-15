<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoMaquinaEditarModelo.php';

class tipoMaquinaEditarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoMaquinaEditarModelo($this->db);
    }

    public function index() {
        // ID puede venir por GET (al entrar) o POST (al guardar)
        $id = $_GET['id'] ?? $_POST['id_tipo_maquina'] ?? null;

        if (!$id) { header("Location: " . BASE_URL . "tipoMaquinaVer"); exit(); }

        $errores = [];
        $datos = [];

        // PROCESAR POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre_tipo_maquina'] ?? '');
            $estado = $_POST['estado'] ?? '1';

            if (empty($nombre)) $errores[] = "El nombre es obligatorio.";

            if (empty($errores)) {
                if ($this->modelo->editarTipo($id, $nombre, $estado)) {
                    header("Location: " . BASE_URL . "tipoMaquinaVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar (Posible nombre duplicado).";
                }
            }
            // Mantenemos datos para la vista en caso de error
            $datos = ['nombre_tipo_maquina' => $nombre, 'estado' => $estado];
        }

        // CARGAR DATOS (Si no hay datos previos del POST)
        if (empty($datos)) {
            $datos = $this->modelo->obtenerTipoPorId($id);
            if (!$datos) { header("Location: " . BASE_URL . "tipoMaquinaVer"); exit(); }
        }

        $titulo = "Editar Tipo de Máquina";
        $vistaContenido = "app/views/admin/tipoMaquinaEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}