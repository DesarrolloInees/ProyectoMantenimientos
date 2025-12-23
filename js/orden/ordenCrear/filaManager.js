// ==========================================
// GESTOR DE FILAS (filaManager.js)
// ==========================================

/**
 * Genera HTML de opciones para selects
 * CORREGIDO: Ahora apunta a window.AppConfig.datos.[lista]
 */
function generarOpciones() {
    // Validar que los datos existan antes de intentar mapearlos
    const datos = window.AppConfig.datos || {};

    const optCli = '<option value="">- Cliente -</option>' +
        (datos.clientes || []).map(c =>
            `<option value="${c.id_cliente}">${c.nombre_cliente}</option>`
        ).join('');

    const optTec = '<option value="">- Técnico -</option>' +
        (datos.tecnicos || []).map(t =>
            `<option value="${t.id_tecnico}">${t.nombre_tecnico}</option>`
        ).join('');

    const optMan = '<option value="">- Resultado Mantenimiento -</option>' +
        (datos.mantos || []).map(m =>
            `<option value="${m.id_tipo_mantenimiento}">${m.nombre_completo}</option>`
        ).join('');

    const optEst = (datos.estados || []).map(e =>
        `<option value="${e.id_estado}">${e.nombre_estado}</option>`
    ).join('');

    const optCal = (datos.califs || []).map(c =>
        `<option value="${c.id_calificacion}">${c.nombre_calificacion}</option>`
    ).join('');

    return { optCli, optTec, optMan, optEst, optCal };
}

/**
 * Agregar una nueva fila de servicio
 */
