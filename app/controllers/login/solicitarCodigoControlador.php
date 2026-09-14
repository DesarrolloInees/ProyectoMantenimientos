<?php
// app/controllers/login/solicitarCodigoControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class solicitarCodigoControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();

        require_once __DIR__ . "/../../models/login/loginModelo.php";
        $this->modelo = new LoginModelo($this->db);
    }

    // --- AQUÍ ESTÁ EL CAMBIO ---
    public function index()
    {
        // NO definimos $vistaContenido ni llamamos a plantillaVista.php

        // Simplemente cargamos la vista "suelta", ya que tiene su propio HTML completo
        require_once "app/views/login/solicitarCodigoVista.php";
    }
    // ---------------------------

    public function enviarCodigo()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $usuario = $this->modelo->obtenerUsuarioPorEmail($email);

            if ($usuario) {
                $codigo = random_int(100000, 999999);
                $codigo_hash = password_hash((string)$codigo, PASSWORD_BCRYPT);
                $expiracion = date('Y-m-d H:i:s', time() + 900); // 15 min

                $this->modelo->guardarCodigoReset($email, $codigo_hash, $expiracion);
                $this->ejecutarEnvioEmail($email, $codigo);
            }

            // Redirigir pasando la acción para mostrar el mensaje de éxito
            header('Location: ' . BASE_URL . 'solicitarCodigo?accion=mensajeEnviado');
            exit();
        }
    }

    // ESTE TAMBIÉN DEBE CARGARSE SOLO (SIN PLANTILLA)
    public function mensajeEnviado()
    {
        require_once "app/views/login/mensajeEnviadoVista.php";
    }

    private function ejecutarEnvioEmail($email, $codigo)
    {
        // Cargar autoload desde la raíz del proyecto
        require_once __DIR__ . '/../../../vendor/autoload.php';

        // Obtener configuración SMTP centralizada
        $smtp = getConfiguracionSmtp();

        $mail = new PHPMailer(true);
        try {
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

            $mail->setFrom($smtp['from'], $smtp['from_name']);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Tu código de recuperación I-Nexis';
            $mail->Body    = "Hola,<br><br>Tu código para recuperar tu contraseña es: <b>$codigo</b><br>Expira en 15 min.";

            $mail->send();
        } catch (Exception $e) {
            error_log("Error PHPMailer enviar código: " . $mail->ErrorInfo);
        }
    }
}
