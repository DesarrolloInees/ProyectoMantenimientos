<?php
// app/controllers/login/loginControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../models/login/loginModelo.php';
require_once __DIR__ . '/../../config/conexion.php';

class loginControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new LoginModelo($this->db);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MM_Login'])) {
            $this->procesarLogin();
        }
    }

    public function cargarVista()
    {
        $datos_plantilla = ['error_login' => false];

        if (defined('BASE_URL')) {
            $datos_plantilla['baseURL'] = BASE_URL;
        }

        require_once __DIR__ . '/../../views/login/loginVista.php';
    }

    public function procesarLogin()
    {
        ob_clean();
        header('Content-Type: application/json');

        $usuario  = $_POST['usuario']  ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->modelo->authenticateUser($usuario, $password);

        if ($user) {

            // ─── CAMBIO DE CONTRASEÑA OBLIGATORIO ───────────────────────────
            if ($user['forzar_cambio_pwd'] == 1) {
                // Sesión TEMPORAL: no se da acceso completo hasta que cambie la clave
                $_SESSION['temp_user_id']      = $user['usuario_id'];
                $_SESSION['temp_username']     = $user['nombre'];        // mismo dato que login normal
                $_SESSION['temp_nivel_acceso'] = $user['nivel_acceso'];  // FALTABA — necesario para RBAC

                echo json_encode([
                    'status'   => 'success',
                    'redirect' => BASE_URL . "cambiarPassword"
                ]);
                exit;
            }
            // ────────────────────────────────────────────────────────────────

            // Login normal: sesión completa
            $_SESSION['usuario_id']    = $user['usuario_id'];
            $_SESSION['usuario_name']  = $user['nombre'];
            $_SESSION['nivel_acceso']  = $user['nivel_acceso'];
            $_SESSION['usuario_cargo'] = $user['cargo'];

            $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
            $this->modelo->logAccess($user['usuario_id'], $user['nombre'], $ip_usuario);
            $this->modelo->updateLastLoginTime($user['usuario_id']);

            echo json_encode([
                'status'   => 'success',
                'redirect' => BASE_URL . "inicio"
            ]);
            exit;

        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Usuario o contraseña incorrectos'
            ]);
            exit;
        }
    }
}