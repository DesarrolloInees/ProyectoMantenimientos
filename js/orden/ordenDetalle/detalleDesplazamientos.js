// ==========================================
// CÁLCULO DE DESPLAZAMIENTOS ENTRE SERVICIOS (OPTIMIZADO 🚀)
// ==========================================

/**
 * Calcular desplazamientos entre servicios
 * @param {string|null} idFilaEditada Si se pasa, recalcula SOLO a ese técnico
 */
function calcularDesplazamientos(idFilaEditada = null) {
    // Mandamos el cálculo al "fondo" para que no congele la pantalla al arrancar
    requestAnimationFrame(() => {
        let filas = Array.from(document.querySelectorAll('.fila-servicio'));
        if (filas.length === 0) return;

        // 1. Averiguar si solo debemos recalcular un técnico específico
        let tecnicoObjetivo = null;
        if (idFilaEditada) {
            let selectFila = document.getElementById(`sel_tecnico_${idFilaEditada}`);
            if (selectFila) tecnicoObjetivo = parseInt(selectFila.value, 10);
        }

        // 🛡️ Polyfill/Fallback para evitar que la conversión de horas falle
        const horaAMinutosSafe = (hora) => {
            if (!hora) return null;
            if (window.DetalleFechaUtils && typeof window.DetalleFechaUtils.horaAMinutos === 'function') {
                let res = window.DetalleFechaUtils.horaAMinutos(hora);
                if (res !== null && !isNaN(res)) return res;
            }
            let partes = hora.split(':');
            if (partes.length >= 2) {
                return parseInt(partes[0], 10) * 60 + parseInt(partes[1], 10);
            }
            return null;
        };

        // 🛡️ Extracción de datos ultra-rápida
        let datosCrudos = filas.map(fila => {
            let idFila = fila.id.replace('fila_', '');
            let selectTecnico = document.getElementById(`sel_tecnico_${idFila}`);
            let tecnicoVal = selectTecnico ? selectTecnico.value : "0";

            let inputEntrada = document.getElementById(`hora_entrada_${idFila}`);
            let inputSalida = document.getElementById(`hora_salida_${idFila}`);
            
            return {
                idFila: idFila,
                tecnico: parseInt(tecnicoVal, 10) || 0,
                minutosEntrada: horaAMinutosSafe(inputEntrada ? inputEntrada.value : ""),
                minutosSalida: horaAMinutosSafe(inputSalida ? inputSalida.value : "")
            };
        });

        // 🔥 FILTRO DE VELOCIDAD: Si editamos a un técnico, descartamos a los demás
        if (tecnicoObjetivo !== null && !isNaN(tecnicoObjetivo)) {
            datosCrudos = datosCrudos.filter(d => d.tecnico === tecnicoObjetivo);
        }

        // Filtrar duplicados
        let datosUnicos = [];
        const map = new Map();
        for (const item of datosCrudos) {
            if (!map.has(item.idFila)) {
                map.set(item.idFila, true);
                datosUnicos.push(item);
            }
        }

        // Ordenar por técnico y hora de entrada
        datosUnicos.sort((a, b) => {
            if (a.tecnico !== b.tecnico) return a.tecnico - b.tecnico;
            let minA = a.minutosEntrada !== null ? a.minutosEntrada : 99999;
            let minB = b.minutosEntrada !== null ? b.minutosEntrada : 99999;
            return minA - minB;
        });

        // Calcular desplazamientos y pintar en el DOM
        for (let i = 0; i < datosUnicos.length; i++) {
            let actual = datosUnicos[i];
            let span = document.getElementById(`desplazamiento_${actual.idFila}`);
            
            if (!span) continue;

            span.className = "text-[10px] font-bold block"; // Reset
            
            // Primer servicio del técnico (o sin técnico)
            if (i === 0 || datosUnicos[i - 1].tecnico !== actual.tecnico || actual.tecnico === 0) {
                span.innerText = "00:00";
                span.classList.add("text-gray-400");
                continue;
            }

            let previo = datosUnicos[i - 1];

            // Faltan horas
            if (actual.minutosEntrada === null || previo.minutosSalida === null) {
                span.innerText = "--";
                span.classList.add("text-gray-400");
                continue;
            }

            let diff = actual.minutosEntrada - previo.minutosSalida;

            if (diff < 0) {
                span.innerText = "Err H.";
                span.classList.add("text-red-500");
                span.title = "Conflicto: Entrada antes de la salida anterior";
            } else {
                let h = Math.floor(diff / 60);
                let m = diff % 60;
                span.innerText = (h > 0 ? `${h}h ` : "") + `${m}m`;

                if (diff > 60) {
                    span.classList.add("text-red-600", "bg-red-100", "px-1", "rounded");
                    
                    // 🛡️ LA CURA DE LA AMETRALLADORA: 
                    // Solo notifica si los desplazamientos son muy altos Y no estamos en el arranque inicial
                    if (diff > 120 && window.silenciarNotificacionesInicio !== true) {
                        const tiempoStr = (h > 0 ? `${h}h ` : "") + `${m}m`;
                        window.DetalleNotificaciones?.notificarDesplazamientoAlto?.(actual.idFila, tiempoStr);
                    }
                } else {
                    span.classList.add("text-green-600");
                }
            }
        }
    });
}

// Exportar
window.DetalleDesplazamientos = {
    calcularDesplazamientos
};

// Retrocompatibilidad
window.calcularDesplazamientos = calcularDesplazamientos;