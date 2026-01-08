<?php
// app/views/login/mensajeEnviadoVista.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisa tu Correo - INEES</title>
    <script src="<?php echo BASE_URL; ?>js/tailwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md text-center">

        <i class="fa-solid fa-envelope-circle-check text-6xl text-green-500 mb-6"></i>

        <h2 class="text-2xl font-bold text-gray-800 mb-4">¡Revisa tu correo!</h2>
        <p class="text-gray-600 mb-6">
            Si tu email está registrado, recibirás un código de 6 dígitos.
            (Recuerda revisar tu carpeta de spam).
        </p>

        <a href="<?php echo BASE_URL; ?>resetPassword"
            class="w-full block py-3 px-4 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-colors">
            Ya tengo el código
        </a>
        <a href="<?php echo BASE_URL; ?>login" class="block text-center mt-4 text-sm text-indigo-600 hover:underline">
            Volver a inicio de sesión
        </a>
    </div>
</body>

</html>