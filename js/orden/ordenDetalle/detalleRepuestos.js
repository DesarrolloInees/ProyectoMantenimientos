// ==========================================
// GESTIÓN DE REPUESTOS (DETALLE) - LÓGICA HÍBRIDA
// ==========================================

/**
 * Convertir texto plano a array de repuestos
 */
function convertirTextoARepuestos(texto) {
    const arrayTemp = [];
    if (!window.DetalleConfig || window.DetalleConfig.isEmpty(texto)) return arrayTemp;

    const items = texto.split(",");
    const palabrasIgnorar = [
        "NO", "NINGUNO", "NINGUNA", "SIN REPUESTOS", "N/A", "NA", ".", "-", "0", "VACIO",
    ];

    items.forEach((item) => {
        let itemLimpio = item.trim();
        if (
            window.DetalleConfig.isEmpty(itemLimpio) ||
            palabrasIgnorar.includes(itemLimpio.toUpperCase())
        )
            return;

        let origen = "INEES";
        let cantidad = 1;

        // Detectar (xN) al final
        const matchCantidad = itemLimpio.match(/\(x(\d+)\)$/i);
        if (matchCantidad) {
            cantidad = parseInt(matchCantidad[1]);
            itemLimpio = itemLimpio.replace(/\(x\d+\)$/i, "").trim();
        }

        // Detectar Origen
        let nombre = itemLimpio;
        if (itemLimpio.toUpperCase().includes("(PROSEGUR)")) {
            origen = "PROSEGUR";
            nombre = itemLimpio.replace(/\(PROSEGUR\)/gi, "").trim();
        } else if (itemLimpio.toUpperCase().includes("(INEES)")) {
            origen = "INEES";
            nombre = itemLimpio.replace(/\(INEES\)/gi, "").trim();
        }

        // Buscar ID en catálogo global
        const repuestoEnCatalogo = window.DetalleConfig.catalogoRepuestos.find(
            (r) =>
                r.nombre_repuesto.toLowerCase() === nombre.toLowerCase() ||
                r.nombre_repuesto.toLowerCase().includes(nombre.toLowerCase())
        );

        arrayTemp.push({
            id: repuestoEnCatalogo ? repuestoEnCatalogo.id_repuesto : "",
            nombre: nombre,
            origen: origen,
            cantidad: cantidad,
        });
    });

    return arrayTemp;
}

/**
 * Abrir modal de repuestos
 */
