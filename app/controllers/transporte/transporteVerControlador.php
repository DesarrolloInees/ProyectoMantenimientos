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

    public function index()
    {
        $titulo = "Gestión de Transportes";
        $vistaContenido = "app/views/transporte/transporteVerVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxListar()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');

        $datos = $this->modelo->obtenerInstalaciones();
        $data = [];

        foreach ($datos as $row) {
            // 1. Badge para la Categoría (Prosegur / Inees)
            $cat = $row['categoria_servicio'];
            $tipoServicio = htmlspecialchars($row['tipo_servicio_nombre'] ?: 'N/A');
            
            if ($cat === 'Prosegur_Cobro') {
                $badgeOp = '<span class="px-2 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full uppercase"><i class="fas fa-dollar-sign mr-1"></i> P. Cobro</span><br><small class="text-gray-500">'.$tipoServicio.'</small>';
            } elseif ($cat === 'Prosegur_NoCobro') {
                $badgeOp = '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full uppercase"><i class="fas fa-handshake mr-1"></i> P. Sin Cobro</span><br><small class="text-gray-500">'.$tipoServicio.'</small>';
            } else { // Inees
                $badgeOp = '<span class="px-2 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full uppercase"><i class="fas fa-building mr-1"></i> Inees</span><br><small class="text-gray-500">'.$tipoServicio.'</small>';
            }

            // 2. Info del Producto (Evalúa si es máquina o texto libre)
            if ($row['es_maquina'] == 1) {
                $serial = htmlspecialchars($row['serial_maquina'] ?: 'Sin serial');
                $tipoMaq = htmlspecialchars($row['nombre_tipo_maquina'] ?: 'Tipo no definido');
                $productoInfo = "<strong><i class='fas fa-hdd text-gray-400'></i> $serial</strong><br><small class='text-gray-500'>$tipoMaq</small>";
            } else {
                $otro = htmlspecialchars($row['producto_otro'] ?: 'No especificado');
                $productoInfo = "<strong><i class='fas fa-box-open text-gray-400'></i> Otros:</strong><br><small class='text-gray-500'>$otro</small>";
            }

            // 3. Info del Destino (Evalúa si viene de tabla Cliente/Punto o si es texto libre)
            $cliente = htmlspecialchars($row['nombre_cliente'] ?: ($row['cliente_destino_texto'] ?: 'Sin cliente'));
            $punto = htmlspecialchars($row['nombre_punto'] ?: ($row['punto_destino_texto'] ?: 'Sin punto'));
            $clientePunto = "<strong>$cliente</strong><br><small class='text-gray-500'>$punto</small>";

            // 4. Botones de Acción
            $id = $row['id_instalacion'];
            $btnVer = "<button onclick='verDetalle($id)' class='text-blue-500 hover:text-blue-700 mx-1' title='Ver Detalles'><i class='fas fa-eye'></i></button>";
            $btnEditar = "<a href='index.php?pagina=transporteEditar&id=$id' class='text-amber-500 hover:text-amber-700 mx-1' title='Editar'><i class='fas fa-edit'></i></a>";
            $btnEliminar = "<button onclick='eliminarRegistro($id)' class='text-red-500 hover:text-red-700 mx-1' title='Eliminar'><i class='fas fa-trash-alt'></i></button>";
            
            $acciones = "<div class='flex justify-center text-lg'>" . $btnVer . $btnEditar . $btnEliminar . "</div>";

            // Armar la fila
            $data[] = [
                $id,
                $row['fecha_instalacion'],
                $badgeOp,
                htmlspecialchars($row['nombre_tecnico']),
                $productoInfo,
                $clientePunto,
                $acciones
            ];
        }

        echo json_encode(['data' => $data]);
        exit;
    }

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