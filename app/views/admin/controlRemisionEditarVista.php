<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .select2-container--default .select2-selection--single {
        height: 46px !important;
        padding: 8px !important;
        border-color: #d1d5db !important;
        border-radius: 0.5rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px !important;
    }
</style>

<div class="w-full max-w-2xl mx-auto">
    <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit text-indigo-600 mr-2"></i> Editar Remisión
            </h1>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <ul class="list-disc list-inside text-sm">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>controlRemisionEditar&id=<?= $datos['id_control'] ?>" method="POST" class="space-y-6">

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Número de Remisión</label>
                <input type="text" name="numero_remision" value="<?= htmlspecialchars($datos['numero_remision']) ?>" required
                    class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Técnico Responsable</label>
                <select name="id_tecnico" id="select-tecnico" required class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500">
                    <?php foreach ($listaTecnicos as $t): ?>
                        <option value="<?= $t['id_tecnico'] ?>" <?= ($datos['id_tecnico'] == $t['id_tecnico']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre_tecnico']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                <select name="id_estado" class="block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500">
                    
                    <?php 
                    // Verificamos si es USADA usando el nombre que trajimos con el JOIN
                    if (isset($datos['nombre_estado']) && $datos['nombre_estado'] == 'USADA'): 
                    ?>
                        <option value="<?= $datos['id_estado'] ?>" selected>USADA (Asignada a Orden)</option>
                    
                    <?php else: ?>
                    
                        <?php foreach ($listaEstados as $estado): ?>
                            <?php 
                                // Omitimos 'USADA' de la lista seleccionable manual si quieres restringirlo
                                if ($estado['nombre_estado'] == 'USADA') continue; 
                                
                                $selected = ($datos['id_estado'] == $estado['id_estado']) ? 'selected' : '';
                            ?>
                            <option value="<?= $estado['id_estado'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($estado['nombre_estado']) ?>
                            </option>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </select>
                <p class="text-xs text-gray-500 mt-1">Seleccione el estado actual de la remisión física.</p>
            </div>

            <div class="pt-6 border-t flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>controlRemisionVer" class="px-6 py-3 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow hover:bg-indigo-700 transition">
                    <i class="fas fa-save mr-2"></i> Actualizar
                </button>
            </div>

        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#select-tecnico').select2({
            placeholder: "Seleccione un técnico",
            allowClear: true,
            width: '100%'
        });
    });
</script>