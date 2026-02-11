<?php
// app/views/login/cambiarPasswordVista.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");
$data = $data ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Contraseña Obligatorio - INEES</title>
    <script src="<?php echo BASE_URL; ?>js/tailwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md">

        <!-- Encabezado con ícono de advertencia -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full mb-4">
                <i class="fa-solid fa-shield-halved text-yellow-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Cambio de Contraseña</h2>
            <p class="text-sm text-gray-500 mt-1">
                Por seguridad, debes crear una nueva contraseña antes de continuar.
            </p>
        </div>

        <!-- Mensaje de error si viene de una redirección con error -->
        <?php if (!empty($data['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <i class="fa-solid fa-circle-exclamation mr-2"></i>
                <span class="sm:inline">
                    <?php
                    $errores = [
                        'no_coinciden' => 'Las contraseñas no coinciden. Inténtalo de nuevo.',
                        'no_segura'    => 'La contraseña no cumple los requisitos de seguridad.',
                        'error_db'     => 'Error al guardar la contraseña. Por favor intenta de nuevo.',
                    ];
                    echo $errores[$data['error']] ?? 'Ocurrió un error desconocido.';
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- Formulario: POST a procesarCambioPassword -->
        <form action="<?php echo BASE_URL; ?>procesarCambioPassword" method="POST" class="space-y-4">

            <!-- Nueva contraseña -->
            <div>
                <label for="nueva_password" class="block text-sm font-medium text-gray-700">
                    Nueva Contraseña
                </label>
                <div class="relative mt-1">
                    <input type="password" name="nueva_password" id="nueva_password" required autocomplete="new-password"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm pr-10 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <i id="toggleNueva" class="fa-solid fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer"></i>
                </div>
            </div>

            <!-- Indicadores de fortaleza -->
            <ul id="password-requisitos" class="text-sm space-y-1 text-gray-500 bg-gray-50 p-3 rounded-lg">
                <li id="req-largo">
                    <i class="fa-solid fa-times text-red-500 mr-2 w-4 inline-block"></i>Al menos 8 caracteres
                </li>
                <li id="req-minuscula">
                    <i class="fa-solid fa-times text-red-500 mr-2 w-4 inline-block"></i>Una letra minúscula
                </li>
                <li id="req-mayuscula">
                    <i class="fa-solid fa-times text-red-500 mr-2 w-4 inline-block"></i>Una letra mayúscula
                </li>
                <li id="req-numero">
                    <i class="fa-solid fa-times text-red-500 mr-2 w-4 inline-block"></i>Un número
                </li>
                <li id="req-simbolo">
                    <i class="fa-solid fa-times text-red-500 mr-2 w-4 inline-block"></i>Un símbolo (@, $, !, %, *, ? o &amp;)
                </li>
            </ul>

            <!-- Confirmar contraseña -->
            <div>
                <label for="confirmar_password" class="block text-sm font-medium text-gray-700">
                    Confirmar Contraseña
                </label>
                <div class="relative mt-1">
                    <input type="password" name="confirmar_password" id="confirmar_password" required autocomplete="new-password"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm pr-10 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <i id="toggleConfirmar" class="fa-solid fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer"></i>
                </div>
                <p id="match-message" class="text-xs mt-1 h-4"></p>
            </div>

            <!-- Botón (deshabilitado hasta que el formulario sea válido) -->
            <button type="submit" id="submit-button" disabled
                class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-lg shadow-md
                       hover:bg-indigo-700 transition-colors
                       disabled:bg-gray-400 disabled:cursor-not-allowed">
                <i class="fa-solid fa-floppy-disk mr-2"></i>Guardar Nueva Contraseña
            </button>

        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const nueva     = document.getElementById('nueva_password');
        const confirmar = document.getElementById('confirmar_password');
        const btn       = document.getElementById('submit-button');
        const msg       = document.getElementById('match-message');

        // ── Mostrar / Ocultar contraseña ──────────────────────────────────
        function setupToggle(inputId, toggleId) {
            const input  = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            toggle.addEventListener('click', () => {
                input.type = input.type === 'password' ? 'text' : 'password';
                toggle.classList.toggle('fa-eye');
                toggle.classList.toggle('fa-eye-slash');
            });
        }
        setupToggle('nueva_password',    'toggleNueva');
        setupToggle('confirmar_password','toggleConfirmar');

        // ── Validar un requisito y actualizar su ícono ────────────────────
        function setRequisito(elId, cumple) {
            const li   = document.getElementById(elId);
            const icon = li.querySelector('i');
            if (cumple) {
                li.classList.remove('text-gray-500');
                li.classList.add('text-green-600');
                icon.classList.remove('fa-times', 'text-red-500');
                icon.classList.add('fa-check', 'text-green-600');
            } else {
                li.classList.remove('text-green-600');
                li.classList.add('text-gray-500');
                icon.classList.remove('fa-check', 'text-green-600');
                icon.classList.add('fa-times', 'text-red-500');
            }
        }

        // ── Validación completa del formulario ────────────────────────────
        function validar() {
            const p = nueva.value;
            const c = confirmar.value;

            const largo     = p.length >= 8;
            const minuscula = /[a-z]/.test(p);
            const mayuscula = /[A-Z]/.test(p);
            const numero    = /\d/.test(p);
            const simbolo   = /[@$!%*?&.]/.test(p);

            setRequisito('req-largo',    largo);
            setRequisito('req-minuscula',minuscula);
            setRequisito('req-mayuscula',mayuscula);
            setRequisito('req-numero',   numero);
            setRequisito('req-simbolo',  simbolo);

            let coinciden = false;
            if (c.length > 0) {
                coinciden = (p === c);
                msg.textContent  = coinciden ? '✓ Las contraseñas coinciden.' : '✗ Las contraseñas no coinciden.';
                msg.className    = coinciden
                    ? 'text-xs mt-1 h-4 text-green-600'
                    : 'text-xs mt-1 h-4 text-red-500';
            } else {
                msg.textContent = '';
            }

            btn.disabled = !(largo && minuscula && mayuscula && numero && simbolo && coinciden);
        }

        nueva.addEventListener('input',     validar);
        confirmar.addEventListener('input', validar);
    });
    </script>

</body>
</html>