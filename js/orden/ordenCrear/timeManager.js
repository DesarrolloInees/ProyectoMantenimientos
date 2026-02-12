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

    // Usamos 'change' y también 'input' por si acaso
    inputFechaGlobal.addEventListener('change', function () {
        const fechaSeleccionada = this.value;
        const esFestivo = esDiaEspecial(fechaSeleccionada);

        console.log(`📅 Fecha: ${fechaSeleccionada} | Festivo: ${esFestivo}`);

        if (esFestivo) {
            if (window.UIUtils && window.UIUtils.mostrarNotificacion) {
                window.UIUtils.mostrarNotificacion("Fecha Domingo/Festivo. Cambiando a INTERURBANO.", 'warning');
            }
        }

        // Actualizar todas las filas activas
        const filas = document.querySelectorAll('#contenedorFilas tr');

        filas.forEach(tr => {
            // Obtenemos el ID de la fila (ej: "fila_1" -> "1")
            const idFila = tr.id.replace('fila_', '');
            const selectModalidad = document.getElementById(`select_modalidad_${idFila}`);

            if (selectModalidad) {
                // ========================================================
                // 🔥 AQUÍ ESTÁ LA CORRECCIÓN DE LA LÓGICA
                // ========================================================
                
                if (esFestivo) {
                    // 1. Si es festivo -> Forzamos INTERURBANO (2)
                    if (selectModalidad.value !== "2") {
                        selectModalidad.value = "2"; 
                        // Guardamos una marca para saber que fue cambiado por script
                        selectModalidad.dataset.cambioAutomatico = "true";
                    }
                    
                    // Aplicamos estilos visuales de advertencia
                    selectModalidad.classList.add('bg-yellow-100', 'border-yellow-500', 'text-yellow-800', 'font-bold');

                } else {
                    // 2. Si YA NO es festivo (es Lunes, Martes, etc.)
                    
                    // Solo regresamos a URBANO (1) si la modalidad actual es INTERURBANO (2)
                    // Esto evita romper configuraciones manuales si usaras otras modalidades (ej: ID 3)
                    if (selectModalidad.value === "2") {
                         selectModalidad.value = "1"; // Vuelve a Urbano
                         delete selectModalidad.dataset.cambioAutomatico;
                    }

                    // Limpiamos los estilos
                    selectModalidad.classList.remove('bg-yellow-100', 'border-yellow-500', 'text-yellow-800', 'font-bold');
                }

                // ========================================================
                // ⚡ IMPORTANTE: FORZAR RECALCULO DE PRECIO
                // ========================================================
                // Al cambiar el valor por JS, el evento 'change' no se dispara solo.
                // Debemos dispararlo manualmente para que AJAX recalcule la tarifa.
                
                if (typeof $ !== 'undefined') {
                    $(selectModalidad).trigger('change');
                } else {
                    // Versión JS Nativo por si jQuery falla
                    const event = new Event('change', { bubbles: true });
                    selectModalidad.dispatchEvent(event);
                }
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