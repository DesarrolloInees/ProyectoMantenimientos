<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-2xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-plus-circle text-blue-600 mr-2"></i> Nuevo Festivo
            </h1>
            <p class="text-gray-500 mt-1">Registra una fecha no laboral.</p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <p class="font-bold">¡Atención!</p>
                <ul class="list-disc list-inside ml-2 text-sm">
                    <?php foreach ($errores as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>diasFestivosCrear" method="POST" class="space-y-6">
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Selecciona la Fecha <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="date" name="fecha" required 
                        value="<?= htmlspecialchars($fecha ?? '') ?>"
                        class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción / Motivo</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-info-circle text-gray-400"></i></div>
                    <input type="text" name="descripcion" 
                        placeholder="Ej: Navidad, Año Nuevo, Semana Santa..."
                        value="<?= htmlspecialchars($descripcion ?? '') ?>"
                        class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>diasFestivosVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-save mr-2"></i> Guardar Festivo
                </button>
            </div>
        </form>
    </div>
</div>