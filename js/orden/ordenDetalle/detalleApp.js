// ==========================================
// INICIALIZACIÓN PRINCIPAL - DETALLE SERVICIOS
// v2.3.0 — Select2 en todos los selects de tabla
// ==========================================

const S2_LANG = { noResults: () => "No encontrado" };

// Configuración base para selects dentro de tabla
function s2Config(placeholder = '— Seleccione —', extra = {}) {
    return {
        width: '100%',
        placeholder,
        allowClear: false,
        language: S2_LANG,
        ...extra
    };
}

/**
 * Inicializar Select2 en TODOS los selects de una fila.
 * Se llama al arrancar y también cuando se recarga una fila (ej: tras cargar remisiones).
 * @param {string|null} idFila  Si null → aplica a toda la tabla
 */
function inicializarSelect2Fila(idFila = null) {
    const scope = idFila ? `#fila_${idFila}` : '#tablaEdicion tbody';

    // Cliente
    $(`${scope} .select2-cliente`).each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
        $(this).select2(s2Config('Cliente'));
    });

    // Punto (carga lazy al abrirse)
    $(`${scope} .select2-punto`).each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
        $(this).select2(s2Config('Punto'));
    });

    // Técnico
    $(`${scope} select[id^="sel_tecnico_"]`).each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
        $(this).select2(s2Config('Técnico'));
    });

    // Servicio (mantenimiento)
    $(`${scope} select[id^="sel_servicio_"]`).each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
        $(this).select2(s2Config('Servicio'));
    });

    // Zona / modalidad
    $(`${scope} select[id^="sel_modalidad_"]`).each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
        $(this).select2(s2Config('Zona'));
    });

    // Máquina
    $(`${scope} select[id^="sel_maq_"]`).each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
        $(this).select2(s2Config('Máquina'));
    });

    // Remisión — minimSearchLength=0 para que muestre todo aunque sea corto
    $(`${scope} select[id^="sel_remision_"]`).each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
        $(this).select2(s2Config('— Sin remisión —', { minimumResultsForSearch: 0 }));
    });

    // Estado
    $(`${scope} select[name*="[id_estado]"]`).each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
        $(this).select2(s2Config('Estado', { minimumResultsForSearch: Infinity }));
    });

    // Calificación
    $(`${scope} select[name*="[id_calif]"]`).each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
        $(this).select2(s2Config('Calificación', { minimumResultsForSearch: Infinity }));
    });
}

function configurarAperturaInteligentePuntos() {
    // Cuando el usuario abre el select de punto, si aún no cargó sus opciones,
    // las carga primero y luego abre el dropdown
    $(document).on('select2:opening', 'select[id^="sel_punto_"]', function (e) {
        const select = $(this);
        const idFila = select.attr('id').replace('sel_punto_', '');
        if (select.attr('data-loaded') === 'true') return;

        e.preventDefault();
        const idCliente = select.closest('tr').find('select[name*="[id_cliente]"]').val();
        if (idCliente) {
            window.DetalleAjax.cargarPuntos(idFila, idCliente, true, function () {
                select.select2('open');
            });
        } else {
            alert("⚠️ Seleccione primero un cliente.");
        }
    });

    // Cuando Select2 cambia el técnico, dispara el onchange nativo
    // (Select2 reemplaza el select nativo, el onchange del HTML puede no dispararse)
    $(document).on('select2:select', 'select[id^="sel_tecnico_"]', function () {
        const idFila = this.id.replace('sel_tecnico_', '');
        const idTecnico = this.value;
        if (typeof calcularDesplazamientos === 'function') calcularDesplazamientos();
        if (typeof cargarRemisiones === 'function') cargarRemisiones(idFila, idTecnico);
    });

    // Cuando cambia servicio, zona o máquina → actualizar tarifa
    $(document).on('select2:select', 'select[id^="sel_servicio_"], select[id^="sel_modalidad_"], select[id^="sel_maq_"]', function () {
        const idFila = this.id.replace(/sel_(servicio|modalidad|maq)_/, '');
        if (this.id.startsWith('sel_maq_') && typeof actualizarTipoMaquina === 'function') {
            actualizarTipoMaquina(idFila);
        }
        if (typeof actualizarTarifa === 'function') actualizarTarifa(idFila);
    });

    // Cuando cambia punto → cargar máquinas
    $(document).on('select2:select', 'select[id^="sel_punto_"]', function () {
        const idFila = this.id.replace('sel_punto_', '');
        if (typeof cargarMaquinas === 'function') cargarMaquinas(idFila, this.value);
    });

    // Cuando cambia cliente → cargar puntos
    $(document).on('select2:select', '.select2-cliente', function () {
        const idFila = $(this).closest('tr').attr('id')?.replace('fila_', '');
        if (idFila && typeof cargarPuntos === 'function') cargarPuntos(idFila, this.value);
    });
}

