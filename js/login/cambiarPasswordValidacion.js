document.addEventListener('DOMContentLoaded', () => {
    // 1. Obtenemos todos los elementos del DOM
    const newPasswordInput = document.getElementById('nueva_password');
    const confirmPasswordInput = document.getElementById('confirmar_password');
    const submitButton = document.getElementById('submit-button');
    const matchMessage = document.getElementById('match-message');

    // Obtenemos los <li> de la lista de requisitos
    const requisitos = {
        largo: document.getElementById('req-largo'),
        minuscula: document.getElementById('req-minuscula'),
        mayuscula: document.getElementById('req-mayuscula'),
        numero: document.getElementById('req-numero'),
        simbolo: document.getElementById('req-simbolo')
    };

    // ✨ FUNCIÓN MEJORADA: Revisa un requisito y actualiza su estilo (más compacta)
    function validarRequisito(elemento, esValido) {
        const icon = elemento.querySelector('i');
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

    // ✨ FUNCIÓN MAESTRA: Valida todo el formulario y actualiza la UI
    function validarFormulario() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // --- 1. Validar la fortaleza de la nueva contraseña ---
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

        // --- 2. Validar que las contraseñas coincidan ---
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
            matchMessage.textContent = ''; // Limpiar mensaje si no hay nada escrito
            passwordsCoinciden = false; // No pueden coincidir si el campo está vacío
        }

        // --- 3. Decisión final: Habilitar o deshabilitar el botón ---
        if (esLargo && tieneMinuscula && tieneMayuscula && tieneNumero && tieneSimbolo && passwordsCoinciden) {
            submitButton.disabled = false;
        } else {
            submitButton.disabled = true;
        }
    }

    // ✨ ASIGNACIÓN DE EVENTOS SIMPLIFICADA
    // Ambos campos llaman a la misma función maestra para re-evaluar todo.
    newPasswordInput.addEventListener('input', validarFormulario);
    confirmPasswordInput.addEventListener('input', validarFormulario);
});