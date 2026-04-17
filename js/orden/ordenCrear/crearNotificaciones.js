// ==========================================
// SISTEMA DE NOTIFICACIONES - CREAR SERVICIOS
// ==========================================

/**
 * Configuración de notificaciones
 */
const NotifConfig = {
    duracion: 4000,
    posicion: 'top-right',
    sonido: true,
    maxNotificaciones: 3
};

let notificacionesActivas = [];

/**
 * Tipos de notificaciones con estilos
 */
const TiposNotificacion = {
    exito: {
        icono: 'fa-check-circle',
        color: 'bg-green-500',
        borderColor: 'border-green-600',
        sonido: 'success'
    },
    info: {
        icono: 'fa-info-circle',
        color: 'bg-blue-500',
        borderColor: 'border-blue-600',
        sonido: 'info'
    },
    advertencia: {
        icono: 'fa-exclamation-triangle',
        color: 'bg-yellow-500',
        borderColor: 'border-yellow-600',
        sonido: 'warning'
    },
    error: {
        icono: 'fa-times-circle',
        color: 'bg-red-500',
        borderColor: 'border-red-600',
        sonido: 'error'
    },
    festivo: {
        icono: 'fa-calendar-day',
        color: 'bg-purple-500',
        borderColor: 'border-purple-600',
        sonido: 'info'
    },
    precio: {
        icono: 'fa-dollar-sign',
        color: 'bg-emerald-500',
        borderColor: 'border-emerald-600',
        sonido: 'success'
    },
    stock: {
        icono: 'fa-box',
        color: 'bg-orange-500',
        borderColor: 'border-orange-600',
        sonido: 'warning'
    },
    remision: {
        icono: 'fa-receipt',
        color: 'bg-pink-500',
        borderColor: 'border-pink-600',
        sonido: 'warning'
    },
    guardado: {
        icono: 'fa-save',
        color: 'bg-teal-500',
        borderColor: 'border-teal-600',
        sonido: 'success'
    }
};

/**
 * Mostrar notificación
 */
function mostrarNotificacion(mensaje, tipo = 'info', duracion = null) {
    if (notificacionesActivas.length >= NotifConfig.maxNotificaciones) {
        eliminarNotificacionMasAntigua();
    }

    const config = TiposNotificacion[tipo] || TiposNotificacion.info;
    const id = `notif_${Date.now()}`;
    duracion = duracion || NotifConfig.duracion;

    const notif = document.createElement('div');
    notif.id = id;
    notif.className = `fixed z-[9999] ${config.color} text-white px-6 py-4 rounded-lg shadow-2xl 
                       border-l-4 ${config.borderColor} transform transition-all duration-300 
                       flex items-center gap-3 min-w-[300px] max-w-[450px]`;

    const posiciones = {
        'top-right': 'top-4 right-4',
        'top-left': 'top-4 left-4',
        'bottom-right': 'bottom-4 right-4',
        'bottom-left': 'bottom-4 left-4'
    };
    notif.className += ` ${posiciones[NotifConfig.posicion]}`;

    const offset = notificacionesActivas.length * 90;
    if (NotifConfig.posicion.includes('top')) {
        notif.style.top = `${16 + offset}px`;
    } else {
        notif.style.bottom = `${16 + offset}px`;
    }

    notif.innerHTML = `
        <div class="flex-shrink-0">
            <i class="fas ${config.icono} text-2xl"></i>
        </div>
        <div class="flex-1">
            <p class="font-semibold text-sm leading-relaxed">${mensaje}</p>
        </div>
        <button onclick="window.CrearNotificaciones.cerrarNotificacion('${id}')" 
                class="flex-shrink-0 hover:bg-white hover:bg-opacity-20 rounded p-1 transition">
            <i class="fas fa-times"></i>
        </button>
    `;

    notif.style.opacity = '0';
    notif.style.transform = 'translateX(100px)';

    document.body.appendChild(notif);
    notificacionesActivas.push(id);

    if (NotifConfig.sonido) {
        reproducirSonido(config.sonido);
    }

    setTimeout(() => {
        notif.style.opacity = '1';
        notif.style.transform = 'translateX(0)';
    }, 10);

    setTimeout(() => {
        cerrarNotificacion(id);
    }, duracion);
}

/**
 * Cerrar notificación
 */
function cerrarNotificacion(id) {
    const notif = document.getElementById(id);
    if (!notif) return;

    notif.style.opacity = '0';
    notif.style.transform = 'translateX(100px)';

    setTimeout(() => {
        notif.remove();
        notificacionesActivas = notificacionesActivas.filter(n => n !== id);
        reposicionarNotificaciones();
    }, 300);
}

