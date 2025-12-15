<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tarifaEditarModelo.php';

class tarifaEditarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TarifaEditarModelo($this->db);
    }

    public function index() {
        $id = $_GET['id'] ?? $_POST['id_tarifa'] ?? null;
        if (!$id) { header("Location: " . BASE_URL . "tarifaVer"); exit(); }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'id_tipo_maquina' => $_POST['id_tipo_maquina'],
                'id_tipo_mantenimiento' => $_POST['id_tipo_mantenimiento'],
                'id_modalidad' => $_POST['id_modalidad'],
                'precio' => $_POST['precio'],
                'año_vigencia' => $_POST['año_vigencia']
            ];

            if ($this->modelo->editarTarifa($id, $datos)) {
                header("Location: " . BASE_URL . "tarifaVer");
                exit();
            } else {
                $errores[] = "Error al actualizar la tarifa.";
            }
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerTarifaPorId($id);
            if (!$datos) { header("Location: " . BASE_URL . "tarifaVer"); exit(); }
        }

        // Listas para los dropdowns
        $listaMaquinas = $this->modelo->obtenerTiposMaquina();
        $listaMantenimientos = $this->modelo->obtenerTiposMantenimiento();
        $listaModalidades = $this->modelo->obtenerModalidades();

        $titulo = "Editar Tarifa";
        $vistaContenido = "app/views/admin/tarifaEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}