<?php
// controladores/login/resetPasswordControlador.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

/**
 * Prepara los datos que la vista 'resetPasswordVista.php' necesitará.
 */
global $datos_plantilla;

$datos_plantilla = [
    'baseURL' => BASE_URL,
    'error' => $_GET['error'] ?? null,
    'email' => $_GET['email'] ?? '' // Para auto-rellenar el email
];
