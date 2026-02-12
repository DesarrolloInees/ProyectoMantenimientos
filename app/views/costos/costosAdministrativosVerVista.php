<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
    /* Estilos personalizados PRO (Idénticos a tu módulo de Clientes) */
    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin: 0 0.5rem;
        outline: none;
    }

    .dataTables_length select:focus,
    .dataTables_filter input:focus {
        border-color: #4f46e5 !important;
    }

    #costosTable tbody tr {
        background-color: white !important;
        border-bottom: 1px solid #f3f4f6;
    }

    #costosTable tbody tr:hover {
        background-color: #f9fafb !important;
    }

    .dataTables_paginate .paginate_button.current {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
        border-radius: 0.375rem;
    }

    .dataTables_paginate .paginate_button:hover {
        background-color: #eef2ff !important;
        color: #4f46e5 !important;
        border: 1px solid #4f46e5 !important;
        border-radius: 0.375rem;
    }

    .dataTables_wrapper .dataTables_info {
        color: #6b7280 !important;
        font-size: 0.875rem;
    }

    /* Ajuste para los totales en el footer */
    #costosTable tfoot tr {
        background-color: #f3f4f6;
        font-weight: bold;
        color: #1f2937;
    }
</style>

<div class="w-full max-w-7xl mx-auto">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b border-gray-100 pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                    <i class="fas fa-chart-line text-indigo-600 mr-2"></i> Histórico Administrativo
                </h1>
                <p class="text-gray-500 mt-1">Resumen mensual de nómina y gastos generales.</p>
            </div>
            <a href="<?= BASE_URL ?>costosAdministrativosCrear" class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                <i class="fas fa-plus-circle"></i> <span>Nuevo Registro</span>
            </a>
        </div>

        <?php if (!empty($reporteMensual)): ?>
            <div class="overflow-x-auto">
                <table id="costosTable" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="py-3 px-4">Mes Reporte</th>
                            <th class="py-3 px-4 text-right">Nómina Admin</th>
                            <th class="py-3 px-4 text-right">Gastos Generales</th>
                            <th class="py-3 px-4 text-right">Total Mes</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php
                        // Array auxiliar para fechas (tu arreglo seguro)
                        $mesesEs = [
                            1 => "Enero",
                            2 => "Febrero",
                            3 => "Marzo",
                            4 => "Abril",
                            5 => "Mayo",
                            6 => "Junio",
                            7 => "Julio",
                            8 => "Agosto",
                            9 => "Septiembre",
                            10 => "Octubre",
                            11 => "Noviembre",
                            12 => "Diciembre"
                        ];
                        ?>
                        <?php foreach ($reporteMensual as $fila): ?>
                            <?php
                            $totalMes = $fila['total_nomina'] + $fila['total_gastos'];

                            // Formato de fecha seguro
                            $fechaObj = new DateTime($fila['mes_reporte']);
                            $numMes = $fechaObj->format('n');
                            $anio = $fechaObj->format('Y');
                            $nombreMes = $mesesEs[$numMes] . " " . $anio;
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4">
                                    <span class="hidden"><?= $fila['mes_reporte'] ?></span>
                                    <div class="flex items-center space-x-2">
                                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                            <i class="far fa-calendar-alt"></i>
                                        </div>
                                        <span class="font-bold text-gray-700 capitalize"><?= $nombreMes ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-right font-mono text-blue-600 font-medium">
                                    $ <?= number_format($fila['total_nomina'], 2) ?>
                                </td>
                                <td class="py-4 px-4 text-right font-mono text-orange-600 font-medium">
                                    $ <?= number_format($fila['total_gastos'], 2) ?>
                                </td>
                                <td class="py-4 px-4 text-right font-mono font-bold text-gray-900 bg-gray-50/50">
                                    $ <?= number_format($totalMes, 2) ?>
                                </td>
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <a href="<?= BASE_URL ?>costosAdministrativosEditar?mes_reporte=<?= $fila['mes_reporte'] ?>"
                                        class="inline-flex items-center justify-center px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition-colors border border-yellow-200 text-xs font-semibold space-x-1"
                                        title="Ver detalles">
                                        <i class="fas fa-eye"></i> <span>Ver / Editar</span>
                                    </a>

                                    <a href="<?= BASE_URL ?>?pagina=costosAdministrativosVer&accion=eliminarMes&mes=<?= $fila['mes_reporte'] ?>"
                                        onclick="return confirm('ATENCIÓN: ¿Estás seguro de eliminar TODO el reporte de <?= $nombreMes ?>?\n\nEsto borrará:\n- Gastos Generales\n- Nómina de Administrativos\n\n(Los técnicos NO se verán afectados)');"
                                        class="inline-flex items-center justify-center px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors border border-red-200 text-xs font-semibold ml-2"
                                        title="Eliminar Reporte Mensual">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="py-3 px-4 text-right uppercase text-xs">Totales Generales:</td>
                            <td class="py-3 px-4 text-right font-mono text-indigo-900"></td>
                            <td class="py-3 px-4 text-right font-mono text-indigo-900"></td>
                            <td class="py-3 px-4 text-right font-mono text-indigo-900"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center p-10 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-4">
                    <i class="fas fa-file-invoice-dollar text-indigo-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No hay registros</h3>
                <p class="text-gray-500 mt-1">Aún no se han cargado costos administrativos.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
    $(document).ready(function() {
        $('#costosTable').DataTable({
            responsive: true,
            order: [
                [0, "desc"]
            ], // Ordenar por fecha descendente
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            // Función para sumar totales automáticamente
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();

                // Función limpiar moneda para sumar
                var intVal = function(i) {
                    return typeof i === 'string' ?
                        i.replace(/[\$,]/g, '') * 1 :
                        typeof i === 'number' ? i : 0;
                };

                // Calcular totales
                var totalNomina = api.column(1, {
                    page: 'current'
                }).data().reduce(function(a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
                var totalGastos = api.column(2, {
                    page: 'current'
                }).data().reduce(function(a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
                var grandTotal = api.column(3, {
                    page: 'current'
                }).data().reduce(function(a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                // Formateador de moneda
                var formato = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD'
                });

                // Poner totales en el footer
                $(api.column(1).footer()).html(formato.format(totalNomina));
                $(api.column(2).footer()).html(formato.format(totalGastos));
                $(api.column(3).footer()).html(formato.format(grandTotal));
            }
        });
    });
</script>