<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/notificaciones/notificacionesLogisticaModelo.php';
// Asegúrate de que el path al autoload de PHPMailer sea el correcto según tu proyecto
require_once __DIR__ . '/../../../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('America/Bogota');

class NotificacionesLogisticaControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new NotificacionesLogisticaModelo($this->db);
    }

    public function index()
    {
        $titulo = "Alertas y Notificaciones Logísticas";
        $vistaContenido = "app/views/notificaciones/notificacionesLogisticaVista.php";
        include "app/views/plantillaVista.php";
    }

    // Helper para enviar el correo (Basado en tu código original)
    private function enviarNotificacion($destinatarioEmail, $asunto, $cuerpoMensaje)
    {
        // --- MODO PRUEBA ---
        $redirigirCorreos = true; // PONER EN FALSE PARA PRODUCCIÓN
        $miCorreoDePruebas = 'aquilesbedoya37@gmail.com'; 

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ineesmensajesautomaticos@gmail.com';
            $mail->Password   = 'bhoh svdq qvfl rxwy'; // Ojo: Considera mover esto a variables de entorno (.env)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('ineesmensajesautomaticos@gmail.com', 'Alertas I-Nexis Logística');

            if ($redirigirCorreos) {
                $mail->addAddress($miCorreoDePruebas);
                $asunto = "[PRUEBA - Para: {$destinatarioEmail}] " . $asunto;
            } else {
                $mail->addAddress($destinatarioEmail);
            }

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpoMensaje . "<br><br><hr><small>Mensaje automático generado el " . date('Y-m-d H:i:s') . "</small>";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("[Notificaciones Logística] Error enviando correo: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function procesarNotificaciones()
    {
        ob_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $visitasFrecuentes = $this->modelo->obtenerVisitasFrecuentes();
                $desplazamientosLargos = $this->modelo->obtenerDesplazamientosLargos();

                $enviado = false;
                $mensajeHTML = "";

                // Solo armamos y enviamos correo si hay datos en alguna de las dos alertas
                if (!empty($visitasFrecuentes) || !empty($desplazamientosLargos)) {
                    
                    $mensajeHTML .= "<h2 style='color: #1F4E78;'>Reporte de Alertas Logísticas Diarias</h2>";
                    
                    // --- TABLA 1: Visitas Frecuentes ---
                    if (!empty($visitasFrecuentes)) {
                        $mensajeHTML .= "<h3 style='color: #C55A11;'>1. Puntos visitados más de 2 veces (Últimos 7 días)</h3>";
                        $mensajeHTML .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
                        $mensajeHTML .= "<thead style='background-color: #f2f2f2;'><tr><th>Punto</th><th>Cliente</th><th>Total Visitas</th><th>Fechas de Visita</th></tr></thead><tbody>";
                        
                        foreach ($visitasFrecuentes as $vf) {
                            $mensajeHTML .= "   <tr>
                                                    <td>" . htmlspecialchars($vf['nombre_punto']) . "</td>
                                                    <td>" . htmlspecialchars($vf['nombre_cliente']) . "</td>
                                                    <td style='text-align:center;'><b>" . $vf['total_visitas'] . "</b></td>
                                                    <td style='font-size: 0.9em;'>" . $vf['fechas_visitadas'] . "</td>
                                            </tr>";
                        }
                        $mensajeHTML .= "</tbody></table><br>";
                    }

                    // --- TABLA 2: Desplazamientos Largos ---
                    if (!empty($desplazamientosLargos)) {
                        $mensajeHTML .= "<h3 style='color: #C55A11;'>2. Desplazamientos Urbanos prolongados (> 40 min) de hoy</h3>";
                        $mensajeHTML .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
                        $mensajeHTML .= "<thead style='background-color: #f2f2f2;'><tr><th>Técnico</th><th>Punto Destino</th><th>Salida Anterior</th><th>Llegada Actual</th><th>Tiempo (Minutos)</th></tr></thead><tbody>";
                        
                        foreach ($desplazamientosLargos as $dl) {
                            $mensajeHTML .= "   <tr>
                                                    <td>" . htmlspecialchars($dl['nombre_tecnico']) . "</td>
                                                    <td>" . htmlspecialchars($dl['destino']) . "</td>
                                                    <td style='text-align:center;'>" . htmlspecialchars($dl['inicio_desplazamiento']) . "</td>
                                                    <td style='text-align:center;'>" . htmlspecialchars($dl['llegada_destino']) . "</td>
                                                    <td style='text-align:center; color: red;'><b>" . $dl['minutos_viaje'] . " min</b></td>
                                                </tr>";
                        }
                        $mensajeHTML .= "</tbody></table><br>";
                    }

                    // Enviar al supervisor (Cambia este correo por el real)
                    $correoSupervisor = 'supervisorsat@inees.co' . '; ' . 'laboratorio@inees.co';
                    $asunto = "[Alertas Operativas] Reporte Logístico - " . date('d/m/Y');
                    
                    $enviado = $this->enviarNotificacion($correoSupervisor, $asunto, $mensajeHTML);
                }

                $respuestaArray = [
                    'exito' => true,
                    'mensaje' => 'Proceso finalizado correctamente.',
                    'datos_visitas' => $visitasFrecuentes,
                    'datos_desplazamientos' => $desplazamientosLargos,
                    'correo_enviado' => $enviado,
                    'html_generado' => $mensajeHTML // Opcional: lo mandamos para pintarlo en la vista
                ];

            } catch (\Throwable $e) {
                $respuestaArray = ['exito' => false, 'error' => 'Error: ' . $e->getMessage()];
            }
        } else {
            $respuestaArray = ['exito' => false, 'error' => 'Método no permitido.'];
        }

        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuestaArray, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>