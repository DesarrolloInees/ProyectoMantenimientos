<?php
// logout.php

// Inicia la sesión.
session_start();

// Destruye todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la cookie de sesión, también es necesario
// borrar la cookie de sesión.
if (ini_get(option: "session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        name: session_name(),
        value: '',
        expires_or_options: time() - 42000,
        path: $params["path"],
        domain: $params["domain"],
        secure: $params["secure"],
        httponly: $params["httponly"]
    );
}

// Finalmente, destruye la sesión.
session_destroy();

// Redirige al usuario a la página de login
header("Location: login");
exit();
