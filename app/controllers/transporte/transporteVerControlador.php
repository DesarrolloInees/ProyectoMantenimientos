<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/transporte/transporteVerModelo.php';

class transporteVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db    = $conexionObj->getConexion();
        $this->modelo = new transporteVerModelo($this->db);
    }

    // --- CARGAR LA VISTA PRINCIPAL ---
    public function index()
    {
        $titulo = "Gestión de Transportes";
        $vistaContenido = "app/views/transporte/transporteVerVista.php";
        include "app/views/plantillaVista.php";
    }

    // --- AJAX: LLENAR EL DATATABLE ---
    public function ajaxListar()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');

        $datos = $this->modelo->obtenerInstalaciones();
        $data = [];

        foreach ($datos as $row) {
            // 1. Badge para el tipo de operación
            $tipo = strtolower($row['tipo_operacion']);
            if ($tipo == 'instalacion') {
                $badgeOp = '<span class="px-2 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full uppercase"><i class="fas fa-plus-circle mr-1"></i> Inst.</span>';
            } elseif ($tipo == 'desinstalacion') {
                $badgeOp = '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full uppercase"><i class="fas fa-minus-circle mr-1"></i> Desinst.</span>';
            } else {
                $badgeOp = '<span class="px-2 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full uppercase"><i class="fas fa-exchange-alt mr-1"></i> Trasl.</span>';
            }

            // 2. Info de la máquina
            $maquinaInfo = "<strong>" . htmlspecialchars($row['serial_maquina'] ?: 'N/A') . "</strong><br><small class='text-gray-500'>" . htmlspecialchars($row['nombre_tipo_maquina']) . "</small>";

            // 3. Info del destino
            $clientePunto = "<strong>" . htmlspecialchars($row['nombre_cliente'] ?: 'N/A') . "</strong><br><small class='text-gray-500'>" . htmlspecialchars($row['nombre_punto'] ?: 'Sin punto') . "</small>";

            // 4. Botones de Acción (CRUD)
            $id = $row['id_instalacion'];
            $btnVer = "<button onclick='verDetalle($id)' class='text-blue-500 hover:text-blue-700 mx-1' title='Ver Detalles'><i class='fas fa-eye'></i></button>";
            $btnEditar = "<a href='index.php?pagina=transporteEditar&id=$id' class='text-amber-500 hover:text-amber-700 mx-1' title='Editar'><i class='fas fa-edit'></i></a>";
            $btnEliminar = "<button onclick='eliminarRegistro($id)' class='text-red-500 hover:text-red-700 mx-1' title='Eliminar'><i class='fas fa-trash-alt'></i></button>";
            
            $acciones = "<div class='flex justify-center text-lg'>" . $btnVer . $btnEditar . $btnEliminar . "</div>";

            // Armar la fila
            $data[] = [
                $id,
                $row['fecha_solicitud'],
                $badgeOp,
                htmlspecialchars($row['nombre_tecnico']),
                $maquinaInfo,
                $clientePunto,
                $acciones
            ];
        }

        echo json_encode(['data' => $data]);
        exit;
    }

    // --- AJAX: ELIMINAR REGISTRO ---
    public function eliminar()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $resultado = $this->modelo->eliminarInstalacion($id);

            if ($resultado) {
                echo json_encode(['status' => 'success', 'message' => 'Registro eliminado correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el registro.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Petición inválida.']);
        }
        exit;
    }

    private function limpiarBuffer()
    {
        while (ob_get_level()) ob_end_clean();
        ob_start();
    }
}