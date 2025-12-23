<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-6xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-dolly-flatbed text-indigo-600 mr-2"></i> Inventario de Técnicos
                </h1>
                <p class="text-gray-500 mt-1">Control de stock en poder de cada técnico.</p>
            </div>
            <a href="<?= BASE_URL ?>inventarioTecnicoCrear" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md flex items-center gap-2">
                <i class="fas fa-plus"></i> Asignar Stock
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-700 uppercase text-xs tracking-wider">
                        <th class="p-4 border-b">Técnico</th>
                        <th class="p-4 border-b">Repuesto</th>
                        <th class="p-4 border-b text-center">Cantidad</th>
                        <th class="p-4 border-b text-center">Última Carga</th>
                        <th class="p-4 border-b text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (empty($inventario)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-400 italic bg-gray-50 rounded-lg">
                                <i class="fas fa-box-open text-4xl mb-2 block text-gray-300"></i>
                                No hay inventario asignado actualmente.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventario as $item): ?>
                            <tr class="hover:bg-indigo-50 transition group">
                                <td class="p-4 font-bold text-gray-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-xs">
                                            <?= substr($item['nombre_tecnico'], 0, 2) ?>
                                        </div>
                                        <?= $item['nombre_tecnico'] ?>
                                    </div>
                                </td>
                                <td class="p-4 text-gray-600">
                                    <?= $item['nombre_repuesto'] ?>
                                    <?php if (!empty($item['codigo_referencia'])): ?>
                                        <span class="text-xs text-gray-400 block"><?= $item['codigo_referencia'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($item['cantidad_actual'] > 0): ?>
                                        <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full font-bold text-xs">
                                            <?= $item['cantidad_actual'] ?> Unids.
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-800 py-1 px-3 rounded-full font-bold text-xs">
                                            Agotado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center text-xs text-gray-400">
                                    <?= date('d/m/Y H:i', strtotime($item['ultima_actualizacion'])) ?>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <a href="<?= BASE_URL ?>inventarioTecnicoEditar?id=<?= $item['id_inventario'] ?>"
                                        class="text-blue-500 hover:text-blue-700 bg-blue-50 p-2 rounded-full hover:bg-blue-100 transition"
                                        title="Ajustar Stock Manualmente">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a href="<?= BASE_URL ?>inventarioTecnicoEliminar?id=<?= $item['id_inventario'] ?>"
                                        onclick="return confirm('¿Seguro que deseas eliminar este registro del inventario?');"
                                        class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-full hover:bg-red-100 transition"
                                        title="Eliminar del inventario">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>