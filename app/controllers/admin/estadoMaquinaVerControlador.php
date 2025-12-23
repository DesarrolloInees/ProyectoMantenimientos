<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/estadoMaquinaVerModelo.php';

class estadoMaquinaVerControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new EstadoMaquinaVerModelo($this->db);
    }

    public function index()
    {
        $estados = $this->modelo->obtenerEstados();
        $data = ['titulo' => 'Estados de Máquina', 'estados' => $estados];
        $vistaContenido = "app/views/admin/estadoMaquinaVerVista.php";
        include "app/views/plantillaVista.php";
    }
}
