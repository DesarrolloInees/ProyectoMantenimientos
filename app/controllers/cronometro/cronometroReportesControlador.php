<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/cronometro/cronometroReportesModelo.php';

class cronometroReportesControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new cronometroReportesModelo($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        // Filtros de fecha (Por defecto el mes actual)
        $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
        $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

        // Configuración de metas por tipo de día (fallback si no envían meta manual)
        $metaLunesViernes = 6;
        $metaSabado = 3;

        if (isset($_GET['meta_total']) && is_numeric($_GET['meta_total']) && (int) $_GET['meta_total'] > 0) {
            $metaTotalServicios = (int) $_GET['meta_total'];
        } else {
            $metaTotalServicios = $this->calcularMetaRango($fechaInicio, $fechaFin, $metaLunesViernes, $metaSabado);
        }

        // Obtener tipos de mantenimiento activos
        $tipos = $this->modelo->obtenerTiposMantenimiento();

        // Obtener rendimiento por técnico
        $rendimientoRaw = $this->modelo->obtenerRendimientoTecnicos($fechaInicio, $fechaFin, $tipos);


        // Procesar datos para la gráfica y la tabla
        $tecnicosNombres = [];
        $serviciosRealizados = [];
        $porcentajesCumplimiento = [];
        $coloresBarras = [];

        $totalGeneralServicios = 0;
        $tecnicosCumplieron = 0;

        foreach ($rendimientoRaw as $r) {
            $finalizados = (int) $r['total_finalizados'];
            $totalGeneralServicios += $finalizados;

            $porcentaje = ($metaTotalServicios > 0) ? round(($finalizados / $metaTotalServicios) * 100, 1) : 0;

            $tecnicosNombres[] = $r['nombre_tecnico'];
            $serviciosRealizados[] = $finalizados;
            $porcentajesCumplimiento[] = $porcentaje;

            if ($porcentaje >= 100) {
                $coloresBarras[] = '#10b981'; // Verde (Meta alcanzada o superada)
                $tecnicosCumplieron++;
            } elseif ($porcentaje >= 70) {
                $coloresBarras[] = '#f59e0b'; // Naranja (Cerca de la meta)
            } else {
                $coloresBarras[] = '#ef4444'; // Rojo (Bajo rendimiento)
            }
        }

        $titulo = "Reporte de Rendimiento de Flota";
        $vistaContenido = "app/views/cronometro/cronometroReportesVista.php";
        include "app/views/plantillaVista.php";
    }

    private function calcularMetaRango($fechaInicio, $fechaFin, $metaLV = 6, $metaSab = 3)
    {
        $inicio = new DateTime($fechaInicio);
        $fin = new DateTime($fechaFin);
        $fin->modify('+1 day'); // Incluir el día final en el periodo

        $intervalo = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $intervalo, $fin);

        $metaTotal = 0;

        foreach ($periodo as $dt) {
            $diaSemana = (int) $dt->format('N'); // 1 (Lunes) a 7 (Domingo)

            if ($diaSemana >= 1 && $diaSemana <= 5) {
                $metaTotal += $metaLV;
            } elseif ($diaSemana === 6) {
                $metaTotal += $metaSab;
            }
        }

        return $metaTotal;
    }
}