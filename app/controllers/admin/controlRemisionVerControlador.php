<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

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

    public function obtenerDatosDatatable()
    {
        try {
            // Solo aceptamos peticiones AJAX
            if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['error' => 'Acceso denegado']);
                exit;
            }

            // Parámetros que envía DataTables
            $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 100;

            // IMPORTANTE: Verificar si search existe y tiene valor
            $searchValue = null;
            if (isset($_GET['search']['value']) && trim($_GET['search']['value']) !== '') {
                $searchValue = trim($_GET['search']['value']);
            }

            $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
            $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

            // Total de registros sin filtrar
            $totalRecords = $this->modelo->contarTotalRemisiones();

            // Total de registros filtrados (solo si hay búsqueda)
            $totalFiltrados = $this->modelo->contarTotalRemisiones($searchValue);

            // Obtener los datos de la página actual
            $data = $this->modelo->obtenerRemisionesParaDatatable($start, $length, $orderColumnIndex, $orderDir, $searchValue);

            // Asegurar que $data sea un array
            if (!is_array($data)) {
                $data = [];
            }

            // Formatear la salida para DataTables
            $response = [
                'draw' => intval($draw),
                'recordsTotal' => intval($totalRecords),
                'recordsFiltered' => intval($totalFiltrados),
                'data' => $data
            ];

            // Limpiar cualquier salida previa
            if (ob_get_level())
                ob_clean();

            header('Content-Type: application/json');
            echo json_encode($response);
            exit;

        } catch (Exception $e) {
            error_log("Error en obtenerDatosDatatable: " . $e->getMessage());

            // Si hay error, devolver JSON con error
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'data' => []
            ]);
            exit;
        }
    }

    public function testDatatable()
    {
        header('Content-Type: application/json');

        try {
            $data = $this->modelo->obtenerRemisionesParaDatatable(0, 10, 0, 'desc', null);

            echo json_encode([
                'status' => 'ok',
                'data' => $data,
                'total' => $this->modelo->contarTotalRemisiones()
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
?>