<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/transporte/transporteEditarModelo.php';

class transporteEditarControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db    = $conexionObj->getConexion();
        $this->modelo = new transporteEditarModelo($this->db);
    }

    public function index()
    {
        // Validar que venga el ID
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            echo "<script>alert('ID no especificado.'); window.location.href='index.php?pagina=transporte';</script>";
            exit;
        }

        $id = intval($_GET['id']);
        $instalacion = $this->modelo->obtenerInstalacionPorId($id);

        if (!$instalacion) {
            echo "<script>alert('Registro no encontrado o fue eliminado.'); window.location.href='index.php?pagina=transporte';</script>";
            exit;
        }

        $this->cargarVista($instalacion);
    }

    private function cargarVista($instalacion)
    {
        // Listas generales
        $tecnicos      = $this->modelo->obtenerTecnicos();
        $delegaciones  = $this->modelo->obtenerDelegaciones();
        $tiposMaquina  = $this->modelo->obtenerTiposMaquina();
        $tiposServicio = $this->modelo->obtenerTiposServicio();
        $clientes      = $this->modelo->obtenerClientes();
        $dirOrigen     = $this->modelo->obtenerDireccionOrigen();

        // Listas precargadas según lo que ya tiene guardado el registro
        $puntosCliente = [];
        if (!empty($instalacion['id_cliente'])) {
            $puntosCliente = $this->modelo->obtenerPuntosPorCliente($instalacion['id_cliente']);
        }

        $remisionesTecnico = [];
        if (!empty($instalacion['id_tecnico'])) {
            $idRemActual = $instalacion['id_control_remision'] ?: 0;
            $remisionesTecnico = $this->modelo->obtenerRemisionesDisponibles($instalacion['id_tecnico'], $idRemActual);
        }

        $titulo          = "Editar Transporte #" . $instalacion['id_instalacion'];
        $vistaContenido  = "app/views/transporte/transporteEditarVista.php";
        include "app/views/plantillaVista.php";
    }

    // --- AJAX METODOS (Iguales a Crear) ---
    public function ajaxPuntos()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');
        if (!empty($_POST['id_cliente'])) {
            echo json_encode($this->modelo->obtenerPuntosPorCliente(intval($_POST['id_cliente'])), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([]);
        }
        exit;
    }

    public function ajaxDetallePunto()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');
        if (!empty($_POST['id_punto'])) {
            echo json_encode($this->modelo->obtenerDetallePunto(intval($_POST['id_punto'])) ?: [], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([]);
        }
        exit;
    }

    public function ajaxRemisiones()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');
        if (!empty($_POST['id_tecnico'])) {
            echo json_encode($this->modelo->obtenerRemisionesDisponibles(intval($_POST['id_tecnico'])), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([]);
        }
        exit;
    }

    // --- ACTUALIZAR ---
    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_instalacion'])) {
            header('Location: index.php?pagina=transporte');
            exit;
        }

        $valorLimpio = str_replace(['$', '.', ' ', ','], '', $_POST['valor_servicio'] ?? '0');
        $valorFinal  = is_numeric($valorLimpio) ? floatval($valorLimpio) : 0;

        $datos = [
            'id_instalacion'        => intval($_POST['id_instalacion']),
            'tipo_operacion'        => $_POST['tipo_operacion']        ?? 'instalacion',
            'fecha_solicitud'       => $_POST['fecha_solicitud']       ?? date('Y-m-d'),
            'fecha_ejecucion'       => $_POST['fecha_ejecucion']       ?? null,
            'id_control_remision'   => $_POST['id_control_remision']   ?? null,
            'serial_maquina'        => trim($_POST['serial_maquina']   ?? ''),
            'id_tipo_maquina'       => $_POST['id_tipo_maquina']       ?? null,
            'id_tecnico'            => $_POST['id_tecnico']            ?? null,
            'id_delegacion_origen'  => $_POST['id_delegacion_origen']  ?? null,
            'id_delegacion_destino' => $_POST['id_delegacion_destino'] ?? null,
            'id_punto'              => $_POST['id_punto']              ?? null,
            'id_tipo_servicio'      => $_POST['id_tipo_servicio']      ?? null,
            'valor_servicio'        => $valorFinal,
            'comentarios'           => trim($_POST['comentarios']      ?? ''),
        ];

        if ($this->modelo->actualizarInstalacion($datos)) {
            echo "<script>
                alert('✅ Registro actualizado correctamente.');
                window.location.href = 'index.php?pagina=transporteVer';
            </script>";
        } else {
            echo "<script>
                alert('❌ Error al actualizar el registro.');
                history.back();
            </script>";
        }
    }

    private function limpiarBuffer() {
        while (ob_get_level()) ob_end_clean();
        ob_start();
    }
}