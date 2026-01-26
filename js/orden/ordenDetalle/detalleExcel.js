// ==========================================
// EXPORTACIÓN A EXCEL (ADAPTADA A BÚSQUEDA)
// ==========================================

/**
 * Utilidades para extracción de datos
 */
const ExcelUtils = {
    getSelectText: (fila, partialName) => {
        let sel = fila.querySelector(`select[name*="${partialName}"]`);
        if (!sel || sel.selectedIndex < 0) return "";
        
        // Intentar sacar del atributo data-full, sino del texto visible
        let text = "";
        if (sel.options[sel.selectedIndex].hasAttribute("data-full")) {
            text = sel.options[sel.selectedIndex].getAttribute("data-full");
        } else {
            text = sel.options[sel.selectedIndex].text;
        }
        return text.trim();
    },

    getInputValue: (fila, partialName) => {
        let input = fila.querySelector(`input[name*="${partialName}"]`);
        return input ? input.value : "";
    },

    getTextareaValue: (fila, partialName) => {
        let txt = fila.querySelector(`textarea[name*="${partialName}"]`);
        return txt ? txt.value : "";
    },
    
    // Nueva utilidad para generar nombre de archivo dinámico
    generarNombreArchivo: (prefijo) => {
        // 1. Verificamos si estamos en modo búsqueda (mirando los inputs del buscador)
        const clienteSelect = document.getElementById('busqCliente');
        const remisionInput = document.getElementById('busqRemision');
        
        let detalleNombre = "";

        if (clienteSelect && clienteSelect.value) {
            // Si hay cliente seleccionado, usamos su nombre
            let nombreCliente = clienteSelect.options[clienteSelect.selectedIndex].text;
            detalleNombre = `_${nombreCliente.replace(/[^a-zA-Z0-9]/g, "")}`; // Limpiar caracteres raros
        } else if (remisionInput && remisionInput.value) {
            // Si es por remisión
            detalleNombre = `_Remision_${remisionInput.value}`;
        } else {
            // Si no es búsqueda, usamos la fecha como antes
            let fechaUrl = new URLSearchParams(window.location.search).get("fecha");
            if (!fechaUrl) {
                const inputFecha = document.querySelector('input[name="fecha_origen"]');
                if (inputFecha) fechaUrl = inputFecha.value;
            }
            detalleNombre = fechaUrl ? `_${fechaUrl}` : "_General";
        }

        return `${prefijo}${detalleNombre}.xlsx`;
    }
};

/**
 * Exportar Excel Limpio (Lo que se ve en pantalla)
 */
