<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoNovedadCrearModelo.php';

class TipoNovedadCrearControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoNovedadCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $nombrePrevio = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombrePrevio = trim($_POST['nombre_novedad'] ?? '');

            if (empty($nombrePrevio)) {
                $errores[] = "El nombre de la novedad es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->crearTipoNovedad($nombrePrevio)) {
                    // Asumo que la ruta para ver es "tipoNovedadVer"
                    header("Location: " . BASE_URL . "tipoNovedadVer");
                    exit();
                } else {
                    $errores[] = "Error: Es posible que este Tipo de Novedad ya exista.";
                }
            }
        }

        $titulo = "Crear Tipo de Novedad";
        $vistaContenido = "app/views/admin/tipoNovedadCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}