<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoMantenimientoVerModelo.php';

class tipoMantenimientoVerControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoMantenimientoVerModelo($this->db);
    }

    public function index()
    {
        $tipos = $this->modelo->obtenerTipos();
        $data = ['titulo' => 'Tipos de Mantenimiento', 'tipos' => $tipos];
        $vistaContenido = "app/views/admin/tipoMantenimientoVerVista.php";
        include "app/views/plantillaVista.php";
    }
}
