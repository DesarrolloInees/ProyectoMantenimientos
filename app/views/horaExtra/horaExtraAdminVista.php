<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<!-- DATATABLES + RESPONSIVE -->
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

    <!-- Encabezado -->
    <div class="flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                <i class="fas fa-user-clock text-blue-600 mr-2"></i>Aprobación de Horas Extra
            </h1>
            <p class="text-gray-500 text-xs md:text-sm mt-1">
                Auditoría, revisión de evidencias y corte para nómina de horas adicionales.
            </p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
            <input type="hidden" name="pagina" value="horaExtraAdmin">

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
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado</label>
                <select name="id_estado"
                    class="w-full border-gray-300 rounded-lg p-2 bg-gray-50 text-sm outline-none focus:border-blue-500">
                    <option value="">- Todos los estados -</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= $est['id_estado'] ?>" <?= ($idEstado == $est['id_estado']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($est['nombre_estado']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Modifica el bloque de botones dentro del formulario de filtros de horasExtraAdminVista.php -->
            <div class="flex flex-wrap gap-2 sm:col-span-2 md:col-span-5 justify-end mt-2 border-t pt-3">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm">
                    <i class="fas fa-search"></i> Filtrar
                </button>

                <a href="index.php?pagina=horasExtraAdmin"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-1 text-sm"
                    title="Limpiar Filtros">
                    <i class="fas fa-undo"></i>
                </a>

                <!-- Botón PDF (Browsershot) -->
                <button type="button" onclick="generarReportePDF()"
                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm">
                    <i class="fas fa-file-pdf"></i> Reporte PDF
                </button>

                <!-- Botón Excel (SheetJS) -->
                <button type="button" onclick="exportarExcelFormato()"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm">
                    <i class="fas fa-file-excel"></i> Formato Excel
                </button>
            </div>
        </form>
    </div>

    <!-- Tarjetas de Resumen -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div
            class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-4 text-white shadow-md flex items-center gap-3">
            <div class="bg-white/20 p-3 rounded-full">
                <i class="fas fa-history text-2xl"></i>
            </div>
            <div>
                <p class="text-blue-100 text-xs font-semibold uppercase">Total Reportadas</p>
                <h3 class="text-2xl font-bold"><?= number_format($totalHoras, 2) ?> <span
                        class="text-sm font-normal">hrs</span></h3>
            </div>
        </div>

        <div
            class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-4 text-white shadow-md flex items-center gap-3">
            <div class="bg-white/20 p-3 rounded-full">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-emerald-100 text-xs font-semibold uppercase">Total Aprobadas</p>
                <h3 class="text-2xl font-bold"><?= number_format($totalAprobadas, 2) ?> <span
                        class="text-sm font-normal">hrs</span></h3>
            </div>
        </div>

        <div
            class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl p-4 text-white shadow-md flex items-center gap-3">
            <div class="bg-white/20 p-3 rounded-full">
                <i class="fas fa-hourglass-half text-2xl"></i>
            </div>
            <div>
                <p class="text-amber-100 text-xs font-semibold uppercase">Pendientes</p>
                <h3 class="text-2xl font-bold"><?= $totalPendientes ?></h3>
            </div>
        </div>

        <div
            class="bg-gradient-to-r from-rose-500 to-red-600 rounded-xl p-4 text-white shadow-md flex items-center gap-3">
            <div class="bg-white/20 p-3 rounded-full">
                <i class="fas fa-times-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-rose-100 text-xs font-semibold uppercase">Rechazadas</p>
                <h3 class="text-2xl font-bold"><?= $totalRechazadas ?></h3>
            </div>
        </div>
    </div>

    <!-- Tabla DataTables -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-2 overflow-x-auto">
            <table id="tablaHorasExtraAdmin" class="display responsive nowrap w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th>Fecha</th>
                        <th>Técnico</th>
                        <th>Cliente / Punto</th>
                        <th>Horario</th>
                        <th class="text-center">Total Horas</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportes as $r): ?>
                        <?php
                        $badgeColor = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                        if ($r['estado_nombre'] === 'Aprobada') {
                            $badgeColor = 'bg-green-100 text-green-800 border-green-200';
                        } elseif ($r['estado_nombre'] === 'Rechazada') {
                            $badgeColor = 'bg-red-100 text-red-800 border-red-200';
                        }

                        $fotos = !empty($r['rutas_fotos']) ? explode('||', $r['rutas_fotos']) : [];
                        ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="font-medium text-gray-900">
                                <?= date('d/m/Y', strtotime($r['fecha_reporte'])) ?>
                            </td>
                            <td class="font-semibold text-blue-700">
                                <?= htmlspecialchars($r['nombre_tecnico']) ?>
                            </td>
                            <td>
                                <span class="block text-xs font-bold text-gray-700">
                                    <?= htmlspecialchars($r['nombre_punto'] ?: 'N/A') ?>
                                </span>
                                <span class="block text-[11px] text-gray-400">
                                    <?= htmlspecialchars($r['nombre_cliente'] ?: 'Sin cliente') ?>
                                </span>
                            </td>
                            <td>
                                <span
                                    class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono border border-gray-200">
                                    <?= date('H:i', strtotime($r['hora_inicio'])) ?> -
                                    <?= date('H:i', strtotime($r['hora_fin'])) ?>
                                </span>
                            </td>
                            <td class="text-center font-bold text-gray-800">
                                <?= number_format($r['total_horas'], 2) ?> hrs
                            </td>
                            <td class="text-center">
                                <span
                                    class="px-2.5 py-1 rounded-full border text-xs font-bold uppercase <?= $badgeColor ?>">
                                    <?= htmlspecialchars($r['estado_nombre']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button"
                                    onclick='abrirModalAuditoria(<?= json_encode($r) ?>, <?= json_encode($fotos) ?>)'
                                    class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 p-2 px-3 rounded-lg font-bold text-xs transition inline-flex items-center gap-1.5">
                                    <i class="fas fa-search"></i> Auditar / Ver
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Auditoría / Estado -->
<div id="modalAuditoria"
    class="fixed inset-0 bg-black/80 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300"
        id="modalContentAuditoria">

        <!-- Modal Header -->
        <div class="bg-blue-800 text-white p-4 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-base" id="modalTituloTecnico">Auditoría de Horas Extra</h3>
                <p class="text-blue-200 text-xs" id="modalSubtituloFecha"></p>
            </div>
            <button type="button" onclick="cerrarModalAuditoria()"
                class="text-white hover:text-red-300 text-2xl leading-none">&times;</button>
        </div>

        <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">

            <!-- Datos del Reporte -->
            <div class="grid grid-cols-2 gap-3 text-xs bg-gray-50 p-3 rounded-lg border border-gray-200">
                <div>
                    <span class="block text-gray-400 font-bold uppercase text-[10px]">Cliente / Punto:</span>
                    <strong class="text-gray-800" id="modalPuntoCliente"></strong>
                </div>
                <div>
                    <span class="block text-gray-400 font-bold uppercase text-[10px]">Horario y Total:</span>
                    <strong class="text-blue-700" id="modalHorarioTotal"></strong>
                </div>
            </div>

            <!-- Justificación del Técnico -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Justificación del Técnico:</label>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 italic"
                    id="modalJustificacion"></div>
            </div>

            <!-- Evidencias Fotográficas -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Evidencias Adjuntas:</label>
                <div id="modalFotosContainer" class="flex flex-wrap gap-2 justify-start"></div>
            </div>

            <hr class="border-gray-200">

            <!-- Formulario para Cambiar Estado -->
            <form id="formCambiarEstado" class="space-y-3">
                <input type="hidden" id="modal_id_registro" name="id_registro">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Acción de Auditoría <span
                            class="text-red-500">*</span></label>
                    <select id="modal_id_estado" name="id_estado" required
                        class="w-full bg-white border border-gray-300 rounded-lg p-2.5 text-sm font-bold shadow-sm outline-none focus:border-blue-500">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= $e['id_estado'] ?>"><?= htmlspecialchars($e['nombre_estado']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Observación / Nota del
                        Supervisor:</label>
                    <textarea id="modal_observacion" name="observacion" rows="2"
                        placeholder="Escribe un motivo si rechazas o apruebas con novedades..."
                        class="w-full bg-white border border-gray-300 rounded-lg p-2.5 text-sm shadow-sm outline-none focus:border-blue-500"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="cerrarModalAuditoria()"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2 rounded-lg text-sm transition">
                        Cancelar
                    </button>
                    <button type="button" onclick="guardarAuditoria()"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2 rounded-lg text-sm shadow transition flex items-center gap-1.5">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
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

        $('#tablaHorasExtraAdmin').DataTable({
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
        });
    });

    function abrirModalAuditoria(data, fotosArray) {
        $('#modal_id_registro').val(data.id_registro_he);
        $('#modalTituloTecnico').text('Técnico: ' + data.nombre_tecnico);
        $('#modalSubtituloFecha').text('Fecha Reporte: ' + data.fecha_reporte);

        const clienteText = data.nombre_cliente ? data.nombre_cliente : 'Sin cliente';
        const puntoText = data.nombre_punto ? data.nombre_punto : 'Sin punto';
        $('#modalPuntoCliente').text(puntoText + ' (' + clienteText + ')');

        $('#modalHorarioTotal').text(data.hora_inicio + ' - ' + data.hora_fin + ' (' + data.total_horas + ' hrs)');
        $('#modalJustificacion').text('"' + data.justificacion_tecnico + '"');

        $('#modal_id_estado').val(data.id_estado_aprobacion);
        $('#modal_observacion').val(data.observacion_supervisor || '');

        // Cargar fotos
        const containerFotos = $('#modalFotosContainer');
        containerFotos.empty();

        if (fotosArray && fotosArray.length > 0) {
            fotosArray.forEach(function (ruta) {
                const urlCompleta = window.BASE_URL + ruta;
                containerFotos.append(`
                    <a href="${urlCompleta}" target="_blank" class="block w-24 h-24 rounded-lg overflow-hidden border border-gray-300 hover:opacity-80 transition">
                        <img src="${urlCompleta}" class="w-full h-full object-cover" alt="Evidencia">
                    </a>
                `);
            });
        } else {
            containerFotos.html('<span class="text-xs text-gray-400 italic">No se adjuntaron fotos en este reporte.</span>');
        }

        $('#modalAuditoria').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalAuditoria').removeClass('opacity-0').addClass('opacity-100');
            $('#modalContentAuditoria').removeClass('scale-95').addClass('scale-100');
        }, 10);
    }

    function cerrarModalAuditoria() {
        $('#modalAuditoria').removeClass('opacity-100').addClass('opacity-0');
        $('#modalContentAuditoria').removeClass('scale-100').addClass('scale-95');

        setTimeout(() => {
            $('#modalAuditoria').removeClass('flex').addClass('hidden');
        }, 300);
    }

    function guardarAuditoria() {
        const idRegistro = $('#modal_id_registro').val();
        const idEstado = $('#modal_id_estado').val();
        const observacion = $('#modal_observacion').val();

        $.ajax({
            url: 'index.php?pagina=horaExtraAdmin&accion=ajaxCambiarEstado',
            type: 'POST',
            data: {
                id_registro: idRegistro,
                id_estado: idEstado,
                observacion: observacion
            },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    alert('✅ ' + res.msj);
                    location.reload();
                } else {
                    alert('❌ ' + res.msj);
                }
            },
            error: function () {
                alert('❌ Error al procesar la solicitud.');
            }
        });
    }

    $('#modalAuditoria').on('click', function (e) {
        if (e.target === this) {
            cerrarModalAuditoria();
        }
    });
</script>

<script>
    // Disparar PDF manteniendo los parámetros del filtro actual
    function generarReportePDF() {
        const fechaIni = $('input[name="fecha_inicio"]').val();
        const fechaFin = $('input[name="fecha_fin"]').val();
        const tec = $('select[name="id_tecnico"]').val();
        const est = $('select[name="id_estado"]').val();

        const url = `index.php?pagina=horaExtraGenerar&accion=generar&fecha_inicio=${fechaIni}&fecha_fin=${fechaFin}&id_tecnico=${tec}&id_estado=${est}`;
        window.open(url, '_blank');
    }

    // Redirección para descargar el archivo Excel formato INEES
    function exportarExcelFormato() {
        const fechaIni = $('input[name="fecha_inicio"]').val();
        const fechaFin = $('input[name="fecha_fin"]').val();
        const tec = $('select[name="id_tecnico"]').val();
        const est = $('select[name="id_estado"]').val();

        const url = `index.php?pagina=horaExtraExcel&accion=generar&fecha_inicio=${fechaIni}&fecha_fin=${fechaFin}&id_tecnico=${tec}&id_estado=${est}`;
        window.location.href = url;
    }




</script>