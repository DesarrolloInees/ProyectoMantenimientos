<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tarifaCrearModelo.php';

class tarifaCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TarifaCrearModelo($this->db);
    }

    public function index() {
        $errores = [];
        $datosPrevios = [];

        // 1. Procesar Formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datosPrevios = [
                'id_tipo_maquina' => $_POST['id_tipo_maquina'] ?? '',
                'id_tipo_mantenimiento' => $_POST['id_tipo_mantenimiento'] ?? '',
                'id_modalidad' => $_POST['id_modalidad'] ?? '',
                'precio' => $_POST['precio'] ?? '',
                'año_vigencia' => $_POST['año_vigencia'] ?? date('Y')
            ];

            if (empty($datosPrevios['id_tipo_maquina'])) $errores[] = "Selecciona una Máquina.";
            if (empty($datosPrevios['id_tipo_mantenimiento'])) $errores[] = "Selecciona un Mantenimiento.";
            if (empty($datosPrevios['id_modalidad'])) $errores[] = "Selecciona una Modalidad.";
            if (empty($datosPrevios['precio'])) $errores[] = "El precio es obligatorio.";

            if (empty($errores)) {
                if ($this->modelo->crearTarifa($datosPrevios)) {
                    header("Location: " . BASE_URL . "tarifaVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar. Verifica que no exista una tarifa igual para este año.";
                }
            }
        }

        // 2. Cargar datos para los Selects
        $listaMaquinas = $this->modelo->obtenerTiposMaquina();
        $listaMantenimientos = $this->modelo->obtenerTiposMantenimiento();
        $listaModalidades = $this->modelo->obtenerModalidades();

        $titulo = "Crear Tarifa";
        $vistaContenido = "app/views/admin/tarifaCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}