<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

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

    table.dataTable thead th {
        border-bottom: 2px solid #e5e7eb !important;
        white-space: nowrap !important;
    }

    table.dataTable tbody td {
        white-space: nowrap !important;
        vertical-align: middle;
    }

    .reloj-fuente {
        font-family: 'Courier New', Courier, monospace;
        font-variant-numeric: tabular-nums;
    }
</style>

<div class="p-4 md:p-6 max-w-full mx-auto space-y-6">

    <!-- Encabezado -->
    <div
        class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-satellite-dish text-green-600"></i> Radar de Técnicos
            </h1>
            <p class="text-gray-500 text-xs md:text-sm mt-1">
                Monitoreo en tiempo real, auditoría de tiempos y servicios modificados.
            </p>
        </div>

        <!-- Botones de Acción Superior -->
        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <!-- BOTÓN A REPORTES Y GRÁFICAS -->
            <a href="index.php?pagina=cronometroReportes"
                class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm font-bold transition">
                <i class="fas fa-chart-bar"></i> Ver Reportes y Gráficas
            </a>

            <!-- BOTÓN ACTUALIZAR RADAR -->
            <button onclick="location.reload();"
                class="bg-blue-50 text-blue-600 p-2.5 rounded-lg hover:bg-blue-100 border border-blue-200 shadow-sm flex items-center gap-2 text-sm font-bold transition"
                title="Actualizar Radar">
                <i class="fas fa-sync-alt"></i> <span class="hidden md:inline">Actualizar</span>
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
            <input type="hidden" name="pagina" value="cronometroAdmin">

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
                <select name="estado_actual"
                    class="w-full border-gray-300 rounded-lg p-2 bg-gray-50 text-sm outline-none focus:border-blue-500">
                    <option value="">- Todos los estados -</option>
                    <option value="En Progreso" <?= ($estado == 'En Progreso') ? 'selected' : '' ?>>En Progreso (En Vivo)
                    </option>
                    <option value="Finalizado" <?= ($estado == 'Finalizado') ? 'selected' : '' ?>>Finalizados</option>
                </select>
            </div>

            <div class="flex flex-wrap gap-2 justify-end mt-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm w-full md:w-auto justify-center">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Tarjetas de Resumen -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div
            class="bg-gradient-to-r from-green-500 to-green-700 rounded-xl p-4 text-white shadow-md flex items-center gap-3">
            <div class="bg-white/20 p-3 rounded-full animate-pulse">
                <i class="fas fa-motorcycle text-2xl"></i>
            </div>
            <div>
                <p class="text-green-100 text-xs font-semibold uppercase">Técnicos Activos</p>
                <h3 class="text-2xl font-bold"><?= $totalActivos ?></h3>
            </div>
        </div>

        <div
            class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-4 text-white shadow-md flex items-center gap-3">
            <div class="bg-white/20 p-3 rounded-full">
                <i class="fas fa-check-double text-2xl"></i>
            </div>
            <div>
                <p class="text-blue-100 text-xs font-semibold uppercase">Servicios Terminados</p>
                <h3 class="text-2xl font-bold"><?= $totalFinalizados ?></h3>
            </div>
        </div>

        <div
            class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl p-4 text-white shadow-md flex items-center gap-3">
            <div class="bg-white/20 p-3 rounded-full">
                <i class="fas fa-exchange-alt text-2xl"></i>
            </div>
            <div>
                <p class="text-amber-100 text-xs font-semibold uppercase">Servicios Modificados</p>
                <h3 class="text-2xl font-bold"><?= $totalModificados ?></h3>
            </div>
        </div>

        <div
            class="bg-gradient-to-r from-red-500 to-rose-700 rounded-xl p-4 text-white shadow-md flex items-center gap-3">
            <div class="bg-white/20 p-3 rounded-full">
                <i class="fas fa-stopwatch text-2xl"></i>
            </div>
            <div>
                <p class="text-red-100 text-xs font-semibold uppercase">Retrasos (Fuera Meta)</p>
                <h3 class="text-2xl font-bold"><?= $totalRetrasados ?></h3>
            </div>
        </div>
    </div>

    <!-- Tabla DataTables -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-2 overflow-x-auto">
            <table id="tablaRadarAdmin" class="display responsive nowrap w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th>Estado / Inicio</th>
                        <th>Técnico</th>
                        <th>Cliente / Punto</th>
                        <th>Servicio</th>
                        <th class="text-center">Tiempo Meta</th>
                        <th class="text-center">T. Real / Cronómetro</th>
                        <th class="text-center">Alertas / Justificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicios as $s): ?>
                        <tr class="border-b hover:bg-gray-50 transition">

                            <!-- Columna: Estado e Inicio -->
                            <td>
                                <?php if ($s['estado_actual'] === 'En Progreso'): ?>
                                    <span
                                        class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full border border-green-200 mb-1 inline-block animate-pulse">
                                        <i class="fas fa-circle text-[8px] mr-1"></i> EN VIVO
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="bg-gray-100 text-gray-700 text-xs font-bold px-2 py-1 rounded-full border border-gray-200 mb-1 inline-block">
                                        FINALIZADO
                                    </span>
                                <?php endif; ?>
                                <br>
                                <span class="text-xs font-mono text-gray-500">
                                    <?= date('d/m/Y H:i', strtotime($s['hora_inicio'])) ?>
                                </span>
                            </td>

                            <!-- Columna: Técnico -->
                            <td class="font-bold text-blue-700">
                                <?= htmlspecialchars($s['nombre_tecnico']) ?>
                            </td>

                            <!-- Columna: Ubicación -->
                            <td>
                                <span class="block text-xs font-bold text-gray-800 truncate w-48">
                                    <?= htmlspecialchars($s['nombre_punto'] ?: 'N/A') ?>
                                </span>
                                <span class="block text-[11px] text-gray-400 truncate w-48">
                                    <?= htmlspecialchars($s['nombre_cliente'] ?: 'Sin cliente') ?>
                                </span>
                            </td>

                            <!-- Columna: Tipo de Servicio -->
                            <td>
                                <span class="text-xs font-bold uppercase bg-blue-50 text-blue-800 px-2 py-1 rounded">
                                    <?= htmlspecialchars($s['tipo_mantenimiento']) ?>
                                </span>
                            </td>

                            <!-- Columna: Meta -->
                            <td class="text-center font-bold text-gray-600">
                                <?= $s['tiempo_estimado_minutos'] ?? 60 ?> min
                            </td>

                            <!-- Columna: Tiempo Real / Cronómetro JavaScript -->
                            <td class="text-center">
                                <?php if ($s['estado_actual'] === 'En Progreso'): ?>
                                    <span
                                        class="reloj-admin-live text-lg font-bold text-green-600 reloj-fuente bg-gray-900 px-2 py-1 rounded shadow-inner"
                                        data-inicio="<?= str_replace('-', '/', $s['hora_inicio']) ?>"
                                        data-meta="<?= $s['tiempo_estimado_minutos'] ?? 60 ?>">
                                        00:00:00
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="text-sm font-bold <?= ($s['duracion_real_minutos'] > $s['tiempo_estimado_minutos']) ? 'text-red-600' : 'text-gray-800' ?>">
                                        <?= $s['duracion_real_minutos'] ?> min
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Columna: Alertas -->
                            <td class="text-center">
                                <?php if ($s['servicio_modificado'] == 1 || !empty($s['justificacion_retraso'])): ?>
                                    <button type="button" onclick='abrirModalDetalles(<?= json_encode($s) ?>)'
                                        class="bg-red-50 hover:bg-red-100 text-red-600 p-2 rounded-lg border border-red-200 transition font-bold text-xs inline-flex items-center gap-1">
                                        <i class="fas fa-exclamation-triangle"></i> Ver Alertas
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-300 text-xs"><i class="fas fa-check"></i> Normal</span>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detalles / Alertas -->
<div id="modalDetalles"
    class="fixed inset-0 bg-black/80 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300"
        id="modalContentDetalles">

        <div class="bg-red-600 text-white p-4 flex justify-between items-center">
            <h3 class="font-bold text-base"><i class="fas fa-exclamation-triangle mr-2"></i> Detalles de Alerta</h3>
            <button type="button" onclick="cerrarModalDetalles()"
                class="text-white hover:text-red-200 text-2xl leading-none">&times;</button>
        </div>

        <div class="p-5 space-y-4">
            <div id="alertaModificado" class="hidden bg-orange-50 border border-orange-200 p-3 rounded-lg">
                <h4 class="text-xs font-bold text-orange-800 uppercase mb-1">Servicio Modificado</h4>
                <p class="text-sm text-orange-900">El técnico inició con un servicio distinto al que reportó al
                    finalizar o lo cambió en vivo.</p>
            </div>

            <div id="alertaRetraso" class="hidden">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Justificación por Retraso:</h4>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700 italic font-medium shadow-inner"
                    id="txtJustificacion"></div>
            </div>

            <div class="pt-4 text-right">
                <button type="button" onclick="cerrarModalDetalles()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-lg text-sm transition">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.select2-admin').select2({
            width: '100%',
            language: { noResults: function () { return "No se encontraron resultados"; } }
        });

        $('#tablaRadarAdmin').DataTable({
            responsive: true,
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            order: [[0, 'desc']], // Ordena priorizando los "EN VIVO"
            pageLength: 25
        });

        // INICIAR LOS RELOJES EN LA TABLA DEL ADMIN
        setInterval(actualizarRelojesAdmin, 1000);
    });

    function actualizarRelojesAdmin() {
        $('.reloj-admin-live').each(function () {
            const fechaString = $(this).attr('data-inicio');
            if (!fechaString) return;

            const inicio = new Date(fechaString).getTime();
            const metaMinutos = parseInt($(this).attr('data-meta'));
            const ahora = new Date().getTime();
            const diferencia = ahora - inicio;

            let horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
            let segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

            // Alerta visual de retraso en vivo para el supervisor
            const minutosTranscurridos = Math.floor(diferencia / (1000 * 60));
            if (minutosTranscurridos > metaMinutos) {
                $(this).removeClass('text-green-600').addClass('text-red-500 animate-pulse');
            }

            horas = (horas < 10) ? "0" + horas : horas;
            minutos = (minutos < 10) ? "0" + minutos : minutos;
            segundos = (segundos < 10) ? "0" + segundos : segundos;

            $(this).text(horas + ":" + minutos + ":" + segundos);
        });
    }

    function abrirModalDetalles(data) {
        if (data.servicio_modificado == 1) {
            $('#alertaModificado').removeClass('hidden');
        } else {
            $('#alertaModificado').addClass('hidden');
        }

        if (data.justificacion_retraso) {
            $('#alertaRetraso').removeClass('hidden');
            $('#txtJustificacion').text('"' + data.justificacion_retraso + '"');
        } else {
            $('#alertaRetraso').addClass('hidden');
            $('#txtJustificacion').text('');
        }

        $('#modalDetalles').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalDetalles').removeClass('opacity-0').addClass('opacity-100');
            $('#modalContentDetalles').removeClass('scale-95').addClass('scale-100');
        }, 10);
    }

    function cerrarModalDetalles() {
        $('#modalDetalles').removeClass('opacity-100').addClass('opacity-0');
        $('#modalContentDetalles').removeClass('scale-100').addClass('scale-95');

        setTimeout(() => {
            $('#modalDetalles').removeClass('flex').addClass('hidden');
        }, 300);
    }
</script>