// ==========================================
// VALIDADOR DE REMISIONES
// ==========================================

/**
 * Cache de remisiones usadas en la sesión actual
 */
let remisionesUsadasLocal = new Set();

/**
 * Cache de remisiones disponibles por técnico
 */
let remisionesDisponiblesPorTecnico = {};

/**
 * Validar si una remisión está duplicada en el formulario actual
 */
function validarRemisionDuplicada(numeroRemision, idFilaActual) {
    if (!numeroRemision) return { valida: true };

    // Verificar en otras filas del formulario
    const todasLasFilas = document.querySelectorAll('[id^="fila_"]');
    let duplicadaEn = null;

    todasLasFilas.forEach(fila => {
        const idFila = fila.id.replace('fila_', '');

        // Saltar la fila actual
        if (idFila === idFilaActual.toString()) return;

        const selectRemision = fila.querySelector(`select[id^="select_remision_"]`);
        if (selectRemision && selectRemision.value === numeroRemision) {
            duplicadaEn = idFila;
        }
    });

    if (duplicadaEn) {
        return {
            valida: false,
            motivo: 'duplicada_local',
            filaConflicto: duplicadaEn
        };
    }

    return { valida: true };
}

/**
 * Validar remisión contra base de datos (AJAX)
 */
async function validarRemisionEnBD(numeroRemision, idTecnico) {
    if (!numeroRemision) return { valida: true };

    try {
        const formData = new FormData();
        formData.append('accion', 'ajaxValidarRemision');
        formData.append('numero_remision', numeroRemision);
        formData.append('id_tecnico', idTecnico);

        const response = await fetch('index.php?pagina=ordenCrear', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        return {
            valida: data.disponible === true,
            motivo: data.disponible ? 'disponible' : 'usada_bd',
            detalles: data
        };

    } catch (error) {
        console.error('Error validando remisión:', error);
        return {
            valida: true, // En caso de error, permitir continuar
            motivo: 'error_validacion',
            error: error.message
        };
    }
}

/**
 * Validación completa de remisión (local + BD)
 */
async function validarRemisionCompleta(numeroRemision, idFila, idTecnico) {
    // 1. Validar duplicados locales (formulario)
    const validacionLocal = validarRemisionDuplicada(numeroRemision, idFila);

    if (!validacionLocal.valida) {
        window.CrearNotificaciones.notificarRemisionDuplicada(numeroRemision, idFila);
        return false;
    }

    // 2. Validar en BD
    const validacionBD = await validarRemisionEnBD(numeroRemision, idTecnico);

    if (!validacionBD.valida) {
        const mensaje = `⚠️ Remisión YA USADA: ${numeroRemision}<br>📋 Esta remisión fue registrada anteriormente en la BD`;
        window.CrearNotificaciones.mostrarNotificacion(mensaje, 'error', 5000);

        // Marcar visualmente
        const selectRemision = document.getElementById(`select_remision_${idFila}`);
        if (selectRemision) {
            selectRemision.classList.add('border-red-500', 'bg-red-50');
            setTimeout(() => {
                selectRemision.classList.remove('border-red-500', 'bg-red-50');
            }, 3000);
        }

        return false;
    }

    // 3. Todo OK
    window.CrearNotificaciones.notificarRemisionValida(numeroRemision, idFila);
    remisionesUsadasLocal.add(numeroRemision);

    return true;
}

/**
 * Configurar listener de cambio de remisión
 */
function configurarValidacionRemisiones() {
    // Usar event delegation para capturar cambios en selects de remisión
    document.addEventListener('change', function (e) {
        const target = e.target;

        // Verificar si es un select de remisión
        if (target.id && target.id.startsWith('select_remision_')) {
            const idFila = target.id.replace('select_remision_', '');
            const numeroRemision = target.value;

            if (numeroRemision) {
                // Obtener el técnico de esa fila
                const selectTecnico = document.getElementById(`select_tecnico_${idFila}`);
                const idTecnico = selectTecnico ? selectTecnico.value : null;

                if (idTecnico) {
                    validarRemisionCompleta(numeroRemision, idFila, idTecnico);
                }
            }
        }
    });
}

/**
 * Validar todas las remisiones antes de enviar formulario
 */
async function validarTodasRemisionesAnteDeEnviar() {
    const filas = document.querySelectorAll('[id^="fila_"]');
    const remisionesEncontradas = new Map();
    const errores = [];

    // 1. Detectar duplicados locales
    for (const fila of filas) {
        const idFila = fila.id.replace('fila_', '');
        const selectRemision = fila.querySelector(`select[id^="select_remision_"]`);

        if (selectRemision && selectRemision.value) {
            const numeroRemision = selectRemision.value;

            if (remisionesEncontradas.has(numeroRemision)) {
                errores.push({
                    tipo: 'duplicado',
                    remision: numeroRemision,
                    fila: idFila,
                    filaOriginal: remisionesEncontradas.get(numeroRemision)
                });
            } else {
                remisionesEncontradas.set(numeroRemision, idFila);
            }
        }
    }

    // 2. Validar en BD (solo las únicas)
    for (const [numeroRemision, idFila] of remisionesEncontradas) {
        const selectTecnico = document.getElementById(`select_tecnico_${idFila}`);
        const idTecnico = selectTecnico ? selectTecnico.value : null;

        if (idTecnico) {
            const validacionBD = await validarRemisionEnBD(numeroRemision, idTecnico);

            if (!validacionBD.valida) {
                errores.push({
                    tipo: 'usada_bd',
                    remision: numeroRemision,
                    fila: idFila
                });
            }
        }
    }

    // 3. Reportar errores
    if (errores.length > 0) {
        let mensajeError = '⚠️ ERRORES EN REMISIONES:\n\n';

        errores.forEach(err => {
            if (err.tipo === 'duplicado') {
                mensajeError += `• Fila #${err.fila}: Remisión ${err.remision} duplicada (ya está en Fila #${err.filaOriginal})\n`;
            } else if (err.tipo === 'usada_bd') {
                mensajeError += `• Fila #${err.fila}: Remisión ${err.remision} ya fue usada anteriormente\n`;
            }
        });

        mensajeError += '\n❌ Corrija estos errores antes de guardar.';

        alert(mensajeError);

        // Notificación visual
        window.CrearNotificaciones.notificarError(`${errores.length} remisión(es) con problemas`);

        return false;
    }

    return true;
}

/**
 * Limpiar cache de remisiones (al limpiar borrador o iniciar nuevo reporte)
 */
function limpiarCacheRemisiones() {
    remisionesUsadasLocal.clear();
    remisionesDisponiblesPorTecnico = {};
    console.log('✅ Cache de remisiones limpiado');
}

/**
 * Marcar remisión como usada (después de guardar exitosamente)
 */
function marcarRemisionComoUsada(numeroRemision) {
    remisionesUsadasLocal.add(numeroRemision);
}

// Exportar
window.ValidadorRemisiones = {
    validarRemisionDuplicada,
    validarRemisionEnBD,
    validarRemisionCompleta,
    configurarValidacionRemisiones,
    validarTodasRemisionesAnteDeEnviar,
    limpiarCacheRemisiones,
    marcarRemisionComoUsada
};

console.log('✅ Validador de remisiones cargado');