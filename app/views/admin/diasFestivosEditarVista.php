<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-2xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit text-orange-500 mr-2"></i> Editar Festivo
            </h1>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm">
                <?php foreach ($errores as $error): ?><p><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>diasFestivosEditar" method="POST" class="space-y-6">
            <input type="hidden" name="id_festivo" value="<?= $datos['id_festivo'] ?>">

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Fecha</label>
                <input type="date" name="fecha" required
                    value="<?= $datos['fecha'] ?>"
                    class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                <input type="text" name="descripcion"
                    value="<?= htmlspecialchars($datos['descripcion']) ?>"
                    class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>diasFestivosVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-orange-500 text-white font-bold rounded-lg shadow-md hover:bg-orange-600 transition-all">
                    Actualizar
                </button>
            </div>
        </form>
    </div>
</div>