<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/programacion/programacionMapaModelo.php';

class programacionMapaControlador
{
    private $modelo;

    public function __construct()
    {
        $db = (new Conexion())->getConexion();
        $this->modelo = new programacionMapaModelo($db);
    }

    public function index()
    {
        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        
        $delegacionSeleccionada = $_REQUEST['delegacion'] ?? '';
        $listaZonas = [];

        if (!empty($delegacionSeleccionada)) {
            $listaZonas = $this->modelo->obtenerZonasPorDelegacion($delegacionSeleccionada);
        }

        $titulo = "Beta: Programación por Mapa";
        $vistaContenido = "app/views/programacion/programacionMapaVista.php";
        include "app/views/plantillaVista.php";
    }

    // Endpoint AJAX para Leaflet
    public function ajax_obtener_puntos()
    {
        header('Content-Type: application/json');
        
        $delegacion = $_POST['delegacion'] ?? '';
        $zonas = $_POST['zonas'] ?? [];

        if (empty($delegacion) || empty($zonas)) {
            echo json_encode(['success' => false, 'msj' => 'Faltan parámetros']);
            exit;
        }

        $puntos = $this->modelo->obtenerPuntosMapa($delegacion, $zonas);
        echo json_encode(['success' => true, 'data' => $puntos]);
        exit;
    }
}