function agregarFila() {
    window.AppConfig.contadorFilas++;
    const id = window.AppConfig.contadorFilas;

    // Generar las opciones con los datos corregidos
    const { optCli, optTec, optMan, optEst, optCal } = generarOpciones();

    const html = `
    <tr id="fila_${id}" class="hover:bg-blue-50 transition">
        <td class="px-2 py-3 text-center font-bold text-gray-400 sticky left-0 bg-white">${id}</td>
        
        <td class="px-2">
            <select name="filas[${id}][id_tecnico]" id="select_tecnico_${id}" 
                    onchange="window.AjaxUtils.cargarRemisiones(${id}, this.value)"
                    class="mi-select2 w-full border rounded p-1 font-semibold">
                ${optTec}
            </select>
        </td>

        <td class="px-2" id="celda_remision_${id}">
            <select name="filas[${id}][remision]" id="select_remision_${id}" 
                    class="w-full border rounded p-1 text-center font-bold bg-gray-100" disabled>
                <option value="">Wait...</option>
            </select>
        </td>

        <td class="px-2 space-y-1">
            <select name="filas[${id}][id_cliente]" id="select_cliente_${id}"  
                    onchange="window.AjaxUtils.cargarPuntos(${id}, this.value)" 
                    class="mi-select2 w-full border rounded p-1">
                ${optCli}
            </select>
            <select name="filas[${id}][id_punto]" id="select_punto_${id}" 
                    onchange="window.AjaxUtils.cargarMaquinas(${id}, this.value)" 
                    class="mi-select2 w-full border rounded p-1" disabled>
                <option value="">- Primero seleccione cliente -</option>
            </select>
        </td>

        <td class="px-2 space-y-1">
            <select name="filas[${id}][id_maquina]" id="select_maquina_${id}" 
                    onchange="window.FilaManager.rellenarDeviceId(${id}, this.value)" 
                    class="w-full border rounded p-1 text-xs bg-yellow-50" disabled>
                <option value="">- Primero seleccione punto -</option>
            </select>
            <input type="text" name="filas[${id}][device_id]" id="device_id_${id}" readonly 
                   class="w-full border p-1 text-xs text-center bg-gray-100 font-mono" 
                   placeholder="Device ID">
        </td>

        <td class="p-1">
            <select name="filas[${id}][id_modalidad]" id="select_modalidad_${id}" 
                    class="w-full border rounded p-1 text-xs bg-gray-50 font-bold"
                    onchange="window.AjaxUtils.calcularPrecio(${id})" disabled>
                <option value="1">URBANO</option>
                <option value="2">INTERURBANO</option>
            </select>
        </td>

        <td class="px-2">
            <select name="filas[${id}][tipo_servicio]" id="select_servicio_${id}" 
                    onchange="window.FilaManager.cambioServicio(${id})" 
                    class="w-full border rounded p-1 font-semibold">
                ${optMan}
            </select>
        </td>

        <td class="px-2 space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-[10px]">Entrada:</span> 
                <input type="text" name="filas[${id}][hora_in]" id="in_${id}" 
                       placeholder="HH:MM" 
                       class="border rounded p-0.5 w-20 text-center font-bold">
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-[10px]">Salida:</span> 
                <input type="text" name="filas[${id}][hora_out]" id="out_${id}" 
                       placeholder="HH:MM" 
                       class="border rounded p-0.5 w-20 text-center font-bold">
            </div>
        </td>

        <td class="px-2 text-center">
            <input type="text" id="duracion_${id}" readonly 
                   class="w-full text-center bg-transparent font-mono text-xs font-bold" 
                   placeholder="00:00">
        </td>

        <td class="px-2">
            <div class="relative">
                <span class="absolute left-2 top-1 text-gray-400">$</span>
                <input type="text" name="filas[${id}][valor]" 
                       class="w-full border rounded pl-4 pr-1 py-1 text-right font-mono font-bold bg-green-50" 
                       placeholder="0" value="0">
            </div>
        </td>

        <td class="px-2 text-center">
            <button type="button" onclick="window.RepuestosManager.abrirModal(${id})" 
                    class="bg-gray-200 hover:bg-gray-300 text-xs px-2 py-1 rounded w-full">
                <i class="fas fa-tools"></i> <span id="count_rep_${id}">0 Items</span>
            </button>
            <input type="hidden" name="filas[${id}][json_repuestos]" id="json_rep_${id}" value="[]">
        </td>

        <td class="px-2">
            <select name="filas[${id}][estado]" id="select_estado_${id}" 
                    class="w-full border rounded p-1 text-[10px]">
                ${optEst}
            </select>
        </td>

        <td class="px-2">
            <select name="filas[${id}][calif]" id="select_calif_${id}" 
                    class="w-full border rounded p-1 text-[10px]">
                ${optCal}
            </select>
        </td>

        <td class="px-2">
            <textarea name="filas[${id}][obs]" rows="2" 
                      class="w-full border rounded p-1 text-xs resize-y" 
                      style="min-height: 38px;"
                      placeholder="Describa actividades..."></textarea>
        </td>

        <td class="px-2 text-center">
            <button type="button" onclick="window.FilaManager.eliminarFila(${id})" 
                    class="text-red-400 hover:text-red-600">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>`;

    const contenedor = document.getElementById('contenedorFilas');
    if (contenedor) {
        contenedor.insertAdjacentHTML('beforeend', html);

        // Inicializar componentes (Usando UIUtils para seguridad)
        if (window.UIUtils && window.UIUtils.activarSelect2) {
            window.UIUtils.activarSelect2(`#select_cliente_${id}`);
            window.UIUtils.activarSelect2(`#select_punto_${id}`);
            window.UIUtils.activarSelect2(`#select_maquina_${id}`);
            window.UIUtils.activarSelect2(`#select_remision_${id}`);
            window.UIUtils.activarSelect2(`#select_modalidad_${id}`);
            window.UIUtils.activarSelect2(`#select_tecnico_${id}`);
            window.UIUtils.activarSelect2(`#select_servicio_${id}`);
            window.UIUtils.activarSelect2(`#select_estado_${id}`);
            window.UIUtils.activarSelect2(`#select_calif_${id}`);
        } else {
            // Fallback si UIUtils no está cargado aún (para debugging)
            console.warn('UIUtils no encontrado, intentando inicializar Select2 manualmente');
            $('.mi-select2').select2();
        }

        // Time Manager
        if (window.TimeManager) {
            window.TimeManager.activarInputHora(`#in_${id}`, id);
            window.TimeManager.activarInputHora(`#out_${id}`, id);
        }

        // Actualizar contador
        document.getElementById('contadorFilasDisplay').innerText = window.AppConfig.contadorFilas;

        // Auto-scroll
        setTimeout(() => {
            document.getElementById(`fila_${id}`)?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }, 100);
    }
}

