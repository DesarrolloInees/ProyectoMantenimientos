// ==========================================
// UTILIDADES DE FECHAS Y FESTIVOS (detalleFecha.js)
// ==========================================

/**
 * Verificar si una fecha es festivo o domingo
 */
function esDiaEspecial(fechaString) {
    if (!fechaString) return false;

    // 1. Verificar si está en la lista de festivos de la BD
    const festivos = window.DetalleConfig?.FESTIVOS_DB || [];
    if (festivos.includes(fechaString)) {
        console.log(`🎉 ${fechaString} es FESTIVO en BD.`);
        return true;
    }

    // 2. Verificar si es Domingo
    const fecha = new Date(fechaString + 'T12:00:00');
    if (fecha.getDay() === 0) {
        console.log(`📅 ${fechaString} es DOMINGO.`);
        return true;
    }

    return false;
}

/**
 * Convertir hora (HH:MM) a minutos
 */
function horaAMinutos(hora) {
    if (!hora) return null;
    let partes = hora.split(':');
    if (partes.length < 2) return null;
    return (parseInt(partes[0]) * 60) + parseInt(partes[1]);
}

/**
 * Calcular duración entre dos horas (Lógica matemática pura)
 */
function calcularDuracion(entrada, salida) {
    if (!entrada || !salida) return "";
    
    let mE = horaAMinutos(entrada);
    let mS = horaAMinutos(salida);
    
    let diff = mS - mE;
    if (diff < 0) diff += 1440; // Ajuste por cruce de medianoche
    
    let h = Math.floor(diff / 60);
    let m = (diff % 60).toString().padStart(2, '0');
    
    return `${h}:${m}`;
}

/**
 * 🔥 NUEVO: Calcular duración en el DOM e inyectar clases (Traído de timeManager)
 */
function calcTiempo(id) {
    // Busca los inputs por ID. Nota: Asegúrate de que los IDs en tu HTML de Detalle coincidan (in_, out_, duracion_)
    const inputIn = document.getElementById(`in_${id}`);
    const inputOut = document.getElementById(`out_${id}`);
    const inputDuracion = document.getElementById(`duracion_${id}`);

    if (!inputIn || !inputOut || !inputDuracion) return;

    const horaIn = inputIn.value;
    const horaOut = inputOut.value;

    if (horaIn && horaOut) {
        // Reutilizamos la función matemática que ya tenías en detalle
        inputDuracion.value = calcularDuracion(horaIn, horaOut);
        inputDuracion.classList.add('text-green-600', 'font-bold');
    } else {
        inputDuracion.value = "";
        inputDuracion.classList.remove('text-green-600', 'font-bold');
    }
}

/**
 * 🔥 NUEVO: Activar máscara y validación para inputs de hora (Traído de timeManager)
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
            if (window.UIUtils && window.UIUtils.mostrarNotificacion) {
                window.UIUtils.mostrarNotificacion('Hora inválida (formato 24h)', 'error');
            } else {
                alert('⚠️ Hora inválida. Use formato 24 horas (00:00 a 23:59).');
            }
            $(this).val('');
            $(this).addClass('border-red-500 bg-red-50');
        } else {
            $(this).removeClass('border-red-500 bg-red-50');
            calcTiempo(idFila); // Recalcula la duración automáticamente
        }
    });
}

/**
 * Configurar detector de cambio de fecha (Actualizado con lógica de timeManager)
 */
