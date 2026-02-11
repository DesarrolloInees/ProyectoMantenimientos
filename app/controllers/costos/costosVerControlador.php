<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/costos/costosVerModelo.php';

class costosVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new CostosVerModelo($this->db);
    }

    // PANTALLA 1: Lista de Meses (Los grupitos)
    public function index()
    {
        $listaMeses = $this->modelo->obtenerMesesAgrupados();

        $titulo = "Resumen de Costos";
        $vistaContenido = "app/views/costos/costosVerVista.php"; // Ojo al nombre nuevo
        include "app/views/plantillaVista.php";
    }

    // PANTALLA 2: Detalle de un mes específico (Cuando das click en Ver)
    // Se llamaría algo así: midominio.com/costosVerDetalle/2023-10
    public function detalle($fechaMes = null)
    {
        if (!$fechaMes) {
            header('Location: ' . BASE_URL . 'costosEditar'); // Si no hay fecha, regresar
            exit;
        }

        $detalles = $this->modelo->obtenerDetallePorMes($fechaMes);

        $data = [
            'mes' => $fechaMes,
            'detalless' => $detalles
        ];

        $titulo = "Detalle Costos " . $fechaMes;
        $vistaContenido = "app/views/costos/costosEditarVista.php"; 
        include "app/views/plantillaVista.php";
    }
}