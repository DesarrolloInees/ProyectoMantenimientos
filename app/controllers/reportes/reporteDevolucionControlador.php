<?php
// app/controllers/reportes/reporteDevolucionControlador.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/reporteDevolucionModelo.php';

class reporteDevolucionControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteDevolucionModelo($this->db);
    }

    public function index()
    {
        $datosDevoluciones = [];
        $tecnicos = $this->modelo->obtenerTecnicos();
        
        $filtros = [
            'id_tecnico' => '',
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin' => date('Y-m-d')
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['id_tecnico'] = $_POST['id_tecnico'] ?? '';
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? '';
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? '';

            $datosDevoluciones = $this->modelo->obtenerRepuestosParaDevolver(
                $filtros['id_tecnico'],
                $filtros['fecha_inicio'],
                $filtros['fecha_fin']
            );
        }

        $titulo = "Control de Devolución de Repuestos";
        $vistaContenido = "app/views/reportes/reporteDevolucionVista.php";
        include "app/views/plantillaVista.php";
    }
}