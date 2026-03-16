// ==========================================
// SISTEMA DE NOTIFICACIONES INTELIGENTES (detalleNotificaciones.js)
// ==========================================

/**
 * Configuración de notificaciones
 */
const NotifConfig = {
    duracion: 4000, // Duración en milisegundos
    posicion: 'top-right', // top-right, top-left, bottom-right, bottom-left
    sonido: true, // Activar sonidos
    maxNotificaciones: 3 // Máximo de notificaciones simultáneas
};

let notificacionesActivas = [];

/**
 * Tipos de notificaciones con sus estilos (Fusionado con Crear)
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
    // 🔥 NUEVOS TIPOS TRAÍDOS DE CREAR
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
                       flex items-center gap-3 min-w-[300px] max-w-[450px]`; // Ajustado ancho máximo
    
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
        <button onclick="window.DetalleNotificaciones.cerrarNotificacion('${id}')" 
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
 * Eliminar la notificación más antigua
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

/**
 * Reproducir sonido (Web Audio API)
 */
function reproducirSonido(tipo) {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const audioCtx = new AudioContext();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        const frecuencias = {
            success: [523.25, 659.25], // Do-Mi
            info: [440], // La
            warning: [392, 440], // Sol-La
            error: [329.63, 293.66] // Mi-Re
        };

        const freqs = frecuencias[tipo] || frecuencias.info;
        
        oscillator.frequency.value = freqs[0];
        gainNode.gain.value = 0.1;
        oscillator.type = 'sine';

        oscillator.start(audioCtx.currentTime);
        
        if (freqs.length > 1) {
            oscillator.frequency.setValueAtTime(freqs[1], audioCtx.currentTime + 0.1);
        }
        
        oscillator.stop(audioCtx.currentTime + 0.2);
    } catch (e) {
        // Silenciar errores de audio
    }
}

// ==========================================
// NOTIFICACIONES ESPECÍFICAS - DETALLE Y CREAR FUSIONADAS
// ==========================================

// --- ORIGINALES DE DETALLE ---

function notificarFestivo(fecha, esDomingo = false) {
    const tipo = esDomingo ? 'Domingo' : 'Festivo';
    const mensaje = `📅 ${tipo} detectado: ${formatearFecha(fecha)}<br>✨ Modalidad cambiada a INTERURBANO automáticamente`;
    mostrarNotificacion(mensaje, 'festivo', 5000);
}

function notificarPrecioActualizado(idFila, precioAnterior, precioNuevo) {
    const diff = precioNuevo - precioAnterior;
    const simbolo = diff > 0 ? '↗️' : diff < 0 ? '↘️' : '➡️';
    const color = diff > 0 ? 'text-green-300' : diff < 0 ? 'text-red-300' : 'text-gray-300';
    const mensaje = `💰 Precio actualizado en Fila ${idFila}<br>${simbolo} ${formatearMoneda(precioAnterior)} → ${formatearMoneda(precioNuevo)}`;
    mostrarNotificacion(mensaje, 'precio', 3000);
}

function notificarDesplazamientoAlto(idFila, tiempo) {
    const mensaje = `⏰ Desplazamiento alto en Fila ${idFila}<br>🚗 Tiempo: ${tiempo}`;
    mostrarNotificacion(mensaje, 'advertencia', 3000);
}

function notificarCambioGuardado() {
    mostrarNotificacion(`✅ Cambios guardados correctamente`, 'exito', 2000);
}

// --- TRAÍDAS DE CREAR ---

function notificarPrecioCalculado(idFila, precio) {
    const mensaje = `💰 Precio calculado en Fila #${idFila}<br>✅ Tarifa: ${formatearMoneda(precio)}`;
    mostrarNotificacion(mensaje, 'precio', 3000);
}

function notificarRemisionDuplicada(numeroRemision, idFila) {
    const mensaje = `⚠️ Remisión DUPLICADA: ${numeroRemision}<br>📋 Esta remisión ya fue usada en otra fila`;
    mostrarNotificacion(mensaje, 'remision', 5000);
    const selectRemision = document.getElementById(`select_remision_${idFila}`);
    if (selectRemision) {
        selectRemision.classList.add('border-red-500', 'bg-red-50');
        setTimeout(() => selectRemision.classList.remove('border-red-500', 'bg-red-50'), 3000);
    }
}

function notificarRemisionValida(numeroRemision, idFila) {
    mostrarNotificacion(`✅ Remisión válida: ${numeroRemision}<br>📝 Disponible para uso`, 'exito', 2000);
}

