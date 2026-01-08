<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");
// Aseguramos que $data exista por si acaso
$data = $data ?? [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - INEES</title>
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
    <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Crear Nueva Contraseña</h2>

        <?php if (!empty($data['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">
                    <?php
                    if ($data['error'] == 'codigo_invalido') echo 'El código es incorrecto o ha expirado.';
                    if ($data['error'] == 'no_coinciden') echo 'Las contraseñas no coinciden.';
                    if ($data['error'] == 'no_segura') echo 'La contraseña no cumple los requisitos.';
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>resetPassword" method="POST" class="space-y-4">

            <input type="hidden" name="accion" value="procesarResetPassword">

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($data['email']); ?>" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="codigo" class="block text-sm font-medium text-gray-700">Código de 6 dígitos</label>
                <input type="text" name="codigo" id="codigo" required autocomplete="off"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="nueva_password" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                <div class="relative mt-1">
                    <input type="password" name="nueva_password" id="nueva_password" required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm pr-10">
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
                <label for="confirmar_password" class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                <div class="relative mt-1">
                    <input type="password" name="confirmar_password" id="confirmar_password" required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm pr-10">
                    <i id="toggleConfirmarPassword" class="fa-solid fa-eye absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer"></i>
                </div>
                <p id="match-message" class="text-xs mt-1"></p>
            </div>

            <button type="submit" id="submit-button" disabled
                class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                Restablecer Contraseña
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const newPasswordInput = document.getElementById('nueva_password');
            const confirmPasswordInput = document.getElementById('confirmar_password');
            const submitButton = document.getElementById('submit-button');
            const matchMessage = document.getElementById('match-message');
            const requisitos = {
                largo: document.getElementById('req-largo'),
                minuscula: document.getElementById('req-minuscula'),
                mayuscula: document.getElementById('req-mayuscula'),
                numero: document.getElementById('req-numero'),
                simbolo: document.getElementById('req-simbolo')
            };

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
            setupToggle('nueva_password', 'toggleNuevaPassword');
            setupToggle('confirmar_password', 'toggleConfirmarPassword');

            function validarRequisito(elemento, esValido) {
                if (!elemento) return;
                const icon = elemento.querySelector('i');
                if (!icon) return;

                if (esValido) {
                    elemento.classList.replace('text-gray-500', 'text-green-600');
                    icon.classList.replace('fa-times', 'fa-check');
                    icon.classList.replace('text-red-500', 'text-green-600');
                } else {
                    elemento.classList.replace('text-green-600', 'text-gray-500');
                    icon.classList.replace('fa-check', 'fa-times');
                    icon.classList.replace('text-green-600', 'text-red-500');
                }
            }

            function validarFormulario() {
                const password = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                const esLargo = password.length >= 8;
                const tieneMinuscula = /[a-z]/.test(password);
                const tieneMayuscula = /[A-Z]/.test(password);
                const tieneNumero = /\d/.test(password);
                const tieneSimbolo = /[@$!%*?&.]/.test(password);

                validarRequisito(requisitos.largo, esLargo);
                validarRequisito(requisitos.minuscula, tieneMinuscula);
                validarRequisito(requisitos.mayuscula, tieneMayuscula);
                validarRequisito(requisitos.numero, tieneNumero);
                validarRequisito(requisitos.simbolo, tieneSimbolo);

                let passwordsCoinciden = false;
                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        matchMessage.textContent = 'Las contraseñas coinciden.';
                        matchMessage.classList.remove('text-red-500');
                        matchMessage.classList.add('text-green-600');
                        passwordsCoinciden = true;
                    } else {
                        matchMessage.textContent = 'Las contraseñas no coinciden.';
                        matchMessage.classList.remove('text-green-600');
                        matchMessage.classList.add('text-red-500');
                        passwordsCoinciden = false;
                    }
                } else {
                    matchMessage.textContent = '';
                    passwordsCoinciden = false;
                }

                if (esLargo && tieneMinuscula && tieneMayuscula && tieneNumero && tieneSimbolo && passwordsCoinciden) {
                    submitButton.disabled = false;
                } else {
                    submitButton.disabled = true;
                }
            }

            if (newPasswordInput) newPasswordInput.addEventListener('input', validarFormulario);
            if (confirmPasswordInput) confirmPasswordInput.addEventListener('input', validarFormulario);
        });
    </script>
</body>

</html>