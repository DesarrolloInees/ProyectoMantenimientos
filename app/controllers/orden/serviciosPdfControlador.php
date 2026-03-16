<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/serviciosPdfModelo.php'; // Ajusta la ruta si es necesario

class serviciosPdfControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new serviciosPdfModelo($this->db);
    }

    public function index()
    {
        $this->cargarVista();
    }

    public function cargarVista()
    {
        $titulo = "Generador de PDFs";
        $vistaContenido = "app/views/orden/serviciosPdfVista.php"; // Ajusta la ruta si es necesario
        include "app/views/plantillaVista.php";
    }

    public function ajaxListar()
    {
        ob_clean();
        header('Content-Type: application/json');
        
        $datos = $this->modelo->listarServiciosParaPdf();
        
        echo json_encode(['data' => $datos], JSON_UNESCAPED_UNICODE);
        exit;
    }
}