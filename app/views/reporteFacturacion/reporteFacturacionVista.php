<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");
?>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<div class="w-full px-4 md:px-6">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex justify-between items-center flex-wrap gap-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-file-invoice-dollar text-green-600 mr-2"></i> Analizador de Cotizaciones</h1>
                <p class="text-gray-500 text-sm">Sube tu Excel, filtra en tiempo real y descarga el reporte (Procesamiento 100% en memoria).</p>
            </div>
            <div>
                <input type="file" id="archivo_excel" accept=".xlsx, .xls, .csv" class="hidden">
                <button type="button" onclick="document.getElementById('archivo_excel').click()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition">
                    <i class="fas fa-upload"></i> Subir Archivo Excel
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end mt-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde Fecha</label>
                <input type="date" id="filtro_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta Fecha</label>
                <input type="date" id="filtro_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                <select id="filtro_categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">Todas</option>
                    <option value="MQ">Máquinas (MQ)</option>
                    <option value="RP">Repuestos (RP)</option>
                    <option value="SE">Servicios (SE)</option>
                    <option value="TRA">Transportadora (TRA)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                <select id="filtro_estado" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">Todos</option>
                    <option value="FACTURADO">Facturado</option>
                    <option value="PENDIENTE">Pendiente</option>
                    <option value="REFACTURADA">Refacturada</option>
                    <option value="ANULADA">Anulada</option>
                    <option value="PAGADA">Pagada</option>
                </select>
            </div>
            <div>
                <button type="button" id="btn_filtrar" class="w-full py-2 px-4 bg-gray-800 text-white font-bold rounded-lg shadow hover:bg-black transition-all">
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
            <p class="text-2xl font-bold text-gray-800" id="resumen_valor">$0.00</p>
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
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
    let dataTable;
    let datosMemoria = [];

    $(document).ready(function() {
        // Inicializar DataTable con estilos en las columnas en lugar de HTML en los datos
        dataTable = $('#tabla_excel').DataTable({
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            dom: '<"flex justify-between items-center mb-4"Bf>rt<"flex justify-between items-center mt-4"ip>',
            columnDefs: [
                { targets: 0, className: 'font-bold text-gray-900' }, 
                { targets: 6, className: 'text-right font-bold' }
            ],
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel mr-1"></i> Descargar Excel',
                    className: 'bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 mr-2',
                    title: 'Reporte_Cotizaciones_Filtrado',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf mr-1"></i> Descargar PDF',
                    className: 'bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500',
                    title: 'Reporte Dinámico de Cotizaciones',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: ':visible' },
                    customize: function(doc) {
                        // Personalización rápida del PDF para que se vea institucional
                        doc.styles.tableHeader.fillColor = '#1e3a8a';
                        doc.styles.tableHeader.color = 'white';
                        doc.styles.tableHeader.alignment = 'center';
                        doc.content[1].table.widths = ['10%', '12%', '10%', '8%', '10%', '35%', '15%'];
                    }
                }
            ],
            order: [[2, "desc"]]
        });

        // Evento para leer el Excel subido
        $('#archivo_excel').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {type: 'array'});
                
                // 1. INTELIGENCIA: Buscar la pestaña correcta
                let nombreHojaCorrecta = workbook.SheetNames[0]; 
                for (let i = 0; i < workbook.SheetNames.length; i++) {
                    // Buscamos la pestaña que diga "CONTROL COT" o "COT"
                    if (workbook.SheetNames[i].toUpperCase().includes("CONTROL COT")) {
                        nombreHojaCorrecta = workbook.SheetNames[i];
                        break;
                    }
                }

                const worksheet = workbook.Sheets[nombreHojaCorrecta];
                
                // Convertir a JSON
                const json = XLSX.utils.sheet_to_json(worksheet, {raw: false});
                
                procesarJSON(json);
            };
            reader.readAsArrayBuffer(file);
        });

        $('#btn_filtrar').on('click', function() {
            aplicarFiltros();
        });
    });

    function procesarJSON(json) {
        datosMemoria = [];
        
        json.forEach(row => {
            let cotizacion = row['N° COTIZACION'] || row['COTIZACION'] || '';
            let remision = row['N° REMISION'] || row['REMISION'] || '';
            let categoria = row['CATEGORIA'] || '';
            let fecha = row['FECHA DE REALIZACION'] || row['FECHA REALIZACION'] || '';
            let estado = row['ESTADO'] || '';
            let items = row['ITEMS'] || '';
            
            // Limpiar subtotal a numérico
            let subtotalStr = row['SUBTOTAL'] || row['TOTAL COT'] || '0';
            let subtotal = parseFloat(String(subtotalStr).replace(/[^0-9.-]+/g,"")) || 0;

            // Ignorar filas en blanco o los "Total general" del final del Excel
            if (cotizacion && cotizacion.trim() !== '' && !cotizacion.toUpperCase().includes('TOTAL')) {
                datosMemoria.push({
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

        if (datosMemoria.length === 0) {
            alert("No se encontraron cotizaciones. Verifica que la pestaña de tu Excel se llame 'CONTROL COT'.");
            return;
        }

        aplicarFiltros();
        alert("¡Archivo cargado y procesado exitosamente!");
    }

    function aplicarFiltros() {
        const f_inicio = $('#filtro_inicio').val();
        const f_fin = $('#filtro_fin').val();
        const f_cat = $('#filtro_categoria').val().toUpperCase();
        const f_estado = $('#filtro_estado').val().toUpperCase();

        let datosFiltrados = datosMemoria.filter(item => {
            let pasaFiltro = true;

            // Para comparar fechas correctamente
            if (f_inicio && item.fecha < f_inicio) pasaFiltro = false;
            if (f_fin && item.fecha > f_fin) pasaFiltro = false;
            
            if (f_cat && (item.categoria || '').toUpperCase() !== f_cat) pasaFiltro = false;
            if (f_estado && (item.estado || '').toUpperCase() !== f_estado) pasaFiltro = false;

            return pasaFiltro;
        });

        actualizarTabla(datosFiltrados);
    }

    function actualizarTabla(datos) {
        let granTotal = 0;
        let rowsToAdd = [];

        datos.forEach(item => {
            granTotal += item.subtotal;
            
            // Limitar texto muy largo para que no rompa el PDF
            let textoItems = item.items.length > 50 ? item.items.substring(0, 50) + '...' : item.items;

            rowsToAdd.push([
                item.cotizacion, 
                item.remision,
                item.fecha,
                item.categoria,
                item.estado,
                textoItems, // Ya no enviamos etiquetas HTML, solo texto puro
                `$${item.subtotal.toLocaleString('es-CO')}`
            ]);
        });

        // Forma correcta de actualizar DataTables para que el plugin de exportación no falle
        dataTable.clear().rows.add(rowsToAdd).draw();

        $('#resumen_cantidad').text(datos.length);
        $('#resumen_valor').text(`$${granTotal.toLocaleString('es-CO')}`);
    }
</script>