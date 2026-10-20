/**
 * Fusiona una lista de novedades con la cache actual.
 * @param {Array} ids
 * @returns {Array} Lista unificada
 */
function fusionarNovedadesTecnico(ids) {
    var union = (ids || []).map(function (v) { return String(v); });
    (novedadesTecnicoActuales || []).forEach(function (v) {
        v = String(v);
        if (union.indexOf(v) === -1) union.push(v);
    });
    novedadesTecnicoActuales = union;
    return union;
}

/**
 * Establece la lista de novedades desde fuentes externas (borrador local).
 * @param {Array} ids
 */
function establecerNovedadesTecnico(ids) {
    novedadesTecnicoActuales = (ids || []).map(function (v) { return String(v); });
    if (typeof sincronizarHiddenNovedad === 'function') sincronizarHiddenNovedad();
}

// Exponer globalmente (el autoguardado puede cargarse antes que este archivo)
window.establecerNovedadesTecnico = establecerNovedadesTecnico;
window.fusionarNovedadesTecnico = fusionarNovedadesTecnico;

// ==========================================
// NOVEDADES - FLUJO TECNICO (tecnicoNovedades.js)
// ==========================================
// Mismo patron que js/orden/ordenDetalle/detalleNovedades.js
// pero apuntando al controlador de tecnicoReporte.

var listaNovedadesTecnico = [];

// Cache local de las novedades de la orden abierta (se usa en el modal y en el autoguardado)
var novedadesTecnicoActuales = [];

// Ultimas novedades confirmadas por el servidor
var novedadesServidorTecnico = [];

/**
 * Aplica las novedades recibidas del servidor.
 * Conserva los IDs locales que todavia no esten en el servidor (pueden provenir
 * de un borrador restaurado desde localStorage).
 *
 * @param {Array} ids IDs devueltos por el servidor
 * @returns {Array} Lista unificada
 */
function aplicarNovedadesServidor(ids) {
    novedadesServidorTecnico = (ids || []).map(function (v) { return String(v); });
    return fusionarNovedadesTecnico(novedadesServidorTecnico);
}

/**
 * Carga el catalogo de novedades desde el servidor (una sola vez).
 */
function cargarCatalogoNovedades() {
    if (listaNovedadesTecnico.length > 0) return Promise.resolve(listaNovedadesTecnico);

    var fd = new FormData();
    fd.append('accion', 'ajaxObtenerCatalogoNovedades');

    return fetch('index.php?pagina=tecnicoReporte&accion=ajaxObtenerCatalogoNovedades', {
        method: 'POST',
        body: fd
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            listaNovedadesTecnico = data || [];
            return listaNovedadesTecnico;
        });
}

/**
 * Inicializa el Select2 del modal con el catalogo cargado.
 */
function inicializarNovedadesTecnico() {
    var select = document.getElementById('nov_tipo');
    if (!select) return;

    select.innerHTML = '';
    listaNovedadesTecnico.forEach(function (t) {
        var opt = document.createElement('option');
        opt.value = t.id_tipo_novedad;
        opt.textContent = t.nombre_novedad;
        select.appendChild(opt);
    });

    if (window.jQuery && jQuery.fn.select2) {
        $('#nov_tipo').select2({
            placeholder: 'Buscar y seleccionar novedades...',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#modalNovedades')
        });
    }
}

/**
 * Consulta al servidor las novedades ya guardadas de una orden.
 * @param {int} idOrden
 * @returns {Promise<Array>}
 */
function consultarNovedadesTecnico(idOrden) {
    var fd = new FormData();
    fd.append('accion', 'ajaxObtenerNovedadesTecnico');
    fd.append('id_orden', idOrden);

    return fetch('index.php?pagina=tecnicoReporte&accion=ajaxObtenerNovedadesTecnico', {
        method: 'POST',
        body: fd
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data || !data.success) return [];
            return (data.ids || []).map(function (v) { return String(v); });
        })
        .catch(function () { return []; });
}

/**
 * Abre el modal de novedades para una orden.
 * @param {int} idOrden
 * @param {string} idsActuales Opcional: IDs existentes separados por coma (precarga optimista).
 */
