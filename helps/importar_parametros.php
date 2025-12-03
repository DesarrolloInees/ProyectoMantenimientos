<?php
// --- CONFIGURACIÓN ---
ini_set('memory_limit', '1024M');
set_time_limit(600);

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
// FUNCIONES AUXILIARES
// =========================================================

// 1. Limpiar precios (Quitar signos $ y puntos)
function limpiar_precio($valor) {
    // "$ 197.172" -> 197172
    return (float) preg_replace('/[^0-9]/', '', $valor);
}

// 2. Normalizar texto
function normalizar($str) {
    $str = mb_strtoupper(trim($str), 'UTF-8');
    return str_replace(['Á','É','Í','Ó','Ú','Ñ'], ['A','E','I','O','U','N'], $str);
}

// 3. Buscar IDs de tipos de máquina basados en el texto del Excel
function buscar_ids_maquina($mysqli, $texto_raw) {
    $ids_encontrados = [];
    $texto = normalizar($texto_raw);

    // Caso especial "TODAS LAS MAQUINAS"
    if (strpos($texto, 'TODAS') !== false) {
        $res = $mysqli->query("SELECT id_tipo_maquina FROM tipo_maquina");
        while($row = $res->fetch_assoc()) $ids_encontrados[] = $row['id_tipo_maquina'];
        return $ids_encontrados;
    }

    // Dividimos por guiones, comas o espacios para buscar coincidencias parciales
    // Ej: "SDM-500_801" -> Busca "SDM", "500", "801"
    $tokens = preg_split('/[\s,_\-]+/', $texto);
    
    // Traemos todas las máquinas de la BD para comparar
    $res = $mysqli->query("SELECT id_tipo_maquina, nombre_tipo_maquina FROM tipo_maquina");
    while($row = $res->fetch_assoc()) {
        $nombre_bd = normalizar($row['nombre_tipo_maquina']);
        // Chequeo simple: Si el nombre de la BD está contenido en el texto del Excel (o viceversa)
        // Ej: BD="SDM-500", Excel="SDM500, SDM801"
        
        // Limpiamos guiones para comparar "SDM500" con "SDM-500"
        $n_bd_clean = str_replace('-', '', $nombre_bd);
        $n_ex_clean = str_replace(['-','_'], '', $texto);

        // Si hay coincidencia fuerte
        if (strpos($n_ex_clean, $n_bd_clean) !== false) {
            $ids_encontrados[] = $row['id_tipo_maquina'];
        }
    }
    return array_unique($ids_encontrados);
}

// 4. Buscar ID de mantenimiento
function buscar_id_mantenimiento($mysqli, $texto) {
    $texto = normalizar($texto);
    $res = $mysqli->query("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento");
    while($row = $res->fetch_assoc()) {
        $bd = normalizar($row['nombre_completo']);
        // Comparacion flexible: "Preventivo Basico" vs "Preventivo Básico"
        if (strpos($texto, $bd) !== false || strpos($bd, $texto) !== false) {
            return $row['id_tipo_mantenimiento'];
        }
        // Mapeo manual para casos difíciles
        if (strpos($texto, 'FALLIDO') !== false && strpos($bd, 'FALLIDA') !== false) return $row['id_tipo_mantenimiento'];
    }
    return null;
}

