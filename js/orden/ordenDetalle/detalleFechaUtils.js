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
 * 🔥 FORMATO EN VIVO: Hora Militar Inteligente
 */
function activarInputHora() {

    // ─── MIENTRAS ESCRIBE: formato en vivo ───
    $(document).off('input.hora', '.input-hora-militar')
               .on('input.hora',  '.input-hora-militar', function () {

        const input  = this;
        const selIni = input.selectionStart;

        // Sacamos solo dígitos del valor actual
        let nums = input.value.replace(/\D/g, '');

        // Máximo 4 dígitos
        if (nums.length > 4) nums = nums.slice(0, 4);

        // Si el primer dígito es 3-9, anteponemos 0 (ej: "8" → "08")
        if (nums.length === 1 && parseInt(nums[0]) > 2) {
            nums = '0' + nums;
        }

        // Construir el string formateado
        let formatted = '';
        if (nums.length <= 2) {
            formatted = nums;                                   // "14"
        } else {
            formatted = nums.slice(0, 2) + ':' + nums.slice(2); // "14:30"
        }

        input.value = formatted;

        // Reposicionar cursor: si acabamos de pasar los 2 primeros dígitos,
        // saltamos el ":" que insertamos
        let newPos = selIni;
        if (formatted.length >= 3 && newPos === 2) newPos = 3;
        try { input.setSelectionRange(newPos, newPos); } catch (e) {}
    });

    // ─── AL SALIR: validar y completar ───
    $(document).off('blur.hora', '.input-hora-militar')
               .on('blur.hora',  '.input-hora-militar', function () {

        const input = this;
        let nums = input.value.replace(/\D/g, '');

        if (!nums) {
            input.value = '';
            input.classList.remove('border-red-500', 'bg-red-50');
            if (window.DetalleDesplazamientos?.calcularDesplazamientos) {
                window.DetalleDesplazamientos.calcularDesplazamientos();
            }
            return;
        }

        // Completar dígitos faltantes hasta 4
        while (nums.length < 4) nums += '0';
        nums = nums.slice(0, 4);

        const h = Math.min(23, parseInt(nums.slice(0, 2), 10));
        const m = Math.min(59, parseInt(nums.slice(2, 4), 10));

        input.value = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
        input.classList.remove('border-red-500', 'bg-red-50');

        if (window.DetalleDesplazamientos?.calcularDesplazamientos) {
            const idFila = input.id.split('_').pop();
            window.DetalleDesplazamientos.calcularDesplazamientos(idFila);
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
    activarInputHora,    // Añadido
    inicializar          // Añadido
};

// Retrocompatibilidad global por si otros scripts llaman a calcTiempo directamente
window.calcTiempo = calcTiempo;