function abrirModalNovedadTecnico(idOrden, idsActuales) {
    var inputOrden = document.getElementById('nov_id_orden');
    if (inputOrden) inputOrden.value = idOrden;

    // IDs optimistas que llegan desde la vista (si los hay)
    var arrPrevio = [];
    if (idsActuales && idsActuales.trim() !== '') {
        arrPrevio = idsActuales.split(',').filter(function (x) { return x !== ''; });
    }
    if (arrPrevio.length > 0) {
        novedadesTecnicoActuales = arrPrevio.slice();
    }

    // Cargar catalogo si aun no esta listo
    cargarCatalogoNovedades().then(function () {
        inicializarNovedadesTecnico();

        // Consultar SIEMPRE al servidor para traer lo que ya estaba guardado
        return consultarNovedadesTecnico(idOrden).then(function (idsServidor) {
            var arrIds = aplicarNovedadesServidor(idsServidor);

            if (window.jQuery && jQuery.fn.select2) {
                $('#nov_tipo').val(arrIds).trigger('change');
            } else {
                var sel = document.getElementById('nov_tipo');
                if (sel) {
                    Array.prototype.forEach.call(sel.options, function (o) {
                        o.selected = arrIds.indexOf(o.value) !== -1;
                    });
                }
            }

            // Mostrar modal
            var modal = document.getElementById('modalNovedades');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });
}

/**
 * Cierra el modal de novedades.
 */
function cerrarModalNovedad() {
    var modal = document.getElementById('modalNovedades');
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    if (window.jQuery && jQuery.fn.select2) {
        $('#nov_tipo').val(null).trigger('change');
    }
}

/**
 * Guarda las novedades seleccionadas via AJAX.
 */
function guardarNovedadTecnico() {
    var idOrden = document.getElementById('nov_id_orden').value;
    var arrayNovedades = [];

    if (window.jQuery && jQuery.fn.select2) {
        arrayNovedades = $('#nov_tipo').val() || [];
    } else {
        var sel = document.getElementById('nov_tipo');
        if (sel) {
            for (var i = 0; i < sel.selectedOptions.length; i++) {
                arrayNovedades.push(sel.selectedOptions[i].value);
            }
        }
    }

    // Si no hay nada seleccionado se permite vaciar la lista (equivale a quitar
    // todas las novedades ya guardadas de la orden), previa confirmacion.
    if (!arrayNovedades || arrayNovedades.length === 0) {
        if (!confirm('No hay novedades seleccionadas. Quieres quitar todas las novedades de esta orden?')) {
            return;
        }
    }

    // Bloquear boton
    var btnGuardar = document.querySelector('#modalNovedades .bg-red-600');
    var textoOriginal = btnGuardar.innerHTML;
    btnGuardar.innerHTML = 'Guardando...';
    btnGuardar.disabled = true;

    var fd = new FormData();
    fd.append('accion', 'ajaxGuardarNovedadTecnico');
    fd.append('id_orden', idOrden);
    arrayNovedades.forEach(function (idNov) {
        fd.append('novedades[]', idNov);
    });

    fetch('index.php?pagina=tecnicoReporte&accion=ajaxGuardarNovedadTecnico', {
        method: 'POST',
        body: fd
    })
        .then(function (res) { return res.json(); })
        .then(function (resp) {
            btnGuardar.innerHTML = textoOriginal;
            btnGuardar.disabled = false;

            if (resp.success) {
                // Actualizar cache local (para el autoguardado y la proxima apertura del modal)
                novedadesTecnicoActuales = arrayNovedades.map(function (v) { return String(v); });

                // El tecnico administro la lista: el submit del reporte debe persistirla
                // (incluso si quedo vacia -> eliminar todas las novedades de la orden).
                marcarNovedadesGestionadas();

                // Sincronizar el hidden del formulario (por si el reporte se envia sin guardar novedades antes)
                sincronizarHiddenNovedad();

                // Actualizar indicador visual en la vista
                var indicador = document.getElementById('indicador_novedad');
                if (indicador) {
                    if (arrayNovedades.length > 0) {
                        indicador.classList.remove('hidden');
                        indicador.classList.add('bg-green-100', 'text-green-800');
                        indicador.classList.remove('bg-gray-200', 'text-gray-700');
                        indicador.textContent = arrayNovedades.length + ' novedad(es)';
                    } else {
                        indicador.classList.remove('hidden', 'bg-green-100', 'text-green-800');
                        indicador.classList.add('bg-gray-200', 'text-gray-700');
                        indicador.textContent = 'Sin novedades';
                    }
                }

                cerrarModalNovedad();

                // Forzar el autoguardado del borrador para no perder las novedades
                if (typeof forzarGuardadoBorrador === 'function') forzarGuardadoBorrador();

                if (window.Notificaciones) {
                    Notificaciones.exito(
                        arrayNovedades.length > 0 ? 'Novedad guardada' : 'Novedades eliminadas',
                        resp.msj
                    );
                } else {
                    alert(arrayNovedades.length > 0 ? 'Novedad guardada correctamente.' : 'Novedades eliminadas correctamente.');
                }
            } else {
                alert('Error: ' + (resp.msj || 'No se pudo guardar la novedad.'));
            }
        })
        .catch(function () {
            btnGuardar.innerHTML = textoOriginal;
            btnGuardar.disabled = false;
            alert('Error de conexion al guardar la novedad.');
        });
}

/**
 * Elimina todas las novedades de la orden.
 */
function eliminarNovedadTecnico() {
    var idOrden = document.getElementById('nov_id_orden').value;
    if (!confirm('Seguro que deseas quitar todas las novedades de esta orden?')) return;

    var fd = new FormData();
    fd.append('accion', 'ajaxGuardarNovedadTecnico');
    fd.append('id_orden', idOrden);
    fd.append('novedades[]', ''); // Sin novedades = eliminar

    fetch('index.php?pagina=tecnicoReporte&accion=ajaxGuardarNovedadTecnico', {
        method: 'POST',
        body: fd
    })
        .then(function (res) { return res.json(); })
        .then(function (resp) {
            if (resp.success) {
                novedadesTecnicoActuales = [];
                marcarNovedadesGestionadas();
                sincronizarHiddenNovedad();

                var indicador = document.getElementById('indicador_novedad');
                if (indicador) {
                    indicador.classList.remove('bg-green-100', 'text-green-800');
                    indicador.classList.add('bg-gray-200', 'text-gray-700');
                    indicador.textContent = 'Sin novedades';
                }
                cerrarModalNovedad();

                if (typeof forzarGuardadoBorrador === 'function') forzarGuardadoBorrador();
            } else {
                alert('No se pudieron eliminar las novedades.');
            }
        })
        .catch(function () {
            alert('Error de conexion al eliminar.');
        });
}

/**
 * Marca que el tecnico administro la lista de novedades en esta sesion.
 * Con este indicador el controlador sabe que debe persistir la lista
 * (incluso si esta vacia, lo que significa "eliminar todas").
 */
function marcarNovedadesGestionadas() {
    var flag = document.querySelector('input[name="novedades_gestionada"]');
    if (flag) flag.value = '1';
}

/**
 * Sincroniza los campos ocultos del formulario con las novedades actuales.
 * Permite que el reporte final (submit) respete las novedades ya registradas.
 *
 * NOTA: 'detalle_novedad' es un campo historico independiente (texto libre del
 * tecnico) y NO se modifica aqui a proposito.
 */
function sincronizarHiddenNovedad() {
    var ids = novedadesTecnicoActuales || [];

    var tiene = document.querySelector('input[name="tiene_novedad"]');
    if (tiene) {
        tiene.value = (ids.length > 0) ? '1' : '0';
    }

    var tipo = document.querySelector('input[name="novedades_seleccionadas"]');
    if (tipo) {
        tipo.value = ids.join(',');
    }
}

// Retrocompatibilidad: alias que usan los HTML del modal copiado de ordenDetalle
window.abrirModalNovedad = function (idOrden) { abrirModalNovedadTecnico(idOrden); };
window.guardarNovedad = guardarNovedadTecnico;
window.eliminarNovedad = eliminarNovedadTecnico;
window.cerrarModalNovedad = cerrarModalNovedad;
