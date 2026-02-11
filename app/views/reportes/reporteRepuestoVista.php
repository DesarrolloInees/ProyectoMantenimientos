<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">

<style>
    /* Estilos base (Mismos de siempre) */
    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-left: 0.75rem;
        color: #374151;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }

    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin: 0 0.5rem;
    }

    #reporteTable tbody tr {
        background-color: white !important;
    }

    #reporteTable tbody tr:hover {
        background-color: #f9fafb !important;
    }

    .dataTables_paginate .paginate_button.current {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }

    .dataTables_wrapper>div:first-child,
    .dataTables_wrapper>div:last-of-type {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        margin: 1.5rem 0;
    }
</style>

<div class="w-full max-w-7xl mx-auto">

    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-cogs text-blue-600 mr-2"></i> Reporte de Repuestos</h1>
                <p class="text-gray-500 text-sm">Control de inventario y salida de repuestos.</p>
            </div>

            <div class="flex gap-2">
                <?php if (!empty($datosReporte)): ?>
                    <button type="button" onclick="exportarExcelResumen()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transform hover:scale-105 transition text-sm">
                        <i class="fas fa-file-excel"></i> Resumen General
                    </button>

                    <button type="button" onclick="exportarDetalleInees()" class="bg-indigo-600 text-white px-4 py-2 rounded font-bold hover:bg-indigo-700 shadow flex items-center gap-2 transform hover:scale-105 transition text-sm">
                        <i class="fas fa-boxes"></i> Detallado INEES
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <form action="<?= BASE_URL ?>reporteRepuesto" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-1">Origen del Repuesto</label>
                <select name="origen" class="select2-search w-full border border-gray-300 rounded-lg">
                    <option value="">-- Todos los Orígenes --</option>
                    <option value="INEES" <?= ($filtros['origen'] == 'INEES') ? 'selected' : '' ?>>INEES</option>
                    <option value="PROSEGUR" <?= ($filtros['origen'] == 'PROSEGUR') ? 'selected' : '' ?>>PROSEGUR</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde</label>
                <input type="date" name="fecha_inicio" value="<?= $filtros['fecha_inicio'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label>
                <input type="date" name="fecha_fin" value="<?= $filtros['fecha_fin'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-all">
                    <i class="fas fa-search mr-2"></i> Generar
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($datosReporte)): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                <p class="text-sm text-blue-600 font-bold uppercase">Referencias Distintas</p>
                <p class="text-2xl font-bold text-gray-800"><?= count($datosReporte) ?></p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                <p class="text-sm text-green-600 font-bold uppercase">Total Piezas Usadas</p>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($totalPiezas) ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <p class="text-sm text-gray-500 font-bold uppercase">Rango Consultado</p>
                <p class="text-sm font-medium text-gray-800 mt-1">
                    <?= date('d/m/Y', strtotime($filtros['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($filtros['fecha_fin'])) ?>
                </p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
            <div class="overflow-x-auto">
                <table id="reporteTable" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                        <tr>
                            <th class="py-3 px-4">Ref.</th>
                            <th class="py-3 px-4">Repuesto</th>
                            <th class="py-3 px-4 text-center">Origen</th>
                            <th class="py-3 px-4 text-center">Veces Solicitado</th>
                            <th class="py-3 px-4 text-right">Cantidad Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($datosReporte as $fila): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 whitespace-nowrap text-gray-500 font-mono"><?= htmlspecialchars($fila['codigo_referencia'] ?? 'S/R') ?></td>
                                <td class="py-3 px-4 font-bold text-gray-800"><?= htmlspecialchars($fila['nombre_repuesto']) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($fila['origen'] == 'INEES'): ?>
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">INEES</span>
                                    <?php else: ?>
                                        <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">PROSEGUR</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center text-gray-600"><?= $fila['veces_usado'] ?></td>
                                <td class="py-3 px-4 text-right font-bold text-blue-600 text-lg"><?= $fila['total_cantidad'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-yellow-400"></i></div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700"><?= htmlspecialchars($mensaje) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>

<script>
    // 1. DATA PHP -> JS
    const datosResumen = <?= json_encode($datosReporte ?? []) ?>;
    const datosInees = <?= json_encode($datosInees ?? []) ?>; // <--- AQUÍ ESTÁ LA NUEVA DATA DETALLADA

    $(document).ready(function() {
        $('.select2-search').select2({
            width: '100%',
            minimumResultsForSearch: Infinity
        });
        if ($('#reporteTable').length) {
            $('#reporteTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                order: [
                    [4, "desc"]
                ]
            });
        }
    });

    // ==========================================
    // EXPORTAR RESUMEN (BOTÓN VERDE)
    // ==========================================
    function exportarExcelResumen() {
        if (!datosResumen.length) return alert("Sin datos.");
        let workbook = XLSX.utils.book_new();
        let matriz = [
            ['CÓDIGO', 'REPUESTO', 'ORIGEN', 'VECES SOLICITADO', 'CANTIDAD TOTAL']
        ];
        datosResumen.forEach(d => matriz.push([d.codigo_referencia, d.nombre_repuesto, d.origen, parseInt(d.veces_usado), parseInt(d.total_cantidad)]));
        let ws = XLSX.utils.aoa_to_sheet(matriz);
        ws['!cols'] = [{
            wch: 15
        }, {
            wch: 40
        }, {
            wch: 15
        }, {
            wch: 20
        }, {
            wch: 15
        }];
        XLSX.utils.book_append_sheet(workbook, ws, "Resumen");
        XLSX.writeFile(workbook, `Resumen_Repuestos.xlsx`);
    }

    // ==========================================
    // EXPORTAR DETALLE INEES (ACTUALIZADO CON CÓDIGO, VALOR Y SUBTOTAL)
    // ==========================================
    function exportarDetalleInees() {
        if (!datosInees || datosInees.length === 0) {
            alert("No hay datos detallados de INEES o hubo un error al cargarlos.");
            return;
        }

        let workbook = XLSX.utils.book_new();

        // 1. Encabezados
        let matriz = [
            ['REPORTE DETALLADO DE REPUESTOS - INEES'],
            [], // Fila vacía de separación
            [
                'CLIENTE',
                'PUNTO / SEDE',
                'DELEGACIÓN',
                'MÁQUINA (ID)',
                'TIPO MÁQUINA',
                'REMISIÓN',
                'DESCRIPCIÓN DEL SERVICIO',
                'REPUESTO',
                'CÓDIGO', // <--- NUEVO
                'VALOR', // <--- NUEVO (VACÍO)
                'CANT.',
                'SUBTOTAL' // <--- NUEVO (VACÍO)
            ]
        ];

        // 2. Llenar filas
        datosInees.forEach(d => {
            matriz.push([
                d.nombre_cliente,
                d.nombre_punto,
                d.nombre_delegacion || 'N/A',
                d.device_id,
                d.nombre_tipo_maquina || 'N/A',
                d.numero_remision,
                d.observacion,
                d.nombre_repuesto,
                d.codigo_referencia || 'S/C', // <--- Aquí va el código (S/C si no tiene)
                "", // <--- VALOR (Celda vacía para llenar manual)
                parseInt(d.cantidad),
                "" // <--- SUBTOTAL (Celda vacía para formula manual)
            ]);
        });

        // 3. Crear hoja
        let ws = XLSX.utils.aoa_to_sheet(matriz);

        // 4. Ajustar anchos de columnas
        ws['!cols'] = [{
                wch: 30
            }, // Cliente
            {
                wch: 25
            }, // Punto
            {
                wch: 20
            }, // Delegación
            {
                wch: 15
            }, // Máquina
            {
                wch: 20
            }, // Tipo Máquina
            {
                wch: 15
            }, // Remisión
            {
                wch: 50
            }, // Observación
            {
                wch: 35
            }, // Repuesto
            {
                wch: 15
            }, // CÓDIGO (Nuevo ancho)
            {
                wch: 12
            }, // VALOR (Nuevo ancho)
            {
                wch: 8
            }, // Cantidad
            {
                wch: 12
            } // SUBTOTAL (Nuevo ancho)
        ];

        XLSX.utils.book_append_sheet(workbook, ws, "Detalle INEES");
        let fecha = new Date().toISOString().slice(0, 10);
        XLSX.writeFile(workbook, `Detalle_Repuestos_INEES_${fecha}.xlsx`);
    }
</script>