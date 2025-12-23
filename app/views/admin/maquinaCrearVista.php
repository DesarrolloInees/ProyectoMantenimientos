<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* Ajustes para que Select2 se parezca a los inputs de Tailwind */
    .select2-container .select2-selection--single {
        height: 46px !important;
        /* Altura similar a py-3 */
        border: 1px solid #d1d5db !important;
        /* gray-300 */
        border-radius: 0.5rem !important;
        /* rounded-lg */
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #374151;
        /* gray-700 */
        padding-left: 0.75rem;
        width: 100%;
    }

    /* Color del anillo de foco (simulando Indigo-500 de Tailwind) */
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 1px #6366f1;
    }
</style>

<div class="w-full max-w-4xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-microchip text-indigo-600 mr-2"></i> Nueva Máquina</h1>
            <p class="text-gray-500 mt-1">Registra un dispositivo y asígnalo a un punto.</p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <ul class="list-disc list-inside text-sm"><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>maquinaCrear" method="POST" class="space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Device ID (Serial) <span class="text-red-500">*</span></label>
                    <input type="text" name="device_id" required placeholder="Ej: DEV-2025-001"
                        value="<?= htmlspecialchars($datos['device_id'] ?? '') ?>"
                        class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 font-mono uppercase">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Última Visita</label>
                    <input type="date" name="ultima_visita"
                        value="<?= htmlspecialchars($datos['ultima_visita'] ?? '') ?>"
                        class="w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Punto de Ubicación <span class="text-red-500">*</span></label>
                    <select name="id_punto" required class="select2-search w-full border border-gray-300 rounded-lg bg-white">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($listaPuntos as $p): ?>
                            <option value="<?= $p['id_punto'] ?>" <?= ($datos['id_punto'] ?? '') == $p['id_punto'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre_punto']) ?> (Cliente: <?= htmlspecialchars($p['nombre_cliente']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Máquina <span class="text-red-500">*</span></label>
                    <select name="id_tipo_maquina" required class="select2-search w-full border border-gray-300 rounded-lg bg-white">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($listaTipos as $t): ?>
                            <option value="<?= $t['id_tipo_maquina'] ?>" <?= ($datos['id_tipo_maquina'] ?? '') == $t['id_tipo_maquina'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nombre_tipo_maquina']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-4">
                <a href="<?= BASE_URL ?>maquinaVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transform hover:-translate-y-1 transition-all">Guardar Máquina</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Inicializar Select2 en los elementos con la clase .select2-search
        $('.select2-search').select2({
            width: '100%', // Asegura que ocupe todo el ancho del contenedor padre
            placeholder: '-- Buscar y Seleccionar --',
            language: {
                noResults: function() {
                    return "No se encontraron resultados";
                }
            }
        });
    });
</script>