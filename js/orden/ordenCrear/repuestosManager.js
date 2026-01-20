// ==========================================
// GESTOR DE REPUESTOS (Lógica Híbrida: Global + Stock)
// ==========================================

/**
 * Abrir modal de repuestos para una fila
 */
async function abrirModal(idFila) {
    document.getElementById('modal_fila_actual').value = idFila;

    const idTecnico = $(`#select_tecnico_${idFila}`).val();

    if (!idTecnico) {
        alert("⚠️ Seleccione primero un TÉCNICO para verificar su inventario.");
        return;
    }

    // 1. Recuperar repuestos ya seleccionados en esta fila
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

    // 2. Preparar el Select
    const selectRep = $('#select_repuesto_modal');
    selectRep.empty();
    selectRep.append(new Option("Cargando catálogo completo...", ""));
    selectRep.prop('disabled', true);

    try {
        // 3. Obtener Inventario REAL del Técnico (AJAX)
        // Esto nos dice qué tiene y cuánto, aunque mostraremos todo.
        const inventarioTecnico = await window.AjaxUtils.cargarInventarioTecnico(idTecnico);

        // Convertimos el array del técnico en un Objeto "Mapa" para búsqueda rápida
        // Ejemplo: { '101': 5, '102': 2 } (Donde key es id_repuesto y value es cantidad)
        window.AppConfig.stockActualModal = {}; 
        
        if (inventarioTecnico.length > 0) {
            inventarioTecnico.forEach(item => {
                window.AppConfig.stockActualModal[item.id_repuesto] = parseInt(item.cantidad_actual);
            });
        }

        selectRep.empty();
        selectRep.append(new Option("- Seleccione un Repuesto -", ""));

        // 4. 🔥 RECORRER LA LISTA GLOBAL (listaRepuestosBD)
        // Esta variable viene desde PHP con TODOS los repuestos del sistema
        if (typeof listaRepuestosBD !== 'undefined' && listaRepuestosBD.length > 0) {
            
            listaRepuestosBD.forEach(globalItem => {
                const idRep = globalItem.id_repuesto;
                // Verificamos si el técnico tiene stock de este ítem global
                const stockReal = window.AppConfig.stockActualModal[idRep] || 0;

                let textoOption = "";
                
                // Formato visual para ayudar al usuario
                if (stockReal > 0) {
                    textoOption = `✅ ${globalItem.nombre_repuesto} (Stock: ${stockReal})`;
                } else {
                    textoOption = `📦 ${globalItem.nombre_repuesto} (Sin Stock)`;
                }

                // Creamos la opción
                const option = new Option(textoOption, idRep, false, false);
                
                // Guardamos el stock real en un atributo data para validarlo luego sin ir al servidor
                $(option).attr('data-stock-real', stockReal);
                
                // (Opcional) Guardamos el nombre limpio para la lista visual
                $(option).attr('data-nombre-limpio', globalItem.nombre_repuesto);

                selectRep.append(option);
            });

        } else {
            selectRep.append(new Option("Error: No hay catálogo de repuestos", ""));
        }

    } catch (error) {
        console.error("Error al cruzar inventarios:", error);
        selectRep.append(new Option("Error de conexión", ""));
    } finally {
        selectRep.prop('disabled', false);
        // Inicializamos Select2 si no estaba ya
        if (!selectRep.data('select2')) {
                window.RepuestosManager.inicializarSelect2Modal();
        }
    }

    // Resetear inputs del modal
    $('#select_repuesto_modal').val(null).trigger('change');
    document.getElementById('cantidad_repuesto_modal').value = "1";
    document.getElementById('select_origen_modal').value = "INEES"; // Default

    // Renderizar la lista de abajo
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
 * Agregar repuesto a la lista temporal (CON VALIDACIÓN INTELIGENTE)
 */
function agregarRepuestoALista() {
    const idFila = document.getElementById('modal_fila_actual').value;
    
    // Obtener datos del Select2
    const idRepuesto = $('#select_repuesto_modal').val();
    const dataSelect = $('#select_repuesto_modal').select2('data');
    
    if (!idRepuesto || dataSelect.length === 0) {
        alert("⚠️ Por favor seleccione un repuesto.");
        return;
    }

    // Recuperar datos guardados en los atributos data- del option
    const element = $(dataSelect[0].element); 
    const stockReal = parseInt(element.attr('data-stock-real')) || 0;
    const nombreLimpio = element.attr('data-nombre-limpio') || dataSelect[0].text;

    const origen = document.getElementById('select_origen_modal').value; // INEES o PROSEGUR
    let cantidadSolicitada = parseInt(document.getElementById('cantidad_repuesto_modal').value) || 1;

    if (cantidadSolicitada <= 0) {
        alert("La cantidad debe ser mayor a 0.");
        return;
    }

    // ========================================================================
    // 🔥 LÓGICA DE VALIDACIÓN (EL CORAZÓN DEL CAMBIO)
    // ========================================================================
    
    // CASO 1: Origen INEES (Debe tener inventario sí o sí)
    if (origen === 'INEES') {
        
        // Calcular cuánto se ha usado ya en la lista temporal de este modal
        const yaEnLista = window.AppConfig.almacenRepuestos[idFila] || [];
        let cantidadEnUso = 0;
        const itemExistente = yaEnLista.find(r => r.id == idRepuesto && r.origen === 'INEES');
        
        if (itemExistente) {
            cantidadEnUso = itemExistente.cantidad;
        }

        const totalRequerido = cantidadEnUso + cantidadSolicitada;

        if (totalRequerido > stockReal) {
            // BLOQUEO: No dejamos agregar porque no tiene stock físico
            alert(`🛑 ERROR DE INVENTARIO (INEES)\n\n` +
                    `El técnico solo tiene: ${stockReal} unidades.\n` +
                    `Ya agregó: ${cantidadEnUso}\n` +
                    `Intenta agregar: ${cantidadSolicitada}\n\n` +
                    `Total requerido: ${totalRequerido} > Disponible: ${stockReal}\n\n` +
                    `👉 SOLUCIÓN: Si el repuesto lo suministró el cliente, cambie el Origen a "PROSEGUR".`);
            return;
        }
    }

    // CASO 2: Origen PROSEGUR (Pase libre)
    if (origen === 'PROSEGUR') {
        // No hacemos validación de stock.
        // El cliente lo trajo, nosotros lo instalamos. No se descuenta de nuestro inventario.
        // Opcional: Podrías mostrar un warning si quieres, pero mejor dejarlo fluido.
        console.log("Agregando repuesto Prosegur (Sin descuento de inventario).");
    }

    // ========================================================================

    // Inicializar array si no existe
    if (!window.AppConfig.almacenRepuestos[idFila]) {
        window.AppConfig.almacenRepuestos[idFila] = [];
    }

    // Lógica de inserción (igual que antes)
    const indiceExistente = window.AppConfig.almacenRepuestos[idFila].findIndex(
        r => r.id === idRepuesto && r.origen === origen
    );

    if (indiceExistente !== -1) {
        window.AppConfig.almacenRepuestos[idFila][indiceExistente].cantidad += cantidadSolicitada;
    } else {
        window.AppConfig.almacenRepuestos[idFila].push({
            id: idRepuesto,
            nombre: nombreLimpio, // Usamos el nombre limpio sin el texto de stock
            origen: origen,
            cantidad: cantidadSolicitada
        });
    }

    // UX: Feedback rápido
    document.getElementById('cantidad_repuesto_modal').value = "1";
    // Opcional: No limpiar el select para facilitar agregar el mismo repuesto con otro origen si fuera necesario, 
    // pero usualmente mejor limpiar:
    $('#select_repuesto_modal').val(null).trigger('change'); 

    renderizarListaVisual(idFila);
}

/**
 * Renderizar lista visual de repuestos (Sin cambios mayores)
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
        
        ul.innerHTML += `
        <li class="flex justify-between items-center bg-gray-50 p-2 mb-2 border rounded shadow-sm hover:bg-gray-100 transition">
            <div class="flex items-center gap-2 overflow-hidden w-full">
                <span class="text-[10px] font-bold px-2 py-0.5 rounded ${bgBadge} border border-opacity-20 flex-shrink-0" style="min-width:60px; text-align:center">
                    ${item.origen}
                </span>
                <span class="text-xs text-gray-700 font-medium truncate flex-grow">
                    ${item.nombre}
                </span>
                <span class="bg-gray-800 text-white text-[11px] px-2 py-0.5 rounded-full font-bold flex-shrink-0">
                    x${cant}
                </span>
            </div>
            <button type="button" onclick="window.RepuestosManager.borrarRepuesto('${idFila}', ${index})" 
                    class="text-red-400 hover:text-red-600 px-2 ml-2 transition transform hover:scale-110">
                <i class="fas fa-trash-alt"></i>
            </button>
        </li>`;
    });
}

// ... Las funciones borrarRepuesto, guardarCambiosModal, etc. se mantienen igual ...
// Solo asegúrate de copiar el resto del archivo original o usar el objeto window.RepuestosManager completo.
// Aquí exportamos solo lo modificado:

function borrarRepuesto(idFila, index) {
    if (window.AppConfig.almacenRepuestos[idFila]) {
        window.AppConfig.almacenRepuestos[idFila].splice(index, 1);
        renderizarListaVisual(idFila);
    }
}

function guardarCambiosModal() {
    const idFila = document.getElementById('modal_fila_actual').value;
    const lista = window.AppConfig.almacenRepuestos[idFila] || [];

    const jsonInput = document.getElementById(`json_rep_${idFila}`);
    if (jsonInput) {
        jsonInput.value = JSON.stringify(lista);
    }

    let totalItems = 0;
    lista.forEach(item => {
        totalItems += (item.cantidad || 1);
    });

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
    // Llamar validación si existe
    if(window.FilaManager && window.FilaManager.validarCoherencia) {
        window.FilaManager.validarCoherencia(idFila);
    }
}

function actualizarBotonRepuestos(id) {
    // ... misma función que tenías ...
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

function inicializarSelect2Modal() {
    $('#select_repuesto_modal').select2({
        width: '100%',
        dropdownParent: $('#modalRepuestos'),
        placeholder: "- Buscar Repuesto -",
        allowClear: true,
        language: { noResults: () => "No se encontró el repuesto" }
    });
}

window.RepuestosManager = {
    abrirModal,
    cerrarModal,
    agregarRepuestoALista,
    borrarRepuesto,
    guardarCambiosModal,
    actualizarBotonRepuestos,
    inicializarSelect2Modal
};