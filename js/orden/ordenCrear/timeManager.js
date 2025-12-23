// ==========================================
// GESTOR DE TIEMPOS Y FECHAS (timeManager.js)
// ==========================================

/**
 * Verificar si una fecha es domingo o festivo
 */
function esDiaEspecial(fechaString) {
    if (!fechaString) return false;

    // Convertir string "YYYY-MM-DD" a objeto Date
    // Agregamos 'T00:00:00' para evitar problemas de zona horaria (UTC vs Local)
    const fecha = new Date(fechaString + 'T00:00:00');

    // 1. Verificar si es Domingo (0 = Domingo)
    if (fecha.getDay() === 0) {
        console.log(`📅 ${fechaString} es Domingo.`);
        return true;
    }

    // 2. Verificar si es Festivo
    // CORRECCIÓN: Apuntamos a window.AppConfig.datos.festivos
    // Usamos el operador ?. y || [] para seguridad por si los datos no cargaron
    const listaFestivos = window.AppConfig.datos?.festivos || [];

    if (listaFestivos.includes(fechaString)) {
        console.log(`🎉 ${fechaString} es Festivo.`);
        return true;
    }

    return false;
}

/**
 * Calcular duración entre hora entrada y salida
 */
function calcTiempo(id) {
    const horaIn = document.getElementById(`in_${id}`)?.value;
    const horaOut = document.getElementById(`out_${id}`)?.value;
    const inputDuracion = document.getElementById(`duracion_${id}`);

    if (!inputDuracion) return;

    if (horaIn && horaOut) {
        // Usamos una fecha arbitraria para calcular la diferencia
        const d1 = new Date(`2000-01-01T${horaIn}:00`);
        const d2 = new Date(`2000-01-01T${horaOut}:00`);

        let diffMs = d2 - d1;

        // Ajuste por cruce de medianoche (Ej: entra 23:00, sale 01:00)
        if (diffMs < 0) {
            diffMs += 24 * 60 * 60 * 1000;
        }

        const diffMins = Math.floor(diffMs / 60000);
        const horas = Math.floor(diffMins / 60);
        const minutos = diffMins % 60;

        const hStr = horas.toString().padStart(2, '0');
        const mStr = minutos.toString().padStart(2, '0');

        inputDuracion.value = `${hStr}:${mStr}`;
        inputDuracion.classList.add('text-green-600', 'font-bold');
    } else {
        inputDuracion.value = "";
        inputDuracion.classList.remove('text-green-600', 'font-bold');
    }
}

/**
 * Activar máscara y validación para inputs de hora
 */
function activarInputHora(selector, idFila) {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.mask === 'undefined') {
        console.warn("jQuery Mask Plugin no cargado. Saltando máscara.");
        return;
    }

    $(selector).mask('00:00');

    $(selector).on('blur', function () {
        const valor = $(this).val();
        if (valor === '') return;

        const partes = valor.split(':');
        const horas = parseInt(partes[0]);
        const minutos = parseInt(partes[1]);

        let esValido = true;

        if (valor.length !== 5 || isNaN(horas) || isNaN(minutos)) esValido = false;
        if (horas < 0 || horas > 23) esValido = false;
        if (minutos < 0 || minutos > 59) esValido = false;

        if (!esValido) {
            // Usar UIUtils si existe, sino alert normal
            if (window.UIUtils && window.UIUtils.mostrarNotificacion) {
                window.UIUtils.mostrarNotificacion('Hora inválida (formato 24h)', 'error');
            } else {
                alert('⚠️ Hora inválida. Use formato 24 horas (00:00 a 23:59).');
            }
            $(this).val('');
            $(this).addClass('border-red-500 bg-red-50');
        } else {
            $(this).removeClass('border-red-500 bg-red-50');
            calcTiempo(idFila);
        }
    });
}

/**
 * Manejar cambio de fecha global
 */
function manejarCambioFecha() {
    const inputFechaGlobal = document.querySelector('input[name="fecha_reporte"]');

    if (!inputFechaGlobal) return;

    inputFechaGlobal.addEventListener('change', function () {
        const fechaSeleccionada = this.value;
        const esFestivo = esDiaEspecial(fechaSeleccionada); //

        console.log(`📅 Fecha: ${fechaSeleccionada} | Festivo: ${esFestivo}`);

        if (esFestivo) {
            // Notificar al usuario
            if (window.UIUtils && window.UIUtils.mostrarNotificacion) {
                window.UIUtils.mostrarNotificacion("Fecha Domingo/Festivo. Modalidad cambiada a INTERURBANO.", 'warning');
            } else {
                alert("📅 Fecha Domingo/Festivo.\n\nSe cambiará modalidad a INTERURBANO automáticamente.");
            }
        }

        // Actualizar todas las filas activas
        const filas = document.querySelectorAll('#contenedorFilas tr');

        filas.forEach(tr => {
            const idFila = tr.id.replace('fila_', '');
            const selectModalidad = document.getElementById(`select_modalidad_${idFila}`);

            if (selectModalidad) {
                // Lógica de negocio: Si es festivo, forzar Interurbano (ID 2), si no, dejar como estaba o Resetear
                // Nota: Esto depende de tu regla de negocio exacta.
                if (esFestivo) {
                    selectModalidad.value = "2"; // Asumiendo 2 = Interurbano
                    selectModalidad.classList.add('bg-yellow-100', 'border-yellow-500');
                    // Disparar evento change para recalcular precios si es necesario
                    if (typeof $ !== 'undefined') $(selectModalidad).trigger('change');
                } else {
                    selectModalidad.classList.remove('bg-yellow-100', 'border-yellow-500');
                }
            }

            if (window.AjaxUtils) {
                window.AjaxUtils.calcularPrecio(idFila);
            }
        });
    });
}

/**
 * Inicializar gestión de tiempos
 */
function inicializar() {
    manejarCambioFecha();
}

// Exportar
window.TimeManager = {
    esDiaEspecial,
    calcTiempo,
    activarInputHora,
    inicializar
};

// Retrocompatibilidad global
window.calcTiempo = calcTiempo;