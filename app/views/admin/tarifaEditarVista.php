<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-4xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-yellow-500 mr-2"></i> Editar Tarifa</h1>
            <p class="text-gray-500 mt-1">Modificando tarifa ID #<?= $id ?></p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <ul class="list-disc list-inside text-sm"><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="id_tarifa" value="<?= $id ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Máquina</label>
                    <select name="id_tipo_maquina" required class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 bg-white">
                        <?php foreach ($listaMaquinas as $item): ?>
                            <option value="<?= $item['id_tipo_maquina'] ?>" <?= $datos['id_tipo_maquina'] == $item['id_tipo_maquina'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['nombre_tipo_maquina']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tipo Mantenimiento</label>
                    <select name="id_tipo_mantenimiento" required class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 bg-white">
                        <?php foreach ($listaMantenimientos as $item): ?>
                            <option value="<?= $item['id_tipo_mantenimiento'] ?>" <?= $datos['id_tipo_mantenimiento'] == $item['id_tipo_mantenimiento'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Modalidad</label>
                    <select name="id_modalidad" required class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 bg-white">
                        <?php foreach ($listaModalidades as $item): ?>
                            <option value="<?= $item['id_modalidad'] ?>" <?= $datos['id_modalidad'] == $item['id_modalidad'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['nombre_modalidad']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Año Vigencia</label>
                    <input type="number" name="año_vigencia" required min="2020" max="2030"
                        value="<?= $datos['año_vigencia'] ?>"
                        class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Precio ($)</label>
                    <input type="number" name="precio" required step="0.01" min="0"
                        value="<?= $datos['precio'] ?>"
                        class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-yellow-500 text-lg font-bold text-gray-700">
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>tarifaVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-yellow-500 text-white font-bold rounded-lg shadow-md hover:bg-yellow-600 transform hover:-translate-y-1 transition-all">Actualizar</button>
            </div>
        </form>
    </div>
</div>