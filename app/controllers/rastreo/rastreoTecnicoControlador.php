<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/rastreo/rastreoTecnicoModelo.php';

class rastreoTecnicoControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new rastreoTecnicoModelo($this->db);
    }

    public function index()
    {
        $titulo = "Rastreo de Técnicos";
        $tecnicos = $this->modelo->obtenerTecnicosActivos();
        $vistaContenido = "app/views/rastreo/rastreoTecnicoVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxObtenerRuta()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        // QUITAMOS EL (int) para que acepte 'todos'
        $idTecnico = isset($_POST['id_tecnico']) ? $_POST['id_tecnico'] : '';
        $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');

        if ($idTecnico === '') {
            echo json_encode(["success" => false, "msj" => "Seleccione un técnico o la opción 'Todos'."]);
            exit;
        }

        $ruta = $this->modelo->obtenerRutaTecnico($idTecnico, $fecha);

        if (count($ruta) > 0) {
            echo json_encode(["success" => true, "data" => $ruta]);
        } else {
            echo json_encode(["success" => false, "msj" => "No hay coordenadas registradas en la fecha seleccionada."]);
        }
        exit;
    }
}