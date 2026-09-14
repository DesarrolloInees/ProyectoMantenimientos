<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/inventarioTecnicoVerModelo.php';

class InventarioTecnicoVerControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new InventarioTecnicoVerModelo($this->db);
    }

    public function index()
    {
        $nivelAcceso = isset($_SESSION['nivel_acceso']) ? (int)$_SESSION['nivel_acceso'] : 0;
        $esTecnico = ($nivelAcceso == 3);

        if ($esTecnico) {
            // --- TÉCNICO: Solo ver su propio inventario ---
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            $inventario = [];

            if ($usuarioId) {
                $tecInfo = $this->modelo->obtenerTecnicoPorUsuarioId($usuarioId);
                if ($tecInfo && !empty($tecInfo['id_tecnico'])) {
                    $inventario = $this->modelo->obtenerInventarioPorTecnico($tecInfo['id_tecnico']);
                }
            }

            $titulo = "Mi Inventario";
            $vistaContenido = "app/views/admin/inventarioTecnicoVerVista.php";
            include "app/views/plantillaVista.php";
        } else {
            // --- ADMIN/SUPERVISOR: Ver todo el inventario ---
            $inventario = $this->modelo->obtenerInventarioCompleto();
            $listaTecnicos = $this->modelo->obtenerListaTecnicos();

            $titulo = "Inventario por Técnico";
            $vistaContenido = "app/views/admin/inventarioTecnicoVerVista.php";
            include "app/views/plantillaVista.php";
        }
    }
}