<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body {
        background-color: #f8fafc;
    }
</style>

<div class="p-4 md:p-6 max-w-full mx-auto space-y-6">

    <!-- En el encabezado de cronometroReportesVista.php -->
    <div class="flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-indigo-600"></i> Rendimiento y Cumplimiento de Flota
            </h1>
            <p class="text-gray-500 text-xs md:text-sm mt-1">
                Comparativa de servicios completados frente a la meta del periodo calculada.
            </p>
        </div>
        <a href="index.php?pagina=cronometroAdmin" 
            class="bg-gray-100 text-gray-700 hover:bg-gray-200 p-2.5 px-4 rounded-lg border border-gray-300 shadow-sm flex items-center gap-2 text-sm font-bold transition">
            <i class="fas fa-arrow-left"></i> Volver al Radar
        </a>
    </div>

    <!-- Filtros de Fecha -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="pagina" value="cronometroReportes">

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>"
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>"
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Meta de Servicios (por
                    técnico)</label>
                <input type="number" name="meta_total" id="inputMetaTotal" min="1" step="1"
                    value="<?= (int) $metaTotalServicios ?>"
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-indigo-500 font-bold text-indigo-700">
                <p class="text-[10px] text-gray-400 mt-1">Sugerida automáticamente según el rango (6 L-V / 3 Sáb).
                    Puedes editarla.</p>
            </div>

            <div
                class="flex flex-wrap gap-2 sm:col-span-2 md:col-span-4 justify-end border-t pt-4 mt-2 border-gray-100">
                <button type="button" onclick="setPreset('hoy')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3 rounded-lg border border-gray-300 transition">
                    Hoy
                </button>
                <button type="button" onclick="setPreset('semana')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3 rounded-lg border border-gray-300 transition">
                    Esta Semana
                </button>
                <button type="button" onclick="setPreset('mes')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3 rounded-lg border border-gray-300 transition">
                    Este Mes
                </button>

                <!-- Botón Filtrar -->
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm ml-2">
                    <i class="fas fa-filter"></i> Generar
                </button>

                <!-- Botón PDF -->
                <button type="button" onclick="exportarRendimientoPdf()"
                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>

                <!-- Botón Excel -->
                <button type="button" onclick="exportarRendimientoExcel()"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 text-sm">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
            </div>
        </form>
    </div>

    <!-- Tarjetas de Resumen KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
            <div class="bg-indigo-50 text-indigo-600 p-3 rounded-full">
                <i class="fas fa-bullseye text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-400 text-xs font-semibold uppercase">Meta Individual Periodo</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= $metaTotalServicios ?> <span
                        class="text-xs font-normal text-gray-500">servicios</span></h3>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
            <div class="bg-emerald-50 text-emerald-600 p-3 rounded-full">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-400 text-xs font-semibold uppercase">Total Servicios Flota</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= $totalGeneralServicios ?></h3>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
            <div class="bg-blue-50 text-blue-600 p-3 rounded-full">
                <i class="fas fa-user-check text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-400 text-xs font-semibold uppercase">Técnicos Cumplieron</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= $tecnicosCumplieron ?> / <?= count($rendimientoRaw) ?>
                </h3>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
            <div class="bg-amber-50 text-amber-600 p-3 rounded-full">
                <i class="fas fa-calendar-day text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-400 text-xs font-semibold uppercase">Meta Diaria Básica</p>
                <h3 class="text-2xl font-bold text-gray-800">6 <span class="text-xs font-normal text-gray-500">L-V / 3
                        Sáb</span></h3>
            </div>
        </div>
    </div>

    <!-- Gráfica de Barras -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="font-bold text-gray-800 text-base">Comparativa de Cumplimiento por Técnico</h2>
            <span class="text-xs font-semibold text-gray-500">Línea Punteada: Meta del Periodo
                (<?= $metaTotalServicios ?>)</span>
        </div>

        <div class="relative w-full h-80 md:h-96">
            <canvas id="graficaCumplimiento"></canvas>
        </div>
    </div>

    <!-- Tabla Detallada -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800 text-base">Detalle Cuantitativo</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="p-3">Técnico</th>
                        <?php foreach ($tipos as $tipo): ?>
                            <th class="p-3 text-center"><?= htmlspecialchars($tipo['nombre_completo']) ?></th>
                        <?php endforeach; ?>
                        <th class="p-3 text-center">Finalizados</th>
                        <th class="p-3 text-center">En Progreso</th>
                        <th class="p-3 text-center">Meta Periodo</th>
                        <th class="p-3 text-center">% Cumplimiento</th>
                        <th class="p-3 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rendimientoRaw as $r): ?>
                        <?php
                        $finalizados = (int) $r['total_finalizados'];
                        $pct = ($metaTotalServicios > 0) ? round(($finalizados / $metaTotalServicios) * 100, 1) : 0;
                        $badge = 'bg-red-100 text-red-800 border-red-200';
                        $estadoTxt = 'Bajo Rendimiento';
                        if ($pct >= 100) {
                            $badge = 'bg-green-100 text-green-800 border-green-200';
                            $estadoTxt = 'Meta Cumplida';
                        } elseif ($pct >= 70) {
                            $badge = 'bg-amber-100 text-amber-800 border-amber-200';
                            $estadoTxt = 'En Progreso / Aceptable';
                        }
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-bold text-gray-800"><?= htmlspecialchars($r['nombre_tecnico']) ?></td>
                            <?php foreach ($tipos as $tipo): ?>
                                <?php $alias = 'tipo_' . $tipo['id_tipo_mantenimiento']; ?>
                                <td class="p-3 text-center font-semibold text-blue-600">
                                    <?= isset($r[$alias]) ? (int) $r[$alias] : 0 ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="p-3 text-center font-semibold text-emerald-600"><?= $finalizados ?></td>
                            <td class="p-3 text-center text-amber-600 font-semibold"><?= (int) $r['total_en_progreso'] ?>
                            </td>
                            <td class="p-3 text-center text-gray-600 font-medium"><?= $metaTotalServicios ?></td>
                            <td class="p-3 text-center font-bold text-gray-800"><?= $pct ?>%</td>
                            <td class="p-3 text-center">
                                <span class="px-2.5 py-1 rounded-full border text-xs font-bold uppercase <?= $badge ?>">
                                    <?= $estadoTxt ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    // Inicialización de Chart.js
    const ctx = document.getElementById('graficaCumplimiento').getContext('2d');

    const nombres = <?= json_encode($tecnicosNombres) ?>;
    const realizados = <?= json_encode($serviciosRealizados) ?>;
    const colores = <?= json_encode($coloresBarras) ?>;
    const metaRango = <?= $metaTotalServicios ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: nombres,
            datasets: [{
                label: 'Servicios Finalizados',
                data: realizados,
                backgroundColor: colores,
                borderRadius: 6,
                maxBarThickness: 45
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let valor = context.raw;
                            let pct = (metaRango > 0) ? ((valor / metaRango) * 100).toFixed(1) : 0;
                            return `Servicios: ${valor} (${pct}% de la meta)`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    title: { display: true, text: 'Cantidad de Servicios' }
                },
                x: {
                    ticks: { font: { weight: 'bold' } }
                }
            }
        }
    });

    // Helper para botones rápidos de rango
    function setPreset(tipo) {
        const hoy = new Date();
        let inicio = new Date();
        let fin = new Date();

        if (tipo === 'hoy') {
            // inicio = hoy, fin = hoy
        } else if (tipo === 'semana') {
            const day = hoy.getDay();
            const diff = hoy.getDate() - day + (day === 0 ? -6 : 1); // Ajustar a lunes
            inicio = new Date(hoy.setDate(diff));
            fin = new Date();
        } else if (tipo === 'mes') {
            inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            fin = new Date();
        }

        const formatDate = (date) => date.toISOString().split('T')[0];

        $('input[name="fecha_inicio"]').val(formatDate(inicio));
        $('input[name="fecha_fin"]').val(formatDate(fin));
    }



    // Exportar a PDF
    function exportarRendimientoPdf() {
        const fechaIni = $('input[name="fecha_inicio"]').val();
        const fechaFin = $('input[name="fecha_fin"]').val();
        const meta = $('#inputMetaTotal').val();

        const url = `index.php?pagina=cronometroPdf&accion=generar&fecha_inicio=${fechaIni}&fecha_fin=${fechaFin}&meta_total=${meta}`;
        window.open(url, '_blank');
    }

    // Exportar a Excel
    function exportarRendimientoExcel() {
        const fechaIni = $('input[name="fecha_inicio"]').val();
        const fechaFin = $('input[name="fecha_fin"]').val();
        const meta = $('#inputMetaTotal').val();

        const url = `index.php?pagina=cronometroExcel&accion=generar&fecha_inicio=${fechaIni}&fecha_fin=${fechaFin}&meta_total=${meta}`;
        window.location.href = url;
    }
</script>