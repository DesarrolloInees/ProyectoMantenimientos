<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-6xl mx-auto"> <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-layer-group text-blue-600 mr-2"></i> Tarifario Masivo</h1>
            <p class="text-gray-500 mt-1">Configura todos los precios para una máquina en un solo paso.</p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <ul class="list-disc list-inside text-sm"><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>tarifaCrear" method="POST" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded-lg border">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Máquina a Configurar</label>
                    <select name="id_tipo_maquina" required class="w-full px-3 py-2 border rounded shadow-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Seleccionar Máquina --</option>
                        <?php foreach ($listaMaquinas as $item): ?>
                            <option value="<?= $item['id_tipo_maquina'] ?>">
                                <?= htmlspecialchars($item['nombre_tipo_maquina']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Año Vigencia</label>
                    <input type="number" name="año_vigencia" required min="2020" max="2030"
                        value="<?= date('Y') ?>"
                        class="w-full px-3 py-2 border rounded shadow-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-800 text-white">
                            <th class="p-3 text-left border border-gray-600">Tipo de Mantenimiento</th>
                            <?php foreach ($listaModalidades as $mod): ?>
                                <th class="p-3 text-center border border-gray-600">
                                    <?= htmlspecialchars($mod['nombre_modalidad']) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaMantenimientos as $manto): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-3 border font-bold text-gray-700 bg-gray-100">
                                    <?= htmlspecialchars($manto['nombre_completo']) ?>
                                </td>
                                
                                <?php foreach ($listaModalidades as $mod): ?>
                                    <td class="p-2 border text-center">
                                        <div class="relative">
                                            <span class="absolute left-2 top-2 text-gray-400 text-xs">$</span>
                                            <input type="number" 
                                                name="precios[<?= $manto['id_tipo_mantenimiento'] ?>][<?= $mod['id_modalidad'] ?>]" 
                                                step="0.01" min="0" placeholder="0.00"
                                                class="w-full pl-6 pr-2 py-1 border rounded text-right focus:bg-green-50 focus:border-green-500 font-mono text-sm">
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="text-xs text-gray-500 mt-2">* Deja en blanco o en 0 las casillas que no apliquen.</p>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>tarifaVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-save mr-2"></i> Guardar Todas las Tarifas
                </button>
            </div>
        </form>
    </div>
</div>