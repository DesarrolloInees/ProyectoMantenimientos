<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");
?>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>


<style>
    /* Forzar que la tabla respete los contenedores */
    .dataTables_wrapper {
        padding: 20px;
        background: white;
        border-radius: 0.75rem;
    }

    /* Estilo profesional para el buscador y botones */
    div.dt-buttons {
        margin-bottom: 1rem;
        display: flex;
        gap: 0.5rem;
    }

    /* Arreglar alineación de la paginación y búsqueda */
    .dt-layout-cell.dt-start,
    .dt-layout-cell.dt-end {
        padding: 10px 0;
    }

    /* Quitar bordes feos de las celdas */
    table.dataTable {
        border-collapse: collapse !important;
        margin-top: 10px !important;
    }

    /* Asegurar que las celdas se vean bien */
    table.dataTable thead th {
        background-color: #f3f4f6 !important;
        padding: 12px 16px !important;
        border-bottom: 2px solid #e5e7eb !important;
    }

    table.dataTable tbody td {
        padding: 10px 16px !important;
        border-bottom: 1px solid #f3f4f6 !important;
    }
</style>

<div class="w-full px-4 md:px-6">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-4 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i
                        class="fas fa-file-invoice-dollar text-green-600 mr-2"></i> Analizador de Cotizaciones</h1>
                <p class="text-gray-500 text-sm">Filtra tus cotizaciones en pantalla y exporta el reporte gerencial en
                    PDF.</p>
            </div>
            <div class="flex gap-2">
                <input type="file" id="archivo_excel" accept=".xlsx, .xls, .csv" class="hidden">

                <button type="button" onclick="document.getElementById('archivo_excel').click()"
                    class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition">
                    <i class="fas fa-upload"></i> Subir Excel
                </button>

                <button type="button" id="btn_generar_pdf"
                    class="bg-red-600 text-white px-4 py-2 rounded font-bold shadow opacity-50 cursor-not-allowed flex items-center gap-2 transition"
                    disabled>
                    <i class="fas fa-file-pdf"></i> Exportar PDF (IM FAC, COT, Q)
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end mt-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde Fecha</label>
                <input type="date" id="filtro_inicio"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta Fecha</label>
                <input type="date" id="filtro_fin"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                <select id="filtro_categoria"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">Todas</option>
                    <option value="MQ">Máquinas (MQ)</option>
                    <option value="RP">Repuestos (RP)</option>
                    <option value="SE">Servicios (SE)</option>
                    <option value="TRA">Transportadora (TRA)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                <select id="filtro_estado"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">Todos</option>
                    <option value="FACTURADO">Facturado</option>
                    <option value="PENDIENTE">Pendiente</option>
                    <option value="REFACTURADA">Refacturada</option>
                    <option value="ANULADA">Anulada</option>
                    <option value="PAGADA">Pagada</option>
                </select>
            </div>
            <div>
                <button type="button" id="btn_filtrar"
                    class="w-full py-2 px-4 bg-gray-800 text-white font-bold rounded-lg shadow hover:bg-black transition-all">
                    <i class="fas fa-filter mr-2"></i> Aplicar Filtros
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
            <p class="text-sm text-blue-600 font-bold uppercase">Registros Listados</p>
            <p class="text-2xl font-bold text-gray-800" id="resumen_cantidad">0</p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg border border-green-100">
            <p class="text-sm text-green-600 font-bold uppercase">Total Subtotal Calculado</p>
            <p class="text-2xl font-bold text-gray-800" id="resumen_valor">$0</p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <div class="overflow-x-auto">
            <table id="tabla_excel" class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                    <tr>
                        <th class="py-3 px-4">N° Cotización</th>
                        <th class="py-3 px-4">Remisión</th>
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4">Categoría</th>
                        <th class="py-3 px-4">Estado</th>
                        <th class="py-3 px-4">Items</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://cdn.datatables.net/2.0.8/css/dataTables.tailwindcss.css" rel="stylesheet">
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.tailwindcss.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>