function exportarExcelLimpio() {
    if (typeof XLSX === "undefined") {
        alert("Error: Librería SheetJS no cargada.");
        return;
    }

    let tabla = document.getElementById("tablaEdicion");
    // 🔥 CLAVE: Esto solo toma las filas que existen en la tabla en ese momento.
    // Si filtraste por búsqueda, solo habrá esas filas.
    let filas = Array.from(tabla.querySelectorAll("tbody tr")); 
    let serviciosPorDelegacion = {};
    let contadorFilas = 0;

    filas.forEach((fila) => {
        if (!fila.id.startsWith("fila_")) return;

        contadorFilas++;
        let idFila = fila.id.replace("fila_", "");

        // --- Extracción de datos (Idéntico a tu lógica anterior) ---
        let txtRemision = ExcelUtils.getInputValue(fila, "[remision]");
        let txtFecha = ExcelUtils.getInputValue(fila, "[fecha_individual]");
        let obs = ExcelUtils.getTextareaValue(fila, "[obs]");

        let cliente = ExcelUtils.getSelectText(fila, "[id_cliente]");
        let punto = ExcelUtils.getSelectText(fila, "[id_punto]");
        let tecnico = ExcelUtils.getSelectText(fila, "[id_tecnico]");
        let servicio = ExcelUtils.getSelectText(fila, "[id_manto]");
        let modalidad = ExcelUtils.getSelectText(fila, "[id_modalidad]");
        let estado = ExcelUtils.getSelectText(fila, "[id_estado]");
        let calif = ExcelUtils.getSelectText(fila, "[id_calif]");

        let selMaq = fila.querySelector('select[name*="[id_maquina]"]');
        let device_id = "";
        if (selMaq && selMaq.selectedIndex >= 0) {
            device_id = selMaq.options[selMaq.selectedIndex].text.split("(")[0].trim();
        }

        let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
        let tipoMaquinatxt = divTipo ? divTipo.innerText : "";

        let divDelegacion = document.getElementById(`td_delegacion_${idFila}`);
        let delegacion = divDelegacion ? divDelegacion.innerText : "SIN ASIGNAR";

        // Checkboxes Lógicos
        let txtServicio = servicio.toLowerCase();
        let esPrevBasico = (txtServicio.includes("basico") || txtServicio.includes("básico")) ? "X" : "";
        let esPrevProfundo = (txtServicio.includes("profundo") || txtServicio.includes("completo")) ? "X" : "";
        let esCorrectivo = (txtServicio.includes("correctivo") || txtServicio.includes("reparacion")) ? "X" : "";

        if (!esPrevBasico && !esPrevProfundo && !esCorrectivo && txtServicio.includes("preventivo")) {
            esPrevBasico = "X";
        }

        // Valores Numéricos
        let inputValor = fila.querySelector('input[name*="[valor]"]');
        let valorRaw = inputValor ? inputValor.value : "0";
        let valorLimpio = valorRaw.toString().replace(/\./g, "").replace(",", ".");
        let valorExcel = parseFloat(valorLimpio) || 0;

        // Tiempos
        let inputEntrada = fila.querySelector('input[name*="[entrada]"]');
        let inputSalida = fila.querySelector('input[name*="[salida]"]');
        let horaEntrada = inputEntrada ? inputEntrada.value : "";
        let horaSalida = inputSalida ? inputSalida.value : "";
        
        // Aseguramos que DetalleFechaUtils exista
        let duracion = "";
        if(window.DetalleFechaUtils && window.DetalleFechaUtils.calcularDuracion){
             duracion = window.DetalleFechaUtils.calcularDuracion(horaEntrada, horaSalida);
        }

        let spanDesplaz = document.getElementById(`desplazamiento_${idFila}`);
        let desplazamiento = spanDesplaz ? spanDesplaz.innerText.replace("Err H.", "") : "";

        // Repuestos
        let inputRepDB = document.getElementById(`input_db_${idFila}`);
        let repuestos = inputRepDB ? inputRepDB.value : "";
        if (repuestos.match(/Gest\. Repuestos|Items|sin repuestos|ninguno|n\/a|vacío/i)) {
            repuestos = "";
        }

        let datos = {
            device_id, txtRemision, cliente, punto, esPrevBasico, esPrevProfundo, esCorrectivo,
            valor: valorExcel, obs, delegacion, fecha: txtFecha, tecnico, tipoMaquina: tipoMaquinatxt,
            servicio, horaEntrada, horaSalida, duracion, desplazamiento, repuestos, estado,
            calificacion: calif, modalidad
        };

        if (!serviciosPorDelegacion[delegacion]) {
            serviciosPorDelegacion[delegacion] = [];
        }
        serviciosPorDelegacion[delegacion].push(datos);
    });

    if (contadorFilas === 0) {
        alert("⚠️ No hay datos visibles para exportar. Realiza una búsqueda primero.");
        return;
    }

    let workbook = XLSX.utils.book_new();

    for (let delegacion in serviciosPorDelegacion) {
        let lista = serviciosPorDelegacion[delegacion];
        let matriz = [[
            "Device_id", "Número de Remisión", "Cliente", "Nombre Punto", "Preventivo Básico",
            "Preventivo Profundo", "Correctivo", "Tarifa", "Observaciones", "Delegación", "Fecha",
            "Técnico", "Tipo de Máquina", "Tipo de Servicio", "Hora Entrada", "Hora Salida",
            "Duración", "Desplazamiento", "Repuestos", "Estado de la Máquina", "Calificación del Servicio",
            "Modalidad Operativa"
        ]];

        lista.forEach((d) => {
            matriz.push([
                d.device_id, d.txtRemision, d.cliente, d.punto, d.esPrevBasico, d.esPrevProfundo, d.esCorrectivo,
                d.valor, d.obs, d.delegacion, d.fecha, d.tecnico, d.tipoMaquina, d.servicio,
                d.horaEntrada, d.horaSalida, d.duracion, d.desplazamiento, d.repuestos, d.estado,
                d.calificacion, d.modalidad
            ]);
        });

        let ws = XLSX.utils.aoa_to_sheet(matriz);

        // Formato Contabilidad
        const formatoContabilidad = '_-"$"* #,##0_-;-"$"* #,##0_-;-"$"* "-"??_-;-_-@_-';
        const range = XLSX.utils.decode_range(ws['!ref']);
        const colTarifa = 7; 

        for (let R = range.s.r + 1; R <= range.e.r; ++R) {
            let cellRef = XLSX.utils.encode_cell({ c: colTarifa, r: R });
            if (ws[cellRef]) {
                ws[cellRef].t = 'n';
                ws[cellRef].z = formatoContabilidad;
            }
        }
        
        // Anchos de columna
        ws["!cols"] = [
            { wch: 15 }, { wch: 12 }, { wch: 25 }, { wch: 25 }, { wch: 8 }, { wch: 8 }, { wch: 8 },
            { wch: 12 }, { wch: 35 }, { wch: 15 }, { wch: 12 }, { wch: 20 }, { wch: 15 }, { wch: 20 },
            { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 12 }, { wch: 40 }, { wch: 15 }, { wch: 15 }, { wch: 15 }
        ];

        let nombreHoja = delegacion.replace(/[:\\/?*\[\]]/g, "").substring(0, 30) || "General";
        XLSX.utils.book_append_sheet(workbook, ws, nombreHoja);
    }

    // 🔥 NOMBRE DE ARCHIVO DINÁMICO
    let nombreArchivo = ExcelUtils.generarNombreArchivo("Reporte");
    XLSX.writeFile(workbook, nombreArchivo);
}

