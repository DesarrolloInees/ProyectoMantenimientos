// ==========================================
// UTILIDADES AJAX
// ==========================================

/**
 * Función genérica para enviar peticiones AJAX
 * @param {string} accion - Nombre de la acción a ejecutar
 * @param {object} datos - Datos a enviar
 * @returns {Promise} Respuesta JSON
 */
async function enviarAjax(accion, datos) {
    try {
        const formData = new FormData();
        formData.append('accion', accion);

        for (const key in datos) {
            formData.append(key, datos[key]);
        }

        const respuesta = await fetch('index.php?pagina=ordenCrear', {
            method: 'POST',
            body: formData
        });

        if (!respuesta.ok) {
            throw new Error(`HTTP error! status: ${respuesta.status}`);
        }

        return await respuesta.json();
    } catch (error) {
        console.error('Error en AJAX:', error);
        return [];
    }
}

/**
 * Cargar puntos de un cliente
 */
async function cargarPuntos(id, idCliente) {
    limpiarFilaDesde(id, 'punto');
    if (!idCliente) return;

    const selectPunto = document.getElementById(`select_punto_${id}`);
    selectPunto.innerHTML = '<option value="">Cargando...</option>';
    selectPunto.disabled = true;

    const puntos = await enviarAjax('ajaxPuntos', { id_cliente: idCliente });

    let options = '<option value="">- Seleccione Punto -</option>';
    puntos.forEach(punto => {
        const codigo = punto.codigo_1 || 'S/C';
        options += `<option value="${punto.id_punto}" data-modalidad="${punto.id_modalidad}">
                        ${punto.nombre_punto} - (${codigo})
                    </option>`;
    });

    selectPunto.innerHTML = options;
    selectPunto.disabled = false;
    activarSelect2(`#select_punto_${id}`);
}

/**
 * Cargar máquinas de un punto
 */
async function cargarMaquinas(id, idPunto) {
    limpiarFilaDesde(id, 'maquina');
    if (!idPunto) return;

    // Actualizar modalidad
    const selPunto = document.getElementById(`select_punto_${id}`);
    const opcionPunto = selPunto.options[selPunto.selectedIndex];
    const modalidadDefecto = opcionPunto.getAttribute('data-modalidad') || 1;

    const selectModalidad = document.getElementById(`select_modalidad_${id}`);
    selectModalidad.value = modalidadDefecto;
    selectModalidad.disabled = false;

    // Cargar máquinas
    const selectMaquina = document.getElementById(`select_maquina_${id}`);
    selectMaquina.innerHTML = '<option value="">Cargando...</option>';

    const maquinas = await enviarAjax('ajaxMaquinas', { id_punto: idPunto });

    let options = '';
    if (maquinas.length === 0) {
        options = '<option value="">- No hay máquinas -</option>';
    }

    maquinas.forEach(m => {
        options += `<option value="${m.id_maquina}" data-device="${m.device_id}" data-tipo="${m.id_tipo_maquina}">
                        ${m.nombre_tipo_maquina} (${m.device_id})
                    </option>`;
    });

    selectMaquina.innerHTML = options;
    selectMaquina.disabled = false;

    // Auto-seleccionar primera máquina
    if (maquinas.length > 0) {
        selectMaquina.selectedIndex = 0;
        rellenarDeviceId(id, maquinas[0].id_maquina);
    }
}

/**
 * Cargar remisiones disponibles de un técnico
 */
