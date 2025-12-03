<?php
// --- CONFIGURACIÓN ---
ini_set('memory_limit', '1024M');
set_time_limit(0); 
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'inees_mantenimientos';

$mensaje = "";
$tipo_mensaje = "";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($host, $user, $pass, $db);
    $mysqli->set_charset("utf8mb4");
} catch (Exception $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// =========================================================
// LÓGICA DE PROCESAMIENTO
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    
    if ($_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        $ruta_archivo = $_FILES['archivo_csv']['tmp_name'];
        
        // 1. Detectar delimitador automáticamente
        $handle_temp = fopen($ruta_archivo, "r");
        $primera_linea = fgets($handle_temp);
        fclose($handle_temp);
        $delimitador = (strpos($primera_linea, ';') !== false) ? ';' : ',';

        $handle = fopen($ruta_archivo, "r");
        if ($handle) {
            $fila = 0;
            $stats = [
                'delegaciones_creadas' => 0,
                'puntos_vinculados' => 0,
                'errores' => 0
            ];

            // Índices de columnas (Se detectarán automáticamente)
            $idx_punto = -1; 
            $idx_codigo = -1; 
            $idx_nombre_del = -1;

            // PREPARAR CONSULTAS
            // 1. Insertar Delegación (Si existe, actualiza el nombre)
            //    Usamos el ID del Excel explícitamente.
            $sqlDel = "INSERT INTO delegacion (id_delegacion, nombre_delegacion) VALUES (?, ?) 
                       ON DUPLICATE KEY UPDATE nombre_delegacion = VALUES(nombre_delegacion)";
            $stmtDel = $mysqli->prepare($sqlDel);

            // 2. Actualizar el Punto (Solo vincula, NO crea puntos nuevos)
            $sqlPunto = "UPDATE punto SET id_delegacion = ? WHERE nombre_punto = ?";
            $stmtPunto = $mysqli->prepare($sqlPunto);

            $mysqli->begin_transaction();

            try {
                while (($data = fgetcsv($handle, 10000, $delimitador)) !== FALSE) {
                    $fila++;

                    // --- DETECTAR COLUMNAS (FILA 1) ---
                    if ($fila === 1) {
                        // Quitamos el BOM si existe para leer bien la primera columna
                        $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                        
                        foreach ($data as $i => $val) {
                            $header = mb_strtoupper(trim($val), 'UTF-8');
                            
                            // Buscar columna PUNTO
                            if (strpos($header, 'NOMBRE DEL PUNTO') !== false) $idx_punto = $i;
                            
                            // Buscar columna CÓDIGO (En tu archivo es "DELEGACION" sin tilde)
                            if ($header === 'DELEGACION' || $header === 'CODIGO') $idx_codigo = $i;
                            
                            // Buscar columna NOMBRE DELEGACIÓN (En tu archivo es "DELEGACIÓN" con tilde)
                            if ($header === 'DELEGACIÓN' || $header === 'NOMBRE DELEGACION') $idx_nombre_del = $i;
                        }

                        if ($idx_punto === -1 || $idx_codigo === -1) {
                            throw new Exception("No se encontraron las columnas necesarias ('NOMBRE DEL PUNTO' y 'DELEGACION'). Revisa el archivo.");
                        }
                        continue;
                    }

                    // --- PROCESAR DATOS ---
                    if (isset($data[$idx_codigo]) && isset($data[$idx_punto])) {
                        
                        // 1. Obtener Datos y Limpiar
                        $codigo_raw = trim($data[$idx_codigo]);
                        $nombre_punto = trim($data[$idx_punto]); // Asumiendo UTF-8 directo
                        
                        // Nombre Delegación: Si no lo encuentra, usa "DELEGACION X"
                        $nombre_del = ($idx_nombre_del >= 0 && isset($data[$idx_nombre_del])) 
                                      ? trim($data[$idx_nombre_del]) 
                                      : "DELEGACION $codigo_raw";

                        // 2. Unificar Código (001 -> 1)
                        $id_delegacion = intval($codigo_raw);

                        if ($id_delegacion > 0 && !empty($nombre_punto)) {
                            
                            // A. Insertar/Actualizar Delegación en la tabla maestra
                            $nombre_del_upper = mb_strtoupper($nombre_del, 'UTF-8');
                            $stmtDel->bind_param("is", $id_delegacion, $nombre_del_upper);
                            $stmtDel->execute();
                            if ($stmtDel->affected_rows > 0 && $stmtDel->affected_rows != 2) { 
                                // affected_rows 1=insert, 2=update. Contamos inserciones reales.
                                $stats['delegaciones_creadas']++; 
                            }

                            // B. Vincular al Punto
                            $stmtPunto->bind_param("is", $id_delegacion, $nombre_punto);
                            $stmtPunto->execute();
                            
                            if ($stmtPunto->affected_rows > 0) {
                                $stats['puntos_vinculados']++;
                            }
                        }
                    }
                }

                $mysqli->commit();
                
                $tipo_mensaje = "success";
                $mensaje = "<h3>✅ Importación Exitosa</h3>
                            <ul>
                                <li><strong>Delegaciones procesadas:</strong> El sistema aseguró que existan los IDs correctos.</li>
                                <li><strong>Puntos actualizados:</strong> {$stats['puntos_vinculados']}</li>
                            </ul>
                            <p>Los puntos ahora están conectados a su delegación correcta (001 → 1).</p>";

                if ($stats['puntos_vinculados'] == 0) {
                    $tipo_mensaje = "warning";
                    $mensaje .= "<br>⚠️ <strong>Atención:</strong> No se vinculó ningún punto. <br>
                                    Verifica que los nombres en la columna 'NOMBRE DEL PUNTO' sean idénticos a los de tu base de datos.";
                }

            } catch (Exception $e) {
                $mysqli->rollback();
                $tipo_mensaje = "error";
                $mensaje = "Error: " . $e->getMessage();
            }
            fclose($handle);
        }
    } else {
        $tipo_mensaje = "error";
        $mensaje = "Error al subir archivo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Delegaciones</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">📍 Vincular Delegaciones</h2>
        <p class="text-gray-500 text-sm text-center mb-6">Sube el CSV para conectar Puntos con Delegaciones</p>

        <?php if ($mensaje): ?>
            <div class="mb-4 p-4 rounded <?= $tipo_mensaje == 'success' ? 'bg-green-100 text-green-700' : ($tipo_mensaje == 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition cursor-pointer">
                <input type="file" name="archivo_csv" id="file" accept=".csv" class="hidden" required onchange="document.getElementById('filename').innerText = this.files[0].name">
                <label for="file" class="cursor-pointer">
                    <i class="fas fa-cloud-upload-alt text-4xl text-blue-500 mb-2"></i>
                    <p class="font-medium text-gray-700">Clic para seleccionar CSV</p>
                    <p id="filename" class="text-sm text-gray-500 mt-2"></p>
                </label>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                🚀 Procesar Archivo
            </button>
        </form>
    </div>

</body>
</html>