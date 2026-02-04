<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-5xl mx-auto">
    
    <?php if (isset($mensaje)): ?>
        <div class="<?= $tipo_mensaje == 'success' ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400' ?> border px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold"><?= $tipo_mensaje == 'success' ? '¡Éxito!' : '¡Error!' ?></strong>
            <span class="block sm:inline"><?= $mensaje ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-blue-600 mr-2"></i> Edición Masiva</h1>
            <a href="<?= BASE_URL ?>tarifaVer" class="text-gray-500 hover:text-gray-700 underline">Volver al listado</a>
        </div>

        <form method="GET" action="<?= BASE_URL ?>tarifaMasiva" class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Seleccionar Máquina</label>
                <select name="id_maquina" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2" onchange="this.form.submit()">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($listaMaquinas as $m): ?>
                        <option value="<?= $m['id_tipo_maquina'] ?>" <?= $id_maquina == $m['id_tipo_maquina'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nombre_tipo_maquina']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Año de Vigencia</label>
                <input type="number" name="año" value="<?= $anio ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2" onchange="this.form.submit()">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-search mr-2"></i> Cargar Tarifas
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($tarifas)): ?>
        <form method="POST" action="" class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            <input type="hidden" name="filtro_maquina" value="<?= $id_maquina ?>">
            <input type="hidden" name="filtro_anio" value="<?= $anio ?>">
            <input type="hidden" name="guardar_masivo" value="1">

            <div class="p-4 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
                <h2 class="font-bold text-blue-800">Editando tarifas de: Año <?= $anio ?></h2>
                <span class="text-sm text-blue-600 bg-white px-3 py-1 rounded-full border border-blue-200"><?= count($tarifas) ?> registros encontrados</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="py-3 px-6">Tipo Mantenimiento</th>
                            <th class="py-3 px-6">Modalidad</th>
                            <th class="py-3 px-6 w-48 text-right">Precio Actual ($)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($tarifas as $t): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 px-6 font-medium text-gray-900">
                                    <?= htmlspecialchars($t['nombre_mantenimiento']) ?>
                                </td>
                                <td class="py-3 px-6">
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs font-semibold">
                                        <?= htmlspecialchars($t['nombre_modalidad']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <input type="number" 
                                           step="0.01" 
                                           min="0"
                                           name="precios[<?= $t['id_tarifa'] ?>]" 
                                           value="<?= $t['precio'] ?>" 
                                           class="w-full text-right border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 font-bold text-gray-800 p-2 bg-yellow-50 focus:bg-white transition border">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="submit" class="px-6 py-3 bg-green-600 text-white font-bold rounded-lg shadow hover:bg-green-700 transform hover:-translate-y-0.5 transition flex items-center">
                    <i class="fas fa-save mr-2"></i> Guardar Todos los Cambios
                </button>
            </div>
        </form>
    <?php elseif ($id_maquina): ?>
        <div class="bg-white p-8 rounded-xl shadow text-center text-gray-500">
            <i class="fas fa-search fa-3x mb-4 text-gray-300"></i>
            <p>No se encontraron tarifas para esta combinación de Máquina y Año.</p>
        </div>
    <?php endif; ?>
</div>