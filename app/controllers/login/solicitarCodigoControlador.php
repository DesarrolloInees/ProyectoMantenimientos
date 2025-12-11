<?php
// controladores/login/solicitarCodigoControlador.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

/**
 * Prepara los datos que la vista 'solicitarCodigoVista.php' necesitará.
 * El index.php se encargará de cargar la vista correspondiente.
 */
global $datos_plantilla;

$datos_plantilla = [
    'baseURL' => BASE_URL,
    'error' => $_GET['error'] ?? null
];
