<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-lg mx-auto mt-10">
    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit text-orange-500 mr-2"></i> Ajuste Manual de Stock
            </h1>
            <p class="text-gray-500 mt-1 text-sm">Corrige la cantidad actual en posesión del técnico.</p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm">
                <?php foreach ($errores as $error): ?><p><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>inventarioTecnicoEditar" method="POST" class="space-y-6">
            <input type="hidden" name="id_inventario" value="<?= $datos['id_inventario'] ?>">

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg border">
                    <label class="block text-xs font-bold text-gray-400 uppercase">Técnico</label>
                    <div class="text-gray-800 font-semibold"><?= $datos['nombre_tecnico'] ?></div>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg border">
                    <label class="block text-xs font-bold text-gray-400 uppercase">Repuesto</label>
                    <div class="text-gray-800 font-semibold"><?= $datos['nombre_repuesto'] ?></div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Cantidad Real en Stock</label>
                <div class="relative">
                    <input type="number" name="cantidad" required min="0"
                        value="<?= $datos['cantidad_actual'] ?>"
                        class="block w-full px-4 py-4 text-center text-2xl font-bold border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-orange-600">
                    <div class="absolute right-4 top-5 text-gray-400 font-bold text-xs">UNIDS</div>
                </div>
                <p class="text-xs text-gray-400 mt-2 text-center">
                    <i class="fas fa-info-circle"></i> Use esto para correcciones (pérdidas, conteos, etc).
                </p>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-between items-center">
                <a href="<?= BASE_URL ?>inventarioTecnicoVer" class="text-gray-500 hover:text-gray-700 font-semibold">
                    Cancelar
                </a>
                <button type="submit" class="px-8 py-3 bg-orange-500 text-white font-bold rounded-lg shadow-md hover:bg-orange-600 transform hover:-translate-y-1 transition-all">
                    Actualizar Cantidad
                </button>
            </div>
        </form>
    </div>
</div>