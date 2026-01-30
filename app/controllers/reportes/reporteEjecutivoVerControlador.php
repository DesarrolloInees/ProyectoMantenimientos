<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/ReporteEjecutivoModelo.php';

class ReporteEjecutivoControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteEjecutivoModelo($this->db);
    }

    public function index()
    {
        // Inicializar variables
        $datosDia          = [];
        $datosDelegacion   = [];
        $datosHoras        = [];
        $datosTipo         = [];
        $datosNovedad      = [];
        $datosPuntosCriticos = []; // Nueva variable para los fallidos
        $datosRepuestos    = [];

        // Inicializar KPIs
        $totalServicios = 0;
        $mediaGlobal    = 0;
        $mediaDiaria    = 0;

        // Filtros por defecto
        $filtros = [
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin'    => date('Y-m-d')
        ];

        $mensaje = "";

        // POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? $filtros['fecha_inicio'];
            $filtros['fecha_fin']    = $_POST['fecha_fin'] ?? $filtros['fecha_fin'];
        }

        // Ejecutar consultas
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {

            $inicio = $filtros['fecha_inicio'];
            $fin    = $filtros['fecha_fin'];

            // 1. OBTENEMOS LOS DATOS (Usando las funciones corregidas del PDF)
            $datosDia          = $this->modelo->getServiciosPorDia($inicio, $fin);

            // KPI Delegaciones (Usamos la misma del PDF para consistencia)
            $rawKpis           = $this->modelo->getKpisPorDelegacion($inicio, $fin);
            // Transformamos para gráfico simple de barras
            foreach ($rawKpis as $k) {
                $datosDelegacion[] = ['nombre_delegacion' => $k['nombre_delegacion'], 'total' => $k['total_servicios']];
            }

            $datosHoras        = $this->modelo->getHorasVsServicios($inicio, $fin);
            $datosTipo         = $this->modelo->getPorTipoMantenimiento($inicio, $fin);
            $datosNovedad      = $this->modelo->getDistribucionNovedades($inicio, $fin);

            // AQUI ESTA LA CLAVE: Usamos la nueva función de > 2 fallidos
            $datosPuntosCriticos = $this->modelo->getPuntosConMasFallidos($inicio, $fin);

            // AQUI TAMBIÉN: Usamos la de origen (INEES vs PROSEGUR)
            $datosRepuestos    = $this->modelo->getOrigenRepuestos($inicio, $fin);

            // 2. CALCULOS KPI
            foreach ($datosDia as $d) $totalServicios += $d['total'];

            $fecha1 = new DateTime($inicio);
            $fecha2 = new DateTime($fin);
            $cantidadDias = $fecha1->diff($fecha2)->days + 1;
            $numTecnicos = count($datosHoras);

            $mediaGlobal = $numTecnicos > 0 ? round($totalServicios / $numTecnicos, 1) : 0;
            $mediaDiaria = ($numTecnicos > 0 && $cantidadDias > 0)
                ? round(($totalServicios / $numTecnicos) / $cantidadDias, 2)
                : 0;

            if (empty($datosDia) && empty($datosDelegacion)) {
                $mensaje = "No se encontraron datos para el rango de fechas seleccionado.";
            }
        } else {
            $mensaje = "Por favor selecciona un rango de fechas válido.";
        }

        // Configuración de la vista
        $titulo = "Tablero Ejecutivo de Servicios";
        $vistaContenido = "app/views/reportes/reporteEjecutivoVista.php";

        include "app/views/plantillaVista.php";
    }
}
