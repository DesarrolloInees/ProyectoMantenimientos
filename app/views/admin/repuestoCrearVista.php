<?php
// app/views/admin/repuestoCrearVista.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");
?>

<div class="w-full max-w-3xl mx-auto">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4 flex items-center justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                    <i class="fas fa-box-open text-indigo-600 mr-2"></i> Crear Nuevo Repuesto
                </h1>
                <p class="text-gray-500 mt-1 ml-1">Registra los items para el inventario de mantenimiento.</p>
            </div>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm animate-pulse" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2 text-lg"></i>
                    <p class="font-bold">¡Atención!</p>
                </div>
                <ul class="list-disc list-inside ml-6 mt-1 text-sm">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>repuestoCrear" method="POST" class="space-y-6">

            <div>
                <label for="nombre_repuesto" class="block text-sm font-bold text-gray-700 mb-1">
                    Nombre del Repuesto <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-tag text-gray-400"></i>
                    </div>
                    <input type="text" id="nombre_repuesto" name="nombre_repuesto" required
                        placeholder="Ej: Batería 12V 7Ah"
                        value="<?= $datosPrevios['nombre_repuesto'] ?? '' ?>"
                        class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
                <p class="text-xs text-gray-400 mt-1">Nombre descriptivo del componente.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label for="codigo_referencia" class="block text-sm font-bold text-gray-700 mb-1">
                        Código / Referencia
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-barcode text-gray-400"></i>
                        </div>
                        <input type="text" id="codigo_referencia" name="codigo_referencia"
                            placeholder="Ej: REF-2025-X"
                            value="<?= $datosPrevios['codigo_referencia'] ?? '' ?>"
                            class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all uppercase">
                    </div>
                </div>

                <div>
                    <label for="estado" class="block text-sm font-bold text-gray-700 mb-1">
                        Estado
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-toggle-on text-gray-400"></i>
                        </div>
                        <select id="estado" name="estado"
                            class="pl-10 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white transition-all appearance-none cursor-pointer">

                            <?php
                            $estadoPrevio = $datosPrevios['estado'] ?? '1';
                            ?>
                            <option value="1" <?= $estadoPrevio == '1' ? 'selected' : '' ?>>Activo (Disponible)</option>
                            <option value="0" <?= $estadoPrevio == '0' ? 'selected' : '' ?>>Inactivo (No Disponible)</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>repuestoVer"
                    class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                    <i class="fas fa-times mr-2"></i> Cancelar
                </a>

                <button type="submit"
                    class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-lg hover:bg-indigo-700 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 transform hover:-translate-y-1">
                    <i class="fas fa-save mr-2"></i> Guardar Repuesto
                </button>
            </div>
        </form>
    </div>
</div>