/**
 * Eliminar la más antigua
 */
function eliminarNotificacionMasAntigua() {
    if (notificacionesActivas.length > 0) {
        cerrarNotificacion(notificacionesActivas[0]);
    }
}

/**
 * Reposicionar notificaciones
 */
function reposicionarNotificaciones() {
    notificacionesActivas.forEach((id, index) => {
        const notif = document.getElementById(id);
        if (notif) {
            const offset = index * 90;
            if (NotifConfig.posicion.includes('top')) {
                notif.style.top = `${16 + offset}px`;
            } else {
                notif.style.bottom = `${16 + offset}px`;
            }
        }
    });
}

// Variable global para el audio
let audioContextGlobal = null;

// Escuchamos cualquier clic en la página para "desbloquear" el motor de audio
document.addEventListener('click', () => {
    try {
        if (!audioContextGlobal) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            audioContextGlobal = new AudioContext();
        } else if (audioContextGlobal.state === 'suspended') {
            audioContextGlobal.resume();
        }
    } catch (e) { console.warn("Audio bloqueado", e); }
}, { once: true }); // El once:true hace que este evento se ejecute solo la primera vez

/**
 * Reproducir sonido (Versión Corregida)
 */
function reproducirSonido(tipo) {
    try {
        // Si el usuario aún no ha interactuado con la página, abortamos en silencio
        if (!audioContextGlobal || audioContextGlobal.state === 'suspended') return;

        // Reutilizamos el contexto global en lugar de crear uno nuevo
        const oscillator = audioContextGlobal.createOscillator();
        const gainNode = audioContextGlobal.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContextGlobal.destination);

        const frecuencias = {
            success: [523.25, 659.25],
            info: [440],
            warning: [392, 440],
            error: [329.63, 293.66]
        };

        const freqs = frecuencias[tipo] || frecuencias.info;

        oscillator.frequency.value = freqs[0];
        gainNode.gain.value = 0.1; // Volumen bajito
        oscillator.type = 'sine';

        oscillator.start(audioContextGlobal.currentTime);

        if (freqs.length > 1) {
            oscillator.frequency.setValueAtTime(freqs[1], audioContextGlobal.currentTime + 0.1);
        }

        oscillator.stop(audioContextGlobal.currentTime + 0.2);
    } catch (e) {
        // Silenciar errores
    }
}

// ==========================================
// NOTIFICACIONES ESPECÍFICAS - CREAR
// ==========================================

/**
 * Notificación de festivo detectado
 */
function notificarFestivo(fecha, esDomingo = false) {
    const tipo = esDomingo ? 'Domingo' : 'Festivo';
    const mensaje = `📅 ${tipo} detectado: ${formatearFecha(fecha)}<br>✨ Se aplicará tarifa INTERURBANA automáticamente`;
    mostrarNotificacion(mensaje, 'festivo', 5000);
}

/**
 * Notificación de precio calculado
 */
function notificarPrecioCalculado(idFila, precio) {
    const mensaje = `💰 Precio calculado en Fila #${idFila}<br>✅ Tarifa: ${formatearMoneda(precio)}`;
    mostrarNotificacion(mensaje, 'precio', 3000);
}

/**
 * Notificación de remisión duplicada (NUEVA)
 */
function notificarRemisionDuplicada(numeroRemision, idFila) {
    const mensaje = `⚠️ Remisión DUPLICADA: ${numeroRemision}<br>📋 Esta remisión ya fue usada en otra fila`;
    mostrarNotificacion(mensaje, 'remision', 5000);

    // Marcar visualmente el campo
    const selectRemision = document.getElementById(`select_remision_${idFila}`);
    if (selectRemision) {
        selectRemision.classList.add('border-red-500', 'bg-red-50');
        setTimeout(() => {
            selectRemision.classList.remove('border-red-500', 'bg-red-50');
        }, 3000);
    }
}

/**
 * Notificación de remisión válida (NUEVA)
 */
function notificarRemisionValida(numeroRemision, idFila) {
    const mensaje = `✅ Remisión válida: ${numeroRemision}<br>📝 Disponible para uso`;
    mostrarNotificacion(mensaje, 'exito', 2000);
}

/**
 * Notificación de técnico sin remisiones (NUEVA)
 */
function notificarTecnicoSinRemisiones(nombreTecnico) {
    const mensaje = `🚫 ${nombreTecnico} no tiene remisiones<br>⚠️ Asigne un talonario en administración`;
    mostrarNotificacion(mensaje, 'advertencia', 5000);
}

