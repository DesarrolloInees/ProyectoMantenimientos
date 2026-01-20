<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/exportar/exportarExcelModelo.php';

class exportarExcelControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new exportarExcelModelo($this->db);
    }

    // Cargar Vista
    public function cargarVista()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        $titulo = "Exportación de Datos";
        $vistaContenido = "app/views/exportar/exportarExcelVista.php";
        require_once __DIR__ . '/../../views/plantillaVista.php';
    }

    // ==========================================
    // CAMBIO: AHORA DEVUELVE JSON PARA GENERAR EL EXCEL EN EL NAVEGADOR
    // ==========================================
    public function descargarReporte()
    {
        // 1. Seguridad
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }

        // 2. Limpieza de buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 3. Obtener datos
        $datos = $this->modelo->obtenerDatosExportacion();

        // 4. Devolver JSON limpio
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos);

        // 5. Matar proceso para que no se mezcle HTML
        die();
    }
}
