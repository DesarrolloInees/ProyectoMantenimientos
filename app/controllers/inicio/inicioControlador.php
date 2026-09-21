<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/inicio/inicioModelo.php';
require_once __DIR__ . '/../../helpers/resumenTiposServicio.php';

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

        // Resumen del mes actual para el técnico logueado (nivel 3).
        // Usa la misma clasificación que el reporte técnico.
        $resumenTecnicoMes = ResumenTiposServicio::vacio();
        $rangoTecnicoMes = ['inicio' => date('Y-m-01'), 'fin' => date('Y-m-d')];
        if (isset($_SESSION['nivel_acceso']) && (int)$_SESSION['nivel_acceso'] === 3) {
            $idTecnicoSesion = $this->modelo->obtenerIdTecnicoPorUsuario($_SESSION['usuario_id'] ?? 0);
            if ($idTecnicoSesion > 0) {
                $filas = $this->modelo->resumenServiciosPorTipo(
                    $idTecnicoSesion,
                    $rangoTecnicoMes['inicio'],
                    $rangoTecnicoMes['fin']
                );
                // Expandimos el GROUP BY a filas individuales para reutilizar el helper.
                $expandidas = [];
                foreach ($filas as $f) {
                    $n = isset($f['total']) ? (int)$f['total'] : 0;
                    for ($i = 0; $i < $n; $i++) {
                        $expandidas[] = ['tipo_mantenimiento' => $f['tipo_mantenimiento'] ?? ''];
                    }
                }
                $resumenTecnicoMes = ResumenTiposServicio::resumir($expandidas);
            }
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