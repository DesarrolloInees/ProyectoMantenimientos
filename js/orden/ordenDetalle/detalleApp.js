// ==========================================
// INICIALIZACIÓN PRINCIPAL - DETALLE SERVICIOS
// ==========================================

/**
 * Configurar Select2 para tabla
 */
function configurarSelect2Tabla() {
    // Clientes
    $('.select2-cliente').select2({
        width: '100%',
        language: { noResults: () => "No encontrado" }
    });

    // Puntos
    $('.select2-punto').select2({
        width: '100%',
        language: { noResults: () => "No encontrado" }
    });
}

/**
 * Configurar apertura inteligente de puntos
 */
function configurarAperturaInteligentePuntos() {
    $(document).on('select2:opening', '.select2-punto', function(e) {
        let select = $(this);
        let idFila = select.attr('id').replace('sel_punto_', '');

        if (select.attr('data-loaded') === 'true') {
            return;
        }

        e.preventDefault();

        let filaTR = select.closest('tr');
        let selectCliente = filaTR.find('.select2-cliente');
        let idCliente = selectCliente.val();

        if (idCliente) {
            window.DetalleAjax.cargarPuntos(idFila, idCliente, true, function() {
                select.select2('open');
            });
        } else {
            alert("⚠️ Por favor seleccione primero un cliente.");
        }
    });
}

/**
 * Configurar Select2 del modal
 */
function configurarSelect2Modal() {
    if ($('#select_repuesto_modal').data('select2')) {
        $('#select_repuesto_modal').select2('destroy');
    }

    $('#select_repuesto_modal').select2({
        width: '100%',
        dropdownParent: $('#modalRepuestos'),
        placeholder: "- Buscar Repuesto -",
        allowClear: true,
        language: { noResults: () => "No se encontró el repuesto" }
    });

    // Llenar opciones
    const selectRep = document.getElementById('select_repuesto_modal');
    if (selectRep) {
        let html = '<option value="">- Buscar Repuesto -</option>';
        window.DetalleConfig.catalogoRepuestos.forEach(r => {
            html += `<option value="${r.id_repuesto}">${r.nombre_repuesto}</option>`;
        });
        selectRep.innerHTML = html;
    }

    // Fix z-index
    $('head').append('<style>.select2-container--open { z-index: 99999999 !important; }</style>');
}

/**
 * Inicializar todos los módulos
 */
function inicializarAplicacionDetalle() {
    console.log('🚀 Iniciando Sistema de Detalle de Servicios...');

    // 1. Configurar Select2
    configurarSelect2Tabla();
    configurarAperturaInteligentePuntos();
    configurarSelect2Modal();

    // 2. Configurar detectores
    window.DetalleFechaUtils.configurarDetectorFechas();

    // 3. Ejecutar cálculos iniciales
    window.DetalleDesplazamientos.calcularDesplazamientos();
    window.DetallePaginacion.iniciarPaginacion();

    // =========================================================
    // 🔥 4. NUEVO: BLOQUEO DE GUARDADO SI HAY ERRORES
    // =========================================================
    const form = document.querySelector('form'); // O usa el ID específico de tu form si lo tienes
    if (form) {
        form.addEventListener('submit', function(e) {
            
            // Buscar filas marcadas con error por el AJAX
            const filasConError = document.querySelectorAll('.error-tarifa-faltante');

            if (filasConError.length > 0) {
                e.preventDefault(); // 🛑 DETENER ENVÍO
                
                // Scroll a la primera fila con error
                filasConError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Efecto visual
                filasConError.forEach(tr => {
                    const input = tr.querySelector('input[id^="input_valor_"]');
                    if(input) input.classList.add('animate-pulse');
                });

                alert(`⛔ NO SE PUEDE GUARDAR\n\nHay ${filasConError.length} servicio(s) marcados en ROJO porque NO tienen tarifa configurada.\n\nPor favor corrija el tipo de servicio o contacte al administrador.`);
                return false;
            }
        });
    }
    // =========================================================

    console.log('✅ Sistema de Detalle inicializado correctamente');
}

/**
 * Validar dependencias
 */
function validarDependenciasDetalle() {
    const dependencias = {
        jQuery: typeof jQuery !== 'undefined',
        Select2: typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined',
        SheetJS: typeof XLSX !== 'undefined'
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
 * Mostrar información del sistema
 */
function mostrarInfoDetalle() {
    console.log('%c🛠️ Sistema de Edición de Servicios', 'color: #3b82f6; font-size: 16px; font-weight: bold;');
    console.log('%cVersión: 2.0.0 - Modular', 'color: #10b981;');
    console.log('%c📚 Características:', 'color: #6b7280; font-weight: bold;');
    console.log('  • Paginación automática (6 filas por página)');
    console.log('  • Cálculo de desplazamientos en tiempo real');
    console.log('  • Detección automática de festivos');
    console.log('  • Control de inventario por técnico');
    console.log('  • Exportación Excel por delegación');
    console.log('  • Gestión de novedades');
}

// ==========================================
// INICIALIZACIÓN AL CARGAR EL DOM
// ==========================================

$(document).ready(function() {
    console.log('📄 DOM cargado');

    // 1. Validar dependencias
    if (!validarDependenciasDetalle()) {
        return;
    }

    // 2. Mostrar información
    mostrarInfoDetalle();

    // 3. Inicializar aplicación
    inicializarAplicacionDetalle();
});

// ==========================================
// EXPORTAR PARA DEBUG
// ==========================================

window.DetalleApp = {
    version: '2.0.0',
    recargar: inicializarAplicacionDetalle,
    init: inicializarAplicacionDetalle, // AQUI estaba el error, antes no tenías 'init'
    recargar: inicializarAplicacionDetalle,
    mostrarEstado: () => {
        console.log('📊 Estado actual:');
        console.log('  Página actual:', window.DetalleConfig.paginaActual);
        console.log('  Total filas:', window.DetalleConfig.totalFilas);
        console.log('  Total páginas:', window.DetalleConfig.totalPaginas);
        console.log('  Repuestos temporales:', window.DetalleConfig.repuestosTemporales.length);
    }
};

console.log('%c💡 Tip: Escribe "DetalleApp.mostrarEstado()" en la consola para ver el estado actual', 'color: #8b5cf6;');