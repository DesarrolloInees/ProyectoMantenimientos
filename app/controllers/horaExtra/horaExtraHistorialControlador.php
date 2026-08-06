<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/horaExtra/horaExtraHistorialModelo.php';

class horaExtraHistorialControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new horaExtraHistorialModelo($this->db);
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

        // Obtener historial de horas extra
        $reportesHE = $this->modelo->obtenerHistorialHorasExtraPorTecnico($idTecnicoActual);

        // Cargar Vista
        $titulo = "Mis Horas Extra";
        $vistaContenido = "app/views/horaExtra/horaExtraHistorialVista.php";
        include "app/views/plantillaVista.php";
    }
}