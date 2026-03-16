// ==========================================
// GESTIÓN DE PETICIONES AJAX
// ==========================================

// URL base dinámica — compatible con cualquier estructura de router
const AJAX_URL = (typeof BASE_URL !== 'undefined')
    ? BASE_URL + 'ordenDetalle'
    : 'index.php?pagina=ordenDetalle';

/**
 * Cargar puntos de un cliente
 */
function cargarPuntos(idFila, idCliente, mantenerValorActual = false, callback = null) {
    let selPunto = $(`#sel_punto_${idFila}`);
    let selMaq = document.getElementById(`sel_maq_${idFila}`);
    let valorPrevio = selPunto.val();

    if (!mantenerValorActual) {
        selPunto.html('<option>Cargando...</option>');
        if (selMaq) selMaq.innerHTML = '<option>Esperando punto...</option>';
    }

    const fd = new FormData();
    fd.append('accion', 'ajaxObtenerPuntos');
    fd.append('id_cliente', idCliente);

    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            let options = '<option value="">- Seleccione -</option>';
            data.forEach(p => {
                options += `<option value="${p.id_punto}" data-full="${p.nombre_punto}">${p.nombre_punto}</option>`;
            });
            selPunto.html(options);

            if (mantenerValorActual && valorPrevio) {
                selPunto.val(valorPrevio);
                selPunto.attr('data-loaded', 'true');
            } else if (data.length > 0) {
                selPunto.val(data[0].id_punto);
                cargarMaquinas(idFila, data[0].id_punto);
            }

            // Re-init Select2 para que el filtrado funcione con las nuevas opciones
            if (selPunto.data('select2')) selPunto.select2('destroy');
            selPunto.select2({ width: '100%', language: { noResults: () => "No encontrado" } });

            if (data.length > 0 && window.DetalleNotificaciones)
                window.DetalleNotificaciones.notificarDatosCargados('Puntos', data.length);
            if (callback) callback();
        })
        .catch(error => {
            console.error("Error cargando puntos:", error);
            if (window.DetalleNotificaciones)
                window.DetalleNotificaciones.notificarError('No se pudieron cargar los puntos');
        });
}

/**
 * Verificar si los puntos están cargados
 */
function verificarCargaPuntos(idFila) {
    let selPunto = document.getElementById(`sel_punto_${idFila}`);
    if (!selPunto || selPunto.getAttribute('data-loaded') === 'true') return;

    let selCliente = document.querySelector(`select[name="servicios[${idFila}][id_cliente]"]`);
    let idCliente = selCliente ? selCliente.value : null;
    if (idCliente) cargarPuntos(idFila, idCliente, true);
}

/**
 * Cargar máquinas de un punto
 */
function cargarMaquinas(idFila, idPunto) {
    let selMaq = document.getElementById(`sel_maq_${idFila}`);
    if (!selMaq) return;
    selMaq.innerHTML = '<option>Cargando...</option>';

    // Delegación en paralelo
    const fdDel = new FormData();
    fdDel.append('accion', 'ajaxObtenerDelegacion');
    fdDel.append('id_punto', idPunto);
    fetch(AJAX_URL, { method: 'POST', body: fdDel })
        .then(r => r.json())
        .then(d => {
            const divDel = document.getElementById(`td_delegacion_${idFila}`);
            if (divDel) divDel.innerText = d.delegacion || 'Sin asignar';
        });

    // Máquinas
    const fd = new FormData();
    fd.append('accion', 'ajaxObtenerMaquinas');
    fd.append('id_punto', idPunto);

    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            selMaq.innerHTML = '<option value="">- Seleccione -</option>';
            data.forEach(m => {
                selMaq.innerHTML += `<option value="${m.id_maquina}"
                    data-tipo="${m.nombre_tipo_maquina}"
                    data-idtipomaquina="${m.id_tipo_maquina}">
                    ${m.device_id} (${m.nombre_tipo_maquina})
                </option>`;
            });

            if (data.length > 0) {
                selMaq.value = data[0].id_maquina;
                actualizarTipoMaquina(idFila);
                actualizarTarifa(idFila);
                if (window.DetalleNotificaciones)
                    window.DetalleNotificaciones.notificarDatosCargados('Máquinas', data.length);
            }

            // Re-init Select2 en máquina para filtrado por escritura
            const $selMaq = $(`#sel_maq_${idFila}`);
            if ($selMaq.data('select2')) $selMaq.select2('destroy');
            $selMaq.select2({ width: '100%', language: { noResults: () => "No encontrado" } });
        })
        .catch(error => {
            console.error('Error cargando máquinas:', error);
            if (window.DetalleNotificaciones)
                window.DetalleNotificaciones.notificarError('No se pudieron cargar las máquinas');
        });
}

