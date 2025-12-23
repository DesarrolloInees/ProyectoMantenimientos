<?php
// --- CONFIGURACIÓN ---
ini_set('memory_limit', '512M');
set_time_limit(300);

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'inees_mantenimientos'; // <--- ¡ASEGÚRATE QUE ESTO SEA CORRECTO!

// Nombre temporal para trabajar el archivo subido
$archivo_trabajo = 'temp_importacion.csv';

$mensaje = "";
$step = 1; // Controla qué pantalla mostramos (1: Subir, 2: Corregir, 3: Fin)
$clientes_con_problemas = [];

// Conexión segura con reporte de errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($host, $user, $pass, $db);
    $mysqli->set_charset("utf8");
} catch (Exception $e) {
    die("<div style='color:red; padding:20px;'>❌ Error fatal de conexión a la Base de Datos: " . $e->getMessage() . "<br>Verifica usuario, contraseña y nombre de la BD.</div>");
}

// =========================================================
// LÓGICA: RECEPCIÓN DEL ARCHIVO (PASO 1 -> 2)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    if ($_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['archivo_csv']['tmp_name'], $archivo_trabajo);
        $step = 2; // Pasamos a analizar
    } else {
        $mensaje = "<div class='error'>❌ Error al subir el archivo. Código: " . $_FILES['archivo_csv']['error'] . "</div>";
    }
}

// =========================================================
// LÓGICA: PROCESAR INSERCIÓN (PASO 2 -> 3)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correcciones'])) {
    if (file_exists($archivo_trabajo)) {
        $correcciones = $_POST['correcciones'];
        $insertados = 0;

        // Iniciamos transacción
        $mysqli->begin_transaction();

        try {
            // Preparamos la consulta
            $stmt = $mysqli->prepare("INSERT IGNORE INTO cliente (codigo_cliente, nombre_cliente) VALUES (?, ?)");

            $handle = fopen($archivo_trabajo, "r");
            $fila = 0;

            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $fila++;
                // Validaciones para saltar cabeceras o vacíos
                if ($fila == 1 && (stripos($data[0], 'COD') !== false)) continue;
                if (empty($data[0])) continue;

                $cod_orig = trim($data[0]);
                // Corrección de tildes y ñ
                $nom = trim(mb_convert_encoding($data[1], 'UTF-8', 'ISO-8859-1'));

                // Usamos tu corrección o el original
                $codigo_final = isset($correcciones[$nom]) ? $correcciones[$nom] : $cod_orig;

                $stmt->bind_param("ss", $codigo_final, $nom);
                $stmt->execute();

                if ($stmt->affected_rows > 0) $insertados++;
            }
            fclose($handle);

            // Cerramos la preparación SOLO si existe
            if (isset($stmt) && $stmt) {
                $stmt->close();
            }

            $mysqli->commit();

            // Limpieza final
            @unlink($archivo_trabajo);

            $mensaje = "<div class='exito'>🚀 <strong>¡Proceso Terminado!</strong><br>Se han insertado $insertados clientes correctamente.</div>";
            $step = 3;
        } catch (Exception $e) {
            $mysqli->rollback();
            $mensaje = "<div class='error'>❌ Error durante el proceso: " . $e->getMessage() . "</div>";
        }
    } else {
        $mensaje = "<div class='error'>⚠️ La sesión expiró. Vuelve a subir el archivo.</div>";
        $step = 1;
    }
}

// =========================================================
// LÓGICA: ANÁLISIS DE CONFLICTOS (PASO 2)
// =========================================================
if ($step === 2 && file_exists($archivo_trabajo)) {
    $conflictos = [];
    $handle = fopen($archivo_trabajo, "r");
    $fila = 0;
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        $fila++;
        if ($fila == 1 && (stripos($data[0], 'COD') !== false)) continue;
        if (empty($data[0])) continue;

        $cod = trim($data[0]);
        $nom = trim(mb_convert_encoding($data[1], 'UTF-8', 'ISO-8859-1'));
        $conflictos[$nom][] = $cod;
    }
    fclose($handle);

    foreach ($conflictos as $cliente => $lista) {
        $unicos = array_unique($lista);
        if (count($unicos) > 1) {
            $clientes_con_problemas[$cliente] = $unicos;
        }
    }

    if (empty($clientes_con_problemas)) {
        $mensaje = "<div class='info'>✅ No hay conflictos. Dale a Guardar.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Importador de Clientes</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef2f5;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 800px;
            margin: auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .upload-area {
            border: 2px dashed #007bff;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            background: #f8faff;
            margin: 20px 0;
        }

        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-success {
            background: #28a745;
            width: 100%;
            padding: 15px;
            font-size: 18px;
            margin-top: 20px;
        }

        .conflicto-row {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .exito {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }

        .info {
            background: #cce5ff;
            color: #004085;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }

        label {
            margin-left: 10px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>🏭 Importador de Clientes</h1>
        <?= $mensaje ?>

        <?php if ($step === 1): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area">
                    <h3>📂 Paso 1: Sube tu archivo CSV</h3>
                    <input type="file" name="archivo_csv" accept=".csv" required>
                    <br><br>
                    <button type="submit" class="btn">Analizar Archivo</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($step === 2): ?>
            <form method="POST">
                <?php if (!empty($clientes_con_problemas)): ?>
                    <h3>⚠️ Paso 2: Resuelve los conflictos</h3>
                    <?php foreach ($clientes_con_problemas as $cliente => $codigos): ?>
                        <div class="conflicto-row">
                            <div style="font-weight:bold; color:#d32f2f; width:40%"><?= $cliente ?></div>
                            <div style="width:60%">
                                <?php foreach ($codigos as $cod): ?>
                                    <label>
                                        <input type="radio" name="correcciones[<?= htmlspecialchars($cliente) ?>]" value="<?= htmlspecialchars($cod) ?>" required>
                                        <?= $cod ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <input type="hidden" name="correcciones[ok]" value="ok">
                <?php endif; ?>

                <button type="submit" class="btn btn-success">💾 Guardar en Base de Datos</button>
            </form>
        <?php endif; ?>

        <?php if ($step === 3): ?>
            <div style="text-align:center; margin-top:20px;">
                <a href="index.php" class="btn">🔄 Subir otro archivo</a>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>