<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/reporteRepuestoModelo.php';

class reporteRepuestoControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteRepuestosModelo($this->db);
    }

    public function index()
    {
        $datosReporte = []; // Reporte Agrupado (Tabla visual)
        $datosInees = [];   // Reporte Detallado (Para el Excel nuevo)

        $filtros = [
            'origen' => '',
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin' => date('Y-m-d')
        ];
        $totalPiezas = 0;
        $mensaje = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['origen'] = $_POST['origen'] ?? '';
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? '';
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? '';

            if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {

                // 1. Obtener Datos Agrupados (Visualización HTML)
                $datosReporte = $this->modelo->generarReporteRepuestos(
                    $filtros['origen'],
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                );

                // 2. Obtener Datos Detallados INEES (Para el Excel Nuevo)
                $datosInees = $this->modelo->obtenerDetalleInees(
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                );

                // Calcular total visual
                foreach ($datosReporte as $row) {
                    $totalPiezas += $row['total_cantidad'];
                }

                if (empty($datosReporte)) {
                    $mensaje = "No se encontraron repuestos usados en ese rango de fechas.";
                }
            } else {
                $mensaje = "Por favor selecciona el rango de fechas.";
            }
        }

        $titulo = "Reporte de Uso de Repuestos";
        $vistaContenido = "app/views/reportes/reporteRepuestoVista.php";
        include "app/views/plantillaVista.php";
    }
}
