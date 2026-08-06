<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/horaExtra/horaExtraAdminModelo.php';

class horaExtraAdminControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new horaExtraAdminModelo($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        // Capturar filtros (Por defecto se muestra el rango del mes actual)
        $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
        $fechaFin    = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');
        $idTecnico   = isset($_GET['id_tecnico']) ? $_GET['id_tecnico'] : '';
        $idEstado    = isset($_GET['id_estado']) ? $_GET['id_estado'] : '';

        // Obtener datos
        $tecnicos = $this->modelo->obtenerTecnicos();
        $estados  = $this->modelo->obtenerEstadosAprobacion();
        $reportes = $this->modelo->obtenerHorasExtraAdmin($fechaInicio, $fechaFin, $idTecnico, $idEstado);

        // Totales para tarjetas de resumen
        $totalHoras     = 0;
        $totalAprobadas = 0;
        $totalPendientes = 0;
        $totalRechazadas = 0;

        foreach ($reportes as $r) {
            $totalHoras += (float) $r['total_horas'];
            if ($r['id_estado_aprobacion'] == 1) $totalPendientes++;
            if ($r['id_estado_aprobacion'] == 2) $totalAprobadas += (float) $r['total_horas'];
            if ($r['id_estado_aprobacion'] == 3) $totalRechazadas++;
        }

        // Cargar vista
        $titulo = "Administración de Horas Extra";
        $vistaContenido = "app/views/horaExtra/horaExtraAdminVista.php";
        include "app/views/plantillaVista.php";
    }

    // Endpoint AJAX para auditar (aprobar o rechazar)
    public function ajaxCambiarEstado()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idRegistro  = isset($_POST['id_registro']) ? (int) $_POST['id_registro'] : 0;
            $idEstado    = isset($_POST['id_estado']) ? (int) $_POST['id_estado'] : 0;
            $observacion = isset($_POST['observacion']) ? trim($_POST['observacion']) : '';

            if ($idRegistro > 0 && $idEstado > 0) {
                if ($this->modelo->actualizarEstadoHorasExtra($idRegistro, $idEstado, $observacion)) {
                    echo json_encode(['success' => true, 'msj' => 'Estado actualizado correctamente.']);
                    exit;
                }
            }
        }

        echo json_encode(['success' => false, 'msj' => 'No se pudo actualizar el registro.']);
        exit;
    }
}