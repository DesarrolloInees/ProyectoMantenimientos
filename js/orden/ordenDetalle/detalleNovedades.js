// ==========================================
// GESTIÓN DE NOVEDADES
// ==========================================

/**
 * Toggle estado de novedad
 */
function toggleNovedad(idFila) {
    let input = document.getElementById(`input_novedad_${idFila}`);
    let btn = document.getElementById(`btn_novedad_${idFila}`);

    let estadoActual = parseInt(input.value);

    if (estadoActual === 0) {
        // ACTIVAR NOVEDAD
        input.value = 1;
        btn.classList.remove('bg-gray-100', 'border-gray-300', 'text-gray-300', 'hover:bg-gray-200');
        btn.classList.add('bg-red-500', 'border-red-700', 'text-white', 'animate-pulse');
        setTimeout(() => btn.classList.remove('animate-pulse'), 500);
    } else {
        // DESACTIVAR NOVEDAD
        input.value = 0;
        btn.classList.remove('bg-red-500', 'border-red-700', 'text-white');
        btn.classList.add('bg-gray-100', 'border-gray-300', 'text-gray-300', 'hover:bg-gray-200');
    }
}

// Exportar
window.DetalleNovedades = {
    toggleNovedad
};

// Retrocompatibilidad
window.toggleNovedad = toggleNovedad;


// js/orden/ordenDetalle/detalleNovedades.js

function inicializarNovedades() {
    const select = document.getElementById('nov_tipo');
    const tipos = window.DetalleConfig.listaNovedades || [];
    
    select.innerHTML = '<option value="">-- Seleccione --</option>';
    tipos.forEach(t => {
        select.innerHTML += `<option value="${t.id_tipo_novedad}">${t.nombre_novedad}</option>`;
    });
}

function abrirModalNovedad(idOrden) {
    document.getElementById('nov_id_orden').value = idOrden;
    
    // Solo buscamos el TIPO, ignoramos el detalle
    const idTipoActual = document.getElementById(`hdn-tipo-${idOrden}`).value;

    document.getElementById('nov_tipo').value = idTipoActual ? idTipoActual.trim() : "";
    
    document.getElementById('modalNovedades').classList.remove('hidden');
    document.getElementById('modalNovedades').classList.add('flex');
}

function cerrarModalNovedad() {
    document.getElementById('modalNovedades').classList.add('hidden');
    document.getElementById('modalNovedades').classList.remove('flex');
}

function guardarNovedad() {
    const idOrden = document.getElementById('nov_id_orden').value;
    const idTipo  = document.getElementById('nov_tipo').value;

    if(!idTipo) {
        alert("Debes seleccionar un tipo de novedad.");
        return;
    }

    const url = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'ordenDetalle' : 'ordenDetalle';

    $.post(url, {
        accion: 'ajaxGuardarNovedad',
        tipo: 'guardar',
        id_orden: idOrden,
        id_tipo_novedad: idTipo
        // 🗑️ YA NO ENVIAMOS EL DETALLE
    }, function(resp) {
        if(resp.success) {
            actualizarIconoNovedad(idOrden, true);
            
            // Actualizamos los inputs ocultos (Solo los necesarios)
            document.getElementById(`hdn-tiene-${idOrden}`).value = "1";
            document.getElementById(`hdn-tipo-${idOrden}`).value = idTipo;

            cerrarModalNovedad();
        } else {
            alert("Error al guardar novedad.");
        }
    }, 'json').fail(function() {
        alert("Error de conexión.");
    });
}

function eliminarNovedad() {
    const idOrden = document.getElementById('nov_id_orden').value;
    if(!confirm("¿Seguro que deseas quitar la novedad?")) return;

    const url = (typeof BASE_URL !== 'undefined') ? BASE_URL + 'ordenDetalle' : 'ordenDetalle';

    $.post(url, {
        accion: 'ajaxGuardarNovedad',
        tipo: 'eliminar',
        id_orden: idOrden
    }, function(resp) {
        if(resp.success) {
            actualizarIconoNovedad(idOrden, false);
            
            document.getElementById(`hdn-tiene-${idOrden}`).value = "0";
            document.getElementById(`hdn-tipo-${idOrden}`).value = "";
            
            cerrarModalNovedad();
        } else {
            alert("No se pudo eliminar.");
        }
    }, 'json');
}

function actualizarIconoNovedad(idOrden, tieneNovedad) {
    const btn = document.getElementById(`btn-nov-${idOrden}`);
    if (tieneNovedad) {
        btn.classList.remove('text-gray-300', 'hover:text-yellow-500');
        btn.classList.add('text-red-600', 'animate-pulse');
    } else {
        btn.classList.remove('text-red-600', 'animate-pulse');
        btn.classList.add('text-gray-300', 'hover:text-yellow-500');
    }
}

document.addEventListener('DOMContentLoaded', inicializarNovedades);