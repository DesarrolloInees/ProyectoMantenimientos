<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/clienteEditarModelo.php';

class clienteEditarControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ClienteEditarModelo($this->db);
    }

    public function index() {
        // 1. Obtener ID (puede venir por GET al abrir o POST al guardar)
        $id_cliente = $_GET['id'] ?? $_POST['id_cliente'] ?? null;

        if (!$id_cliente) {
            header("Location: " . BASE_URL . "clienteVer");
            exit();
        }

        $errores = [];
        $datosCliente = [];

        // ------------------------------------------------
        // A. PROCESAR GUARDADO (POST)
        // ------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $datosCliente = [
                'nombre_cliente' => trim($_POST['nombre_cliente'] ?? ''),
                'codigo_cliente' => trim($_POST['codigo_cliente'] ?? ''),
                'estado'         => $_POST['estado'] ?? '1'
            ];

            if (empty($datosCliente['nombre_cliente'])) {
                $errores[] = "El nombre es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->editarCliente($id_cliente, $datosCliente)) {
                    // Éxito: Volvemos a la lista
                    header("Location: " . BASE_URL . "clienteVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar. Verifica que el código no pertenezca a otro cliente.";
                }
            }
        }

        // ------------------------------------------------
        // B. CARGAR DATOS (GET)
        // ------------------------------------------------
        if (empty($datosCliente)) {
            $datosCliente = $this->modelo->obtenerClientePorId($id_cliente);
            if (!$datosCliente) {
                header("Location: " . BASE_URL . "clienteVer");
                exit();
            }
        }

        // ------------------------------------------------
        // C. VISTA
        // ------------------------------------------------
        $titulo = "Editar Cliente";
        $vistaContenido = "app/views/admin/clienteEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
?>