// ==========================================
// GESTIÓN DE PETICIONES AJAX
// ==========================================

/**
 * Cargar puntos de un cliente
 */
function cargarPuntos(idFila, idCliente, mantenerValorActual = false, callback = null) {
    let selPunto = $(`#sel_punto_${idFila}`);
    let selMaq = document.getElementById(`sel_maq_${idFila}`);
    let valorPrevio = selPunto.val();

    if (!mantenerValorActual) {
        selPunto.html('<option>Cargando...</option>');
        selMaq.innerHTML = '<option>Esperando punto...</option>';
    }

    let fd = new FormData();
    fd.append('accion', 'ajaxObtenerPuntos');
    fd.append('id_cliente', idCliente);

    fetch('index.php?pagina=ordenDetalle', {
        method: 'POST',
        body: fd
    })
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

        selPunto.trigger('change.select2');
        
        // 🔔 NOTIFICACIÓN
        if (data.length > 0) {
            window.DetalleNotificaciones.notificarDatosCargados('Puntos', data.length);
        }
        
        if (callback) callback();
    })
    .catch(error => {
        console.error("Error cargando puntos:", error);
        window.DetalleNotificaciones.notificarError('No se pudieron cargar los puntos');
    });
}

/**
 * Verificar si los puntos están cargados
 */
function verificarCargaPuntos(idFila) {
    let selPunto = document.getElementById(`sel_punto_${idFila}`);
    if (selPunto.getAttribute('data-loaded') === 'true') return;

    let selCliente = document.querySelector(`select[name="servicios[${idFila}][id_cliente]"]`);
    let idCliente = selCliente ? selCliente.value : null;

    if (idCliente) {
        console.log("Cargando puntos completos para fila " + idFila);
        cargarPuntos(idFila, idCliente, true);
    }
}

/**
 * Cargar máquinas de un punto
 */
function cargarMaquinas(idFila, idPunto) {
    let selMaq = document.getElementById(`sel_maq_${idFila}`);
    selMaq.innerHTML = '<option>Cargando...</option>';

    // Cargar delegación
    let divDel = document.getElementById(`td_delegacion_${idFila}`);
    let fdDel = new FormData();
    fdDel.append('accion', 'ajaxObtenerDelegacion');
    fdDel.append('id_punto', idPunto);
    
    fetch('index.php?pagina=ordenDetalle', {
        method: 'POST',
        body: fdDel
    })
    .then(r => r.json())
    .then(d => {
        if (divDel) divDel.innerText = d.delegacion;
    });

    // Cargar máquinas
    let fd = new FormData();
    fd.append('accion', 'ajaxObtenerMaquinas');
    fd.append('id_punto', idPunto);

    fetch('index.php?pagina=ordenDetalle', {
        method: 'POST',
        body: fd
    })
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
            
            // 🔔 NOTIFICACIÓN
            window.DetalleNotificaciones.notificarDatosCargados('Máquinas', data.length);
        }
    })
    .catch(error => {
        console.error('Error cargando máquinas:', error);
        window.DetalleNotificaciones.notificarError('No se pudieron cargar las máquinas');
    });
}

/**
 * Actualizar tipo de máquina mostrado
 */
function actualizarTipoMaquina(idFila) {
    let selMaq = document.getElementById(`sel_maq_${idFila}`);
    let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
    
    if (selMaq.selectedIndex >= 0) {
        let opt = selMaq.options[selMaq.selectedIndex];
        if (divTipo) divTipo.innerText = opt.getAttribute('data-tipo') || '';
    }
}

/**
 * Actualizar tarifa con fecha dinámica y validación de existencia
 */
