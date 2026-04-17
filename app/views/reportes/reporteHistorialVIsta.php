<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">

<style>
    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin: 0 0.5rem;
    }
    #historialTable tbody tr { background-color: white !important; }
    #historialTable tbody tr:hover { background-color: #f9fafb !important; }
    .dataTables_paginate .paginate_button.current {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }
    .dataTables_wrapper>div:first-child,
    .dataTables_wrapper>div:last-of-type {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin: 1.5rem 0;
    }
</style>

<div class="w-full px-4 md:px-6">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex justify-between items-center flex-wrap gap-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-calendar-check text-blue-600 mr-2"></i> Reporte de Cumplimiento Semestral</h1>
                <p class="text-gray-500 text-sm">Validación de mantenimientos ejecutados vs. propuesta por máquina.</p>
            </div>
            <?php if (!empty($datosReporte)): ?>
                <button type="button" onclick="exportarExcelCumplimiento()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
            <?php endif; ?>
        </div>

        <form action="<?= BASE_URL ?>reporteHistorial" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= $filtros['fecha_inicio'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="<?= $filtros['fecha_fin'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-all">
                    <i class="fas fa-search mr-2"></i> Generar Búsqueda
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($datosReporte)): ?>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
            <div class="overflow-x-auto">
                <table id="historialTable" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                        <tr>
                            <th class="py-3 px-4">Fecha</th>
                            <th class="py-3 px-4">Cliente</th>
                            <th class="py-3 px-4">Punto</th>
                            <th class="py-3 px-4">Delegación</th>
                            <th class="py-3 px-4">Tipo Máquina</th>
                            <th class="py-3 px-4">Device ID</th>
                            <th class="py-3 px-4">Tipo Mantenimiento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($datosReporte as $fila): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($fila['fecha_visita'])) ?></td>
                                <td class="py-3 px-4 font-bold text-gray-800"><?= htmlspecialchars($fila['cliente']) ?></td>
                                <td class="py-3 px-4 text-gray-700"><?= htmlspecialchars($fila['punto']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($fila['dele'] ?? 'N/A') ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($fila['tipo_maquina'] ?? 'N/A') ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-blue-600"><?= htmlspecialchars($fila['device_id'] ?? 'N/A') ?></td>
                                <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($fila['tipo_mantenimiento'] ?? 'SIN ESPECIFICAR') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-4">
            <p class="text-yellow-700"><?= $mensaje ?></p>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script>
    const datosHistorial = <?= json_encode($datosExcel ?? []) ?>;

    $(document).ready(function() {
        if ($('#historialTable').length) {
            $('#historialTable').DataTable({
                responsive: true,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                order: [[0, "desc"]]
            });
        }
    });

    // =========================================================
    // EXPORTACIÓN A EXCEL (TODOS LOS PUNTOS)
    // =========================================================
    function exportarExcelCumplimiento() {
        try {
            if (typeof XLSX === 'undefined') {
                alert("Error: Librería SheetJS no cargada."); return;
            }
            if (!datosHistorial || datosHistorial.length === 0) {
                alert("No hay datos para exportar."); return;
            }

            let workbook = XLSX.utils.book_new();

            // ENCABEZADOS EN EL ORDEN SOLICITADO
            let encabezados = [
                'DEVICE ID', 
                'TIPO DE MÁQUINA', 
                'CLIENTE', 
                'PUNTO', 
                'DELEGACIÓN', 
                'PROPUESTA SEMESTRAL', 
                'TOTAL MANTENIMIENTOS', 
                'FALTAN', 
                'FECHA ÚLTIMO MANTENIMIENTO'
            ];
            
            let matriz = [encabezados];
            
            let granTotalPropuesta = 0;
            let granTotalMantenimientos = 0;
            let granTotalFaltantes = 0;

            // LLENAR FILAS
            datosHistorial.forEach(row => {
                let propuesta = row.frecuencia ? Math.floor(180 / row.frecuencia) : 'N/A';
                let faltantes = 'N/A';
                
                // Total general para mostrar en la columna (incluye correctivos, instalaciones, etc)
                let totalMtto = parseInt(row.total_mantenimientos) || 0;
                
                // Solo los preventivos para hacer la resta matemática
                let totalPreventivos = parseInt(row.total_preventivos) || 0;
                
                if (propuesta !== 'N/A') {
                    // Ahora restamos SOLO los preventivos y permitimos que queden en negativo
                    faltantes = propuesta - totalPreventivos;
                    
                    granTotalPropuesta += propuesta;
                    granTotalFaltantes += faltantes;
                }

                granTotalMantenimientos += totalMtto;

                let fila = [
                    row.device_id || 'N/A',
                    row.tipo_maquina || 'N/A',
                    row.cliente || 'N/A', 
                    row.punto || 'N/A', 
                    row.dele || 'N/A', 
                    propuesta,
                    totalMtto,
                    faltantes, // Aquí saldrá el negativo si se pasan de la propuesta
                    row.fecha_ultima || ''
                ];

                matriz.push(fila);
            });

            // FILA DE TOTALES AL FINAL DE LA TABLA
            let filaTotales = ['TOTALES', '', '', '', '', granTotalPropuesta, granTotalMantenimientos, granTotalFaltantes, ''];
            
            matriz.push([]); // Espacio en blanco
            matriz.push(filaTotales);

            // GENERAR HOJA EXCEL Y AJUSTAR COLUMNAS
            let ws = XLSX.utils.aoa_to_sheet(matriz);
            
            let wscols = [
                {wch: 20}, // DEVICE ID
                {wch: 25}, // TIPO MAQUINA
                {wch: 30}, // CLIENTE
                {wch: 35}, // PUNTO
                {wch: 15}, // DELEGACION
                {wch: 22}, // PROPUESTA
                {wch: 22}, // TOTAL MANTENIMIENTOS
                {wch: 12}, // FALTAN
                {wch: 28}  // FECHA ULTIMA
            ];
            ws['!cols'] = wscols;

            XLSX.utils.book_append_sheet(workbook, ws, "Historial_Mantenimientos");
            
            let fechaHoy = new Date().toISOString().slice(0, 10).replace(/-/g, "");
            XLSX.writeFile(workbook, `Historial_Mantenimientos_${fechaHoy}.xlsx`);

        } catch (error) {
            console.error("Error Excel:", error);
            alert("Error al generar Excel. Revisa consola (F12).");
        }
    }
</script>