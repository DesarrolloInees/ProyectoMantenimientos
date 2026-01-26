<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/reportes/ReporteMaquinasModelo.php';

class ReporteMaquinasControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteMaquinasModelo($this->db);
    }

    public function index()
    {
        // Variables iniciales
        $datosMaquinas = [];
        $idTipoSeleccionado = '';
        
        // Obtenemos la lista para el dropdown
        $listaTipos = $this->modelo->obtenerListaTipos();

        // Si el usuario dio clic en "Generar"
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idTipoSeleccionado = $_POST['id_tipo_maquina'] ?? '';

            if (!empty($idTipoSeleccionado)) {
                $datosMaquinas = $this->modelo->obtenerMaquinasPorTipo($idTipoSeleccionado);
            }
        }
        
        $titulo = "Reporte por Tipo de Máquina";
        $vistaContenido = "app/views/reportes/reporteMaquinasVista.php";
        include "app/views/plantillaVista.php";
    }
}