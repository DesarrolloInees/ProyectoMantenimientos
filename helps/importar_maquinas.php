<?php
// --- CONFIGURACIÓN ---
ini_set('memory_limit', '1024M'); 
set_time_limit(600); 

$host = 'localhost';
$user = 'root'; 
$pass = ''; 
$db   = 'inees_mantenimientos'; 

$mensaje = "";
$errores_log = []; 

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($host, $user, $pass, $db);
    $mysqli->set_charset("utf8mb4");
} catch (Exception $e) {
    die("❌ Error conexión BD: " . $e->getMessage());
}

// =========================================================
// FUNCIÓN AUXILIAR: NORMALIZAR TEXTO (ELIMINA TILDES)
// =========================================================
// Esto es vital para que "Bogotá" coincida con "BOGOTA"
function normalizar_clave($string) {
    $string = mb_strtoupper(trim($string), 'UTF-8');
    $string = str_replace(
        ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
        ['A', 'E', 'I', 'O', 'U', 'U', 'N'],
        $string
    );
    // Eliminar caracteres no alfanuméricos (opcional, pero ayuda)
    return preg_replace('/[^A-Z0-9]/', '', $string);
}

// =========================================================
// PASO 1: CARGAR MAPAS EN MEMORIA (PUNTOS Y TIPOS)
// =========================================================

// A. Mapa de Tipos de Máquina
// Si el archivo trae un tipo nuevo, lo crearemos al vuelo.
$mapa_tipos = [];
$res = $mysqli->query("SELECT id_tipo_maquina, nombre_tipo_maquina FROM tipo_maquina");
while ($row = $res->fetch_assoc()) {
    $clave = normalizar_clave($row['nombre_tipo_maquina']);
    $mapa_tipos[$clave] = $row['id_tipo_maquina'];
}

// B. Mapa de Puntos (ESTE ES EL CRÍTICO)
$mapa_puntos = [];
$res = $mysqli->query("SELECT id_punto, nombre_punto FROM punto");
while ($row = $res->fetch_assoc()) {
    // Guardamos la versión "sucia" (original) para mostrarla si quieres, 
    // pero usamos la versión "limpia" como CLAVE para buscar.
    $clave = normalizar_clave($row['nombre_punto']);
    $mapa_puntos[$clave] = $row['id_punto'];
}

