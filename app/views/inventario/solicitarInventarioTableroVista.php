<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
    #tablaSolicitudes {
        width: 100% !important;
        margin: 0 !important;
    }

    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.4rem 0.75rem;
        margin: 0 0.5rem;
    }

    .dataTables_length label,
    .dataTables_filter label {
        color: #4b5563 !important;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }

    #tablaSolicitudes tbody tr {
        background-color: white !important;
    }

    #tablaSolicitudes tbody tr:hover {
        background-color: #f9fafb !important;
    }

    .dataTables_paginate .paginate_button.current,
    .dataTables_paginate .paginate_button:hover {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }
</style>

<div class="w-full px-4 md:px-6 py-4">
    <div class="bg-white p-6 md:p-8 rounded-xl shadow-lg border border-gray-200">

        <!-- Encabezado -->
        <div class="mb-6 border-b pb-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-tasks text-indigo-600 mr-3"></i> Tablero de Solicitudes de Inventario
                </h1>
                <p class="text-gray-500 mt-1 text-sm">Gestiona y actualiza el estado de los repuestos solicitados por el equipo técnico.</p>
            </div>
        </div>

        <?php if (!empty($mensajeExito)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center justify-between">
                <div><i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($mensajeExito) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Error:</p>
                <ul class="list-disc list-inside ml-4 text-sm mt-1">
                    <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Tarjetas de Estadísticas KPI -->
        <?php
        $totalSolicitudes = count($solicitudes ?? []);
        $cantPendientes = 0;
        $cantGestion = 0;
        $cantFinalizadas = 0;

        foreach ($solicitudes as $s) {
            $est = $s['estado'] ?? 'Pendiente';
            if ($est === 'Pendiente') $cantPendientes++;
            elseif ($est === 'En gestión') $cantGestion++;
            elseif ($est === 'Finalizado') $cantFinalizadas++;
        }
        ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-indigo-600 font-semibold uppercase tracking-wider">Total Solicitudes</p>
                    <h3 class="text-2xl font-black text-indigo-900 mt-1"><?= $totalSolicitudes ?></h3>
                </div>
                <div class="bg-indigo-200 text-indigo-700 p-3 rounded-lg"><i class="fas fa-boxes-stacked text-xl"></i></div>
            </div>

            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-amber-600 font-semibold uppercase tracking-wider">Pendientes</p>
                    <h3 class="text-2xl font-black text-amber-900 mt-1"><?= $cantPendientes ?></h3>
                </div>
                <div class="bg-amber-200 text-amber-700 p-3 rounded-lg"><i class="fas fa-clock text-xl"></i></div>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-blue-600 font-semibold uppercase tracking-wider">En Gestión</p>
                    <h3 class="text-2xl font-black text-blue-900 mt-1"><?= $cantGestion ?></h3>
                </div>
                <div class="bg-blue-200 text-blue-700 p-3 rounded-lg"><i class="fas fa-spinner text-xl"></i></div>
            </div>

            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-emerald-600 font-semibold uppercase tracking-wider">Finalizadas</p>
                    <h3 class="text-2xl font-black text-emerald-900 mt-1"><?= $cantFinalizadas ?></h3>
                </div>
                <div class="bg-emerald-200 text-emerald-700 p-3 rounded-lg"><i class="fas fa-check-double text-xl"></i></div>
            </div>
        </div>

        <!-- Tabla de Solicitudes -->
        <div class="overflow-x-auto">
            <table id="tablaSolicitudes" class="w-full text-left border-collapse stripe hover">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 uppercase text-xs tracking-wider">
                        <th class="p-4 border-b">ID</th>
                        <th class="p-4 border-b">Solicitante</th>
                        <th class="p-4 border-b">Fecha y Hora</th>
                        <th class="p-4 border-b">Repuestos Solicitados</th>
                        <th class="p-4 border-b">Observaciones</th>
                        <th class="p-4 border-b text-center">Estado</th>
                        <th class="p-4 border-b text-center">Cambiar Estado</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    <?php if (!empty($solicitudes)): ?>
                        <?php foreach ($solicitudes as $sol): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-mono font-bold text-indigo-700">#<?= $sol['id_solicitud'] ?></td>
                                <td class="p-4 font-bold text-gray-800">
                                    <div class="flex items-center">
                                        <i class="fas fa-user-circle text-gray-400 mr-2 text-lg"></i>
                                        <?= htmlspecialchars($sol['solicitante_nombre']) ?>
                                    </div>
                                </td>
                                <td class="p-4 text-gray-500 text-xs whitespace-nowrap">
                                    <?= date('d/m/Y H:i', strtotime($sol['fecha_solicitud'])) ?>
                                </td>
                                <td class="p-4">
                                    <ul class="space-y-1">
                                        <?php foreach (($sol['detalles'] ?? []) as $det): ?>
                                            <li class="text-xs text-gray-800 flex items-center gap-1.5">
                                                <span class="bg-indigo-100 text-indigo-800 font-bold px-2 py-0.5 rounded-md text-[11px]">
                                                    <?= intval($det['cantidad']) ?>x
                                                </span>
                                                <span class="font-medium"><?= htmlspecialchars($det['nombre_repuesto']) ?></span>
                                                <?php if (!empty($det['codigo_referencia'])): ?>
                                                    <span class="text-gray-400 text-[10px]">(Ref: <?= htmlspecialchars($det['codigo_referencia']) ?>)</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td class="p-4 text-xs text-gray-600 max-w-xs italic">
                                    <?= !empty($sol['observaciones']) ? htmlspecialchars($sol['observaciones']) : '<span class="text-gray-300">Sin observaciones</span>' ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php 
                                    $est = $sol['estado'] ?? 'Pendiente'; 
                                    $badgeClass = 'bg-amber-100 text-amber-800 border-amber-300';
                                    if ($est === 'En gestión') {
                                        $badgeClass = 'bg-blue-100 text-blue-800 border-blue-300';
                                    } elseif ($est === 'Finalizado') {
                                        $badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                                    }
                                    ?>
                                    <span class="inline-block px-3 py-1 text-xs font-bold rounded-full border <?= $badgeClass ?>">
                                        <?= htmlspecialchars($est) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <form action="<?= BASE_URL ?>solicitarinventario" method="POST" class="inline-block">
                                        <input type="hidden" name="accion" value="cambiarEstado">
                                        <input type="hidden" name="id_solicitud" value="<?= $sol['id_solicitud'] ?>">
                                        <select name="nuevo_estado" onchange="this.form.submit()" 
                                                class="text-xs border border-gray-300 rounded-lg px-2.5 py-1.5 bg-white font-semibold shadow-sm hover:border-indigo-500 focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                            <option value="Pendiente" <?= $est === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                            <option value="En gestión" <?= $est === 'En gestión' ? 'selected' : '' ?>>En gestión</option>
                                            <option value="Finalizado" <?= $est === 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
    $(document).ready(function () {
        $('#tablaSolicitudes').DataTable({
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            order: [[0, 'desc']]
        });
    });
</script>
