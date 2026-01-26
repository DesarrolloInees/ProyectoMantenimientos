<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


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

    #tablaMaquinas tbody tr {
        background-color: white !important;
    }

    #tablaMaquinas tbody tr:hover {
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
    
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-6">
        <div class="flex justify-between items-center border-b pb-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-filter text-indigo-600 mr-2"></i> Reporte por Tipo de Máquina
                </h1>
                <p class="text-gray-500 text-sm">Selecciona el tipo de equipo para ver su ubicación y fechas.</p>
            </div>
            <?php if (!empty($datosMaquinas)): ?>
                <button type="button" onclick="generarExcel()" class="bg-green-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-green-700 shadow flex items-center gap-2 transition-all">
                    <i class="fas fa-file-excel"></i> Exportar Resultados
                </button>
            <?php endif; ?>
        </div>

        <form action="" method="POST" class="flex flex-wrap gap-4 items-end">
            <div class="w-full md:w-1/3">
                <label class="block text-sm font-bold text-gray-700 mb-1">Seleccionar Tipo de Máquina:</label>
                <select name="id_tipo_maquina" id="select_tipo" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($listaTipos as $tipo): ?>
                        <option value="<?= $tipo['id_tipo_maquina'] ?>" <?= ($idTipoSeleccionado == $tipo['id_tipo_maquina']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tipo['nombre_tipo_maquina']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700 shadow transition-all">
                    <i class="fas fa-search mr-2"></i> Consultar
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($idTipoSeleccionado)): ?>
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
        <div class="overflow-x-auto">
            <table id="tablaMaquinas" class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                    <tr>
                        <th class="px-6 py-3">Delegación</th>
                        <th class="px-6 py-3">Device ID</th>
                        <th class="px-6 py-3">Tipo</th>
                        <th class="px-6 py-3">Punto</th>
                        <th class="px-6 py-3">Dirección</th>
                        <th class="px-6 py-3">Última Visita (Punto)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($datosMaquinas)): ?>
                        <?php else: ?>
                        <?php foreach ($datosMaquinas as $row): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-blue-600"><?= !empty($row['nombre_delegacion']) ? $row['nombre_delegacion'] : 'N/A' ?></td>
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600"><?= $row['device_id'] ?></td>
                                <td class="px-6 py-4"><?= $row['nombre_tipo_maquina'] ?></td>
                                <td class="px-6 py-4 font-bold text-gray-900"><?= $row['nombre_punto'] ?></td>
                                <td class="px-6 py-4"><?= $row['direccion'] ?></td>
                                <td class="px-6 py-4">
                                    <?php 
                                        echo $row['fecha_ultima_visita'] 
                                            ? date('d/m/Y', strtotime($row['fecha_ultima_visita'])) 
                                            : '<span class="text-red-400">Sin registro</span>'; 
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Recibimos los datos de PHP
    const datosRaw = <?= json_encode($datosMaquinas ?? []) ?>;

    $(document).ready(function() {
        // Inicializar Select2 (Buscador en el dropdown)
        $('#select_tipo').select2({
            placeholder: "Escribe para buscar...",
            allowClear: true,
            width: '100%'
        });

        // Inicializar DataTables
        if ($('#tablaMaquinas').length) {
            $('#tablaMaquinas').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                responsive: true
            });
        }
    });

    function generarExcel() {
        if (datosRaw.length === 0) {
            alert("No hay datos para exportar");
            return;
        }

        const datosExcel = datosRaw.map(d => ({
            "Delegación": d.nombre_delegacion || "N/A",
            "ID Dispositivo": d.device_id,
            "Tipo Máquina": d.nombre_tipo_maquina,
            "Nombre Punto": d.nombre_punto,
            "Dirección": d.direccion,
            "Fecha Visita Punto": d.fecha_ultima_visita
        }));

        const ws = XLSX.utils.json_to_sheet(datosExcel);
        const wb = XLSX.utils.book_new();
        
        // Anchos
        ws['!cols'] = [{wch:20}, {wch:15}, {wch:20}, {wch:30}, {wch:40}, {wch:15}];

        // Nombre del archivo dinámico
        let nombreTipo = datosRaw[0].nombre_tipo_maquina.replace(/[^a-z0-9]/gi, '_');
        XLSX.utils.book_append_sheet(wb, ws, "Reporte");
        XLSX.writeFile(wb, `Reporte_${nombreTipo}.xlsx`);
    }
</script>

<style>
    .select2-container .select2-selection--single {
        height: 42px !important;
        border-color: #d1d5db !important;
        display: flex; align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }
</style>