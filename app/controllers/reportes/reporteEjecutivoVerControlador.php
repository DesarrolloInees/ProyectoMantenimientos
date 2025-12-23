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
        $datosDia        = [];
        $datosDelegacion = [];
        $datosHoras      = [];
        $datosTipo       = [];
        $datosNovedad    = [];
        $datosEstado     = [];
        $datosRepuestos  = [];

        // Inicializar KPIs
        $totalServicios = 0;
        $mediaServicios = 0; // Media Global
        $mediaDiaria    = 0; // Media Diaria (La nueva)
        $numTecnicos    = 0;

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

            // 1. PRIMERO OBTENEMOS LOS DATOS DE LA BD (Esto estaba abajo antes)
            $datosDia        = $this->modelo->getServiciosPorDia($filtros['fecha_inicio'], $filtros['fecha_fin']);
            $datosDelegacion = $this->modelo->getDelegacionesIntervenidas($filtros['fecha_inicio'], $filtros['fecha_fin']);
            $datosHoras      = $this->modelo->getHorasVsServicios($filtros['fecha_inicio'], $filtros['fecha_fin']);
            $datosTipo       = $this->modelo->getPorTipoMantenimiento($filtros['fecha_inicio'], $filtros['fecha_fin']);
            $datosNovedad    = $this->modelo->getDistribucionNovedades($filtros['fecha_inicio'], $filtros['fecha_fin']);
            $datosEstado     = $this->modelo->getServiciosFallidos($filtros['fecha_inicio'], $filtros['fecha_fin']);
            $datosRepuestos  = $this->modelo->getComparativaRepuestos($filtros['fecha_inicio'], $filtros['fecha_fin']);

            // 2. AHORA SI CALCULAMOS (Ya tenemos datos)

            // A. Calcular Total Servicios
            foreach ($datosDia as $d) {
                $totalServicios += $d['total'];
            }

            // B. Calcular Días del Rango
            $fecha1 = new DateTime($filtros['fecha_inicio']);
            $fecha2 = new DateTime($filtros['fecha_fin']);
            $diferencia = $fecha1->diff($fecha2);
            $cantidadDias = $diferencia->days + 1; // +1 para incluir el día final

            $numTecnicos = count($datosHoras);

            // C. Calcular Medias
            // Media Global (Total / Técnicos)
            $mediaGlobal = $numTecnicos > 0 ? round($totalServicios / $numTecnicos, 1) : 0;
            // Variable para la vista (por compatibilidad con código anterior)
            $mediaServicios = $mediaGlobal;

            // Media Diaria ( (Total / Técnicos) / Días )
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