/**
 * Actualizar tipo de máquina mostrado
 */
function actualizarTipoMaquina(idFila) {
    const selMaq = document.getElementById(`sel_maq_${idFila}`);
    const divTipo = document.getElementById(`td_tipomaq_${idFila}`);
    if (selMaq && selMaq.selectedIndex >= 0 && divTipo) {
        divTipo.innerText = selMaq.options[selMaq.selectedIndex].getAttribute('data-tipo') || '';
    }
}

/**
 * Actualizar tarifa con validación de existencia
 */
function actualizarTarifa(idFila) {
    const inputValor = document.getElementById(`input_valor_${idFila}`);
    const selectMaquina = document.getElementById(`sel_maq_${idFila}`);
    const selectServicio = document.getElementById(`sel_servicio_${idFila}`);
    const selectModalidad = document.getElementById(`sel_modalidad_${idFila}`);
    const filaTR = document.getElementById(`fila_${idFila}`);

    if (!inputValor || !selectMaquina || !selectServicio || !selectModalidad) return;

    const opcionMaquina = selectMaquina.options[selectMaquina.selectedIndex];
    const idTipoMaquina = opcionMaquina ? opcionMaquina.getAttribute('data-idtipomaquina') : '';
    const idTipoMantenimiento = selectServicio.value;
    const idModalidad = selectModalidad.value;

    const inputFecha = document.querySelector(`input[name="servicios[${idFila}][fecha_individual]"]`);
    const fechaVal = inputFecha ? inputFecha.value : '';

    inputValor.classList.remove('bg-red-100', 'border-red-500', 'text-red-700');
    if (filaTR) filaTR.classList.remove('error-tarifa-faltante');
    inputValor.placeholder = "Valor";

    if (!idTipoMaquina || !idTipoMantenimiento) return;

    inputValor.style.opacity = "0.5";

    const formData = new FormData();
    formData.append('accion', 'ajaxObtenerPrecio');
    formData.append('id_tipo_maquina', idTipoMaquina);
    formData.append('id_tipo_mantenimiento', idTipoMantenimiento);
    formData.append('id_modalidad', idModalidad);
    formData.append('fecha_visita', fechaVal);

    fetch(AJAX_URL, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            const precio = parseInt(data.precio);
            inputValor.style.opacity = "1";

            if (precio === -1) {
                inputValor.value = "";
                inputValor.placeholder = "🚫 SIN TARIFA";
                inputValor.classList.add('bg-red-100', 'border-red-500', 'text-red-700');
                if (filaTR) filaTR.classList.add('error-tarifa-faltante');
                if (window.DetalleNotificaciones)
                    window.DetalleNotificaciones.notificarError(`Fila ${idFila}: Sin tarifa configurada`);
            } else {
                inputValor.value = new Intl.NumberFormat('es-CO').format(precio);
                inputValor.style.backgroundColor = "#bbf7d0";
                setTimeout(() => inputValor.style.backgroundColor = "", 500);
            }
        })
        .catch(() => {
            inputValor.style.opacity = "1";
            if (window.DetalleNotificaciones)
                window.DetalleNotificaciones.notificarError('Error de conexión al obtener precio');
        });
}

// ==========================================
// ✅ REMISIONES — CORREGIDO
// ==========================================

/**
 * Cargar remisiones del técnico en el select de la fila.
 *
 * Muestra siempre:
 *  - La remisión actualmente asignada a la orden (aunque esté USADA) → marcada con ✓
 *  - Las remisiones DISPONIBLES del técnico → para corregir si se equivocó
 *
 * El servidor recibe 'remision_actual' y la incluye en el resultado
 * aunque no esté disponible, garantizando que nunca desaparezca.
 */
