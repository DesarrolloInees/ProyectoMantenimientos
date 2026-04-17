// ==========================================
// INICIALIZACIÓN PRINCIPAL DE LA APLICACIÓN
// ==========================================

/**
 * Inicializar todos los módulos de la aplicación
 */
function inicializarAplicacion() {
    console.log('🚀 Iniciando aplicación de órdenes de servicio...');

    // 1. Inicializar datos globales desde PHP
    window.AppConfig.inicializarDatosGlobales({
        clientes: listaClientes,
        mantos: listaMantos,
        tecnicos: listaTecnicos,
        estados: listaEstados,
        califs: listaCalif,
        repuestos: listaRepuestosBD,
        festivos: FESTIVOS_DB
    });

    // 2. Configurar Select2 en el modal de repuestos
    window.RepuestosManager.inicializarSelect2Modal();

    // 3. Inicializar gestión de tiempos y fechas
    window.TimeManager.inicializar();

    // 4. Configurar auto-guardado
    window.StorageManager.configurarAutoGuardado();

    // 5. Verificar y restaurar borrador (con delay para asegurar carga)
    setTimeout(() => {
        window.StorageManager.verificarYRestaurar();
    }, 500);

    // 6. Configurar validación de formulario
    const form = document.getElementById('formServicios');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!window.UIUtils.validarFormulario()) {
                e.preventDefault();
                return false;
            }
        });
    }

    console.log('✅ Aplicación inicializada correctamente');
}

/**
 * Validar dependencias requeridas
 */
function validarDependencias() {
    const dependencias = {
        jQuery: typeof jQuery !== 'undefined',
        Select2: typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined',
        Flatpickr: typeof flatpickr !== 'undefined',
        jQueryMask: typeof jQuery !== 'undefined' && typeof jQuery.fn.mask !== 'undefined'
    };

    let faltantes = [];
    for (const [lib, cargada] of Object.entries(dependencias)) {
        if (!cargada) faltantes.push(lib);
    }

    if (faltantes.length > 0) {
        console.error('❌ Faltan dependencias:', faltantes);
        alert(`Error: No se cargaron las librerías: ${faltantes.join(', ')}`);
        return false;
    }

    console.log('✅ Todas las dependencias están cargadas');
    return true;
}

/**
 * Manejo de errores globales
 */
function configurarManejadorErrores() {
    window.addEventListener('error', function (e) {
        console.error('Error global capturado:', e.error);

        // No mostrar errores menores al usuario
        if (e.error && e.error.message &&
            !e.error.message.includes('ResizeObserver') &&
            !e.error.message.includes('Script error')) {

            window.UIUtils.mostrarNotificacion(
                'Ocurrió un error. Por favor, recargue la página.',
                'error'
            );
        }
    });

    window.addEventListener('unhandledrejection', function (e) {
        console.error('Promesa rechazada:', e.reason);
    });
}

/**
 * Configurar atajos de teclado
 */
function configurarAtajosTeclado() {
    document.addEventListener('keydown', function (e) {
        // Ctrl/Cmd + K = Agregar fila
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            window.FilaManager.agregarFila();
            window.UIUtils.mostrarNotificacion('Nueva fila agregada', 'success');
        }

        // Ctrl/Cmd + S = Guardar (prevenir guardar navegador)
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            window.StorageManager.guardarProgresoLocal();
            window.UIUtils.mostrarNotificacion('Progreso guardado', 'success');
        }

        const flechas = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
        if (flechas.includes(e.key)) {
            // Solo actuar si estamos dentro de la tabla de servicios
            if ($(e.target).closest('#contenedorFilas').length > 0) {
                navegarTabla(e);
            }
        }
    });
}


/**
 * Lógica de navegación tipo Excel (Versión Definitiva por Índice)
 */
