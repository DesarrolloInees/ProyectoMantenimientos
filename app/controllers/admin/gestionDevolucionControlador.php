<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/gestionDevolucionModelo.php'; // <-- Ajusta esta ruta según tu estructura

class GestionDevolucionControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new GestionDevolucionModelo($this->db);
    }

    // Carga la vista principal
    public function index()
    {
        $tecnicos = $this->modelo->obtenerTecnicos();
        
        // Si vienen filtros por POST (para recargar la página con un técnico específico)
        $filtroTecnico = $_POST['id_tecnico'] ?? '';

        $datosPendientes = $this->modelo->obtenerRepuestosPendientes($filtroTecnico);

        $titulo = "Recepción de Devoluciones";
        $vistaContenido = "app/views/admin/gestionDevolucionVista.php"; // <-- Ajusta ruta
        include "app/views/plantillaVista.php";
    }

    // Recibe el JSON por AJAX y marca como entregados
    public function ajaxProcesarDevolucion()
    {
        ob_clean(); // Limpiar basura HTML
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Recibimos un Array JSON con los IDs
            $itemsJSON = $_POST['items'] ?? '[]';
            $items = json_decode($itemsJSON, true);

            if (empty($items) || !is_array($items)) {
                echo json_encode(['status' => 'error', 'msg' => 'No se seleccionaron repuestos.']);
                exit;
            }

            $exitos = 0;
            $errores = 0;

            foreach ($items as $item) {
                // Usamos la combinación de orden y repuesto para saber exactamente cuál actualizar
                $idOrden = intval($item['id_orden']);
                $idRepuesto = intval($item['id_repuesto']);

                if ($this->modelo->marcarComoDevuelto($idOrden, $idRepuesto)) {
                    $exitos++;
                } else {
                    $errores++;
                }
            }

            if ($errores == 0) {
                echo json_encode(['status' => 'ok', 'msg' => "✅ $exitos repuestos marcados como devueltos correctamente."]);
            } else {
                echo json_encode(['status' => 'warning', 'msg' => "⚠️ Se procesaron $exitos, pero hubo $errores errores."]);
            }
            exit;
        }
    }
}