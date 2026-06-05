<?php if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">

<style>
    /* tus estilos se mantienen igual */
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

<div class="w-full px-4 md:px-6">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                    <i class="fas fa-boxes text-indigo-600 mr-2"></i> Inventario de Remisiones
                </h1>
                <p class="text-gray-500 mt-1">Gestiona el inventario de Remisiones.</p>
            </div>
            <a href="<?= BASE_URL ?>controlRemisionCrear"
                class="mt-4 sm:mt-0 px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                <i class="fas fa-plus-circle"></i>
                <span>Nuevas Remisiones</span>
            </a>
        </div>

        <div class="w-full px-4 md:px-6">
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <table id="tablaRemisiones" class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3"># Remisión</th>
                            <th class="px-6 py-3">Técnico Responsable</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Fecha Asignación</th>
                            <th class="px-6 py-3">Fecha Uso</th>
                            <th class="px-6 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Aquí ya no se imprimen filas estáticas, DataTables las llena vía AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>

<script>
    $(document).ready(function () {
        $('#tablaRemisiones').DataTable({
            serverSide: true,      // Habilita procesamiento del lado del servidor
            ajax: {
                url: '<?= BASE_URL ?>index.php?pagina=controlRemisionVer&accion=obtenerDatosDatatable',
                type: 'GET',
                dataSrc: 'data'
            },
            columns: [
                { data: 'numero_remision', className: 'font-bold text-gray-900' },
                { data: 'nombre_tecnico' },
                {
                    data: 'nombre_estado',
                    render: function (data, type, row) {
                        if (data == 'DISPONIBLE') {
                            return '<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Disponible</span>';
                        } else if (data == 'USADA') {
                            return '<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Usada</span>';
                        } else if (data == 'ANULADA') {
                            return '<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Anulada</span>';
                        } else {
                            return '<span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">' + data + '</span>';
                        }
                    }
                },
                {
                    data: 'fecha_asignacion',
                    render: function (data) {
                        if (!data) return '';
                        let date = new Date(data);
                        return date.toLocaleDateString('es-ES');
                    }
                },
                {
                    data: 'fecha_uso',
                    render: function (data) {
                        if (!data) return '<span class="text-gray-300">---</span>';
                        let date = new Date(data);
                        return `<div class="flex flex-col">
                                <span class="text-gray-900 font-medium">${date.toLocaleDateString('es-ES')}</span>
                                <span class="text-xs text-gray-400">${date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}</span>
                            </div>`;
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        if (row.nombre_estado != 'USADA') {
                            return `<div class="text-center">
                                    <a href="<?= BASE_URL ?>controlRemisionEditar&id=${row.id_control}" class="text-indigo-600 hover:text-indigo-900 mx-2" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>controlRemisionEliminar&id=${row.id_control}" class="text-red-600 hover:text-red-900 mx-2" onclick="return confirm('¿Eliminar esta remisión?');" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>`;
                        } else {
                            return '<span class="text-gray-300 italic text-xs">Bloqueado</span>';
                        }
                    },
                    orderable: false
                }
            ],
            order: [[0, 'desc']],  // orden por número de remisión descendente (ajústalo según tu columna)
            pageLength: 100,        // registros por página
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            }
        });
    });
</script>