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
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            echo "<script>alert('ID no especificado.'); window.location.href='index.php?pagina=transporteVer';</script>";
            exit;
        }

        $id = intval($_GET['id']);
        $instalacion = $this->modelo->obtenerInstalacionPorId($id);

        if (!$instalacion) {
            echo "<script>alert('Registro no encontrado o fue eliminado.'); window.location.href='index.php?pagina=transporteVer';</script>";
            exit;
        }

        $this->cargarVista($instalacion);
    }

    private function cargarVista($instalacion)
    {
        $tecnicos     = $this->modelo->obtenerTecnicos();
        $tiposMaquina = $this->modelo->obtenerTiposMaquina();
        $clientes     = $this->modelo->obtenerClientes();

        // Cargar puntos de origen y destino si tienen ID registrado
        $puntosOrigen = [];
        if (!empty($instalacion['id_cliente_origen'])) {
            $puntosOrigen = $this->modelo->obtenerPuntosPorCliente($instalacion['id_cliente_origen']);
        }
        $puntosDestino = [];
        if (!empty($instalacion['id_cliente_destino'])) {
            $puntosDestino = $this->modelo->obtenerPuntosPorCliente($instalacion['id_cliente_destino']);
        }

        // Cargar remisiones del técnico
        $remisionesTecnico = [];
        if (!empty($instalacion['id_tecnico'])) {
            $idRemActual = $instalacion['id_control_remision'] ?: 0;
            $remisionesTecnico = $this->modelo->obtenerRemisionesDisponibles($instalacion['id_tecnico'], $idRemActual);
        }

        $titulo         = "Editar Transporte #" . $instalacion['id_instalacion'];
        $vistaContenido = "app/views/transporte/transporteEditarVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxPuntos()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');
        if (!empty($_POST['id_cliente']) && is_numeric($_POST['id_cliente'])) {
            echo json_encode($this->modelo->obtenerPuntosPorCliente(intval($_POST['id_cliente'])), JSON_UNESCAPED_UNICODE);
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

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_instalacion'])) {
            header('Location: index.php?pagina=transporteVer');
            exit;
        }

        // Limpiar el valor económico (Tarifa)
        $valorLimpio = str_replace(['$', '.', ' ', ','], '', $_POST['valor_servicio'] ?? '0');
        $valorFinal = is_numeric($valorLimpio) ? floatval($valorLimpio) : 0;

        // Parsear inputs dinámicos (Select2 Tags)
        $parseDinamico = function($input) {
            $val = trim($input ?? '');
            if ($val === '') return ['id' => null, 'texto' => null];
            if (is_numeric($val)) return ['id' => intval($val), 'texto' => null];
            return ['id' => null, 'texto' => $val];
        };

        $clienteOrigen = $parseDinamico($_POST['cliente_origen'] ?? '');
        $puntoOrigen = $parseDinamico($_POST['punto_origen'] ?? '');
        $clienteDestino = $parseDinamico($_POST['cliente_destino'] ?? '');
        $puntoDestino = $parseDinamico($_POST['punto_destino'] ?? '');

        // Determinar el nombre del tipo de servicio según categoría
        $categoria = $_POST['categoria_servicio'] ?? '';
        $tipoServicioNombre = null;
        if ($categoria === 'Inees') {
            $tipoServicioNombre = $_POST['tipo_servicio_inees'] ?? null;
        } else if ($categoria === 'Prosegur_Cobro') {
            $tipoServicioNombre = $_POST['tipo_servicio_cobro'] ?? null;
        } else if ($categoria === 'Prosegur_NoCobro') {
            $tipoServicioNombre = $_POST['tipo_servicio_nocobro'] ?? null;
        }

        $datos = [
            'id_instalacion'        => intval($_POST['id_instalacion']),
            'id_tecnico'            => $_POST['id_tecnico'] ?? null,
            'id_control_remision'   => $_POST['id_control_remision'] ?? null,
            'fecha_instalacion'     => $_POST['fecha_instalacion'] ?? null,
            'categoria_servicio'    => $categoria,
            'tipo_servicio_nombre'  => $tipoServicioNombre,
            'notas'                 => trim($_POST['notas'] ?? ''),
            'descripcion_inees'     => trim($_POST['descripcion_inees'] ?? ''),
            'lugar_recogida'        => $_POST['lugar_recogida'] ?? null,
            'fecha_recogida'        => $_POST['fecha_recogida'] ?? null,
            'es_maquina'            => isset($_POST['es_maquina']) ? intval($_POST['es_maquina']) : 1,
            'id_tipo_maquina'       => $_POST['id_tipo_maquina'] ?? null,
            'serial_maquina'        => trim($_POST['serial_maquina'] ?? ''),
            'producto_otro'         => trim($_POST['producto_otro'] ?? ''),
            'id_cliente_origen'     => $clienteOrigen['id'],
            'cliente_origen_texto'  => $clienteOrigen['texto'],
            'id_punto_origen'       => $puntoOrigen['id'],
            'punto_origen_texto'    => $puntoOrigen['texto'],
            'id_cliente_destino'    => $clienteDestino['id'],
            'cliente_destino_texto' => $clienteDestino['texto'],
            'id_punto_destino'      => $puntoDestino['id'],
            'punto_destino_texto'   => $puntoDestino['texto'],
            'valor_servicio'        => $valorFinal
        ];
    
        if ($this->modelo->actualizarInstalacion($datos)) {
            $_SESSION['mensaje_exito'] = "✅ Registro actualizado correctamente. Tarifa aplicada: $" . number_format($valorFinal, 0, '', '.');
            header("Location: index.php?pagina=transporteVer");
        } else {
            $_SESSION['mensaje_error'] = "❌ Error al actualizar el registro.";
            header("Location: index.php?pagina=transporteEditar&id=" . $datos['id_instalacion']);
        }
        exit;
    }

    private function limpiarBuffer()
    {
        while (ob_get_level()) ob_end_clean();
        ob_start();
    }
}