function configurarDetectorFechas() {
    $('#tablaEdicion').on('change', 'input[type="date"]', function() {
        let tr = $(this).closest('tr');
        let idFila = tr.attr('id').replace('fila_', '');
        let fechaNueva = $(this).val();

        console.log(`📅 Fecha cambiada en fila ${idFila} a: ${fechaNueva}`);

        let selModalidad = document.getElementById(`sel_modalidad_${idFila}`);
        if (!selModalidad) return;

        // Verificar si es festivo
        if (esDiaEspecial(fechaNueva)) {
            if (selModalidad.value !== "2") {
                selModalidad.value = "2"; // Cambiar a Interurbano
                selModalidad.dataset.cambioAutomatico = "true";

                // Efecto visual
                selModalidad.classList.add('bg-yellow-100', 'border-yellow-500', 'text-yellow-800', 'font-bold');
                
                setTimeout(() => {
                    selModalidad.classList.remove('bg-yellow-100', 'border-yellow-500', 'text-yellow-800', 'font-bold');
                }, 2000);

                // 🔔 NOTIFICACIÓN
                const esDomingo = new Date(fechaNueva + 'T12:00:00').getDay() === 0;
                if(window.DetalleNotificaciones) {
                    window.DetalleNotificaciones.notificarFestivo(fechaNueva, esDomingo);
                }

                console.log("-> 🚀 DETECTADO FESTIVO! Modalidad cambiada a INTERURBANO.");
            }
        } else {
            // 🔥 NUEVO: Si dejó de ser festivo, devolver a URBANO (Traído de timeManager)
            if (selModalidad.value === "2") {
                selModalidad.value = "1"; // Vuelve a Urbano
                delete selModalidad.dataset.cambioAutomatico;
                console.log("-> 🔄 DÍA NORMAL! Modalidad revertida a URBANO.");
            }
            selModalidad.classList.remove('bg-yellow-100', 'border-yellow-500', 'text-yellow-800', 'font-bold');
        }

        // 🔥 NUEVO: Forzar recálculo del select para que AJAX lo detecte
        $(selModalidad).trigger('change');

        // Recalcular tarifa explícitamente en el módulo Detalle
        if (window.DetalleAjax && window.DetalleAjax.actualizarTarifa) {
            window.DetalleAjax.actualizarTarifa(idFila);
        }
    });
}

/**
 * 🔥 NUEVO: Manejar cambio de fecha global (Por si en Detalle también tienen un input general)
 */
function manejarCambioFechaGlobal() {
    const inputFechaGlobal = document.querySelector('input[name="fecha_reporte"]');
    if (!inputFechaGlobal) return;

    inputFechaGlobal.addEventListener('change', function () {
        const fechaSeleccionada = this.value;
        const esFestivo = esDiaEspecial(fechaSeleccionada);

        console.log(`📅 Fecha Global: ${fechaSeleccionada} | Festivo: ${esFestivo}`);

        if (esFestivo && window.UIUtils && window.UIUtils.mostrarNotificacion) {
            window.UIUtils.mostrarNotificacion("Fecha Domingo/Festivo. Cambiando a INTERURBANO.", 'warning');
        }

        // Seleccionamos las filas de la tabla de edición
        const filas = document.querySelectorAll('#tablaEdicion tr');

        filas.forEach(tr => {
            if(!tr.id.includes('fila_')) return;
            const idFila = tr.id.replace('fila_', '');
            const selModalidad = document.getElementById(`sel_modalidad_${idFila}`);

            if (selModalidad) {
                if (esFestivo) {
                    if (selModalidad.value !== "2") {
                        selModalidad.value = "2"; 
                        selModalidad.dataset.cambioAutomatico = "true";
                    }
                    selModalidad.classList.add('bg-yellow-100', 'border-yellow-500', 'text-yellow-800', 'font-bold');
                } else {
                    if (selModalidad.value === "2") {
                        selModalidad.value = "1"; 
                        delete selModalidad.dataset.cambioAutomatico;
                    }
                    selModalidad.classList.remove('bg-yellow-100', 'border-yellow-500', 'text-yellow-800', 'font-bold');
                }

                $(selModalidad).trigger('change');
                if (window.DetalleAjax && window.DetalleAjax.actualizarTarifa) {
                    window.DetalleAjax.actualizarTarifa(idFila);
                }
            }
        });
    });
}

/**
 * Inicializar gestión de tiempos
 */
function inicializar() {
    configurarDetectorFechas();
    manejarCambioFechaGlobal();
}

// Exportar
window.DetalleFechaUtils = {
    esDiaEspecial,
    horaAMinutos,
    calcularDuracion,
    calcTiempo,          // Añadido
    activarInputHora,    // Añadido
    configurarDetectorFechas,
    manejarCambioFechaGlobal, // Añadido
    inicializar          // Añadido
};

// Retrocompatibilidad global por si otros scripts llaman a calcTiempo directamente
window.calcTiempo = calcTiempo;