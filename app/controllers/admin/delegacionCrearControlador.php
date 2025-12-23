<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/delegacionCrearModelo.php';

class delegacionCrearControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new DelegacionCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $nombrePrevio = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombrePrevio = trim($_POST['nombre_delegacion'] ?? '');

            if (empty($nombrePrevio)) {
                $errores[] = "El nombre de la delegación es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->crearDelegacion($nombrePrevio)) {
                    header("Location: " . BASE_URL . "delegacionVer");
                    exit();
                } else {
                    $errores[] = "Error: Es posible que esta Delegación ya exista.";
                }
            }
        }

        $titulo = "Crear Delegación";
        $vistaContenido = "app/views/admin/delegacionCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}
