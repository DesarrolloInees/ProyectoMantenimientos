<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
    /* Reutilizamos los estilos pro que ya definimos antes */
    .dataTables_length select, .dataTables_filter input {
        background-color: white !important; color: #374151 !important;
        border: 1px solid #d1d5db !important; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; margin: 0 0.5rem;
    }
    #clientesTable tbody tr { background-color: white !important; }
    #clientesTable tbody tr:hover { background-color: #f9fafb !important; }
    .dataTables_paginate .paginate_button.current {
        background-color: #4f46e5 !important; color: white !important; border-color: #4f46e5 !important;
    }
    .dataTables_wrapper>div:first-child, .dataTables_wrapper>div:last-of-type {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin: 1.5rem 0;
    }
</style>

<div class="w-full max-w-7xl mx-auto">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                    <i class="fas fa-users text-indigo-600 mr-2"></i> Clientes
                </h1>
                <p class="text-gray-500 mt-1">Administra las empresas y entidades registradas.</p>
            </div>
            <a href="<?= BASE_URL ?>clienteCrear" class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                <i class="fas fa-plus-circle"></i> <span>Nuevo Cliente</span>
            </a>
        </div>

        <?php if (!empty($data['clientes'])): ?>
            <div class="overflow-x-auto">
                <table id="clientesTable" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="py-3 px-4">ID</th>
                            <th class="py-3 px-4">Código</th>
                            <th class="py-3 px-4">Nombre Cliente</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($data['clientes'] as $c): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4 font-bold text-gray-600">#<?= $c['id_cliente'] ?></td>
                                <td class="py-4 px-4 font-mono text-indigo-600 font-bold"><?= htmlspecialchars($c['codigo_cliente']) ?></td>
                                <td class="py-4 px-4 font-medium text-gray-900 text-base"><?= htmlspecialchars($c['nombre_cliente']) ?></td>
                                <td class="py-4 px-4 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full border border-green-200">Activo</span>
                                </td>
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <div class="flex justify-center items-center space-x-2">
                                        <a href="<?= BASE_URL ?>clienteEditar/<?= $c['id_cliente'] ?>" class="p-2 w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200 transition-colors border border-yellow-200">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>
                                        <button onclick="abrirModal(<?= $c['id_cliente'] ?>)" class="p-2 w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-full hover:bg-red-200 transition-colors border border-red-200">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center p-10 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                <p class="text-gray-500">No hay clientes registrados aún.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalEliminar" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="cerrarModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Eliminar Cliente</h3>
                        <div class="mt-2"><p class="text-sm text-gray-500">¿Estás seguro? Se ocultará el cliente y sus puntos asociados podrían quedar inaccesibles.</p></div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a id="btnConfirmar" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Sí, Eliminar</a>
                <button type="button" onclick="cerrarModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
    $(document).ready(function() {
        $('#clientesTable').DataTable({ responsive: true, language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' } });
    });
    function abrirModal(id) {
        document.getElementById('btnConfirmar').href = "<?= BASE_URL ?>clienteEliminar/" + id;
        document.getElementById('modalEliminar').classList.remove('hidden');
    }
    function cerrarModal() {
        document.getElementById('modalEliminar').classList.add('hidden');
    }
</script>