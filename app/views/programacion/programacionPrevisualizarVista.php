<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-7xl mx-auto p-6 bg-white shadow-lg rounded-xl border border-gray-200 mt-6">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Verificar Propuesta</h1>
            <p class="text-sm text-gray-500">Revise la distribución antes de guardar en la base de datos.</p>
        </div>
        <div class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-lg font-bold border border-indigo-200">
            Total a crear: <?= isset($simulacion) ? count($simulacion) : 0 ?>
        </div>
    </div>

    <?php if (empty($simulacion)): ?>
        <div class="text-center py-10 bg-gray-50 rounded border border-dashed">
            <p class="text-gray-500 text-lg">No se encontraron máquinas pendientes con los filtros seleccionados.</p>
            <p class="text-sm text-gray-400 mt-2">Intenta cambiar el rango de fechas o seleccionar más zonas.</p>
            <a href="<?= BASE_URL ?>programacionCrear" class="text-indigo-600 font-bold hover:underline mt-4 inline-block">Volver a filtrar</a>
        </div>
    <?php else: ?>

        <form action="<?= BASE_URL ?>programacionPrevisualizar/guardar" method="POST">
            
            <div class="overflow-auto max-h-[600px] border rounded-lg shadow-inner">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3">Punto / Dirección</th>
                            <th class="px-4 py-3">Ubicación</th>
                            <th class="px-4 py-3 w-64">Técnico Asignado (Editar)</th>
                            <th class="px-4 py-3 w-48">Fecha Visita (Editar)</th>
                            <th class="px-4 py-3 text-center">Quitar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($simulacion as $i => $row): ?>
                            <tr id="fila_<?= $i ?>" class="hover:bg-indigo-50 transition duration-150">
                                
                                <input type="hidden" name="ordenes[<?= $i ?>][id_maquina]" value="<?= $row['id_maquina'] ?>">

                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-800"><?= htmlspecialchars($row['info'] ?? 'Sin nombre') ?></div>
                                    <div class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($row['direccion'] ?? 'Sin dirección') ?></div>
                                </td>

                                <td class="px-4 py-3">
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-medium">
                                        <?= htmlspecialchars($row['ubicacion'] ?? 'N/A') ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <select name="ordenes[<?= $i ?>][id_tecnico]" class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                                        <?php if (!empty($listaTecnicos)): ?>
                                            <?php foreach ($listaTecnicos as $idTech => $nomTech): ?>
                                                <option value="<?= $idTech ?>" <?= ($idTech == ($row['id_tecnico'] ?? 0)) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($nomTech) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="">No hay técnicos cargados</option>
                                        <?php endif; ?>
                                    </select>
                                </td>

                                <td class="px-4 py-3">
                                    <input type="date" name="ordenes[<?= $i ?>][fecha]" value="<?= $row['fecha'] ?? '' ?>" 
                                           class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <button type="button" onclick="eliminarFila(<?= $i ?>)" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-full transition">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end items-center gap-4 pt-4 border-t border-gray-200">
                <a href="<?= BASE_URL ?>programacionCrear" class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Volver a Configurar
                </a>
                <button type="submit" class="px-8 py-2.5 text-white bg-indigo-600 rounded-lg font-bold shadow-md hover:bg-indigo-700 hover:shadow-lg transform hover:-translate-y-0.5 transition duration-200">
                    <i class="fas fa-save mr-2"></i> Confirmar y Generar Órdenes
                </button>
            </div>

        </form>
    <?php endif; ?>
</div>

<script>
function eliminarFila(index) {
    if(confirm("¿Seguro que quieres descartar este servicio?")) {
        const fila = document.getElementById('fila_' + index);
        fila.style.opacity = '0.5';
        setTimeout(() => fila.remove(), 200);
    }
}
</script>