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

    <!-- Encabezado con Botones de Exportación -->
    <div class="flex flex-col md:flex-row md:items-center justify-between bg-white p-5 rounded-xl shadow-sm border border-gray-100 gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                <i class="fas fa-parking text-blue-600 mr-2"></i>Reporte de Parqueaderos
            </h1>
            <p class="text-gray-500 text-xs md:text-sm mt-1">Gestión y auditoría de facturas registradas por los técnicos.</p>
        </div>

        <!-- BOTONES EXPORTAR -->
        <div class="flex items-center gap-2">
            <a href="index.php?pagina=parqueaderoAdmin&accion=exportarExcel&fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>&id_tecnico=<?= $idTecnico ?>"
                target="_blank"
                class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 px-4 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="fas fa-file-excel text-base"></i> Exportar Excel
            </a>

            <a href="index.php?pagina=parqueaderoAdmin&accion=exportarPdf&fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>&id_tecnico=<?= $idTecnico ?>"
                target="_blank"
                class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-4 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="fas fa-file-pdf text-base"></i> Exportar PDF
            </a>
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
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-5 text-white shadow-md flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-full">
                <i class="fas fa-receipt text-3xl"></i>
            </div>
            <div>
                <p class="text-blue-100 text-sm font-semibold uppercase">Total Facturas</p>
                <h3 class="text-3xl font-bold"><?= $totalFacturas ?></h3>
            </div>
        </div>
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-5 text-white shadow-md flex items-center gap-4">
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
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono border border-gray-200">
                                    <?= date('H:i', strtotime($fac['hora_inicio'])) ?> - <?= date('H:i', strtotime($fac['hora_fin'])) ?>
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
                                    onclick="abrirModalFotoAdmin('<?= htmlspecialchars($fac['ruta_foto']) ?>', '<?= htmlspecialchars($fac['numero_factura']) ?>', '<?= htmlspecialchars($fac['nombre_tecnico']) ?>')"
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

<!-- Modal para ver la foto con botones de rotación y guardado -->
<div id="modalFotoAdmin"
    class="fixed inset-0 bg-black/90 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-4xl transform scale-95 transition-transform duration-300" id="modalContentAdmin">

        <div class="flex flex-wrap justify-between items-center mb-3 text-white border-b border-gray-700 pb-2 gap-2">
            <div>
                <h3 class="font-bold text-lg" id="tituloModalFotoAdmin">Factura</h3>
                <p class="text-gray-400 text-sm" id="subtituloModalFotoAdmin"></p>
            </div>

            <!-- CONTROLES DE ROTACIÓN Y GUARDADO -->
            <div class="flex items-center gap-2">
                <button type="button" onclick="rotarImagenModal(-90)"
                    class="bg-gray-800 hover:bg-gray-700 text-white p-2 px-3 rounded-lg text-xs font-bold transition flex items-center gap-1"
                    title="Rotar 90° a la izquierda">
                    <i class="fas fa-undo"></i> 90°
                </button>
                <button type="button" onclick="rotarImagenModal(90)"
                    class="bg-gray-800 hover:bg-gray-700 text-white p-2 px-3 rounded-lg text-xs font-bold transition flex items-center gap-1"
                    title="Rotar 90° a la derecha">
                    <i class="fas fa-redo"></i> 90°
                </button>

                <!-- BOTÓN GUARDAR ROTACIÓN -->
                <button type="button" id="btnGuardarRotacion" onclick="guardarRotacionServidor()"
                    class="hidden bg-emerald-600 hover:bg-emerald-700 text-white p-2 px-3 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                    <i class="fas fa-save"></i> Guardar Rotación
                </button>

                <button type="button" onclick="cerrarModalFotoAdmin()"
                    class="text-gray-400 hover:text-white text-3xl leading-none ml-2">&times;</button>
            </div>
        </div>

        <div class="bg-white rounded-xl overflow-hidden flex justify-center items-center min-h-[300px] shadow-2xl relative p-2">
            <img id="imagenModalAdmin" src="" alt="Foto Factura"
                class="max-w-full max-h-[75vh] object-contain transition-transform duration-300">
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

    let anguloRotacionActual = 0;
    let rutaFotoActualRelativa = '';

    function abrirModalFotoAdmin(rutaRelativa, numeroFactura, nombreTecnico) {
        anguloRotacionActual = 0;
        rutaFotoActualRelativa = rutaRelativa;
        
        $('#imagenModalAdmin').css('transform', 'rotate(0deg)');
        $('#btnGuardarRotacion').addClass('hidden').removeClass('inline-flex');

        $('#tituloModalFotoAdmin').html('<i class="fas fa-file-invoice-dollar mr-2 text-blue-400"></i>Factura N° ' + numeroFactura);
        $('#subtituloModalFotoAdmin').html('<i class="fas fa-user-hard-hat mr-1"></i> Subida por: ' + nombreTecnico);

        const urlCompleta = (rutaRelativa.startsWith('http') || rutaRelativa.startsWith('/')) 
            ? rutaRelativa 
            : window.BASE_URL + rutaRelativa;

        $('#imagenModalAdmin').attr('src', urlCompleta);
        $('#modalFotoAdmin').removeClass('hidden').addClass('flex');

        setTimeout(() => {
            $('#modalFotoAdmin').removeClass('opacity-0').addClass('opacity-100');
            $('#modalContentAdmin').removeClass('scale-95').addClass('scale-100');
        }, 10);
    }

    function rotarImagenModal(grados) {
        anguloRotacionActual = (anguloRotacionActual + grados) % 360;
        $('#imagenModalAdmin').css('transform', `rotate(${anguloRotacionActual}deg)`);

        if (anguloRotacionActual !== 0) {
            $('#btnGuardarRotacion').removeClass('hidden').addClass('inline-flex');
        } else {
            $('#btnGuardarRotacion').addClass('hidden').removeClass('inline-flex');
        }
    }

    function guardarRotacionServidor() {
        if (anguloRotacionActual === 0) return;

        const btn = $('#btnGuardarRotacion');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: 'index.php?pagina=parqueaderoAdmin&accion=guardarRotacion',
            type: 'POST',
            data: {
                ruta_foto: rutaFotoActualRelativa,
                grados: anguloRotacionActual
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.exito) {
                    alert('✅ Rotación guardada correctamente en el servidor.');
                    const timestamp = new Date().getTime();
                    const srcLimpio = $('#imagenModalAdmin').attr('src').split('?')[0];
                    $('#imagenModalAdmin').attr('src', srcLimpio + '?v=' + timestamp);
                    
                    anguloRotacionActual = 0;
                    $('#imagenModalAdmin').css('transform', 'rotate(0deg)');
                    btn.addClass('hidden').removeClass('inline-flex');
                } else {
                    alert('❌ Error: ' + (resp.mensaje || 'No se pudo rotar la imagen.'));
                }
            },
            error: function() {
                alert('❌ Error de comunicación con el servidor.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Rotación');
            }
        });
    }

    function cerrarModalFotoAdmin() {
        $('#modalFotoAdmin').removeClass('opacity-100').addClass('opacity-0');
        $('#modalContentAdmin').removeClass('scale-100').addClass('scale-95');

        setTimeout(() => {
            $('#modalFotoAdmin').removeClass('flex').addClass('hidden');
            $('#imagenModalAdmin').attr('src', '');
        }, 300);
    }

    $('#modalFotoAdmin').on('click', function (e) {
        if (e.target === this) {
            cerrarModalFotoAdmin();
        }
    });
</script>