<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/costos/costosAdministrativosVerModelo.php';

class costosAdministrativosVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new costosAdministrativosVerModelo($this->db);
    }

    public function index()
    {
        // 1. Obtener la data agrupada por mes
        $reporteMensual = $this->modelo->obtenerResumenMensual();

        // 2. Cargar la vista
        $titulo = "Histórico Costos Administrativos";
        $vistaContenido = "app/views/costos/costosAdministrativosVerVista.php";
        
        include "app/views/plantillaVista.php";
    }



    public function eliminarMes()
    {
        // 1. Verificamos que llegue el dato del mes (ej: 2023-10)
        if (isset($_GET['mes'])) {
            $mes = $_GET['mes'];
            
            // 2. Llamamos al modelo
            $this->modelo->eliminarMesAdministrativoCompleto($mes);
        }

        // 3. Redireccionar siempre a la lista principal
        header('Location: ' . BASE_URL . 'costosAdministrativosVer');
        exit;
    }



}