function actualizarTarifa(idFila) {
    const inputValor = document.getElementById(`input_valor_${idFila}`);
    const selectMaquina = document.getElementById(`sel_maq_${idFila}`);
    const selectServicio = document.getElementById(`sel_servicio_${idFila}`);
    const selectModalidad = document.getElementById(`sel_modalidad_${idFila}`);
    const filaTR = document.getElementById(`fila_${idFila}`); // Referencia a la fila

    if (!selectMaquina || !selectServicio || !selectModalidad) return;

    const opcionMaquina = selectMaquina.options[selectMaquina.selectedIndex];
    const idTipoMaquina = opcionMaquina ? opcionMaquina.getAttribute('data-idtipomaquina') : '';
    const idTipoMantenimiento = selectServicio.value;
    const idModalidad = selectModalidad.value;

    const inputFecha = document.querySelector(`input[name="servicios[${idFila}][fecha_individual]"]`);
    const fechaVal = inputFecha ? inputFecha.value : '';

    // Limpieza de estados de error previos
    inputValor.classList.remove('bg-red-200', 'border-red-500', 'text-red-700', 'font-bold');
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

    fetch('index.php?pagina=ordenDetalle', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        let precio = parseInt(data.precio);

        // 🛑 CASO 1: TARIFA NO EXISTE (-1)
        if (precio === -1) {
            inputValor.value = ""; 
            inputValor.placeholder = "🚫 SIN TARIFA";
            
            // Pintar de rojo
            inputValor.style.opacity = "1";
            inputValor.classList.add('bg-red-200', 'border-red-500', 'text-red-700', 'font-bold');
            
            // MARCAR LA FILA (Vital para el bloqueo al guardar)
            if (filaTR) filaTR.classList.add('error-tarifa-faltante');

            window.DetalleNotificaciones.notificarError(`Fila ${idFila}: No existe tarifa para esa máquina/servicio`);
        
        } else {
            // ✅ CASO 2: TARIFA CORRECTA (Incluye 0)
            inputValor.value = new Intl.NumberFormat('es-CO').format(precio);
            
            inputValor.style.opacity = "1";
            inputValor.style.backgroundColor = "#bbf7d0"; // Verde flash
            setTimeout(() => inputValor.style.backgroundColor = "", 500);
        }
    })
    .catch(err => {
        console.error(err);
        inputValor.style.opacity = "1";
        window.DetalleNotificaciones.notificarError('Error de conexión al obtener precio');
    });
}

/**
 * Cargar stock de un técnico
 */
function cargarStockEnModal(idTecnico) {
    $('#select_repuesto_modal').empty();
    let opcionCargando = new Option("Cargando inventario...", "", true, true);
    $('#select_repuesto_modal').append(opcionCargando).trigger('change');

    const fd = new FormData();
    fd.append('accion', 'ajaxObtenerStockTecnico');
    fd.append('id_tecnico', idTecnico);

    fetch('index.php?pagina=ordenDetalle', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        $('#select_repuesto_modal').empty();
        $('#select_repuesto_modal').append('<option value="">- Seleccione del Inventario -</option>');

        window.DetalleConfig.stockActualTecnico = {};

        if (data.length > 0) {
            data.forEach(item => {
                let stock = parseInt(item.cantidad_actual);
                window.DetalleConfig.stockActualTecnico[item.id_repuesto] = stock;
                
                let textoOpcion = `${item.nombre_repuesto} (Disp: ${stock})`;
                let newOption = new Option(textoOpcion, item.id_repuesto, false, false);
                $(newOption).attr('data-stock', stock);
                $(newOption).attr('data-nombre', item.nombre_repuesto);
                
                $('#select_repuesto_modal').append(newOption);
            });
        } else {
            let sinStock = new Option("⚠️ Técnico SIN repuestos en su maleta", "", false, false);
            $(sinStock).attr('disabled', 'disabled');
            $('#select_repuesto_modal').append(sinStock);
        }

        $('#select_repuesto_modal').trigger('change');
    })
    .catch(err => {
        console.error("Error cargando stock", err);
        $('#select_repuesto_modal').html('<option>Error cargando inventario</option>');
    });
}

// Exportar
window.DetalleAjax = {
    cargarPuntos,
    verificarCargaPuntos,
    cargarMaquinas,
    actualizarTipoMaquina,
    actualizarTarifa,
    cargarStockEnModal
};

// Retrocompatibilidad
window.cargarPuntos = cargarPuntos;
window.verificarCargaPuntos = verificarCargaPuntos;
window.cargarMaquinas = cargarMaquinas;
window.actualizarTipoMaquina = actualizarTipoMaquina;
window.actualizarTarifa = actualizarTarifa;