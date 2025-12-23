// ==========================================
// GESTOR DE REPUESTOS
// ==========================================

/**
 * Abrir modal de repuestos para una fila
 */
async function abrirModal(idFila) {
    document.getElementById('modal_fila_actual').value = idFila;

    const idTecnico = $(`#select_tecnico_${idFila}`).val();

    if (!idTecnico) {
        alert("⚠️ Seleccione primero un TÉCNICO para cargar su inventario.");
        return;
    }

    // Recuperar datos guardados
    if (!window.AppConfig.almacenRepuestos[idFila]) {
        const hiddenInput = document.getElementById(`json_rep_${idFila}`);
        if (hiddenInput && hiddenInput.value && hiddenInput.value !== '[]') {
            try {
                window.AppConfig.almacenRepuestos[idFila] = JSON.parse(hiddenInput.value);
            } catch (e) {
                window.AppConfig.almacenRepuestos[idFila] = [];
            }
        } else {
            window.AppConfig.almacenRepuestos[idFila] = [];
        }
    }

    // Preparar select
    const selectRep = $('#select_repuesto_modal');
    selectRep.empty();
    selectRep.append(new Option("Cargando inventario...", ""));
    selectRep.prop('disabled', true);

    // Cargar inventario en vivo
    try {
        const inventario = await window.AjaxUtils.cargarInventarioTecnico(idTecnico);

        selectRep.empty();
        selectRep.append(new Option("- Buscar en Maleta del Técnico -", ""));

        window.AppConfig.stockActualModal = {};

        if (inventario.length > 0) {
            inventario.forEach(item => {
                window.AppConfig.stockActualModal[item.id_repuesto] = parseInt(item.cantidad_actual);

                const textoOption = `${item.nombre_repuesto} (Disp: ${item.cantidad_actual})`;
                const option = new Option(textoOption, item.id_repuesto, false, false);
                $(option).attr('data-max', item.cantidad_actual);

                selectRep.append(option);
            });
        } else {
            selectRep.append(new Option("🚫 Técnico sin stock asignado", ""));
        }
    } catch (error) {
        console.error("Error cargando inventario:", error);
        selectRep.append(new Option("Error de conexión", ""));
    } finally {
        selectRep.prop('disabled', false);
        selectRep.trigger('change');
    }

    // Resetear formulario
    $('#select_repuesto_modal').val(null).trigger('change');
    document.getElementById('cantidad_repuesto_modal').value = "1";
    document.getElementById('select_origen_modal').value = "INEES";

    renderizarListaVisual(idFila);

    // Mostrar modal
    $('#modalRepuestos').removeClass('hidden').addClass('flex');
}

/**
 * Cerrar modal
 */
function cerrarModal() {
    $('#modalRepuestos').addClass('hidden').removeClass('flex');
}

/**
 * Agregar repuesto a la lista temporal
 */
function agregarRepuestoALista() {
    const idFila = document.getElementById('modal_fila_actual').value;
    const idRepuesto = $('#select_repuesto_modal').val();
    const dataSelect = $('#select_repuesto_modal').select2('data');
    const nombreRepuesto = dataSelect[0]?.text || '';
    const origen = document.getElementById('select_origen_modal').value;
    let cantidadSolicitada = parseInt(document.getElementById('cantidad_repuesto_modal').value) || 1;

    if (!idRepuesto) {
        alert("⚠️ Seleccione un repuesto.");
        return;
    }

    // Validar stock para origen INEES
    if (origen === 'INEES') {
        const maxDisponible = window.AppConfig.stockActualModal[idRepuesto] || 0;
        const yaEnLista = window.AppConfig.almacenRepuestos[idFila] || [];
        let cantidadEnUso = 0;

        const itemExistente = yaEnLista.find(r => r.id == idRepuesto && r.origen === 'INEES');
        if (itemExistente) {
            cantidadEnUso = itemExistente.cantidad;
        }

        const totalFinal = cantidadEnUso + cantidadSolicitada;

        if (totalFinal > maxDisponible) {
            alert(`⚠️ STOCK INSUFICIENTE\n\nDisponible: ${maxDisponible}\nYa usaste: ${cantidadEnUso}\nIntentas agregar: ${cantidadSolicitada}`);
            return;
        }
    }

    // Inicializar array
    if (!window.AppConfig.almacenRepuestos[idFila]) {
        window.AppConfig.almacenRepuestos[idFila] = [];
    }

    // Limpiar nombre (quitar texto de disponibilidad)
    let nombreLimpio = nombreRepuesto.split(' (Disp:')[0];

    // Buscar si ya existe
    const indiceExistente = window.AppConfig.almacenRepuestos[idFila].findIndex(
        r => r.id === idRepuesto && r.origen === origen
    );

    if (indiceExistente !== -1) {
        window.AppConfig.almacenRepuestos[idFila][indiceExistente].cantidad += cantidadSolicitada;
    } else {
        window.AppConfig.almacenRepuestos[idFila].push({
            id: idRepuesto,
            nombre: nombreLimpio,
            origen: origen,
            cantidad: cantidadSolicitada
        });
    }

    // Resetear formulario
    $('#select_repuesto_modal').val(null).trigger('change');
    document.getElementById('cantidad_repuesto_modal').value = "1";
    renderizarListaVisual(idFila);
}

