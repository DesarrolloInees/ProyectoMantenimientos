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

    // Extraer datos crudos
    let datosCrudos = filas.map(fila => {
        let idFila = fila.id.replace('fila_', '');
        let selectTecnico = fila.querySelector(`select[name^="servicios"][name$="[id_tecnico]"]`);
        let tecnicoVal = selectTecnico ? selectTecnico.value : "0";

        let entrada = document.getElementById(`hora_entrada_${idFila}`).value;
        let salida = document.getElementById(`hora_salida_${idFila}`).value;

        return {
            idFila: idFila,
            tecnico: parseInt(tecnicoVal) || 0,
            horaEntradaTexto: entrada,
            horaSalidaTexto: salida,
            minutosEntrada: window.DetalleFechaUtils.horaAMinutos(entrada),
            minutosSalida: window.DetalleFechaUtils.horaAMinutos(salida)
        };
    });

    // Filtrar duplicados
    let datosUnicos = [];
    const map = new Map();
    for (const item of datosCrudos) {
        if (!map.has(item.idFila)) {
            map.set(item.idFila, true);
            datosUnicos.push(item);
        }
    }
    let datos = datosUnicos;

    // Ordenar por técnico y hora de entrada
    datos.sort((a, b) => {
        if (a.tecnico !== b.tecnico) return a.tecnico - b.tecnico;
        let minA = a.minutosEntrada !== null ? a.minutosEntrada : 99999;
        let minB = b.minutosEntrada !== null ? b.minutosEntrada : 99999;
        return minA - minB;
    });

    // Calcular desplazamientos
    for (let i = 0; i < datos.length; i++) {
        let actual = datos[i];
        let span = document.getElementById(`desplazamiento_${actual.idFila}`);
        if (!span) continue;

        // Reset visual
        span.className = "text-[10px] font-bold block";
        span.innerText = "-";

        // Primer servicio del técnico
        if (i === 0 || datos[i - 1].tecnico !== actual.tecnico) {
            span.innerText = "00:00";
            span.classList.add("text-gray-400");
            continue;
        }

        let previo = datos[i - 1];

        if (actual.minutosEntrada === null || previo.minutosSalida === null) {
            span.innerText = "--";
            continue;
        }

        let diff = actual.minutosEntrada - previo.minutosSalida;

        if (diff < 0) {
            span.innerText = "Err H.";
            span.classList.add("text-red-500", "font-bold");
        } else {
            let h = Math.floor(diff / 60);
            let m = diff % 60;
            span.innerText = (h > 0 ? `${h}h ` : "") + `${m}m`;

            if (diff > 60) {
                span.classList.add("text-red-600", "bg-red-100", "px-1", "rounded");
                
                // 🔔 NOTIFICACIÓN de desplazamiento alto (solo si es > 2 horas)
                if (diff > 120) {
                    const h = Math.floor(diff / 60);
                    const m = diff % 60;
                    const tiempo = (h > 0 ? `${h}h ` : "") + `${m}m`;
                    window.DetalleNotificaciones.notificarDesplazamientoAlto(actual.idFila, tiempo);
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