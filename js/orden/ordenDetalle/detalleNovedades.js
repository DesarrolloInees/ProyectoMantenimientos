// ==========================================
// GESTIÓN DE NOVEDADES
// ==========================================

/**
 * Toggle estado de novedad
 */
function toggleNovedad(idFila) {
    let input = document.getElementById(`input_novedad_${idFila}`);
    let btn = document.getElementById(`btn_novedad_${idFila}`);

    let estadoActual = parseInt(input.value);

    if (estadoActual === 0) {
        // ACTIVAR NOVEDAD
        input.value = 1;
        btn.classList.remove('bg-gray-100', 'border-gray-300', 'text-gray-300', 'hover:bg-gray-200');
        btn.classList.add('bg-red-500', 'border-red-700', 'text-white', 'animate-pulse');
        setTimeout(() => btn.classList.remove('animate-pulse'), 500);
    } else {
        // DESACTIVAR NOVEDAD
        input.value = 0;
        btn.classList.remove('bg-red-500', 'border-red-700', 'text-white');
        btn.classList.add('bg-gray-100', 'border-gray-300', 'text-gray-300', 'hover:bg-gray-200');
    }
}

// Exportar
window.DetalleNovedades = {
    toggleNovedad
};

// Retrocompatibilidad
window.toggleNovedad = toggleNovedad;