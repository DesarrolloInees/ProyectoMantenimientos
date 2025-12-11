<?php
// vistas/usuario/cambiarPasswordVista.php
if (!defined(constant_name: 'ENTRADA_PRINCIPAL')) die("Acceso denegado.");

$data = $datos_plantilla ?? [];
$username = $data['username'] ?? 'Usuario';
$baseURL = $data['baseURL'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Contraseña - INEES</title>
    <script src="js/tailwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-lg mx-auto">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">

            <div class="mb-6 border-b pb-4 text-center">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Actualizar Contraseña</h1>

                <?php if (isset($_GET['motivo']) && $_GET['motivo'] == 'expirada'): ?>
                    <p class="text-gray-500 mt-1">Hola <span class="font-semibold"><?php echo htmlspecialchars(string: $username); ?></span>. Tu contraseña ha expirado.</p>
                <?php else: ?>
                    <p class="text-gray-500 mt-1">Hola <span class="font-semibold"><?php echo htmlspecialchars(string: $username); ?></span>. Por seguridad, establece tu nueva contraseña.</p>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                    <p class="font-bold">¡Atención!</p>
                    <p>
                        <?php
                        switch ($_GET['error']) {
                            case 'no_coinciden':
                                echo 'Las contraseñas no coinciden. Inténtalo de nuevo.';
                                break;
                            case 'no_segura':
                                echo 'La contraseña no cumple con los requisitos de seguridad.';
                                break;
                            case 'db_error':
                                echo 'Hubo un error al guardar. Por favor, contacta al administrador.';
                                break;
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form action="<?php echo $baseURL; ?>procesarCambioPassword" method="POST" class="space-y-4">
                <div>
                    <label for="nueva_password" class="block text-sm font-medium text-gray-600">Nueva Contraseña</label>
                    <div class="relative mt-1">
                        <input type="password" id="nueva_password" name="nueva_password" required placeholder="••••••••"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                        <i id="toggleNuevaPassword" class="fa-solid fa-eye absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer"></i>
                    </div>
                </div>

                <ul id="password-requisitos" class="text-sm space-y-1 text-gray-500">
                    <li id="req-largo"><i class="fa-solid fa-times text-red-500 mr-2"></i>Al menos 8 caracteres</li>
                    <li id="req-minuscula"><i class="fa-solid fa-times text-red-500 mr-2"></i>Una letra minúscula</li>
                    <li id="req-mayuscula"><i class="fa-solid fa-times text-red-500 mr-2"></i>Una letra mayúscula</li>
                    <li id="req-numero"><i class="fa-solid fa-times text-red-500 mr-2"></i>Un número</li>
                    <li id="req-simbolo"><i class="fa-solid fa-times text-red-500 mr-2"></i>Un símbolo (ej: @, $, !)</li>
                </ul>

                <div>
                    <label for="confirmar_password" class="block text-sm font-medium text-gray-600">Confirmar Nueva Contraseña</label>
                    <div class="relative mt-1">
                        <input type="password" id="confirmar_password" name="confirmar_password" required placeholder="••••••••"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                        <i id="toggleConfirmarPassword" class="fa-solid fa-eye absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer"></i>
                    </div>
                    <p id="match-message" class="text-xs mt-1"></p>
                </div>

                <div class="pt-4 border-t flex justify-end">
                    <button type="submit" id="submit-button" disabled
                        class="w-full sm:w-auto px-6 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 disabled:bg-gray-400 disabled:cursor-not-allowed disabled:transform-none">
                        Actualizar y Continuar
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="<?php echo $baseURL; ?>js/login/cambiarPasswordValidacion.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Función para configurar un toggle (copiada de tu login.js)
            function setupToggle(inputId, toggleId) {
                const passwordInput = document.getElementById(inputId);
                const toggleIcon = document.getElementById(toggleId);

                if (toggleIcon && passwordInput) {
                    toggleIcon.addEventListener('click', () => {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);
                        toggleIcon.classList.toggle('fa-eye');
                        toggleIcon.classList.toggle('fa-eye-slash');
                    });
                }
            }

            // Configuramos los dos toggles de esta página
            setupToggle('nueva_password', 'toggleNuevaPassword');
            setupToggle('confirmar_password', 'toggleConfirmarPassword');
        });
    </script>
</body>
</body>

</html>