function notificarTecnicoSinRemisiones(nombreTecnico) {
    mostrarNotificacion(`🚫 ${nombreTecnico} no tiene remisiones<br>⚠️ Asigne un talonario en administración`, 'advertencia', 5000);
}

function notificarStockInsuficiente(repuesto, disponible, solicitado) {
    const mensaje = `⚠️ Stock insuficiente: ${repuesto}<br>📦 Disponible: ${disponible} | Solicitado: ${solicitado}`;
    mostrarNotificacion(mensaje, 'stock', 4000);
}

function notificarRepuestosAgregados(cantidad, total) {
    mostrarNotificacion(`✅ ${cantidad} repuesto(s) agregado(s)<br>📦 Total en lista: ${total} items`, 'exito', 2500);
}

function notificarDatosCargados(tipo, cantidad, idFila = '') {
    const filaInfo = idFila ? ` en Fila #${idFila}` : '';
    mostrarNotificacion(`🔄 ${tipo} cargados${filaInfo}<br>📋 ${cantidad} disponible(s)`, 'info', 2000);
}

function notificarFilaAgregada(numeroFila) {
    mostrarNotificacion(`➕ Nueva fila agregada: #${numeroFila}<br>📝 Completa los datos del servicio`, 'info', 2000);
}

function notificarFilaEliminada(numeroFila) {
    mostrarNotificacion(`🗑️ Fila #${numeroFila} eliminada<br>✅ Servicio removido del reporte`, 'info', 2000);
}

function notificarAutoGuardado() {
    mostrarNotificacion(`💾 Progreso guardado automáticamente<br>🔒 Tus datos están seguros`, 'guardado', 2000);
}

function notificarBorradorRecuperado(cantidadFilas) {
    mostrarNotificacion(`📂 Borrador recuperado exitosamente<br>📋 ${cantidadFilas} servicio(s) restaurado(s)`, 'exito', 3000);
}

function notificarIncoherencia(idFila, tipoServicio, tieneRepuestos) {
    let mensaje = '';
    if (tipoServicio.includes('CORRECTIVO') && !tieneRepuestos) {
        mensaje = `🤔 Fila #${idFila}: Correctivo SIN repuestos<br>¿Seguro que no se usaron piezas?`;
    } else if (tipoServicio.includes('PREVENTIVO') && tieneRepuestos) {
        mensaje = `🤔 Fila #${idFila}: Preventivo CON repuestos<br>¿Debería ser Correctivo?`;
    }
    if (mensaje) mostrarNotificacion(mensaje, 'advertencia', 4000);
}

function notificarCamposIncompletos(idFila, camposFaltantes) {
    const camposTexto = camposFaltantes.join(', ');
    mostrarNotificacion(`⚠️ Fila #${idFila}: Datos incompletos<br>📝 Faltan: ${camposTexto}`, 'advertencia', 4000);
}

function notificarHoraInvalida(idFila, campo) {
    mostrarNotificacion(`⚠️ Hora inválida en Fila #${idFila}<br>⏰ Use formato 24h (00:00 - 23:59) en ${campo}`, 'error', 4000);
}

function notificarDuracionCalculada(idFila, duracion) {
    mostrarNotificacion(`⏱️ Duración calculada en Fila #${idFila}<br>✅ Tiempo de servicio: ${duracion}`, 'info', 2000);
}

function notificarError(mensaje) {
    mostrarNotificacion(`❌ Error: ${mensaje}`, 'error', 4000);
}

function notificarAdvertencia(mensaje) {
    mostrarNotificacion(`⚠️ Advertencia: ${mensaje}`, 'advertencia', 3500);
}

function notificarGuardadoExitoso(cantidadServicios) {
    mostrarNotificacion(`🎉 ¡Reporte guardado exitosamente!<br>✅ ${cantidadServicios} servicio(s) registrado(s)`, 'exito', 5000);
}

function notificarEnviandoFormulario(cantidadServicios) {
    mostrarNotificacion(`⏳ Guardando reporte...<br>📤 Procesando ${cantidadServicios} servicio(s)`, 'info', 10000);
}

function notificarInventarioCargado(nombreTecnico, cantidadItems) {
    mostrarNotificacion(`📦 Inventario de ${nombreTecnico}<br>✅ ${cantidadItems} repuesto(s) disponible(s)`, 'stock', 2500);
}

