<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/programacion/programacionConsolidadoModelo.php';

class programacionConsolidadoControlador
{
    private $modelo;

    public function __construct()
    {
        $db = (new Conexion())->getConexion();
        $this->modelo = new programacionConsolidadoModelo($db);
    }

    public function index()
    {
        // Valores por defecto: Semana actual
        // En programacionConsolidadoControlador.php
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('monday this week'));
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d', strtotime('sunday this week'));
        $delegacion = $_GET['delegacion'] ?? '';
        $tecnico = $_GET['tecnico'] ?? '';

        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaModalidades = $this->modelo->obtenerModalidades();

        $consolidado = $this->modelo->obtenerConsolidadoRutas($fecha_inicio, $fecha_fin, $delegacion, $tecnico);

        $titulo = "Consolidado General de Programación";
        $vistaContenido = "app/views/programacion/programacionConsolidadoVista.php";
        include "app/views/plantillaVista.php";
    }


    public function exportarExcelConsolidado()
    {
        // 1. Ejecutar la restauración automática de las máquinas que faltaron por programar
        $resRestauracion = $this->modelo->restaurarMaquinasRestantesAOperativo();

        // 2. Proceder con la lógica habitual de consulta e impresión/generación del Excel...
        // $datosExcel = $this->modelo->obtenerConsolidadoRutas(...);
    }

    /**
     * AJAX: borra una orden programada (estado = 2) de ordenes_servicio
     * antes de descargar el Excel maestro.
     */
    public function eliminarOrden()
    {
        header('Content-Type: application/json; charset=utf-8');

        $idOrden = $_POST['id_orden'] ?? '';
        $resultado = $this->modelo->eliminarOrdenProgramada($idOrden);
        echo json_encode($resultado);
        exit;
    }

    /**
     * AJAX: actualiza una orden programada (estado = 2) de ordenes_servicio
     * Permite editar: punto, técnico, fecha, cliente, máquina, modalidad.
     */
    public function actualizarOrden()
    {
        header('Content-Type: application/json; charset=utf-8');

        $idOrden = $_POST['id_orden'] ?? '';
        $idPunto = $_POST['id_punto'] ?? '';
        $idTecnico = $_POST['id_tecnico'] ?? '';
        $idCliente = $_POST['id_cliente'] ?? '';
        $idMaquina = $_POST['id_maquina'] ?? '';
        $idModalidad = $_POST['id_modalidad'] ?? '';
        $fechaVisita = $_POST['fecha_visita'] ?? '';

        if (empty($idOrden) || empty($idPunto) || empty($idTecnico) || empty($idCliente) || empty($fechaVisita)) {
            echo json_encode(['status' => false, 'msg' => 'Faltan campos obligatorios.']);
            exit;
        }

        $resultado = $this->modelo->actualizarOrden($idOrden, $idPunto, $idTecnico, $idCliente, $idMaquina, $idModalidad, $fechaVisita);
        echo json_encode($resultado);
        exit;
    }

    /**
 * AJAX: obtiene los clientes asociados a un punto específico
 */
public function obtenerClientesPorPunto() {
    header('Content-Type: application/json; charset=utf-8');
    
    $id_punto = intval($_POST['id_punto'] ?? 0);
    $id_cliente_excluir = intval($_POST['id_cliente_excluir'] ?? 0);
    
    if ($id_punto <= 0) {
        echo json_encode(['status' => false, 'msg' => 'Punto inválido.']);
        exit;
    }
    
    $datos = $this->modelo->obtenerClientesPorPunto($id_punto, $id_cliente_excluir);
    echo json_encode(['status' => true, 'datos' => $datos]);
    exit;
}

    /**
     * AJAX: lista de clientes activos con al menos un punto activo.
     * Alimenta el select de cliente del modal de edición.
     */
    public function listarClientesEditar()
    {
        header('Content-Type: application/json; charset=utf-8');

        $clientes = $this->modelo->obtenerClientesParaEditar();
        echo json_encode(['status' => true, 'clientes' => $clientes], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AJAX: obtiene los puntos de un cliente específico
     */
    public function obtenerPuntosCliente()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id_cliente = intval($_POST['id_cliente'] ?? 0);
        $id_punto_actual = intval($_POST['id_punto_actual'] ?? $_POST['id_punto_excluir'] ?? 0);
        $id_orden = intval($_POST['id_orden'] ?? 0);

        if ($id_cliente <= 0) {
            echo json_encode(['status' => false, 'msg' => 'Cliente inválido.']);
            exit;
        }

        $puntos = $this->modelo->obtenerPuntosPorCliente($id_cliente, $id_punto_actual, $id_orden);

        $modalidades = $this->modelo->obtenerModalidades();

        echo json_encode([
            'status' => true,
            'puntos' => $puntos,
            'id_punto_actual' => $id_punto_actual,
            'modalidades' => $modalidades
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
