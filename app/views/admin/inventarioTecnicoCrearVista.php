<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Estilos Select2 */
    .select2-container .select2-selection--single {
        height: 42px !important;
        padding-top: 6px !important;
        border-color: #d1d5db !important;
        border-radius: 0.5rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 6px !important;
    }
</style>

<div class="w-full max-w-4xl mx-auto mt-6">
    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">

        <div class="mb-6 border-b pb-4 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-dolly-flatbed text-indigo-600 mr-2"></i> Asignación Masiva
                </h1>
                <p class="text-gray-500 mt-1 text-sm">Carga múltiples repuestos a un solo técnico.</p>
            </div>
            <a href="<?= BASE_URL ?>inicio" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </a>
        </div>

        <?php if (!empty($mensajeExito)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                <i class="fas fa-check-circle mr-2"></i> <?= $mensajeExito ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Hubo errores en la carga:</p>
                <ul class="list-disc list-inside ml-4 text-sm">
                    <?php foreach ($errores as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>inventarioTecnicoCrear" method="POST" id="formInventario">

            <div class="bg-indigo-50 p-4 rounded-lg mb-6 border border-indigo-100">
                <label class="block text-sm font-bold text-indigo-900 mb-1">Técnico Responsable (Destino)</label>
                <select name="id_tecnico" class="select2-tecnico w-full" required>
                    <option value="">- Seleccione Técnico -</option>
                    <?php foreach ($listaTecnicos as $t): ?>
                        <option value="<?= $t['id_tecnico'] ?>" <?= (isset($tecnicoSeleccionado) && $tecnicoSeleccionado == $t['id_tecnico']) ? 'selected' : '' ?>>
                            <?= $t['nombre_tecnico'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Detalle de Repuestos</label>

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase border-b">
                            <th class="py-2 w-2/3">Repuesto / Insumo</th>
                            <th class="py-2 w-24 text-center">Cant.</th>
                            <th class="py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="contenedor-filas">
                    </tbody>
                </table>
            </div>

            <div class="flex justify-center mb-8">
                <button type="button" onclick="agregarFila()" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-full border border-gray-300 transition flex items-center">
                    <i class="fas fa-plus-circle text-indigo-500 mr-2"></i> Agregar otro ítem
                </button>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end space-x-3">
                <a href="<?= BASE_URL ?>inventarioTecnicoVer" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-save mr-2"></i> Guardar Todo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const listaRepuestosGlobal = <?= json_encode($listaRepuestos) ?>;
</script>

<script>
    $(document).ready(function() {
        // Inicializar Select del técnico
        $('.select2-tecnico').select2({
            width: '100%'
        });

        // Agregar la primera fila automáticamente al cargar
        agregarFila();
    });

    /**
     * Función para agregar una nueva fila a la tabla
     */
    function agregarFila() {
        // Generamos las opciones del select basándonos en el JSON de PHP
        let opciones = '<option value="">- Buscar -</option>';
        listaRepuestosGlobal.forEach(r => {
            let codigo = r.codigo_referencia ? `(${r.codigo_referencia})` : '';
            opciones += `<option value="${r.id_repuesto}">${r.nombre_repuesto} ${codigo}</option>`;
        });

        // Crear ID único para el select nuevo (necesario para Select2)
        let idUnico = Date.now();

        let html = `
            <tr class="border-b border-gray-100 fila-repuesto">
                <td class="py-2 pr-2">
                    <select name="repuestos[]" id="sel_${idUnico}" class="w-full select2-dinamico" required>
                        ${opciones}
                    </select>
                </td>
                <td class="py-2 px-2">
                    <input type="number" name="cantidades[]" min="1" value="1" required
                        class="w-full h-[42px] border border-gray-300 rounded text-center font-bold text-indigo-700">
                </td>
                <td class="py-2 text-center">
                    <button type="button" onclick="eliminarFila(this)" class="text-red-400 hover:text-red-600 p-2">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#contenedor-filas').append(html);

        // Inicializar Select2 SOLO en el nuevo elemento
        $(`#sel_${idUnico}`).select2({
            width: '100%',
            language: {
                noResults: () => "Sin resultados"
            }
        });
    }

    function eliminarFila(btn) {
        // Evitar eliminar si es la única fila
        if ($('#contenedor-filas tr').length > 1) {
            $(btn).closest('tr').remove();
        } else {
            alert("Debe haber al menos un repuesto.");
        }
    }
</script>