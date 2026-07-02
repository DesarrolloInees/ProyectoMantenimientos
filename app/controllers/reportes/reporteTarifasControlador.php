<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/reporteTarifasModelo.php';

class reporteTarifasControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new reporteTarifasModelo($this->db);
    }

    public function cargarVista()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $titulo = "Reporte de Tarifas Mensuales";
        $vistaContenido = "app/views/reportes/reporteTarifasVista.php";

        require_once __DIR__ . '/../../views/plantillaVista.php';
    }

    public function descargarReportePorcentajes()
    {
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $fechaInicio = isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
        $fechaFin = isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');

        $datos = $this->modelo->obtenerDatosPorcentajesMantenimiento($fechaInicio, $fechaFin);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos);

        die();
    }
}