/**
 * Notificación de stock insuficiente
 */
function notificarStockInsuficiente(repuesto, disponible, solicitado) {
    const mensaje = `⚠️ Stock insuficiente: ${repuesto}<br>📦 Disponible: ${disponible} | Solicitado: ${solicitado}`;
    mostrarNotificacion(mensaje, 'stock', 4000);
}

/**
 * Notificación de repuestos agregados
 */
function notificarRepuestosAgregados(cantidad, total) {
    const mensaje = `✅ ${cantidad} repuesto(s) agregado(s)<br>📦 Total en servicio: ${total} items`;
    mostrarNotificacion(mensaje, 'exito', 2500);
}

/**
 * Notificación de datos cargados
 */
function notificarDatosCargados(tipo, cantidad, idFila) {
    const mensaje = `🔄 ${tipo} cargados en Fila #${idFila}<br>📋 ${cantidad} disponible(s)`;
    mostrarNotificacion(mensaje, 'info', 2000);
}

/**
 * Notificación de fila agregada
 */
function notificarFilaAgregada(numeroFila) {
    const mensaje = `➕ Nueva fila agregada: #${numeroFila}<br>📝 Completa los datos del servicio`;
    mostrarNotificacion(mensaje, 'info', 2000);
}

/**
 * Notificación de fila eliminada
 */
function notificarFilaEliminada(numeroFila) {
    const mensaje = `🗑️ Fila #${numeroFila} eliminada<br>✅ Servicio removido del reporte`;
    mostrarNotificacion(mensaje, 'info', 2000);
}

/**
 * Notificación de auto-guardado
 */
function notificarAutoGuardado() {
    const mensaje = `💾 Progreso guardado automáticamente<br>🔒 Tus datos están seguros`;
    mostrarNotificacion(mensaje, 'guardado');
}

/**
 * Notificación de borrador recuperado
 */
function notificarBorradorRecuperado(cantidadFilas) {
    const mensaje = `📂 Borrador recuperado exitosamente<br>📋 ${cantidadFilas} servicio(s) restaurado(s)`;
    mostrarNotificacion(mensaje, 'exito', 3000);
}

/**
 * Notificación de validación de coherencia
 */
function notificarIncoherenciaServicio(idFila, tipoServicio, tieneRepuestos) {
    let mensaje = '';

    if (tipoServicio.includes('CORRECTIVO') && !tieneRepuestos) {
        mensaje = `🤔 Fila #${idFila}: Correctivo SIN repuestos<br>¿Seguro que no se usaron piezas?`;
    } else if (tipoServicio.includes('PREVENTIVO') && tieneRepuestos) {
        mensaje = `🤔 Fila #${idFila}: Preventivo CON repuestos<br>¿Debería ser Correctivo?`;
    }

    if (mensaje) {
        mostrarNotificacion(mensaje, 'advertencia', 4000);
    }
}

/**
 * Notificación de validación de campos vacíos (NUEVA)
 */
function notificarCamposIncompletos(idFila, camposFaltantes) {
    const camposTexto = camposFaltantes.join(', ');
    const mensaje = `⚠️ Fila #${idFila}: Datos incompletos<br>📝 Faltan: ${camposTexto}`;
    mostrarNotificacion(mensaje, 'advertencia', 4000);
}

/**
 * Notificación de hora inválida (NUEVA)
 */
function notificarHoraInvalida(idFila, campo) {
    const mensaje = `⚠️ Hora inválida en Fila #${idFila}<br>⏰ Use formato 24h (00:00 - 23:59) en ${campo}`;
    mostrarNotificacion(mensaje, 'error', 4000);
}

/**
 * Notificación de duración calculada (NUEVA)
 */
function notificarDuracionCalculada(idFila, duracion) {
    const mensaje = `⏱️ Duración calculada en Fila #${idFila}<br>✅ Tiempo de servicio: ${duracion}`;
    mostrarNotificacion(mensaje, 'info', 2000);
}

/**
 * Notificación de error de conexión
 */
function notificarError(mensaje) {
    mostrarNotificacion(`❌ Error: ${mensaje}`, 'error', 4000);
}

/**
 * Notificación de guardado exitoso (NUEVA)
 */
function notificarGuardadoExitoso(cantidadServicios) {
    const mensaje = `🎉 ¡Reporte guardado exitosamente!<br>✅ ${cantidadServicios} servicio(s) registrado(s)`;
    mostrarNotificacion(mensaje, 'exito', 5000);
}

