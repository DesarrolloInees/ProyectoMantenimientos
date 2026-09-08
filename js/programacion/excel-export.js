/**
 * js/programacion/excel-export.js
 * Requiere SheetJS (xlsx.full.min.js) cargado antes que este archivo.
 * La vista debe invocar: generarExcelProgramacion(<?= json_encode($datosParaExcel) ?>)
 */
function generarExcelProgramacion(datos) {
    const btn = document.getElementById('btnExportarProgramacion');
    const txt = document.getElementById('txtBotonProg');

    if (btn) {
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
    }
    if (txt) txt.innerHTML = "<i class='fas fa-spinner fa-spin mr-2'></i> Generando...";

    try {
        const datosFormateados = datos.map(fila => {
            let fechaUltima = null;
            if (fila.fecha_ultima_visita && !fila.fecha_ultima_visita.startsWith('0000')) {
                const soloFecha = fila.fecha_ultima_visita.split(' ')[0];
                const partes = soloFecha.split('-');
                if (partes.length === 3) {
                    fechaUltima = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
                }
            }

            let fechaProg = null;
            if (fila.fecha_visita_programada) {
                const partes = fila.fecha_visita_programada.split('-');
                fechaProg = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            }

            return {
                "Código Cliente": fila.codigo_cliente || "",
                "Cliente": fila.nombre_cliente || "",
                "Nombre del Punto": fila.nombre_punto || "",
                "Dirección": fila.direccion || "",
                "Municipio": fila.nombre_municipio || "",
                "Zona": fila.zona || "",
                "Delegación": fila.nombre_delegacion || "Sin Asignar",
                "Técnico Asignado": fila.tecnico_asignado || "",
                "Fecha Visita Programada": fechaProg,
                "ID Dispositivo (Device)": fila.device_id || "",
                "Última Visita": fechaUltima
            };
        });

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(datosFormateados, { cellDates: true });

        const range = XLSX.utils.decode_range(worksheet['!ref']);
        for (let R = range.s.r + 1; R <= range.e.r; ++R) {
            const cellProg = worksheet[XLSX.utils.encode_cell({ r: R, c: 8 })];  // Fecha Visita Programada
            if (cellProg && cellProg.t === 'd') cellProg.z = 'dd/mm/yyyy';

            const cellUlt = worksheet[XLSX.utils.encode_cell({ r: R, c: 10 })]; // Última Visita
            if (cellUlt && cellUlt.t === 'd') cellUlt.z = 'dd/mm/yyyy';
        }

        worksheet['!cols'] = [
            { wch: 15 }, { wch: 35 }, { wch: 30 }, { wch: 40 }, { wch: 25 },
            { wch: 20 }, { wch: 20 }, { wch: 30 }, { wch: 22 }, { wch: 25 }, { wch: 18 }
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Rutas Programadas");
        const nombreArchivo = "Rutas_Programadas_" + new Date().toISOString().slice(0, 10) + ".xlsx";
        XLSX.writeFile(workbook, nombreArchivo);
    } catch (error) {
        console.error("Error al generar Excel:", error);
        alert("Hubo un error al generar el Excel.");
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-75', 'cursor-not-allowed');
        }
        if (txt) txt.innerHTML = "<i class='fas fa-download mr-2'></i> Descargar Excel Programado";
    }
}
