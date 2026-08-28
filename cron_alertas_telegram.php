<?php
define('ENTRADA_PRINCIPAL', true);

date_default_timezone_set('America/Bogota');

$baseDir = 'C:/xampp/htdocs/ProyectoMantenimientos/';
$logFile = $baseDir . 'cron_debug.log';

file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Executing cron...\n", FILE_APPEND);

$posiblesConexiones = [
    $baseDir . 'app/config/conexion.php',
    $baseDir . 'app/app/config/conexion.php',
    $baseDir . 'config/conexion.php'
];

$rutaConexion = null;
foreach ($posiblesConexiones as $path) {
    if (file_exists($path)) {
        $rutaConexion = $path;
        break;
    }
}

$posiblesModelos = [
    $baseDir . 'app/models/cronometro/cronometroAdminModelo.php',
    $baseDir . 'app/app/models/cronometro/cronometroAdminModelo.php',
    $baseDir . 'models/cronometro/cronometroAdminModelo.php'
];

$rutaModelo = null;
foreach ($posiblesModelos as $path) {
    if (file_exists($path)) {
        $rutaModelo = $path;
        break;
    }
}

if ($rutaConexion && $rutaModelo) {
    require_once $rutaConexion;
    require_once $rutaModelo;
} else {
    file_put_contents($logFile, "Error al cargar archivos de conexión/modelo.\n", FILE_APPEND);
    die();
}

try {
    $conexionObj = new Conexion();
    $db = $conexionObj->getConexion();
    $modelo = new cronometroAdminModelo($db);

    $catalogoTiemposDB = $modelo->obtenerCatalogoTiempos();
    $catalogoTiempos = !empty($catalogoTiemposDB) ? $catalogoTiemposDB : [
        "1" => 45, "2" => 60, "3" => 80, "4" => 20
    ];

    $sql = "SELECT  ml.id_monitoreo,
                    ml.id_tipo_mantenimiento,
                    ml.hora_inicio,
                    TIMESTAMPDIFF(MINUTE, ml.hora_inicio, NOW()) AS minutos_transcurridos,
                    t.nombre_tecnico, 
                    p.nombre_punto, 
                    tm.nombre_completo AS tipo_mantenimiento 
            FROM monitoreo_motorizados_live ml
            LEFT JOIN tecnico t ON ml.id_tecnico = t.id_tecnico
            LEFT JOIN punto p ON ml.id_punto = p.id_punto
            LEFT JOIN tipo_mantenimiento tm ON ml.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
            WHERE ml.estado_actual = 'En Progreso' 
            AND (ml.alerta_telegram_enviada IS NULL OR ml.alerta_telegram_enviada = 0)";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $serviciosEnProgreso = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $alertasEnviadas = 0;

    foreach ($serviciosEnProgreso as $s) {
        $idMonitoreo = (int) $s['id_monitoreo'];
        $idTipoStr = (string) $s['id_tipo_mantenimiento'];
        $minutosTranscurridos = (int) $s['minutos_transcurridos'];
        
        if (isset($catalogoTiempos[$idTipoStr])) {
            $tiempoMeta = (int) $catalogoTiempos[$idTipoStr];
        } elseif (isset($catalogoTiempos[(int)$idTipoStr])) {
            $tiempoMeta = (int) $catalogoTiempos[(int)$idTipoStr];
        } else {
            $tiempoMeta = 45;
        }

        file_put_contents($logFile, "Evaluando ID: {$idMonitoreo} | Transcurrido: {$minutosTranscurridos}m | Meta: {$tiempoMeta}m\n", FILE_APPEND);

        if ($minutosTranscurridos >= $tiempoMeta) {
            $nombreTecnico = !empty($s['nombre_tecnico']) ? $s['nombre_tecnico'] : 'Técnico Desconocido';
            $nombrePunto = !empty($s['nombre_punto']) ? $s['nombre_punto'] : 'Punto Desconocido';
            $tipoMantenimiento = !empty($s['tipo_mantenimiento']) ? $s['tipo_mantenimiento'] : 'Servicio General';

            $msg = "⚠️ <b>ALERTA DE RETRASO EN VIVO</b>\n\n";
            $msg .= "<b>Técnico:</b> " . htmlspecialchars($nombreTecnico) . "\n";
            $msg .= "<b>Servicio:</b> " . htmlspecialchars($tipoMantenimiento) . "\n";
            $msg .= "<b>Punto:</b> " . htmlspecialchars($nombrePunto) . "\n";
            $msg .= "<b>Transcurrido:</b> {$minutosTranscurridos} min (Meta: {$tiempoMeta} min)\n";

            // 1. Enviar mensaje a Telegram y registrar respuesta
            $resTelegram = $modelo->enviarAlertaTelegram($msg);
            file_put_contents($logFile, "Telegram Response: " . $resTelegram . "\n", FILE_APPEND);

            // 2. Intentar actualización en MySQL
            $updateSql = "UPDATE monitoreo_motorizados_live SET alerta_telegram_enviada = 1 WHERE id_monitoreo = :id";
            $updateStmt = $db->prepare($updateSql);
            $filasActualizadas = $updateStmt->execute([':id' => $idMonitoreo]);
            
            file_put_contents($logFile, "DB Update Filas Afectadas: " . $updateStmt->rowCount() . "\n", FILE_APPEND);

            $alertasEnviadas++;
        }
    }

    file_put_contents($logFile, "Finished. Total processed: {$alertasEnviadas}\n\n", FILE_APPEND);

} catch (Exception $e) {
    file_put_contents($logFile, "Database Error: " . $e->getMessage() . "\n\n", FILE_APPEND);
}