<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/costos/costosCrearModelo.php';

class costosCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new CostosCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $mensajeExito = "";
        
        // CAMBIO: Llamamos solo a motorizados
        $listaPersonal = $this->modelo->obtenerMotorizados(); 

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $mesSeleccionado = $_POST['mes_reporte'] ?? '';
            $datosCostos = $_POST['costos'] ?? [];

            if (empty($mesSeleccionado)) {
                $errores[] = "Debes seleccionar el Mes de Reporte.";
            }

            if (empty($datosCostos)) {
                $errores[] = "No hay datos para guardar.";
            }

            if (empty($errores)) {
                $fechaReporte = $mesSeleccionado . "-01";

                // CAMBIO: Método de guardado específico para técnicos
                if ($this->modelo->guardarCostosMotorizados($fechaReporte, $datosCostos)) {
                    $mensajeExito = "Costos de motorizados guardados correctamente.";
                } else {
                    $errores[] = "Error al guardar en la base de datos.";
                }
            }
        }

        $titulo = "Costos Motorizados";
        $vistaContenido = "app/views/costos/costosCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}