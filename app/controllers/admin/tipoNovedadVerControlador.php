<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tipoNovedadVerModelo.php';

class TipoNovedadVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TipoNovedadVerModelo($this->db);
    }

    public function index()
    {
        // Obtenemos todos los registros
        $novedadesRaw = $this->modelo->obtenerTodos();

        // TRANSFORMACIÓN: Aseguramos que se vean en Capitalize en la tabla
        $novedades = array_map(function($item) {
            $item['nombre_novedad'] = mb_convert_case($item['nombre_novedad'], MB_CASE_TITLE, "UTF-8");
            return $item;
        }, $novedadesRaw);

        $titulo = "Gestión de Tipos de Novedad";
        $vistaContenido = "app/views/admin/tipoNovedadVerVista.php";
        include "app/views/plantillaVista.php";
    }
}