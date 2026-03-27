<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/supervisor/panelSupervisorModelo.php';

class panelSupervisorControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new panelSupervisorModelo($this->db);
    }

    // Carga la vista principal (incrustada en tu plantilla general)
    public function index()
    {
        $titulo = "Panel de Supervisor - Tiempo Real";
        $vistaContenido = "app/views/supervisor/panelSupervisorVista.php";
        include "app/views/plantillaVista.php";
    }

    // Endpoint AJAX que llamará JavaScript cada 10 segundos
    public function ajaxObtenerServicios()
    {
        // Limpiamos cualquier salida previa (evita que HTML basura rompa el JSON)
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $servicios = $this->modelo->obtenerServiciosDelDia();

        if ($servicios !== false) {
            echo json_encode(["success" => true, "data" => $servicios]);
        } else {
            echo json_encode(["success" => false, "msj" => "Error al consultar la base de datos."]);
        }
        exit;
    }

    // Endpoint AJAX para cancelar un servicio
    public function ajaxCancelarServicio()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $idOrden = $_POST['id_orden'] ?? null;

        if ($idOrden) {
            $resultado = $this->modelo->cancelarServicio($idOrden);
            if ($resultado) {
                echo json_encode(["success" => true, "msj" => "Servicio cancelado correctamente."]);
                exit;
            }
        }
        
        echo json_encode(["success" => false, "msj" => "Error al cancelar el servicio."]);
        exit;
    }

    // ── ENDPOINTS PARA SELECTS EN CASCADA ──
    public function ajaxObtenerTecnicos() {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($this->modelo->obtenerTecnicosActivos()); exit;
    }

    public function ajaxObtenerClientes() {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($this->modelo->obtenerClientesActivos()); exit;
    }

    public function ajaxObtenerPuntos() {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
        echo json_encode($this->modelo->obtenerPuntosPorCliente($id)); exit;
    }

    public function ajaxObtenerMaquinas() {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = isset($_POST['id_punto']) ? (int)$_POST['id_punto'] : 0;
        echo json_encode($this->modelo->obtenerMaquinasPorPunto($id)); exit;
    }

    public function ajaxObtenerTiposMantenimiento() {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($this->modelo->obtenerTiposMantenimiento()); exit;
    }

    // ── ENDPOINT PARA GUARDAR ──
    public function ajaxGuardarServicio() {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if (empty($_POST['id_tecnico']) || empty($_POST['id_cliente']) || empty($_POST['id_punto']) || empty($_POST['id_maquina']) || empty($_POST['id_tipo_mantenimiento']) || empty($_POST['fecha_visita'])) {
            echo json_encode(["success" => false, "msj" => "Faltan datos en el formulario."]);
            exit;
        }

        $datos = [
            'id_tecnico' => (int)$_POST['id_tecnico'],
            'id_cliente' => (int)$_POST['id_cliente'],
            'id_punto' => (int)$_POST['id_punto'],
            'id_maquina' => (int)$_POST['id_maquina'],
            'id_tipo_mantenimiento' => (int)$_POST['id_tipo_mantenimiento'],
            'fecha_visita' => $_POST['fecha_visita']
        ];

        $guardado = $this->modelo->guardarNuevoServicio($datos);

        if ($guardado) {
            echo json_encode(["success" => true, "msj" => "Servicio agendado correctamente."]);
        } else {
            echo json_encode(["success" => false, "msj" => "Error al guardar en la base de datos."]);
        }
        exit;
    }
}