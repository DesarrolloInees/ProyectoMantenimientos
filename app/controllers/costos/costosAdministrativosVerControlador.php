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
}