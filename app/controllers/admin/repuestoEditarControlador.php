<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/repuestoEditarModelo.php';

class repuestoEditarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new RepuestoEditarModelo($this->db);
    }

    // Método principal: Maneja la carga de la vista y el guardado (POST)
    public function index() {
        
        // 1. Obtener ID de la URL (repuestoEditar/5 -> $_GET['id'] = 5)
        // O si enviaste formulario, el ID viene por POST
        $id_repuesto = $_GET['id'] ?? $_POST['id_repuesto'] ?? null;

        if (!$id_repuesto) {
            header("Location: " . BASE_URL . "repuestoVer");
            exit();
        }

        $errores = [];
        $datosRepuesto = [];

        // ------------------------------------------------
        // A. PROCESAR GUARDADO (POST)
        // ------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $datosRepuesto = [
                'nombre_repuesto'   => trim($_POST['nombre_repuesto'] ?? ''),
                'codigo_referencia' => trim($_POST['codigo_referencia'] ?? ''),
                'estado'            => $_POST['estado'] ?? '1'
            ];

            if (empty($datosRepuesto['nombre_repuesto'])) {
                $errores[] = "El nombre es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->editarRepuesto($id_repuesto, $datosRepuesto)) {
                    header("Location: " . BASE_URL . "repuestoVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar en la BD.";
                }
            }
        } 
        
        // ------------------------------------------------
        // B. CARGAR DATOS SI NO ES POST (O SI HUBO ERROR)
        // ------------------------------------------------
        // Si no tenemos datos (porque es GET o primera carga), los pedimos a la BD
        if (empty($datosRepuesto)) {
            $datosRepuesto = $this->modelo->obtenerRepuestoPorId($id_repuesto);
            if (!$datosRepuesto) {
                // Si el ID no existe en BD
                header("Location: " . BASE_URL . "repuestoVer");
                exit();
            }
        }

        // ------------------------------------------------
        // C. MOSTRAR VISTA
        // ------------------------------------------------
        $titulo = "Editar Repuesto #" . $id_repuesto;
        $vistaContenido = "app/views/admin/repuestoEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
?>