/**
 * Notificación de formulario enviando (NUEVA)
 */
function notificarEnviandoFormulario(cantidadServicios) {
    const mensaje = `⏳ Guardando reporte...<br>📤 Procesando ${cantidadServicios} servicio(s)`;
    mostrarNotificacion(mensaje, 'info', 10000); // Más tiempo porque es un proceso largo
}

/**
 * Notificación de inventario cargado (NUEVA)
 */
function notificarInventarioCargado(nombreTecnico, cantidadItems) {
    const mensaje = `📦 Inventario de ${nombreTecnico}<br>✅ ${cantidadItems} repuesto(s) disponible(s)`;
    mostrarNotificacion(mensaje, 'stock', 2500);
}

/**
 * Notificación de máquina auto-seleccionada (NUEVA)
 */
function notificarMaquinaAutoSeleccionada(idFila, deviceId) {
    const mensaje = `🤖 Máquina seleccionada automáticamente<br>📟 Device ID: ${deviceId}`;
    mostrarNotificacion(mensaje, 'info', 2000);
}

// ==========================================
// UTILIDADES
// ==========================================

/**
 * Formatear fecha
 */
function formatearFecha(fecha) {
    if (!fecha) return '';
    const [y, m, d] = fecha.split('-');
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return `${d} ${meses[parseInt(m) - 1]} ${y}`;
}

/**
 * Formatear moneda
 */
function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(valor);
}

/**
 * Configurar notificaciones
 */
function configurarNotificaciones(opciones = {}) {
    Object.assign(NotifConfig, opciones);
}


// ==========================================
// NUEVO: MODAL DE CONFIRMACIÓN (BLOQUEANTE) Y VALIDACIÓN ESTRICTA
// ==========================================

/**
 * Valida de forma ESTRICTA que todos los campos obligatorios estén llenos
 * @returns {boolean} true si todo está bien, false si hay errores
 */
function validarCamposEstrictos() {
    const filas = document.querySelectorAll('#contenedorFilas tr');
    let formularioValido = true;
    let mensajesError = [];

    filas.forEach((tr, index) => {
        const numFila = index + 1;
        // Solo validamos las filas que tengan una máquina seleccionada
        const campoMaquina = tr.querySelector('select[name*="[id_maquina]"]');
        
        if (campoMaquina && campoMaquina.value !== '') {
            // Buscamos los demás campos clave de esta fila
            const campoRemision = tr.querySelector('select[name*="[remision]"]');
            const campoHoraIn = tr.querySelector('input[name*="[hora_in]"]');
            const campoHoraOut = tr.querySelector('input[name*="[hora_out]"]');
            const campoObs = tr.querySelector('textarea[name*="[obs]"]');
            
            let faltan = [];

            // Validar Remisión (Como es un select, verificamos que no esté vacío)
            if (!campoRemision || campoRemision.value.trim() === '') {
                faltan.push('Remisión');
                if(campoRemision) campoRemision.classList.add('border-red-500', 'bg-red-50');
            }

            // Validar Horas
            if (!campoHoraIn || campoHoraIn.value.trim() === '') {
                faltan.push('Hora Entrada');
                if(campoHoraIn) campoHoraIn.classList.add('border-red-500', 'bg-red-50');
            }
            if (!campoHoraOut || campoHoraOut.value.trim() === '') {
                faltan.push('Hora Salida');
                if(campoHoraOut) campoHoraOut.classList.add('border-red-500', 'bg-red-50');
            }

            // Validar Observaciones
            if (!campoObs || campoObs.value.trim() === '') {
                faltan.push('¿Qué se Realizó?');
                if(campoObs) campoObs.classList.add('border-red-500', 'bg-red-50');
            }

            // Si faltó algo en esta fila, anotamos el error
            if (faltan.length > 0) {
                formularioValido = false;
                mensajesError.push(`<b>Fila ${numFila}:</b> ${faltan.join(', ')}`);
                
                // Quitamos el resaltado rojo después de unos segundos
                setTimeout(() => {
                    if(campoRemision) campoRemision.classList.remove('border-red-500', 'bg-red-50');
                    if(campoHoraIn) campoHoraIn.classList.remove('border-red-500', 'bg-red-50');
                    if(campoHoraOut) campoHoraOut.classList.remove('border-red-500', 'bg-red-50');
                    if(campoObs) campoObs.classList.remove('border-red-500', 'bg-red-50');
                }, 5000);
            }
        }
    });

    if (!formularioValido) {
        // Mostramos una notificación consolidada con todos los errores
        mostrarNotificacion(
            `❌ Error antes de guardar:<br>${mensajesError.join('<br>')}`, 
            'error', 
            6000
        );
    }

    return formularioValido;
}

