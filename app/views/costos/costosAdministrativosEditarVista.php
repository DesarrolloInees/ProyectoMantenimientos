<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full mx-auto space-y-8">

    <div class="bg-white p-6 rounded-xl shadow-md border border-yellow-100 flex flex-col md:flex-row justify-between items-center gap-4">
        
        <div class="flex items-center gap-4 w-full md:w-auto">
            <a href="<?= BASE_URL ?>costosAdministrativosVer" 
               class="bg-gray-100 hover:bg-gray-200 text-gray-600 w-10 h-10 flex items-center justify-center rounded-full transition shadow-sm"
               title="Volver al reporte">
                <i class="fas fa-arrow-left"></i>
            </a>

            <div>
                <h1 class="text-2xl font-bold text-gray-800 leading-tight">
                    <i class="fas fa-edit text-yellow-600 mr-2"></i> Editar Costos
                </h1>
                <p class="text-gray-500 text-sm">Modifica valores masivamente.</p>
            </div>
        </div>
        
        <form action="<?= BASE_URL ?>costosAdministrativosEditar" method="GET" class="flex items-center gap-2 bg-yellow-50 p-2 rounded-lg border border-yellow-200 w-full md:w-auto justify-center">
            <label class="font-bold text-yellow-800 text-sm whitespace-nowrap">Editando Mes:</label>
            <input type="month" name="mes_reporte" 
                   value="<?= $mesSeleccionado ?>" 
                   onchange="this.form.submit()"
                   class="border border-yellow-200 rounded px-2 py-1 text-sm focus:ring-yellow-500 focus:border-yellow-500 text-yellow-900 bg-white">
        </form>
    </div>

    <?php if (!empty($mensajeExito)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($mensajeExito) ?>
            </div>
            <a href="<?= BASE_URL ?>costosAdministrativosCrear?mes_reporte=<?= $mesSeleccionado ?>" class="text-sm font-bold underline hover:text-green-900">Volver a ver todo &rarr;</a>
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
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="font-bold text-lg text-gray-800"><i class="fas fa-users-cog text-blue-500 mr-2"></i> Nómina (Edición Masiva)</h2>
            <span class="text-xs text-gray-400 hidden sm:block">Cambia los valores y guarda todo al final</span>
        </div>
        
        <form action="<?= BASE_URL ?>costosAdministrativosEditar" method="POST">
            <input type="hidden" name="accion" value="actualizar_nomina">
            <input type="hidden" name="mes_reporte" value="<?= $mesSeleccionado ?>">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Funcionario</th>
                            <th class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Salario</th>
                            <th class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Bono Meta</th>
                            <th class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase w-24">H. Extra</th>
                            <th class="px-2 py-3 text-left text-xs font-bold text-gray-500 uppercase w-24">Aux. Com.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php if (!empty($listaNomina)): ?>
                            <?php foreach ($listaNomina as $u): ?>
                                <tr class="hover:bg-blue-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($u['nombre']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($u['cargo']) ?></div>
                                    </td>
                                    <td class="p-2"><input type="number" step="0.01" name="nomina[<?= $u['id'] ?>][salario]" value="<?= $u['salario'] ?>" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 bg-gray-50 focus:bg-white transition"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="nomina[<?= $u['id'] ?>][bono_meta]" value="<?= $u['bono_meta'] ?>" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 bg-gray-50 focus:bg-white"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="nomina[<?= $u['id'] ?>][horas_extra]" value="<?= $u['horas_extra'] ?>" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 bg-gray-50 focus:bg-white"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="nomina[<?= $u['id'] ?>][auxilio_comunicacion]" value="<?= $u['auxilio_comunicacion'] ?>" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 bg-gray-50 focus:bg-white"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="p-4 text-center text-gray-500">No se encontró personal para este mes.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="bg-gray-50 px-6 py-3 flex justify-end items-center gap-3 border-t border-gray-200">
                <a href="<?= BASE_URL ?>costosAdministrativosCrear?mes_reporte=<?= $mesSeleccionado ?>" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded shadow hover:bg-blue-700 text-sm font-bold transition flex items-center">
                    <i class="fas fa-save mr-2"></i> Actualizar Nómina
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <h2 class="font-bold text-lg text-gray-800"><i class="fas fa-receipt text-orange-500 mr-2"></i> Gastos Generales (Edición Masiva)</h2>
            <p class="text-xs text-gray-500">Corrige conceptos o valores ya registrados.</p>
        </div>

        <?php if (!empty($listaGastos)): ?>
            <form action="<?= BASE_URL ?>costosAdministrativosEditar" method="POST">
                <input type="hidden" name="accion" value="actualizar_gastos_generales">
                <input type="hidden" name="mes_reporte" value="<?= $mesSeleccionado ?>">

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-orange-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-40">Categoría</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Concepto</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Valor</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-16">Borrar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php foreach ($listaGastos as $g): ?>
                                <tr class="hover:bg-orange-50 transition">
                                    <td class="p-2">
                                        <select name="gastos_generales[<?= $g['id_gasto'] ?>][categoria]" class="w-full border-gray-300 rounded text-sm focus:ring-orange-500 bg-gray-50">
                                            <option value="Software" <?= $g['categoria'] == 'Software' ? 'selected' : '' ?>>Software</option>
                                            <option value="Administrativo" <?= $g['categoria'] == 'Administrativo' ? 'selected' : '' ?>>Admin</option>
                                            <option value="Servicios" <?= $g['categoria'] == 'Servicios' ? 'selected' : '' ?>>Servicios</option>
                                            <option value="Infraestructura" <?= $g['categoria'] == 'Infraestructura' ? 'selected' : '' ?>>Infra.</option>
                                            <option value="Otros" <?= $g['categoria'] == 'Otros' ? 'selected' : '' ?>>Otros</option>
                                        </select>
                                    </td>
                                    <td class="p-2">
                                        <input type="text" name="gastos_generales[<?= $g['id_gasto'] ?>][concepto]" value="<?= htmlspecialchars($g['concepto']) ?>" class="w-full border-gray-300 rounded text-sm focus:ring-orange-500 bg-gray-50">
                                    </td>
                                    <td class="p-2">
                                        <input type="number" step="0.01" name="gastos_generales[<?= $g['id_gasto'] ?>][valor]" value="<?= $g['valor'] ?>" class="w-full border-gray-300 rounded text-sm focus:ring-orange-500 font-mono text-right bg-gray-50">
                                    </td>
                                    <td class="p-2 text-center align-middle">
                                        <a href="<?= BASE_URL ?>costosAdministrativosEditar?eliminar_gasto_id=<?= $g['id_gasto'] ?>&mes_reporte=<?= $mesSeleccionado ?>" 
                                           onclick="return confirm('¿Eliminar este gasto permanentemente?');" class="text-red-400 hover:text-red-600 transition">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="bg-gray-50 px-6 py-3 flex justify-end items-center gap-3 border-t border-gray-200">
                    <a href="<?= BASE_URL ?>costosAdministrativosCrear?mes_reporte=<?= $mesSeleccionado ?>" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Cancelar</a>
                    <button type="submit" class="bg-orange-600 text-white px-5 py-2 rounded shadow hover:bg-orange-700 text-sm font-bold transition flex items-center">
                        <i class="fas fa-save mr-2"></i> Actualizar Gastos
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="p-8 text-center text-gray-400 bg-white">
                <i class="fas fa-folder-open text-4xl mb-2 opacity-50"></i>
                <p>No hay gastos registrados para editar en este mes.</p>
                <a href="<?= BASE_URL ?>costosAdministrativosCrear" class="text-blue-500 hover:underline mt-2 inline-block">Volver a crear</a>
            </div>
        <?php endif; ?>
    </div>

</div>