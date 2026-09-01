<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/tecnico/tecnicoReporteModelo.php';
require_once __DIR__ . '/../../models/orden/ordenCrearModelo.php';

class tecnicoReporteEditarControlador
{
    private $modelo;
    private $modeloMaestro;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new tecnicoReporteModelo($this->db);
        $this->modeloMaestro = new ordenCrearModels($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        $idOrden = isset($_GET['orden']) ? (int) $_GET['orden'] : 0;

        if ($idOrden === 0 || $idUsuarioLogueado === 0) {
            echo "<script>alert('Orden no válida.'); window.history.back();</script>";
            return;
        }

        $idTecnicoActual = $this->modelo->obtenerIdTecnicoPorUsuario($idUsuarioLogueado);
        
        // Obtenemos la orden directamente para edición sin importar si ya está finalizada
        $orden = $this->modelo->obtenerDetalleOrdenParaEdicion($idOrden);

        if (!$orden) {
            echo "<script>alert('La orden no existe en el sistema.'); window.history.back();</script>";
            return;
        }

        // 🔥 PRECARGA DE DATOS EXISTENTES
        $reporteGuardado = $this->modelo->obtenerReporteGuardado($idOrden);
        if (!$reporteGuardado) {
            echo "<script>alert('Esta orden aún no tiene un reporte guardado para editar.'); window.history.back();</script>";
            return;
        }

        $repuestosGuardados = $this->modelo->obtenerRepuestosGuardados($idOrden);

        // Listas para selects
        $remisiones = $this->modelo->obtenerRemisionesTecnico($idTecnicoActual > 0 ? $idTecnicoActual : $orden['id_tecnico']);
        $estados = $this->modeloMaestro->obtenerEstadosMaquina();
        $calificaciones = $this->modeloMaestro->obtenerCalificaciones();
        $tiposManto = $this->modelo->obtenerTiposMantenimientoTecnico();
        $inventario = $this->modelo->obtenerTodosLosRepuestos();

        $titulo = "Editar Reporte de Servicio";
        $vistaContenido = "app/views/tecnico/tecnicoReporteEditarVista.php";
        include "app/views/plantillaVista.php";
    }

    public function actualizar()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'msj' => 'Método no permitido.']);
            exit;
        }

        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
        $idTecnicoActual = $this->modelo->obtenerIdTecnicoPorUsuario($idUsuarioLogueado);
        $idOrdenServicio = (int) ($_POST['id_ordenes_servicio'] ?? 0);

        // Armamos el array de datos principales (Idéntico a Crear, pero referenciando a Editar)
        $datos = [
            'id_ordenes_servicio'   => $idOrdenServicio,
            'id_tecnico'            => $idTecnicoActual,
            'numero_remision'       => $_POST['numero_remision'] ?? '',
            'hora_entrada'          => $_POST['hora_entrada'] ?? '',
            'hora_salida'           => $_POST['hora_salida'] ?? '',
            'tiempo_servicio'       => $_POST['tiempo_servicio'] ?? '',
            'actividades_realizadas'=> $_POST['actividades_realizadas'] ?? '',
            'id_estado_maquina'     => $_POST['id_estado_maquina'] ?? null,
            'id_calificacion'       => !empty($_POST['id_calificacion']) ? $_POST['id_calificacion'] : null,
            'id_tipo_mantenimiento' => $_POST['id_tipo_mantenimiento'] ?? null,
            'soporte_remoto'        => !empty($_POST['soporte_remoto']) ? $_POST['soporte_remoto'] : null,
            'tiene_novedad'         => isset($_POST['tiene_novedad']) ? 1 : 0,
            'detalle_novedad'       => !empty($_POST['detalle_novedad']) ? $_POST['detalle_novedad'] : null,
            'repuestos_tecnico'     => !empty($_POST['json_repuestos']) ? $_POST['json_repuestos'] : null
        ];

        $datosComplementarios = [
            'id_orden_servicio'    => $idOrdenServicio,
            'numero_maquina'       => $_POST['numero_maquina'] ?? null,
            'serial_maquina'       => $_POST['serial_maquina'] ?? null,
            'serial_router'        => $_POST['serial_router'] ?? null,
            'serial_ups'           => $_POST['serial_ups'] ?? null,
            'pendientes'           => $_POST['pendientes'] ?? null,
            'administrador_punto'  => $_POST['administrador_punto'] ?? null,
            'celular_encargado'    => $_POST['celular_encargado'] ?? null,
            'id_estado_inicial'    => $_POST['id_estado_inicial'] ?? null
        ]; // NOTA: No pasamos GPS aquí.

        // Realizar los UPDATES
        $this->modelo->actualizarDatosComplementarios($datosComplementarios);
        $this->modelo->actualizarFechaModificacion($idOrdenServicio);

        if ($this->modelo->actualizarReporteTecnico($datos)) {

            // Procesar nueva Firma Canvas (Solo si el técnico dibujó una nueva)
            if (!empty($_POST['firma_base64'])) {
                $remisionCarpeta = !empty($datos['numero_remision']) ? $datos['numero_remision'] : 'SIN_REMISION_' . $idOrdenServicio;
                $carpetaDestino = __DIR__ . '/../../uploads/imagenes_servicios/' . $remisionCarpeta . '/';
                if (!file_exists($carpetaDestino)) mkdir($carpetaDestino, 0777, true);

                $partesFirma = explode(',', $_POST['firma_base64']);
                if (count($partesFirma) === 2) {
                    $firmaDecodificada = base64_decode($partesFirma[1]);
                    $nombreFirma = 'REM-' . $remisionCarpeta . '_firma_EDIT_' . uniqid() . '.png';
                    
                    if (file_put_contents($carpetaDestino . $nombreFirma, $firmaDecodificada)) {
                        $rutaBDFirma = 'uploads/imagenes_servicios/' . $remisionCarpeta . '/' . $nombreFirma;
                        // Borrar firma anterior si existe y guardar nueva
                        $this->modelo->eliminarEvidenciaPorTipo($idOrdenServicio, 'firma');
                        $this->modelo->guardarEvidenciaFoto($idOrdenServicio, 'firma', $rutaBDFirma);
                    }
                }
            }

            // Procesar Repuestos modificados
            if (!empty($_POST['json_repuestos'])) {
                $repuestosUsados = json_decode($_POST['json_repuestos'], true);
                $this->modelo->limpiarRepuestosOrden($idOrdenServicio);
                if (is_array($repuestosUsados)) {
                    foreach ($repuestosUsados as $rep) {
                        $this->modelo->guardarRepuestoOrden($idOrdenServicio, (int)$rep['id'], (int)$rep['cantidad'], $rep['origen']);
                    }
                }
            }

            echo json_encode(['success' => true, 'msj' => 'Reporte editado y actualizado correctamente.']);
            exit;
        } else {
            echo json_encode(['success' => false, 'msj' => 'Error al actualizar la base de datos.']);
            exit;
        }
    }
}