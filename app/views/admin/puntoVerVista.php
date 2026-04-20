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

    #puntosTable tbody tr {
        background-color: white !important;
    }

    #puntosTable tbody tr:hover {
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

<div class="flex justify-between items-center mb-6 border-b pb-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-store-alt text-red-600 mr-2"></i> Puntos de Atención</h1>
        <p class="text-gray-500">Gestión de sucursales y ubicaciones.</p>
    </div>

    <div class="flex space-x-3">
    <button onclick="descargarExcelPuntos()" class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow hover:bg-green-700 transition flex items-center space-x-2">
        <i class="fas fa-file-excel"></i> <span>Exportar Excel</span>
    </button>
    <a href="<?= BASE_URL ?>puntoCrear" class="px-5 py-2.5 bg-red-600 text-white font-bold rounded-lg shadow hover:bg-red-700 transition flex items-center space-x-2">
        <i class="fas fa-plus-circle"></i> <span>Nuevo Punto</span>
    </a>
</div>
</div>

<?php if (!empty($data['puntos'])): ?>
    <div class="overflow-x-auto">
        <table id="puntosTable" class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="py-3 px-4">ID</th>
                    <th class="py-3 px-4">Punto</th>
                    <th class="py-3 px-4">Cliente</th>
                    <th class="py-3 px-4">Ubicación</th>
                    <th class="py-3 px-4">Modalidad</th>
                    <th class="py-3 px-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($data['puntos'] as $p): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 font-bold text-gray-600">#<?= $p['id_punto'] ?></td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-800"><?= htmlspecialchars($p['nombre_punto']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($p['direccion']) ?></div>
                        </td>
                        <td class="py-3 px-4"><?= htmlspecialchars($p['nombre_cliente']) ?></td>
                        <td class="py-3 px-4">
                            <span class="block"><?= htmlspecialchars($p['nombre_municipio']) ?></span>
                            <?php if (!empty($p['nombre_delegacion'])): ?>
                                <span class="text-xs text-indigo-600 bg-indigo-50 px-1 rounded"><?= htmlspecialchars($p['nombre_delegacion']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4"><?= htmlspecialchars($p['nombre_modalidad']) ?></td>
                        <td class="py-3 px-4 text-center flex justify-center space-x-2">
                            <a href="<?= BASE_URL ?>puntoEditar/<?= $p['id_punto'] ?>" class="p-2 bg-yellow-100 text-yellow-600 rounded-full hover:bg-yellow-200"><i class="fas fa-edit"></i></a>
                            <button onclick="abrirModal(<?= $p['id_punto'] ?>)" class="p-2 bg-red-100 text-red-600 rounded-full hover:bg-red-200"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="text-center p-8 bg-gray-50 rounded-lg">
        <p class="text-gray-500">No hay puntos registrados.</p>
    </div>
<?php endif; ?>
</div>
</div>

<div id="modalEliminar" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Eliminar Punto</h3>
        <p class="text-sm text-gray-500 mb-4">¿Seguro? Quedará inactivo en el sistema.</p>
        <div class="flex justify-end space-x-3">
            <button onclick="cerrarModal()" class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200">Cancelar</button>
            <a id="btnConfirmar" href="#" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Sí, Eliminar</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

<script>
    // 1. RECIBIMOS LOS DATOS DE PHP DIRECTO AL JS (El Truco)
    const datosCompletosPuntos = <?= json_encode($data['datosExcel'] ?? []) ?>;

    $(document).ready(function() {
        $('#puntosTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
        });
    });

    // Modal functions...
    function abrirModal(id) { /* ... */ }
    function cerrarModal() { /* ... */ }

    // 2. FUNCIÓN PARA EXPORTAR RESPETANDO EL FILTRO
    function descargarExcelPuntos() {
        if (!datosCompletosPuntos || datosCompletosPuntos.length === 0) {
            alert("No hay datos cargados en el sistema.");
            return;
        }

        const table = $('#puntosTable').DataTable();
        
        // Obtenemos los nodos de las filas que cumplen con el filtro actual del buscador
        const filasFiltradas = table.rows({ search: 'applied' }).nodes();
        let idsValidos = [];
        
        // Extraemos el ID de cada fila (columna 0)
        $(filasFiltradas).each(function() {
            let textoId = $(this).find('td:eq(0)').text(); // Ej: "#4665"
            let id = textoId.replace('#', '').trim();
            if(id) {
                idsValidos.push(id.toString());
            }
        });

        if(idsValidos.length === 0) {
            alert("No hay registros para exportar con el filtro actual.");
            return;
        }

        // Filtramos el JSON gigante cruzándolo con los IDs válidos del DataTables
        let dataAExportar = datosCompletosPuntos.filter(item => idsValidos.includes(item.id_punto.toString()));

        // Mapeamos para limpiar las columnas y no mostrar el 'id_punto' en el Excel
        let dataFinalExcel = dataAExportar.map(item => {
            return {
                "Cliente": item.Cliente,
                "Punto": item.Punto,
                "Tipo de Máquina": item.Tipo_Maquina || 'Sin Máquina',
                "Device ID": item.Device_ID || 'N/A',
                "Dirección": item.Direccion
            };
        });

        // Generamos el Excel
        const hoja = XLSX.utils.json_to_sheet(dataFinalExcel);
        const libro = XLSX.utils.book_new();
        
        // Anchos de columna pro
        hoja['!cols'] = [
            {wch: 25}, // Cliente
            {wch: 35}, // Punto
            {wch: 20}, // Tipo de Máquina
            {wch: 15}, // Device ID
            {wch: 40}  // Dirección
        ];

        XLSX.utils.book_append_sheet(libro, hoja, "Puntos de Atención");
        
        let fechaHoy = new Date().toISOString().slice(0, 10).replace(/-/g, "");
        XLSX.writeFile(libro, `Reporte_Puntos_${fechaHoy}.xlsx`);
    }
</script>