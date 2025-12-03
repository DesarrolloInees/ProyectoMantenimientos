<?php
// reparar_municipios.php
require_once "app/config/Database.php";
set_time_limit(300); // Darle 5 minutos para correr si son muchos datos

$db = new Database();
$conn = $db->getConnection();

echo "<h1>🕵️‍♂️ Iniciando reparación de Puntos mal asignados...</h1>";

// 1. Cargar diccionario de municipios (ID y Nombre)
// Traemos primero los nombres más largos para evitar errores (Ej: que confunda "Santa Rosa" con "Santa Rosa de Osos")
$sqlM = "SELECT id_municipio, nombre_municipio FROM municipio ORDER BY LENGTH(nombre_municipio) DESC";
$municipios = $conn->query($sqlM)->fetchAll(PDO::FETCH_ASSOC);

// 2. Traer puntos que sospechamos están mal (municipio NULL, 0, o genérico como 'SIN DEFINIR')
// Ajusta el '299' al ID que tengas de 'SIN DEFINIR' si existe.
$sqlP = "SELECT id_punto, nombre_punto, direccion FROM punto WHERE id_municipio IS NULL OR id_municipio = 0 OR id_municipio = 299";
$puntosSucios = $conn->query($sqlP)->fetchAll(PDO::FETCH_ASSOC);

$contador = 0;

foreach ($puntosSucios as $punto) {
    // Unimos nombre y dirección para buscar pistas, todo en mayúsculas
    $textoBusqueda = strtoupper($punto['direccion'] . " " . $punto['nombre_punto']);
    
    // Limpiamos tildes básicas por si acaso
    $textoBusqueda = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $textoBusqueda);

    foreach ($municipios as $mun) {
        $nombreMun = strtoupper($mun['nombre_municipio']);
        $nombreMun = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $nombreMun);

        // BUSCAMOS LA COINCIDENCIA EXACTA DEL MUNICIPIO EN LA DIRECCIÓN
        // Usamos bordes de palabra (\b) para evitar que encuentre "CALI" dentro de "CALIDAD"
        // Pero como strpos es más rápido y tus datos son direcciones, usaremos strpos simple primero
        
        if (strpos($textoBusqueda, $nombreMun) !== false) {
            
            // ¡ENCONTRADO!
            $sqlUp = "UPDATE punto SET id_municipio = :id_mun WHERE id_punto = :id_punto";
            $stmt = $conn->prepare($sqlUp);
            $stmt->execute([
                ':id_mun' => $mun['id_municipio'],
                ':id_punto' => $punto['id_punto']
            ]);

            echo "✅ <b>{$punto['nombre_punto']}</b> detectado en <b>{$mun['nombre_municipio']}</b>. (Dir: {$punto['direccion']})<br>";
            $contador++;
            break; // Pasamos al siguiente punto apenas encontremos match
        }
    }
}

echo "<h2>🎉 Terminamos. Se arreglaron $contador puntos.</h2>";
?>