<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoMantenimientoCrearModelo.php';

class tipoMantenimientoCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoMantenimientoCrearModelo($this->db);
    }

    public function index() {
        $errores = [];
        $nombrePrevio = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombrePrevio = trim($_POST['nombre_completo'] ?? '');

            if (empty($nombrePrevio)) {
                $errores[] = "El nombre del mantenimiento es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->crearTipo($nombrePrevio)) {
                    header("Location: " . BASE_URL . "tipoMantenimientoVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar en la base de datos.";
                }
            }
        }

        $titulo = "Crear Tipo Mantenimiento";
        $vistaContenido = "app/views/admin/tipoMantenimientoCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}