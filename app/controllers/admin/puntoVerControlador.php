<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/puntoVerModelo.php';

class puntoVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new PuntoVerModelo($this->db);
    }

    public function index()
    {
        // 1. Datos para pintar la tabla HTML
        $puntos = $this->modelo->obtenerPuntos();
        
        // 2. Datos completos para enviar al JavaScript (Excel)
        $datosExcel = $this->modelo->obtenerDatosExcelPuntos();

        // 3. Pasamos todo a la vista
        $data = [
            'titulo' => 'Listado de Puntos', 
            'puntos' => $puntos,
            'datosExcel' => $datosExcel
        ];
        
        $vistaContenido = "app/views/admin/puntoVerVista.php";
        include "app/views/plantillaVista.php";
    }
}