<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenVerModelo.php';

class ordenVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ordenVerModelo($this->db);
    }

    public function index()
    {
        $this->cargarVista();
    }

    public function cargarVista()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $titulo = "Historial por Días";
        // Cambia 'idTipoUsuario' por 'nivel_acceso'
        $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int)$_SESSION['nivel_acceso'] : 0; 
        
        $vistaContenido = "app/views/orden/ordenVerVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxListar()
    {
        // Limpiamos el buffer para asegurar que el JSON no se corrompa
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int)$_SESSION['nivel_acceso'] : 0;
            
        // El modelo trae los datos limpios
        $datos = $this->modelo->listarOrdenesPorFecha();
        
        // SEGURIDAD: Si el rol es 5, ponemos todos los valores monetarios en cero
        if ($rolUsuario === 5) {
            foreach ($datos as &$dia) {
                $dia['valor_total_dia'] = 0;
                foreach ($dia['detalles_delegacion'] as &$det) {
                    $det['valor'] = 0;
                }
            }
        }
        
        echo json_encode(['data' => $datos], JSON_UNESCAPED_UNICODE);
        exit;
    }
}