function notificarMaquinaAutoSeleccionada(idFila, deviceId) {
    mostrarNotificacion(`🤖 Máquina seleccionada automáticamente<br>📟 Device ID: ${deviceId}`, 'info', 2000);
}


// ==========================================
// NUEVO: MODAL DE CONFIRMACIÓN Y VALIDACIÓN ESTRICTA (Traído de Crear)
// ==========================================

/**
 * Valida de forma ESTRICTA que el campo "¿Qué se realizó?" no esté vacío
 */
function validarCamposEstrictos() {
    // 🔥 Ajuste: Busca en tablaEdicion (Detalle) o contenedorFilas (Crear)
    const filas = document.querySelectorAll('#tablaEdicion tr, #contenedorFilas tr');
    let formularioValido = true;

    filas.forEach((tr, index) => {
        // Evitar procesar filas que no sean de datos (ej: thead)
        if(!tr.id || !tr.id.includes('fila_')) return;

        const campoObs = tr.querySelector('[name*="[obs]"]');
        
        if (campoObs) {
            const valorObs = campoObs.value.trim();

            if (valorObs === '') {
                formularioValido = false;
                campoObs.classList.add('border-red-500', 'bg-red-50');
                
                mostrarNotificacion(
                    `❌ Fila ${index + 1}: El campo "¿Qué se Realizó?" no puede estar vacío.`, 
                    'error', 
                    5000
                );

                setTimeout(() => {
                    campoObs.classList.remove('border-red-500', 'bg-red-50');
                }, 4000);
            }
        }
    });

    return formularioValido;
}

/**
 * Muestra un modal que exige respuesta del usuario
 */
function mostrarModalConfirmacion(mensaje, callbackConfirmar) {
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

    document.getElementById(`btn_cancelar_${id}`).onclick = () => {
        modal.style.opacity = '0';
        setTimeout(() => modal.remove(), 300);
    };

    document.getElementById(`btn_confirmar_${id}`).onclick = () => {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
            callbackConfirmar();
        }, 300);
    };

    modal.style.opacity = '0';
    setTimeout(() => modal.style.opacity = '1', 10);
}

/**
 * Valida coherencia de UNA fila (Correctivo vs Repuestos)
 */
function validarCoherenciaFila(idFila, tipoServicio, tieneRepuestos) {
    let mensaje = '';
    let hayError = false;

    if (tipoServicio.toUpperCase().includes('CORRECTIVO') && !tieneRepuestos) {
        mensaje = `🤔 Fila #${idFila}: Es Mantenimiento CORRECTIVO pero no has agregado repuestos.<br>¿Seguro que no se usaron piezas?`;
        hayError = true;
    } else if (tipoServicio.toUpperCase().includes('PREVENTIVO') && tieneRepuestos) {
        mensaje = `🤔 Fila #${idFila}: Es Mantenimiento PREVENTIVO pero tiene repuestos.<br>¿Debería ser Correctivo?`;
        hayError = true;
    }

    if (mensaje) {
        mostrarNotificacion(mensaje, 'advertencia', 6000);
    }

    return hayError;
}

// ==========================================
// UTILIDADES
// ==========================================

function formatearFecha(fecha) {
    if (!fecha) return '';
    const [y, m, d] = fecha.split('-');
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return `${d} ${meses[parseInt(m)-1]} ${y}`;
}

function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(valor);
}

function configurarNotificaciones(opciones = {}) {
    Object.assign(NotifConfig, opciones);
}

// Exportar
window.DetalleNotificaciones = {
    mostrarNotificacion,
    cerrarNotificacion,
    configurarNotificaciones,
    
    // Originales de Detalle
    notificarFestivo,
    notificarPrecioActualizado,
    notificarDesplazamientoAlto,
    notificarCambioGuardado,
    
    // Traídas de Crear
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
    notificarIncoherencia, // Funciona igual que la de Detalle pero mejorada
    notificarCamposIncompletos,
    notificarHoraInvalida,
    notificarDuracionCalculada,
    notificarError,
    notificarAdvertencia,
    notificarGuardadoExitoso,
    notificarEnviandoFormulario,
    notificarInventarioCargado,
    notificarMaquinaAutoSeleccionada,
    
    // Modal y Validaciones
    mostrarModalConfirmacion,
    validarCoherenciaFila,
    validarCamposEstrictos
};

console.log('🔔 Sistema de notificaciones DETALLE cargado (Fusionado con Crear)');