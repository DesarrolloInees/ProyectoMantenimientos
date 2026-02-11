<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/costos/costosAdministrativosCrearModelo.php';

class costosAdministrativosCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new costosAdministrativosCrearModelo($this->db);
    }

    // ==========================================================
    // MÉTODO PRINCIPAL (Carga la vista y listas)
    // ==========================================================
    public function index($mensajeExito = "", $errores = [])
    {
        // 1. Configuración del Mes
        $mesActual = date('Y-m');
        $mesSeleccionado = $_REQUEST['mes_reporte'] ?? $mesActual;
        $fechaReporteSQL = $mesSeleccionado . "-01";

        // 2. Procesar ELIMINAR (Viene por GET, así que entra al index)
        if (isset($_GET['eliminar_id'])) {
            $idEliminar = intval($_GET['eliminar_id']);
            if ($this->modelo->eliminarGasto($idEliminar)) {
                $mensajeExito = "Gasto eliminado correctamente.";
            }
        }

        // 3. Cargar datos para la vista
        $listaPersonal = $this->modelo->obtenerPersonalAdmin();
        $listaGastos = $this->modelo->obtenerGastosPorMes($fechaReporteSQL);

        // 4. Calcular total
        $totalGastos = 0;
        foreach ($listaGastos as $g) { $totalGastos += $g['valor']; }

        // 5. Cargar la vista
        $titulo = "Gestión de Costos Administrativos";
        $vistaContenido = "app/views/costos/costosAdministrativosCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    // ==========================================================
    // MÉTODO 2: GUARDAR NÓMINA (Llamado por el Router)
    // ==========================================================
    public function guardar_nomina()
    {
        $mesSeleccionado = $_POST['mes_reporte'] ?? date('Y-m');
        $fechaReporteSQL = $mesSeleccionado . "-01";
        $datosCostos = $_POST['costos'] ?? [];
        
        $mensajeExito = "";
        $errores = [];

        if (!empty($datosCostos)) {
            if ($this->modelo->guardarNominaAdmin($fechaReporteSQL, $datosCostos)) {
                $mensajeExito = "Nómina administrativa actualizada correctamente.";
            } else {
                $errores[] = "Error al guardar la nómina en la base de datos.";
            }
        } else {
            $errores[] = "No se recibieron datos para guardar.";
        }

        // Volvemos a cargar el index pasando los mensajes
        $this->index($mensajeExito, $errores);
    }

    // ==========================================================
    // MÉTODO 3: GUARDAR GASTO (Llamado por el Router)
    // ==========================================================
    public function guardar_gasto()
    {
        $mesSeleccionado = $_POST['mes_reporte'] ?? date('Y-m');
        $fechaReporteSQL = $mesSeleccionado . "-01";
        
        $concepto = trim($_POST['concepto'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $valor = floatval($_POST['valor'] ?? 0);

        $mensajeExito = "";
        $errores = [];

        if (empty($concepto) || $valor <= 0) {
            $errores[] = "Debes ingresar un concepto y un valor mayor a 0.";
        } else {
            $datos = [
                'mes_reporte' => $fechaReporteSQL,
                'concepto' => $concepto,
                'categoria' => $categoria,
                'valor' => $valor
            ];
            
            if ($this->modelo->crearGasto($datos)) {
                $mensajeExito = "Gasto agregado exitosamente.";
            } else {
                $errores[] = "Error al guardar el gasto.";
            }
        }

        // Volvemos a cargar el index pasando los mensajes
        $this->index($mensajeExito, $errores);
    }
}