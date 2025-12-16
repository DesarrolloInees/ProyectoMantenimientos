<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/maquinaCrearModelo.php';

class maquinaCrearControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new MaquinaCrearModelo($this->db);
    }

    public function index() {
        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'device_id'       => trim($_POST['device_id'] ?? ''),
                'id_punto'        => $_POST['id_punto'] ?? '',
                'id_tipo_maquina' => $_POST['id_tipo_maquina'] ?? '',
                'ultima_visita'   => $_POST['ultima_visita'] ?? ''
            ];

            if (empty($datos['device_id'])) $errores[] = "El Device ID es obligatorio.";
            if (empty($datos['id_punto'])) $errores[] = "Debes seleccionar un Punto.";
            if (empty($datos['id_tipo_maquina'])) $errores[] = "Debes seleccionar un Tipo.";

            if (empty($errores)) {
                if ($this->modelo->crearMaquina($datos)) {
                    header("Location: " . BASE_URL . "maquinaVer");
                    exit();
                } else {
                    $errores[] = "Error: El Device ID ya existe o hubo un fallo en BD.";
                }
            }
        }

        $listaPuntos = $this->modelo->obtenerPuntos();
        $listaTipos = $this->modelo->obtenerTipos();

        $titulo = "Registrar Máquina";
        $vistaContenido = "app/views/admin/maquinaCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}