<style>
    /* Contenedor principal del DataTable */
    .dataTables_wrapper {
        padding: 20px;
        background-color: #ffffff !important;
        border-radius: 0.75rem;
        color: #374151 !important;
    }

    /* Estilo profesional para el buscador y botones */
    div.dt-buttons {
        margin-bottom: 1rem;
        display: flex;
        gap: 0.5rem;
    }

    /* Arreglar alineación de la paginación y búsqueda en DT 2.x */
    .dt-layout-cell {
        padding: 10px 0;
    }

    /* Forzar tabla clara y legible */
    table.dataTable {
        border-collapse: collapse !important;
        margin-top: 10px !important;
        background-color: #ffffff !important;
        width: 100% !important;
    }

    /* Cabeceras limpias */
    table.dataTable thead th {
        background-color: #f3f4f6 !important;
        color: #1f2937 !important;
        padding: 12px 16px !important;
        border-bottom: 2px solid #e5e7eb !important;
        font-weight: 700;
        text-transform: uppercase;
    }

    /* Celdas legibles */
    table.dataTable tbody td {
        padding: 10px 16px !important;
        border-bottom: 1px solid #f3f4f6 !important;
        color: #4b5563 !important;
        background-color: #ffffff !important;
    }

    /* Hover en filas */
    table.dataTable tbody tr:hover td {
        background-color: #f9fafb !important;
    }
</style>

