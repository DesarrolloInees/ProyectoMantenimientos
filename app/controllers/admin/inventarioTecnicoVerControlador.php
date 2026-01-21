<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/inventarioTecnicoVerModelo.php';

class InventarioTecnicoVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new InventarioTecnicoVerModelo($this->db);
    }

    public function index()
    {
        // Obtenemos todos los datos
        $inventario = $this->modelo->obtenerInventarioCompleto();
        
        // Obtenemos lista única de técnicos para el filtro Select2
        $listaTecnicos = $this->modelo->obtenerListaTecnicos();

        $titulo = "Inventario por Técnico";
        $vistaContenido = "app/views/admin/inventarioTecnicoVerVista.php";
        include "app/views/plantillaVista.php";
    }
}