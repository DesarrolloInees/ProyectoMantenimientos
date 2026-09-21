<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<style>
    .dataTables_length select, .dataTables_filter input {
        background-color: white !important; color: #374151 !important;
        border: 1px solid #d1d5db !important; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; margin: 0 0.5rem;
    }
    #tablaTrazabilidad tbody tr, #tablaTop tbody tr { background-color: white !important; }
    #tablaTrazabilidad tbody tr:hover, #tablaTop tbody tr:hover { background-color: #f9fafb !important; }
    .dataTables_paginate .paginate_button.current {
        background-color: #4f46e5 !important; color: white !important; border-color: #4f46e5 !important;
    }
    .dataTables_wrapper>div:first-child, .dataTables_wrapper>div:last-of-type {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin: 1.5rem 0;
    }
</style>
<div class="w-full px-4 md:px-6">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-4 border-b pb-2 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-route text-teal-600 mr-2"></i> Trazabilidad de Repuestos</h1>
                <p class="text-gray-500 text-sm">Detalle por punto y top consolidado por tipo de maquina. Descarga en Excel (2 hojas).</p>
            </div>
            <?php if (!empty($datosTrazabilidad) || !empty($datosTop)): ?>
            <a href="<?= BASE_URL ?>reporteTrazabilidad?accion=descargarExcel&fecha_inicio=<?= $filtros['fecha_inicio'] ?>&fecha_fin=<?= $filtros['fecha_fin'] ?>"
               class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow flex items-center gap-2 text-sm">
                <i class="fas fa-file-excel"></i> Descargar Excel (2 hojas)
            </a>
            <?php endif; ?>
        </div>
        <form action="<?= BASE_URL ?>reporteTrazabilidad" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde</label>
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($filtros['fecha_inicio']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($filtros['fecha_fin']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <button type="submit" class="w-full py-2 px-4 bg-teal-600 text-white font-bold rounded-lg shadow hover:bg-teal-700 transition-all">
                    <i class="fas fa-search mr-2"></i> Consultar
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($mensaje) && empty($datosTrazabilidad)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm mb-6">
            <p class="text-sm text-yellow-700"><?= htmlspecialchars($mensaje) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($datosTrazabilidad) || !empty($datosTop)): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-teal-50 p-4 rounded-lg border border-teal-100">
                <p class="text-sm text-teal-600 font-bold uppercase">Registros detalle</p>
                <p class="text-2xl font-bold text-gray-800"><?= number_format(count($datosTrazabilidad)) ?></p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                <p class="text-sm text-green-600 font-bold uppercase">Total piezas</p>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($totalPiezas) ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <p class="text-sm text-gray-500 font-bold uppercase">Rango</p>
                <p class="text-sm font-medium text-gray-800 mt-1"><?= date('d/m/Y', strtotime($filtros['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($filtros['fecha_fin'])) ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-6">
            <h2 class="font-bold text-gray-800 mb-3">Hoja 1: Trazabilidad por Punto</h2>
            <div class="overflow-x-auto">
                <table id="tablaTrazabilidad" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                        <tr><th class="py-3 px-4">Cliente</th><th class="py-3 px-4">Punto</th><th class="py-3 px-4">Tipo Maquina</th><th class="py-3 px-4">Device ID</th><th class="py-3 px-4">Repuesto</th><th class="py-3 px-4">Origen</th><th class="py-3 px-4 text-center">Cant.</th><th class="py-3 px-4">Fecha Cambio</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($datosTrazabilidad as $f): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 font-bold"><?= htmlspecialchars($f['cliente']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($f['punto']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($f['tipo_maquina']) ?></td>
                            <td class="py-3 px-4 font-mono text-teal-700 font-bold"><?= htmlspecialchars($f['device_id']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($f['repuesto']) ?></td>
                            <td class="py-3 px-4 text-center"><?= htmlspecialchars($f['origen']) ?></td>
                            <td class="py-3 px-4 text-center font-bold"><?= (int)$f['cantidad'] ?></td>
                            <td class="py-3 px-4"><?= date('d/m/Y', strtotime($f['fecha_cambio'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-3">Hoja 2: Top Repuestos por Tipo de Maquina</h2>
            <div class="overflow-x-auto">
                <table id="tablaTop" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                        <tr><th class="py-3 px-4">Tipo Maquina</th><th class="py-3 px-4">Repuesto</th><th class="py-3 px-4">Origen</th><th class="py-3 px-4 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($datosTop as $t): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 font-bold"><?= htmlspecialchars($t['tipo_maquina']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($t['repuesto']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($t['origen']) ?></td>
                            <td class="py-3 px-4 text-right font-bold text-teal-700"><?= (int)$t['total_cambiados'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script>
$(document).ready(function(){
    if ($('#tablaTrazabilidad').length) { $('#tablaTrazabilidad').DataTable({responsive:true, language:{url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'}, order:[[1,'asc'],[7,'desc']]}); }
    if ($('#tablaTop').length) { $('#tablaTop').DataTable({responsive:true, language:{url:'//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'}, order:[[0,'asc'],[3,'desc']]}); }
});
</script>