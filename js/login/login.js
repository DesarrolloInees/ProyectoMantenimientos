/**
 * login.js - Sistema de Login Ultra Moderno (Versión Final Estable)
 * Incluye: Validación, Efectos Visuales, Konami Code y Fix de Redirección
 */

document.addEventListener('DOMContentLoaded', () => {

    // ========================================
    // 1. ELEMENTOS DEL DOM
    // ========================================

    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');
    const btnLogin = document.getElementById('btnLogin'); // IMPORTANTE: El botón
    const jsMessageContainer = document.getElementById('js-message-container');
    const btnText = document.getElementById('btn-text');
    const btnLoading = document.getElementById('btn-loading');
    const serverErrorMessage = document.getElementById('error-message');

    // ========================================
    // 2. LÓGICA DE LOGIN (MODO BOTÓN SEGURO)
    // ========================================

    // Función principal que maneja el login
    const handleLogin = () => {
        const usuario = loginForm.querySelector('input[name="usuario"]').value.trim();
        const password = loginForm.querySelector('input[name="password"]').value.trim();

        clearMessages();

        // Validar campos vacíos
        if (usuario === '' || password === '') {
            showError('Por favor, completa todos los campos', 'warning');
            return;
        }

        // Validar longitud mínima
        if (password.length < 4) {
            showError('La contraseña debe tener al menos 4 caracteres', 'warning');
            return;
        }

        // UI: Mostrar estado de carga
        showLoading();

        // Preparar datos
        const formData = new FormData(loginForm);

        // Enviar petición
        fetch(loginForm.action, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('--> Login exitoso. Redirigiendo a:', data.redirect);
                    window.location.href = data.redirect;
                } else {
                    // Si falla, restauramos el botón y mostramos error
                    hideLoading();
                    showError(data.message || 'Usuario o contraseña incorrectos', 'error');
                }
            })
            .catch(error => {
                console.error('Error crítico:', error);
                hideLoading();
                showError('Error de conexión con el servidor', 'error');
            });
    };

    // EVENTO 1: Click en el botón (Seguro)
    if (btnLogin) {
        btnLogin.addEventListener('click', (e) => {
            e.preventDefault(); // Doble seguridad
            handleLogin();
        });
    } else {
        console.error('ERROR: No se encontró el botón con id="btnLogin"');
    }

    // EVENTO 2: Presionar Enter en el formulario
    if (loginForm) {
        loginForm.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault(); // Evitamos submit nativo
                handleLogin();
            }
        });
    }

    // ========================================
    // 3. TOGGLE PASSWORD VISIBILITY
    // ========================================

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type');
            const newType = type === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', newType);
            togglePassword.classList.toggle('fa-eye');
            togglePassword.classList.toggle('fa-eye-slash');

            // Animación mini
            togglePassword.style.transform = 'translateY(-50%) scale(1.2)';
            setTimeout(() => togglePassword.style.transform = 'translateY(-50%) scale(1)', 200);
        });
    }

    // ========================================
    // 4. EFECTOS VISUALES Y ANIMACIONES
    // ========================================

    // Error del servidor (si viene desde PHP directo)
    if (serverErrorMessage) {
        serverErrorMessage.classList.add('animate-shake');
        setTimeout(() => {
            serverErrorMessage.style.transition = 'opacity 0.5s ease';
            serverErrorMessage.style.opacity = '0';
            setTimeout(() => serverErrorMessage.remove(), 500);
        }, 5000);
    }

    // Efectos Inputs
    const inputs = document.querySelectorAll('.input-login');
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            input.style.borderColor = input.value.length > 0 ? 'rgba(96, 165, 250, 0.5)' : 'rgba(255, 255, 255, 0.2)';
        });
        input.addEventListener('focus', () => {
            input.parentElement.style.transform = 'scale(1.02)';
        });
        input.addEventListener('blur', () => {
            input.parentElement.style.transform = 'scale(1)';
        });
    });

    // Caps Lock Detector
    if (passwordInput) {
        passwordInput.addEventListener('keyup', (e) => {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                showCapsLockWarning();
            } else {
                hideCapsLockWarning();
            }
        });
    }

    // ========================================
    // 5. FUNCIONES AUXILIARES
    // ========================================

    function showLoading() {
        if (btnText && btnLoading) {
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            if (btnLogin) {
                btnLogin.disabled = true;
                btnLogin.style.opacity = '0.7';
                btnLogin.style.cursor = 'not-allowed';
            }
        }
    }

    function hideLoading() {
        if (btnText && btnLoading) {
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
            if (btnLogin) {
                btnLogin.disabled = false;
                btnLogin.style.opacity = '1';
                btnLogin.style.cursor = 'pointer';
            }
        }
    }

    function showError(message, type = 'error') {
        clearMessages();
        const colors = {
            error: 'bg-red-500 bg-opacity-20 border-red-400 text-red-300',
            warning: 'bg-yellow-500 bg-opacity-20 border-yellow-400 text-yellow-300'
        };
        const icons = {
            error: 'fa-circle-exclamation',
            warning: 'fa-triangle-exclamation'
        };

        const errorDiv = document.createElement('div');
        errorDiv.className = `${colors[type]} px-4 py-3 rounded-xl text-sm font-semibold flex items-center justify-center border backdrop-blur-lg animate-shake`;
        errorDiv.innerHTML = `<i class="fa-solid ${icons[type]} mr-2"></i><span>${message}</span>`;

        if (jsMessageContainer) jsMessageContainer.appendChild(errorDiv);
        else alert(message); // Fallback
    }

    function clearMessages() {
        if (jsMessageContainer) jsMessageContainer.innerHTML = '';
    }

    function showCapsLockWarning() {
        if (!document.getElementById('caps-warning') && passwordInput.parentElement) {
            const warning = document.createElement('div');
            warning.id = 'caps-warning';
            warning.className = 'mt-2 text-yellow-300 text-xs flex items-center justify-center';
            warning.innerHTML = '<i class="fa-solid fa-keyboard mr-2"></i>Bloq Mayús activado';
            passwordInput.parentElement.appendChild(warning);
        }
    }

    function hideCapsLockWarning() {
        const warning = document.getElementById('caps-warning');
        if (warning) warning.remove();
    }

    // ========================================
    // 6. EASTER EGG (Konami Code)
    // ========================================
    let konamiCode = [];
    const konamiSequence = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];

    document.addEventListener('keydown', (e) => {
        konamiCode.push(e.key);
        konamiCode = konamiCode.slice(-10);
        if (konamiCode.join(',') === konamiSequence.join(',')) {
            document.body.style.animation = 'rainbow 2s linear infinite';
            const style = document.createElement('style');
            style.innerHTML = `@keyframes rainbow { 0% { filter: hue-rotate(0deg); } 100% { filter: hue-rotate(360deg); } }`;
            document.head.appendChild(style);
            setTimeout(() => { document.body.style.animation = ''; style.remove(); }, 5000);
            console.log('🎮 Konami Code!');
        }
        // Limpiar formulario con ESC
        if (e.key === 'Escape') {
            if (confirm('¿Limpiar formulario?')) {
                loginForm.reset();
                clearMessages();
            }
        }
    });

    console.log('%c✨ I-Nexis Login System v5.0 - Ready', 'color: #10b981; font-weight: bold;');
});