/**
 * Muestra un modal que exige respuesta del usuario
 * PREVIAMENTE valida que los campos estrictos estén llenos.
 * @param {string} mensaje - El texto de la pregunta
 * @param {function} callbackConfirmar - Función a ejecutar si dice SÍ
 */
function mostrarModalConfirmacion(mensaje, callbackConfirmar) {
    
    // 🔥 PRIMERO: Validamos estrictamente. Si falla, cortamos la ejecución y NO sale el modal.
    if (!validarCamposEstrictos()) {
        return; 
    }

    const id = 'modal_confirm_' + Date.now();

    const modal = document.createElement('div');
    modal.id = id;
    modal.className = 'fixed inset-0 z-[10000] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm transition-opacity duration-300';

    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full transform scale-100 transition-transform duration-300 border-t-4 border-blue-600">
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                    <i class="fas fa-question text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">¿Estás seguro?</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500">${mensaje}</p>
                </div>
            </div>
            <div class="flex justify-center gap-3">
                <button id="btn_cancelar_${id}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancelar
                </button>
                <button id="btn_confirmar_${id}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Confirmar y Guardar
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Eventos
    document.getElementById(`btn_cancelar_${id}`).onclick = () => {
        modal.style.opacity = '0';
        setTimeout(() => modal.remove(), 300);
    };

    document.getElementById(`btn_confirmar_${id}`).onclick = () => {
        // 1. Desaparecemos el modal de confirmación
        modal.style.opacity = '0';
        
        // 2. 🔥 ACTIVAMOS LA PANTALLA DE CARGA BLOQUEANTE 🔥
        const pantallaCarga = document.getElementById('pantallaCargaGuardando');
        if (pantallaCarga) {
            pantallaCarga.classList.remove('hidden');
        }

        setTimeout(() => {
            modal.remove();
            // 3. Ejecutamos el envío del formulario
            callbackConfirmar(); 
        }, 300);
    };

    // Animación de entrada
    modal.style.opacity = '0';
    setTimeout(() => modal.style.opacity = '1', 10);
}

/**
 * Valida coherencia de UNA fila (Correctivo vs Repuestos)
 * Retorna TRUE si hay error, FALSE si está bien
 */
function validarCoherenciaFila(idFila, tipoServicio, tieneRepuestos) {
    let mensaje = '';
    let hayError = false;

    // Lógica ajustada: Solo notifica, pero retornamos el estado para que decidas si bloqueas o no
    if (tipoServicio.toUpperCase().includes('CORRECTIVO') && !tieneRepuestos) {
        mensaje = `🤔 Fila #${idFila}: Es Mantenimiento CORRECTIVO pero no has agregado repuestos.<br>¿Seguro que no se usaron piezas?`;
        hayError = true;
    } else if (tipoServicio.toUpperCase().includes('PREVENTIVO') && tieneRepuestos) {
        mensaje = `🤔 Fila #${idFila}: Es Mantenimiento PREVENTIVO pero tiene repuestos.<br>¿Debería ser Correctivo?`;
        hayError = true;
    }

    if (mensaje) {
        // Usamos advertencia sonora y visual
        mostrarNotificacion(mensaje, 'advertencia', 6000);
    }

    return hayError;
}

// Exportar
window.CrearNotificaciones = {
    mostrarNotificacion,
    cerrarNotificacion,
    configurarNotificaciones,

    // Notificaciones específicas
    notificarFestivo,
    notificarPrecioCalculado,
    notificarRemisionDuplicada,
    notificarRemisionValida,
    notificarTecnicoSinRemisiones,
    notificarStockInsuficiente,
    notificarRepuestosAgregados,
    notificarDatosCargados,
    notificarFilaAgregada,
    notificarFilaEliminada,
    notificarAutoGuardado,
    notificarBorradorRecuperado,
    notificarIncoherenciaServicio,
    notificarCamposIncompletos,
    notificarHoraInvalida,
    notificarDuracionCalculada,
    notificarError,
    notificarGuardadoExitoso,
    notificarEnviandoFormulario,
    notificarInventarioCargado,
    notificarMaquinaAutoSeleccionada,
    mostrarModalConfirmacion,
    validarCoherenciaFila,
    validarCamposEstrictos // Exportamos la nueva función por si la necesitas en otro lado
};

console.log('🔔 Sistema de notificaciones CREAR cargado');