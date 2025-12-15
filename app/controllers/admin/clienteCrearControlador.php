<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/clienteCrearModelo.php';

class clienteCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ClienteCrearModelo($this->db);
    }

    public function index() {
        $errores = [];
        $datosPrevios = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $datosPrevios = [
                'nombre_cliente' => trim($_POST['nombre_cliente'] ?? ''),
                'codigo_cliente' => trim($_POST['codigo_cliente'] ?? '')
            ];

            // Validaciones
            if (empty($datosPrevios['nombre_cliente'])) {
                $errores[] = "El nombre del cliente es obligatorio.";
            }

            // Si tienes validación de código obligatorio, descomenta esto:
            // if (empty($datosPrevios['codigo_cliente'])) { $errores[] = "El código es obligatorio."; }

            if (empty($errores)) {
                if ($this->modelo->crearCliente($datosPrevios)) {
                    header("Location: " . BASE_URL . "clienteVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar. Verifica que el Código del Cliente no esté repetido.";
                }
            }
        }

        $titulo = "Registrar Cliente";
        $vistaContenido = "app/views/admin/clienteCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}