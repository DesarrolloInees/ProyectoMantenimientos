// ==========================================
// SISTEMA DE PAGINACIÓN
// ==========================================

/**
 * Inicializar paginación
 */
function iniciarPaginacion() {
    const filas = document.querySelectorAll('#tablaEdicion tbody tr');

    if (filas.length <= 1 && filas[0] && filas[0].innerText.includes("No hay datos")) {
        return;
    }

    window.DetalleConfig.totalFilas = filas.length;

    // ✅ Actualizar AMBOS contadores (top y bottom)
    ['totalRegistros', 'totalRegistrosTop'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerText = window.DetalleConfig.totalFilas;
    });

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

    let finM = fin > window.DetalleConfig.totalFilas
        ? window.DetalleConfig.totalFilas
        : fin;

    const textoInfo = `${inicio + 1} - ${finM} de ${window.DetalleConfig.totalFilas}`;
    const textoPagina = `${pag} / ${window.DetalleConfig.totalPaginas}`;

    // ✅ Actualizar AMBOS indicadores (top y bottom)
    ['indicadorPagina', 'indicadorPaginaTop'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerText = textoPagina;
    });

    ['infoPagina', 'infoPaginaTop'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerText = textoInfo;
    });
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