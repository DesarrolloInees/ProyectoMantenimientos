<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/cronometro/cronometroCrearModelo.php';

class cronometroCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new cronometroCrearModelo($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);

        if (!$datosTecnico || empty($datosTecnico['id_tecnico'])) {
            echo "<script>alert('Tu usuario no está vinculado a un perfil de técnico.'); window.history.back();</script>";
            return;
        }

        // Catálogo centralizado de tiempos (en minutos)
        $catalogoTiempos = [
            1 => 60,  // Preventivo
            2 => 120, // Correctivo
            3 => 45,  // Diagnóstico
            4 => 20   // Instalación
        ];

        $servicioActivo = $this->modelo->obtenerServicioEnProgreso($datosTecnico['id_tecnico']);

        // Si hay un servicio en curso, le asignamos la meta basada en su tipo
        if ($servicioActivo) {
            $idTipo = (int) $servicioActivo['id_tipo_mantenimiento'];
            $servicioActivo['tiempo_estimado_minutos'] = isset($catalogoTiempos[$idTipo]) ? $catalogoTiempos[$idTipo] : 60;
        }

        // Cambia esta parte en tu función index():
        $clientes = [];
        $puntos = [];
        // Sacamos $tiposMantenimiento del if, lo necesitamos SIEMPRE
        $tiposMantenimiento = $this->modelo->obtenerTiposMantenimiento();

        if (!$servicioActivo) {
            $clientes = $this->modelo->obtenerClientesActivos();
            $puntos = $this->modelo->obtenerPuntosActivos();
        }
        $titulo = "Cronómetro de Servicio";
        $vistaContenido = "app/views/cronometro/cronometroCrearVista.php";
        include "app/views/plantillaVista.php";
    }
    public function iniciar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
            $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);

            if (!$datosTecnico)
                return;

            $idCliente = !empty($_POST['id_cliente']) ? (int) $_POST['id_cliente'] : null;
            $idPunto = !empty($_POST['id_punto']) ? (int) $_POST['id_punto'] : null;
            $idTipoMantenimiento = !empty($_POST['id_tipo_mantenimiento']) ? (int) $_POST['id_tipo_mantenimiento'] : null;

            if (!$idCliente || !$idPunto || !$idTipoMantenimiento) {
                echo "<script>alert('⚠️ Faltan datos.'); window.history.back();</script>";
                return;
            }

            // === DEFINICIÓN DE TIEMPOS ESTIMADOS (EN MINUTOS) ===
            // Ajusta los IDs según tu tabla tipo_mantenimiento
            $catalogoTiempos = [
                1 => 60,  // Mantenimiento Preventivo
                2 => 120, // Mantenimiento Correctivo
                3 => 45,  // Diagnóstico
                4 => 90   // Instalación
            ];

            // Si el ID no está en el array, asignamos 60 minutos por defecto
            $tiempoEstimado = isset($catalogoTiempos[$idTipoMantenimiento]) ? $catalogoTiempos[$idTipoMantenimiento] : 60;

            date_default_timezone_set('America/Bogota');
            $fechaActual = date('Y-m-d');
            $horaExacta = date('Y-m-d H:i:s');

            $datosGuardar = [
                'id_tecnico' => $datosTecnico['id_tecnico'],
                'id_cliente' => $idCliente,
                'id_punto' => $idPunto,
                'id_tipo_mantenimiento' => $idTipoMantenimiento,
                'tiempo_estimado' => $tiempoEstimado,
                'fecha_servicio' => $fechaActual,
                'hora_inicio' => $horaExacta
            ];

            $exito = $this->modelo->iniciarCronometro($datosGuardar);

            if ($exito) {
                header("Location: index.php?pagina=cronometroCrear");
            } else {
                echo "<script>alert('❌ Error al iniciar.'); window.history.back();</script>";
            }
        }
    }

    public function finalizar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idMonitoreo = !empty($_POST['id_monitoreo']) ? (int) $_POST['id_monitoreo'] : null;
            $horaInicioReal = !empty($_POST['hora_inicio_real']) ? $_POST['hora_inicio_real'] : null;
            $idTipoMantenimientoFinal = !empty($_POST['id_tipo_mantenimiento_final']) ? (int) $_POST['id_tipo_mantenimiento_final'] : null;

            // NUEVO: Capturamos el inicial y la justificación
            $idTipoMantenimientoInicial = !empty($_POST['id_tipo_mantenimiento_inicial']) ? (int) $_POST['id_tipo_mantenimiento_inicial'] : null;
            $justificacion = !empty($_POST['justificacion_retraso']) ? trim($_POST['justificacion_retraso']) : null;

            if (!$idMonitoreo || !$horaInicioReal || !$idTipoMantenimientoFinal) {
                echo "<script>alert('⚠️ Faltan datos para finalizar.'); window.history.back();</script>";
                return;
            }

            date_default_timezone_set('America/Bogota');
            $horaFinExacta = date('Y-m-d H:i:s');

            $inicio = new DateTime($horaInicioReal);
            $fin = new DateTime($horaFinExacta);
            $diferencia = $inicio->diff($fin);

            $minutosReales = ($diferencia->days * 24 * 60) + ($diferencia->h * 60) + $diferencia->i;

            // Calculamos si el servicio fue modificado
            $servicioModificado = ($idTipoMantenimientoFinal !== $idTipoMantenimientoInicial) ? 1 : 0;

            // Pasamos los nuevos campos al modelo
            $exito = $this->modelo->finalizarCronometro($idMonitoreo, $horaFinExacta, $minutosReales, $idTipoMantenimientoFinal, $servicioModificado, $justificacion);

            if ($exito) {
                echo "<script>alert('✅ Servicio finalizado con éxito. Tiempo: {$minutosReales} min.'); window.location.href='index.php?pagina=cronometroHistorial';</script>";
            } else {
                echo "<script>alert('❌ Error al finalizar.'); window.history.back();</script>";
            }
        }
    }

    public function actualizarTipoVivo()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idMonitoreo = !empty($_POST['id_monitoreo']) ? (int) $_POST['id_monitoreo'] : 0;
            $nuevoTipo = !empty($_POST['id_tipo_mantenimiento']) ? (int) $_POST['id_tipo_mantenimiento'] : 0;

            if ($idMonitoreo > 0 && $nuevoTipo > 0) {
                $exito = $this->modelo->actualizarTipoMantenimientoLive($idMonitoreo, $nuevoTipo);
                
                if ($exito) {
                    echo json_encode(['status' => 'success', 'message' => 'Tipo de servicio modificado correctamente (1/1).']);
                    exit;
                } else {
                    echo json_encode(['status' => 'limit_reached', 'message' => 'Ya has alcanzado el límite máximo de 1 modificación para este servicio.']);
                    exit;
                }
            }
        }
        
        echo json_encode(['status' => 'error', 'message' => 'No se pudo procesar la solicitud.']);
        exit;
    }
}