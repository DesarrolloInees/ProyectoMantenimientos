<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/costos/costosVerModelo.php';

class costosVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new CostosVerModelo($this->db);
    }

    // PANTALLA 1: Lista de Meses (Los grupitos)
    public function index()
    {
        $listaMeses = $this->modelo->obtenerMesesAgrupados();

        $titulo = "Resumen de Costos";
        $vistaContenido = "app/views/costos/costosVerVista.php"; // Ojo al nombre nuevo
        include "app/views/plantillaVista.php";
    }

    // PANTALLA 2: Detalle de un mes específico (Cuando das click en Ver)
    // Se llamaría algo así: midominio.com/costosVerDetalle/2023-10
    public function detalle($fechaMes = null)
    {
        if (!$fechaMes) {
            header('Location: ' . BASE_URL . 'costosEditar'); // Si no hay fecha, regresar
            exit;
        }

        $detalles = $this->modelo->obtenerDetallePorMes($fechaMes);

        $data = [
            'mes' => $fechaMes,
            'detalless' => $detalles
        ];

        $titulo = "Detalle Costos " . $fechaMes;
        $vistaContenido = "app/views/costos/costosEditarVista.php"; 
        include "app/views/plantillaVista.php";
    }

    /**
     * Procesa la eliminación lógica de un registro específico
     * Se puede llamar vía GET: index.php?pagina=costosVer&accion=eliminar&id=123&mes=2023-10
     */
    public function eliminar()
    {
        // 1. Verificar si tenemos el ID
        if (!isset($_GET['id'])) {
            // Si no hay ID, devolvemos a la lista principal
            header('Location: ' . BASE_URL . 'costosVer');
            exit;
        }

        $id_costo = $_GET['id'];
        
        // 2. Ejecutar el borrado lógico en el modelo
        $resultado = $this->modelo->eliminarCosto($id_costo);

        // 3. Redireccionar
        // Capturamos el mes para devolver al usuario a la vista de detalle de ese mes
        $mesVolver = isset($_GET['mes']) ? $_GET['mes'] : null;

        if ($resultado) {
            // Opción A: Si usas sesiones para mensajes flash
            // $_SESSION['mensaje'] = "Registro eliminado correctamente.";
        } else {
            // $_SESSION['error'] = "No se pudo eliminar el registro.";
        }

        if ($mesVolver) {
            // Volver al detalle del mes
            header('Location: ' . BASE_URL . 'costosVer/detalle/' . $mesVolver);
        } else {
            // Volver a la lista general
            header('Location: ' . BASE_URL . 'costosVer');
        }
        exit;
    }

    public function eliminarMes()
    {
        $mes = $_GET['mes'] ?? null;
        if($mes){
            $this->modelo->eliminarMesCompleto($mes);
        }
        header('Location: ' . BASE_URL . 'costosVer');
        exit;
    }
    
}