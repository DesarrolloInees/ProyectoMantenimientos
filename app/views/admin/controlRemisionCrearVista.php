<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full max-w-3xl mx-auto">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                <i class="fas fa-ticket-alt text-indigo-600 mr-2"></i> Asignar Remisiones
            </h1>
            <p class="text-gray-500 mt-1">Registra remisiones permitiendo duplicados entre técnicos diferentes.</p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <ul class="list-disc list-inside ml-2 text-sm">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>controlRemisionCrear" method="POST" class="space-y-6">

            <div>
                <label for="id_tecnico" class="block text-sm font-bold text-gray-700 mb-1">Técnico Responsable <span class="text-red-500">*</span></label>
                <div class="relative">
                    <select id="id_tecnico" name="id_tecnico" required class="pl-3 mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="">-- Seleccione un Técnico --</option>
                        <?php foreach ($listaTecnicos as $t): ?>
                            <option value="<?= $t['id_tecnico'] ?>"><?= htmlspecialchars($t['nombre_tecnico']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p id="msgUltima" class="text-xs text-indigo-600 mt-1 font-semibold h-4"></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="remision_inicio" class="block text-sm font-bold text-gray-700 mb-1">Número Inicial <span class="text-red-500">*</span></label>
                    <input type="number" id="remision_inicio" name="remision_inicio" required
                        placeholder="Ej: 100" class="mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="remision_fin" class="block text-sm font-bold text-gray-700 mb-1">Número Final (Opcional)</label>
                    <input type="number" id="remision_fin" name="remision_fin"
                        placeholder="Ej: 150" class="mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Déjalo vacío si solo vas a registrar una.</p>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>controlRemisionVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-save mr-2"></i> Guardar Asignación
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script>
    $(document).ready(function() {
        // Detectar cambio de técnico
        $('#id_tecnico').change(function() {
            let idTecnico = $(this).val();
            let msg = $('#msgUltima');
            let inputInicio = $('#remision_inicio');

            if (idTecnico) {
                // Hacemos petición AJAX al mismo controlador
                $.ajax({
                    url: '<?= BASE_URL ?>controlRemisionCrear&ajax=getUltima',
                    type: 'GET',
                    data: {
                        id_tecnico: idTecnico
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.ultima != 0) {
                            msg.text('Última remisión registrada: ' + data.ultima);
                            // Sugerimos la siguiente en el input
                            if (data.siguiente != '') {
                                inputInicio.val(data.siguiente);
                            }
                        } else {
                            msg.text('Este técnico no tiene remisiones asignadas aún.');
                            inputInicio.val('');
                        }
                    },
                    error: function() {
                        msg.text('Error al consultar última remisión.');
                    }
                });
            } else {
                msg.text('');
                inputInicio.val('');
            }
        });
    });
</script>