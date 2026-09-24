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

/**
 * Borra una orden programada de ordenes_servicio y la quita de la tabla
 * y del dataset del Excel, para que no salga en la descarga posterior.
 */
function eliminarOrdenConsolidado(idOrden, nombrePunto) {
    const id = parseInt(idOrden, 10);
    if (!Number.isInteger(id) || id <= 0) return;

    const etiqueta = nombrePunto ? `"${nombrePunto}"` : `orden #${id}`;
    if (!confirm(`¿Eliminar ${etiqueta} de la programación?\n\nSe borrará de ordenes_servicio y no saldrá en el Excel.`)) {
        return;
    }

    const fila = document.getElementById('fila_orden_' + id);
    const boton = fila ? fila.querySelector('button') : null;
    if (boton) {
        boton.disabled = true;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }

    const body = new URLSearchParams();
    body.append('id_orden', String(id));

    const base = (typeof BASE_URL !== 'undefined' && BASE_URL) ? BASE_URL : '';

    fetch(`${base}index.php?pagina=programacionConsolidado&accion=eliminarOrden`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
    })
        .then(resp => resp.json())
        .then(data => {
            if (!data || !data.status) {
                alert((data && data.msg) ? data.msg : 'No se pudo eliminar la orden.');
                if (boton) {
                    boton.disabled = false;
                    boton.innerHTML = '<i class="fas fa-trash-alt"></i>';
                }
                return;
            }

            window.ProgConsolidadoData = (window.ProgConsolidadoData || []).filter(filaData => {
                return String(filaData.id_orden) !== String(id);
            });

            if (fila) fila.remove();

            const contador = document.getElementById('contadorConsolidado');
            if (contador) contador.textContent = window.ProgConsolidadoData.length;

            const tarjetaServicios = document.getElementById('contadorServicios');
            if (tarjetaServicios) tarjetaServicios.textContent = window.ProgConsolidadoData.length;

            const tbody = document.getElementById('tbodyConsolidado');
            if (tbody && window.ProgConsolidadoData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 font-bold">
                            No se encontraron rutas programadas en este rango de fechas.
                        </td>
                    </tr>`;
                const btnExcel = document.querySelector('button[onclick="exportarExcelMaestro()"]');
                if (btnExcel) btnExcel.remove();
            }
        })
        .catch(() => {
            alert('Error de conexión al eliminar la orden.');
            if (boton) {
                boton.disabled = false;
                boton.innerHTML = '<i class="fas fa-trash-alt"></i>';
            }
        });
}
