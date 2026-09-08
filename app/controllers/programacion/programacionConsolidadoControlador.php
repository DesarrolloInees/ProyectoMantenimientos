<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/programacion/programacionConsolidadoModelo.php';

class programacionConsolidadoControlador
{
    private $modelo;

    public function __construct()
    {
        $db = (new Conexion())->getConexion();
        $this->modelo = new programacionConsolidadoModelo($db);
    }

    public function index()
    {
        // Valores por defecto: Semana actual
        // En programacionConsolidadoControlador.php
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('monday this week'));
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d', strtotime('sunday this week'));
        $delegacion = $_GET['delegacion'] ?? '';
        $tecnico = $_GET['tecnico'] ?? '';

        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaTecnicos = $this->modelo->obtenerTecnicos();

        $consolidado = $this->modelo->obtenerConsolidadoRutas($fecha_inicio, $fecha_fin, $delegacion, $tecnico);

        $titulo = "Consolidado General de Programación";
        $vistaContenido = "app/views/programacion/programacionConsolidadoVista.php";
        include "app/views/plantillaVista.php";
    }


    public function exportarExcelConsolidado()
    {
        // 1. Ejecutar la restauración automática de las máquinas que faltaron por programar
        $resRestauracion = $this->modelo->restaurarMaquinasRestantesAOperativo();

        // 2. Proceder con la lógica habitual de consulta e impresión/generación del Excel...
        // $datosExcel = $this->modelo->obtenerConsolidadoRutas(...);
    }
}
