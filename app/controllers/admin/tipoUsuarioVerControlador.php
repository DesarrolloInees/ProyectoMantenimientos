<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoUsuarioVerModelo.php';

class tipoUsuarioVerControlador {

    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoUsuarioVerModelo($this->db);
    }

    public function index() {
        $tipos = $this->modelo->obtenerTipos();
        $data = ['titulo' => 'Roles de Usuario', 'tipos' => $tipos];
        $vistaContenido = "app/views/admin/tipoUsuarioVerVista.php";
        include "app/views/plantillaVista.php";
    }
}