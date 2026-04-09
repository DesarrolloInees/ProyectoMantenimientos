<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/reporteHistorialModelo.php';

class reporteHistorialControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        // CÓDIGO CORRECTO:
        $this->modelo = new reporteHistorialModelo($this->db);
    }

    public function index()
    {
        $datosReporte = []; 
        $datosExcel = [];   

        $filtros = [
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin' => date('Y-m-d')
        ];
        
        $mensaje = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? '';
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? '';

            if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {

                // Traemos los datos para la vista y el Excel
                $datosReporte = $this->modelo->obtenerHistorialMantenimientos(
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                );
                
                $datosExcel = $datosReporte; // En este caso usamos los mismos datos

                if (empty($datosReporte)) {
                    $mensaje = "No se encontraron mantenimientos en ese rango de fechas.";
                }
            } else {
                $mensaje = "Por favor selecciona el rango de fechas.";
            }
        }

        $titulo = "Historial de Mantenimientos por Punto";
        $vistaContenido = "app/views/reportes/reporteHistorialVista.php";
        
        include "app/views/plantillaVista.php";
    }
}