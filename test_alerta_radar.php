<?php
session_start();
define('ENTRADA_PRINCIPAL', true);

// Verificar la ubicación de la conexión según la estructura de tu proyecto
if (file_exists(__DIR__ . '/config/conexion.php')) {
    require_once __DIR__ . '/config/conexion.php';
} elseif (file_exists(__DIR__ . '/app/config/conexion.php')) {
    require_once __DIR__ . '/app/config/conexion.php';
} else {
    die("No se encontró el archivo conexion.php. Revisa la ruta.");
}

// Cargar el modelo
require_once __DIR__ . '/app/models/cronometro/cronometroAdminModelo.php';

$conexionObj = new Conexion();
$db = $conexionObj->getConexion();
$modelo = new cronometroAdminModelo($db);

// Datos simulados para la prueba
$tecnicoPrueba = "Carlos Rodríguez (Prueba)";
$servicioPrueba = "Mantenimiento Correctivo";
$puntoPrueba = "Sede Norte";
$minutosTranscurridos = 135;
$tiempoMeta = 120;

$msg = "⚠️ <b>ALERTA DE RETRASO EN VIVO (PRUEBA)</b>\n\n";
$msg .= "<b>Técnico:</b> {$tecnicoPrueba}\n";
$msg .= "<b>Servicio:</b> {$servicioPrueba}\n";
$msg .= "<b>Punto:</b> {$puntoPrueba}\n";
$msg .= "<b>Transcurrido:</b> {$minutosTranscurridos} min (Meta: {$tiempoMeta} min)\n";

$res = $modelo->enviarAlertaTelegram($msg);

echo "<h3>Resultado del envío:</h3>";
echo "<pre>" . print_r($res, true) . "</pre>";