// ==========================================
// EXPORTACIÓN A EXCEL
// ==========================================

/**
 * Utilidades para extracción de datos
 */
const ExcelUtils = {
    getSelectText: (fila, partialName) => {
    let sel = fila.querySelector(`select[name*="${partialName}"]`);
    if (!sel || sel.selectedIndex < 0) return "";
    return (
        sel.options[sel.selectedIndex].getAttribute("data-full") ||
        sel.options[sel.selectedIndex].text.trim()
    );
    },

    getInputValue: (fila, partialName) => {
    let input = fila.querySelector(`input[name*="${partialName}"]`);
    return input ? input.value : "";
    },

    getTextareaValue: (fila, partialName) => {
    let txt = fila.querySelector(`textarea[name*="${partialName}"]`);
    return txt ? txt.value : "";
    },
};

/**
 * Exportar Excel Limpio
 */
function exportarExcelLimpio() {
    if (typeof XLSX === "undefined") {
    alert("Error: Librería SheetJS no cargada.");
    return;
    }

    let tabla = document.getElementById("tablaEdicion");
    let filas = Array.from(tabla.querySelectorAll("tbody tr"));
    let serviciosPorDelegacion = {};

    filas.forEach((fila) => {
    if (!fila.id.startsWith("fila_")) return;

    let idFila = fila.id.replace("fila_", "");

    // Extraer datos
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

    // Máquina
    let selMaq = fila.querySelector('select[name*="[id_maquina]"]');
    let device_id = "";
    if (selMaq && selMaq.selectedIndex >= 0) {
        device_id = selMaq.options[selMaq.selectedIndex].text
        .split("(")[0]
        .trim();
    }

    let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
    let tipoMaquinatxt = divTipo ? divTipo.innerText : "";

    // Delegación
    let divDelegacion = document.getElementById(`td_delegacion_${idFila}`);
    let delegacion = divDelegacion ? divDelegacion.innerText : "SIN ASIGNAR";

    // Checkboxes de tipo de servicio
    let txtServicio = servicio.toLowerCase();
    let esPrevBasico =
        txtServicio.includes("basico") || txtServicio.includes("básico")
        ? "X"
        : "";
    let esPrevProfundo =
        txtServicio.includes("profundo") || txtServicio.includes("completo")
        ? "X"
        : "";
    let esCorrectivo =
        txtServicio.includes("correctivo") || txtServicio.includes("reparacion")
        ? "X"
        : "";

    if (
        !esPrevBasico &&
        !esPrevProfundo &&
        !esCorrectivo &&
        txtServicio.includes("preventivo")
    ) {
        esPrevBasico = "X";
    }

    // Valor (con comas)
    let inputValor = fila.querySelector('input[name*="[valor]"]');
    let valorRaw = inputValor ? inputValor.value : "0";
    let valorExcel = valorRaw.toString().replace(/\./g, ",");

    // Horas
    let inputEntrada = fila.querySelector('input[name*="[entrada]"]');
    let inputSalida = fila.querySelector('input[name*="[salida]"]');
    let horaEntrada = inputEntrada ? inputEntrada.value : "";
    let horaSalida = inputSalida ? inputSalida.value : "";
    let duracion = window.DetalleFechaUtils.calcularDuracion(
        horaEntrada,
        horaSalida
    );

    let spanDesplaz = document.getElementById(`desplazamiento_${idFila}`);
    let desplazamiento = spanDesplaz
        ? spanDesplaz.innerText.replace("Err H.", "")
        : "";

    // Repuestos
    let inputRepDB = document.getElementById(`input_db_${idFila}`);
    let repuestos = inputRepDB ? inputRepDB.value : "";

    if (
        repuestos.match(
        /Gest\. Repuestos|Items|sin repuestos|ninguno|n\/a|vacío/i
        )
    ) {
        repuestos = "";
    }

    // Objeto de datos
    let datos = {
        device_id,
        txtRemision,
        cliente,
        punto,
        esPrevBasico,
        esPrevProfundo,
        esCorrectivo,
        valor: valorExcel,
        obs,
        delegacion,
        fecha: txtFecha,
        tecnico,
        tipoMaquina: tipoMaquinatxt,
        servicio,
        horaEntrada,
        horaSalida,
        duracion,
        desplazamiento,
        repuestos,
        estado,
        calificacion: calif,
        modalidad,
    };

    if (!serviciosPorDelegacion[delegacion]) {
        serviciosPorDelegacion[delegacion] = [];
    }
    serviciosPorDelegacion[delegacion].push(datos);
    });

  // Generar Excel
    let workbook = XLSX.utils.book_new();
    let hayDatos = Object.keys(serviciosPorDelegacion).length > 0;

    if (!hayDatos) {
    alert("No hay datos válidos para exportar.");
    return;
    }

    for (let delegacion in serviciosPorDelegacion) {
    let lista = serviciosPorDelegacion[delegacion];
    let matriz = [
        [
        "Device_id",
        "Número de Remisión",
        "Cliente",
        "Nombre Punto",
        "Preventivo Básico",
        "Preventivo Profundo",
        "Correctivo",
        "Tarifa",
        "Observaciones",
        "Delegación",
        "Fecha",
        "Técnico",
        "Tipo de Máquina",
        "Tipo de Servicio",
        "Hora Entrada",
        "Hora Salida",
        "Duración",
        "Desplazamiento",
        "Repuestos",
        "Estado de la Máquina",
        "Calificación del Servicio",
        "Modalidad Operativa",
        ],
    ];

    lista.forEach((d) => {
        matriz.push([
        d.device_id,
        d.txtRemision,
        d.cliente,
        d.punto,
        d.esPrevBasico,
        d.esPrevProfundo,
        d.esCorrectivo,
        d.valor,
        d.obs,
        d.delegacion,
        d.fecha,
        d.tecnico,
        d.tipoMaquina,
        d.servicio,
        d.horaEntrada,
        d.horaSalida,
        d.duracion,
        d.desplazamiento,
        d.repuestos,
        d.estado,
        d.calificacion,
        d.modalidad,
        ]);
    });

    let ws = XLSX.utils.aoa_to_sheet(matriz);
    ws["!cols"] = [
        { wch: 15 },
        { wch: 12 },
        { wch: 25 },
        { wch: 25 },
        { wch: 8 },
        { wch: 8 },
        { wch: 8 },
        { wch: 12 },
        { wch: 35 },
        { wch: 15 },
        { wch: 12 },
        { wch: 20 },
        { wch: 15 },
        { wch: 20 },
        { wch: 10 },
        { wch: 10 },
        { wch: 10 },
        { wch: 12 },
        { wch: 40 },
        { wch: 15 },
        { wch: 15 },
        { wch: 15 },
    ];

    let nombreHoja =
            delegacion.replace(/[:\\/?*\[\]]/g, "").substring(0, 30) || "General";
        XLSX.utils.book_append_sheet(workbook, ws, nombreHoja);
    }

    // ==========================================
    // CAMBIO APLICADO: NOMBRE DEL ARCHIVO
    // ==========================================

    // 1. Intentamos sacar la fecha de la URL
    let nombreArchivo = new URLSearchParams(window.location.search).get("fecha");

    // 2. Si no está en la URL, la sacamos del input oculto "fecha_origen" del formulario
    if (!nombreArchivo) {
        const inputFecha = document.querySelector('input[name="fecha_origen"]');
        if (inputFecha && inputFecha.value) {
            nombreArchivo = inputFecha.value;
        }
    }

    // 3. Fallback por seguridad
    if (!nombreArchivo) {
        nombreArchivo = "reporte";
    }

    // Limpiamos espacios y generamos el archivo
    XLSX.writeFile(workbook, `${nombreArchivo.trim()}.xlsx`);
}

