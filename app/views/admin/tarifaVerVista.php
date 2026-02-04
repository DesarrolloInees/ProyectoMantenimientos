<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<style>
    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        border: 1px solid #d1d5db !important;
        padding: 0.5rem;
        border-radius: 0.5rem;
    }

    .dataTables_wrapper {
        padding: 1rem 0;
    }

    /* Reutilizamos los estilos pro que ya definimos antes */
    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin: 0 0.5rem;
    }

    #tarifasTable tbody tr {
        background-color: white !important;
    }

    #tarifasTable tbody tr:hover {
        background-color: #f9fafb !important;
    }

    .dataTables_paginate .paginate_button.current {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }

    .dataTables_wrapper>div:first-child,
    .dataTables_wrapper>div:last-of-type {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        margin: 1.5rem 0;
    }
</style>

<div class="w-full max-w-7xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">

        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-tags text-green-600 mr-2"></i> Tarifas</h1>
                <p class="text-gray-500">Precios configurados por servicio y máquina.</p>
            </div>

            <div class="flex gap-2">
                <a href="<?= BASE_URL ?>tarifaCrear" class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow hover:bg-green-700 transition flex items-center space-x-2">
                    <i class="fas fa-plus-circle"></i> <span>Nueva Tarifa</span>
                </a>

                <a href="<?= BASE_URL ?>tarifaMasiva" class="px-5 py-2.5 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition flex items-center space-x-2">
                    <i class="fas fa-th-list"></i> <span>Edición Masiva</span>
                </a>
            </div>
        </div>

        <?php if (!empty($data['tarifas'])): ?>
            <div class="overflow-x-auto">
                <table id="tarifasTable" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="py-3 px-4">Máquina</th>
                            <th class="py-3 px-4">Mantenimiento</th>
                            <th class="py-3 px-4">Modalidad</th>
                            <th class="py-3 px-4">Año</th>
                            <th class="py-3 px-4 text-right">Precio</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($data['tarifas'] as $t): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 font-bold text-gray-700"><?= htmlspecialchars($t['nombre_tipo_maquina']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($t['nombre_mantenimiento']) ?></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($t['nombre_modalidad']) ?></td>
                                <td class="py-3 px-4 font-mono"><?= htmlspecialchars($t['año_vigencia']) ?></td>
                                <td class="py-3 px-4 text-right font-bold text-green-600">
                                    $<?= number_format($t['precio'], 2) ?>
                                </td>
                                <td class="py-3 px-4 text-center flex justify-center space-x-2">
                                    <a href="<?= BASE_URL ?>tarifaEditar/<?= $t['id_tarifa'] ?>" class="p-2 bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200"><i class="fas fa-edit"></i></a>
                                    <button onclick="abrirModal(<?= $t['id_tarifa'] ?>)" class="p-2 bg-red-100 text-red-600 rounded-full hover:bg-red-200"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center p-8 bg-gray-50 rounded-lg">
                <p class="text-gray-500">No hay tarifas registradas.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalEliminar" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Eliminar Tarifa</h3>
        <p class="text-sm text-gray-500 mb-4">¿Seguro? Esta acción es irreversible.</p>
        <div class="flex justify-end space-x-3">
            <button onclick="cerrarModal()" class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200">Cancelar</button>
            <a id="btnConfirmar" href="#" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Sí, Borrar</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tarifasTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            // AGREGA ESTO: Para que muestre 50 filas por defecto
            pageLength: 50,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Todos"]
            ],
            // AGREGA ESTO: Para ordenar por la columna de Máquina (índice 0) y Mantenimiento (1)
            order: [
                [0, 'asc'],
                [1, 'asc']
            ]

        });
    });

    function abrirModal(id) {
        document.getElementById('btnConfirmar').href = "<?= BASE_URL ?>tarifaEliminar/" + id;
        document.getElementById('modalEliminar').classList.remove('hidden');
    }

    function cerrarModal() {
        document.getElementById('modalEliminar').classList.add('hidden');
    }
</script>