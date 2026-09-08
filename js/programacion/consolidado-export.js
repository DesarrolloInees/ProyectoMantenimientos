/**
 * js/programacion/consolidado-export.js
 * Requiere SheetJS cargado antes que este archivo.
 * La vista debe definir: window.ProgConsolidadoData = <?= json_encode($consolidado) ?>
 * y llamar a exportarExcelMaestro() desde el botón.
 */
function exportarExcelMaestro() {
    const datos = window.ProgConsolidadoData || [];
    if (datos.length === 0) return;

    try {
        const datosFormateados = datos.map(fila => {
            let fechaProg = null;
            if (fila.fecha_visita) {
                const partes = fila.fecha_visita.split('-');
                fechaProg = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            }

            let fechaUlt = null;
            if (fila.fecha_ultima_visita && !fila.fecha_ultima_visita.startsWith('0000')) {
                const soloFecha = fila.fecha_ultima_visita.split(' ')[0];
                const partes = soloFecha.split('-');
                if (partes.length === 3) {
                    fechaUlt = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
                }
            }

            return {
                "Fecha Visita": fechaProg,
                "Ruta": fila.codigo_ruta || "Sin Ruta",
                "Técnico Asignado": fila.nombre_tecnico || "",
                "Código Cliente": fila.codigo_cliente || "",
                "Cliente": fila.nombre_cliente || "",
                "Nombre del Punto": fila.nombre_punto || "",
                "Dirección": fila.direccion || "",
                "Municipio": fila.nombre_municipio || "",
                "Zona": fila.zona || "",
                "Delegación": fila.nombre_delegacion || "",
                "ID Dispositivo (Device)": fila.device_id || "",
                "Aún Fuera de Servicio": (fila.activo_operativo == 0) ? "SI" : "",
                "Fecha Última Visita": fechaUlt
            };
        });

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(datosFormateados, { cellDates: true });

        const range = XLSX.utils.decode_range(worksheet['!ref']);
        for (let R = range.s.r + 1; R <= range.e.r; ++R) {
            const cellVisita = worksheet[XLSX.utils.encode_cell({ r: R, c: 0 })];  // Fecha Visita
            if (cellVisita && cellVisita.t === 'd') cellVisita.z = 'dd/mm/yyyy';

            const cellUlt = worksheet[XLSX.utils.encode_cell({ r: R, c: 12 })];    // Fecha Última Visita
            if (cellUlt && cellUlt.t === 'd') cellUlt.z = 'dd/mm/yyyy';
        }

        worksheet['!cols'] = [
            { wch: 15 }, { wch: 12 }, { wch: 30 }, { wch: 15 }, { wch: 35 },
            { wch: 30 }, { wch: 40 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
            { wch: 25 }, { wch: 18 }, { wch: 18 }
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Maestro Rutas");
        const nombreArchivo = "Maestro_Programacion_" + new Date().toISOString().slice(0, 10) + ".xlsx";
        XLSX.writeFile(workbook, nombreArchivo);
    } catch (error) {
        console.error("Error al generar Excel:", error);
        alert("Hubo un error al generar el Excel.");
    }
}
