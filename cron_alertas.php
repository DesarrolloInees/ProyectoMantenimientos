<?php
// cron_alertas.php (Guardar en la raíz del proyecto)

define('ENTRADA_PRINCIPAL', true);
$_SERVER['REQUEST_METHOD'] = 'POST';

// Cargar variables de entorno (.env) — necesario porque este cron
// no pasa por index.php, donde normalmente se carga Dotenv.
// Así getConfiguracionSmtp() puede leer SMTP_PASS.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists(Dotenv\Dotenv::class) && file_exists(__DIR__ . '/.env')) {
        Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
    }
}

require_once __DIR__ . '/app/controllers/notificaciones/notificacionesLogisticaControlador.php';

// 1. Instanciamos el controlador primero
$controlador = new NotificacionesLogisticaControlador();

// 2. Atrapamos silenciosamente lo que hace el controlador (incluyendo sus headers)
ob_start();
$controlador->procesarNotificaciones();
$respuestaJson = ob_get_clean();

// 3. AHORA SÍ, imprimimos todo nuestro texto de la consola para que PHP no se queje
echo "Iniciando Cron de Alertas Logísticas - " . date('Y-m-d H:i:s') . "\n";

$resultado = json_decode($respuestaJson, true);

if ($resultado && $resultado['exito']) {
    echo "EXITO: " . $resultado['mensaje'] . "\n";
    echo " - Visitas frecuentes detectadas: " . count($resultado['datos_visitas']) . "\n";
    echo " - Desplazamientos largos detectados: " . count($resultado['datos_desplazamientos']) . "\n";
    echo " - Correo enviado: " . ($resultado['correo_enviado'] ? 'SI' : 'NO') . "\n";
} else {
    echo "ERROR: " . ($resultado['error'] ?? 'Respuesta desconocida') . "\n";
}

echo "Finalizado.\n";
?>