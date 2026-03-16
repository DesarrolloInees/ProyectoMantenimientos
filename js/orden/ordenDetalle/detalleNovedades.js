// ==========================================
// GESTIÓN DE NOVEDADES (MÚLTIPLES CON SELECT2)
// ==========================================

function inicializarNovedades() {
    const select = document.getElementById('nov_tipo');
    const tipos = window.DetalleConfig.listaNovedades || [];
    
    // 1. Limpiamos y cargamos opciones (Ya no necesitamos el option "-- Seleccione --")
    select.innerHTML = ''; 
    tipos.forEach(t => {
        select.innerHTML += `<option value="${t.id_tipo_novedad}">${t.nombre_novedad}</option>`;
    });

    // 2. Inicializamos Select2
    // Asegúrate de que jQuery y Select2 estén cargados antes de ejecutar esto
    if ($.fn.select2) {
        $('#nov_tipo').select2({
            placeholder: "Buscar y seleccionar novedades...",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#modalNovedades') // Vital para que no quede detrás del modal
        });
    } else {
        console.warn("Select2 no está cargado. Se usará un select múltiple nativo.");
    }
}

function abrirModalNovedad(idOrden) {
    document.getElementById('nov_id_orden').value = idOrden;
    
    // Obtenemos los IDs guardados en el hidden (suponiendo que vienen separados por coma, ej: "1,4,5")
    const idsTiposActuales = document.getElementById(`hdn-tipo-${idOrden}`).value;
    
    // Convertimos ese string a un arreglo para que Select2 lo entienda
    let arrIds = [];
    if (idsTiposActuales && idsTiposActuales.trim() !== "") {
        arrIds = idsTiposActuales.split(',');
    }

    // Le pasamos el arreglo a Select2 y forzamos la actualización visual con .trigger('change')
    $('#nov_tipo').val(arrIds).trigger('change');
    
    // Mostramos el modal
    document.getElementById('modalNovedades').classList.remove('hidden');
    document.getElementById('modalNovedades').classList.add('flex');
}

function cerrarModalNovedad() {
    document.getElementById('modalNovedades').classList.add('hidden');
    document.getElementById('modalNovedades').classList.remove('flex');
    
    // Limpiamos el Select2 al cerrar
    $('#nov_tipo').val(null).trigger('change');
}

function guardarNovedad() {
    const idOrden = document.getElementById('nov_id_orden').value;
    
    // Como es múltiple, .val() de jQuery nos devuelve un arreglo directamente: ["1", "3"]
    const arrayNovedades = $('#nov_tipo').val();

    if (!arrayNovedades || arrayNovedades.length === 0) {
        alert("Debes seleccionar al menos un tipo de novedad.");
        return;
    }

    const url = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'ordenDetalle' : 'ordenDetalle';

    // Bloqueamos el botón para evitar dobles clics
    const btnGuardar = event.target || document.querySelector('#modalNovedades .bg-red-600');
    let textoOriginal = btnGuardar.innerHTML;
    btnGuardar.innerHTML = 'Guardando...';
    btnGuardar.disabled = true;

    $.post(url, {
        accion: 'ajaxGuardarNovedad',
        tipo: 'guardar',
        id_orden: idOrden,
        novedades: arrayNovedades // Enviamos el arreglo completo
    }, function(resp) {
        if (resp.success) {
            actualizarIconoNovedad(idOrden, true);
            
            // Actualizamos los inputs ocultos
            document.getElementById(`hdn-tiene-${idOrden}`).value = "1";
            // Guardamos el arreglo como string (separado por comas) en el hidden para futuras ediciones
            document.getElementById(`hdn-tipo-${idOrden}`).value = arrayNovedades.join(',');

            cerrarModalNovedad();
        } else {
            alert("Error al guardar novedad.");
        }
    }, 'json').fail(function() {
        alert("Error de conexión al guardar.");
    }).always(function() {
        // Restaurar el botón siempre
        btnGuardar.innerHTML = textoOriginal;
        btnGuardar.disabled = false;
    });
}

function eliminarNovedad() {
    const idOrden = document.getElementById('nov_id_orden').value;
    if (!confirm("¿Seguro que deseas quitar todas las novedades de esta orden?")) return;

    const url = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'ordenDetalle' : 'ordenDetalle';

    $.post(url, {
        accion: 'ajaxGuardarNovedad',
        tipo: 'eliminar',
        id_orden: idOrden
    }, function(resp) {
        if (resp.success) {
            actualizarIconoNovedad(idOrden, false);
            
            document.getElementById(`hdn-tiene-${idOrden}`).value = "0";
            document.getElementById(`hdn-tipo-${idOrden}`).value = ""; // Vaciamos el hidden
            
            cerrarModalNovedad();
        } else {
            alert("No se pudo eliminar.");
        }
    }, 'json').fail(function() {
        alert("Error de conexión al eliminar.");
    });
}

function actualizarIconoNovedad(idOrden, tieneNovedad) {
    const btn = document.getElementById(`btn-nov-${idOrden}`);
    if (!btn) return; // Por si acaso no existe el botón
    
    if (tieneNovedad) {
        btn.classList.remove('text-gray-300', 'hover:text-yellow-500');
        btn.classList.add('text-red-600', 'animate-pulse');
        
        // Opcional: Quitar la animación después de unos segundos
        setTimeout(() => btn.classList.remove('animate-pulse'), 2000);
    } else {
        btn.classList.remove('text-red-600', 'animate-pulse');
        btn.classList.add('text-gray-300', 'hover:text-yellow-500');
    }
}

// Inicialización cuando carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Si estás usando jQuery, a veces es mejor inicializar Select2 dentro de $(document).ready
    if (typeof jQuery !== 'undefined') {
        $(document).ready(inicializarNovedades);
    } else {
        inicializarNovedades();
    }
});