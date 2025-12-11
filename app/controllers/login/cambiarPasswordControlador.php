<?php
// controladores/usuario/cambiarPasswordControlador.php

if (!defined('ENTRADA_PRINCIPAL')) {
    die("Acceso denegado.");
}

// Inicia la sesión para leer los datos del usuario
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad: Si alguien intenta entrar aquí sin estar en el proceso, lo expulsamos.
if (!isset($_SESSION['temp_user_id'])) {
    header('Location: ' . BASE_URL . 'login');
    exit();
}

// Le pasamos las "instrucciones" al index.php
global $datos_plantilla, $vista_contenido;

// Instrucción 1: Prepara los datos que la vista podría necesitar.
$datos_plantilla = [
    'baseURL' => BASE_URL,
    'username' => $_SESSION['username'] ?? 'Usuario'
];

// Instrucción 2: Define cuál es el archivo de contenido que se debe mostrar.
$vistaContenido = "app/views/login/cambiarPasswordVista.php";
        include "app/views/plantillaVista.php";
