<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-4xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-calendar-alt text-blue-600 mr-2"></i> Días Festivos
                </h1>
                <p class="text-gray-500 mt-1">Administra los días no laborales del sistema.</p>
            </div>
            <a href="<?= BASE_URL ?>diasFestivosCrear" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition shadow-md">
                <i class="fas fa-plus"></i> Nuevo Festivo
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-700 uppercase text-xs tracking-wider">
                        <th class="p-4 border-b">Fecha</th>
                        <th class="p-4 border-b">Descripción</th>
                        <th class="p-4 border-b text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (empty($festivos)): ?>
                        <tr>
                            <td colspan="3" class="p-4 text-center text-gray-400 italic">No hay días festivos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($festivos as $f): ?>
                            <tr class="hover:bg-blue-50 transition">
                                <td class="p-4 font-bold text-gray-800">
                                    <?= date('d/m/Y', strtotime($f['fecha'])) ?>
                                </td>
                                <td class="p-4 text-gray-600">
                                    <?= htmlspecialchars($f['descripcion'] ?? 'Sin descripción') ?>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <a href="<?= BASE_URL ?>diasFestivosEditar?id=<?= $f['id_festivo'] ?>" class="text-blue-500 hover:text-blue-700" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>diasFestivosEliminar?id=<?= $f['id_festivo'] ?>"
                                        onclick="return confirm('¿Estás seguro de eliminar este festivo?');"
                                        class="text-red-500 hover:text-red-700" title="Eliminar">
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