function configurarSelect2Modal() {
    if ($('#select_repuesto_modal').data('select2')) {
        $('#select_repuesto_modal').select2('destroy');
    }
    $('#select_repuesto_modal').select2({
        width: '100%',
        dropdownParent: $('#modalRepuestos'),
        placeholder: '- Buscar Repuesto -',
        allowClear: true,
        language: S2_LANG
    });
    const selectRep = document.getElementById('select_repuesto_modal');
    if (selectRep && window.DetalleConfig?.catalogoRepuestos) {
        let html = '<option value="">- Buscar Repuesto -</option>';
        window.DetalleConfig.catalogoRepuestos.forEach(r => {
            html += `<option value="${r.id_repuesto}">${r.nombre_repuesto}</option>`;
        });
        selectRep.innerHTML = html;
    }
    $('head').append('<style>.select2-container--open { z-index: 99999999 !important; }</style>');
}

/*
 * Cargar remisiones iniciales para todas las filas.
 * Tras el AJAX, re-inicializa Select2 en el select de remisión de esa fila
 * para que el filtrado por escritura funcione con las nuevas opciones.
 */
function cargarRemisionesIniciales() {
    const filas = document.querySelectorAll('.fila-servicio');
    filas.forEach((fila, indice) => {
        const idFila = fila.id.replace('fila_', '');
        const selTecnico = document.getElementById(`sel_tecnico_${idFila}`);
        const selRemision = document.getElementById(`sel_remision_${idFila}`);
        if (!selTecnico || !selRemision) return;

        const idTecnico = selTecnico.value;
        if (!idTecnico || idTecnico == 0) return;

        if (!selRemision.dataset.remisionOriginal) {
            selRemision.dataset.remisionOriginal = selRemision.value || '';
        }

        setTimeout(() => {
            window.cargarRemisiones(idFila, idTecnico);
        }, indice * 150);
    });
}

function inicializarAplicacionDetalle() {
    console.log('🚀 Iniciando Sistema de Detalle de Servicios v2.3...');

    // 1. Select2 en todos los selects + eventos delegados
    inicializarSelect2Fila(null);       // toda la tabla
    configurarAperturaInteligentePuntos();
    configurarSelect2Modal();

    // 2. Detectores de fecha
    if (window.DetalleFechaUtils) window.DetalleFechaUtils.configurarDetectorFechas();

    // 3. Cálculos iniciales
    if (window.DetalleDesplazamientos) window.DetalleDesplazamientos.calcularDesplazamientos();
    if (window.DetallePaginacion) window.DetallePaginacion.iniciarPaginacion();

    // 4. Remisiones: actual (USADA) + disponibles para corregir
    cargarRemisionesIniciales();

    // 5. Bloqueo de guardado si hay errores de tarifa
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            const filasConError = document.querySelectorAll('.error-tarifa-faltante');
            if (filasConError.length > 0) {
                e.preventDefault();
                filasConError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                filasConError.forEach(tr => {
                    const input = tr.querySelector('input[id^="input_valor_"]');
                    if (input) input.classList.add('animate-pulse');
                });
                alert(`⛔ NO SE PUEDE GUARDAR\n\nHay ${filasConError.length} servicio(s) sin tarifa configurada.\n\nCorrija o contacte al administrador.`);
                return false;
            }
        });
    }

    console.log('✅ Sistema inicializado v2.3');
}

function validarDependenciasDetalle() {
    const deps = {
        jQuery: typeof jQuery !== 'undefined',
        Select2: typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined',
        SheetJS: typeof XLSX !== 'undefined'
    };
    const faltantes = Object.entries(deps).filter(([, v]) => !v).map(([k]) => k);
    if (faltantes.length > 0) {
        console.error('❌ Faltan dependencias:', faltantes);
        alert(`Error: No se cargaron las librerías: ${faltantes.join(', ')}`);
        return false;
    }
    return true;
}

// BOOT
$(document).ready(function () {
    if (!validarDependenciasDetalle()) return;
    inicializarAplicacionDetalle();
});

window.DetalleApp = {
    version: '2.3.0',
    init: inicializarAplicacionDetalle,
    recargar: inicializarAplicacionDetalle,
    // Exponer para que detalleAjax.js pueda reinit Select2 tras reconstruir un select
    reinitSelect2Fila: inicializarSelect2Fila
};