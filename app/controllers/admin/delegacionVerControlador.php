<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/delegacionVerModelo.php';

class delegacionVerControlador {

    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new DelegacionVerModelo($this->db);
    }

    public function index() {
        $delegaciones = $this->modelo->obtenerDelegaciones();
        $data = ['titulo' => 'Delegaciones', 'delegaciones' => $delegaciones];
        $vistaContenido = "app/views/admin/delegacionVerVista.php";
        include "app/views/plantillaVista.php";
    }
}