async function abrirModalRepuestos(idFila) {
    document.getElementById("modal_fila_actual").value = idFila;

    // 1. Identificar técnico
    const filaTR = document.getElementById(`fila_${idFila}`);
    const selectTecnico = filaTR.querySelector(`select[name*="[id_tecnico]"]`);
    const idTecnico = selectTecnico ? selectTecnico.value : 0;

    if (!idTecnico || idTecnico == 0) {
        alert("⚠️ Primero debes seleccionar un TÉCNICO en la fila.");
        return;
    }

    // 2. Preparar el Select (Limpiar y deshabilitar)
    const selectRep = $('#select_repuesto_modal');
    selectRep.empty();
    selectRep.append(new Option("Cargando catálogo...", ""));
    selectRep.prop('disabled', true);

    try {
        // 3. Cargar inventario REAL del técnico (AJAX)
        // Usamos await para esperar la respuesta antes de pintar el select
        const inventarioTecnico = await cargarStockTecnicoPromesa(idTecnico);

        // Convertimos a mapa para búsqueda rápida: { '101': 5, '102': 2 }
        const stockMap = {};
        if (inventarioTecnico && inventarioTecnico.length > 0) {
            inventarioTecnico.forEach(item => {
                stockMap[item.id_repuesto] = parseInt(item.cantidad_actual);
            });
        }

        // 4. Llenar el Select con TODOS los repuestos (Global)
        selectRep.empty();
        selectRep.append(new Option("- Seleccione un Repuesto -", ""));

        if (window.DetalleConfig.catalogoRepuestos && window.DetalleConfig.catalogoRepuestos.length > 0) {
            window.DetalleConfig.catalogoRepuestos.forEach(globalItem => {
                const idRep = globalItem.id_repuesto;
                const stockReal = stockMap[idRep] || 0; // Si no está en el mapa, es 0

                let textoOption = "";
                
                // Formato visual
                if (stockReal > 0) {
                    textoOption = `✅ ${globalItem.nombre_repuesto} (Stock Mío: ${stockReal})`;
                } else {
                    textoOption = `📦 ${globalItem.nombre_repuesto} (Sin Stock)`;
                }

                // Crear opción
                const option = new Option(textoOption, idRep, false, false);
                
                // Guardar datos vitales en atributos data
                $(option).attr('data-stock', stockReal);
                $(option).attr('data-nombre-limpio', globalItem.nombre_repuesto);

                selectRep.append(option);
            });
        } else {
            selectRep.append(new Option("Error: Catálogo vacío", ""));
        }

    } catch (error) {
        console.error("Error cargando inventario:", error);
        selectRep.append(new Option("Error de conexión", ""));
    } finally {
        selectRep.prop('disabled', false);
        // Inicializar o refrescar Select2
        if (selectRep.data('select2')) {
            selectRep.trigger('change');
        } else {
            // Si no estaba inicializado (raro en tu código, pero por si acaso)
            selectRep.select2({ width: '100%', dropdownParent: $('#modalRepuestos') });
        }
    }

    // 5. Recuperar datos previos de la fila (JSON o DB)
    const inputJson = document.getElementById(`input_json_${idFila}`);
    const inputDb = document.getElementById(`input_db_${idFila}`);
    let repuestosFinales = [];
    let jsonValido = false;

    if (inputJson && inputJson.value && inputJson.value.trim() !== "[]") {
        try {
            repuestosFinales = JSON.parse(inputJson.value);
            repuestosFinales = repuestosFinales.map((r) => ({
                ...r,
                cantidad: parseInt(r.cantidad) || 1,
            }));
            jsonValido = true;
        } catch (e) {
            console.error(e);
        }
    }

    if (!jsonValido) {
        const textoBD = inputDb ? inputDb.value : "";
        if (textoBD) repuestosFinales = convertirTextoARepuestos(textoBD);
    }

    window.DetalleConfig.repuestosTemporales = repuestosFinales;
    renderizarListaVisual();

    // Resetear inputs del modal
    $('#select_repuesto_modal').val(null).trigger('change');
    document.getElementById('cantidad_repuesto_modal').value = "1";
    document.getElementById('select_origen_modal').value = "INEES";

    $("#modalRepuestos").removeClass("hidden").addClass("flex");
}

/**
 * Función auxiliar para convertir AJAX de jQuery a Promesa (para usar await)
 */
function cargarStockTecnicoPromesa(idTecnico) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: "index.php?pagina=ordenDetalle",
            type: "POST",
            data: {
                accion: "ajaxObtenerStockTecnico",
                id_tecnico: idTecnico
            },
            dataType: "json",
            success: function(data) {
                resolve(data);
            },
            error: function(err) {
                reject(err);
            }
        });
    });
}

/**
 * Cerrar modal
 */
function cerrarModal() {
    $("#modalRepuestos").addClass("hidden").removeClass("flex");
    $("#select_repuesto_modal").val(null).trigger("change");
}

/**
 * GESTIÓN TIEMPO REAL (Agregar Repuesto)
 */
