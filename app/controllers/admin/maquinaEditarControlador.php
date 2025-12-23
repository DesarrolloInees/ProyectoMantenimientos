<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/maquinaEditarModelo.php';

class maquinaEditarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new MaquinaEditarModelo($this->db);
    }

    public function index()
    {
        $id = $_GET['id'] ?? $_POST['id_maquina'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "maquinaVer");
            exit();
        }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'device_id' => trim($_POST['device_id']),
                'id_punto' => $_POST['id_punto'],
                'id_tipo_maquina' => $_POST['id_tipo_maquina'],
                'ultima_visita' => $_POST['ultima_visita'],
                'estado' => $_POST['estado']
            ];

            if ($this->modelo->editarMaquina($id, $datos)) {
                header("Location: " . BASE_URL . "maquinaVer");
                exit();
            } else {
                $errores[] = "Error al actualizar (¿Device ID duplicado?).";
            }
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerMaquinaPorId($id);
            if (!$datos) {
                header("Location: " . BASE_URL . "maquinaVer");
                exit();
            }
        }

        $listaPuntos = $this->modelo->obtenerPuntos();
        $listaTipos = $this->modelo->obtenerTipos();

        $titulo = "Editar Máquina";
        $vistaContenido = "app/views/admin/maquinaEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
