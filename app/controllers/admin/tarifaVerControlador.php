<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tarifaVerModelo.php';

class tarifaVerControlador {

    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TarifaVerModelo($this->db);
    }

    public function index() {
        $tarifas = $this->modelo->obtenerTarifas();
        $data = ['titulo' => 'Listado de Tarifas', 'tarifas' => $tarifas];
        $vistaContenido = "app/views/admin/tarifaVerVista.php";
        include "app/views/plantillaVista.php";
    }
}