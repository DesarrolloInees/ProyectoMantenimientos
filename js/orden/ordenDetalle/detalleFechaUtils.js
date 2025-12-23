// ==========================================
// UTILIDADES DE FECHAS Y FESTIVOS
// ==========================================

/**
 * Verificar si una fecha es festivo o domingo
 */
function esDiaEspecial(fechaString) {
    if (!fechaString) return false;

    // 1. Verificar si está en la lista de festivos
    if (window.DetalleConfig.FESTIVOS_DB.includes(fechaString)) {
        console.log(`📅 ${fechaString} es FESTIVO en BD.`);
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
 * Calcular duración entre dos horas
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
 * Configurar detector de cambio de fecha
 */
function configurarDetectorFechas() {
    $('#tablaEdicion').on('change', 'input[type="date"]', function() {
        let tr = $(this).closest('tr');
        let idFila = tr.attr('id').replace('fila_', '');
        let fechaNueva = $(this).val();

        console.log(`📅 Fecha cambiada en fila ${idFila} a: ${fechaNueva}`);

        // Verificar si es festivo
        if (esDiaEspecial(fechaNueva)) {
            let selModalidad = document.getElementById(`sel_modalidad_${idFila}`);

            if (selModalidad && selModalidad.value !== "2") {
                selModalidad.value = "2"; // Cambiar a Interurbano

                // Efecto visual
                selModalidad.style.backgroundColor = "#fef08a";
                selModalidad.style.borderColor = "#eab308";
                selModalidad.style.transition = "all 0.5s";

                setTimeout(() => {
                    selModalidad.style.backgroundColor = "";
                    selModalidad.style.borderColor = "";
                }, 2000);

                // 🔔 NOTIFICACIÓN
                const esDomingo = new Date(fechaNueva + 'T12:00:00').getDay() === 0;
                window.DetalleNotificaciones.notificarFestivo(fechaNueva, esDomingo);

                console.log("-> 🚀 DETECTADO FESTIVO! Modalidad cambiada a INTERURBANO.");
            }
        }

        // Recalcular tarifa
        window.DetalleAjax.actualizarTarifa(idFila);
    });
}

// Exportar
window.DetalleFechaUtils = {
    esDiaEspecial,
    horaAMinutos,
    calcularDuracion,
    configurarDetectorFechas
};