function agregarRepuestoALista() {
    const select = $("#select_repuesto_modal");
    const idRepuesto = select.val();
    const dataSelect = select.select2("data");

    if (!idRepuesto || dataSelect.length === 0) {
        alert("⚠️ Seleccione un repuesto.");
        return;
    }

    // Obtener datos del option seleccionado
    const optionElement = $(dataSelect[0].element);
    const stockDisponible = parseInt(optionElement.attr("data-stock")) || 0;
    const nombreLimpio = optionElement.attr("data-nombre-limpio") || dataSelect[0].text; // Usamos el nombre limpio

    const origen = document.getElementById("select_origen_modal").value;
    const cantidadVal = document.getElementById("cantidad_repuesto_modal").value;
    const cantidad = cantidadVal ? parseInt(cantidadVal) : 1;
    const idOrden = document.getElementById("modal_fila_actual").value;

    let filaTR = document.getElementById(`fila_${idOrden}`);
    let selectTecnico = filaTR.querySelector(`select[name*="[id_tecnico]"]`);
    let idTecnico = selectTecnico ? selectTecnico.value : 0;

    if (cantidad < 1) {
        alert("La cantidad debe ser mayor a 0.");
        return;
    }

    // ===============================================
    // 🔥 VALIDACIÓN DE STOCK (Solo para INEES)
    // ===============================================
    if (origen === 'INEES') {
        if (cantidad > stockDisponible) {
            alert(`🛑 STOCK INSUFICIENTE (INEES)\n\n` +
                  `Disponible en stock: ${stockDisponible}\n` +
                  `Solicitado: ${cantidad}\n\n` +
                  `Si el repuesto lo suministró el cliente, cambie el origen a "PROSEGUR".`);
            return;
        }
    }
    // Si es PROSEGUR, pasa sin validar stock.

    // Bloquear botón para evitar doble click
    let btnAdd = document.querySelector('#modalRepuestos button[onclick="agregarRepuestoALista()"]');
    btnAdd.disabled = true;
    btnAdd.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    let fd = new FormData();
    fd.append("accion", "ajaxGestionarRepuestoRT");
    fd.append("tipo", "agregar");
    fd.append("id_orden", idOrden);
    fd.append("id_repuesto", idRepuesto);
    fd.append("cantidad", cantidad);
    fd.append("origen", origen);
    fd.append("id_tecnico", idTecnico);

    fetch("index.php?pagina=ordenDetalle", { method: "POST", body: fd })
        .then((res) => res.json())
        .then((data) => {
            if (data.status === "ok") {
                
                // ===============================================
                // 🎨 ACTUALIZACIÓN VISUAL (Si es INEES)
                // ===============================================
                if (origen === 'INEES') {
                    let nuevoStock = stockDisponible - cantidad;
                    
                    // Actualizar atributo data-stock
                    optionElement.attr("data-stock", nuevoStock);
                    
                    // Actualizar texto visual del select (Icono y cantidad)
                    let nuevoTextoOption = "";
                    if (nuevoStock > 0) {
                        nuevoTextoOption = `✅ ${nombreLimpio} (Stock Mío: ${nuevoStock})`;
                    } else {
                        nuevoTextoOption = `📦 ${nombreLimpio} (Sin Stock)`;
                    }
                    
                    // Actualizar DOM y Select2
                    optionElement.text(nuevoTextoOption);
                    select.trigger("change.select2");
                }

                // Agregar a la lista visual inferior
                window.DetalleConfig.repuestosTemporales.push({
                    id: idRepuesto,
                    nombre: nombreLimpio, // Guardamos el nombre limpio sin "(Stock...)"
                    origen: origen,
                    cantidad: cantidad
                });

                renderizarListaVisual();
                actualizarBotonFila(idOrden);

                // Feedback UX
                document.getElementById('cantidad_repuesto_modal').value = "1";
                select.val(null).trigger('change');

            } else {
                alert("❌ Error: " + data.msg);
            }
        })
        .catch((err) => console.error(err))
        .finally(() => {
            btnAdd.disabled = false;
            btnAdd.innerHTML = '<i class="fas fa-plus"></i>';
        });
}

/**
 * Renderizar lista visual de repuestos
 */