async function cargarRemisiones(idFila, idTecnico) {
    const selectRemision = document.getElementById(`select_remision_${idFila}`);

    if ($(selectRemision).data('select2')) {
        $(selectRemision).select2('destroy');
    }

    selectRemision.innerHTML = '<option value="">Cargando...</option>';
    selectRemision.disabled = true;

    if (!idTecnico) {
        selectRemision.innerHTML = '<option value="">- Seleccione Técnico -</option>';
        return;
    }

    const remisiones = await enviarAjax('ajaxRemisiones', { id_tecnico: idTecnico });

    if (remisiones.length > 0) {
        let options = '<option value="">- Buscar Remisión -</option>';
        remisiones.forEach(r => {
            options += `<option value="${r.numero_remision}">${r.numero_remision}</option>`;
        });

        selectRemision.innerHTML = options;
        selectRemision.disabled = false;
        selectRemision.classList.remove('bg-gray-100');
        selectRemision.classList.add('bg-white');

        $(`#select_remision_${idFila}`).select2({
            width: '100%',
            placeholder: "Escriba remisión...",
            allowClear: true,
            language: { noResults: () => "Sin coincidencias" }
        });
    } else {
        selectRemision.innerHTML = '<option value="">🚫 Sin remisiones</option>';
        alert('⚠️ Este técnico no tiene remisiones disponibles.');
    }
}

/**
 * Calcular precio del servicio
 */
async function calcularPrecio(id) {
    const fila = document.getElementById(`fila_${id}`);
    if (!fila) return;

    // Forma segura con jQuery/Select2
    const idModalidad = $(`#select_modalidad_${id}`).val();

    // Obtener la opción seleccionada de forma segura
    const $maquinaOption = $(`#select_maquina_${id}`).find(':selected');
    const tipoMaq = $maquinaOption.data('tipo');

    const selectServicio = fila.querySelector(`select[name="filas[${id}][tipo_servicio]"]`);
    const idManto = selectServicio?.value;

    const inputValor = fila.querySelector(`input[name="filas[${id}][valor]"]`);
    const inputFechaGlobal = document.querySelector('input[name="fecha_reporte"]');
    const fechaVal = inputFechaGlobal?.value || '';

    // Limpiar estados previos antes de calcular
    inputValor.classList.remove('bg-red-200', 'border-red-500', 'text-red-700', 'font-bold', 'placeholder-red-700');
    fila.classList.remove('error-tarifa-faltante');
    inputValor.placeholder = "Valor";

    if (idModalidad && tipoMaq && idManto) {
        inputValor.value = "...";

        const res = await enviarAjax('ajaxCalcularPrecio', {
            id_maquina_tipo: tipoMaq,
            id_manto: idManto,
            id_modalidad: idModalidad,
            fecha_visita: fechaVal
        });

        if (res && res.precio !== undefined) {

            // 🛑 CASO 1: NO EXISTE TARIFA (-1)
            if (parseInt(res.precio) === -1) {
                inputValor.value = ""; // Borramos el valor
                inputValor.placeholder = "🚫 SIN TARIFA"; // Mensaje claro

                // Estilos de ERROR VISUAL
                inputValor.classList.add('bg-red-200', 'border-red-500', 'text-red-700', 'font-bold', 'placeholder-red-700');

                // MARCA TÉCNICA PARA EL VALIDADOR
                fila.classList.add('error-tarifa-faltante');

                // Notificación opcional (si quieres ser muy evidente)
                // window.CrearNotificaciones.mostrarNotificacion('⚠️ Máquina sin tarifa configurada', 'warning');

            } else {
                // ✅ CASO 2: PRECIO VÁLIDO (Incluye 0)
                inputValor.value = new Intl.NumberFormat('es-CO').format(res.precio);

                // 🔔 NOTIFICACIÓN de precio calculado
                if (res.precio > 0) {
                    window.CrearNotificaciones.notificarPrecioCalculado(id, res.precio);
                }
            }

        } else {
            inputValor.value = 0;
        }
    }
}

/**
 * Cargar inventario de un técnico
 */
async function cargarInventarioTecnico(idTecnico) {
    try {
        const inventario = await enviarAjax('ajaxInventarioTecnico', { id_tecnico: idTecnico });
        return inventario;
    } catch (error) {
        console.error('Error cargando inventario:', error);
        return [];
    }
}

// Exportar funciones
window.AjaxUtils = {
    enviarAjax,
    cargarPuntos,
    cargarMaquinas,
    cargarRemisiones,
    calcularPrecio,
    cargarInventarioTecnico
};