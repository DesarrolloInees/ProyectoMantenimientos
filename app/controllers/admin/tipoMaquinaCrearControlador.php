<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoMaquinaCrearModelo.php';

class tipoMaquinaCrearControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoMaquinaCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $nombrePrevio = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombrePrevio = trim($_POST['nombre_tipo_maquina'] ?? '');

            if (empty($nombrePrevio)) {
                $errores[] = "El nombre es obligatorio.";
            }

            if (empty($errores)) {
                if ($this->modelo->crearTipo($nombrePrevio)) {
                    header("Location: " . BASE_URL . "tipoMaquinaVer");
                    exit();
                } else {
                    $errores[] = "Error: Es posible que este Tipo de Máquina ya exista.";
                }
            }
        }

        $titulo = "Crear Tipo de Máquina";
        $vistaContenido = "app/views/admin/tipoMaquinaCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}
