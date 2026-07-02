<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="w-full max-w-4xl mx-auto mt-6">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
        <div class="bg-indigo-600 p-6">
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Reporte de Tarifas Mensuales
            </h2>
            <p class="text-indigo-100 mt-2">
                Genera el reporte definitivo en 4 hojas separando los Resúmenes Ejecutivos y los Desgloses por tamaño.
            </p>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-gray-50 p-4 rounded-md border border-gray-200">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio</label>
                    <input type="date" id="fechaInicio"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Fin</label>
                    <input type="date" id="fechaFin"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border">
                </div>
            </div>

            <div class="flex justify-center">
                <button onclick="generarExcelPorcentajes()" id="btnExportarPorcentajes"
                    class="group relative w-full flex justify-center py-3 px-8 border border-transparent text-lg font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-md transform hover:-translate-y-1 cursor-pointer">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-6 w-6 text-indigo-300 group-hover:text-indigo-100" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                        </svg>
                    </span>
                    <span id="txtBotonPorcentajes">GENERAR REPORTE (.XLSX)</span>
                </button>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
            <span class="text-sm text-gray-500">Formato de salida: Excel Original (.xlsx)</span>
            <a href="<?php echo BASE_URL; ?>inicio"
                class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                &larr; Volver al inicio
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const hoy = new Date();
        const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
        const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0).toISOString().split('T')[0];

        document.getElementById('fechaInicio').value = primerDia;
        document.getElementById('fechaFin').value = ultimoDia;
    });

    async function generarExcelPorcentajes() {
        const btn = document.getElementById('btnExportarPorcentajes');
        const txt = document.getElementById('txtBotonPorcentajes');
        const fInicio = document.getElementById('fechaInicio').value;
        const fFin = document.getElementById('fechaFin').value;

        if (!fInicio || !fFin) {
            alert("Por favor selecciona ambas fechas.");
            return;
        }

        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        txt.innerText = "Calculando archivo...";

        try {
            const url = `<?php echo BASE_URL; ?>index.php?pagina=reporteTarifas&accion=descargarReportePorcentajes&fecha_inicio=${fInicio}&fecha_fin=${fFin}`;
            const response = await fetch(url);
            if (!response.ok) throw new Error('Error en la red');
            const datos = await response.json();

            if (datos.length === 0) {
                alert("No hay datos para el rango de fechas seleccionado.");
                return;
            }

            const workbook = XLSX.utils.book_new();

            // ==========================================
            // 1. PROCESAR Y CALCULAR TOTALES POR GRUPO
            // ==========================================
            const mapaResumen = {};
            const totalesPorGrupo = { 'Grandes': 0, 'Pequeñas': 0 };

            datos.forEach(d => {
                const cat = d.categoria_tamano;
                const mant = d.tipo_mantenimiento || "Desconocido";
                const key = cat + '|' + mant;
                const precio = parseFloat(d.precio_total || 0);

                if (!mapaResumen[key]) {
                    mapaResumen[key] = { Categoria: cat, Mantenimiento: mant, Cantidad: 0, Precio: 0 };
                }
                mapaResumen[key].Cantidad += parseInt(d.cantidad_servicios);
                mapaResumen[key].Precio += precio;
                totalesPorGrupo[cat] += precio;
            });

            // Convertir el mapa en un array completo estructurado
            const todosLosResumenes = Object.values(mapaResumen).map(item => ({
                "Grupo de Máquinas": item.Categoria,
                "Tipo de Mantenimiento": item.Mantenimiento,
                "Cantidad de Servicios": item.Cantidad,
                "Precio Total Recaudado": item.Precio,
                "Porcentaje del Grupo (%)": totalesPorGrupo[item.Categoria] > 0 ? (item.Precio / totalesPorGrupo[item.Categoria]) : 0
            }));

            // ==========================================
            // 2. SEPARAR LOS DATOS PARA LAS 4 HOJAS
            // ==========================================

            // Hojas de Resumen (4 filas c/u)
            const resumenGrandes = todosLosResumenes.filter(r => r["Grupo de Máquinas"] === 'Grandes');
            const resumenPequenas = todosLosResumenes.filter(r => r["Grupo de Máquinas"] === 'Pequeñas');

            // Hojas de Desglose Específico
            const prepararDesglose = (datosFiltrados, categoria) => {
                const totalCategoria = totalesPorGrupo[categoria];
                return datosFiltrados.map(d => {
                    const precio = parseFloat(d.precio_total || 0);
                    return {
                        "Máquina Específica": d.nombre_tipo_maquina || "Sin Grupo",
                        "Tipo de Mantenimiento": d.tipo_mantenimiento || "Desconocido",
                        "Cantidad de Servicios": parseInt(d.cantidad_servicios || 0),
                        "Precio Total Recaudado": precio,
                        "Porcentaje del Grupo (%)": totalCategoria > 0 ? (precio / totalCategoria) : 0
                    }
                });
            };

            const datosGrandes = datos.filter(d => d.categoria_tamano === 'Grandes');
            const datosPequenas = datos.filter(d => d.categoria_tamano === 'Pequeñas');

            const desgloseGrandes = prepararDesglose(datosGrandes, 'Grandes');
            const desglosePequenas = prepararDesglose(datosPequenas, 'Pequeñas');

            // ==========================================
            // 3. MONTAR LAS HOJAS EN EL ORDEN SOLICITADO
            // ==========================================

            // Bloque Grandes
            agregarHojaAlLibro(workbook, resumenGrandes, "Resumen Grandes", [20, 35, 22, 25, 25]);
            agregarHojaAlLibro(workbook, desgloseGrandes, "Desglose Grandes", [30, 35, 22, 25, 25]);

            // Bloque Pequeñas
            agregarHojaAlLibro(workbook, resumenPequenas, "Resumen Pequeñas", [20, 35, 22, 25, 25]);
            agregarHojaAlLibro(workbook, desglosePequenas, "Desglose Pequeñas", [30, 35, 22, 25, 25]);

            // ==========================================
            // 4. DESCARGAR ARCHIVO
            // ==========================================
            const nombreArchivo = `Reporte_Tarifas_${fInicio}_al_${fFin}.xlsx`;
            XLSX.writeFile(workbook, nombreArchivo);

        } catch (error) {
            console.error("Error al exportar:", error);
            alert("Hubo un error al generar el Excel.");
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            txt.innerText = "GENERAR REPORTE (.XLSX)";
        }
    }

    // Función auxiliar para formatear columnas de dinero/porcentaje y añadir hojas
    function agregarHojaAlLibro(workbook, datosFormateados, nombreHoja, anchos) {
        if (datosFormateados.length === 0) return;

        const worksheet = XLSX.utils.json_to_sheet(datosFormateados);

        if (worksheet['!ref']) {
            const range = XLSX.utils.decode_range(worksheet['!ref']);
            for (let R = range.s.r + 1; R <= range.e.r; ++R) {
                // Columna D (Precio Total Recaudado) -> índice de columna es 3
                const cellPrecio = worksheet[XLSX.utils.encode_cell({ r: R, c: 3 })];
                if (cellPrecio) cellPrecio.z = '"$"#,##0.00';

                // Columna E (Porcentaje del Grupo) -> índice de columna es 4
                const cellPorcentaje = worksheet[XLSX.utils.encode_cell({ r: R, c: 4 })];
                if (cellPorcentaje) cellPorcentaje.z = '0.00%';
            }
        }

        worksheet['!cols'] = anchos.map(w => ({ wch: w }));
        XLSX.utils.book_append_sheet(workbook, worksheet, nombreHoja);
    }
</script>