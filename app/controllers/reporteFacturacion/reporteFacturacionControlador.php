<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
// Asegúrate de que la ruta al modelo coincida con tu estructura
require_once __DIR__ . '/../../models/reporteFacturacion/reporteFacturacionModelo.php'; 

class ReporteFacturacionControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ReporteFacturacionModelo($this->db);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        $titulo = "Analizador de Facturación";
        
        // El controlador solo llama a la vista
        $vistaContenido = "app/views/reporteFacturacion/reporteFacturacionVista.php";
        include "app/views/plantillaVista.php";
    }
}