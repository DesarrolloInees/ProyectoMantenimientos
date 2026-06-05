<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/controlRemisionVerModelo.php';

class controlRemisionVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ControlRemisionVerModelo($this->db);
    }

    public function index()
    {
        // Ya no cargamos todos los registros, solo la estructura de la vista
        $titulo = "Control de Remisiones";
        $vistaContenido = "app/views/admin/controlRemisionVerVista.php";
        include "app/views/plantillaVista.php";
    }

    /**
     * Nuevo endpoint para DataTables (server-side)
     */
    public function obtenerDatosDatatable()
    {
        // Solo aceptamos peticiones AJAX
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            http_response_code(403);
            exit;
        }

        // Parámetros que envía DataTables
        $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $length = isset($_GET['length']) ? intval($_GET['length']) : 100; // valor por defecto
        $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : null;
        $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
        $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

        // Total de registros sin filtrar
        $totalRecords = $this->modelo->contarTotalRemisiones();
        
        // Total de registros filtrados (si hay búsqueda)
        $totalFiltrados = $this->modelo->contarTotalRemisiones($searchValue);
        
        // Obtener los datos de la página actual
        $data = $this->modelo->obtenerRemisionesParaDatatable($start, $length, $orderColumnIndex, $orderDir, $searchValue);
        
        // Formatear la salida para DataTables
        $response = [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalFiltrados,
            'data' => $data
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
?>