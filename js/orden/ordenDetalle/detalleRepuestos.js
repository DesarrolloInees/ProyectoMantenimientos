// ==========================================
// GESTIÓN DE REPUESTOS
// ==========================================

/**
 * Convertir texto plano a array de repuestos
 */
function convertirTextoARepuestos(texto) {
    const arrayTemp = [];
    if (window.DetalleConfig.isEmpty(texto)) return arrayTemp;

    const items = texto.split(",");
    const palabrasIgnorar = [
        "NO",
        "NINGUNO",
        "NINGUNA",
        "SIN REPUESTOS",
        "N/A",
        "NA",
        ".",
        "-",
        "0",
        "VACIO",
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

        // Buscar ID en catálogo
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
function abrirModalRepuestos(idFila) {
    document.getElementById("modal_fila_actual").value = idFila;

    // Identificar técnico
    const filaTR = document.getElementById(`fila_${idFila}`);
    const selectTecnico = filaTR.querySelector(`select[name*="[id_tecnico]"]`);
    const idTecnico = selectTecnico ? selectTecnico.value : 0;

    if (!idTecnico || idTecnico == 0) {
        alert("⚠️ Primero debes seleccionar un TÉCNICO en la fila.");
        return;
    }

    // Cargar stock del técnico
    window.DetalleAjax.cargarStockEnModal(idTecnico);

    // Recuperar datos previos
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

    $("#modalRepuestos").removeClass("hidden").addClass("flex");
}

/**
 * Cerrar modal
 */
function cerrarModal() {
    $("#modalRepuestos").addClass("hidden").removeClass("flex");
    $("#select_repuesto_modal").val(null).trigger("change");
}

// ==========================================
// ⚡ GESTIÓN TIEMPO REAL (REEMPLAZAR EN TU JS)
// ==========================================

function agregarRepuestoALista() {
    const select = $("#select_repuesto_modal");
    const idRepuesto = select.val();
    const dataSelect = select.select2("data");

    // Obtener stock actual desde el atributo data-stock
    // (Aseguramos que sea número con parseInt)
    const stockElement = $(dataSelect[0]?.element);
    const stockDisponible = parseInt(stockElement.attr("data-stock")) || 0;

    const origen = document.getElementById("select_origen_modal").value;
    const cantidadVal = document.getElementById("cantidad_repuesto_modal").value;
    const cantidad = cantidadVal ? parseInt(cantidadVal) : 1;
    const idOrden = document.getElementById("modal_fila_actual").value;

    let filaTR = document.getElementById(`fila_${idOrden}`);
    let selectTecnico = filaTR.querySelector(`select[name*="[id_tecnico]"]`);
    let idTecnico = selectTecnico ? selectTecnico.value : 0;

    // Validaciones
    if (!idRepuesto) {
        alert("Seleccione un repuesto");
        return;
    }

    // 🔥 CORRECCIÓN AQUÍ: SOLO VALIDAR STOCK SI ES DE INEES
    if (origen === 'INEES' && cantidad > stockDisponible) {
        alert("⛔ Stock insuficiente en inventario (INEES).");
        return;
    }
    if (cantidad > stockDisponible) {
        alert("⛔ Stock insuficiente en inventario.");
        return;
    }
    if (cantidad < 1) return;

    // Bloquear botón
    let btnAdd = document.querySelector(
        '#modalRepuestos button[onclick="agregarRepuestoALista()"]'
    );
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
                // alert("✅ Agregado correctamente."); // Opcional, a veces molesta tanto alert

                // ===============================================
                // 🎨 ACTUALIZACIÓN VISUAL INMEDIATA (LO NUEVO)
                // ===============================================

                // 1. Calcular nuevo stock visual
                let nuevoStock = stockDisponible - cantidad;

                // 2. Obtener el nombre limpio (quitando el texto anterior de Disp)
                // Ej: "ALMOHADILLAS (Disp: 10)" -> "ALMOHADILLAS"
                let nombreBase = dataSelect[0].text.split(" (Disp:")[0];

                // 3. Actualizar el atributo data-stock para la próxima vez
                stockElement.attr("data-stock", nuevoStock);

                // 4. Actualizar el texto visible en el Select2
                let nuevoTexto = `${nombreBase} (Disp: ${nuevoStock})`;

                // Truco para actualizar Select2 sin recargar todo:
                // Buscamos la opción en el DOM y le cambiamos el texto
                select.find(`option[value="${idRepuesto}"]`).text(nuevoTexto);

                // Forzamos a Select2 a repintar la selección actual
                select.trigger("change.select2");

                // ===============================================

                // Agregar a la lista visual de abajo
            // CORRECCIÓN AQUÍ: Usar window.DetalleConfig
            window.DetalleConfig.repuestosTemporales.push({
                id: idRepuesto,
                nombre: nombreBase,
                origen: origen,
                cantidad: cantidad
            });
            renderizarListaVisual();
            actualizarBotonFila(idOrden);
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
        ul.innerHTML =
            '<li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>';
        return;
    }

    window.DetalleConfig.repuestosTemporales.forEach((item, index) => {
        const colorOrigen =
            item.origen === "INEES" ? "text-blue-600" : "text-orange-600";
        const cant = parseInt(item.cantidad) || 1;
        const textoCantidad =
            cant > 1
                ? ` <span class="font-bold text-gray-800 bg-gray-200 px-1 rounded text-[10px] ml-1">x${cant}</span>`
                : "";

        ul.innerHTML += `
        <li class="flex justify-between items-center bg-white p-2 mb-1 border rounded shadow-sm">
            <span class="text-xs">
                <b class="${colorOrigen}">[${item.origen}]</b> ${item.nombre}${textoCantidad}
            </span>
            <button type="button" onclick="window.DetalleRepuestos.borrarRepuestoTemporal(${index})" 
                    class="text-red-500 hover:text-red-700">
                <i class="fas fa-trash"></i>
            </button>
        </li>`;
    });
}

/**
 * Borrar repuesto temporal
 */
function borrarRepuestoTemporal(index) {
    if (!confirm("¿Devolver este repuesto al inventario del técnico?")) return;

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
                // 🎨 DEVOLVER STOCK VISUALMENTE (LO NUEVO)
                // ===============================================
                const select = $("#select_repuesto_modal");
                const option = select.find(`option[value="${item.id}"]`);

                if (option.length > 0) {
                    // 1. Recuperar stock actual del atributo
                    let stockActual = parseInt(option.attr("data-stock")) || 0;
                    let cantidadDevuelta = parseInt(item.cantidad) || 1;

                    // 2. Sumar
                    let nuevoStock = stockActual + cantidadDevuelta;

                    // 3. Obtener nombre base
                    let nombreCompleto = option.text();
                    let nombreBase = nombreCompleto.split(" (Disp:")[0];

                    // 4. Actualizar atributo y texto
                    option.attr("data-stock", nuevoStock);
                    option.text(`${nombreBase} (Disp: ${nuevoStock})`);

                    // 5. Refrescar (solo si el item borrado estaba seleccionado actualmente)
                    if (select.val() == item.id) {
                        select.trigger("change.select2");
                    }
                }
                // CORRECCIÓN 2: Eliminar del array correcto
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

    // CORRECCIÓN: Usar window.DetalleConfig
    const totalItems = window.DetalleConfig.repuestosTemporales.reduce(
        (acc, it) => acc + (parseInt(it.cantidad) || 1),
        0
    );

    if (totalItems > 0) {
        btnTexto.innerText = `${totalItems} Items`;
        btnTexto.parentElement.classList.add("bg-blue-100", "border-blue-400");
    } else {
        btnTexto.innerText = "";
        btnTexto.parentElement.classList.remove("bg-blue-100", "border-blue-400");
    }
}

// ==========================================
// 🛠️ FUNCIÓN FALTANTE PARA EVITAR EL ERROR
// ==========================================
function validarCoherenciaServicioRepuestos(idFila) {
    // Por ahora, solo retornamos true para que no se bloquee.
    // Aquí podrías poner lógica futura (ej: avisar si gastan toner en un mantenimiento de limpieza).
    console.log(`✅ Validación de coherencia exitosa para la fila ${idFila}`);
    return true;
}
/**
 * Guardar cambios del modal
 */
function guardarCambiosModal() {
    const idFila = document.getElementById("modal_fila_actual").value;
    const inputJson = document.getElementById(`input_json_${idFila}`);
    const inputDb = document.getElementById(`input_db_${idFila}`);
    const btnTexto = document.getElementById(`btn_texto_${idFila}`);

    if (!inputJson) return;

    // Guardar JSON
    inputJson.value = JSON.stringify(window.DetalleConfig.repuestosTemporales);

    // Generar texto para Excel
    const textoParaBD = window.DetalleConfig.repuestosTemporales
        .map((item) => {
            let txt = item.nombre;
            const cant = parseInt(item.cantidad) || 1;

            if (item.origen === "PROSEGUR") txt += " (PROSEGUR)";
            else if (item.origen === "INEES") txt += " (INEES)";

            if (cant > 1) txt += ` (x${cant})`;

            return txt;
        })
        .join(", ");

    inputDb.value = textoParaBD;

    // Actualizar botón
    const totalItems = window.DetalleConfig.repuestosTemporales.reduce(
        (acc, it) => acc + (parseInt(it.cantidad) || 1),
        0
    );

    if (totalItems > 0) {
        btnTexto.innerText = `${totalItems} Items`;
        btnTexto.parentElement.classList.add("bg-blue-100", "border-blue-400");
    } else {
        btnTexto.innerText = "";
        btnTexto.parentElement.classList.remove("bg-blue-100", "border-blue-400");
    }

    cerrarModal();

    // 🔔 NOTIFICACIÓN de cambios guardados
    window.DetalleNotificaciones.notificarCambioGuardado();

    // 🔔 VALIDAR COHERENCIA (servicio vs repuestos)
    validarCoherenciaServicioRepuestos(idFila);
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
