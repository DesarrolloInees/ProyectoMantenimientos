<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/modalidadOperativaVerModelo.php';

class modalidadOperativaVerControlador {

    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ModalidadOperativaVerModelo($this->db);
    }

    public function index() {
        $modalidades = $this->modelo->obtenerModalidades();
        $data = ['titulo' => 'Modalidades Operativas', 'modalidades' => $modalidades];
        $vistaContenido = "app/views/admin/modalidadOperativaVerVista.php";
        include "app/views/plantillaVista.php";
    }
}