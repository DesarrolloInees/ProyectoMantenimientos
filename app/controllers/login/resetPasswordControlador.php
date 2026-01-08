<?php
// app/controllers/login/resetPasswordControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';

class resetPasswordControlador
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

    public function index()
    {
        $data = [
            'baseURL' => BASE_URL,
            'error'   => $_GET['error'] ?? null,
            'email'   => $_GET['email'] ?? ''
        ];
        require_once "app/views/login/resetPasswordVista.php";
    }

    public function procesarResetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $email = $_POST['email'];
            $codigo_enviado = trim($_POST['codigo']); // Trim para quitar espacios accidentales
            $nuevaPass = $_POST['nueva_password'];
            $confirmPass = $_POST['confirmar_password'];

            $error_url = BASE_URL . "resetPassword?email=" . urlencode($email);

            // 1. Validar coincidencia
            if ($nuevaPass !== $confirmPass) {
                header('Location: ' . $error_url . '&error=no_coinciden');
                exit();
            }

            // 2. Validar fortaleza (Regex)
            $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.])[A-Za-z\d@$!%*?&.]{8,}$/";
            if (!preg_match($regex, $nuevaPass)) {
                header('Location: ' . $error_url . '&error=no_segura');
                exit();
            }

            // 3. Verificar código
            $id_codigo_reset = $this->modelo->verificarCodigoReset($email, $codigo_enviado);

            if ($id_codigo_reset) {
                // Código OK. Buscamos al usuario.
                $usuario = $this->modelo->obtenerUsuarioPorEmail($email);

                if ($usuario) {
                    $hash = password_hash($nuevaPass, PASSWORD_BCRYPT);

                    // --- CORRECCIÓN IMPORTANTE ---
                    // En tu tabla la columna se llama 'usuario_id'
                    $this->modelo->actualizarPassword($usuario['usuario_id'], $hash);

                    // Quemar el código
                    $this->modelo->marcarCodigoComoUsado($id_codigo_reset);

                    header('Location: ' . BASE_URL . 'login?mensaje=reset_exitoso');
                    exit();
                }
            }

            header('Location: ' . $error_url . '&error=codigo_invalido');
            exit();
        }
    }
}
