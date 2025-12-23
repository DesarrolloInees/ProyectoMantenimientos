<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/modalidadOperativaCrearModelo.php';

class modalidadOperativaCrearControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ModalidadOperativaCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $nombrePrevio = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombrePrevio = trim($_POST['nombre_modalidad'] ?? '');

            if (empty($nombrePrevio)) {
                $errores[] = "El nombre de la modalidad es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->crearModalidad($nombrePrevio)) {
                    header("Location: " . BASE_URL . "modalidadOperativaVer");
                    exit();
                } else {
                    $errores[] = "Error al guardar en base de datos.";
                }
            }
        }

        $titulo = "Crear Modalidad Operativa";
        $vistaContenido = "app/views/admin/modalidadOperativaCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}
