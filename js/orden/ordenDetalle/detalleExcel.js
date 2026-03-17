// ==========================================
// detalleExcel.js — reescrito con datos del servidor
// Estrategia: igual que ordenReporteVista.php
// NO lee el DOM. Pide los datos limpios al backend via AJAX.
// ==========================================

function _calcularDiferenciaHoras(horaInicio, horaFin) {
    if (!horaInicio || !horaFin) return "";
    let d1 = new Date(`2000-01-01T${horaInicio}`);
    let d2 = new Date(`2000-01-01T${horaFin}`);
    if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return "";
    let diffMs = d2 - d1;
    if (diffMs < 0) return "";
    let diffMins = Math.floor(diffMs / 60000);
    let horas = Math.floor(diffMins / 60);
    let mins = diffMins % 60;
    return `${String(horas).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}

function _obtenerFechaActiva() {
    let fechaUrl = new URLSearchParams(window.location.search).get("fecha");
    if (fechaUrl) return fechaUrl;
    let inputFecha = document.querySelector('input[name="fecha_origen"]');
    if (inputFecha && inputFecha.value) return inputFecha.value;
    return new Date().toISOString().split('T')[0];
}

function _obtenerBaseUrl() {
    if (typeof BASE_URL !== 'undefined') return BASE_URL + 'ordenDetalle';
    let path = window.location.pathname.split('/');
    path.pop();
    return path.join('/') + '/ordenDetalle';
}

// ─────────────────────────────────────────────────────────────
// 1. EXPORTAR EXCEL LIMPIO (BOTÓN VERDE)
// ─────────────────────────────────────────────────────────────
function exportarExcelLimpio() {
    if (typeof XLSX === "undefined") { alert("Error: Librería SheetJS no cargada."); return; }

    let fecha = _obtenerFechaActiva();
    let baseUrl = _obtenerBaseUrl();
    let btn = document.querySelector('[onclick*="exportarExcelLimpio"]');
    if (btn) { btn._htmlOrig = btn.innerHTML; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...'; btn.disabled = true; }

    fetch(baseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ accion: 'ajaxExportarDetalle', fecha: fecha })
    })
        .then(r => r.json())
        .then(response => {
            if (response.status !== 'ok' || !response.datos.length) {
                alert('No se encontraron registros para esta fecha.');
                return;
            }
            _generarExcelServicios(response.datos, fecha);
        })
        .catch(err => { console.error(err); alert('Error de conexión al exportar.'); })
        .finally(() => {
            if (btn) { btn.innerHTML = btn._htmlOrig || '<i class="fas fa-file-excel"></i> Excel'; btn.disabled = false; }
        });
}

// ─────────────────────────────────────────────────────────────
// 2. EXPORTAR EXCEL NOVEDADES (BOTÓN ROJO)
// ─────────────────────────────────────────────────────────────
function exportarExcelNovedades() {
    if (typeof XLSX === "undefined") { alert("Librería SheetJS no cargada."); return; }

    let fecha = _obtenerFechaActiva();
    let baseUrl = _obtenerBaseUrl();
    let btn = document.querySelector('[onclick*="exportarExcelNovedades"]');
    if (btn) { btn._htmlOrig = btn.innerHTML; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...'; btn.disabled = true; }

    fetch(baseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ accion: 'ajaxExportarDetalle', fecha: fecha })
    })
        .then(r => r.json())
        .then(response => {
            if (response.status !== 'ok') { alert('Error al obtener datos.'); return; }
            let conNovedad = response.datos.filter(d => parseInt(d.tiene_novedad) === 1 || d.ids_novedades);
            if (!conNovedad.length) { alert('No hay servicios con novedad en esta fecha.'); return; }
            _generarExcelNovedades(conNovedad, fecha);
        })
        .catch(err => { console.error(err); alert('Error de conexión al exportar.'); })
        .finally(() => {
            if (btn) { btn.innerHTML = btn._htmlOrig || '<i class="fas fa-file-contract"></i> Novedades'; btn.disabled = false; }
        });
}

// ─────────────────────────────────────────────────────────────
// INTERNO: generar workbook de servicios por delegación
// ─────────────────────────────────────────────────────────────
function _generarExcelServicios(datos, fecha) {
    let wb = XLSX.utils.book_new();
    let porDelegacion = {};
    let viaticoPendiente = null;
    let prevTecnico = null;
    let prevFecha = null;
    let prevHoraSalida = null;

    datos.forEach((d, index) => {
        let delegacion = (d.delegacion || "SIN ASIGNAR").replace(/[\r\n\t\s]+/g, ' ').trim().toUpperCase();
        if (!porDelegacion[delegacion]) porDelegacion[delegacion] = [];

        // Desplazamiento
        let desplazamiento = "";
        if (d.nombre_tecnico === prevTecnico && d.fecha_visita === prevFecha && prevHoraSalida) {
            desplazamiento = _calcularDiferenciaHoras(prevHoraSalida, d.hora_entrada);
        }
        prevTecnico = d.nombre_tecnico;
        prevFecha = d.fecha_visita;
        prevHoraSalida = d.hora_salida;

        // Tipo servicio → X
        let txtServ = (d.tipo_servicio || "").toLowerCase();
        let prevBasico = (txtServ.includes("basico") || txtServ.includes("básico")) ? "X" : "";
        let prevProfundo = (txtServ.includes("profundo") || txtServ.includes("completo")) ? "X" : "";
        let correctivo = (txtServ.includes("correctivo") || txtServ.includes("reparacion")) ? "X" : "";
        if (!prevBasico && !prevProfundo && !correctivo && txtServ.includes("preventivo")) prevBasico = "X";

        // Duración
        let duracion = d.tiempo_servicio || "";
        if (!duracion || duracion === '00:00' || duracion === '00:00:00') {
            duracion = _calcularDiferenciaHoras(d.hora_entrada, d.hora_salida);
        }

        let valorServicio = parseFloat(d.valor_servicio) || 0;
        let valorViaticos = parseFloat(d.valor_viaticos) || 0;

        porDelegacion[delegacion].push({
            device_id: d.device_id || "",
            remision: d.numero_remision || "",
            cliente: d.nombre_cliente || "",
            punto: d.nombre_punto || "",
            prevBasico, prevProfundo, correctivo,
            valor: valorServicio,
            obs: d.que_se_hizo || d.actividades_realizadas || "",
            delegacion,
            fecha: d.fecha_visita || "",
            tecnico: d.nombre_tecnico || "",
            tipoMaquina: d.nombre_tipo_maquina || "",
            tipoServicio: d.tipo_servicio || "",
            horaEntrada: d.hora_entrada || "",
            horaSalida: d.hora_salida || "",
            duracion,
            desplazamiento,
            repuestos: d.repuestos_texto || "",
            estado: d.estado_maquina || d.nombre_estado || "",
            calificacion: d.nombre_calificacion || "",
            modalidad: d.tipo_zona || ""
        });

        if (valorViaticos > 0) {
            viaticoPendiente = {
                device_id: "", remision: "", cliente: "", punto: "",
                prevBasico: "", prevProfundo: "", correctivo: "",
                valor: valorViaticos, obs: "TARIFA ADICIONAL POR DÍA",
                delegacion: "", fecha: "", tecnico: "", tipoMaquina: "",
                tipoServicio: "", horaEntrada: "", horaSalida: "",
                duracion: "", desplazamiento: "", repuestos: "",
                estado: "", calificacion: "", modalidad: ""
            };
        }

        let sig = datos[index + 1];
        let cerrar = !sig
            || sig.fecha_visita !== d.fecha_visita
            || sig.nombre_tecnico !== d.nombre_tecnico
            || (sig.delegacion || "SIN ASIGNAR").replace(/[\r\n\t\s]+/g, ' ').trim().toUpperCase() !== delegacion;

        if (cerrar && viaticoPendiente) {
            porDelegacion[delegacion].push(viaticoPendiente);
            viaticoPendiente = null;
        }
    });

    for (let del in porDelegacion) {
        let filas = porDelegacion[del];
        let matriz = [[
            "Device_id", "Número de Remisión", "Cliente", "Nombre Punto",
            "Preventivo Básico", "Preventivo Profundo", "Correctivo", "Tarifa",
            "Observaciones", "Delegación", "Fecha", "Técnico",
            "Tipo de Máquina", "Tipo de Servicio", "Hora Entrada", "Hora Salida",
            "Duración", "Desplazamiento", "Repuestos", "Estado de la Máquina",
            "Calificación del Servicio", "Modalidad Operativa"
        ]];

        filas.forEach(f => {
            matriz.push([
                f.device_id, f.remision, f.cliente, f.punto,
                f.prevBasico, f.prevProfundo, f.correctivo, f.valor,
                f.obs, f.delegacion, f.fecha, f.tecnico,
                f.tipoMaquina, f.tipoServicio, f.horaEntrada, f.horaSalida,
                f.duracion, f.desplazamiento, f.repuestos, f.estado,
                f.calificacion, f.modalidad
            ]);
        });

        let ws = XLSX.utils.aoa_to_sheet(matriz);

        const fmtMoneda = '_-"$"* #,##0_-;-"$"* #,##0_-;-"$"* "-"??_-;-_-@_-';
        if (ws['!ref']) {
            const range = XLSX.utils.decode_range(ws['!ref']);
            for (let R = range.s.r + 1; R <= range.e.r; ++R) {
                let ref = XLSX.utils.encode_cell({ c: 7, r: R });
                if (!ws[ref]) ws[ref] = { t: 'n', v: 0 };
                ws[ref].t = 'n';
                ws[ref].z = fmtMoneda;
            }
        }

        ws["!cols"] = [
            { wch: 15 }, { wch: 14 }, { wch: 25 }, { wch: 25 },
            { wch: 8 }, { wch: 8 }, { wch: 8 }, { wch: 15 },
            { wch: 50 }, { wch: 15 }, { wch: 12 }, { wch: 20 },
            { wch: 15 }, { wch: 20 }, { wch: 10 }, { wch: 10 },
            { wch: 10 }, { wch: 12 }, { wch: 30 }, { wch: 15 },
            { wch: 15 }, { wch: 15 }
        ];

        let nombreHoja = del.replace(/[:\\/?*\[\]]/g, "").substring(0, 30) || "General";
        XLSX.utils.book_append_sheet(wb, ws, nombreHoja);
    }

    XLSX.writeFile(wb, `Reporte_Servicios_${fecha}.xlsx`);
}

// ─────────────────────────────────────────────────────────────
// INTERNO: generar excel de novedades
// ─────────────────────────────────────────────────────────────
function _generarExcelNovedades(datos, fecha) {
    let lista = datos.map(d => ({
        "Tipo de Novedad": d.nombres_novedades_resueltos || d.nombres_novedades || "SIN ESPECIFICAR",
        "Descripción del Servicio": d.que_se_hizo || d.actividades_realizadas || "",
        "Cliente": d.nombre_cliente || "",
        "Punto": d.nombre_punto || "",
        "Delegación": (d.delegacion || "").trim(),
        "Tipo de Máquina": d.nombre_tipo_maquina || "",
        "Device_id": d.device_id || "",
        "Número de Remisión": d.numero_remision || "",
        "Fecha del Servicio": d.fecha_visita || "",
        "Nombre del Técnico": d.nombre_tecnico || ""
    }));

    let wb = XLSX.utils.book_new();
    let ws = XLSX.utils.json_to_sheet(lista);

    let headers = Object.keys(lista[0]);
    ws["!cols"] = headers.map(h => {
        let max = h.length;
        lista.forEach(r => { let v = r[h] ? String(r[h]) : ""; if (v.length > max) max = v.length; });
        if (max > 70) max = 70;
        if (max < 10) max = 10;
        return { wch: max + 2 };
    });

    const range = XLSX.utils.decode_range(ws['!ref']);
    for (let R = range.s.r; R <= range.e.r; ++R) {
        for (let C = range.s.c; C <= range.e.c; ++C) {
            let ref = XLSX.utils.encode_cell({ r: R, c: C });
            if (!ws[ref]) continue;
            if (!ws[ref].s) ws[ref].s = {};
            ws[ref].s.alignment = { wrapText: true, vertical: "top" };
        }
    }

    XLSX.utils.book_append_sheet(wb, ws, "Novedades");
    XLSX.writeFile(wb, `Novedades_${fecha}.xlsx`);
}

// ─────────────────────────────────────────────────────────────
// EXPORTAR AL OBJETO GLOBAL
// ─────────────────────────────────────────────────────────────
window.DetalleExcel = { exportarExcelLimpio, exportarExcelNovedades };
window.exportarExcelLimpio = exportarExcelLimpio;
window.exportarExcelNovedades = exportarExcelNovedades;