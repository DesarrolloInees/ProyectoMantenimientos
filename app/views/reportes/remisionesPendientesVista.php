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

    #tablaSandwich tbody tr {
        background-color: white !important;
    }

    #tablaSandwich tbody tr:hover {
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

<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">

<div class="w-full max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-bullseye text-red-600 mr-2"></i> Remisiones Salteadas
            </h1>
            <p class="text-gray-500 mt-1">
                Detectando remisiones <strong>DISPONIBLES</strong> atrapadas entre dos <strong>USADAS</strong>.
            </p>
        </div>
        <a href="<?= BASE_URL ?>controlRemisionVer" class="text-gray-600 hover:text-gray-900 font-medium">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if(empty($pendientes)): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-10 text-center shadow-sm">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-2xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Todo Correcto</h3>
            <p class="text-gray-600">No hay saltos individuales detectados actualmente.</p>
        </div>
    <?php else: ?>

    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <table id="tablaSandwich" class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Técnico</th>
                    <th class="px-6 py-3 text-center">Secuencia (Ant - Actual - Sig)</th>
                    <th class="px-6 py-3 text-center">Estado</th>
                    <th class="px-6 py-3 text-right">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendientes as $p): ?>
                    <tr class="bg-white border-b hover:bg-red-50 transition">
                        <td class="px-6 py-4 font-bold text-gray-800">
                            <?= $p['nombre_tecnico'] ?>
                            <div class="text-xs text-gray-400 font-normal mt-1">
                                Asig: <?= date('d/m/Y', strtotime($p['fecha_asignacion'])) ?>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <div class="text-center opacity-60">
                                    <span class="bg-gray-200 text-gray-600 px-2 py-1 rounded text-xs font-bold block mb-1">
                                        #<?= $p['anterior'] ?>
                                    </span>
                                    <span class="text-[10px] uppercase text-green-600 font-bold">Usada</span>
                                </div>

                                <i class="fas fa-arrow-right text-gray-300 text-xs"></i>

                                <div class="text-center transform scale-110 mx-1">
                                    <span class="bg-red-100 text-red-600 border border-red-200 px-3 py-1.5 rounded text-sm font-black block mb-1">
                                        #<?= $p['numero_remision'] ?>
                                    </span>
                                    <span class="text-[10px] uppercase text-red-500 font-bold">Disponible</span>
                                </div>

                                <i class="fas fa-arrow-right text-gray-300 text-xs"></i>

                                <div class="text-center opacity-60">
                                    <span class="bg-gray-200 text-gray-600 px-2 py-1 rounded text-xs font-bold block mb-1">
                                        #<?= $p['siguiente'] ?>
                                    </span>
                                    <span class="text-[10px] uppercase text-green-600 font-bold">Usada</span>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                             <span class="bg-orange-100 text-orange-800 text-xs font-bold px-2.5 py-0.5 rounded border border-orange-200">
                                Salteada
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="<?= BASE_URL ?>remisionesPendientes?accion=cambiarEstado&id=<?= $p['id_control'] ?>&estado=ANULADA" 
                                   onclick="return confirm('¿Confirmas ANULAR la remisión #<?= $p['numero_remision'] ?>?');"
                                   class="text-white bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded shadow text-xs font-bold transition flex items-center gap-1">
                                    <i class="fas fa-ban"></i> Anular
                                </a>
                                
                                <a href="<?= BASE_URL ?>remisionesPendientes?accion=cambiarEstado&id=<?= $p['id_control'] ?>&estado=USADA" 
                                   onclick="return confirm('¿Marcar #<?= $p['numero_remision'] ?> como USADA?');"
                                   class="text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 p-2 rounded transition border border-green-200" title="Marcar como Usada">
                                    <i class="fas fa-check"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tablaSandwich').DataTable({
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            order: [[0, 'asc']]
        });
    });
</script>