<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/tarifaMasivaModelo.php';

class tarifaMasivaControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new TarifaMasivaModelo($this->db);
    }

    public function index()
    {
        $mensaje = null;
        $tipo_mensaje = ""; // success o error
        
        // Variables de filtro
        $id_maquina = $_GET['id_maquina'] ?? "";
        $anio = $_GET['año'] ?? date('Y');
        $tarifas = [];

        // 1. Procesar Guardado Masivo (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_masivo'])) {
            // $_POST['precios'] será un array: [id_tarifa => precio_nuevo, id_tarifa2 => precio2...]
            if (!empty($_POST['precios'])) {
                if ($this->modelo->actualizarPreciosMasivos($_POST['precios'])) {
                    $mensaje = "¡Tarifas actualizadas correctamente!";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Hubo un error al guardar los cambios.";
                    $tipo_mensaje = "error";
                }
            }
            // Mantenemos los filtros para no perder la vista
            $id_maquina = $_POST['filtro_maquina'];
            $anio = $_POST['filtro_anio'];
        }

        // 2. Obtener datos para la vista si hay filtros seleccionados
        if ($id_maquina && $anio) {
            $tarifas = $this->modelo->obtenerTarifasPorFiltro($id_maquina, $anio);
        }

        // Listas para filtros
        $listaMaquinas = $this->modelo->obtenerTiposMaquina();

        $titulo = "Edición Masiva de Tarifas";
        $vistaContenido = "app/views/admin/tarifaMasivaVista.php";
        include "app/views/plantillaVista.php";
    }
}