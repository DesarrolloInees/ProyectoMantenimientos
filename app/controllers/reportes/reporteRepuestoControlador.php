<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/reporteRepuestoModelo.php';

class reporteRepuestoControlador {
    
    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteRepuestosModelo($this->db);
    }

    public function index() {
        $datosReporte = [];
        $filtros = [
            'origen' => '', // Filtro opcional por INEES o PROSEGUR
            'fecha_inicio' => date('Y-m-01'), // Por defecto 1ro del mes
            'fecha_fin' => date('Y-m-d')      // Por defecto hoy
        ];
        $totalPiezas = 0;
        $mensaje = "";

        // Si se envió el formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['origen'] = $_POST['origen'] ?? '';
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? '';
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? '';

            if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
                $datosReporte = $this->modelo->generarReporteRepuesto(
                    $filtros['origen'], 
                    $filtros['fecha_inicio'], 
                    $filtros['fecha_fin']
                );
                
                // Calcular total de piezas usadas
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