/**
 * Exportar Excel de Novedades
 */
function exportarExcelNovedades() {
    if (typeof XLSX === "undefined") {
    alert("Librería SheetJS no cargada.");
    return;
    }

    let tabla = document.getElementById("tablaEdicion");
    let filas = Array.from(tabla.querySelectorAll("tbody tr"));
    let listaNovedades = [];

    filas.forEach((fila) => {
    if (!fila.id.startsWith("fila_")) return;

    let idFila = fila.id.replace("fila_", "");
    let inputNovedad = document.getElementById(`input_novedad_${idFila}`);

    if (!inputNovedad || inputNovedad.value != "1") return;

    // Extraer datos
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
    let obs = txtObs ? txtObs.value : "";

    let inputFecha = fila.querySelector('input[name*="[fecha_individual]"]');
    let fecha = inputFecha ? inputFecha.value : "";

    listaNovedades.push({
        Delegación: delegacion,
        Cliente: cliente,
        Punto: punto,
        "Device ID": deviceID,
        "Tipo Máquina": tipoMaq,
        Remisión: remision,
        Técnico: tecnico,
        Observación: obs,
        Fecha: fecha,
    });
    });

    if (listaNovedades.length === 0) {
    alert("¡Excelente! No hay novedades marcadas para generar reporte.");
    return;
    }

    let wb = XLSX.utils.book_new();
    let ws = XLSX.utils.json_to_sheet(listaNovedades);

    ws["!cols"] = [
    { wch: 15 },
    { wch: 25 },
    { wch: 25 },
    { wch: 15 },
    { wch: 15 },
    { wch: 12 },
    { wch: 20 },
    { wch: 50 },
    { wch: 12 },
    ];

    XLSX.utils.book_append_sheet(wb, ws, "Novedades");

    let fechaParam =
    new URLSearchParams(window.location.search).get("fecha") || "reporte";
    XLSX.writeFile(wb, `Novedades_${fechaParam}.xlsx`);
}

// Exportar
window.DetalleExcel = {
    exportarExcelLimpio,
    exportarExcelNovedades,
};

// Retrocompatibilidad
window.exportarExcelLimpio = exportarExcelLimpio;
window.exportarExcelNovedades = exportarExcelNovedades;
