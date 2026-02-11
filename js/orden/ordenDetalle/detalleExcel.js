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
 * Exportar Excel Limpio (CORREGIDO ERROR VARIABLE)
 */
function exportarExcelLimpio() {
    if (typeof XLSX === "undefined") {
        alert("Error: Librería SheetJS no cargada.");
        return;
    }

    let tabla = document.getElementById("tablaEdicion");
    let filasHTML = Array.from(tabla.querySelectorAll("tbody tr")).filter(f => f.id.startsWith("fila_"));

    let ArbolDatos = {};
    let contadorFilas = 0;

    // --- PASO 1: EXTRACCIÓN MASIVA ---
    filasHTML.forEach((fila) => {
        contadorFilas++;
        let idFila = fila.id.replace("fila_", "");

        // Extracción de datos
        let txtRemision = ExcelUtils.getInputValue(fila, "[remision]");
        let txtFecha = ExcelUtils.getInputValue(fila, "[fecha_individual]");
        let obs = ExcelUtils.getTextareaValue(fila, "[obs]");

        let cliente = ExcelUtils.getSelectText(fila, "[id_cliente]");
        let punto = ExcelUtils.getSelectText(fila, "[id_punto]");
        let tecnico = ExcelUtils.getSelectText(fila, "[id_tecnico]");
        let servicio = ExcelUtils.getSelectText(fila, "[id_manto]");
        let modalidad = ExcelUtils.getSelectText(fila, "[id_modalidad]");
        let estado = ExcelUtils.getSelectText(fila, "[id_estado]");

        // ⚠️ AQUÍ ESTABA EL DETALLE: La variable se llama 'calif'
        let calif = ExcelUtils.getSelectText(fila, "[id_calif]");

        // Device ID
        let selMaq = fila.querySelector('select[name*="[id_maquina]"]');
        let device_id = "";
        if (selMaq && selMaq.selectedIndex >= 0) {
            device_id = selMaq.options[selMaq.selectedIndex].text.split("(")[0].trim();
        }

        let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
        let tipoMaquinatxt = divTipo ? divTipo.innerText : "";
        let divDelegacion = document.getElementById(`td_delegacion_${idFila}`);
        let delegacion = divDelegacion ? divDelegacion.innerText : "SIN ASIGNAR";

        let txtServicio = servicio.toLowerCase();
        let esPrevBasico = (txtServicio.includes("basico") || txtServicio.includes("básico")) ? "X" : "";
        let esPrevProfundo = (txtServicio.includes("profundo") || txtServicio.includes("completo")) ? "X" : "";
        let esCorrectivo = (txtServicio.includes("correctivo") || txtServicio.includes("reparacion")) ? "X" : "";
        if (!esPrevBasico && !esPrevProfundo && !esCorrectivo && txtServicio.includes("preventivo")) esPrevBasico = "X";

        let inputValor = fila.querySelector('input[name*="[valor]"]');
        let valorRaw = inputValor ? inputValor.value : "0";
        let valorLimpio = valorRaw.toString().replace(/\./g, "").replace(",", ".");
        let valorExcel = parseFloat(valorLimpio) || 0;

        let inputViaticos = document.getElementById(`viaticos_${idFila}`);
        let valorViaticos = inputViaticos ? parseFloat(inputViaticos.value) : 0;

        let inputEntrada = fila.querySelector('input[name*="[entrada]"]');
        let inputSalida = fila.querySelector('input[name*="[salida]"]');
        let horaEntrada = inputEntrada ? inputEntrada.value : "";
        let horaSalida = inputSalida ? inputSalida.value : "";

        let duracion = "";
        if (window.DetalleFechaUtils && window.DetalleFechaUtils.calcularDuracion) {
            duracion = window.DetalleFechaUtils.calcularDuracion(horaEntrada, horaSalida);
        }

        let spanDesplaz = document.getElementById(`desplazamiento_${idFila}`);
        let desplazamiento = spanDesplaz ? spanDesplaz.innerText.replace("Err H.", "") : "";

        let inputRepDB = document.getElementById(`input_db_${idFila}`);
        let repuestos = inputRepDB ? inputRepDB.value : "";
        if (repuestos.match(/Gest\. Repuestos|Items|sin repuestos|ninguno|n\/a|vacío/i)) repuestos = "";

        // --- PASO 2: AGRUPACIÓN ---
        if (!ArbolDatos[delegacion]) ArbolDatos[delegacion] = {};

        let claveGrupo = tecnico + "|" + txtFecha;

        if (!ArbolDatos[delegacion][claveGrupo]) {
            ArbolDatos[delegacion][claveGrupo] = { items: [], totalViatico: 0 };
        }

        // 3. Agregar Datos (CORREGIDO)
        ArbolDatos[delegacion][claveGrupo].items.push({
            device_id, txtRemision, cliente, punto, esPrevBasico, esPrevProfundo, esCorrectivo,
            valor: valorExcel, obs, delegacion, fecha: txtFecha, tecnico, tipoMaquina: tipoMaquinatxt,
            servicio, horaEntrada, horaSalida, duracion, desplazamiento, repuestos, estado,

            // 🔥 CORRECCIÓN AQUÍ: Asignamos la variable 'calif' a la propiedad 'calificacion'
            calificacion: calif,

            modalidad
        });

        if (valorViaticos > 0) {
            ArbolDatos[delegacion][claveGrupo].totalViatico += valorViaticos;
        }
    });

    if (contadorFilas === 0) {
        alert("⚠️ No hay datos visibles para exportar.");
        return;
    }

    // --- PASO 3: GENERAR EXCEL ---
    let workbook = XLSX.utils.book_new();

    for (let delegacion in ArbolDatos) {
        let gruposDeLaDelegacion = ArbolDatos[delegacion];
        let matrizFinal = [];

        // Encabezados
        matrizFinal.push([
            "Device_id", "Número de Remisión", "Cliente", "Nombre Punto", "Preventivo Básico",
            "Preventivo Profundo", "Correctivo", "Tarifa", "Observaciones", "Delegación", "Fecha",
            "Técnico", "Tipo de Máquina", "Tipo de Servicio", "Hora Entrada", "Hora Salida",
            "Duración", "Desplazamiento", "Repuestos", "Estado de la Máquina", "Calificación del Servicio",
            "Modalidad Operativa"
        ]);

        for (let clave in gruposDeLaDelegacion) {
            let grupo = gruposDeLaDelegacion[clave];

            // A. Servicios
            grupo.items.forEach(d => {
                matrizFinal.push([
                    d.device_id, d.txtRemision, d.cliente, d.punto, d.esPrevBasico, d.esPrevProfundo, d.esCorrectivo,
                    d.valor, d.obs, d.delegacion, d.fecha, d.tecnico, d.tipoMaquina, d.servicio,
                    d.horaEntrada, d.horaSalida, d.duracion, d.desplazamiento, d.repuestos, d.estado,
                    d.calificacion, d.modalidad
                ]);
            });

            // B. Viáticos al final del grupo
            if (grupo.totalViatico > 0) {
                matrizFinal.push([
                    "", "", "", "", "", "", "",
                    grupo.totalViatico,
                    ">> TARIFA ADICIONAL POR DÍA (DESPLAZAMIENTO/VIÁTICOS)",
                    delegacion, "", "", "", "VIÁTICOS",
                    "", "", "", "", "", "", "", ""
                ]);
            }
        }

        let ws = XLSX.utils.aoa_to_sheet(matrizFinal);

        // Formato Moneda
        const formatoContabilidad = '_-"$"* #,##0_-;-"$"* #,##0_-;-"$"* "-"??_-;-_-@_-';
        if (ws['!ref']) {
            const range = XLSX.utils.decode_range(ws['!ref']);
            const colTarifa = 7;
            for (let R = range.s.r + 1; R <= range.e.r; ++R) {
                let cellRef = XLSX.utils.encode_cell({ c: colTarifa, r: R });
                if (!ws[cellRef]) ws[cellRef] = { t: 'n', v: 0 };
                ws[cellRef].t = 'n';
                ws[cellRef].z = formatoContabilidad;
            }
        }

        ws["!cols"] = [
            { wch: 15 }, { wch: 12 }, { wch: 25 }, { wch: 25 }, { wch: 8 }, { wch: 8 }, { wch: 8 },
            { wch: 15 }, { wch: 50 }, { wch: 15 }, { wch: 12 }, { wch: 20 }, { wch: 15 }, { wch: 20 },
            { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 12 }, { wch: 40 }, { wch: 15 }, { wch: 15 }, { wch: 15 }
        ];

        let nombreHoja = delegacion.replace(/[:\\/?*\[\]]/g, "").substring(0, 30) || "General";
        XLSX.utils.book_append_sheet(workbook, ws, nombreHoja);
    }

    let nombreArchivo = ExcelUtils.generarNombreArchivo("Reporte_Servicios_Agrupado");
    XLSX.writeFile(workbook, nombreArchivo);
}




