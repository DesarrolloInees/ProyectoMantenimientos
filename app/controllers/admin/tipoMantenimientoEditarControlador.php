<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoMantenimientoEditarModelo.php';

class tipoMantenimientoEditarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoMantenimientoEditarModelo($this->db);
    }

    public function index()
    {
        $id = $_GET['id'] ?? $_POST['id_tipo_mantenimiento'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "tipoMantenimientoVer");
            exit();
        }

        $errores = [];
        $datos = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre_completo'] ?? '');
            $estado = $_POST['estado'] ?? '1';

            if (empty($nombre)) $errores[] = "El nombre es obligatorio.";

            if (empty($errores)) {
                if ($this->modelo->editarTipo($id, $nombre, $estado)) {
                    header("Location: " . BASE_URL . "tipoMantenimientoVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar.";
                }
            }
            $datos = ['nombre_completo' => $nombre, 'estado' => $estado];
        }

        if (empty($datos)) {
            $datos = $this->modelo->obtenerTipoPorId($id);
            if (!$datos) {
                header("Location: " . BASE_URL . "tipoMantenimientoVer");
                exit();
            }
        }

        $titulo = "Editar Tipo Mantenimiento";
        $vistaContenido = "app/views/admin/tipoMantenimientoEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
