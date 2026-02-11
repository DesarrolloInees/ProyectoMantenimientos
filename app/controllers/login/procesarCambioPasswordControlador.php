<?php
// app/controllers/login/procesarCambioPasswordControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';

class procesarCambioPasswordControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $conexionObj  = new Conexion();
        $this->db     = $conexionObj->getConexion();

        require_once __DIR__ . "/../../models/login/loginModelo.php";
        $this->modelo = new LoginModelo($this->db);
    }

    public function index()
    {
        // SEGURIDAD: Solo accesible si existe sesión temporal
        if (!isset($_SESSION['temp_user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Solo acepta POST — si llega por GET lo mandamos al formulario
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'cambiarPassword');
            exit();
        }

        $nuevaPass   = $_POST['nueva_password']    ?? '';
        $confirmPass = $_POST['confirmar_password'] ?? '';
        $usuario_id  = $_SESSION['temp_user_id'];   // ← Siempre de SESSION, nunca del POST

        // ── VALIDACIÓN 1: Coincidencia ───────────────────────────────────────
        if ($nuevaPass !== $confirmPass) {
            header('Location: ' . BASE_URL . 'cambiarPassword?error=no_coinciden');
            exit();
        }

        // ── VALIDACIÓN 2: Fortaleza ──────────────────────────────────────────
        $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.])[A-Za-z\d@$!%*?&.]{8,}$/";
        if (!preg_match($regex, $nuevaPass)) {
            header('Location: ' . BASE_URL . 'cambiarPassword?error=no_segura');
            exit();
        }

        // ── ACTUALIZAR BD ────────────────────────────────────────────────────
        // actualizarPassword() hace: UPDATE password_hash, forzar_cambio_pwd = 0, pwd_ultimo_cambio = NOW()
        $hash      = password_hash($nuevaPass, PASSWORD_BCRYPT);
        $resultado = $this->modelo->actualizarPassword($usuario_id, $hash);

        if ($resultado) {
            // ✅ ÉXITO: Convertir sesión temporal en sesión REAL
            // Las claves deben ser IDÉNTICAS a las que usa el login normal
            $_SESSION['usuario_id']    = $_SESSION['temp_user_id'];
            $_SESSION['usuario_name']  = $_SESSION['temp_username'];
            $_SESSION['nivel_acceso']  = $_SESSION['temp_nivel_acceso'];
            // usuario_cargo no está en la sesión temporal — se puede omitir o buscarlo:
            $userdata = $this->modelo->obtenerUsuarioPorEmail(''); // No aplica aquí
            // Si necesitas el cargo, puedes buscarlo así:
            // $stmt = $this->db->prepare("SELECT cargo FROM usuarios WHERE usuario_id = :id");
            // $stmt->execute([':id' => $usuario_id]);
            // $_SESSION['usuario_cargo'] = $stmt->fetchColumn();

            // Limpiar variables temporales
            unset(
                $_SESSION['temp_user_id'],
                $_SESSION['temp_username'],
                $_SESSION['temp_nivel_acceso']
            );

            // Log del acceso ahora que ya es sesión real
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
            $this->modelo->logAccess(
                $_SESSION['usuario_id'],
                $_SESSION['usuario_name'],
                $ip
            );
            $this->modelo->updateLastLoginTime($_SESSION['usuario_id']);

            header('Location: ' . BASE_URL . 'inicio');
            exit();
        }

        // Error de BD
        header('Location: ' . BASE_URL . 'cambiarPassword?error=error_db');
        exit();
    }
}