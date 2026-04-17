<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/transporte/transporteCrearModelo.php';

class transporteCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db    = $conexionObj->getConexion();
        $this->modelo = new transporteCrearModelo($this->db);
    }

    public function index()
    {
        $this->cargarVista();
    }

    public function cargarVista()
    {
        $tecnicos      = $this->modelo->obtenerTecnicos();
        $delegaciones  = $this->modelo->obtenerDelegaciones();
        $tiposMaquina  = $this->modelo->obtenerTiposMaquina();
        $tiposServicio = $this->modelo->obtenerTiposServicio();
        $clientes      = $this->modelo->obtenerClientes();
        $dirOrigen     = $this->modelo->obtenerDireccionOrigen();

        $titulo          = "Transportes";
        $vistaContenido  = "app/views/transporte/transporteCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxPuntos()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!empty($_POST['id_cliente'])) {
                $puntos = $this->modelo->obtenerPuntosPorCliente(intval($_POST['id_cliente']));
                echo json_encode($puntos, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([]);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function ajaxDetallePunto()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!empty($_POST['id_punto'])) {
                $detalle = $this->modelo->obtenerDetallePunto(intval($_POST['id_punto']));
                echo json_encode($detalle ?: [], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([]);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function ajaxRemisiones()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!empty($_POST['id_tecnico'])) {
                $remisiones = $this->modelo->obtenerRemisionesDisponibles(intval($_POST['id_tecnico']));
                echo json_encode($remisiones, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([]);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?pagina=transporte');
            exit;
        }

        $valorLimpio = str_replace(['$', '.', ' ', ','], '', $_POST['valor_servicio'] ?? '0');
        $valorFinal  = is_numeric($valorLimpio) ? floatval($valorLimpio) : 0;

        $datos = [
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

        $errores = [];
        if (empty($datos['tipo_operacion']))        $errores[] = 'Tipo de operación requerido.';
        if (empty($datos['fecha_solicitud']))       $errores[] = 'Fecha de solicitud requerida.';
        if (empty($datos['id_tecnico']))            $errores[] = 'Técnico requerido.';
        if (empty($datos['id_tipo_maquina']))       $errores[] = 'Tipo de máquina requerido.';
        if (empty($datos['id_delegacion_origen']))  $errores[] = 'Delegación origen requerida.';

        if (!empty($errores)) {
            $mensajeError = implode('\\n', $errores);
            echo "<script>alert('⚠️ Por favor corrija:\\n$mensajeError'); history.back();</script>";
            return;
        }

        $idInstalacion = $this->modelo->guardarInstalacion($datos);

        if ($idInstalacion) {
            echo "<script>
                alert('✅ Registro #$idInstalacion guardado correctamente.');
                window.location.href = 'index.php?pagina=transporteVer';
            </script>";
        } else {
            echo "<script>
                alert('❌ Error al guardar el registro. Revisa los datos e intenta de nuevo.');
                history.back();
            </script>";
        }
    }

    private function limpiarBuffer()
    {
        while (ob_get_level()) ob_end_clean();
        ob_start();
    }
}