<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/delegacionEditarModelo.php';

class delegacionEditarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new DelegacionEditarModelo($this->db);
    }

    public function index()
    {
        $id = $_GET['id'] ?? $_POST['id_delegacion'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "delegacionVer");
            exit();
        }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre_delegacion'] ?? '');
            $estado = $_POST['estado'] ?? '1';

            if (empty($nombre)) $errores[] = "El nombre es obligatorio.";

            if (empty($errores)) {
                if ($this->modelo->editarDelegacion($id, $nombre, $estado)) {
                    header("Location: " . BASE_URL . "delegacionVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar (nombre duplicado?).";
                }
            }
            $datos = ['nombre_delegacion' => $nombre, 'estado' => $estado];
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerDelegacionPorId($id);
            if (!$datos) {
                header("Location: " . BASE_URL . "delegacionVer");
                exit();
            }
        }

        $titulo = "Editar Delegación";
        $vistaContenido = "app/views/admin/delegacionEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
