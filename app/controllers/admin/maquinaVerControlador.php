<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/maquinaVerModelo.php';

class maquinaVerControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new MaquinaVerModelo($this->db);
    }

    public function index()
    {
        $maquinas = $this->modelo->obtenerMaquinas();
        $data = ['titulo' => 'Inventario de Máquinas', 'maquinas' => $maquinas];
        $vistaContenido = "app/views/admin/maquinaVerVista.php";
        include "app/views/plantillaVista.php";
    }
}