/**
 * Renderizar lista visual de repuestos
 */
function renderizarListaVisual(idFila) {
    const ul = document.getElementById('lista_repuestos_visual');
    ul.innerHTML = '';

    const lista = window.AppConfig.almacenRepuestos[idFila] || [];

    if (lista.length === 0) {
        ul.innerHTML = '<li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>';
        return;
    }

    lista.forEach((item, index) => {
        const bgBadge = item.origen === 'INEES' ?
            'bg-blue-100 text-blue-800' :
            'bg-orange-100 text-orange-800';

        const cant = item.cantidad || 1;
        const badgeCantidad = cant > 1 ?
            `<span class="ml-1 bg-gray-700 text-white text-[10px] px-1.5 rounded font-bold">x${cant}</span>` : '';

        ul.innerHTML += `
        <li class="flex justify-between items-center bg-gray-50 p-2 mb-2 border rounded shadow-sm">
            <div class="flex items-center gap-2 overflow-hidden">
                <span class="text-[10px] font-bold px-2 py-0.5 rounded ${bgBadge}">${item.origen}</span>
                <span class="text-xs text-gray-700 truncate font-medium">
                    ${item.nombre} ${badgeCantidad}
                </span>
            </div>
            <button type="button" onclick="window.RepuestosManager.borrarRepuesto('${idFila}', ${index})" 
                    class="text-red-400 hover:text-red-600 px-2">
                <i class="fas fa-trash-alt"></i>
            </button>
        </li>`;
    });
}

/**
 * Borrar un repuesto de la lista temporal
 */
function borrarRepuesto(idFila, index) {
    if (window.AppConfig.almacenRepuestos[idFila]) {
        window.AppConfig.almacenRepuestos[idFila].splice(index, 1);
        renderizarListaVisual(idFila);
    }
}

/**
 * Guardar cambios del modal
 */
function guardarCambiosModal() {
    const idFila = document.getElementById('modal_fila_actual').value;
    const lista = window.AppConfig.almacenRepuestos[idFila] || [];

    // Actualizar JSON oculto
    const jsonInput = document.getElementById(`json_rep_${idFila}`);
    if (jsonInput) {
        jsonInput.value = JSON.stringify(lista);
    }

    // Calcular total de items
    let totalItems = 0;
    lista.forEach(item => {
        totalItems += (item.cantidad || 1);
    });

    // Actualizar botón visual
    const btnTexto = document.getElementById(`count_rep_${idFila}`);
    if (btnTexto) {
        btnTexto.innerText = `${totalItems} Items`;

        const btnPadre = btnTexto.parentElement;
        if (totalItems > 0) {
            btnPadre.classList.remove('bg-gray-200', 'text-gray-700');
            btnPadre.classList.add('bg-blue-600', 'text-white', 'border-blue-700');
        } else {
            btnPadre.classList.add('bg-gray-200', 'text-gray-700');
            btnPadre.classList.remove('bg-blue-600', 'text-white', 'border-blue-700');
        }
    }

    cerrarModal();
    window.FilaManager.validarCoherencia(idFila);
}

/**
 * Actualizar botón de repuestos
 */
function actualizarBotonRepuestos(id) {
    const btnTexto = document.getElementById(`count_rep_${id}`);
    const lista = window.AppConfig.almacenRepuestos[id] || [];

    let totalReal = 0;
    lista.forEach(item => {
        totalReal += parseInt(item.cantidad || 1);
    });

    if (btnTexto) {
        btnTexto.innerText = `${totalReal} Items`;

        const btnPadre = btnTexto.parentElement;
        if (lista.length > 0) {
            btnPadre.classList.remove('bg-gray-200', 'text-gray-700');
            btnPadre.classList.add('bg-blue-600', 'text-white', 'border-blue-700');
        } else {
            btnPadre.classList.add('bg-gray-200', 'text-gray-700');
            btnPadre.classList.remove('bg-blue-600', 'text-white', 'border-blue-700');
        }
    }
}

/**
 * Inicializar select2 en el modal
 */
function inicializarSelect2Modal() {
    $('#select_repuesto_modal').select2({
        width: '100%',
        dropdownParent: $('#modalRepuestos'),
        placeholder: "- Escriba para buscar -",
        allowClear: true,
        language: { noResults: () => "No se encontró el repuesto" }
    });
}

// Exportar
window.RepuestosManager = {
    abrirModal,
    cerrarModal,
    agregarRepuestoALista,
    borrarRepuesto,
    guardarCambiosModal,
    actualizarBotonRepuestos,
    inicializarSelect2Modal
};

// Retrocompatibilidad
window.abrirModalRepuestos = abrirModal;
window.cerrarModal = cerrarModal;
window.agregarRepuestoALista = agregarRepuestoALista;
window.borrarRepuestoTemporal = borrarRepuesto;
window.guardarCambiosModal = guardarCambiosModal;