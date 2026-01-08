<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - INEES</title>
    <script src="<?php echo BASE_URL; ?>js/tailwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Recuperar Contraseña</h2>
        <p class="text-center text-gray-600 mb-4">Ingresa tu email registrado.</p>

        <form action="<?php echo BASE_URL; ?>solicitarCodigo" method="POST">

            <input type="hidden" name="accion" value="enviarCodigo">

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <button type="submit"
                class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-colors">
                Enviar Código
            </button>

            <a href="<?php echo BASE_URL; ?>login" class="block text-center mt-4 text-sm text-indigo-600 hover:underline">Volver a inicio de sesión</a>
        </form>
    </div>
</body>

</html>