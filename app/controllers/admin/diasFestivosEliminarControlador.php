<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
// Ahora requerimos el modelo de VER, porque ahí metimos la función
require_once __DIR__ . '/../../models/admin/diasFestivosVerModelo.php';

class DiasFestivosEliminarControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        // Instanciamos el modelo de VER
        $this->modelo = new DiasFestivosVerModelo($this->db);
    }

    public function index()
    {
        if (isset($_GET['id'])) {
            // Llamamos a la función que movimos
            $this->modelo->eliminarFestivo($_GET['id']);
        }
        // Redirigir siempre al listado
        header("Location: " . BASE_URL . "diasFestivosVer");
        exit();
    }
}
