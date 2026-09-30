<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/turno/turnoModelo.php';

/**
 * Reporte de entradas de turno (roles 1, 2 y 5).
 * Muestra los registros del rango elegido y da salida a Excel y PDF.
 */
class turnoReportesControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new turnoModelo($this->db);
    }

    public function index()
    {
        $fechaInicio = $this->fechaValida($_GET['fecha_inicio'] ?? null, date('Y-m-01'));
        $fechaFin    = $this->fechaValida($_GET['fecha_fin'] ?? null, date('Y-m-d'));
        $idTecnico   = !empty($_GET['id_tecnico']) ? (int) $_GET['id_tecnico'] : null;

        // Si el usuario invirtió el rango, lo ordenamos solos
        if ($fechaInicio > $fechaFin) {
            $tmp = $fechaInicio;
            $fechaInicio = $fechaFin;
            $fechaFin = $tmp;
        }

        $tecnicos  = $this->modelo->obtenerTecnicosActivos();
        $registros = $this->modelo->obtenerTurnos($fechaInicio, $fechaFin, $idTecnico);

        // Totales sencillos para las tarjetas de arriba
        $totalRegistros = count($registros);
        $totalNovedades = 0;
        $totalConUbicacion = 0;
        $tecnicosConRegistro = [];

        foreach ($registros as $r) {
            if (!empty(trim((string) $r['novedad']))) {
                $totalNovedades++;
            }
            if (!empty($r['latitud']) && !empty($r['longitud'])) {
                $totalConUbicacion++;
            }
            $tecnicosConRegistro[$r['nombre_tecnico']] = true;
        }

        $totales = [
            'registros'   => $totalRegistros,
            'novedades'   => $totalNovedades,
            'ubicacion'   => $totalConUbicacion,
            'tecnicos'    => count($tecnicosConRegistro)
        ];

        $titulo = "Reporte de Turnos";
        $vistaContenido = "app/views/turno/turnoReportesVista.php";
        include "app/views/plantillaVista.php";
    }

    /** Devuelve la fecha en Y-m-d o el valor por defecto si viene mal. */
    private function fechaValida($valor, $porDefecto)
    {
        if (!empty($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) && strtotime($valor)) {
            return $valor;
        }
        return $porDefecto;
    }
}
