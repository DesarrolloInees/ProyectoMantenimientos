<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/costos/costosAdministrativosEditarModelo.php';

class costosAdministrativosEditarControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new costosAdministrativosEditarModelo($this->db);
    }

    public function index($mensajeExito = "", $errores = [])
    {
        // 1. Configuración del Mes (Por defecto el actual o el que venga por GET/POST)
        $mesSeleccionado = $_REQUEST['mes_reporte'] ?? date('Y-m');
        $fechaReporteSQL = $mesSeleccionado . "-01";

        // 2. Eliminar Gasto Individual (si se solicita)
        if (isset($_GET['eliminar_gasto_id'])) {
            if ($this->modelo->eliminarGasto($_GET['eliminar_gasto_id'])) {
                $mensajeExito = "Gasto eliminado.";
            }
        }

        // 3. Obtener Datos para poblar los inputs
        // Nómina: Trae usuarios + lo que ya tengan guardado
        $listaNomina = $this->modelo->obtenerNominaCompletaPorMes($fechaReporteSQL);
        // Gastos: Trae la lista de gastos
        $listaGastos = $this->modelo->obtenerGastosPorMes($fechaReporteSQL);

        // 4. Vista
        $titulo = "Editar Costos Administrativos";
        $vistaContenido = "app/views/costos/costosAdministrativosEditarVista.php";
        include "app/views/plantillaVista.php";
    }

    // ACCTIÓN 1: Actualizar toda la tabla de nómina de golpe
    public function actualizar_nomina()
    {
        $mesSeleccionado = $_POST['mes_reporte'];
        $fechaReporteSQL = $mesSeleccionado . "-01";
        $datosNomina = $_POST['nomina'] ?? []; // Array masivo [id_usuario => [datos]]

        if (!empty($datosNomina)) {
            if ($this->modelo->actualizarNominaMasiva($fechaReporteSQL, $datosNomina)) {
                $this->index("Nómina administrativa actualizada exitosamente.");
            } else {
                $this->index("", ["Error al actualizar la base de datos."]);
            }
        } else {
            $this->index("", ["No se enviaron datos para actualizar."]);
        }
    }

    // ACCTIÓN 2: Actualizar la tabla de gastos generales de golpe
    public function actualizar_gastos_generales()
    {
        $mesSeleccionado = $_POST['mes_reporte'];
        $datosGastos = $_POST['gastos_generales'] ?? []; // Array masivo [id_gasto => [datos]]

        if (!empty($datosGastos)) {
            if ($this->modelo->actualizarGastosMasivos($datosGastos)) {
                $this->index("Gastos generales actualizados correctamente.");
            } else {
                $this->index("", ["Error al actualizar gastos generales."]);
            }
        } else {
            $this->index("", ["No hay gastos para actualizar."]);
        }
    }
}