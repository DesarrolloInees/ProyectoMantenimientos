<?php
// controladores/login/procesarResetPasswordControlador.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../modelos/login/loginModelo.php';
$modelo = new LoginModelo($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $codigo_enviado = $_POST['codigo'];
    $nuevaPass = $_POST['nueva_password'];
    $confirmPass = $_POST['confirmar_password'];

    // URL de error para redirigir en caso de fallo
    $error_url = BASE_URL . "resetPassword?email=" . urlencode($email);

    // 1. Validar que las contraseñas coincidan
    if ($nuevaPass !== $confirmPass) {
        header('Location: ' . $error_url . '&error=no_coinciden');
        exit();
    }

    // 2. Validar fortaleza de la contraseña (usa la tabla de parámetros)
    $longitudMinima = (int)$modelo->obtenerParametro('password_min_longitud', '8');
    $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.])[A-Za-z\d@$!%*?&.]{" . $longitudMinima . ",}$/";
    if (!preg_match($regex, $nuevaPass)) {
        header('Location: ' . $error_url . '&error=no_segura');
        exit();
    }

    // 3. Verificar el código en la BD
    $id_del_codigo = $modelo->verificarCodigoReset($email, $codigo_enviado); // Función del modelo

    if ($id_del_codigo) {
        // ¡Código válido!
        // 4. Obtener el ID del usuario
        $usuario = $modelo->obtenerUsuarioPorEmail($email);

        // 5. Actualizar la contraseña en la tabla 'usuarios'
        $modelo->actualizarPassword($usuario['usuario_id'], $nuevaPass); // Esta función ya la tenías

        // 6. Marcar el código como 'usado'
        $modelo->marcarCodigoComoUsado($id_del_codigo); // Función del modelo

        // ¡Éxito!
        header('Location: ' . BASE_URL . 'login?exito=reset_ok');
        exit();
    } else {
        // Código inválido o expirado
        header('Location: ' . $error_url . '&error=codigo_invalido');
        exit();
    }
}
