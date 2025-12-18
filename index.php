<?php

/**
 * index.php (v5.0 - MASTER)
 * Fusión: Seguridad estricta del proyecto antiguo + Arquitectura MVC del nuevo.
 */

// --- 1. CONFIGURACIÓN INICIAL ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Bogota');
define('ENTRADA_PRINCIPAL', true);

// Definir URL Base automáticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_path = str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $base_path . '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a Base de Datos
require_once __DIR__ . '/app/config/conexion.php';
$conexionObj = new Conexion();
$db = $conexionObj->getConexion();

// 1. Capturamos la ruta amigable (ej: ordenDetalle/2025-12-10)
$ruta = isset($_GET['ruta']) ? $_GET['ruta'] : 'inicio';

// 2. Separamos por las barras inclinadas "/"
$partes = explode('/', rtrim($ruta, '/'));

// 3. La primera parte es la PAGINA
$pagina = $partes[0];

// 4. La segunda parte (si existe) la convertimos en $_GET para no romper tu código actual
if (isset($partes[1])) {
    // Truco: Simulamos que llegó por ?fecha=...
    // Si tu página es ordenDetalle, asumimos que el segundo parametro es la fecha
    if ($pagina == 'ordenDetalle') {
        $_GET['fecha'] = $partes[1];
    }
    // Aquí podrías agregar más casos si otras páginas usan ID
}

// --- 2. LÓGICA DE ENRUTAMIENTO ---
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'inicio';

// TRUCO MAESTRO: Puente entre URL Amigable y Código Viejo
// Tu .htaccess manda el segundo dato como $_GET['id'] (ej: 2025-12-10)
if (isset($_GET['id'])) {
    // Si estamos en ordenDetalle, convertimos ese 'id' en 'fecha'
    if ($pagina == 'ordenDetalle') {
        $_GET['fecha'] = $_GET['id'];
    }
    // Aquí puedes agregar más casos si otras páginas usan ID
}

// --- 3. GESTIÓN DE SEGURIDAD (EL PORTERO) ---

// A. Definimos qué páginas puede ver TODO EL MUNDO (Sin loguearse)
$paginas_publicas = [
    'login',
    'solicitarCodigo',
    'resetPassword',
    'procesarResetPassword',
    'enviarCodigo',
    'cambiarPassword',
    'procesarCambioPassword',
    'mensajeEnviado',
    'error404'
];

$esta_logueado = isset($_SESSION['usuario_id']);

// B. LOGOUT (Caso especial)
if ($pagina === 'logout') {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'login');
    exit();
}

// C. REGLA DE ORO: Si NO estás logueado y NO es pública -> AL LOGIN
if (!$esta_logueado && !in_array($pagina, $paginas_publicas)) {
    header('Location: ' . BASE_URL . 'login');
    exit();
}

// D. REGLA INVERSA: Si YA estás logueado e intentas ir al login -> AL INICIO
if ($esta_logueado && $pagina === 'login') {
    header('Location: ' . BASE_URL . 'inicio');
    exit();
}

// --- 4. CEREBRO RBAC (BUSCAR RUTA EN BD) ---
$controlador_ruta_archivo = null;

try {
    if ($esta_logueado) {
        $nivel_usuario = $_SESSION['nivel_acceso'];

        // Si es SuperAdmin (1), tiene acceso directo si la ruta existe
        if ($nivel_usuario == 1) {
            $sql = "SELECT controlador_ruta FROM rutas WHERE nombre_ruta = :pagina AND estado = 'activo'";
            $stmt = $db->prepare($sql);
            $stmt->execute([':pagina' => $pagina]);
        } else {
            // Si es mortal, verificamos en la tabla intermedia rol_permisos
            $sql = "SELECT r.controlador_ruta 
                    FROM rol_permisos rp
                    INNER JOIN rutas r ON rp.ruta_id = r.id_ruta
                    WHERE rp.rol_id = :rol_id AND r.nombre_ruta = :pagina AND r.estado = 'activo'";
            $stmt = $db->prepare($sql);
            $stmt->execute([':rol_id' => $nivel_usuario, ':pagina' => $pagina]);
        }
        $controlador_ruta_archivo = $stmt->fetchColumn();
    } elseif (in_array($pagina, $paginas_publicas)) {
        // Rutas públicas: solo consultamos si existe la ruta física en la BD
        $sql = "SELECT controlador_ruta FROM rutas WHERE nombre_ruta = :pagina AND estado = 'activo'";
        $stmt = $db->prepare($sql);
        $stmt->execute([':pagina' => $pagina]);
        $controlador_ruta_archivo = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    die("Error crítico en el Router RBAC: " . $e->getMessage());
}

// --- 5. CARGA Y EJECUCIÓN (MVC POO) ---
// AQUÍ ESTÁ LA CORRECCIÓN CLAVE PARA QUE FUNCIONE TU CONTROLADOR NUEVO

if ($controlador_ruta_archivo && file_exists(__DIR__ . '/' . $controlador_ruta_archivo)) {

    // 1. Incluimos el archivo
    require_once __DIR__ . '/' . $controlador_ruta_archivo;

    // 2. Nombre de la clase
    $nombreClase = $pagina . 'Controlador';

    if (class_exists($nombreClase)) {
        // 3. Instanciamos la clase
        $controlador = new $nombreClase();

        // 4. Determinamos la acción (Método)
        // CAMBIO: Por defecto ahora usamos 'index', que es el estándar
        $accion = 'index';

        if (isset($_POST['accion'])) {
            $accion = $_POST['accion']; // Prioridad si viene por POST (AJAX)
        } elseif (isset($_GET['accion'])) {
            $accion = $_GET['accion']; // Prioridad si viene por GET
        }

        // 5. Ejecutamos con inteligencia
        if (method_exists($controlador, $accion)) {
            // Si el método existe (ej: index, guardar, eliminar), se ejecuta.
            $controlador->{$accion}();
        } elseif ($accion === 'index' && method_exists($controlador, 'cargarVista')) {
            // COMPATIBILIDAD HACIA ATRÁS:
            // Si buscamos 'index' (default) pero no existe, y la clase tiene 'cargarVista'
            // (tu forma antigua de hacerlo), usamos cargarVista.
            $controlador->cargarVista();
        } else {
            // Error si no encuentra ni index ni el método pedido
            echo "Error: Método '$accion' no encontrado en el controlador '$nombreClase'.";
        }
    } else {
        echo "Error: La clase '$nombreClase' no se encontró en el archivo.";
    }
} else {
    // 404
    header("HTTP/1.0 404 Not Found");
    echo "<h1>Error 404</h1><p>Página no encontrada o acceso denegado.</p>";
}
