<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-3xl mx-auto">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                <i class="fas fa-handshake text-indigo-600 mr-2"></i> Nuevo Cliente
            </h1>
            <p class="text-gray-500 mt-1">Registra una nueva empresa o cliente para gestionar sus puntos.</p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
                <p class="font-bold">¡Error!</p>
                <ul class="list-disc list-inside ml-2 text-sm">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>clienteCrear" method="POST" class="space-y-6">

            <div>
                <label for="nombre_cliente" class="block text-sm font-bold text-gray-700 mb-1">Nombre / Razón Social <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-building text-gray-400"></i></div>
                    <input type="text" id="nombre_cliente" name="nombre_cliente" required
                        placeholder="Ej: Almacenes Éxito S.A."
                        value="<?= $datosPrevios['nombre_cliente'] ?? '' ?>"
                        class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 uppercase">
                </div>
            </div>

            <div>
                <label for="codigo_cliente" class="block text-sm font-bold text-gray-700 mb-1">Código Interno / NIT</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-barcode text-gray-400"></i></div>
                    <input type="text" id="codigo_cliente" name="codigo_cliente"
                        placeholder="Ej: CLI-001"
                        value="<?= $datosPrevios['codigo_cliente'] ?? '' ?>"
                        class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 uppercase">
                </div>
                <p class="text-xs text-gray-400 mt-1">Este código debe ser único en el sistema.</p>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>clienteVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition-all">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all transform hover:-translate-y-1">
                    <i class="fas fa-save mr-2"></i> Guardar Cliente
                </button>
            </div>
        </form>
    </div>
</div>