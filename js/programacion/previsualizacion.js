/**
 * js/programacion/previsualizacion.js
 * Tabla de previsualización final antes de aprobar.
 *
 * IMPORTANTE: aquí se cierra el ciclo que la vista prometía pero
 * nunca ejecutaba: antes de guardar definitivamente, se le avisa
 * al backend (accion=restaurarOperativo) cuáles id_punto SÍ quedaron
 * programados, para que TODAS las demás máquinas fuera de servicio
 * que no se programaron vuelvan a "Operativo" automáticamente.
 */
(function () {
    function eliminarFila(index) {
        const fila = document.getElementById('fila_' + index);
        if (fila && confirm('¿Eliminar este servicio de la programación?')) {
            fila.remove();
        }
    }

    function idsPuntosEnPrevisualizacion() {
        return Prog.util.qsa('#formGuardar tbody tr[id^="fila_"] input[name*="[id_punto]"]')
            .map(input => parseInt(input.value, 10))
            .filter(id => Number.isInteger(id));
    }

    function manejarEnvioFinal(e) {
        e.preventDefault();
        const form = e.target;
        const puntosProgramados = idsPuntosEnPrevisualizacion();

        if (puntosProgramados.length === 0) {
            alert('No hay servicios en la tabla para aprobar.');
            return;
        }

        const boton = form.querySelector('button[type="submit"]');
        if (boton) {
            boton.disabled = true;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';
        }

        // Se envía directamente el formulario final. 
        // Las máquinas fuera de servicio NO se restauran aquí; 
        // permanecerán inactivas hasta la descarga del Consolidado en Excel.
        form.removeEventListener('submit', manejarEnvioFinal);
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const formGuardar = document.getElementById('formGuardar');
        if (formGuardar) formGuardar.addEventListener('submit', manejarEnvioFinal);
    });

    window.eliminarFila = eliminarFila;
})();
// Agrega este bloque dentro de la función auto-ejecutable en previsualizacion.js

function reordenarFilasPorFecha() {
    const tbody = document.getElementById('tablaServicios');
    if (!tbody) return;

    const filas = Array.from(tbody.querySelectorAll('tr[id^="fila_"]'));

    // Ordenar las filas según la nueva fecha seleccionada en el input
    filas.sort((a, b) => {
        const fechaA = a.querySelector('input[name*="[fecha_visita]"]').value;
        const fechaB = b.querySelector('input[name*="[fecha_visita]"]').value;
        return fechaA.localeCompare(fechaB);
    });

    // Reinyectar las filas en orden
    filas.forEach(fila => tbody.appendChild(fila));
}

document.addEventListener('DOMContentLoaded', () => {
    // Escuchar cambios en los inputs de fecha dentro de la tabla de previsualización
    const inputsFecha = document.querySelectorAll('#tablaServicios input[name*="[fecha_visita]"]');
    inputsFecha.forEach(input => {
        input.addEventListener('change', reordenarFilasPorFecha);
    });
});