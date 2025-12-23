<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/controlRemisionVerModelo.php';

class controlRemisionVerControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ControlRemisionVerModelo($this->db);
    }

    public function index()
    {
        $remisiones = $this->modelo->listarRemisiones();

        $titulo = "Control de Remisiones";
        $vistaContenido = "app/views/admin/controlRemisionVerVista.php";
        include "app/views/plantillaVista.php";
    }
}
