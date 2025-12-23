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
// PASO 0: CREAR EL "MUNICIPIO COMODÍN" (SALVAVIDAS)
// =========================================================
// Como la tabla punto exige id_municipio, creamos uno genérico para los que no tienen.
$id_muni_default = null;
$check_default = $mysqli->query("SELECT id_municipio FROM municipio WHERE nombre_municipio = 'SIN DEFINIR' LIMIT 1");

if ($check_default->num_rows > 0) {
    $row = $check_default->fetch_assoc();
    $id_muni_default = $row['id_municipio'];
} else {
    // Lo creamos si no existe
    $mysqli->query("INSERT INTO municipio (nombre_municipio, id_delegacion) VALUES ('SIN DEFINIR', NULL)");
    $id_muni_default = $mysqli->insert_id;
}

// =========================================================
// PASO 1: MEMORIZAR DATOS DE LA BD
// =========================================================
$mapa_clientes = [];
$res = $mysqli->query("SELECT id_cliente, nombre_cliente FROM cliente");
while ($row = $res->fetch_assoc()) {
    $mapa_clientes[mb_strtoupper(trim($row['nombre_cliente']))] = $row['id_cliente'];
}

$mapa_municipios = [];
$res = $mysqli->query("SELECT id_municipio, nombre_municipio FROM municipio");
while ($row = $res->fetch_assoc()) {
    $mapa_municipios[mb_strtoupper(trim($row['nombre_municipio']))] = $row['id_municipio'];
}

// =========================================================
// PASO 2: PROCESAR EL CSV
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {

    if ($_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        $ruta = $_FILES['archivo_csv']['tmp_name'];
        $handle = fopen($ruta, "r");

        $insertados = 0;
        $fila_num = 0;

        $stmt = $mysqli->prepare("INSERT IGNORE INTO punto (nombre_punto, direccion, codigo_1, codigo_2, id_municipio, id_cliente) VALUES (?, ?, ?, ?, ?, ?)");

        $col = ['nom' => -1, 'dir' => -1, 'c1' => -1, 'c2' => -1, 'mun' => -1, 'cli' => -1, 'del' => -1];

        $mysqli->begin_transaction();

        try {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $fila_num++;

                // --- DETECTAR COLUMNAS ---
                if ($fila_num === 1) {
                    foreach ($data as $i => $val) {
                        $v = strtoupper(trim($val));
                        if (strpos($v, 'NOMBRE DEL PUNTO') !== false) $col['nom'] = $i;
                        if (strpos($v, 'DIRECCIÓN') !== false || strpos($v, 'DIRECCION') !== false) $col['dir'] = $i;
                        if (strpos($v, 'COD 1') !== false) $col['c1'] = $i;
                        if (strpos($v, 'COD 2') !== false) $col['c2'] = $i;
                        if (strpos($v, 'MUNICIPIO') !== false) $col['mun'] = $i;
                        if (strpos($v, 'CLIENTE') !== false) $col['cli'] = $i;
                        if (strpos($v, 'DELEGACI') !== false) $col['del'] = $i;
                    }
                    continue;
                }

                if (!isset($data[$col['nom']])) continue;

                // --- DATOS ---
                $raw_nombre = trim(mb_convert_encoding($data[$col['nom']], 'UTF-8', 'ISO-8859-1'));
                $raw_cli    = mb_strtoupper(trim(mb_convert_encoding($data[$col['cli']], 'UTF-8', 'ISO-8859-1')));

                // Si el nombre del punto está vacío, saltamos
                if (empty($raw_nombre)) continue;

                $raw_dir = ($col['dir'] >= 0) ? trim(mb_convert_encoding($data[$col['dir']], 'UTF-8', 'ISO-8859-1')) : '';
                $raw_c1  = ($col['c1'] >= 0) ? trim($data[$col['c1']]) : null;
                $raw_c2  = ($col['c2'] >= 0) ? trim($data[$col['c2']]) : null;
                $raw_mun = ($col['mun'] >= 0) ? mb_strtoupper(trim(mb_convert_encoding($data[$col['mun']], 'UTF-8', 'ISO-8859-1'))) : '.';
                $raw_del = ($col['del'] >= 0) ? mb_strtoupper(trim(mb_convert_encoding($data[$col['del']], 'UTF-8', 'ISO-8859-1'))) : '';

                // --- CLIENTE (OBLIGATORIO) ---
                if (isset($mapa_clientes[$raw_cli])) {
                    $id_cliente_final = $mapa_clientes[$raw_cli];
                } else {
                    $errores_log[] = "Fila $fila_num: Cliente '$raw_cli' no existe en BD.";
                    continue; // Este sí debemos saltarlo porque la BD no deja meter clientes nulos
                }

                // --- MUNICIPIO (FLEXIBLE) ---
                $id_municipio_final = $id_muni_default; // Por defecto: SIN DEFINIR

                // 1. Intentamos buscar el nombre exacto si existe
                if (isset($mapa_municipios[$raw_mun])) {
                    $id_municipio_final = $mapa_municipios[$raw_mun];
                }
                // 2. Si no, intentamos con la Delegación (como plan B si es que existe como municipio)
                elseif (isset($mapa_municipios[$raw_del])) {
                    $id_municipio_final = $mapa_municipios[$raw_del];
                }

                // NOTA: Si ninguno coincide, se queda con $id_muni_default ("SIN DEFINIR")
                // Así que NUNCA fallará por culpa del municipio.

                // --- INSERTAR ---
                $stmt->bind_param("ssssii", $raw_nombre, $raw_dir, $raw_c1, $raw_c2, $id_municipio_final, $id_cliente_final);
                $stmt->execute();

                if ($stmt->affected_rows > 0) $insertados++;
            }

            $mysqli->commit();
            $mensaje = "<div class='exito'>✅ <strong>¡IMPORTACIÓN FINALIZADA!</strong><br>Puntos insertados correctamente: <strong>$insertados</strong><br><small>(Los puntos sin municipio se guardaron como 'SIN DEFINIR')</small></div>";
        } catch (Exception $e) {
            $mysqli->rollback();
            $mensaje = "<div class='error'>❌ Error Fatal: " . $e->getMessage() . "</div>";
        }

        fclose($handle);
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Importador Puntos (Modo Seguro)</title>
    <style>
        body {
            font-family: sans-serif;
            background: #eef2f5;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 900px;
            margin: auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }

        .btn:hover {
            background: #0069d9;
        }

        .exito {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.2em;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }

        .log-box {
            max-height: 200px;
            overflow-y: auto;
            background: #333;
            color: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 style="text-align: center; color: #333;">📍 Carga de Puntos (Sin Bloqueos)</h1>
        <p style="text-align: center;">Si un punto no tiene municipio, se guardará como <strong>"SIN DEFINIR"</strong> automáticamente.</p>

        <?= $mensaje ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="text-align: center; padding: 40px; border: 2px dashed #ccc; margin-bottom: 20px;">
                <input type="file" name="archivo_csv" accept=".csv" required>
            </div>
            <button type="submit" class="btn">🚀 IMPORTAR AHORA</button>
        </form>

        <?php if (!empty($errores_log)): ?>
            <div style="margin-top:20px; font-weight:bold; color:red;">⚠️ Errores de Clientes (No encontrados en BD):</div>
            <div class="log-box">
                <?php foreach ($errores_log as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>