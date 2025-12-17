<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">

<style>
    /* Reutilizamos los estilos pro que ya definimos antes */
    .dataTables_length select, .dataTables_filter input {
        background-color: white !important; color: #374151 !important;
        border: 1px solid #d1d5db !important; border-radius: 0.5rem;
        padding: 0.5rem 0.75rem; margin: 0 0.5rem;
    }
    #tablaRemisiones tbody tr { background-color: white !important; }
    #tablaRemisiones tbody tr:hover { background-color: #f9fafb !important; }
    .dataTables_paginate .paginate_button.current {
        background-color: #4f46e5 !important; color: white !important; border-color: #4f46e5 !important;
    }
    .dataTables_wrapper>div:first-child, .dataTables_wrapper>div:last-of-type {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin: 1.5rem 0;
    }
</style>

<div class="w-full max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-list-alt text-indigo-600 mr-2"></i> Control de Remisiones</h1>
        <a href="<?= BASE_URL ?>controlRemisionCrear" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow transition transform hover:scale-105">
            <i class="fas fa-plus mr-2"></i> Nueva Asignación
        </a>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <table id="tablaRemisiones" class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3"># Remisión</th>
                    <th class="px-6 py-3">Técnico Responsable</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Fecha Asignación</th>
                    <th class="px-6 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($remisiones as $r): ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-bold text-gray-900"><?= $r['numero_remision'] ?></td>
                        <td class="px-6 py-4"><?= $r['nombre_tecnico'] ?></td>
                        <td class="px-6 py-4">
                            <?php if ($r['estado'] == 'DISPONIBLE'): ?>
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Disponible</span>
                            <?php elseif ($r['estado'] == 'USADA'): ?>
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Usada</span>
                            <?php else: ?>
                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Anulada</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4"><?= date('d/m/Y', strtotime($r['fecha_asignacion'])) ?></td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($r['estado'] != 'USADA'): ?>
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
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            order: [[ 0, "desc" ]]
        });
    });
</script>
