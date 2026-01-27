<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* Tus estilos personalizados */
    .dataTables_length select, .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin: 0 0.5rem;
    }
    .dataTables_length label, .dataTables_filter label {
        color: #4b5563 !important;
        font-weight: 500;
        display: flex; align-items: center;
    }
    #tablaInventario tbody tr { background-color: white !important; }
    #tablaInventario tbody tr:hover { background-color: #f9fafb !important; }
    .dataTables_paginate .paginate_button.current, .dataTables_paginate .paginate_button:hover {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }
    .dataTables_wrapper>div:first-child, .dataTables_wrapper>div:last-of-type {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin: 1.5rem 0;
    }
    /* Estilo Select2 */
    .select2-container .select2-selection--single {
        height: 42px !important;
        border-color: #d1d5db !important;
        display: flex; align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }
</style>

<div class="w-full max-w-7xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="flex flex-col md:flex-row justify-between items-center mb-6 border-b pb-4 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-dolly-flatbed text-indigo-600 mr-2"></i> Inventario de Técnicos
                </h1>
                <p class="text-gray-500 mt-1">Control de stock asignado.</p>
            </div>
            
            <div class="flex gap-2">
                <?php if (!empty($inventario)): ?>
                    <button type="button" onclick="generarExcelInventario()" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-green-700 transition shadow-md flex items-center gap-2">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>inventarioTecnicoCrear" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md flex items-center gap-2">
                    <i class="fas fa-plus"></i> Asignar Stock
                </a>
            </div>
        </div>


        <div class="overflow-hidden">
            <table id="tablaInventario" class="w-full text-left border-collapse stripe hover">
                <thead>
                    <tr class="bg-gray-50 text-gray-700 uppercase text-xs tracking-wider">
                        <th class="p-4 border-b">Técnico</th>
                        <th class="p-4 border-b">Repuesto</th>
                        <th class="p-4 border-b text-center">Cantidad</th>
                        <th class="p-4 border-b text-center">Última Carga</th>
                        <th class="p-4 border-b text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    <?php foreach ($inventario as $item): ?>
                        <tr>
                            <td class="p-4 font-bold text-gray-800"><?= $item['nombre_tecnico'] ?></td>
                            <td class="p-4 text-gray-600">
                                <div class="flex flex-col">
                                    <span class="font-medium"><?= $item['nombre_repuesto'] ?></span>
                                    <?php if (!empty($item['codigo_referencia'])): ?>
                                        <span class="text-xs text-gray-400">Ref: <?= $item['codigo_referencia'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($item['cantidad_actual'] > 0): ?>
                                    <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full font-bold text-xs"><?= $item['cantidad_actual'] ?></span>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-800 py-1 px-3 rounded-full font-bold text-xs">Agotado</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center text-xs text-gray-500">
                                <span data-order="<?= strtotime($item['ultima_actualizacion']) ?>">
                                    <?= date('d/m/Y H:i', strtotime($item['ultima_actualizacion'])) ?>
                                </span>
                            </td>
                            <td class="p-4 text-right space-x-2">
                                <a href="<?= BASE_URL ?>inventarioTecnicoEditar?id=<?= $item['id_inventario'] ?>" class="text-blue-500 hover:text-blue-700 bg-blue-50 p-2 rounded-full hover:bg-blue-100 transition inline-block"><i class="fas fa-edit"></i></a>
                                <a href="<?= BASE_URL ?>inventarioTecnicoEliminar?id=<?= $item['id_inventario'] ?>" onclick="return confirm('¿Seguro?');" class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-full hover:bg-red-100 transition inline-block"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // 3. Pasamos los datos de PHP a JS tal como en tu ejemplo
    const datosInventario = <?= json_encode($inventario ?? []) ?>;

    $(document).ready(function() {
        var table = $('#tablaInventario').DataTable({
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            dom: '<"flex flex-wrap justify-between items-center mb-4"lf>rt<"flex flex-wrap justify-between items-center mt-4"ip>',
            order: [[0, 'asc']]
        });

        $('#filtroTecnico').select2({
            placeholder: "Buscar técnico...",
            allowClear: true,
            width: '100%'
        });

        $('#filtroTecnico').on('change', function() {
            var val = $(this).val();
            table.column(0).search(val ? val : '', true, false).draw();
        });
    });

    // 4. Función JS para generar el Excel usando SheetJS
    function generarExcelInventario() {
        if (datosInventario.length === 0) {
            alert("No hay datos de inventario para exportar");
            return;
        }

        // Mapeamos los datos para que las columnas tengan nombres bonitos
        const datosExcel = datosInventario.map(d => ({
            "Técnico": d.nombre_tecnico,
            "Repuesto": d.nombre_repuesto,
            "Referencia": d.codigo_referencia || "S/R",
            "Cantidad Actual": d.cantidad_actual,
            "Última Actualización": d.ultima_actualizacion
        }));

        const ws = XLSX.utils.json_to_sheet(datosExcel);
        const wb = XLSX.utils.book_new();

        // Ajustamos ancho de columnas (Opcional pero recomendado)
        ws['!cols'] = [
            {wch: 25}, // Técnico
            {wch: 30}, // Repuesto
            {wch: 15}, // Ref
            {wch: 15}, // Cantidad
            {wch: 20}  // Fecha
        ];

        XLSX.utils.book_append_sheet(wb, ws, "Inventario");
        
        // Generamos el nombre del archivo con la fecha de hoy
        const fechaHoy = new Date().toISOString().slice(0,10);
        XLSX.writeFile(wb, `Inventario_Tecnicos_${fechaHoy}.xlsx`);
    }
</script>