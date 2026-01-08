<?php
// app/controllers/login/solicitarCodigoControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// 1. IMPORTAR ARCHIVO DE CONEXIÓN (Igual que en tu ejemplo)
require_once __DIR__ . '/../../config/conexion.php';

// 2. IMPORTAR PHPMAILER
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class enviarCodigoControlador
{

    private $modelo;
    private $db; // Variable para guardar la conexión

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 3. CONEXIÓN A LA BASE DE DATOS (Tu método)
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();

        // 4. CARGAR EL MODELO
        // Usamos __DIR__ para asegurar la ruta correcta
        require_once __DIR__ . "/../../models/login/loginModelo.php";

        // Pasamos la conexión ($this->db) al modelo
        $this->modelo = new LoginModelo($this->db);
    }

    public function index()
    {
        $titulo = "Recuperar Contraseña";
        $vistaContenido = "app/views/login/solicitarCodigoVista.php";
        include "app/views/plantillaVista.php";
    }

    public function enviarCodigo()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
            $email = $_POST['email'];
            $usuario = $this->modelo->obtenerUsuarioPorEmail($email);

            if ($usuario) {
                // Lógica de generación de código
                $codigo = random_int(100000, 999999);
                $codigo_hash = password_hash((string)$codigo, PASSWORD_BCRYPT);
                $expiracion = date('Y-m-d H:i:s', time() + 900);

                $this->modelo->guardarCodigoReset($email, $codigo_hash, $expiracion);

                // Enviar el correo
                $this->ejecutarEnvioEmail($email, $codigo);
            }

            // Redirigir
            header('Location: ' . BASE_URL . 'solicitarCodigo/mensajeEnviado');
            exit();
        }
    }

    private function ejecutarEnvioEmail($email, $codigo)
    {
        // 5. CARGAR AUTOLOAD CON RUTA ABSOLUTA
        // Subimos 3 niveles: login -> controllers -> app -> raiz (donde está vendor)
        require_once __DIR__ . '/../../../vendor/autoload.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ineesmensajesautomaticos@gmail.com';
            // Recuerda poner tu contraseña de aplicación real aquí
            $mail->Password   = 'bhoh svdq qvfl rxwy';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('ineesmensajesautomaticos@gmail.com', 'Sistema I-Nexis');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Tu código de recuperación I-Nexis';
            $mail->Body    = "Hola,<br><br>Tu código para recuperar tu contraseña es: <b>$codigo</b><br>Expira en 15 min.";

            $mail->send();
        } catch (Exception $e) {
            error_log("Error PHPMailer: " . $mail->ErrorInfo);
        }
    }
}
