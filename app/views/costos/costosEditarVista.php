<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full mx-auto">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                    <i class="fas fa-edit text-yellow-500 mr-2"></i> Gestión de Costos
                </h1>
                <p class="text-gray-500 mt-1">
                    Visualizando y editando reporte del mes: <span class="font-bold text-indigo-600 text-lg"><?= $mes ?></span>
                </p>
            </div>
            
            <a href="<?= BASE_URL ?>costosVer" class="mt-4 sm:mt-0 px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition border border-gray-300 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Listado
            </a>
        </div>

        <?php if (!empty($mensajeExito)): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center">
                <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($mensajeExito) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <?php foreach ($errores as $err): ?>
                    <p><i class="fas fa-times-circle mr-2"></i> <?= $err ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">

            <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sticky left-0 bg-gray-100 z-10 shadow-sm border-r">Motorizado</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Salario</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Rodamiento</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Gasolina</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Bono Meta</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Horas Extra</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32">Aux. Com</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-32 bg-gray-50">Total Fila</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php 
                            $granTotal = 0;
                            foreach ($datosExistentes as $dato): 
                                $idCosto = $dato['id_costo'];
                                // Calculamos el total de la fila para referencia visual
                                $totalFila = $dato['salario'] + $dato['auxilio_rodamiento'] + $dato['gasolina'] + $dato['bono_meta'] + $dato['horas_extra'] + $dato['auxilio_comunicacion'];
                                $granTotal += $totalFila;
                        ?>
                            <tr class="hover:bg-gray-50 transition group">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sticky left-0 bg-white group-hover:bg-gray-50 z-10 border-r border-gray-200 shadow-sm">
                                    <div class="flex flex-col">
                                        <span><?= htmlspecialchars($dato['nombre_tecnico']) ?></span>
                                    </div>
                                </td>

                                <td class="p-2">
                                    <input type="number" step="0.01" name="costos[<?= $idCosto ?>][salario]" 
                                           value="<?= $dato['salario'] ?>" 
                                           class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-right font-mono text-gray-600">
                                </td>
                                <td class="p-2">
                                    <input type="number" step="0.01" name="costos[<?= $idCosto ?>][auxilio_rodamiento]" 
                                           value="<?= $dato['auxilio_rodamiento'] ?>" 
                                           class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-right font-mono text-gray-600">
                                </td>
                                <td class="p-2">
                                    <input type="number" step="0.01" name="costos[<?= $idCosto ?>][gasolina]" 
                                           value="<?= $dato['gasolina'] ?>" 
                                           class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-right font-mono text-gray-600">
                                </td>
                                <td class="p-2">
                                    <input type="number" step="0.01" name="costos[<?= $idCosto ?>][bono_meta]" 
                                           value="<?= $dato['bono_meta'] ?>" 
                                           class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-right font-mono text-gray-600">
                                </td>
                                <td class="p-2">
                                    <input type="number" step="0.01" name="costos[<?= $idCosto ?>][horas_extra]" 
                                           value="<?= $dato['horas_extra'] ?>" 
                                           class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-right font-mono text-gray-600">
                                </td>
                                <td class="p-2">
                                    <input type="number" step="0.01" name="costos[<?= $idCosto ?>][auxilio_comunicacion]" 
                                           value="<?= $dato['auxilio_comunicacion'] ?>" 
                                           class="w-24 border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-right font-mono text-gray-600">
                                </td>
                                
                                <td class="p-2 text-right font-bold text-gray-900 bg-gray-50 border-l">
                                    $<?= number_format($totalFila, 0) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-bold border-t border-gray-300">
                            <td class="py-3 px-4 text-right uppercase text-xs sticky left-0 bg-gray-100 z-10 border-r">Total General:</td>
                            <td colspan="6"></td>
                            <td class="py-3 px-4 text-right text-indigo-700">$<?= number_format($granTotal, 0) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>costosVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all transform hover:-translate-y-1">
                    <i class="fas fa-save mr-2"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>