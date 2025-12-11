<?php
// controladores/login/enviarCodigoControlador.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// 1. Cargar dependencias
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../modelos/login/loginModelo.php';
$modelo = new LoginModelo($db);

// 2. Validar que sea un POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $usuario = $modelo->obtenerUsuarioPorEmail($email); // Función que creamos en el modelo

    // 3. Si el usuario existe, preparamos el correo
    if ($usuario) {
        // Generar código
        $codigo = random_int(100000, 999999);
        $codigo_hash = password_hash((string)$codigo, PASSWORD_BCRYPT);
        $expiracion = date('Y-m-d H:i:s', time() + 900); // Válido por 15 minutos

        // Guardar el HASH en la BD
        $modelo->guardarCodigoReset($email, $codigo_hash, $expiracion); // Función del modelo

        // Enviar el correo
        $mail = new PHPMailer(true);
        try {
            // ----- ¡RELLENA TUS DATOS DE GMAIL AQUÍ! -----
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ineesmensajesautomaticos@gmail.com'; // Tu email
            $mail->Password   = 'bhoh svdq qvfl rxwy';       // Tu contraseña de aplicación
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';
            // ----------------------------------------------

            // Contenido
            $mail->setFrom('ineesmensajesautomaticos@gmail.com', 'Sistema I-Nexis');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Tu codigo de recuperacion I-Nexis';
            $mail->Body    = "Hola,<br><br>Tu codigo para recuperar tu contrasena es: <b>" . $codigo . "</b><br>Este codigo expira en 15 minutos.";

            $mail->send();
        } catch (Exception $e) {
            // Guardar el error para ti, pero no se lo muestres al usuario
            error_log("Error al enviar correo: {$mail->ErrorInfo}");
        }
    }

    // Por seguridad, siempre redirigimos al mismo sitio
    header('Location: ' . BASE_URL . 'mensajeEnviado');
    exit();
}
