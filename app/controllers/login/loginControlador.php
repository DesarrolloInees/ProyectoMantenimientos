<?php
// app/controllers/login/loginControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// 1. CORRECCIÓN DE RUTAS: Usamos 'models' (inglés) y 'config'
require_once __DIR__ . '/../../models/login/loginModelo.php';
require_once __DIR__ . '/../../config/conexion.php';

class loginControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        // Creamos la conexión interna aquí para evitar el error de "$db undefined"
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();

        // Instanciamos el modelo pasándole la conexión
        $this->modelo = new LoginModelo($this->db);

        // Si detectamos que se envió el formulario por POST, procesamos el login
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MM_Login'])) {
            $this->procesarLogin();
        }
    }

    // Método para mostrar la vista (HTML)
    public function cargarVista()
    {
        // Datos necesarios para la vista
        $datos_plantilla = ['error_login' => false];

        if (defined('BASE_URL')) {
            $datos_plantilla['baseURL'] = BASE_URL;
        }

        // Cargar la vista física
        // Asegúrate de que la carpeta sea 'views' (inglés) o 'vistas' (español) según tu estructura real.
        // En el MVC estándar suele ser 'views'. Si te da error, cambia 'views' por 'vistas' aquí abajo.
        require_once __DIR__ . '/../../views/login/loginVista.php';
    }

    // Método para procesar los datos (AJAX)
    public function procesarLogin()
    {
        // Limpiamos el buffer por si acaso
        ob_clean();
        header('Content-Type: application/json');

        $usuario = $_POST['usuario'] ?? '';
        $password = $_POST['password'] ?? '';

        // Autenticar
        $user = $this->modelo->authenticateUser($usuario, $password);

        if ($user) {
            // --- Validaciones de Seguridad (Password expirado, etc) ---

            if ($user['forzar_cambio_pwd'] == 1) {
                $_SESSION['temp_user_id'] = $user['usuario_id'];
                $_SESSION['username'] = $user['usuario'];
                echo json_encode(['status' => 'success', 'redirect' => BASE_URL . "cambiarPassword"]);
                exit;
            }

            // Guardamos sesión
            $_SESSION['usuario_id'] = $user['usuario_id'];
            $_SESSION['usuario_name'] = $user['nombre'];
            $_SESSION['nivel_acceso'] = $user['nivel_acceso'];
            $_SESSION['usuario_cargo'] = $user['cargo'];

            // Log de acceso
            $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
            $this->modelo->logAccess($user['usuario_id'], $user['nombre'], $ip_usuario);
            $this->modelo->updateLastLoginTime($user['usuario_id']);

            echo json_encode(['status' => 'success', 'redirect' => BASE_URL . "inicio"]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos']);
            exit;
        }
    }
}
