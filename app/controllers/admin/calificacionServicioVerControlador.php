<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/calificacionServicioVerModelo.php';

class calificacionServicioVerControlador {

    private $modelo;
    private $db;

    public function __construct() {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new CalificacionServicioVerModelo($this->db);
    }

    public function index() {
        $calificaciones = $this->modelo->obtenerCalificaciones();
        $data = ['titulo' => 'Calificaciones de Servicio', 'calificaciones' => $calificaciones];
        $vistaContenido = "app/views/admin/calificacionServicioVerVista.php";
        include "app/views/plantillaVista.php";
    }
}