/**
 * Exportar Excel de Novedades (ADAPTADO A BÚSQUEDA)
 */
function exportarExcelNovedades() {
    if (typeof XLSX === "undefined") {
        alert("Librería SheetJS no cargada.");
        return;
    }

    let tabla = document.getElementById("tablaEdicion");
    let filas = Array.from(tabla.querySelectorAll("tbody tr"));
    let listaNovedades = [];
    const catalogoNovedades = window.DetalleConfig.listaNovedades || [];
    let contadorNovedades = 0;

    filas.forEach((fila) => {
        if (!fila.id.startsWith("fila_")) return;
        let idFila = fila.id.replace("fila_", "");

        // Verificar si tiene novedad (solo filas visibles)
        let inputTiene = document.getElementById(`hdn-tiene-${idFila}`);
        if (!inputTiene || inputTiene.value != "1") return;

        contadorNovedades++;

        let inputTipo = document.getElementById(`hdn-tipo-${idFila}`);
        let idTipo = inputTipo ? inputTipo.value : "";
        
        let nombreNovedad = "SIN ESPECIFICAR";
        if (idTipo) {
            let novEncontrada = catalogoNovedades.find(n => n.id_tipo_novedad == idTipo);
            if (novEncontrada) nombreNovedad = novEncontrada.nombre_novedad;
        }

        let divDel = document.getElementById(`td_delegacion_${idFila}`);
        let delegacion = divDel ? divDel.innerText : "SIN ASIGNAR";
        let cliente = ExcelUtils.getSelectText(fila, "[id_cliente]");
        let punto = ExcelUtils.getSelectText(fila, "[id_punto]");
        let tecnico = ExcelUtils.getSelectText(fila, "[id_tecnico]");
        let selMaq = fila.querySelector('select[name*="[id_maquina]"]');
        let deviceID = (selMaq && selMaq.selectedIndex >= 0) ? selMaq.options[selMaq.selectedIndex].text.split("(")[0].trim() : "";
        let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
        let tipoMaq = divTipo ? divTipo.innerText : "";
        let inputRem = fila.querySelector('input[name*="[remision]"]');
        let remision = inputRem ? inputRem.value : "";
        let txtObs = fila.querySelector('textarea[name*="[obs]"]');
        let obsServicio = txtObs ? txtObs.value : "";
        let inputFecha = fila.querySelector('input[name*="[fecha_individual]"]');
        let fecha = inputFecha ? inputFecha.value : "";

        listaNovedades.push({
            "Fecha": fecha, "Delegación": delegacion, "Cliente": cliente, "Punto": punto,
            "Técnico": tecnico, "Motivo Novedad": nombreNovedad, "Device ID": deviceID,
            "Tipo Máquina": tipoMaq, "Remisión": remision, "Obs. Servicio": obsServicio
        });
    });

    if (listaNovedades.length === 0) {
        alert("⚠️ No se encontraron novedades en los resultados actuales.");
        return;
    }

    let wb = XLSX.utils.book_new();
    let ws = XLSX.utils.json_to_sheet(listaNovedades);

    ws["!cols"] = [
        { wch: 12 }, { wch: 15 }, { wch: 25 }, { wch: 25 }, { wch: 20 },
        { wch: 25 }, { wch: 12 }, { wch: 20 }, { wch: 12 }, { wch: 40 }
    ];

    XLSX.utils.book_append_sheet(workbook, ws, "Novedades");

    // 🔥 NOMBRE DE ARCHIVO DINÁMICO
    let nombreArchivo = ExcelUtils.generarNombreArchivo("Novedades");
    XLSX.writeFile(workbook, nombreArchivo);
}

// Exportar al objeto global
window.DetalleExcel = { exportarExcelLimpio, exportarExcelNovedades };
window.exportarExcelLimpio = exportarExcelLimpio;
window.exportarExcelNovedades = exportarExcelNovedades;



/**
 * Exportar Excel de Novedades (ORDEN CORREGIDO: TIPO -> OBS -> DETALLES)
 */
