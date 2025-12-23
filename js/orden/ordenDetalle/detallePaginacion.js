// ==========================================
// SISTEMA DE PAGINACIÓN
// ==========================================

/**
 * Inicializar paginación
 */
function iniciarPaginacion() {
    const filas = document.querySelectorAll('#tablaEdicion tbody tr');
    
    if (filas.length <= 1 && filas[0].innerText.includes("No hay datos")) {
        return;
    }

    window.DetalleConfig.totalFilas = filas.length;
    document.getElementById('totalRegistros').innerText = window.DetalleConfig.totalFilas;
    
    window.DetalleConfig.totalPaginas = Math.ceil(
        window.DetalleConfig.totalFilas / window.DetalleConfig.filasPorPagina
    );
    
    mostrarPagina(window.DetalleConfig.paginaActual);
}

/**
 * Cambiar de página
 */
function cambiarPagina(dir) {
    let nueva = window.DetalleConfig.paginaActual + dir;
    
    if (nueva > 0 && nueva <= window.DetalleConfig.totalPaginas) {
        window.DetalleConfig.paginaActual = nueva;
        mostrarPagina(window.DetalleConfig.paginaActual);
    }
}

/**
 * Mostrar página específica
 */
function mostrarPagina(pag) {
    let filas = document.querySelectorAll('#tablaEdicion tbody tr');
    let inicio = (pag - 1) * window.DetalleConfig.filasPorPagina;
    let fin = inicio + window.DetalleConfig.filasPorPagina;
    
    filas.forEach((tr, i) => {
        tr.style.display = (i >= inicio && i < fin) ? 'table-row' : 'none';
    });
    
    document.getElementById('indicadorPagina').innerText = 
        `${pag} / ${window.DetalleConfig.totalPaginas}`;
    
    let finM = fin > window.DetalleConfig.totalFilas ? 
        window.DetalleConfig.totalFilas : fin;
    
    document.getElementById('infoPagina').innerText = 
        `${inicio + 1} - ${finM} de ${window.DetalleConfig.totalFilas}`;
}

// Exportar
window.DetallePaginacion = {
    iniciarPaginacion,
    cambiarPagina,
    mostrarPagina
};

// Retrocompatibilidad
window.iniciarPaginacion = iniciarPaginacion;
window.cambiarPagina = cambiarPagina;