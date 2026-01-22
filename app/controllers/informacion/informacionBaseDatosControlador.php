<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/informacion/informacionBaseDatosModelo.php';

class informacionBaseDatosControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new informacionBaseDatosModelo($this->db);
    }

    public function index()
    {
        // Obtener datos
        $kpis = $this->modelo->getResumenGeneral();
        $datosInventario = $this->modelo->getInventarioPorDelegacionYTipo();
        // CAMBIO: Reemplazamos municipios por Clientes y Antigüedad
        $topClientes = $this->modelo->getTopClientes();
        $antiguedad = $this->modelo->getAntiguedadVisitas();

        // Configuración de la vista
        $titulo = "Estado de Base Instalada";
        // Asegúrate de crear esta carpeta/archivo
        $vistaContenido = "app/views/informacion/informacionBaseDatosVista.php"; 

        include "app/views/plantillaVista.php";
    }
}