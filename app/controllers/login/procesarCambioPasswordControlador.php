<?php
// controladores/usuario/procesarCambioPasswordControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// Importamos lo necesario
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/login/loginModelo.php';

class ProcesarCambioPasswordControlador {
    
    private $db;
    private $modelo;

    public function __construct() {
        // 1. SOLUCIÓN AL ERROR: Creamos la conexión aquí mismo
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        
        // 2. Instanciamos el modelo con la conexión segura
        $this->modelo = new LoginModelo($this->db);
    }

    public function index() {
        // Verificar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validación de seguridad de sesión temporal
        if (!isset($_SESSION['temp_user_id'])) {
            // Si pierdes la sesión, redirige al login con error
            header('Location: ' . BASE_URL . 'login?error=sesion_expirada');
            exit();
        }

        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // --- RECOLECCIÓN Y LÓGICA ---
        
        $nuevaPass = $_POST['nueva_password'] ?? '';
        $confirmPass = $_POST['confirmar_password'] ?? '';
        $userId = (int)$_SESSION['temp_user_id'];

        // 1. VALIDACIÓN: Coincidencia
        if ($nuevaPass !== $confirmPass) {
            header('Location: ' . BASE_URL . 'cambiarPassword?error=no_coinciden');
            exit();
        }

        // 2. VALIDACIÓN: Seguridad (Regex)
        // Obtenemos parámetro de longitud mínima (valor por defecto 8)
        $longitudMinima = (int)$this->modelo->obtenerParametro('password_min_longitud', '8');
        
        $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.])[A-Za-z\d@$!%*?&.]{" . $longitudMinima . ",}$/";

        if (!preg_match($regex, $nuevaPass)) {
            header('Location: ' . BASE_URL . 'cambiarPassword?error=no_segura');
            exit();
        }

        // 3. EJECUCIÓN
        $actualizado = $this->modelo->actualizarPassword($userId, $nuevaPass);

        if ($actualizado) {
            // ÉXITO: Limpiamos sesión y mandamos al login
            session_destroy(); 
            header('Location: ' . BASE_URL . 'login?exito=cambio_ok');
            exit();
        } else {
            // ERROR BD
            header('Location: ' . BASE_URL . 'cambiarPassword?error=db_error');
            exit();
        }
    }
}
?>