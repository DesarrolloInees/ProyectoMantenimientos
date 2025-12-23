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

    #maquinasTable tbody tr {
        background-color: white !important;
    }

    #maquinasTable tbody tr:hover {
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
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-server text-indigo-600 mr-2"></i> Máquinas</h1>
                <p class="text-gray-500">Gestión de dispositivos instalados.</p>
            </div>
            <a href="<?= BASE_URL ?>maquinaCrear" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow hover:bg-indigo-700 transition flex items-center space-x-2">
                <i class="fas fa-plus-circle"></i> <span>Nueva Máquina</span>
            </a>
        </div>

        <?php if (!empty($data['maquinas'])): ?>
            <div class="overflow-x-auto">
                <table id="maquinasTable" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="py-3 px-4">Device ID</th>
                            <th class="py-3 px-4">Tipo</th>
                            <th class="py-3 px-4">Ubicación (Punto)</th>
                            <th class="py-3 px-4">Última Visita</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($data['maquinas'] as $m): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 font-bold text-gray-800 font-mono"><?= htmlspecialchars($m['device_id']) ?></td>
                                <td class="py-3 px-4">
                                    <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded-md text-xs font-bold"><?= htmlspecialchars($m['nombre_tipo_maquina']) ?></span>
                                </td>
                                <td class="py-3 px-4"><?= htmlspecialchars($m['nombre_punto']) ?></td>
                                <td class="py-3 px-4 text-gray-500">
                                    <?= !empty($m['ultima_visita']) ? date('d/m/Y', strtotime($m['ultima_visita'])) : 'Sin registro' ?>
                                </td>
                                <td class="py-3 px-4 text-center flex justify-center space-x-2">
                                    <a href="<?= BASE_URL ?>maquinaEditar/<?= $m['id_maquina'] ?>" class="p-2 bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200"><i class="fas fa-edit"></i></a>
                                    <button onclick="abrirModal(<?= $m['id_maquina'] ?>)" class="p-2 bg-red-100 text-red-600 rounded-full hover:bg-red-200"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center p-8 bg-gray-50 rounded-lg">
                <p class="text-gray-500">No hay máquinas registradas.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalEliminar" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Eliminar Máquina</h3>
        <p class="text-sm text-gray-500 mb-4">¿Seguro? Quedará inactiva.</p>
        <div class="flex justify-end space-x-3">
            <button onclick="cerrarModal()" class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200">Cancelar</button>
            <a id="btnConfirmar" href="#" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Sí, Eliminar</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script>
    $(document).ready(function() {
        $('#maquinasTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            }
        });
    });

    function abrirModal(id) {
        document.getElementById('btnConfirmar').href = "<?= BASE_URL ?>maquinaEliminar/" + id;
        document.getElementById('modalEliminar').classList.remove('hidden');
    }

    function cerrarModal() {
        document.getElementById('modalEliminar').classList.add('hidden');
    }
</script>