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
        
        // Asegurarnos de que la sesión esté iniciada para leer el rol
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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

        // 🔒 SEGURIDAD: Capturamos el rol actual
        $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int)$_SESSION['nivel_acceso'] : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros['id_tecnico'] = $_POST['id_tecnico'] ?? '';
            $filtros['fecha_inicio'] = $_POST['fecha_inicio'] ?? '';
            $filtros['fecha_fin'] = $_POST['fecha_fin'] ?? '';

            if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {

                // 1. CONSULTA PARA LA VISTA (Tabla HTML)
                $datosReporte = $this->modelo->generarReporteServicios(
                    $filtros['id_tecnico'],
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                );

                // 2. CONSULTA PARA EL EXCEL (Javascript)
                $datosExcel = $this->modelo->generarReporteServicios(
                    '', // <--- TRUCO: Forzamos vacío para traer todo
                    $filtros['fecha_inicio'],
                    $filtros['fecha_fin']
                );

                // 🛡️ FILTRO DE SEGURIDAD PARA ROL 5
                if ($rolUsuario === 5) {
                    if (!empty($datosReporte)) {
                        foreach ($datosReporte as &$r) {
                            $r['valor_servicio'] = 0;
                        }
                    }
                    if (!empty($datosExcel)) {
                        foreach ($datosExcel as &$e) {
                            $e['valor_servicio'] = 0;
                        }
                    }
                }

                // Calcular total (Solo de lo que se ve en pantalla)
                // Si es rol 5, como arriba pusimos todo en 0, el total dará 0 automáticamente
                foreach ($datosReporte as $row) {
                    $totalValor += floatval($row['valor_servicio']);
                }

                if (empty($datosReporte)) {
                    $mensaje = "No se encontraron servicios para la vista en ese rango.";
                }
            } else {
                $mensaje = "Por favor selecciona el rango de fechas.";
            }
        }
        
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaFestivos = $this->modelo->obtenerFestivos($filtros['fecha_inicio'], $filtros['fecha_fin']);
        $titulo = "Reporte de Servicios por Técnico";

        $vistaContenido = "app/views/reportes/reporteTecnicoVista.php";
        include "app/views/plantillaVista.php";
    }
}