function exportarExcelNovedades() {
    if (typeof XLSX === "undefined") {
        alert("Librería SheetJS no cargada.");
        return;
    }

    let tabla = document.getElementById("tablaEdicion");
    let filas = Array.from(tabla.querySelectorAll("tbody tr"));
    let listaNovedades = [];

    // Obtenemos el catálogo de novedades para traducir ID -> NOMBRE
    const catalogoNovedades = window.DetalleConfig.listaNovedades || [];

    filas.forEach((fila) => {
        if (!fila.id.startsWith("fila_")) return;

        let idFila = fila.id.replace("fila_", "");

        // 1. Verificamos si tiene novedad marcada
        let inputTiene = document.getElementById(`hdn-tiene-${idFila}`);
        if (!inputTiene || inputTiene.value != "1") return;

        // 2. Recuperar el ID y Nombre del tipo de novedad
        let inputTipo = document.getElementById(`hdn-tipo-${idFila}`);
        let idTipo = inputTipo ? inputTipo.value : "";
        
        let nombreNovedad = "SIN ESPECIFICAR";
        if (idTipo) {
            let novEncontrada = catalogoNovedades.find(n => n.id_tipo_novedad == idTipo);
            if (novEncontrada) {
                nombreNovedad = novEncontrada.nombre_novedad;
            }
        }

        // 3. Extraer resto de datos
        let divDel = document.getElementById(`td_delegacion_${idFila}`);
        let delegacion = divDel ? divDel.innerText : "SIN ASIGNAR";

        let cliente = ExcelUtils.getSelectText(fila, "[id_cliente]");
        let punto = ExcelUtils.getSelectText(fila, "[id_punto]");
        let tecnico = ExcelUtils.getSelectText(fila, "[id_tecnico]");

        let selMaq = fila.querySelector('select[name*="[id_maquina]"]');
        let deviceID = "";
        if (selMaq && selMaq.selectedIndex >= 0) {
            deviceID = selMaq.options[selMaq.selectedIndex].text.split("(")[0].trim();
        }

        let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
        let tipoMaq = divTipo ? divTipo.innerText : "";

        let inputRem = fila.querySelector('input[name*="[remision]"]');
        let remision = inputRem ? inputRem.value : "";

        let txtObs = fila.querySelector('textarea[name*="[obs]"]');
        let obsServicio = txtObs ? txtObs.value : "";

        let inputFecha = fila.querySelector('input[name*="[fecha_individual]"]');
        let fecha = inputFecha ? inputFecha.value : "";

        // --- AQUÍ ESTÁ EL CAMBIO DE ORDEN ---
        // Columna 1: Tipo Novedad
        // Columna 2: Observación
        // Resto: Contexto
        listaNovedades.push({
            "Tipo de Novedad": nombreNovedad,      // COLUMNA 1
            "Observación / Qué se hizo": obsServicio, // COLUMNA 2
            "Remisión": remision,
            "Técnico": tecnico,
            "Cliente": cliente,
            "Punto": punto,
            "Delegación": delegacion,
            "Device ID": deviceID,
            "Tipo Máquina": tipoMaq,
            "Fecha": fecha
            
        });
    });

    if (listaNovedades.length === 0) {
        alert("No se encontraron servicios marcados con novedad en la tabla visible.");
        return;
    }

    let wb = XLSX.utils.book_new();
    let ws = XLSX.utils.json_to_sheet(listaNovedades);

    // Ajustar ancho de columnas (Indices actualizados al nuevo orden)
    ws["!cols"] = [
        { wch: 30 }, // A: Tipo de Novedad (Ancho generoso)
        { wch: 50 }, // B: Observación (Muy ancho para leer bien)
        { wch: 12 }, // C: Fecha
        { wch: 20 }, // D: Técnico
        { wch: 25 }, // E: Cliente
        { wch: 25 }, // F: Punto
        { wch: 15 }, // G: Delegación
        { wch: 15 }, // H: Device ID
        { wch: 20 }, // I: Tipo Maquina
        { wch: 12 }  // J: Remisión
    ];

    XLSX.utils.book_append_sheet(wb, ws, "Novedades");

    // Nombre del archivo
    let fechaNombre = new URLSearchParams(window.location.search).get("fecha");
    if (!fechaNombre) {
        const inputFecha = document.querySelector('input[name="fecha_origen"]');
        if (inputFecha && inputFecha.value) fechaNombre = inputFecha.value;
    }
    if (!fechaNombre) fechaNombre = "Reporte";

    XLSX.writeFile(wb, `Novedades_${fechaNombre.trim()}.xlsx`);
}
// Exportar
window.DetalleExcel = {
    exportarExcelLimpio,
    exportarExcelNovedades,
};

// Retrocompatibilidad
window.exportarExcelLimpio = exportarExcelLimpio;
window.exportarExcelNovedades = exportarExcelNovedades;
