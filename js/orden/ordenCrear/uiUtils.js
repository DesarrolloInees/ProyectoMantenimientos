// ==========================================
// UTILIDADES DE INTERFAZ
// ==========================================

/**
 * Activar Select2 en un elemento
 */
function activarSelect2(selector) {
    $(selector).select2({
        width: '100%',
        language: {
            noResults: function () {
                return "No se encontraron resultados";
            }
        }
    });
}

/**
 * Configurar estilos de Select2
 */
function configurarEstilosSelect2() {
    // Los estilos ya están en el HTML, pero podemos agregar más si se necesita
    console.log('Estilos Select2 configurados');
}

/**
 * Mostrar notificación temporal
 */
function mostrarNotificacion(mensaje, tipo = 'info') {
    const colores = {
        info: 'bg-blue-500',
        success: 'bg-green-500',
        warning: 'bg-yellow-500',
        error: 'bg-red-500'
    };

    const notif = document.createElement('div');
    notif.className = `fixed top-4 right-4 ${colores[tipo]} text-white px-6 py-3 rounded-lg shadow-xl z-50 transform transition-all duration-300`;
    notif.textContent = mensaje;

    document.body.appendChild(notif);

    setTimeout(() => {
        notif.style.opacity = '0';
        notif.style.transform = 'translateY(-20px)';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

/**
 * Validar formulario antes de enviar
 */
function validarFormulario() {
    const filas = document.querySelectorAll('#contenedorFilas tr');

    if (filas.length === 0) {
        alert('⚠️ Debe agregar al menos un servicio antes de guardar.');
        return false;
    }

    let filasValidas = 0;
    let errores = [];

    filas.forEach((tr, index) => {
        const idFila = tr.id.replace('fila_', '');

        const idMaquina = $(`#select_maquina_${idFila}`).val();
        const idTecnico = $(`#select_tecnico_${idFila}`).val();
        const tipoServicio = $(`#select_servicio_${idFila}`).val();

        if (idMaquina && idTecnico && tipoServicio) {
            filasValidas++;
        } else if (idMaquina || idTecnico || tipoServicio) {
            errores.push(`Fila ${index + 1}: Datos incompletos`);
        }
    });





    return true;
}

/**
 * Actualizar contador de servicios
 */
function actualizarContador() {
    const total = document.querySelectorAll('#contenedorFilas tr').length;
    const display = document.getElementById('contadorFilasDisplay');
    if (display) {
        display.textContent = total;
    }
}

/**
 * Scroll suave a un elemento
 */
function scrollSuave(selector) {
    const elemento = document.querySelector(selector);
    if (elemento) {
        elemento.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
}

/**
 * Resaltar campo con error
 */
function resaltarError(selector, mensaje = null) {
    const elemento = $(selector);
    elemento.addClass('border-red-500 bg-red-50');

    if (mensaje) {
        mostrarNotificacion(mensaje, 'error');
    }

    setTimeout(() => {
        elemento.removeClass('border-red-500 bg-red-50');
    }, 3000);
}

/**
 * Formatear número como moneda colombiana
 */
function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(valor);
}

/**
 * Limpiar formato de moneda para envío
 */
function limpiarMoneda(texto) {
    return texto.replace(/[$.,\s]/g, '');
}

/**
 * Confirmar acción destructiva
 */
function confirmarAccion(mensaje) {
    return confirm(mensaje);
}

/**
 * Gestiona la navegación tipo Excel con flechas de teclado
 */
function manejarNavegacionTeclado(e) {
    const $actual = $(e.target);
    const $celdaActual = $actual.closest('td');
    const $filaActual = $actual.closest('tr');
    const colIndex = $celdaActual.index();

    // Flechas horizontales (Izquierda / Derecha)
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
        // Solo saltar si es un select o si el cursor está al inicio/final del input
        const esSelect = $actual.is('select') || $actual.hasClass('select2-selection--single');
        const alFinal = e.target.selectionEnd === $actual.val()?.length;
        const alInicio = e.target.selectionStart === 0;

        if (e.key === 'ArrowRight' && (esSelect || alFinal)) {
            const $sig = $celdaActual.nextAll().find('input, select, .select2-selection').first();
            if ($sig.length) { e.preventDefault(); $sig.focus(); }
        } else if (e.key === 'ArrowLeft' && (esSelect || alInicio)) {
            const $ant = $celdaActual.prevAll().find('input, select, .select2-selection').first();
            if ($ant.length) { e.preventDefault(); $ant.focus(); }
        }
    }

    // Flechas verticales (Arriba / Abajo)
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        // IMPORTANTE: Si es un Select2, solo navegar si el menú está CERRADO
        if ($actual.hasClass('select2-selection--single')) {
            const $selectOriginal = $celdaActual.find('select');
            if ($selectOriginal.data('select2').isOpen()) return; 
        }

        e.preventDefault();
        const $targetFila = (e.key === 'ArrowDown') ? $filaActual.next() : $filaActual.prev();
        const $inputDestino = $targetFila.find('td').eq(colIndex).find('input, select, .select2-selection').first();
        
        if ($inputDestino.length) {
            $inputDestino.focus();
        }
    }
}

// Exportar
window.UIUtils = {
    activarSelect2,
    configurarEstilosSelect2,
    mostrarNotificacion,
    validarFormulario,
    actualizarContador,
    scrollSuave,
    resaltarError,
    formatearMoneda,
    limpiarMoneda,
    confirmarAccion
};

// Retrocompatibilidad
window.activarSelect2 = activarSelect2;