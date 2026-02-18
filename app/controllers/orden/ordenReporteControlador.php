<?php
// app/controllers/orden/ordenReporteControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenReporteModelo.php';

class ordenReporteControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ordenReporteModelo($this->db);
    }

    public function cargarVista()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $titulo = "Generador de Reportes";
        $vistaContenido = "app/views/orden/ordenReporteVista.php";
        require_once __DIR__ . '/../../views/plantillaVista.php';
    }

    // --- ACCIÓN 1: Servicios Generales ---
    public function ajaxDescargarServicios()
    {
        ob_clean();
        header('Content-Type: application/json');

        $inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fin    = $_POST['fecha_fin'] ?? date('Y-m-d');

        if ($inicio > $fin) {
            echo json_encode(['status' => 'error', 'msg' => 'Fechas incorrectas.']);
            exit;
        }

        try {
            $datos = $this->modelo->obtenerServiciosPorRango($inicio, $fin);
            echo json_encode(['status' => 'ok', 'datos' => $datos]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // --- ACCIÓN 2: Novedades Específicas ---
    public function ajaxDescargarNovedades()
    {
        ob_clean();
        header('Content-Type: application/json');

        $inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fin    = $_POST['fecha_fin'] ?? date('Y-m-d');

        if ($inicio > $fin) {
            echo json_encode(['status' => 'error', 'msg' => 'Fechas incorrectas.']);
            exit;
        }

        try {
            $datos = $this->modelo->obtenerNovedadesPorRango($inicio, $fin);
            echo json_encode(['status' => 'ok', 'datos' => $datos]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