function cargarRemisiones(idFila, idTecnico) {
    const selRemision = document.getElementById(`sel_remision_${idFila}`);
    if (!selRemision) return;

    if (!idTecnico || idTecnico == 0) {
        selRemision.innerHTML = '<option value="">- Sin técnico -</option>';
        return;
    }

    // Leer la remisión actual ANTES de tocar el select
    // Puede venir del HTML (carga inicial) o del valor previo si el usuario ya cambió
    const remisionActual = selRemision.dataset.remisionOriginal
        || selRemision.value
        || '';

    // Guardar como data attribute para sobrevivir reconstrucciones del select
    selRemision.dataset.remisionOriginal = remisionActual;

    selRemision.innerHTML = '<option value="">⏳ Cargando remisiones...</option>';
    selRemision.disabled = true;

    const fd = new FormData();
    fd.append('accion', 'ajaxObtenerRemisiones');
    fd.append('id_tecnico', idTecnico);
    fd.append('remision_actual', remisionActual); // ← el servidor la incluye aunque esté USADA

    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            selRemision.innerHTML = '';

            // Opción vacía (sin remisión)
            const optVacia = document.createElement('option');
            optVacia.value = '';
            optVacia.textContent = '- Sin remisión -';
            selRemision.appendChild(optVacia);

            // La remisión actual SIEMPRE aparece primero, marcada con ✓
            // El servidor ya la incluyó en `data`, pero si no vino la ponemos nosotros
            let actualYaVino = false;

            if (Array.isArray(data) && data.length > 0) {
                data.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.numero_remision;

                    const esActual = String(r.numero_remision) === String(remisionActual);
                    if (esActual) {
                        opt.textContent = `${r.numero_remision} ✓ (actual)`;
                        opt.selected = true;
                        actualYaVino = true;
                        selRemision.insertBefore(opt, selRemision.children[1] || null);
                    } else {
                        opt.textContent = r.numero_remision;
                        selRemision.appendChild(opt);
                    }
                });
            }

            // Seguro de vida: si el servidor no devolvió la actual, la añadimos igual
            if (remisionActual && !actualYaVino) {
                const optFallback = document.createElement('option');
                optFallback.value = remisionActual;
                optFallback.textContent = `${remisionActual} ✓ (actual)`;
                optFallback.selected = true;
                selRemision.insertBefore(optFallback, selRemision.children[1] || null);
            }

            selRemision.disabled = false;

            // Re-inicializar Select2 en este select específico para que
            // el filtrado por escritura funcione con las nuevas opciones
            if (window.DetalleApp?.reinitSelect2Fila) {
                window.DetalleApp.reinitSelect2Fila(idFila);
            } else {
                // Fallback directo si DetalleApp aún no cargó
                const $sel = $(`#sel_remision_${idFila}`);
                if ($sel.data('select2')) $sel.select2('destroy');
                $sel.select2({ width: '100%', language: { noResults: () => "No encontrado" } });
            }
        })
        .catch(err => {
            console.error('Error cargando remisiones:', err);
            selRemision.innerHTML = '';
            const optErr = document.createElement('option');
            optErr.value = remisionActual || '';
            optErr.textContent = remisionActual ? `${remisionActual} ✓ (actual)` : '- Error al cargar -';
            optErr.selected = true;
            selRemision.appendChild(optErr);
            selRemision.disabled = false;

            // Re-init Select2 también en error para no quedar sin él
            const $sel = $(`#sel_remision_${idFila}`);
            if ($sel.data('select2')) $sel.select2('destroy');
            $sel.select2({ width: '100%', language: { noResults: () => "No encontrado" } });
        });
}

/**
 * Cargar stock del técnico en el modal de repuestos
 */
function cargarStockEnModal(idTecnico) {
    $('#select_repuesto_modal').empty()
        .append(new Option("Cargando inventario...", "", true, true))
        .trigger('change');

    const fd = new FormData();
    fd.append('accion', 'ajaxObtenerStockTecnico');
    fd.append('id_tecnico', idTecnico);

    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            $('#select_repuesto_modal').empty()
                .append('<option value="">- Seleccione del Inventario -</option>');
            window.DetalleConfig.stockActualTecnico = {};

            if (data.length > 0) {
                data.forEach(item => {
                    const stock = parseInt(item.cantidad_actual);
                    window.DetalleConfig.stockActualTecnico[item.id_repuesto] = stock;
                    const opt = new Option(`${item.nombre_repuesto} (Disp: ${stock})`, item.id_repuesto, false, false);
                    $(opt).attr('data-stock', stock).attr('data-nombre', item.nombre_repuesto);
                    $('#select_repuesto_modal').append(opt);
                });
            } else {
                const sinStock = new Option("⚠️ Técnico SIN repuestos en su maleta", "", false, false);
                $(sinStock).prop('disabled', true);
                $('#select_repuesto_modal').append(sinStock);
            }
            $('#select_repuesto_modal').trigger('change');
        })
        .catch(() => {
            $('#select_repuesto_modal').html('<option>Error cargando inventario</option>');
        });
}

// ==========================================
// EXPORTAR
// ==========================================
window.DetalleAjax = {
    cargarPuntos, verificarCargaPuntos, cargarMaquinas,
    actualizarTipoMaquina, actualizarTarifa,
    cargarStockEnModal, cargarRemisiones
};

// Retrocompatibilidad global
window.cargarPuntos = cargarPuntos;
window.verificarCargaPuntos = verificarCargaPuntos;
window.cargarMaquinas = cargarMaquinas;
window.actualizarTipoMaquina = actualizarTipoMaquina;
window.actualizarTarifa = actualizarTarifa;
window.cargarRemisiones = cargarRemisiones;