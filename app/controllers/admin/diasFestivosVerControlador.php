<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/diasFestivosVerModelo.php';

class DiasFestivosVerControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new DiasFestivosVerModelo($this->db);
    }

    public function index()
    {
        $festivos = $this->modelo->obtenerTodos();

        $titulo = "Gestión de Días Festivos";
        $vistaContenido = "app/views/admin/diasFestivosVerVista.php";
        include "app/views/plantillaVista.php";
    }
}
