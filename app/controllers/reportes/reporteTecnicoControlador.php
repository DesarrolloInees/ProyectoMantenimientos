<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/reporteTecnicoModelo.php';

class reporteTecnicoControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteTecnicoModelo($this->db);
    }

    public function index()
    {
        $datosReporte = []; // Para mostrar en la Tabla (Filtrado)
        $datosExcel = [];   // Para enviar al JS (Todos los técnicos)

        $filtros = [
            'id_tecnico' => '',
            'fecha_inicio' => date('Y-m-01'),
            'fecha_fin' => date('Y-m-d')
        ];
        $totalValor = 0;
        $mensaje = "";

        

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['id_tecnico'] = $_POST['id_tecnico'] ?? '';
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? '';
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? '';

            if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {

                // 1. CONSULTA PARA LA VISTA (Tabla HTML)
                // Respeta el filtro que haya elegido el usuario
                $datosReporte = $this->modelo->generarReporteServicios(
                    $filtros['id_tecnico'],
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                );

                // 2. CONSULTA PARA EL EXCEL (Javascript)
                // Aquí mandamos '' (vacío) en el ID para forzar que traiga A TODOS
                $datosExcel = $this->modelo->generarReporteServicios(
                    '', // <--- TRUCO: Forzamos vacío para traer todo
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                );

                // Calcular total (Solo de lo que se ve en pantalla)
                foreach ($datosReporte as $row) {
                    $totalValor += $row['valor_servicio'];
                }

                if (empty($datosReporte)) {
                    $mensaje = "No se encontraron servicios para la vista en ese rango.";
                }
            } else {
                $mensaje = "Por favor selecciona el rango de fechas.";
            }
        }
        // OBTENER EL PARAMETRO DE LA BD
        $valorCorrectivo = $this->modelo->obtenerParametro('servicioCorrectivo');
        // Validación: Si está vacío o no existe, usamos 1.5 por defecto para no romper nada
        if (!$valorCorrectivo) {
            $valorCorrectivo = 1.5; 
        }

        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $titulo = "Reporte de Servicios por Técnico";

        $vistaContenido = "app/views/reportes/reporteTecnicoVista.php";
        include "app/views/plantillaVista.php";
    }
}
