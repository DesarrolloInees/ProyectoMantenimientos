<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class CambiarPasswordControlador
{

    public function __construct()
    {
        // Inicia la sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        // Seguridad: Si no existe la sesión temporal, redirigir al login
        if (!isset($_SESSION['temp_user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // --- PREPARACIÓN DE DATOS PARA LA VISTA ---
        // Usamos la misma lógica de variables que tu controlador de Remisiones
        $titulo = "Recuperar Contraseña";
        $username = $_SESSION['username'] ?? 'Usuario';

        // Datos que la plantilla/vista podrían usar
        $datos_plantilla = [
            'baseURL' => BASE_URL,
            'username' => $username
        ];

        // --- CARGA DE VISTAS ---
        // Definimos la vista de contenido y llamamos a la plantilla principal
        $vistaContenido = "app/views/login/solicitarCodigoVista.php";
        include "app/views/plantillaVista.php";
    }

    /**
     * Ejemplo de cómo podrías manejar la validación del código 
     * si decides enviarlo por POST a este mismo controlador
     */
    public function validarCodigo()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Aquí iría la lógica para verificar el código
            // similar a como validas las remisiones
        }
    }
}
