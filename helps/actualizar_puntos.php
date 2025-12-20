<?php
// helps/actualizar_puntos.php

require_once __DIR__ . '/../app/config/conexion.php';

$conexionObj = new Conexion();
$pdo = $conexionObj->getConexion();

if (!$pdo) die("No se pudo establecer conexión.");

echo "<h3>Procesando archivo y generando reporte...</h3>";

// MAPA DE MANTENIMIENTOS
$mapaMantenimiento = [
    'MTTO PREVENTIVO' => 1,
    'MTTO PROFUNDO'   => 2,
    'MTTO CORRECTIVO' => 3,
    'MTTO CORECTIVO'  => 3,
];

// --- PREPARAR CONSULTAS ---

// 1. MODIFICADO: Ahora traemos también el NOMBRE y la FECHA ACTUAL para el reporte
// Hacemos JOIN con 'punto' de una vez para tener el nombre a la mano
$sqlInfoPunto = "SELECT p.id_punto, p.nombre_punto, p.fecha_ultima_visita 
                 FROM maquina m 
                 INNER JOIN punto p ON m.id_punto = p.id_punto 
                 WHERE m.device_id = ? LIMIT 1";
$stmtInfo = $pdo->prepare($sqlInfoPunto);

// 2. El Update (ya sin la condición de fecha en el WHERE, porque la validamos en PHP)
$sqlActualizar = "UPDATE punto 
                  SET fecha_ultima_visita = ?, 
                      id_ultimo_tipo_mantenimiento = ? 
                  WHERE id_punto = ?";
$stmtUpdate = $pdo->prepare($sqlActualizar);


$archivoCsv = __DIR__ . '/CONSOLIDADO INEES actualizado (1).csv'; 

if (($handle = fopen($archivoCsv, "r")) !== FALSE) {
    fgetcsv($handle, 1000, ";"); // Saltar header

    // Contadores
    $leidos = 0;
    $actualizados = 0;
    
    // Arrays para el reporte
    $reporteOmitidos = []; // Puntos que ya tenían fecha más reciente
    $reporteNoEncontrados = []; // Device IDs que no existen en BD

    $pdo->beginTransaction();

    try {
        while (($datos = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $leidos++;
            
            $device_id = trim($datos[0]);
            $fechaRaw  = trim($datos[1]);
            $tipoRaw   = trim(strtoupper($datos[4]));

            // Validar fecha
            $fechaObj = DateTime::createFromFormat('d/m/Y', $fechaRaw);
            if (!$fechaObj) continue;
            $fechaCSV = $fechaObj->format('Y-m-d');

            // Determinar ID Tipo Mantenimiento
            $idTipoMant = null;
            if (isset($mapaMantenimiento[$tipoRaw])) {
                $idTipoMant = $mapaMantenimiento[$tipoRaw];
            } else {
                if (strpos($tipoRaw, 'PROFUNDO') !== false) $idTipoMant = 2;
                elseif (strpos($tipoRaw, 'PREVENTIVO') !== false) $idTipoMant = 1;
                elseif (strpos($tipoRaw, 'CORRECTIVO') !== false || strpos($tipoRaw, 'CORECTIVO') !== false) $idTipoMant = 3;
            }
            if (!$idTipoMant) continue; 

            // --- LÓGICA DE REPORTE ---

            // 1. Buscar información actual del punto
            $stmtInfo->execute([$device_id]);
            $infoPunto = $stmtInfo->fetch();

            if ($infoPunto) {
                $fechaActualBD = $infoPunto['fecha_ultima_visita'];
                $nombrePunto   = $infoPunto['nombre_punto'];
                $idPunto       = $infoPunto['id_punto'];

                // 2. Comparar fechas
                // ¿La fecha del CSV es mayor a la de la BD? (O la BD está vacía)
                if ($fechaActualBD === null || $fechaCSV > $fechaActualBD) {
                    
                    // ACTUALIZAR
                    $stmtUpdate->execute([$fechaCSV, $idTipoMant, $idPunto]);
                    $actualizados++;

                } else {
                    
                    // NO ACTUALIZAR (Reportar)
                    // Guardamos los datos para mostrar la tabla al final
                    $reporteOmitidos[] = [
                        'device' => $device_id,
                        'punto'  => $nombrePunto,
                        'fecha_csv' => $fechaCSV,     // La que intentamos poner
                        'fecha_bd'  => $fechaActualBD // La que ya tenía (más reciente)
                    ];
                }

            } else {
                // Device ID no existe
                $reporteNoEncontrados[] = $device_id;
            }
        }
        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
    fclose($handle);

    // --- MOSTRAR RESULTADOS EN HTML ---
    ?>
    
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; font-family: Arial, sans-serif; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .danger { color: red; font-weight: bold; }
    </style>

    <h2>Resumen del Proceso</h2>
    <ul>
        <li>Filas leídas: <strong><?php echo $leidos; ?></strong></li>
        <li class="success">Puntos Actualizados: <strong><?php echo $actualizados; ?></strong></li>
        <li class="warning">Puntos Omitidos (Ya tenían fecha reciente): <strong><?php echo count($reporteOmitidos); ?></strong></li>
        <li class="danger">Devices No Encontrados: <strong><?php echo count($reporteNoEncontrados); ?></strong></li>
    </ul>

    <?php if (!empty($reporteOmitidos)): ?>
        <h3>⚠️ Puntos NO actualizados (Fecha en BD es más reciente o igual)</h3>
        <table>
            <thead>
                <tr>
                    <th>Device ID</th>
                    <th>Nombre del Punto</th>
                    <th>Fecha en CSV (Intentada)</th>
                    <th>Fecha en BD (Mantenida)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reporteOmitidos as $item): ?>
                <tr>
                    <td><?php echo $item['device']; ?></td>
                    <td><?php echo $item['punto']; ?></td>
                    <td><?php echo $item['fecha_csv']; ?></td>
                    <td style="background-color: #e6fffa; font-weight:bold;"><?php echo $item['fecha_bd']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($reporteNoEncontrados)): ?>
        <h3>❌ Dispositivos no encontrados en base de datos</h3>
        <div style="background: #fee; padding: 10px; border: 1px solid red;">
            <?php echo implode(', ', $reporteNoEncontrados); ?>
        </div>
    <?php endif; ?>

    <?php
} else {
    echo "No se pudo abrir el archivo CSV.";
}
?>