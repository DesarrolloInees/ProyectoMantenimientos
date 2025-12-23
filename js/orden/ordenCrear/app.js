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
    });
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