// ==========================================
// 2. FUNCIÓN EXPORTAR NOVEDADES (ORDENADO 1-10 + AUTO-ANCHO COLUMNAS)
// ==========================================
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

        // --- VALIDACIÓN: SOLO FILAS CON NOVEDAD ---
        let inputTiene = document.getElementById(`hdn-tiene-${idFila}`);
        if (!inputTiene || inputTiene.value != "1") return;

        // --- DATOS ---

        // Tipo Novedad
        let inputTipo = document.getElementById(`hdn-tipo-${idFila}`);
        let idTipo = inputTipo ? inputTipo.value : "";
        let nombreNovedad = "SIN ESPECIFICAR";
        if (idTipo) {
            let novEncontrada = catalogoNovedades.find(n => n.id_tipo_novedad == idTipo);
            if (novEncontrada) nombreNovedad = novEncontrada.nombre_novedad;
        }

        // Contexto
        let divDel = document.getElementById(`td_delegacion_${idFila}`);
        let delegacion = divDel ? divDel.innerText : "SIN ASIGNAR";

        let cliente = ExcelUtils.getSelectText(fila, "[id_cliente]");
        let punto = ExcelUtils.getSelectText(fila, "[id_punto]");
        let tecnico = ExcelUtils.getSelectText(fila, "[id_tecnico]");

        // Device ID
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

        // --- CONSTRUCCIÓN DEL OBJETO (ORDEN SOLICITADO) ---
        listaNovedades.push({
            "Tipo de Novedad": nombreNovedad,          // 1
            "Descripción del Servicio": obsServicio,   // 2
            "Cliente": cliente,                        // 3
            "Punto": punto,                            // 4
            "Delegación": delegacion,                  // 5
            "Tipo de Máquina": tipoMaq,                // 6
            "Device_id": deviceID,                     // 7
            "Número de Remisión": remision,            // 8
            "Fecha del Servicio": fecha,               // 9
            "Nombre del Técnico": tecnico              // 10
        });
    });

    if (listaNovedades.length === 0) {
        alert("No se encontraron servicios marcados con novedad en la tabla visible.");
        return;
    }

    let wb = XLSX.utils.book_new();
    let ws = XLSX.utils.json_to_sheet(listaNovedades);

    // --- ALGORITMO DE AUTO-AJUSTE DE ANCHO DE COLUMNAS ---
    // Recorremos las claves (encabezados) para calcular el ancho ideal
    let headers = Object.keys(listaNovedades[0]);
    let wscols = headers.map(header => {
        // Empezamos con la longitud del encabezado
        let maxLen = header.length;

        // Revisamos todas las filas para esa columna
        listaNovedades.forEach(row => {
            let val = row[header] ? String(row[header]) : "";
            if (val.length > maxLen) {
                maxLen = val.length;
            }
        });

        // REGLAS: 
        // 1. Si es gigante, lo cortamos visualmente en 60 para no hacer un Excel infinito
        if (maxLen > 60) maxLen = 60;
        // 2. Mínimo 10 para que no quede muy apretado
        if (maxLen < 10) maxLen = 10;

        return { wch: maxLen + 2 }; // +2 para un poco de "aire"
    });

    // Aplicamos los anchos calculados a la hoja
    ws["!cols"] = wscols;

    // (Opcional) Activamos Wrap Text para que si cortamos el ancho, el texto baje
    // Pero NO tocamos la altura de fila (!rows), dejamos que Excel se encargue.
    const range = XLSX.utils.decode_range(ws['!ref']);
    for (let R = range.s.r; R <= range.e.r; ++R) {
        for (let C = range.s.c; C <= range.e.c; ++C) {
            let cell_ref = XLSX.utils.encode_cell({ r: R, c: C });
            if (!ws[cell_ref]) continue;
            if (!ws[cell_ref].s) ws[cell_ref].s = {};

            // Alineación superior y ajuste de texto, pero sin forzar altura
            ws[cell_ref].s.alignment = { wrapText: true, vertical: "top" };
        }
    }

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

// ==========================================
// 3. EXPORTAR AL OBJETO GLOBAL (PARA QUE LOS BOTONES FUNCIONEN)
// ==========================================
window.DetalleExcel = {
    exportarExcelLimpio,
    exportarExcelNovedades
};

// Retrocompatibilidad por si se llaman directamente desde el HTML
window.exportarExcelLimpio = exportarExcelLimpio;
window.exportarExcelNovedades = exportarExcelNovedades;