<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-2xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit text-yellow-500 mr-2"></i> Editar Delegación
            </h1>
            <p class="text-gray-500 mt-1">Modificando: <strong><?= htmlspecialchars($datos['nombre_delegacion']) ?></strong></p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <p class="font-bold">¡Error!</p>
                <ul class="list-disc list-inside ml-2 text-sm">
                    <?php foreach ($errores as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="id_delegacion" value="<?= $id ?>">

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre Delegación <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-building text-gray-400"></i></div>
                    <input type="text" name="nombre_delegacion" required
                        value="<?= htmlspecialchars($datos['nombre_delegacion']) ?>"
                        class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 uppercase">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-toggle-on text-gray-400"></i></div>
                    <select name="estado" class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 bg-white">
                        <option value="1" <?= $datos['estado'] == 1 ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= $datos['estado'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>delegacionVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-yellow-500 text-white font-bold rounded-lg shadow-md hover:bg-yellow-600 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-sync-alt mr-2"></i> Actualizar
                </button>
            </div>
        </form>
    </div>
</div>