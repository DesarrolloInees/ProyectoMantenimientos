<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<div class="w-full mx-auto px-4">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-tags text-indigo-600 mr-2"></i> Tipos de Novedad
            </h1>
            <p class="text-gray-500 text-sm mt-1">Listado de clasificaciones activas.</p>
        </div>
        <a href="<?= BASE_URL ?>tipoNovedadCrear" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all flex items-center">
            <i class="fas fa-plus mr-2"></i> Nuevo Tipo
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        
        <div class="p-6">
            <table id="tablaNovedades" class="w-full text-sm text-left text-gray-500 stripe hover row-border">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 rounded-tl-lg">ID</th>
                        <th class="px-4 py-3">Nombre Novedad</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right rounded-tr-lg">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($novedades as $item): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                #<?= $item['id_tipo_novedad'] ?>
                            </td>
                            <td class="px-4 py-3 font-semibold uppercase">
                                <?= htmlspecialchars($item['nombre_novedad']) ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded border border-green-200">
                                    Activo
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="<?= BASE_URL ?>tipoNovedadEditar/<?= $item['id_tipo_novedad'] ?>" 
                                   class="text-blue-600 hover:text-blue-900 p-1" title="Editar">
                                    <i class="fas fa-edit fa-lg"></i>
                                </a>
                                <button onclick="confirmarEliminar(<?= $item['id_tipo_novedad'] ?>)" 
                                        class="text-red-500 hover:text-red-700 p-1" title="Eliminar">
                                    <i class="fas fa-trash-alt fa-lg"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Inicializar DataTable (Sin filtro externo ya)
        $('#tablaNovedades').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            },
            dom: '<"flex justify-between items-center mb-4"f>rt<"flex justify-between items-center mt-4"lip>',
            drawCallback: function() {
                $('.dataTables_paginate > .paginate_button').addClass('px-3 py-1 mx-1 rounded hover:bg-gray-200 cursor-pointer');
            }
        });
    });

    function confirmarEliminar(id) {
        if(confirm('¿Estás seguro de eliminar este registro? Desaparecerá de la lista.')) {
            window.location.href = '<?= BASE_URL ?>tipoNovedadEliminar/' + id;
        }
    }
</script>