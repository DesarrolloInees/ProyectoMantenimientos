<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-6xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-layer-group text-blue-600 mr-2"></i> Tarifario Multi-Máquina</h1>
            <p class="text-gray-500 mt-1">Selecciona varias máquinas y aplícales los mismos precios simultáneamente.</p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <ul class="list-disc list-inside text-sm"><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>tarifaCrear" method="POST" class="space-y-6">

            <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">

                <div class="mb-4 w-full md:w-1/4">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Año Vigencia</label>
                    <input type="number" name="año_vigencia" required min="2020" max="2030"
                        value="<?= date('Y') ?>"
                        class="w-full px-3 py-2 border rounded shadow-sm focus:ring-2 focus:ring-blue-500 bg-white">
                </div>

                <div class="block mb-2">
                    <label class="text-sm font-bold text-gray-700">Selecciona las Máquinas a aplicar:</label>
                    <button type="button" onclick="toggleCheckboxes(this)" class="text-xs text-blue-600 hover:underline ml-2 cursor-pointer select-none">
                        (Seleccionar Disponibles)
                    </button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 bg-white p-3 rounded border h-48 overflow-y-auto">
                    <?php foreach ($listaMaquinas as $item): ?>
                        <label class="flex items-center space-x-2 p-2 hover:bg-blue-50 rounded cursor-pointer border border-transparent hover:border-blue-100 transition">
                            <input type="checkbox" name="ids_maquinas[]" value="<?= $item['id_tipo_maquina'] ?>" class="form-checkbox h-5 w-5 text-blue-600 maquina-checkbox">
                            <span class="text-sm text-gray-700 maquina-label-text"><?= htmlspecialchars($item['nombre_tipo_maquina']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Las máquinas en gris ya tienen precios configurados para ese año.</p>
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
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>tarifaVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-save mr-2"></i> Guardar Tarifas Masivas
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // URL base desde PHP
    const baseUrl = "<?= BASE_URL ?>";

    // Referencias al DOM
    const inputAnio = document.querySelector('input[name="año_vigencia"]');

    document.addEventListener('DOMContentLoaded', verificarDisponibilidad);
    inputAnio.addEventListener('change', verificarDisponibilidad);
    inputAnio.addEventListener('keyup', verificarDisponibilidad);

    function verificarDisponibilidad() {
        const anio = inputAnio.value;

        if (anio.length !== 4) return;

        // --- CAMBIO CLAVE AQUÍ ---
        // En lugar de usar la barra /, usamos el parámetro ?accion=
        // Esto le dice a tu index.php actual exactamente qué función ejecutar.
        const urlFetch = `${baseUrl}tarifaCrear?accion=verificarMaquinas&anio=${anio}`;

        fetch(urlFetch)
            .then(response => {
                // Si el servidor responde con HTML por error (ej: login), lanzamos error
                if (!response.ok) throw new Error("Error en la red");
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("El servidor no devolvió JSON. Respuesta:", text);
                        throw new Error("Formato de respuesta inválido");
                    }
                });
            })
            .then(idsOcupados => {
                const checkboxes = document.querySelectorAll('.maquina-checkbox');

                checkboxes.forEach(chk => {
                    const idMaquina = parseInt(chk.value);
                    const label = chk.closest('label');
                    const textoSpan = label.querySelector('.maquina-label-text');

                    // 1. Resetear estado
                    chk.disabled = false;
                    label.classList.remove('opacity-50', 'bg-gray-100', 'cursor-not-allowed');
                    label.classList.add('hover:bg-blue-50', 'cursor-pointer');
                    let textoOriginal = textoSpan.innerText.replace(' (Ya existe)', '');
                    textoSpan.innerText = textoOriginal;

                    // 2. Bloquear si está ocupado
                    // Convertimos a String ambos para asegurar que la comparación funcione
                    if (idsOcupados.map(String).includes(String(idMaquina))) {
                        chk.checked = false;
                        chk.disabled = true;

                        label.classList.add('opacity-50', 'bg-gray-100', 'cursor-not-allowed');
                        label.classList.remove('hover:bg-blue-50', 'cursor-pointer');

                        textoSpan.innerText = textoOriginal + ' (Ya existe)';
                    }
                });
            })
            .catch(error => console.error('Error verificando máquinas:', error));
    }

    function toggleCheckboxes(btn) {
        const checkboxes = document.querySelectorAll('.maquina-checkbox:not(:disabled)');
        if (checkboxes.length === 0) return;

        const shouldCheck = !checkboxes[0].checked;
        checkboxes.forEach(cb => cb.checked = shouldCheck);
        btn.innerText = shouldCheck ? "(Deseleccionar Disponibles)" : "(Seleccionar Disponibles)";
    }
</script>