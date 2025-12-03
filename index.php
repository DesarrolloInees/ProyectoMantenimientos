<?php
// 1. Configuración Inicial
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
define('ENTRADA_PRINCIPAL', true);

require_once "app/config/Database.php";

// 2. Capturar Controller (Página)
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'inicio';

// CamelCase -> Carpeta
$partesCamelCase = preg_split('/(?=[A-Z])/', $pagina);
$modulo = strtolower($partesCamelCase[0]); 

// Nombres de Archivos
$nombreControlador = $pagina . 'Controlador';
$nombreModelo      = $pagina . 'Modelo';

// Rutas
$rutaControlador = "app/controllers/" . $modulo . "/" . $nombreControlador . ".php";
$rutaModelo      = "app/models/" . $modulo . "/" . $nombreModelo . ".php";

// 3. Cargar Archivos
if (file_exists($rutaControlador)) {
    
    // Cargar Modelo
    if (file_exists($rutaModelo)) {
        require_once $rutaModelo;
    }

    require_once $rutaControlador;

    // Instanciar
    if (class_exists($nombreControlador)) {
        $objetoControlador = new $nombreControlador();
        
        // =======================================================
        // 🔴 AQUÍ ESTÁ LA SOLUCIÓN A TU ERROR 🔴
        // =======================================================
        
        // 1. Definimos la acción por defecto como 'cargarVista' (NO 'index')
        $accion = 'cargarVista'; 

        // 2. Si viene 'accion' por POST (AJAX), la usamos
        if (isset($_POST['accion'])) {
            $accion = $_POST['accion'];
        } 
        // 3. Si viene 'accion' por GET (URL), la usamos
        elseif (isset($_GET['accion'])) {
            $accion = $_GET['accion'];
        }

        // =======================================================

        // 4. Ejecutar la acción
        if (method_exists($objetoControlador, $accion)) {
            $objetoControlador->{$accion}();
        } else {
            // Manejo de error si la función no existe
            if (isset($_POST['accion'])) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['error' => "El método '$accion' no existe."]);
                exit;
            } else {
                echo "<div style='background-color:#ffebee; color:#c62828; padding:20px; border:1px solid #ef5350; font-family:sans-serif;'>";
                echo "<h3>🛑 Error del Router</h3>";
                echo "<p>El sistema intentó buscar la función <b>$accion()</b> en el controlador <b>$nombreControlador</b>, pero no existe.</p>";
                echo "<p>Verifica que en tu controlador la función se llame igual.</p>";
                echo "</div>";
            }
        }
        
    } else {
        echo "<h1>Error: Clase no encontrada ($nombreControlador)</h1>";
    }

} else {
    echo "<h1>Error 404: Archivo no encontrado ($rutaControlador)</h1>";
}

ob_end_flush();
?>