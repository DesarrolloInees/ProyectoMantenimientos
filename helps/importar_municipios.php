<?php
// --- CONFIGURACIÓN ---
ini_set('memory_limit', '512M');
set_time_limit(300);

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'inees_mantenimientos';

$mensaje = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($host, $user, $pass, $db);
    $mysqli->set_charset("utf8mb4");
} catch (Exception $e) {
    die("❌ Error conexión BD: " . $e->getMessage());
}

// =========================================================
// PASO 1: CARGAR DELEGACIONES EN MEMORIA (Para obtener sus IDs)
// =========================================================
// Hacemos esto para no hacer 5000 consultas a la BD. Traemos todas las delegaciones a un Array.
$mapa_delegaciones = []; // Ejemplo: ['BOGOTA' => 1, 'CALI' => 2]
$res = $mysqli->query("SELECT id_delegacion, nombre_delegacion FROM delegacion");
while ($row = $res->fetch_assoc()) {
    // Guardamos en mayúsculas para comparar fácil
    $mapa_delegaciones[strtoupper($row['nombre_delegacion'])] = $row['id_delegacion'];
}

// =========================================================
// PASO 2: PROCESAR ARCHIVO
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {

    if ($_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        $ruta = $_FILES['archivo_csv']['tmp_name'];
        $handle = fopen($ruta, "r");

        $insertados = 0;
        $saltados = 0; // Por si no encontramos la delegación

        // Preparamos consulta: Insertamos Municipio y su ID de Delegación
        // Usamos INSERT IGNORE para no repetir si el municipio ya existe con esa delegación
        // Nota: Asumo que en tu tabla el nombre_municipio NO es único globalmente (puede haber municipios con mismo nombre en dif delegaciones?), 
        // pero si quieres evitar duplicados, asegúrate de tener un índice UNIQUE o usar lógica extra.
        // Aquí usaremos una verificación previa simple.

        $stmt_check = $mysqli->prepare("SELECT id_municipio FROM municipio WHERE nombre_municipio = ? AND id_delegacion = ?");
        $stmt_insert = $mysqli->prepare("INSERT INTO municipio (nombre_municipio, id_delegacion) VALUES (?, ?)");

        // Variables para detectar columnas dinámicamente
        $idx_muni = -1;
        $idx_del  = -1;
        $idx_dir  = -1;
        $fila = 0;

        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $fila++;

            // --- A. DETECTAR COLUMNAS AUTOMÁTICAMENTE ---
            if ($fila === 1) {
                foreach ($data as $i => $col) {
                    $col = strtoupper(trim($col));
                    // Buscamos palabras clave en los títulos
                    if (strpos($col, 'MUNICIPIO') !== false) $idx_muni = $i;
                    if (strpos($col, 'DELEGACI') !== false)  $idx_del  = $i;
                    if (strpos($col, 'DIRECCI') !== false)   $idx_dir  = $i;
                }
                if ($idx_del === -1) {
                    $mensaje = "<div class='error'>❌ No encontré la columna DELEGACIÓN.</div>";
                    break;
                }
                continue;
            }

            // --- B. EXTRAER DATOS ---
            // Validar que tengamos datos en la fila
            if (!isset($data[$idx_del])) continue;

            $raw_delegacion = mb_strtoupper(trim(mb_convert_encoding($data[$idx_del], 'UTF-8', 'ISO-8859-1')));
            $raw_municipio  = ($idx_muni >= 0) ? mb_strtoupper(trim(mb_convert_encoding($data[$idx_muni], 'UTF-8', 'ISO-8859-1'))) : '.';
            $raw_direccion  = ($idx_dir >= 0)  ? mb_convert_encoding($data[$idx_dir], 'UTF-8', 'ISO-8859-1') : '';

            // --- C. BUSCAR ID DE LA DELEGACIÓN ---
            if (!isset($mapa_delegaciones[$raw_delegacion])) {
                // Si la delegación del Excel no existe en la BD, no podemos crear el municipio
                // (Opcional: Podrías crearla al vuelo, pero mejor mantener integridad)
                $saltados++;
                continue;
            }
            $id_delegacion_bd = $mapa_delegaciones[$raw_delegacion];

            // --- D. INTELIGENCIA PARA EL NOMBRE DEL MUNICIPIO ---
            $nombre_final_municipio = "";

            if (strlen($raw_municipio) > 2 && $raw_municipio !== '.') {
                // CASO 1: El municipio viene escrito explícitamente (ej: MAICAO)
                $nombre_final_municipio = $raw_municipio;
            } else {
                // CASO 2: El municipio es un punto "." o vacío. Intentamos rescatarlo.

                // Intento A: Buscar el Slash "/" en la dirección (Para el caso Yumbo)
                // Ej: "Bodega 7 / Yumbo, Valle del Cauca"
                $partes_dir = explode('/', $raw_direccion);
                if (count($partes_dir) > 1) {
                    // Tomamos la parte después del slash
                    $posible_muni = trim($partes_dir[1]);
                    // Quitamos cosas extra como ", Valle del Cauca"
                    $posible_muni = explode(',', $posible_muni)[0];
                    $posible_muni = mb_strtoupper(trim($posible_muni));

                    // Validación básica: que no sea muy largo ni muy corto
                    if (strlen($posible_muni) > 2 && strlen($posible_muni) < 40) {
                        $nombre_final_municipio = $posible_muni;
                    }
                }

                // Intento B: Si falló lo anterior, asumimos que es la Capital (Misma Delegación)
                if ($nombre_final_municipio == "") {
                    $nombre_final_municipio = $raw_delegacion;
                }
            }

            // --- E. INSERTAR EN BD (Evitando duplicados) ---

            // 1. Verificamos si ya existe ese par (Municipio + Delegacion)
            $stmt_check->bind_param("si", $nombre_final_municipio, $id_delegacion_bd);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows == 0) {
                // 2. Si no existe, insertamos
                $stmt_insert->bind_param("si", $nombre_final_municipio, $id_delegacion_bd);
                $stmt_insert->execute();
                $insertados++;
            }
        }

        fclose($handle);

        $mensaje = "<div class='exito'>
            🎉 <strong>¡Proceso Terminado!</strong><br>
            Municipios nuevos insertados: <strong>$insertados</strong><br>
            Filas saltadas (Delegación no encontrada): $saltados
        </div>";
    } else {
        $mensaje = "<div class='error'>❌ Error subiendo archivo.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Importar Municipios</title>
    <style>
        body {
            font-family: sans-serif;
            background: #eef2f5;
            padding: 40px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            margin: auto;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn {
            background: #6f42c1;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 15px;
        }

        .exito {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>🏙️ Importar Municipios</h1>
        <p>Este script insertará municipios y los vinculará con su Delegación (ID).</p>
        <?= $mensaje ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="archivo_csv" accept=".csv" required style="margin: 20px 0;">
            <br>
            <button type="submit" class="btn">Procesar Municipios</button>
        </form>
    </div>
</body>

</html>