function navegarTabla(e) {
    const $actual = $(e.target);
    const $filaActual = $actual.closest('tr');
    
    // Selector inteligente: 
    // - Ignora los hidden
    // - Ignora los readonly (para no atascarse en campos bloqueados)
    // - Ignora los select originales que Select2 oculta (.select2-hidden-accessible)
    const selectorDestino = 'input:not([type="hidden"]):not([readonly]), select:not(.select2-hidden-accessible), .select2-selection';
    
    // Obtenemos TODOS los elementos navegables de la fila actual, en perfecto orden de izquierda a derecha
    const $elementosFila = $filaActual.find(selectorDestino);
    
    // Buscamos el elemento exacto en la lista (Si es un Select2, agarramos el span correcto)
    const $elementoReferencia = $actual.closest(selectorDestino);
    const indexActual = $elementosFila.index($elementoReferencia);

    // Detectar si es un campo de opciones
    const esCampoOpciones = $elementoReferencia.is('select') || $elementoReferencia.hasClass('select2-selection');

    switch (e.key) {
        case 'ArrowDown':
        case 'ArrowUp':
            e.preventDefault();
            // Para arriba/abajo, simplemente buscamos la otra fila y enfocamos la misma posición (índice)
            const $filaDestino = e.key === 'ArrowDown' ? $filaActual.next() : $filaActual.prev();
            if ($filaDestino.length) {
                $filaDestino.find(selectorDestino).eq(indexActual).focus();
            }
            break;

        case 'ArrowRight':
            // Saltar si es Select/Select2, o si es input y el cursor está al final del texto
            if (esCampoOpciones || ($actual.is('input') && e.target.selectionEnd === $actual.val().length)) {
                // Verificamos que no estemos en el último elemento
                if (indexActual >= 0 && indexActual < $elementosFila.length - 1) {
                    e.preventDefault();
                    $elementosFila.eq(indexActual + 1).focus();
                }
            }
            break;

        case 'ArrowLeft':
            // Saltar si es Select/Select2, o si es input y el cursor está al inicio del texto
            if (esCampoOpciones || ($actual.is('input') && e.target.selectionStart === 0)) {
                // Verificamos que no estemos en el primer elemento
                if (indexActual > 0) {
                    e.preventDefault();
                    // Retrocedemos exactamente 1 posición (ya no te enviará a la primera columna)
                    $elementosFila.eq(indexActual - 1).focus();
                }
            }
            break;
    }
}

/**
 * Mostrar información de versión y ayuda
 */
function mostrarInfoInicial() {
    console.log('%c🔧 Sistema de Órdenes de Servicio', 'color: #3b82f6; font-size: 16px; font-weight: bold;');
    console.log('%cVersión: 2.0.0 - Modular', 'color: #10b981;');
    console.log('%c Atajos de teclado:', 'color: #6b7280; font-weight: bold;');
    console.log('  • Ctrl/Cmd + K: Agregar nueva fila');
    console.log('  • Ctrl/Cmd + S: Guardar progreso');
    console.log('%c📚 Características:', 'color: #6b7280; font-weight: bold;');
    console.log('  • Auto-guardado cada 4 segundos');
    console.log('  • Recuperación automática de borradores');
    console.log('  • Validación inteligente de repuestos vs tipo de servicio');
    console.log('  • Control de inventario en tiempo real');
    console.log('  • Cálculo automático de precios por año de vigencia');
}

// ==========================================
// INICIALIZACIÓN AL CARGAR EL DOM
// ==========================================

document.addEventListener("DOMContentLoaded", function () {
    console.log('📄 DOM cargado');

    // 1. Validar dependencias
    if (!validarDependencias()) {
        return;
    }

    // 2. Configurar manejadores de errores
    configurarManejadorErrores();

    // 3. Configurar atajos de teclado
    configurarAtajosTeclado();

    // 4. Mostrar información inicial
    mostrarInfoInicial();

    // 5. Inicializar aplicación
    inicializarAplicacion();

    // 6. Configurar validador de remisiones
    window.ValidadorRemisiones.configurarValidacionRemisiones();

    // 7. Validar remisiones antes de enviar formulario
    const form = document.getElementById('formServicios');
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Validar remisiones
            const remisionesValidas = await window.ValidadorRemisiones.validarTodasRemisionesAnteDeEnviar();

            if (!remisionesValidas) {
                return false;
            }

            // Notificar envío
            const cantidadFilas = document.querySelectorAll('#contenedorFilas tr').length;
            window.CrearNotificaciones.notificarEnviandoFormulario(cantidadFilas);

            // Enviar formulario
            this.submit();
        });
    }
});

