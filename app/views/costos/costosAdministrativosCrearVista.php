<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full mx-auto space-y-8">
    
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-chart-pie text-purple-600 mr-2"></i> Gestión Costos Administrativos
            </h1>
            <p class="text-gray-500 text-sm">Administra la nómina de oficina y otros gastos generales.</p>
            <p class="text-red-500 text-s">Por favor guardar primero la Nómina Administrativa y posteriormente los Otros Gastos Generales.</p>

        </div>
        
        <form action="<?= BASE_URL ?>costosAdministrativosCrear" method="GET" class="flex items-center gap-2 bg-purple-50 p-2 rounded-lg border border-purple-100">
            <label class="font-bold text-purple-800 text-sm">Mes de Reporte:</label>
            <input type="month" name="mes_reporte" 
                    value="<?= $mesSeleccionado ?>" 
                    onchange="this.form.submit()"
                    class="border border-purple-200 rounded px-2 py-1 text-sm focus:ring-purple-500 focus:border-purple-500 text-purple-900 bg-white">
        </form>
    </div>

    <?php if (!empty($mensajeExito)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($mensajeExito) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
            <ul class="list-disc list-inside">
                <?php foreach ($errores as $err): ?> <li><?= htmlspecialchars($err) ?></li> <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <h2 class="font-bold text-lg text-gray-800"><i class="fas fa-users text-blue-500 mr-2"></i> 1. Nómina Administrativa</h2>
            <p class="text-xs text-gray-500">Ingresa salarios y bonos para Jefes, Coordinadores y Auxiliares.</p>
        </div>
        
        <form action="<?= BASE_URL ?>costosAdministrativosCrear" method="POST" class="p-0">
            <input type="hidden" name="accion" value="guardar_nomina">
            <input type="hidden" name="mes_reporte" value="<?= $mesSeleccionado ?>">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Funcionario</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Salario</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Bono</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Horas Extra</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php if (!empty($listaPersonal)): ?>
                            <?php foreach ($listaPersonal as $user): ?>
                                <?php $id = $user['id']; ?>
                                <tr class="hover:bg-blue-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-100">
                                        <div class="flex flex-col">
                                            <span><?= htmlspecialchars($user['nombre']) ?></span>
                                            <span class="text-xs text-blue-600"><?= htmlspecialchars($user['cargo']) ?></span>
                                        </div>
                                    </td>
                                    <td class="p-2"><input type="number" step="0.01" name="costos[<?= $id ?>][salario]" placeholder="0" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="costos[<?= $id ?>][bono_meta]" placeholder="0" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="costos[<?= $id ?>][horas_extra]" placeholder="0" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500"></td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="p-4 text-center text-sm text-gray-500">No hay usuarios administrativos activos.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="bg-gray-50 px-6 py-3 text-right">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-blue-700 text-sm">
                    Guardar Nómina
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h2 class="font-bold text-lg text-gray-800"><i class="fas fa-receipt text-orange-500 mr-2"></i> 2. Otros Gastos Generales</h2>
                <p class="text-xs text-gray-500">Registra costos asociados a la administración.</p>
            </div>
            <span class="bg-orange-100 text-orange-800 text-xs font-bold px-3 py-1 rounded-full">
                Total Gastos: $ <?= number_format($totalGastos, 2) ?>
            </span>
        </div>

        <div class="p-6 border-b border-gray-100">
            <form action="<?= BASE_URL ?>costosAdministrativosCrear" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <input type="hidden" name="accion" value="guardar_gasto">
                <input type="hidden" name="mes_reporte" value="<?= $mesSeleccionado ?>">

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Categoría</label>
                    <select name="categoria" class="w-full border-gray-300 rounded-md text-sm">
                        <option value="Software">Software/Tecnología</option>
                        <option value="Administrativo">Administrativo</option>
                        <option value="Servicios">Servicios Públicos</option>
                        <option value="Infraestructura">Infraestructura</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
                <div class="md:col-span-6">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Concepto</label>
                    <input type="text" name="concepto" required placeholder="Ej: Pagó Software Motorizados" class="w-full border-gray-300 rounded-md text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Valor</label>
                    <input type="number" step="0.01" name="valor" required placeholder="0" class="w-full border-gray-300 rounded-md text-sm">
                </div>
                <div class="md:col-span-1">
                    <button type="submit" class="w-full bg-orange-500 text-white h-9 rounded-md hover:bg-orange-600 shadow">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase">Categoría</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-400 uppercase">Concepto</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-400 uppercase">Valor</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-400 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php if (!empty($listaGastos)): ?>
                        <?php foreach ($listaGastos as $gasto): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-500"><span class="bg-gray-100 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($gasto['categoria']) ?></span></td>
                                <td class="px-6 py-3 text-sm font-medium text-gray-800"><?= htmlspecialchars($gasto['concepto']) ?></td>
                                <td class="px-6 py-3 text-sm text-gray-800 text-right font-mono">$ <?= number_format($gasto['valor'], 2) ?></td>
                                <td class="px-6 py-3 text-center">
                                    <a href="<?= BASE_URL ?>costosAdministrativosCrear?eliminar_id=<?= $gasto['id_gasto'] ?>&mes_reporte=<?= $mesSeleccionado ?>" 
                                        onclick="return confirm('¿Borrar?');" class="text-red-400 hover:text-red-600">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">No hay gastos generales registrados este mes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>