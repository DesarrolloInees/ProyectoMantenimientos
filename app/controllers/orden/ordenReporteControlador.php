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

    // =========================================================
    // 1. CARGAR LA VISTA (GET)
    // Se ejecuta cuando entras a la página normal
    // =========================================================
    public function cargarVista()
    {
        // Seguridad
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $titulo = "Generador de Reportes";
        $vistaContenido = "app/views/orden/ordenReporteVista.php";
        
        require_once __DIR__ . '/../../views/plantillaVista.php';
    }

    // =========================================================
    // 2. ACCIÓN DEL BOTÓN (AJAX / POST)
    // Tu index.php busca esta función porque en JS enviamos "accion: ajaxDescargarReporte"
    // =========================================================
    public function ajaxDescargarReporte()
    {
        // Limpiamos cualquier salida previa para evitar errores en el JSON
        ob_clean();
        header('Content-Type: application/json');

        // Capturamos datos del POST
        $inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fin    = $_POST['fecha_fin'] ?? date('Y-m-d');

        // Validación básica
        if ($inicio > $fin) {
            echo json_encode(['status' => 'error', 'msg' => 'La fecha inicio no puede ser mayor a la fin.']);
            exit;
        }

        try {
            // Consulta al modelo
            $datos = $this->modelo->obtenerServiciosPorRango($inicio, $fin);
            
            // Respuesta exitosa
            echo json_encode(['status' => 'ok', 'datos' => $datos]);
        } catch (Exception $e) {
            // Error controlado
            echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit; // Importante: Matar el script aquí para que no se imprima nada más del HTML
    }
}