// ==========================================
// SISTEMA DE NOTIFICACIONES INTELIGENTES
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
 * Tipos de notificaciones con sus estilos
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
    }
};

/**
 * Mostrar notificación
 */
function mostrarNotificacion(mensaje, tipo = 'info', duracion = null) {
    // Limitar notificaciones simultáneas
    if (notificacionesActivas.length >= NotifConfig.maxNotificaciones) {
        eliminarNotificacionMasAntigua();
    }

    const config = TiposNotificacion[tipo] || TiposNotificacion.info;
    const id = `notif_${Date.now()}`;
    duracion = duracion || NotifConfig.duracion;

    // Crear elemento de notificación
    const notif = document.createElement('div');
    notif.id = id;
    notif.className = `fixed z-[9999] ${config.color} text-white px-6 py-4 rounded-lg shadow-2xl 
                       border-l-4 ${config.borderColor} transform transition-all duration-300 
                       flex items-center gap-3 min-w-[300px] max-w-[400px]`;
    
    // Posición según configuración
    const posiciones = {
        'top-right': 'top-4 right-4',
        'top-left': 'top-4 left-4',
        'bottom-right': 'bottom-4 right-4',
        'bottom-left': 'bottom-4 left-4'
    };
    notif.className += ` ${posiciones[NotifConfig.posicion]}`;

    // Calcular posición vertical si hay otras notificaciones
    const offset = notificacionesActivas.length * 90; // 90px de separación
    if (NotifConfig.posicion.includes('top')) {
        notif.style.top = `${16 + offset}px`;
    } else {
        notif.style.bottom = `${16 + offset}px`;
    }

    // Contenido HTML
    notif.innerHTML = `
        <div class="flex-shrink-0">
            <i class="fas ${config.icono} text-2xl"></i>
        </div>
        <div class="flex-1">
            <p class="font-semibold text-sm">${mensaje}</p>
        </div>
        <button onclick="window.DetalleNotificaciones.cerrarNotificacion('${id}')" 
                class="flex-shrink-0 hover:bg-white hover:bg-opacity-20 rounded p-1 transition">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Animación de entrada
    notif.style.opacity = '0';
    notif.style.transform = 'translateX(100px)';

    document.body.appendChild(notif);
    notificacionesActivas.push(id);

    // Reproducir sonido
    if (NotifConfig.sonido) {
        reproducirSonido(config.sonido);
    }

    // Animación de entrada
    setTimeout(() => {
        notif.style.opacity = '1';
        notif.style.transform = 'translateX(0)';
    }, 10);

    // Auto-cerrar
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

    // Animación de salida
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
 * Reposicionar notificaciones después de cerrar una
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

        // Frecuencias según tipo
        const frecuencias = {
            success: [523.25, 659.25], // Do-Mi
            info: [440], // La
            warning: [392, 440], // Sol-La
            error: [329.63, 293.66] // Mi-Re (descendente)
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
// NOTIFICACIONES ESPECÍFICAS DEL SISTEMA
// ==========================================

/**
 * Notificación de festivo detectado
 */
function notificarFestivo(fecha, esDomingo = false) {
    const tipo = esDomingo ? 'Domingo' : 'Festivo';
    const mensaje = `📅 ${tipo} detectado: ${formatearFecha(fecha)}\n✨ Modalidad cambiada a INTERURBANO automáticamente`;
    mostrarNotificacion(mensaje, 'festivo', 5000);
}

/**
 * Notificación de precio actualizado
 */
function notificarPrecioActualizado(idFila, precioAnterior, precioNuevo) {
    const diff = precioNuevo - precioAnterior;
    const simbolo = diff > 0 ? '↗️' : diff < 0 ? '↘️' : '➡️';
    const color = diff > 0 ? 'text-green-300' : diff < 0 ? 'text-red-300' : 'text-gray-300';
    
    const mensaje = `💰 Precio actualizado en Fila ${idFila}\n${simbolo} ${formatearMoneda(precioAnterior)} → ${formatearMoneda(precioNuevo)}`;
    mostrarNotificacion(mensaje, 'precio', 3000);
}

/**
 * Notificación de stock insuficiente
 */
function notificarStockInsuficiente(repuesto, disponible, solicitado) {
    const mensaje = `⚠️ Stock insuficiente: ${repuesto}\n📦 Disponible: ${disponible} | Solicitado: ${solicitado}`;
    mostrarNotificacion(mensaje, 'stock', 4000);
}

/**
 * Notificación de repuestos agregados
 */
function notificarRepuestosAgregados(cantidad, total) {
    const mensaje = `✅ ${cantidad} repuesto(s) agregado(s)\n📦 Total en lista: ${total} items`;
    mostrarNotificacion(mensaje, 'exito', 2500);
}

/**
 * Notificación de datos cargados
 */
function notificarDatosCargados(tipo, cantidad) {
    const mensaje = `🔄 ${tipo} cargados: ${cantidad} disponibles`;
    mostrarNotificacion(mensaje, 'info', 2000);
}

/**
 * Notificación de cambio guardado
 */
function notificarCambioGuardado() {
    const mensaje = `✅ Cambios guardados correctamente`;
    mostrarNotificacion(mensaje, 'exito', 2000);
}

/**
 * Notificación de error
 */
function notificarError(mensaje) {
    mostrarNotificacion(`❌ Error: ${mensaje}`, 'error', 4000);
}

/**
 * Notificación de advertencia
 */
function notificarAdvertencia(mensaje) {
    mostrarNotificacion(`⚠️ Advertencia: ${mensaje}`, 'advertencia', 3500);
}

/**
 * Notificación de desplazamiento alto
 */
function notificarDesplazamientoAlto(idFila, tiempo) {
    const mensaje = `⏰ Desplazamiento alto en Fila ${idFila}\n🚗 Tiempo: ${tiempo}`;
    mostrarNotificacion(mensaje, 'advertencia', 3000);
}

/**
 * Notificación de coherencia servicio-repuestos
 */
function notificarIncoherencia(idFila, tipoServicio, tieneRepuestos) {
    let mensaje = '';
    
    if (tipoServicio.includes('CORRECTIVO') && !tieneRepuestos) {
        mensaje = `🤔 Fila ${idFila}: Correctivo SIN repuestos\n¿Seguro que no se usaron piezas?`;
    } else if (tipoServicio.includes('PREVENTIVO') && tieneRepuestos) {
        mensaje = `🤔 Fila ${idFila}: Preventivo CON repuestos\n¿Debería ser Correctivo?`;
    }
    
    if (mensaje) {
        mostrarNotificacion(mensaje, 'advertencia', 4000);
    }
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
    return `${d} ${meses[parseInt(m)-1]} ${y}`;
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
 * Configurar sistema de notificaciones
 */
function configurarNotificaciones(opciones = {}) {
    Object.assign(NotifConfig, opciones);
}

// Exportar
window.DetalleNotificaciones = {
    mostrarNotificacion,
    cerrarNotificacion,
    configurarNotificaciones,
    
    // Notificaciones específicas
    notificarFestivo,
    notificarPrecioActualizado,
    notificarStockInsuficiente,
    notificarRepuestosAgregados,
    notificarDatosCargados,
    notificarCambioGuardado,
    notificarError,
    notificarAdvertencia,
    notificarDesplazamientoAlto,
    notificarIncoherencia
};

console.log('🔔 Sistema de notificaciones cargado');