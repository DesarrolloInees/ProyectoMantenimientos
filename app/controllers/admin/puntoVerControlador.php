<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/puntoVerModelo.php';

class puntoVerControlador {

    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new PuntoVerModelo($this->db);
    }

    public function index() {
        $puntos = $this->modelo->obtenerPuntos();
        $data = ['titulo' => 'Listado de Puntos', 'puntos' => $puntos];
        $vistaContenido = "app/views/admin/puntoVerVista.php";
        include "app/views/plantillaVista.php";
    }
}