/**
 * Eliminar una fila
 */
function eliminarFila(id) {
    const fila = document.getElementById(`fila_${id}`);
    if (fila) {
        fila.remove();
        if (window.AppConfig.almacenRepuestos) {
            delete window.AppConfig.almacenRepuestos[id];
        }

        const filasActuales = document.querySelectorAll('#contenedorFilas tr').length;
        document.getElementById('contadorFilasDisplay').innerText = filasActuales;
    }
}

/**
 * Rellenar Device ID automáticamente
 */
function rellenarDeviceId(id, idMaquina) {
    const selectMaquina = document.getElementById(`select_maquina_${id}`);
    if (!selectMaquina) return;

    const selectedOption = selectMaquina.options[selectMaquina.selectedIndex];
    const deviceId = selectedOption.getAttribute('data-device') || '';

    const inputDevice = document.getElementById(`device_id_${id}`);
    if (inputDevice) inputDevice.value = deviceId;

    if (window.AjaxUtils) {
        window.AjaxUtils.calcularPrecio(id);
    }
}

/**
 * Limpiar campos dependientes de una fila
 */
function limpiarFilaDesde(id, desde) {
    if (desde === 'punto' || desde === 'maquina') {
        const selectMaquina = document.getElementById(`select_maquina_${id}`);
        if (selectMaquina) {
            selectMaquina.innerHTML = '<option value="">- Primero seleccione punto -</option>';
            selectMaquina.disabled = true;
        }
        const devId = document.getElementById(`device_id_${id}`);
        if (devId) devId.value = '';
    }
    if (desde === 'punto') {
        const selectPunto = document.getElementById(`select_punto_${id}`);
        if (selectPunto) {
            selectPunto.innerHTML = '<option value="">- Primero seleccione cliente -</option>';
            selectPunto.disabled = true;
        }
    }
}

/**
 * Validar coherencia servicio-repuestos
 */
function validarCoherencia(id) {
    if (window.AppConfig.ignorarCambios) return;

    const selectServicio = document.getElementById(`select_servicio_${id}`);
    if (!selectServicio || selectServicio.selectedIndex === -1) return;

    const textoServicio = selectServicio.options[selectServicio.selectedIndex].text.toUpperCase();
    const numRepuestos = window.AppConfig.almacenRepuestos[id]?.length || 0;

    let mensaje = "";

    if (textoServicio.includes("CORRECTIVO") && numRepuestos === 0) {
        mensaje = "⚠️ AVISO: Mantenimiento CORRECTIVO sin repuestos.\n\n¿Estás seguro que no se usaron piezas?";
    }

    if (textoServicio.includes("PREVENTIVO") && numRepuestos > 0) {
        mensaje = "🤔 AVISO: Mantenimiento PREVENTIVO con repuestos.\n\nVerifica si deberías cambiar a Correctivo.";
    }

    if (mensaje) {
        setTimeout(() => alert(mensaje), 300);
    }
}

/**
 * Handler para cambio de servicio
 */
function cambioServicio(id) {
    if (window.AjaxUtils) {
        window.AjaxUtils.calcularPrecio(id);
    }
    validarCoherencia(id);
}

// Exportar
window.FilaManager = {
    agregarFila,
    eliminarFila,
    rellenarDeviceId,
    limpiarFilaDesde,
    validarCoherencia,
    cambioServicio
};

// Funciones globales para HTML onclick (Compatibilidad)
window.agregarFila = agregarFila;
window.eliminarFila = eliminarFila;
window.limpiarFilaDesde = limpiarFilaDesde;