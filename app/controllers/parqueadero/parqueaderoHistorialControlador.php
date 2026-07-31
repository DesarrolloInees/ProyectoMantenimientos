<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/parqueadero/parqueaderoHistorialModelo.php';

class ParqueaderoHistorialControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ParqueaderoHistorialModelo($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        $idTecnicoActual = $this->modelo->obtenerIdTecnicoPorUsuario($idUsuarioLogueado);

        if ($idTecnicoActual === 0) {
            echo "<script>alert('Tu usuario no está vinculado a un perfil de técnico.'); window.history.back();</script>";
            return;
        }

        // Obtener las facturas del técnico
        $facturas = $this->modelo->obtenerHistorialPorTecnico($idTecnicoActual);

        // Cargar Vista
        $titulo = "Historial de Parqueaderos";
        $vistaContenido = "app/views/parqueadero/parqueaderoHistorialVista.php";
        include "app/views/plantillaVista.php";
    }
}