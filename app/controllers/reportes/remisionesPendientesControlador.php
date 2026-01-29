<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/controlRemisionVerModelo.php';

class RemisionesPendientesControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ControlRemisionVerModelo($this->db);
    }

    public function index()
    {
        // Usamos la nueva lógica de vecindad
        
        $pendientes = $this->modelo->obtenerSalteadasSandwich();

        $titulo = "Remisiones Salteadas (Detectadas por uso posterior)";
        $vistaContenido = "app/views/reportes/remisionesPendientesVista.php";
        include "app/views/plantillaVista.php";
    }

    public function cambiarEstado()
    {
        if (isset($_GET['id']) && isset($_GET['estado'])) {
            $id = $_GET['id'];
            $estado = $_GET['estado'];
            
            // AGREGADO: 'DISPONIBLE' por seguridad si necesitas revertir
            // Asegúrate de que estos nombres sean IDÉNTICOS a los de tu tabla 'estados_remision'
            $estadosPermitidos = ['ANULADA', 'USADA', 'FALTANTE', 'ELIMINADO', 'DISPONIBLE']; 

            if (in_array($estado, $estadosPermitidos)) {
                // Ahora esto funcionará porque el modelo traducirá el texto a ID
                $this->modelo->actualizarEstadoRapido($id, $estado);
            }
        }

        header('Location: ' . BASE_URL . 'remisionesPendientes');
        exit();
    }
}