// =========================================================
// PASO 2: PROCESAR CSV
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    
    if ($_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        $ruta = $_FILES['archivo_csv']['tmp_name'];
        $handle = fopen($ruta, "r");
        
        $insertados = 0;
        $nuevos_tipos = 0;
        $fila_num = 0;
        
        // Preparar inserción de Máquina
        $stmt_maquina = $mysqli->prepare("INSERT IGNORE INTO maquina (device_id, id_punto, id_tipo_maquina) VALUES (?, ?, ?)");
        
        // Preparar inserción de Tipo de Máquina (por si aparece uno nuevo)
        $stmt_tipo = $mysqli->prepare("INSERT INTO tipo_maquina (nombre_tipo_maquina) VALUES (?)");
        
        $col = ['dev' => -1, 'pto' => -1, 'tip' => -1];

        $mysqli->begin_transaction();

        try {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $fila_num++;

                // --- DETECTAR COLUMNAS ---
                if ($fila_num === 1) {
                    foreach ($data as $i => $val) {
                        $v = strtoupper(normalizar_clave($val)); // Normalizamos el header también
                        if (strpos($v, 'DEVICEID') !== false) $col['dev'] = $i;
                        if (strpos($v, 'NOMBREDELPUNTO') !== false) $col['pto'] = $i;
                        if (strpos($v, 'TIPODEMAQUINA') !== false) $col['tip'] = $i;
                    }
                    if ($col['dev'] == -1 || $col['pto'] == -1) {
                        throw new Exception("No encontré las columnas DEVICE ID o NOMBRE DEL PUNTO.");
                    }
                    continue;
                }

                // --- VALIDAR FILA ---
                if (!isset($data[$col['dev']]) || !isset($data[$col['pto']])) continue;

                $raw_device = trim($data[$col['dev']]);
                $raw_punto  = trim(mb_convert_encoding($data[$col['pto']], 'UTF-8', 'ISO-8859-1'));
                $raw_tipo   = isset($data[$col['tip']]) ? trim(mb_convert_encoding($data[$col['tip']], 'UTF-8', 'ISO-8859-1')) : 'DESCONOCIDO';

                if (empty($raw_device) || empty($raw_punto)) continue;

                // --- 1. BUSCAR EL PUNTO (CRÍTICO) ---
                $clave_punto = normalizar_clave($raw_punto);
                
                if (isset($mapa_puntos[$clave_punto])) {
                    $id_punto_final = $mapa_puntos[$clave_punto];
                } else {
                    // SI NO EXISTE, LO REPORTAMOS Y SALTAMOS
                    $errores_log[] = "Fila $fila_num: El punto <b>'$raw_punto'</b> no existe en la BD (Device: $raw_device).";
                    continue; 
                }

                // --- 2. BUSCAR O CREAR TIPO DE MÁQUINA ---
                $clave_tipo = normalizar_clave($raw_tipo);
                $id_tipo_final = null;

                if (isset($mapa_tipos[$clave_tipo])) {
                    $id_tipo_final = $mapa_tipos[$clave_tipo];
                } else {
                    // Crear nuevo tipo al vuelo
                    $stmt_tipo->bind_param("s", $raw_tipo);
                    $stmt_tipo->execute();
                    $id_tipo_final = $mysqli->insert_id;
                    
                    // Guardar en memoria para la próxima vez
                    $mapa_tipos[$clave_tipo] = $id_tipo_final;
                    $nuevos_tipos++;
                }

                // --- 3. INSERTAR MÁQUINA ---
                // Insert Ignore: Si el Device ID ya existe, no hace nada (evita duplicados)
                $stmt_maquina->bind_param("sii", $raw_device, $id_punto_final, $id_tipo_final);
                $stmt_maquina->execute();
                
                if ($stmt_maquina->affected_rows > 0) $insertados++;
            }
            
            $mysqli->commit();
            $mensaje = "<div class='exito'>
                            🤖 <strong>¡PROCESO FINALIZADO!</strong><br>
                            Máquinas insertadas: <strong>$insertados</strong><br>
                            Nuevos Tipos de Máquina creados: <strong>$nuevos_tipos</strong>
                        </div>";

        } catch (Exception $e) {
            $mysqli->rollback();
            $mensaje = "<div class='error'>❌ Error Fatal: " . $e->getMessage() . "</div>";
        }

        fclose($handle);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importador de Máquinas</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef2f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn { background: #673ab7; color: white; border: none; padding: 15px 30px; font-size: 18px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; }
        .btn:hover { background: #5e35b1; }
        .exito { background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; text-align: center; margin-bottom: 20px; font-size: 1.2em; }
        .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; text-align: center; margin-bottom: 20px; }
        
        .log-container { margin-top: 30px; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
        .log-header { background: #d32f2f; color: white; padding: 10px 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .log-body { max-height: 300px; overflow-y: auto; background: #fafafa; padding: 0; }
        .log-item { padding: 10px 15px; border-bottom: 1px solid #eee; font-family: monospace; font-size: 14px; color: #555; }
        .log-item:last-child { border-bottom: none; }
        .log-item b { color: #d32f2f; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; color: #333;">🤖 Importador de Máquinas</h1>
        <p style="text-align: center; color: #666;">
            Vincula máquinas a Puntos existentes.<br>
            Si el punto no existe, <strong>se saltará y aparecerá en el reporte</strong> para gestión manual.
        </p>
        
        <?= $mensaje ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="text-align: center; padding: 40px; border: 2px dashed #ccc; margin-bottom: 20px;">
                <p>📂 Sube el archivo CSV de Máquinas</p>
                <input type="file" name="archivo_csv" accept=".csv" required>
            </div>
            <button type="submit" class="btn">PROCESAR MÁQUINAS</button>
        </form>

        <?php if (!empty($errores_log)): ?>
            <div class="log-container">
                <div class="log-header">
                    <span>⚠️ Puntos No Encontrados</span>
                    <span style="background: rgba(0,0,0,0.2); padding: 2px 8px; border-radius: 10px;"><?= count($errores_log) ?></span>
                </div>
                <div class="log-body">
                    <?php foreach ($errores_log as $err): ?>
                        <div class="log-item"><?= $err ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <p style="text-align: center; margin-top: 10px; color: #666; font-size: 0.9em;">
                * Copia esta lista. Estos son los puntos que debes crear manualmente o corregir el nombre en el CSV.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>