<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/costos/costosEditarModelo.php';

class costosEditarControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db    = $conexionObj->getConexion();
        $this->modelo = new CostosEditarModelo($this->db);
    }

    public function index()
    {
        // index.php no parsea el segmento para esta ruta, pero $_GET['ruta']
        // siempre contiene la URL completa: "costosEditar/2024-05"
        // La leemos directamente y extraemos el segundo segmento.
        // DESPUÉS (lee de $_GET['mes'])
        $mes = isset($_GET['mes']) ? $_GET['mes'] : null;

        if (!$mes) {
            header('Location: ' . BASE_URL . 'costosVer');
            exit;
        }

        $errores      = [];
        $mensajeExito = "";

        // --- GUARDADO (POST) ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datosCostos = $_POST['costos'] ?? [];

            if (!empty($datosCostos)) {
                if ($this->modelo->actualizarCostos($datosCostos)) {
                    $mensajeExito = "Costos actualizados correctamente.";
                } else {
                    $errores[] = "Error al actualizar la base de datos.";
                }
            } else {
                $errores[] = "No se recibieron datos para actualizar.";
            }
        }

        // --- DATOS ---
        $datosExistentes = $this->modelo->obtenerDatosPorMes($mes);

        if (empty($datosExistentes)) {
            header('Location: ' . BASE_URL . 'costosVer');
            exit;
        }

        // --- VISTA ---
        // extract() pone $mes, $datosExistentes, $errores y $mensajeExito
        // disponibles directamente en la vista incluida por la plantilla
        extract(compact('mes', 'datosExistentes', 'errores', 'mensajeExito'));

        $titulo         = "Editar Costos " . $mes;
        $vistaContenido = "app/views/costos/costosEditarVista.php";
        include "app/views/plantillaVista.php";
    }
}
