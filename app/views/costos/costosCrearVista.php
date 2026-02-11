<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full mx-auto">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                <i class="fas fa-motorcycle text-indigo-600 mr-2"></i> Costos Motorizados
            </h1>
            <p class="text-gray-500 mt-1">
                Gestión de costos operativos para técnicos de campo (Tabla Técnico).
            </p>
        </div>

        <?php if (!empty($mensajeExito)): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($mensajeExito) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>costosCrear" method="POST" class="space-y-6">

            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 w-full sm:w-1/3">
                <label for="mes_reporte" class="block text-sm font-bold text-gray-700 mb-1">Mes a Reportar <span class="text-red-500">*</span></label>
                <input type="month" id="mes_reporte" name="mes_reporte" required
                    value="<?= isset($_POST['mes_reporte']) ? $_POST['mes_reporte'] : date('Y-m') ?>"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sticky left-0 bg-gray-100 z-10">Motorizado</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Salario</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Aux. Rodamiento</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Gasolina</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Bono Meta</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Horas Extra</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Aux. Com</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php if (!empty($listaPersonal)): ?>
                            <?php foreach ($listaPersonal as $tech): ?>
                                <?php $id = $tech['id']; ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sticky left-0 bg-white z-10 border-r border-gray-200">
                                        <div class="flex flex-col">
                                            <span><?= htmlspecialchars($tech['nombre']) ?></span>
                                            <span class="text-xs text-gray-400">ID: <?= $id ?></span>
                                        </div>
                                    </td>

                                    <td class="p-2"><input type="number" step="0.01" name="costos[<?= $id ?>][salario]" placeholder="0" class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="costos[<?= $id ?>][auxilio_rodamiento]" placeholder="0" class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="costos[<?= $id ?>][gasolina]" placeholder="0" class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="costos[<?= $id ?>][bono_meta]" placeholder="0" class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="costos[<?= $id ?>][horas_extra]" placeholder="0" class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="costos[<?= $id ?>][auxilio_comunicacion]" placeholder="0" class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-500">No se encontraron técnicos activos.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>dashboard" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all transform hover:-translate-y-1">
                    <i class="fas fa-save mr-2"></i> Guardar Costos
                </button>
            </div>
        </form>
    </div>
</div>