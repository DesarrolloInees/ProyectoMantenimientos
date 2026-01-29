<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">

<style>
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

    #tablaRemisiones tbody tr {
        background-color: white !important;
    }

    #tablaRemisiones tbody tr:hover {
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
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                    <i class="fas fa-boxes text-indigo-600 mr-2"></i> Inventario de Remisiones
                </h1>
                <p class="text-gray-500 mt-1">Gestiona el inventario de Remisiones.</p>
            </div>
            <a href="<?= BASE_URL ?>controlRemisionCrear" class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                <i class="fas fa-plus-circle"></i>
                <span>Nuevas Remisiones</span>
            </a>
        </div>

<div class="w-full max-w-7xl mx-auto">
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <table id="tablaRemisiones" class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3"># Remisión</th>
                    <th class="px-6 py-3">Técnico Responsable</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Fecha Asignación</th>
                    <th class="px-6 py-3">Fecha Uso</th> <th class="px-6 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($remisiones as $r): ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-bold text-gray-900"><?= $r['numero_remision'] ?></td>
                        <td class="px-6 py-4"><?= $r['nombre_tecnico'] ?></td>
                        <td class="px-6 py-4">
                            <?php if ($r['nombre_estado'] == 'DISPONIBLE'): ?>
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Disponible</span>
                            
                            <?php elseif ($r['nombre_estado'] == 'USADA'): ?>
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Usada</span>
                            
                            <?php elseif ($r['nombre_estado'] == 'ANULADA'): ?>
                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Anulada</span>
                            
                            <?php else: ?>
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                    <?= htmlspecialchars($r['nombre_estado']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="px-6 py-4">
                            <?= date('d/m/Y', strtotime($r['fecha_asignacion'])) ?>
                        </td>

                        <td class="px-6 py-4">
                            <?php if (!empty($r['fecha_uso'])): ?>
                                <div class="flex flex-col">
                                    <span class="text-gray-900 font-medium">
                                        <?= date('d/m/Y', strtotime($r['fecha_uso'])) ?>
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        <?= date('H:i', strtotime($r['fecha_uso'])) ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-300">---</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <?php if ($r['nombre_estado'] != 'USADA'): ?>
                                <a href="<?= BASE_URL ?>controlRemisionEditar&id=<?= $r['id_control'] ?>" class="text-indigo-600 hover:text-indigo-900 mx-2" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= BASE_URL ?>controlRemisionEliminar&id=<?= $r['id_control'] ?>"
                                    class="text-red-600 hover:text-red-900 mx-2"
                                    onclick="return confirm('¿Eliminar esta remisión?');" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-300 italic text-xs">Bloqueado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tablaRemisiones').DataTable({
            responsive: true,
            pageLength: 100, // <--- Esto establece el valor por defecto a 100

            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            order: [
                [0, "desc"]
            ]
        });
    });
</script>