<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tecnicoEditarModelo.php';

class tecnicoEditarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TecnicoEditarModelo($this->db);
    }

    public function index()
    {
        $id = $_GET['id'] ?? $_POST['id_tecnico'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "tecnicoVer");
            exit();
        }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre_tecnico'] ?? '');
            $estado = $_POST['estado'] ?? '1';

            if (empty($nombre)) $errores[] = "El nombre es obligatorio.";

            if (empty($errores)) {
                if ($this->modelo->editarTecnico($id, $nombre, $estado)) {
                    header("Location: " . BASE_URL . "tecnicoVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar.";
                }
            }
            $datos = ['nombre_tecnico' => $nombre, 'estado' => $estado];
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerTecnicoPorId($id);
            if (!$datos) {
                header("Location: " . BASE_URL . "tecnicoVer");
                exit();
            }
        }

        $titulo = "Editar Técnico";
        $vistaContenido = "app/views/admin/tecnicoEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
