<?php // app/views/reportes/reporteDevolucionVista.php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

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

    #tablaDevoluciones tbody tr {
        background-color: white !important;
    }

    #tablaDevoluciones tbody tr:hover {
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

<div class="w-full px-4 md:px-6">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-6 border-b pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-undo-alt text-orange-600 mr-2"></i> Control de Devoluciones
                </h1>
                <p class="text-gray-500 text-sm">Listado de piezas retiradas que los técnicos deben entregar en sede.</p>
            </div>

            <?php if (!empty($datosDevoluciones)): ?>
                <button type="button" onclick="exportarExcelDevoluciones()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 transition text-sm">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
            <?php endif; ?>
        </div>

        <form action="<?= BASE_URL ?>reporteDevolucion" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Técnico</label>
                <select name="id_tecnico" class="select2-tecnico w-full border border-gray-300 rounded-lg">
                    <option value="">-- Todos los Técnicos --</option>
                    <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= $t['id_tecnico'] ?>" <?= ($filtros['id_tecnico'] == $t['id_tecnico']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre_tecnico']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde</label>
                <input type="date" name="fecha_inicio" value="<?= $filtros['fecha_inicio'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label>
                <input type="date" name="fecha_fin" value="<?= $filtros['fecha_fin'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <button type="submit" class="w-full py-2 px-4 bg-orange-600 text-white font-bold rounded-lg shadow hover:bg-orange-700 transition-all">
                    <i class="fas fa-search mr-2"></i> Consultar Pendientes
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($datosDevoluciones)): ?>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
            <div class="overflow-x-auto">
                <table id="tablaDevoluciones" class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                        <tr>
                            <th class="py-3 px-4">Técnico</th>
                            <th class="py-3 px-4">Repuesto / Código</th>
                            <th class="py-3 px-4">Fecha Servicio</th>
                            <th class="py-3 px-4">Remisión</th>
                            <th class="py-3 px-4">Cliente / Punto</th>
                            <th class="py-3 px-4 text-center">Cant.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($datosDevoluciones as $d): ?>
                            <tr class="hover:bg-orange-50 transition-colors">
                                <td class="py-3 px-4 font-bold text-gray-800"><?= htmlspecialchars($d['nombre_tecnico']) ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-medium"><?= htmlspecialchars($d['nombre_repuesto']) ?></div>
                                    <div class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($d['codigo_referencia'] ?? 'S/R') ?></div>
                                </td>
                                <td class="py-3 px-4"><?= date('d/m/Y', strtotime($d['fecha_visita'])) ?></td>
                                <td class="py-3 px-4 font-semibold text-blue-600"><?= htmlspecialchars($d['numero_remision']) ?></td>
                                <td class="py-3 px-4">
                                    <div class="text-xs font-bold"><?= htmlspecialchars($d['nombre_cliente']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($d['nombre_punto']) ?></div>
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-lg"><?= $d['cantidad'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>

<script>
    const dataJS = <?= json_encode($datosDevoluciones ?? []) ?>;

    $(document).ready(function() {
        $('.select2-tecnico').select2();
        if ($('#tablaDevoluciones').length) {
            $('#tablaDevoluciones').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                order: [
                    [0, "asc"]
                ]
            });
        }
    });

    function exportarExcelDevoluciones() {
        if (!dataJS.length) return;
        let wb = XLSX.utils.book_new();

        // Agregamos la columna DELEGACIÓN al Excel
        let matriz = [
            ['TECNICO', 'REPUESTO', 'CODIGO', 'FECHA', 'REMISIÓN', 'DELEGACIÓN', 'CLIENTE', 'PUNTO', 'CANTIDAD']
        ];

        dataJS.forEach(d => {
            matriz.push([
                d.nombre_tecnico,
                d.nombre_repuesto,
                d.codigo_referencia || 'S/C',
                d.fecha_visita,
                d.numero_remision,
                d.nombre_delegacion || 'N/A', // <--- Dato de delegación
                d.nombre_cliente,
                d.nombre_punto,
                parseInt(d.cantidad)
            ]);
        });

        let ws = XLSX.utils.aoa_to_sheet(matriz);

        // Ajustamos los anchos de columna para que el Excel quede presentable
        ws['!cols'] = [{
                wch: 25
            }, // Tecnico
            {
                wch: 35
            }, // Repuesto
            {
                wch: 15
            }, // Codigo
            {
                wch: 12
            }, // Fecha
            {
                wch: 15
            }, // Remision
            {
                wch: 20
            }, // Delegacion
            {
                wch: 30
            }, // Cliente
            {
                wch: 30
            }, // Punto
            {
                wch: 10
            } // Cantidad
        ];

        XLSX.utils.book_append_sheet(wb, ws, "Pendientes Devolucion");
        XLSX.writeFile(wb, `Control_Devoluciones_<?= date('Y-m-d') ?>.xlsx`);
    }
</script>