<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/clienteVerModelo.php';

class clienteVerControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ClienteVerModelo($this->db);
    }

    public function index()
    {
        $clientes = $this->modelo->obtenerClientes();

        $data = [
            'titulo' => 'Gestión de Clientes',
            'clientes' => $clientes
        ];

        $vistaContenido = "app/views/admin/clienteVerVista.php";
        include "app/views/plantillaVista.php";
    }
}