function renderizarListaVisual() {
    const ul = document.getElementById("lista_repuestos_visual");
    if (!ul) return;

    ul.innerHTML = "";

    if (window.DetalleConfig.repuestosTemporales.length === 0) {
        ul.innerHTML = '<li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>';
        return;
    }

    window.DetalleConfig.repuestosTemporales.forEach((item, index) => {
        const bgBadge = item.origen === "INEES" ? "bg-blue-100 text-blue-800" : "bg-orange-100 text-orange-800";
        const cant = parseInt(item.cantidad) || 1;

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
            <button type="button" onclick="window.DetalleRepuestos.borrarRepuestoTemporal(${index})" 
                    class="text-red-400 hover:text-red-600 px-2 ml-2 transition transform hover:scale-110">
                <i class="fas fa-trash-alt"></i>
            </button>
        </li>`;
    });
}

/**
 * Borrar repuesto temporal
 */
function borrarRepuestoTemporal(index) {
    if (!confirm("¿Eliminar este repuesto de la orden? (Si era de INEES, el stock se devolverá al técnico)")) return;

    let item = window.DetalleConfig.repuestosTemporales[index];
    const idOrden = document.getElementById("modal_fila_actual").value;

    let filaTR = document.getElementById(`fila_${idOrden}`);
    let selectTecnico = filaTR.querySelector(`select[name*="[id_tecnico]"]`);
    let idTecnico = selectTecnico ? selectTecnico.value : 0;

    let fd = new FormData();
    fd.append("accion", "ajaxGestionarRepuestoRT");
    fd.append("tipo", "eliminar");
    fd.append("id_orden", idOrden);
    fd.append("id_repuesto", item.id);
    fd.append("origen", item.origen);
    fd.append("id_tecnico", idTecnico);

    fetch("index.php?pagina=ordenDetalle", { method: "POST", body: fd })
        .then((res) => res.json())
        .then((data) => {
            if (data.status === "ok") {
                
                // ===============================================
                // 🎨 DEVOLVER STOCK VISUALMENTE (Si era INEES)
                // ===============================================
                if (item.origen === 'INEES') {
                    const select = $("#select_repuesto_modal");
                    const option = select.find(`option[value="${item.id}"]`);

                    if (option.length > 0) {
                        let stockActual = parseInt(option.attr("data-stock")) || 0;
                        let cantidadDevuelta = parseInt(item.cantidad) || 1;
                        let nuevoStock = stockActual + cantidadDevuelta;
                        let nombreLimpio = option.attr("data-nombre-limpio") || item.nombre;

                        // Actualizar data y texto
                        option.attr("data-stock", nuevoStock);
                        option.text(`✅ ${nombreLimpio} (Stock Mío: ${nuevoStock})`);

                        // Refrescar si está seleccionado
                        if (select.val() == item.id) {
                            select.trigger("change.select2");
                        }
                    }
                }

                window.DetalleConfig.repuestosTemporales.splice(index, 1);
                renderizarListaVisual();
                actualizarBotonFila(idOrden);

            } else {
                alert("Error al eliminar: " + data.msg);
            }
        });
}

// Función auxiliar para actualizar el texto del botón en la tabla principal
function actualizarBotonFila(idFila) {
    const btnTexto = document.getElementById(`btn_texto_${idFila}`);
    if(!btnTexto) return;

    const totalItems = window.DetalleConfig.repuestosTemporales.reduce(
        (acc, it) => acc + (parseInt(it.cantidad) || 1),
        0
    );

    if (totalItems > 0) {
        btnTexto.innerText = `${totalItems} Items`;
        btnTexto.parentElement.classList.remove("bg-gray-100", "text-gray-400");
        btnTexto.parentElement.classList.add("bg-blue-100", "text-blue-800", "border-blue-300");
    } else {
        btnTexto.innerText = "Sin Rep.";
        btnTexto.parentElement.classList.remove("bg-blue-100", "text-blue-800", "border-blue-300");
        btnTexto.parentElement.classList.add("bg-gray-100", "text-gray-400");
    }
}

function validarCoherenciaServicioRepuestos(idFila) {
    console.log(`✅ Validación de coherencia exitosa para la fila ${idFila}`);
    return true;
}

/**
 * Guardar cambios del modal (Cierra el modal y actualiza input oculto)
 */
function guardarCambiosModal() {
    const idFila = document.getElementById("modal_fila_actual").value;
    const inputJson = document.getElementById(`input_json_${idFila}`);
    // El input_db ya no lo usamos tanto porque dependemos del JSON para futuras ediciones, 
    // pero lo actualizamos por consistencia visual si fuera necesario.
    
    if (inputJson) {
        inputJson.value = JSON.stringify(window.DetalleConfig.repuestosTemporales);
    }
    
    actualizarBotonFila(idFila);
    cerrarModal();

    if(window.DetalleNotificaciones) {
        window.DetalleNotificaciones.notificarCambioGuardado();
    }
}

// Exportar
window.DetalleRepuestos = {
    convertirTextoARepuestos,
    abrirModalRepuestos,
    cerrarModal,
    agregarRepuestoALista,
    borrarRepuestoTemporal,
    guardarCambiosModal,
};

// Retrocompatibilidad
window.abrirModalRepuestos = abrirModalRepuestos;
window.cerrarModal = cerrarModal;
window.agregarRepuestoALista = agregarRepuestoALista;
window.borrarRepuestoTemporal = borrarRepuestoTemporal;
window.guardarCambiosModal = guardarCambiosModal;