<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<!-- CDN DATATABLES + RESPONSIVE -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<style>
    body {
        background-color: #f8fafc;
    }

    .select2-container .select2-selection--single {
        height: 2.5rem !important;
        padding: 0.25rem !important;
        border-color: #d1d5db !important;
        border-radius: 0.375rem !important;
        display: flex;
        align-items: center;
    }

    .dataTables_wrapper {
        padding: 1rem;
        width: 100% !important;
    }

    /* FORZAR LA TABLA AL 100% DE ANCHO EN CUALQUIER PANTALLA */
    table.dataTable {
        width: 100% !important;
        margin: 0 auto !important;
    }

    table.dataTable.no-footer {
        border-bottom: 1px solid #e5e7eb !important;
    }

    table.dataTable thead th {
        border-bottom: 2px solid #e5e7eb !important;
        white-space: nowrap !important;
    }

    /* Evitar saltos de línea feos en celdas de PC */
    table.dataTable tbody td {
        white-space: nowrap !important;
        vertical-align: middle;
    }

    .dataTables_length select,
    .dataTables_filter input {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.375rem 0.5rem;
        outline: none;
    }

    .dataTables_filter input:focus {
        border-color: #2563eb;
    }
</style>

<div class="p-4 md:p-6 max-w-full mx-auto space-y-6">

    <!-- Encabezado -->
    <div class="flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800"><i
                    class="fas fa-parking text-blue-600 mr-2"></i>Reporte de Parqueaderos</h1>
            <p class="text-gray-500 text-xs md:text-sm mt-1">Gestión y auditoría de facturas registradas por los
                técnicos.</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="pagina" value="parqueaderoAdmin">

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>"
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>"
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Técnico</label>
                <select name="id_tecnico" class="w-full select2-admin border-gray-300 rounded-lg">
                    <option value="">- Todos los técnicos -</option>
                    <?php foreach ($tecnicos as $tec): ?>
                        <option value="<?= $tec['id_tecnico'] ?>" <?= ($idTecnico == $tec['id_tecnico']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tec['nombre_tecnico']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="index.php?pagina=parqueaderoAdmin"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center justify-center"
                    title="Limpiar Filtros">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tarjetas de Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
            class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-5 text-white shadow-md flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-full">
                <i class="fas fa-receipt text-3xl"></i>
            </div>
            <div>
                <p class="text-blue-100 text-sm font-semibold uppercase">Total Facturas</p>
                <h3 class="text-3xl font-bold"><?= $totalFacturas ?></h3>
            </div>
        </div>
        <div
            class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-5 text-white shadow-md flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-full">
                <i class="fas fa-dollar-sign text-3xl"></i>
            </div>
            <div>
                <p class="text-emerald-100 text-sm font-semibold uppercase">Total Gastado</p>
                <h3 class="text-3xl font-bold">$<?= number_format($totalGastado, 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <!-- Tabla con DataTables -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-2 overflow-x-auto">
            <table id="tablaParqueaderosAdmin" class="display responsive nowrap w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th>Fecha</th>
                        <th>Técnico</th>
                        <th>Punto</th>
                        <th>Horario</th>
                        <th>N° Factura</th>
                        <th class="text-right">Valor</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $fac): ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="font-medium text-gray-900">
                                <?= date('d/m/Y', strtotime($fac['fecha_servicio'])) ?>
                            </td>
                            <td class="font-semibold text-blue-700">
                                <?= htmlspecialchars($fac['nombre_tecnico']) ?>
                            </td>
                            <td class="text-gray-600">
                                <?= htmlspecialchars($fac['nombre_punto']) ?>
                            </td>
                            <td>
                                <span
                                    class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono border border-gray-200">
                                    <?= date('H:i', strtotime($fac['hora_inicio'])) ?> -
                                    <?= date('H:i', strtotime($fac['hora_fin'])) ?>
                                </span>
                            </td>
                            <td class="font-mono font-bold">
                                <?= htmlspecialchars($fac['numero_factura']) ?>
                            </td>
                            <td class="text-right font-bold text-green-600">
                                $<?= number_format($fac['valor_factura'], 2, ',', '.') ?>
                            </td>
                            <td class="text-center">
                                <button type="button"
                                    onclick="abrirModalFotoAdmin('<?= BASE_URL . $fac['ruta_foto'] ?>', '<?= htmlspecialchars($fac['numero_factura']) ?>', '<?= htmlspecialchars($fac['nombre_tecnico']) ?>')"
                                    class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 p-2 px-3 rounded-lg font-bold text-xs transition inline-flex items-center justify-center gap-1.5 whitespace-nowrap"
                                    title="Ver Factura">
                                    <i class="fas fa-eye"></i> Ver Factura
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para ver la foto -->
<div id="modalFotoAdmin"
    class="fixed inset-0 bg-black/90 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-3xl transform scale-95 transition-transform duration-300" id="modalContentAdmin">
        <div class="flex justify-between items-center mb-3 text-white border-b border-gray-700 pb-2">
            <div>
                <h3 class="font-bold text-lg" id="tituloModalFotoAdmin">Factura</h3>
                <p class="text-gray-400 text-sm" id="subtituloModalFotoAdmin"></p>
            </div>
            <button type="button" onclick="cerrarModalFotoAdmin()"
                class="text-gray-400 hover:text-white text-3xl leading-none transition">&times;</button>
        </div>
        <div
            class="bg-white rounded-xl overflow-hidden flex justify-center items-center min-h-[300px] shadow-2xl relative">
            <div id="loadingSpinner" class="absolute flex flex-col items-center justify-center text-gray-400">
                <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                <span class="text-sm">Cargando imagen...</span>
            </div>
            <img id="imagenModalAdmin" src="" alt="Foto Factura"
                class="max-w-full max-h-[75vh] object-contain relative z-10 hidden" onload="imagenCargada()">
        </div>
    </div>
</div>

<script>
    if (typeof window.BASE_URL === 'undefined') {
        window.BASE_URL = '<?= BASE_URL ?>';
    }

    $(document).ready(function () {
        $('.select2-admin').select2({
            width: '100%',
            language: { noResults: function () { return "No se encontraron resultados"; } }
        });

        // DataTables con responsive inteligente
        $('#tablaParqueaderosAdmin').DataTable({
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
        });
    });

    function abrirModalFotoAdmin(rutaCompleta, numeroFactura, nombreTecnico) {
        $('#tituloModalFotoAdmin').html('<i class="fas fa-file-invoice-dollar mr-2 text-blue-400"></i>Factura N° ' + numeroFactura);
        $('#subtituloModalFotoAdmin').html('<i class="fas fa-user-hard-hat mr-1"></i> Subida por: ' + nombreTecnico);

        $('#imagenModalAdmin').addClass('hidden').attr('src', '');
        $('#loadingSpinner').removeClass('hidden');

        $('#imagenModalAdmin').attr('src', rutaCompleta);

        $('#modalFotoAdmin').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalFotoAdmin').removeClass('opacity-0').addClass('opacity-100');
            $('#modalContentAdmin').removeClass('scale-95').addClass('scale-100');
        }, 10);
    }

    function imagenCargada() {
        $('#loadingSpinner').addClass('hidden');
        $('#imagenModalAdmin').removeClass('hidden');
    }

    function cerrarModalFotoAdmin() {
        $('#modalFotoAdmin').removeClass('opacity-100').addClass('opacity-0');
        $('#modalContentAdmin').removeClass('scale-100').addClass('scale-95');

        setTimeout(() => {
            $('#modalFotoAdmin').removeClass('flex').addClass('hidden');
            $('#imagenModalAdmin').attr('src', '').addClass('hidden');
        }, 300);
    }

    $('#modalFotoAdmin').on('click', function (e) {
        if (e.target === this) {
            cerrarModalFotoAdmin();
        }
    });
</script>