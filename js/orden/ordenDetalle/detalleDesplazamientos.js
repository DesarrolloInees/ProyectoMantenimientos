// ==========================================
// CÁLCULO DE DESPLAZAMIENTOS ENTRE SERVICIOS
// ==========================================

/**
 * Calcular desplazamientos entre servicios
 */
function calcularDesplazamientos() {
    console.clear();
    console.log("--- INICIANDO CÁLCULO DE DESPLAZAMIENTOS ---");

    let filas = Array.from(document.querySelectorAll('.fila-servicio'));
    if (filas.length === 0) return;

    // 🛡️ 1. Polyfill/Fallback para evitar que la conversión de horas falle
    const horaAMinutosSafe = (hora) => {
        if (!hora) return null;
        // Intentar usar tu utilitario original
        if (window.DetalleFechaUtils && typeof window.DetalleFechaUtils.horaAMinutos === 'function') {
            let res = window.DetalleFechaUtils.horaAMinutos(hora);
            if (res !== null && !isNaN(res)) return res;
        }
        // Fallback manual si el utilitario no existe o falla
        let partes = hora.split(':');
        if (partes.length >= 2) {
            return parseInt(partes[0], 10) * 60 + parseInt(partes[1], 10);
        }
        return null;
    };

    // 🛡️ 2. Extracción segura de datos
    let datosCrudos = filas.map(fila => {
        let idFila = fila.id.replace('fila_', '');
        
        // Usamos el ID directo que tienes en detalleFila.php (mucho más seguro)
        let selectTecnico = document.getElementById(`sel_tecnico_${idFila}`);
        let tecnicoVal = selectTecnico ? selectTecnico.value : "0";

        // Prevenimos error si el input no existe en el DOM por alguna razón
        let inputEntrada = document.getElementById(`hora_entrada_${idFila}`);
        let inputSalida = document.getElementById(`hora_salida_${idFila}`);
        
        let entrada = inputEntrada ? inputEntrada.value : "";
        let salida = inputSalida ? inputSalida.value : "";

        return {
            idFila: idFila,
            tecnico: parseInt(tecnicoVal, 10) || 0,
            minutosEntrada: horaAMinutosSafe(entrada),
            minutosSalida: horaAMinutosSafe(salida)
        };
    });

    // 3. Filtrar duplicados
    let datosUnicos = [];
    const map = new Map();
    for (const item of datosCrudos) {
        if (!map.has(item.idFila)) {
            map.set(item.idFila, true);
            datosUnicos.push(item);
        }
    }

    // 4. Ordenar por técnico y hora de entrada
    datosUnicos.sort((a, b) => {
        if (a.tecnico !== b.tecnico) return a.tecnico - b.tecnico;
        let minA = a.minutosEntrada !== null ? a.minutosEntrada : 99999;
        let minB = b.minutosEntrada !== null ? b.minutosEntrada : 99999;
        return minA - minB;
    });

    // 5. Calcular desplazamientos
    for (let i = 0; i < datosUnicos.length; i++) {
        let actual = datosUnicos[i];
        let span = document.getElementById(`desplazamiento_${actual.idFila}`);
        
        if (!span) continue;

        // Reset visual
        span.className = "text-[10px] font-bold block";
        
        // Primer servicio del técnico
        if (i === 0 || datosUnicos[i - 1].tecnico !== actual.tecnico) {
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
                
                // 🛡️ 3. Notificación protegida con Optional Chaining (?.)
                // Esto evita que el código muera si DetalleNotificaciones no existe
                if (diff > 120) {
                    const tiempoStr = (h > 0 ? `${h}h ` : "") + `${m}m`;
                    window.DetalleNotificaciones?.notificarDesplazamientoAlto?.(actual.idFila, tiempoStr);
                }
            } else {
                span.classList.add("text-green-600");
            }
        }
    }

    console.log("✅ Cálculo de desplazamientos completado");
}

// Exportar
window.DetalleDesplazamientos = {
    calcularDesplazamientos
};

// Retrocompatibilidad
window.calcularDesplazamientos = calcularDesplazamientos;