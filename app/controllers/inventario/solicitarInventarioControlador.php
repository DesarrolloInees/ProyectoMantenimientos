<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/inventario/solicitarInventarioModelo.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class solicitarInventarioControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new solicitarInventarioModelo($this->db);
    }

    public function index($errores = [], $mensajeExito = "")
    {
        $nivelAcceso = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : 0;
        $esTecnico = ($nivelAcceso == 3);

        $vistaSolicitada = $_GET['vista'] ?? '';

        if (!$esTecnico && $vistaSolicitada !== 'crear') {
            $solicitudes = $this->modelo->obtenerSolicitudes();
            $titulo = "Tablero de Solicitudes de Inventario";
            $vistaContenido = "app/views/inventario/solicitarInventarioTableroVista.php";
        } else {
            $listaRepuestos = $this->modelo->obtenerRepuestos();
            $nombreUsuarioLogueado = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario del Sistema';

            if (isset($_SESSION['usuario_id'])) {
                $tecInfo = $this->modelo->obtenerTecnicoPorUsuarioId($_SESSION['usuario_id']);
                if ($tecInfo && !empty($tecInfo['nombre_tecnico'])) {
                    $nombreUsuarioLogueado = $tecInfo['nombre_tecnico'];
                }
            }

            $titulo = "Solicitar Inventario";
            $vistaContenido = "app/views/inventario/solicitarInventarioVista.php";
        }

        include "app/views/plantillaVista.php";
    }

    public function cambiarEstado()
    {
        $errores = [];
        $mensajeExito = "";

        $idSolicitud = intval($_POST['id_solicitud'] ?? 0);
        $nuevoEstado = trim($_POST['nuevo_estado'] ?? '');

        if ($idSolicitud > 0 && !empty($nuevoEstado)) {
            $res = $this->modelo->actualizarEstadoSolicitud($idSolicitud, $nuevoEstado);
            if ($res) {
                $mensajeExito = "La solicitud #$idSolicitud ha cambiado su estado a '$nuevoEstado' con éxito.";
            } else {
                $errores[] = "No se pudo actualizar el estado de la solicitud #$idSolicitud.";
            }
        } else {
            $errores[] = "Datos de cambio de estado no válidos.";
        }

        $this->index($errores, $mensajeExito);
    }

    public function crearSolicitud()
    {
        $errores = [];
        $mensajeExito = "";

        $repuestos = $_POST['repuestos'] ?? [];
        $cantidades = $_POST['cantidades'] ?? [];
        $observaciones = trim($_POST['observaciones'] ?? '');

        if (empty($repuestos) || count($repuestos) === 0) {
            $errores[] = "Debes seleccionar al menos un repuesto para realizar la solicitud.";
        }

        if (empty($errores)) {
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            $solicitanteNombre = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario del Sistema';

            if ($usuarioId) {
                $tecInfo = $this->modelo->obtenerTecnicoPorUsuarioId($usuarioId);
                if ($tecInfo && !empty($tecInfo['nombre_tecnico'])) {
                    $solicitanteNombre = $tecInfo['nombre_tecnico'];
                }
            }

            $idSolicitudGuardada = $this->modelo->guardarSolicitud(
                $usuarioId,
                $solicitanteNombre,
                $observaciones,
                $repuestos,
                $cantidades
            );

            if ($idSolicitudGuardada) {
                $listaRepuestosBD = $this->modelo->obtenerRepuestos();
                $correoEnviado = $this->enviarNotificacionEmail($idSolicitudGuardada, $solicitanteNombre, $repuestos, $cantidades, $observaciones, $listaRepuestosBD);

                if ($correoEnviado) {
                    $mensajeExito = "¡Éxito! Tu solicitud de repuestos (#$idSolicitudGuardada) ha sido registrada en el tablero y enviada a Logística por correo.";
                } else {
                    $mensajeExito = "Tu solicitud de repuestos (#$idSolicitudGuardada) fue registrada en el tablero, pero hubo un problema al enviar el correo de notificación. Contacta al administrador.";
                }
            } else {
                $errores[] = "Error al guardar la solicitud en la base de datos.";
            }
        }

        $this->index($errores, $mensajeExito);
    }

    private function enviarNotificacionEmail($idSolicitud, $solicitante, $repuestos, $cantidades, $observaciones, $listaRepuestosBD)
    {
        try {
            $mapaRepuestos = [];
            foreach ($listaRepuestosBD as $r) {
                $ref = !empty($r['codigo_referencia']) ? " (Ref: " . $r['codigo_referencia'] . ")" : "";
                $mapaRepuestos[$r['id_repuesto']] = $r['nombre_repuesto'] . $ref;
            }

            $filasRepuestosHtml = "";
            foreach ($repuestos as $i => $idRepuesto) {
                $cantidad = intval($cantidades[$i] ?? 0);
                if (!empty($idRepuesto) && $cantidad > 0 && isset($mapaRepuestos[$idRepuesto])) {
                    $nombreRep = htmlspecialchars($mapaRepuestos[$idRepuesto]);
                    $filasRepuestosHtml .= "
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; color: #334155;'>$nombreRep</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center; font-weight: bold; color: #0284c7;'>$cantidad</td>
                    </tr>";
                }
            }

            $fechaHora = date('d/m/Y H:i');
            $obsHtml = !empty($observaciones) ? htmlspecialchars($observaciones) : 'Ninguna';

            $cuerpoHtml = "
            <div style='font-family: sans-serif; max-width: 650px; margin: 0 auto; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; background-color: #ffffff;'>
                <div style='background-color: #4f46e5; padding: 20px; text-align: center; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 22px;'>Nueva Solicitud de Inventario #$idSolicitud</h2>
                    <p style='margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;'>Sistema I-Nexis Mantenimientos</p>
                </div>
                <div style='padding: 25px; color: #1e293b;'>
                    <p style='font-size: 15px; margin-top: 0;'>Se ha registrado una nueva solicitud de repuestos con el siguiente detalle:</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: #f8fafc; border-radius: 8px;'>
                        <tr>
                            <td style='padding: 10px; font-weight: bold; width: 35%; color: #475569;'>N° Solicitud:</td>
                            <td style='padding: 10px; color: #0f172a; font-weight: bold;'>#$idSolicitud</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; font-weight: bold; color: #475569;'>Solicitado Por:</td>
                            <td style='padding: 10px; color: #0f172a; font-weight: bold;'>" . htmlspecialchars($solicitante) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; font-weight: bold; color: #475569;'>Fecha y Hora:</td>
                            <td style='padding: 10px; color: #0f172a;'>$fechaHora</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; font-weight: bold; color: #475569;'>Observaciones:</td>
                            <td style='padding: 10px; color: #0f172a;'>$obsHtml</td>
                        </tr>
                    </table>

                    <h3 style='font-size: 16px; color: #334155; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px;'>Detalle de Repuestos Solicitados</h3>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;'>
                        <thead>
                            <tr style='background-color: #f1f5f9; text-align: left; color: #475569;'>
                                <th style='padding: 10px; border-bottom: 2px solid #cbd5e1;'>Repuesto / Material</th>
                                <th style='padding: 10px; border-bottom: 2px solid #cbd5e1; text-align: center;'>Cantidad Solicitada</th>
                            </tr>
                        </thead>
                        <tbody>
                            $filasRepuestosHtml
                        </tbody>
                    </table>

                    <div style='background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px; margin-top: 20px; border-radius: 4px; font-size: 12px; color: #1d4ed8;'>
                        Este es un mensaje automático generado por el sistema I-Nexis. Por favor no responda directamente a este correo.
                    </div>
                </div>
            </div>";

            $smtp = getConfiguracionSmtp();

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['user'];
            $mail->Password   = $smtp['pass'];
            $mail->SMTPSecure = ($smtp['secure'] === 'tls')
                ? PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $smtp['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($smtp['from'], 'Sistema I-Nexis - Solicitud Inventario');

            $destinatarios = [
                'almacen@inees.co',
                'administrativo@inees.co',
                'desarrollo@inees.co',
                'desarrollo2@inees.co',
                'operaciones@inees.co',
                'auxadministrativo@inees.co'
            ];

            foreach ($destinatarios as $correo) {
                $mail->addAddress(trim($correo));
            }

            $mail->isHTML(true);
            $mail->Subject = 'Nueva Solicitud de Inventario #' . $idSolicitud . ' - ' . $solicitante;
            $mail->Body    = $cuerpoHtml;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Registrar el error real de SMTP para depuración
            error_log("Error enviando notificación de solicitud de inventario: " . $e->getMessage());
            error_log("ErrorInfo SMTP: " . $mail->ErrorInfo);

            // Guardar en archivo visible (solo desarrollo)
            $dirTemp = __DIR__ . '/../../temp';
            if (!is_dir($dirTemp)) {
                @mkdir($dirTemp, 0777, true);
            }
            @file_put_contents(
                $dirTemp . '/error_smtp_inventario.log',
                "[" . date('Y-m-d H:i:s') . "] " . $e->getMessage() . " | ErrorInfo: " . $mail->ErrorInfo . "\n",
                FILE_APPEND
            );

            return false;
        }
    }
}