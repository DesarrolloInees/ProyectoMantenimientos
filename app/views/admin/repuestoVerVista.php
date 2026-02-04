<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

<style>
    /* Personalización para que DataTables se vea nativo de Tailwind */
    .dataTables_length select,
    .dataTables_filter input {
        background-color: white !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin: 0 0.5rem;
    }

    .dataTables_length label,
    .dataTables_filter label {
        color: #4b5563 !important;
        font-weight: 500;
        display: flex;
        align-items: center;
    }

    #repuestosTable tbody tr {
        background-color: white !important;
    }

    #repuestosTable tbody tr:hover {
        background-color: #f9fafb !important;
    }

    .dataTables_paginate .paginate_button.current,
    .dataTables_paginate .paginate_button:hover {
        background-color: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }

    /* Controles responsive */
    .dataTables_wrapper>div:first-child,
    .dataTables_wrapper>div:last-of-type {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin: 1.5rem 0;
    }
</style>

<div class="w-full max-w-7xl mx-auto">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 border-b pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                    <i class="fas fa-boxes text-indigo-600 mr-2"></i> Inventario de Repuestos
                </h1>
                <p class="text-gray-500 mt-1">Gestiona el catálogo de componentes y sus referencias.</p>
            </div>
            
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                <?php if (!empty($data['repuestos'])): ?>
                    <button onclick="exportarExcel()" class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow-md hover:bg-green-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                        <i class="fas fa-file-excel"></i>
                        <span>Excel</span>
                    </button>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>repuestoCrear" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-md hover:bg-indigo-700 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                    <i class="fas fa-plus-circle"></i>
                    <span>Nuevo Repuesto</span>
                </a>
            </div>
        </div>

        <?php if (!empty($data['repuestos'])): ?>
            <div class="overflow-x-auto">
                <table id="repuestosTable" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="py-3 px-4">ID</th>
                            <th class="py-3 px-4">Nombre Repuesto</th>
                            <th class="py-3 px-4">Cód. Referencia</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($data['repuestos'] as $r): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4 font-bold text-gray-600">#<?= $r['id_repuesto'] ?></td>
                                <td class="py-4 px-4 font-medium text-gray-900"><?= htmlspecialchars($r['nombre_repuesto']) ?></td>
                                <td class="py-4 px-4 font-mono text-gray-500 text-xs">
                                    <?= !empty($r['codigo_referencia']) ? htmlspecialchars($r['codigo_referencia']) : '<span class="text-gray-300 italic">N/A</span>' ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <?php if ($r['estado'] == 1): ?>
                                        <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full border border-green-200">
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full border border-red-200">
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <div class="flex justify-center items-center space-x-2">
                                        <a href="<?= BASE_URL ?>repuestoEditar/<?= $r['id_repuesto'] ?>"
                                            class="p-2 w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200 transition-colors border border-yellow-200" title="Editar">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>

                                        <button onclick="abrirModalEliminar(<?= $r['id_repuesto'] ?>)"
                                            class="p-2 w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-full hover:bg-red-200 transition-colors border border-red-200" title="Eliminar">
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
            <div class="text-center p-10 border-2 border-dashed border-gray-200 rounded-lg bg-gray-50">
                <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 font-medium">No hay repuestos registrados.</p>
                <p class="text-gray-400 text-sm mt-1">¡Registra el primero usando el botón azul!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalEliminar" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="cerrarModalEliminar()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Eliminar Repuesto
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                ¿Estás seguro de que deseas eliminar este repuesto? Esta acción ocultará el ítem del inventario activo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a id="btnConfirmarEliminar" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Sí, Eliminar
                </a>
                <button type="button" onclick="cerrarModalEliminar()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
    // 1. CAPTURAMOS LOS DATOS DE PHP AQUI
    const listaRepuestos = <?= json_encode($data['repuestos'] ?? []) ?>;

    // 2. FUNCIÓN DE EXPORTAR
    function exportarExcel() {
        if (listaRepuestos.length === 0) {
            alert("No hay datos para exportar");
            return;
        }

        // Mapeamos los datos para que las columnas tengan nombres bonitos en el Excel
        const datosExcel = listaRepuestos.map(r => ({
            "ID": r.id_repuesto,
            "Nombre Repuesto": r.nombre_repuesto,
            "Código Referencia": r.codigo_referencia || "N/A",
            "Estado": r.estado == 1 ? "Activo" : "Inactivo"
        }));

        // Crear hoja y libro
        const ws = XLSX.utils.json_to_sheet(datosExcel);
        const wb = XLSX.utils.book_new();

        // Ajustar ancho de columnas (Opcional, para que se vea Pro)
        ws['!cols'] = [{wch: 10}, {wch: 40}, {wch: 20}, {wch: 15}];

        XLSX.utils.book_append_sheet(wb, ws, "Repuestos");

        // Descargar archivo con fecha
        const fecha = new Date().toISOString().slice(0,10);
        XLSX.writeFile(wb, `Repuestos_${fecha}.xlsx`);
    }

    // TU CÓDIGO EXISTENTE DE DATATABLES
    $(document).ready(function() {
        $('#repuestosTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            dom: '<"flex flex-wrap justify-between items-center mb-4"lf>rt<"flex flex-wrap justify-between items-center mt-4"ip>'
        });
    });

    // Lógica del Modal
    function abrirModalEliminar(id) {
        // Actualizamos el enlace del botón rojo con el ID correcto
        const urlBase = "<?= BASE_URL ?>";
        document.getElementById('btnConfirmarEliminar').href = urlBase + "repuestoEliminar/" + id;

        // Mostramos el modal
        document.getElementById('modalEliminar').classList.remove('hidden');
    }

    function cerrarModalEliminar() {
        document.getElementById('modalEliminar').classList.add('hidden');
    }
</script>