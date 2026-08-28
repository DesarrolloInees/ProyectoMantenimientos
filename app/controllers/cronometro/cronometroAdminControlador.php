<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/cronometro/cronometroAdminModelo.php';

class cronometroAdminControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new cronometroAdminModelo($this->db);
    }

    public function index()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            echo "<script>alert('Sesión expirada.'); window.location.href='index.php';</script>";
            return;
        }

        // Filtros (Por defecto día actual)
        $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d');
        $fechaFin    = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
        $idTecnico   = isset($_GET['id_tecnico']) ? $_GET['id_tecnico'] : '';
        $estado      = isset($_GET['estado_actual']) ? $_GET['estado_actual'] : '';

        // 1. Obtener catálogo dinámico desde la tabla parametros
        $catalogoTiemposDB = $this->modelo->obtenerCatalogoTiempos();
        $catalogoTiempos = !empty($catalogoTiemposDB) ? $catalogoTiemposDB : [
            1 => 60,  // Preventivo
            2 => 120, // Correctivo
            3 => 45,  // Diagnóstico
            4 => 20   // Instalación
        ];

        // 2. Obtener datos
        $tecnicos = $this->modelo->obtenerTecnicos();
        $servicios = $this->modelo->obtenerServiciosAdmin($fechaInicio, $fechaFin, $idTecnico, $estado);

        // 3. Mapear 'tiempo_estimado_minutos' y verificar retrasos en vivo
        foreach ($servicios as &$s) {
            $idTipo = (int) $s['id_tipo_mantenimiento'];
            $s['tiempo_estimado_minutos'] = isset($catalogoTiempos[$idTipo]) ? (int) $catalogoTiempos[$idTipo] : 60;

            // --- ALERTA EN TIEMPO REAL A TELEGRAM ---
            if ($s['estado_actual'] === 'En Progreso') {
                $inicio = strtotime($s['hora_inicio']);
                $ahora = time();
                $minutosTranscurridos = floor(($ahora - $inicio) / 60);

                // Si excedió la meta y no se ha notificado en esta sesión
                $keyAlerta = 'alerta_retraso_' . $s['id_monitoreo'];
                if ($minutosTranscurridos > $s['tiempo_estimado_minutos'] && !isset($_SESSION[$keyAlerta])) {
                    
                    $msg = "⚠️ <b>ALERTA DE RETRASO EN VIVO</b>\n\n";
                    $msg .= "<b>Técnico:</b> " . htmlspecialchars($s['nombre_tecnico']) . "\n";
                    $msg .= "<b>Servicio:</b> " . htmlspecialchars($s['tipo_mantenimiento']) . "\n";
                    $msg .= "<b>Punto:</b> " . htmlspecialchars($s['nombre_punto']) . "\n";
                    $msg .= "<b>Transcurrido:</b> {$minutosTranscurridos} min (Meta: {$s['tiempo_estimado_minutos']} min)\n";

                    $this->modelo->enviarAlertaTelegram($msg);

                    // Guardar flag en sesión para enviar una sola notificación por servicio
                    $_SESSION[$keyAlerta] = true;
                }
            }
        }
        unset($s); // Romper referencia del foreach

        // 4. Totales para tarjetas de resumen
        $totalActivos = 0;
        $totalFinalizados = 0;
        $totalRetrasados = 0;
        $totalModificados = 0;

        foreach ($servicios as $s) {
            if ($s['estado_actual'] === 'En Progreso') {
                $totalActivos++;
            } else {
                $totalFinalizados++;
                if ((int)$s['duracion_real_minutos'] > (int)$s['tiempo_estimado_minutos']) {
                    $totalRetrasados++;
                }
            }

            if ($s['servicio_modificado'] == 1) {
                $totalModificados++;
            }
        }

        $titulo = "Monitoreo de Servicios (Supervisor)";
        $vistaContenido = "app/views/cronometro/cronometroAdminVista.php";
        include "app/views/plantillaVista.php";
    }
}