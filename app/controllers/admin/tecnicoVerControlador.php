<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tecnicoVerModelo.php';

class tecnicoVerControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TecnicoVerModelo($this->db);
    }

    public function index()
    {
        $tecnicos = $this->modelo->obtenerTecnicos();
        $data = ['titulo' => 'Gestión de Técnicos', 'tecnicos' => $tecnicos];
        $vistaContenido = "app/views/admin/tecnicoVerVista.php";
        include "app/views/plantillaVista.php";
    }
}
