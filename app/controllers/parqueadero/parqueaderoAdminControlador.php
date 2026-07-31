<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/parqueadero/parqueaderoAdminModelo.php';

class ParqueaderoAdminControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ParqueaderoAdminModelo($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        // Capturar filtros (Por defecto mostramos el mes actual si no hay filtros)
        $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
        $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');
        $idTecnico = isset($_GET['id_tecnico']) ? $_GET['id_tecnico'] : '';

        // Obtener datos para la vista
        $tecnicos = $this->modelo->obtenerTecnicos();
        $facturas = $this->modelo->obtenerFacturasAdmin($fechaInicio, $fechaFin, $idTecnico);

        // Calcular totales para las tarjetas (cards)
        $totalGastado = 0;
        $totalFacturas = count($facturas);
        foreach ($facturas as $fac) {
            $totalGastado += (float)$fac['valor_factura'];
        }

        // Cargar Vista
        $titulo = "Administración de Parqueaderos";
        $vistaContenido = "app/views/parqueadero/parqueaderoAdminVista.php";
        include "app/views/plantillaVista.php";
    }
}