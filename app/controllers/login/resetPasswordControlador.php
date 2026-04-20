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
        if (session_status() === PHP_SESSION_NONE) session_start();
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        require_once __DIR__ . "/../../models/login/loginModelo.php";
        $this->modelo = new LoginModelo($this->db);
    }

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarResetPassword();
            return;
        }

        $data = [
            'baseURL' => BASE_URL,
            'error'   => $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
            'email'   => $_GET['email'] ?? ''
        ];
        require_once "app/views/login/resetPasswordVista.php";
    }

    public function procesarResetPassword()
    {
        $email  = $_POST['email'] ?? '';
        $codigo = trim($_POST['codigo'] ?? '');
        $p1     = $_POST['nueva_password'] ?? '';
        $p2     = $_POST['confirmar_password'] ?? '';

        // 1. Validación de coincidencia
        if ($p1 !== $p2) {
            $this->redireccionarError("Las contraseñas no coinciden.", $email);
        }

        // 2. Validación de complejidad (Regex)
        $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.])[A-Za-z\d@$!%*?&.]{8,}$/";
        if (!preg_match($regex, $p1)) {
            $this->redireccionarError("La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.", $email);
        }

        // 3. Verificar Registro de Reset
        $stmt = $this->db->prepare("SELECT * FROM password_reset WHERE usuario_email = :email ORDER BY id DESC LIMIT 1");
        $stmt->execute([':email' => $email]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            $this->redireccionarError("No se encontró una solicitud de restablecimiento válida.", $email);
        }

        // 4. Validaciones de Seguridad del Código
        $ahora = date('Y-m-d H:i:s');
        
        if ($registro['usado'] == 1) {
            $this->redireccionarError("Este código ya ha sido utilizado.", $email);
        }

        if ($registro['expira_en'] <= $ahora) {
            $this->redireccionarError("El código ha expirado. Por favor, solicita uno nuevo.", $email);
        }

        if (!password_verify($codigo, $registro['codigo_hash'])) {
            $this->redireccionarError("El código de verificación es incorrecto.", $email);
        }

        // 5. Actualizar Contraseña del Usuario
        $usuario = $this->modelo->obtenerUsuarioPorEmail($email);
        if (!$usuario) {
            $this->redireccionarError("Usuario no encontrado.", $email);
        }

        $hash = password_hash($p1, PASSWORD_BCRYPT);
        $actualizado = $this->modelo->actualizarPassword($usuario['usuario_id'], $hash);

        if ($actualizado) {
            $this->modelo->marcarCodigoComoUsado($registro['id']);
            header("Location: " . BASE_URL . "login?success=Tu contraseña ha sido actualizada correctamente.");
            exit();
        } else {
            $this->redireccionarError("Error interno al actualizar la contraseña.", $email);
        }
    }

    private function redireccionarError($mensaje, $email)
    {
        $url = BASE_URL . "resetPassword?error=" . urlencode($mensaje) . "&email=" . urlencode($email);
        header("Location: " . $url);
        exit();
    }
}