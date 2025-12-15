<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/estadoMaquinaCrearModelo.php';

class estadoMaquinaCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new EstadoMaquinaCrearModelo($this->db);
    }

    public function index() {
        $errores = [];
        $nombrePrevio = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombrePrevio = trim($_POST['nombre_estado'] ?? '');

            if (empty($nombrePrevio)) {
                $errores[] = "El nombre del estado es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->crearEstado($nombrePrevio)) {
                    header("Location: " . BASE_URL . "estadoMaquinaVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar en base de datos.";
                }
            }
        }

        $titulo = "Crear Estado de Máquina";
        $vistaContenido = "app/views/admin/estadoMaquinaCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}