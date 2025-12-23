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

    if (filasValidas === 0) {
        alert('⚠️ No hay servicios completos para guardar.\n\nAsegúrese de llenar al menos: Máquina, Técnico y Tipo de Servicio.');
        return false;
    }

    if (errores.length > 0) {
        const confirmar = confirm(
            `⚠️ ADVERTENCIA\n\nSe encontraron ${errores.length} filas incompletas que NO se guardarán:\n\n${errores.join('\n')}\n\n¿Desea continuar?`
        );
        return confirmar;
    }

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