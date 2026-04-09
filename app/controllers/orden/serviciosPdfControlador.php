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
        
        // 1. Obtener el rol del usuario correctamente
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // AQUÍ USAMOS nivel_acceso COMO EN EL LOGIN
        $idRolUsuario = isset($_SESSION['nivel_acceso']) ? $_SESSION['nivel_acceso'] : null;
        
        // 2. Lógica de restricción
        $mesesRestriccion = 0;
        if ($idRolUsuario == 4) {
            $paramValor = $this->modelo->obtenerParametro('meses_restriccion_prosegur');
            $mesesRestriccion = $paramValor ? (int)$paramValor : 1; // 1 mes por defecto
        }
        
        // 3. Pasar los datos al modelo
        $datos = $this->modelo->listarServiciosParaPdf($idRolUsuario, $mesesRestriccion);
        
        echo json_encode(['data' => $datos], JSON_UNESCAPED_UNICODE);
        exit;
    }
}