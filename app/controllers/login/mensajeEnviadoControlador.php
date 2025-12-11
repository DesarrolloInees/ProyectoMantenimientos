<?php
// controladores/login/mensajeEnviadoControlador.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

/**
 * Prepara los datos que la vista 'mensajeEnviadoVista.php' necesitará.
 */
global $datos_plantilla;

$datos_plantilla = [
    'baseURL' => BASE_URL
];
