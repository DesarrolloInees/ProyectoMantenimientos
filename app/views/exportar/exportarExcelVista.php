<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="max-w-4xl mx-auto mt-10">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
        <div class="bg-blue-600 p-6">
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Exportar Reporte de Puntos
            </h2>
            <p class="text-blue-100 mt-2">Descarga la información filtrada de Puntos con Máquinas en formato Excel (.xlsx).</p>
        </div>

        <div class="p-8">
            <div class="flex justify-center">
                <button onclick="generarExcelReal()" id="btnExportar" class="group relative w-full flex justify-center py-3 px-8 border border-transparent text-lg font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-md transform hover:-translate-y-1 cursor-pointer">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-6 w-6 text-green-300 group-hover:text-green-100" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                        </svg>
                    </span>
                    <span id="txtBoton">GENERAR EXCEL AHORA (.XLSX)</span>
                </button>
            </div>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
            <span class="text-sm text-gray-500">Formato de salida: Excel Original (.xlsx)</span>
            <a href="<?php echo BASE_URL; ?>" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                &larr; Volver al inicio
            </a>
        </div>
    </div>
</div>

<script>
    async function generarExcelReal() {
        const btn = document.getElementById('btnExportar');
        const txt = document.getElementById('txtBoton');

        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        txt.innerText = "Generando archivo...";

        try {
            // 1. Obtener datos
            const response = await fetch('<?php echo BASE_URL; ?>index.php?pagina=exportarExcel&accion=descargarReporte');
            if (!response.ok) throw new Error('Error en la red');
            const datos = await response.json();

            if (datos.length === 0) {
                alert("No hay datos para exportar.");
                return;
            }

            // 2. Preparar datos (Limpiando hora desde el origen)
            const datosFormateados = datos.map(fila => {
                let fechaObjeto = null;
                
                // Validar que no sea null y no sea fecha cero
                if (fila.fecha_ultima_visita && !fila.fecha_ultima_visita.startsWith('0000')) {
                    try {
                        // Cortamos cualquier hora que venga del servidor: "2025-12-01 15:30" -> "2025-12-01"
                        let soloFecha = fila.fecha_ultima_visita.split(' ')[0]; 
                        let partes = soloFecha.split('-'); 
                        
                        if (partes.length === 3) {
                            const anio = parseInt(partes[0]);
                            const mes = parseInt(partes[1]) - 1; 
                            const dia = parseInt(partes[2]);
                            
                            // Validar año lógico (>1990) para evitar fechas "1969"
                            if (!isNaN(anio) && anio > 1990 && !isNaN(mes) && !isNaN(dia)) {
                                fechaObjeto = new Date(anio, mes, dia);
                            }
                        }
                    } catch (e) {
                        fechaObjeto = null; 
                    }
                }

                return {
                    "Código Cliente": fila.codigo_cliente || "",
                    "Cliente": fila.nombre_cliente || "",
                    "Nombre del Punto": fila.nombre_punto,
                    "Dirección": fila.direccion,
                    "Delegación": fila.nombre_delegacion || "Sin Asignar",
                    "ID Dispositivo (Device)": fila.device_id,
                    "Fecha Última Visita": fechaObjeto, 
                    "Último Mantenimiento": fila.nombre_mantenimiento || ""
                };
            });

            // 3. Crear Libro
            const workbook = XLSX.utils.book_new();
            const worksheet = XLSX.utils.json_to_sheet(datosFormateados, { cellDates: true });

            // 4. 🔥 ELIMINAR HORA VISUALMENTE 🔥
            if (worksheet['!ref']) {
                const range = XLSX.utils.decode_range(worksheet['!ref']);
                const columnaFechaIndex = 6; // Columna G (La 7ma columna, índice 6)

                for (let R = range.s.r + 1; R <= range.e.r; ++R) {
                    const address = XLSX.utils.encode_cell({r: R, c: columnaFechaIndex});
                    
                    // Si la celda existe y es de tipo Fecha ('d')
                    if (worksheet[address] && worksheet[address].t === 'd') {
                        // Forzamos el formato estricto de solo fecha
                        worksheet[address].z = 'dd/mm/yyyy'; 
                        
                        // Opcional: Si lo anterior no basta, usa el numFmt 14 (Fecha corta estándar)
                        // worksheet[address].z = 'm/d/yy'; 
                    }
                }
            }

            // 5. Ajustar anchos
            worksheet['!cols'] = [
                { wch: 15 }, { wch: 35 }, { wch: 30 }, { wch: 40 }, 
                { wch: 20 }, { wch: 25 }, { wch: 15 }, { wch: 25 }
            ];

            // 6. Descargar
            XLSX.utils.book_append_sheet(workbook, worksheet, "Reporte Puntos");
            const nombreArchivo = "Reporte_Puntos_" + new Date().toISOString().slice(0, 10) + ".xlsx";
            XLSX.writeFile(workbook, nombreArchivo);

        } catch (error) {
            console.error("Error:", error);
            alert("Hubo un error al generar el Excel.");
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            txt.innerText = "GENERAR EXCEL AHORA (.XLSX)";
        }
    }
</script>