$(document).ready(function () {
    // 1. DESVINCULAMOS CUALQUIER EVENTO ANTERIOR PARA EVITAR DUPLICADOS
    $('#btnGuardarFijo').off('click');
    $('#formServicios').off('submit'); // Nos aseguramos que el submit no haga nada automático

    // 2. ESCUCHAMOS EL CLICK DEL BOTÓN (No el submit del form)
    $('#btnGuardarFijo').on('click', function (e) {
        e.preventDefault();
        console.log('👆 Botón presionado. Iniciando validación...');
        procesarGuardado();
    });
});

async function procesarGuardado() {
    console.log('⚙️ Ejecutando procesarGuardado()...');

    const filas = document.querySelectorAll('#contenedorFilas tr');
    let hayErroresBloqueantes = false;
    let hayAdvertenciasLogicas = false;

    // --- 1. VALIDAR SI HAY FILAS ---
    if (filas.length === 0) {
        window.CrearNotificaciones.mostrarNotificacion('⚠️ No hay servicios para guardar.', 'error');
        return;
    }

    // --- 2. VALIDAR REMISIONES ---
    console.log('⏳ Validando remisiones...');
    const remisionesValidas = await window.ValidadorRemisiones.validarTodasRemisionesAnteDeEnviar();
    if (!remisionesValidas) {
        console.log('❌ Error en remisiones. Cancelando.');
        return;
    }

    // --- 3. RECORRIDO DE FILAS (Validaciones unificadas) ---
    filas.forEach((fila, index) => {
        const idFila = fila.getAttribute('data-id');
        console.log(`🔍 Analizando fila ${index + 1} (ID: ${idFila})`);

        // A. Obtener valores
        const tecnico = fila.querySelector(`select[name^="filas"][name$="[id_tecnico]"]`)?.value;
        const cliente = fila.querySelector(`select[name^="filas"][name$="[id_cliente]"]`)?.value;
        const punto = fila.querySelector(`select[name^="filas"][name$="[id_punto]"]`)?.value;
        const maquina = fila.querySelector(`select[name^="filas"][name$="[id_maquina]"]`)?.value;
        const tipoServicioElem = fila.querySelector(`select[name^="filas"][name$="[tipo_servicio]"]`);
        const tipoServicioVal = tipoServicioElem?.value;

        // B. Validar vacíos (EXISTENTE)
        if (!tecnico || !cliente || !punto || !maquina || !tipoServicioVal) {
            hayErroresBloqueantes = true;
            fila.classList.add('bg-red-200'); // Fila roja
            setTimeout(() => fila.classList.remove('bg-red-200'), 4000); // Efecto visual
        }

        // =====================================================================
        // 🔥 C. NUEVA VALIDACIÓN: TARIFA FALTANTE (DENTRO DEL BUCLE)
        // =====================================================================
        // Si ajaxUtils.js marcó la fila con 'error-tarifa-faltante', la bloqueamos aquí
        if (fila.classList.contains('error-tarifa-faltante')) {
            hayErroresBloqueantes = true;
            fila.classList.add('bg-red-200'); // También la ponemos roja

            // Opcional: Hacer que parpadee el input del valor para que se vea cuál es
            const inputValor = fila.querySelector(`input[name^="filas"][name$="[valor]"]`);
            if (inputValor) inputValor.classList.add('animate-pulse');

            console.warn(`⚠️ Fila ${index + 1}: Sin tarifa configurada.`);
        }
        // =====================================================================

        // D. Lógica CORRECTIVO SIN REPUESTOS (ADVERTENCIA)
        if (tipoServicioElem) {
            const textoServicio = tipoServicioElem.options[tipoServicioElem.selectedIndex].text.toUpperCase().trim();
            const inputRepuestos = fila.querySelector(`.input-json-repuestos`) || fila.querySelector(`input[id^="json_rep_"]`);
            const jsonRepuestos = inputRepuestos ? inputRepuestos.value : '[]';

            let cantidadRepuestos = 0;
            try {
                const arr = JSON.parse(jsonRepuestos || '[]');
                cantidadRepuestos = arr.length;
            } catch (e) { console.error('Error parseando JSON repuestos', e); }

            if (textoServicio.includes("CORRECTIVO") && cantidadRepuestos === 0) {
                console.warn('⚠️ DETECTADO: Correctivo sin repuestos');
                hayAdvertenciasLogicas = true;
                fila.classList.add('bg-yellow-200'); // Advertencia amarilla
            }
        }
    });

    // --- 4. SI HAY ERRORES BLOQUEANTES (Vacíos O Sin Tarifa) ---
    if (hayErroresBloqueantes) {
        // Mensaje genérico para ambos casos
        window.CrearNotificaciones.mostrarNotificacion('⛔ No se puede guardar: Hay datos incompletos o servicios SIN TARIFA (marcados en rojo).', 'error');
        return; // SE DETIENE AQUÍ
    }

    // --- 5. PREPARAR EL MODAL (Si pasó las validaciones rojas) ---
    console.log(`📊 Resultado análisis: Advertencias Logicas = ${hayAdvertenciasLogicas}`);

    let mensajeModal = "¿Estás seguro de que deseas guardar y enviar este reporte?";
    let tipoIcono = "question";

    if (hayAdvertenciasLogicas) {
        mensajeModal = "⚠️ <b>¡ADVERTENCIA!</b><br><br>Hay mantenimientos <b>CORRECTIVOS que NO tienen repuestos</b>.<br>Esto es inusual.<br><br>¿Deseas guardar de todas formas?";
        tipoIcono = "warning";
    }

    // --- 6. MOSTRAR EL MODAL ---
    if (typeof window.CrearNotificaciones.mostrarModalConfirmacion === 'function') {
        window.CrearNotificaciones.mostrarModalConfirmacion(mensajeModal, function () {
            if (window.StorageManager && window.StorageManager.limpiarStorageParaEnvio) {
                window.StorageManager.limpiarStorageParaEnvio();
            }
            
            // 🔥 AQUÍ CONECTAMOS EL JSON EN VEZ DEL SUBMIT TRADICIONAL 🔥
            if (typeof ejecutarGuardadoJSONCrear === 'function') {
                ejecutarGuardadoJSONCrear();
            } else {
                console.error("No se encontró la función ejecutarGuardadoJSONCrear");
            }

        }, tipoIcono);
    } else {
        if (confirm(mensajeModal.replace(/<br>/g, '\n').replace(/<b>/g, '').replace(/<\/b>/g, ''))) {
            // 🔥 Y AQUÍ TAMBIÉN 🔥
            if (typeof ejecutarGuardadoJSONCrear === 'function') {
                ejecutarGuardadoJSONCrear();
            }
        }
    }
}
// ==========================================
// EXPORTAR PARA DEBUG EN CONSOLA
// ==========================================

window.App = {
    version: '2.0.0',
    recargar: inicializarAplicacion,
    limpiarBorrador: () => {
        localStorage.removeItem(window.AppConfig.CLAVE_GUARDADO);
        console.log('✅ Borrador eliminado');
        window.UIUtils.mostrarNotificacion('Borrador eliminado', 'success');
    },
    mostrarEstado: () => {
        console.log('📊 Estado actual:');
        console.log('  Filas activas:', document.querySelectorAll('#contenedorFilas tr').length);
        console.log('  Repuestos en memoria:', Object.keys(window.AppConfig.almacenRepuestos).length);
        console.log('  Auto-guardado activo:', !window.AppConfig.enviandoFormulario);
    }
};

console.log('%c💡 Tip: Escribe "App.mostrarEstado()" en la consola para ver el estado actual', 'color: #8b5cf6;');