<?php
// app/controllers/orden/ordenMovilControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenMovilModelo.php';

class ordenMovilControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ordenMovilModelo($this->db);
    }

    public function index()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        $listaClientes = $this->modelo->obtenerClientes();
        $titulo = "Consulta Técnica Móvil";
        $vistaContenido = "app/views/orden/ordenMovilVista.php";
        require_once __DIR__ . '/../../views/plantillaVista.php';
    }

    // --- CORRECCIÓN: Métodos públicos directos ---

    // 1. Método llamado por AJAX cuando accion = 'cargarPuntos'
    public function cargarPuntos()
    {
        // Limpiamos buffer por si acaso hay basura
        ob_clean(); 
        
        $idCliente = $_POST['id_cliente'] ?? 0;
        $puntos = $this->modelo->obtenerPuntosPorCliente($idCliente);
        
        // Devolvemos JSON puro
        header('Content-Type: application/json');
        echo json_encode($puntos);
        exit; // Importante detener el script aquí
    }

    // 2. Método llamado por AJAX cuando accion = 'buscarHistorial'
    public function buscarHistorial()
    {
        $idCliente = $_POST['id_cliente'] ?? 0;
        $idPunto   = $_POST['id_punto'] ?? 0;
        
        $resultados = $this->modelo->buscarServicios($idCliente, $idPunto);
        
        // Aquí devolvemos HTML (tarjetas), no JSON
        $this->renderizarTarjetas($resultados);
        exit;
    }

    private function renderizarTarjetas($resultados)
    {
        if (empty($resultados)) {
            echo '<div class="p-4 bg-yellow-50 text-yellow-700 rounded-lg text-center border border-yellow-200 shadow-sm">
                    <i class="fas fa-info-circle mb-2 text-2xl"></i><br>
                    No se encontraron servicios anteriores en este punto.
                  </div>';
            return;
        }

        foreach ($resultados as $row) {
            $colorBorde = 'border-blue-500';
            if (stripos($row['tipo_servicio'], 'CORRECTIVO') !== false) $colorBorde = 'border-orange-500';
            if (stripos($row['tipo_servicio'], 'PREVENTIVO') !== false) $colorBorde = 'border-green-500';
            if (stripos($row['tipo_servicio'], 'FALLIDO') !== false) $colorBorde = 'border-red-500';

            $fecha = date('d/m/Y', strtotime($row['fecha_visita']));
            ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-4 border-l-4 <?= $colorBorde ?>">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-100 flex justify-between items-center">
                    <span class="font-bold text-gray-800 text-sm">
                        <i class="far fa-calendar-alt text-gray-500 mr-1"></i> <?= $fecha ?>
                    </span>
                    <span class="text-xs font-bold px-2 py-1 rounded bg-white border border-gray-200 text-gray-600">
                        <?= $row['tipo_servicio'] ?>
                    </span>
                </div>
                <div class="p-4">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mr-3">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Técnico</p>
                            <p class="text-sm font-semibold text-gray-800 leading-tight"><?= $row['nombre_tecnico'] ?></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-1">Actividades / Observación</p>
                        <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <?= !empty($row['que_se_hizo']) ? nl2br($row['que_se_hizo']) : 'Sin observaciones.' ?>
                        </div>
                    </div>
                    <?php if (!empty($row['repuestos'])): ?>
                    <div class="mt-3 pt-3 border-t border-dashed border-gray-200">
                        <p class="text-xs font-bold text-gray-700 flex items-center gap-1 mb-1">
                            <i class="fas fa-tools text-gray-400"></i> Repuestos:
                        </p>
                        <p class="text-xs text-gray-500 pl-4">
                            <?= $row['repuestos'] ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
    }
}