<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/inventarioTecnicoEditarModelo.php';

class InventarioTecnicoEditarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new InventarioTecnicoEditarModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $datos = [];

        // 1. Cargar datos si viene ID por GET
        if (isset($_GET['id'])) {
            $datos = $this->modelo->obtenerPorId($_GET['id']);
            if (!$datos) {
                header("Location: " . BASE_URL . "inventarioTecnicoVer");
                exit();
            }
        }

        // 2. Procesar formulario POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id_inventario'];
            $cantidad = intval($_POST['cantidad']);

            // Validaciones
            if ($cantidad < 0) $errores[] = "La cantidad no puede ser negativa.";

            if (empty($errores)) {
                if ($this->modelo->actualizarCantidad($id, $cantidad)) {
                    header("Location: " . BASE_URL . "inventarioTecnicoVer");
                    exit();
                } else {
                    $errores[] = "Error al actualizar la base de datos.";
                }
            }
            // Recargar datos para mostrar el error en el formulario
            $datos = $this->modelo->obtenerPorId($id);
        }

        $titulo = "Ajustar Stock de Técnico";
        $vistaContenido = "app/views/admin/inventarioTecnicoEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
