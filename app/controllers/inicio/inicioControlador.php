<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/inicio/inicioModelo.php';

class inicioControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->modelo = new inicioModelo($this->db);
    }

    public function index()
    {
        $this->cargarVista();
    }

    public function cargarVista()
    {
        // Solo estadísticas para admin (nivel 1 o 2)
        $estadisticas = [];
        if (isset($_SESSION['nivel_acceso']) && in_array($_SESSION['nivel_acceso'], [1, 2])) {
            $estadisticas = [
                'ordenes_mes' => $this->modelo->totalOrdenesMes(),
                'clientes'     => $this->modelo->totalClientes(),
                'tecnicos'     => $this->modelo->totalTecnicos()
            ];
        }

        $usuario = [
            'nombre' => $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario',
            'rol_id' => $_SESSION['nivel_acceso'] ?? 0,
            'rol_nombre' => $this->getRolNombre($_SESSION['nivel_acceso'] ?? 0)
        ];

        $titulo = "Inicio";
        $vistaContenido = "app/views/inicio/inicioVista.php";
        include "app/views/plantillaVista.php";
    }

    private function getRolNombre($nivel)
    {
        $roles = [
            1 => 'Super Administrador',
            2 => 'Administrador',
            3 => 'Técnico de Campo',
            4 => 'Funcionario Prosegur',
            5 => 'Supervisor Motorizados'
        ];
        return $roles[$nivel] ?? 'Usuario';
    }
}