// =========================================================
// PROCESAMIENTO
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    if ($_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        $ruta = $_FILES['archivo_csv']['tmp_name'];
        
        // --- PARTE A: TARIFAS (Filas 7 a 20 aprox) ---
        $handle = fopen($ruta, "r");
        $fila = 0;
        $tarifas_insertadas = 0;
        $muni_actualizados = 0;
        $muni_nuevos = 0;
        
        // Vaciamos tabla tarifas para no duplicar (Opcional, pero recomendado para recargas)
        $mysqli->query("TRUNCATE TABLE tarifa");

        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $fila++;

            // Detectar bloque de precios (tiene signo $ en columna 2)
            if (isset($data[2]) && strpos($data[2], '$') !== false) {
                
                $txt_maquinas = $data[0];
                $txt_mant     = $data[1];
                $precio       = limpiar_precio($data[2]);

                // Determinar Zona (Urbano vs Interurbano)
                // En tu archivo, las filas 7-13 parecen Urbanas y 14-20 Interurbanas (por el precio más alto)
                // O podemos detectar mayúsculas/minúsculas en "CORRECTIVO" vs "Correctivo"
                // Asumiremos por rango de fila o precio. 
                // Mejor lógica: Si fila < 14 es Urbano (1), si >= 14 es Interurbano (2)
                $id_zona = ($fila < 14) ? 1 : 2; 

                $ids_maquinas = buscar_ids_maquina($mysqli, $txt_maquinas);
                $id_mant      = buscar_id_mantenimiento($mysqli, $txt_mant);

                if ($id_mant && !empty($ids_maquinas)) {
                    $stmt = $mysqli->prepare("INSERT INTO tarifa (id_tipo_maquina, id_tipo_mantenimiento, id_tipo_zona, precio) VALUES (?, ?, ?, ?)");
                    foreach ($ids_maquinas as $id_maq) {
                        $stmt->bind_param("iiid", $id_maq, $id_mant, $id_zona, $precio);
                        $stmt->execute();
                        $tarifas_insertadas++;
                    }
                    $stmt->close();
                }
            }
        }
        fclose($handle);

        // --- PARTE B: MUNICIPIOS (Filas 23 en adelante) ---
        // Necesitamos leer de nuevo o guardar en memoria. Leemos de nuevo para ser limpios.
        $handle = fopen($ruta, "r");
        $fila = 0;
        
        // Definimos las columnas donde hay datos: [IndiceColumna => 'CiudadPadre']
        // Basado en tu archivo: 
        // Col 1 (Bajo Bogota), Col 4 (Bajo Medellin), Col 7 (Bajo B/quilla), Col 10 (Bajo Cali)
        $contexto_columnas = [
            1 => ['delegacion' => 'BOGOTA', 'zona' => 1], 
            4 => ['delegacion' => 'MEDELLIN', 'zona' => 1],
            7 => ['delegacion' => 'BARRANQUILLA', 'zona' => 1],
            10 => ['delegacion' => 'CALI', 'zona' => 1]
        ];

        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $fila++;
            if ($fila < 23) continue; // Saltar parte de precios

            // Revisamos las 4 columnas principales
            foreach ($contexto_columnas as $col_idx => $ctx) {
                // 1. Detectar Cambios de Cabecera (Ej: "EXTERNOS BOGOTA")
                // Miramos la celda anterior (col_idx - 1)
                $cabecera = isset($data[$col_idx - 1]) ? normalizar($data[$col_idx - 1]) : '';
                
                if (strpos($cabecera, 'EXTERNOS') !== false || strpos($cabecera, 'INTERURBANO') !== false) {
                    $contexto_columnas[$col_idx]['zona'] = 2; // Cambiamos a INTERURBANO
                    // Si dice "EXTERNOS MEDELLIN", actualizamos la delegación padre
                    if (strpos($cabecera, 'MEDELLIN') !== false) $contexto_columnas[$col_idx]['delegacion'] = 'MEDELLIN';
                    if (strpos($cabecera, 'BOGOTA') !== false) $contexto_columnas[$col_idx]['delegacion'] = 'BOGOTA';
                    if (strpos($cabecera, 'CALI') !== false) $contexto_columnas[$col_idx]['delegacion'] = 'CALI';
                    if (strpos($cabecera, 'BARRAN') !== false) $contexto_columnas[$col_idx]['delegacion'] = 'BARRANQUILLA';
                }

                // 2. Leer Municipio
                if (isset($data[$col_idx]) && !empty(trim($data[$col_idx]))) {
                    $nombre_muni = normalizar($data[$col_idx]);
                    
                    // Ignorar palabras clave que no son municipios
                    if ($nombre_muni == 'URBANOS' || $nombre_muni == 'CHIA' && $fila == 24) { 
                        // Nota: CHIA fila 24 es un caso real, no ignorar si es dato
                    }
                    if (in_array($nombre_muni, ['URBANOS', 'EXTERNOS', 'MUNICIPIO', 'CIUDAD'])) continue;

                    // 3. Insertar o Actualizar en BD
                    $zona_actual = $contexto_columnas[$col_idx]['zona'];
                    $del_actual  = $contexto_columnas[$col_idx]['delegacion'];

                    // Obtener ID Delegación Padre
                    $res_del = $mysqli->query("SELECT id_delegacion FROM delegacion WHERE nombre_delegacion LIKE '%$del_actual%' LIMIT 1");
                    $id_del_bd = ($res_del->num_rows > 0) ? $res_del->fetch_assoc()['id_delegacion'] : 1; // 1 por defecto

                    // Verificar si existe municipio
                    $check = $mysqli->prepare("SELECT id_municipio FROM municipio WHERE nombre_municipio = ?");
                    $check->bind_param("s", $nombre_muni);
                    $check->execute();
                    $res_check = $check->get_result();

                    if ($res_check->num_rows > 0) {
                        // ACTUALIZAR (Ya existe, solo le ponemos la zona correcta)
                        $id_existente = $res_check->fetch_assoc()['id_municipio'];
                        $upd = $mysqli->query("UPDATE municipio SET id_tipo_zona = $zona_actual WHERE id_municipio = $id_existente");
                        $muni_actualizados++;
                    } else {
                        // INSERTAR (No existe, lo creamos nuevo)
                        $ins = $mysqli->prepare("INSERT INTO municipio (nombre_municipio, id_delegacion, id_tipo_zona) VALUES (?, ?, ?)");
                        $ins->bind_param("sii", $nombre_muni, $id_del_bd, $zona_actual);
                        $ins->execute();
                        $muni_nuevos++;
                    }
                }
            }
        }
        fclose($handle);

        $mensaje = "<div class='exito'>
            🚀 <strong>¡Importación Parametrizada Exitosa!</strong><br>
            <ul>
                <li>Tarifas creadas: <strong>$tarifas_insertadas</strong></li>
                <li>Municipios actualizados (Zona asignada): <strong>$muni_actualizados</strong></li>
                <li>Municipios nuevos creados: <strong>$muni_nuevos</strong></li>
            </ul>
        </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Tarifas y Zonas</title>
    <style>
        body { font-family: sans-serif; background: #eef2f5; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: auto; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn { background: #28a745; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 4px; cursor: pointer; margin-top: 15px; }
        .exito { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: left; }
    </style>
</head>
<body>
    <div class="card">
        <h1>💰 Importador Maestro</h1>
        <p>Sube el archivo <b>HORAS GENERALES (1).csv</b>.</p>
        <p>Este script actualizará precios y definirá qué municipios son Urbanos o Interurbanos.</p>
        <?= $mensaje ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="archivo_csv" accept=".csv" required style="margin: 20px 0;">
            <br>
            <button type="submit" class="btn">Procesar Archivo</button>
        </form>
    </div>
</body>
</html>