<script>
    let dataTable;
    let datosMemoriaTabla = [];
    let datos_IM_FAC = [];
    let datos_IM_COT = [];
    let datos_IM_Q = [];

    $(document).ready(function () {
        dataTable = $('#tabla_excel').DataTable({
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' }, // Actualizado a v2
            layout: {
                topStart: 'buttons',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging'
            },
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel mr-1"></i> Excel',
                    className: 'bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 transition-colors'
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf mr-1"></i> PDF Lista',
                    className: 'bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700 transition-colors'
                }
            ],
            order: [[2, "desc"]]
        });
        $('#archivo_excel').on('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });

                    // --- DEBUG: Imprime las hojas detectadas ---
                    console.log("Hojas detectadas en el Excel:", workbook.SheetNames);

                    // 1. Cargar CONTROL COT
                    let sheetCotMain = workbook.SheetNames.find(n => n.toUpperCase().includes('CONTROL COT'));
                    if (!sheetCotMain) sheetCotMain = workbook.SheetNames[0]; // Fallback

                    let jsonCot = XLSX.utils.sheet_to_json(workbook.Sheets[sheetCotMain], { raw: false });
                    procesarParaTabla(jsonCot);

                    // 2. Extraer datos para PDF
                    let sFac = workbook.SheetNames.find(n => n.toUpperCase().includes('IM FAC'));
                    let sCot = workbook.SheetNames.find(n => n.toUpperCase().includes('IM COT'));
                    let sQ = workbook.SheetNames.find(n => n.toUpperCase().includes('IM Q'));

                    datos_IM_FAC = extraerDatosHoja(workbook.Sheets[sFac], 'N° FACTURA');
                    datos_IM_COT = extraerDatosHoja(workbook.Sheets[sCot], 'N° COTIZACION');
                    datos_IM_Q = extraerDatosHoja(workbook.Sheets[sQ], 'N° COTIZACION');

                    console.log("Registros extraídos: FAC:", datos_IM_FAC.length, "COT:", datos_IM_COT.length, "Q:", datos_IM_Q.length);

                    // Habilitar botón PDF
                    $('#btn_generar_pdf').removeClass('opacity-50 cursor-not-allowed').addClass('hover:bg-red-700').prop('disabled', false);
                    alert("¡Archivo procesado con éxito!");
                } catch (error) {
                    console.error("Error al leer el archivo:", error);
                    alert("Error al procesar el archivo. Revisa la consola (F12).");
                }
            };
            reader.readAsArrayBuffer(file);
        });
    });

    // Evento: Filtros de la Tabla
    $('#btn_filtrar').on('click', function () {
        aplicarFiltrosTabla();
    });

    // Evento: Generar PDF
    $('#btn_generar_pdf').on('click', function () {
        generarPDFTresHojas();
    });

    function extraerDatosHoja(hoja, colBusqueda) {
        if (!hoja) {
            console.warn("No se encontró la hoja requerida.");
            return [];
        }
        const filas = XLSX.utils.sheet_to_json(hoja, { header: 1, defval: "" });
        let rowIndex = -1;
        for (let i = 0; i < filas.length; i++) {
            if (filas[i].some(celda => String(celda).toUpperCase().includes(colBusqueda))) {
                rowIndex = i;
                break;
            }
        }
        if (rowIndex === -1) return [];

        const encabezados = filas[rowIndex];
        let datosLimpios = [];
        for (let i = rowIndex + 1; i < filas.length; i++) {
            let fila = filas[i];
            // Validación robusta: Si la fila está vacía, saltarla
            if (!fila[0] && !fila[1]) continue;
            if (String(fila[0]).toUpperCase().includes('TOTAL')) continue;

            let obj = {};
            encabezados.forEach((enc, index) => {
                if (enc) obj[String(enc).trim()] = fila[index];
            });
            datosLimpios.push(obj);
        }
        return datosLimpios;
    }

    // ==========================================
    // LÓGICA PARA LA TABLA INTERACTIVA
    // ==========================================
    function procesarParaTabla(json) {
        datosMemoriaTabla = [];

        json.forEach(row => {
            let cotizacion = row['N° COTIZACION'] || row['COTIZACION'] || '';
            let remision = row['N° REMISION'] || row['REMISION'] || '';
            let categoria = row['CATEGORIA'] || '';
            let fecha = row['FECHA DE REALIZACION'] || row['FECHA REALIZACION'] || '';
            let estado = row['ESTADO'] || row['ESTADO '] || '';
            let items = row['ITEMS'] || '';

            let subtotalStr = row['SUBTOTAL'] || row['TOTAL COT'] || '0';
            let subtotal = parseFloat(String(subtotalStr).replace(/[^0-9.-]+/g, "")) || 0;

            if (cotizacion && cotizacion.trim() !== '' && !cotizacion.toUpperCase().includes('TOTAL')) {
                datosMemoriaTabla.push({
                    cotizacion: cotizacion,
                    remision: remision,
                    categoria: categoria,
                    fecha: fecha,
                    estado: estado,
                    items: items,
                    subtotal: subtotal
                });
            }
        });

        aplicarFiltrosTabla();
    }

    function aplicarFiltrosTabla() {
        const f_inicio = $('#filtro_inicio').val();
        const f_fin = $('#filtro_fin').val();
        const f_cat = $('#filtro_categoria').val().toUpperCase();
        const f_estado = $('#filtro_estado').val().toUpperCase();

        let datosFiltrados = datosMemoriaTabla.filter(item => {
            let pasaFiltro = true;
            if (f_inicio && item.fecha < f_inicio) pasaFiltro = false;
            if (f_fin && item.fecha > f_fin) pasaFiltro = false;
            if (f_cat && (item.categoria || '').toUpperCase() !== f_cat) pasaFiltro = false;
            if (f_estado && (item.estado || '').toUpperCase() !== f_estado) pasaFiltro = false;
            return pasaFiltro;
        });

        actualizarTabla(datosFiltrados);
    }

    function actualizarTabla(datos) {
        dataTable.clear();
        let granTotal = 0;

        datos.forEach(item => {
            granTotal += item.subtotal;
            let textoItems = item.items.length > 40 ? item.items.substring(0, 40) + '...' : item.items;

            dataTable.row.add([
                `<strong>${item.cotizacion}</strong>`,
                item.remision,
                item.fecha,
                item.categoria,
                item.estado,
                `<span title="${item.items}">${textoItems}</span>`,
                `<strong>$${item.subtotal.toLocaleString('es-CO')}</strong>`
            ]);
        });

        dataTable.draw();
        $('#resumen_cantidad').text(datos.length);
        $('#resumen_valor').text(`$${granTotal.toLocaleString('es-CO')}`);
    }


    // ==========================================
    // LÓGICA PARA EXTRAER Y GENERAR EL PDF
    // ==========================================
    function extraerDatosHoja(hoja, palabraClaveColumna) {
        if (!hoja) return [];
        const filas = XLSX.utils.sheet_to_json(hoja, { header: 1, defval: "" });

        let rowIndex = -1;
        for (let i = 0; i < filas.length; i++) {
            if (filas[i].some(celda => String(celda).toUpperCase().includes(palabraClaveColumna))) {
                rowIndex = i;
                break;
            }
        }

        if (rowIndex === -1) return [];

        const encabezados = filas[rowIndex];
        let datosLimpios = [];

        for (let i = rowIndex + 1; i < filas.length; i++) {
            let fila = filas[i];
            let primerDato = String(fila[0] || '').trim().toUpperCase();
            if (primerDato === '' || primerDato.includes('TOTAL GENERAL')) continue;

            let obj = {};
            encabezados.forEach((enc, index) => {
                let nombreCol = String(enc).trim();
                if (nombreCol) obj[nombreCol] = fila[index];
            });
            datosLimpios.push(obj);
        }
        return datosLimpios;
    }

    function formatoMoneda(valor) {
        let num = parseFloat(String(valor).replace(/[^0-9.-]+/g, "")) || 0;
        return '$' + num.toLocaleString('es-CO');
    }

    function generarPDFTresHojas() {
        // --- 1. TABLA FACTURAS (IM FAC) ---
        let bodyFac = [[{ text: 'N° FACTURA', style: 'th' }, { text: 'N° COTIZACION', style: 'th' }, { text: 'N° OC', style: 'th' }, { text: 'FECHA DE FACTURA', style: 'th' }, { text: 'DATAICO', style: 'th' }, { text: 'TOTAL COT', style: 'th' }]];
        let totalFac = 0;
        datos_IM_FAC.forEach(row => {
            let val = parseFloat(row['Suma de TOTAL COT']) || 0;
            totalFac += val;
            bodyFac.push([
                { text: row['N° FACTURA'] || '', bold: true }, row['N° COTIZACION'] || '', row['N° OC'] || '', row['FECHA DE FACTURA'] || '', row['DATAICO'] || '', { text: formatoMoneda(val), alignment: 'right' }
            ]);
        });
        bodyFac.push([{ text: 'Total general', colSpan: 5, alignment: 'right', bold: true }, {}, {}, {}, {}, { text: formatoMoneda(totalFac), alignment: 'right', bold: true }]);

        // --- 2. TABLA COTIZACIONES (IM COT) ---
        let bodyCot = [[{ text: 'N° COTIZACION', style: 'th' }, { text: 'N° REMISION', style: 'th' }, { text: 'CATEGORIA', style: 'th' }, { text: 'FECHA REALIZACION', style: 'th' }, { text: 'FECHA ENVIADO', style: 'th' }, { text: 'SUBTOTAL', style: 'th' }]];
        let totalCot = 0;
        datos_IM_COT.forEach(row => {
            let val = parseFloat(row['Suma de SUBTOTAL']) || 0;
            totalCot += val;
            bodyCot.push([
                { text: row['N° COTIZACION'] || '', bold: true }, row['N° REMISION'] || '', row['CATEGORIA'] || '', row['FECHA REALIZACION'] || '', row['FECHA ENVIADO'] || '', { text: formatoMoneda(val), alignment: 'right' }
            ]);
        });
        bodyCot.push([{ text: 'Total general', colSpan: 5, alignment: 'right', bold: true }, {}, {}, {}, {}, { text: formatoMoneda(totalCot), alignment: 'right', bold: true }]);

        // --- 3. TABLA Q (IM Q) ---
        let bodyQ = [[{ text: 'N° COTIZACION', style: 'th' }, { text: 'N° REMISION', style: 'th' }, { text: 'FECHA DE REALIZACION', style: 'th' }, { text: 'SUBTOTAL', style: 'th' }]];
        let totalQ = 0;
        datos_IM_Q.forEach(row => {
            let val = parseFloat(row['Suma de SUBTOTAL']) || 0;
            totalQ += val;
            bodyQ.push([
                { text: row['N° COTIZACION'] || '', bold: true }, row['N° REMISION'] || '', row['FECHA DE REALIZACION'] || '', { text: formatoMoneda(val), alignment: 'right' }
            ]);
        });
        bodyQ.push([{ text: 'Total general', colSpan: 3, alignment: 'right', bold: true }, {}, {}, { text: formatoMoneda(totalQ), alignment: 'right', bold: true }]);

        // --- CONSTRUCCIÓN DEL PDF ---
        let docDefinition = {
            pageSize: 'A4', pageOrientation: 'portrait', pageMargins: [30, 40, 30, 40],
            content: [
                { text: 'REPORTE DE FACTURAS', style: 'titulo' },
                { table: { headerRows: 1, widths: ['15%', '15%', '25%', '15%', '15%', '15%'], body: bodyFac }, layout: 'lightHorizontalLines', margin: [0, 0, 0, 30] },
                { text: 'COTIZACIONES PENDIENTES', style: 'titulo' },
                { table: { headerRows: 1, widths: ['15%', '20%', '15%', '15%', '15%', '20%'], body: bodyCot }, layout: 'lightHorizontalLines', margin: [0, 0, 0, 30] },
                { text: 'COTIZACIONES Q PENDIENTES', style: 'titulo' },
                { table: { headerRows: 1, widths: ['25%', '25%', '25%', '25%'], body: bodyQ }, layout: 'lightHorizontalLines', margin: [0, 0, 0, 30] }
            ],
            styles: {
                titulo: { fontSize: 14, bold: true, color: '#1e3a8a', margin: [0, 10, 0, 10], alignment: 'center' },
                th: { fillColor: '#1e3a8a', color: 'white', bold: true, alignment: 'center', fontSize: 10 }
            },
            defaultStyle: { fontSize: 9 }
        };

        pdfMake.createPdf(docDefinition).download('Reportes_Gerenciales.pdf');
    }
</script>