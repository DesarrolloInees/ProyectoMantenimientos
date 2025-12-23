<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="w-full max-w-7xl mx-auto">

    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-6 border-b pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-chart-line text-blue-600 mr-2"></i> Reporte Ejecutivo Operativo</h1>
                <p class="text-gray-500 text-sm">Análisis gráfico de mantenimiento y servicios.</p>
            </div>

            <?php if (!empty($datosDia)): ?>
                <a href="<?= BASE_URL ?>generarReporte?inicio=<?= $filtros['fecha_inicio'] ?>&fin=<?= $filtros['fecha_fin'] ?>"
                    target="_blank"
                    class="bg-red-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-red-700 shadow flex items-center gap-2 transform hover:scale-105 transition text-sm">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </a>
            <?php endif; ?>
        </div>

        <form action="<?= BASE_URL ?>reporteEjecutivo" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="hidden md:block"></div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde</label>
                <input type="date" name="fecha_inicio" value="<?= $filtros['fecha_inicio'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta</label>
                <input type="date" name="fecha_fin" value="<?= $filtros['fecha_fin'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700">
            </div>
            <div>
                <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-all">
                    <i class="fas fa-filter mr-2"></i> Filtrar Datos
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm mb-6">
            <div class="flex">
                <div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-yellow-400"></i></div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700"><?= htmlspecialchars($mensaje) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($datosDia)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-500 flex flex-col items-center justify-center">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Servicios</p>
                <p class="text-3xl font-extrabold text-blue-600 mt-1"><?= number_format($totalServicios) ?></p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-indigo-500 flex flex-col items-center justify-center">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider text-center">Promedio Diario x Técnico</p>
                <p class="text-3xl font-extrabold text-indigo-600 mt-1"><?= $mediaDiaria ?></p>
                <span class="text-xs text-gray-400 mt-1">(Global periodo: <?= $mediaGlobal ?>)</span>
            </div>

            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-red-500 flex flex-col items-center justify-center">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider"> Servicios Con Novedad</p>
                <p class="text-3xl font-extrabold text-red-600 mt-1"><?= number_format($datosNovedad['con_novedad'] ?? 0) ?></p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-500 flex flex-col items-center justify-center">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Delegaciones</p>
                <p class="text-3xl font-extrabold text-green-600 mt-1"><?= count($datosDelegacion) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Evolución Diaria de Servicios</h3>
                <div class="relative h-64 w-full">
                    <canvas id="chartDias"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Preventivo vs Correctivo</h3>
                <div class="relative h-64 w-full flex justify-center">
                    <canvas id="chartTipo"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Estado Final del Servicio</h3>
                <div class="relative h-64 w-full flex justify-center">
                    <canvas id="chartEstado"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Top Delegaciones</h3>
                <div class="relative h-64 w-full">
                    <canvas id="chartDelegacion"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 lg:col-span-2">
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Productividad por Técnico (Horas vs Servicios)</h3>
                <div class="relative h-80 w-full">
                    <canvas id="chartTecnico"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 lg:col-span-2">
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Distribución de Repuestos (Origen)</h3>
                <div class="relative h-64 w-full flex justify-center">
                    <canvas id="chartRepuestos"></canvas>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<script>
    // 1. Convertir PHP Arrays a JS Objects
    const dataDias = <?= json_encode($datosDia ?? []) ?>;
    const dataTipo = <?= json_encode($datosTipo ?? []) ?>;
    const dataEstado = <?= json_encode($datosEstado ?? []) ?>;
    const dataDelegacion = <?= json_encode($datosDelegacion ?? []) ?>;
    const dataTecnico = <?= json_encode($datosHoras ?? []) ?>;
    const dataRepuestos = <?= json_encode($datosRepuestos ?? []) ?>;

    // Configuración Global de Fuente para ChartJS
    Chart.defaults.font.family = "'Segoe UI', 'Helvetica', 'Arial', sans-serif";
    Chart.defaults.color = '#4b5563';

    // A. GRÁFICO DIAS (Línea)
    if (document.getElementById('chartDias')) {
        new Chart(document.getElementById('chartDias'), {
            type: 'line',
            data: {
                labels: dataDias.map(i => i.fecha_visita),
                datasets: [{
                    label: 'Servicios',
                    data: dataDias.map(i => i.total),
                    borderColor: '#2563eb', // Blue-600
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2563eb',
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true
            }
        });
    }

    // B. TIPO MANTENIMIENTO (Pie)
    if (document.getElementById('chartTipo')) {
        new Chart(document.getElementById('chartTipo'), {
            type: 'pie',
            data: {
                labels: dataTipo.map(i => i.tipo),
                datasets: [{
                    data: dataTipo.map(i => i.total),
                    backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true
            }
        });
    }

    // C. ESTADO (Doughnut)
    if (document.getElementById('chartEstado')) {
        new Chart(document.getElementById('chartEstado'), {
            type: 'doughnut',
            data: {
                labels: dataEstado.map(i => i.nombre_estado),
                datasets: [{
                    data: dataEstado.map(i => i.total),
                    backgroundColor: ['#10b981', '#ef4444', '#6b7280', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true
            }
        });
    }

    // D. DELEGACIONES (Bar Horizontal)
    if (document.getElementById('chartDelegacion')) {
        new Chart(document.getElementById('chartDelegacion'), {
            type: 'bar',
            data: {
                labels: dataDelegacion.map(i => i.nombre_delegacion),
                datasets: [{
                    label: 'Intervenciones',
                    data: dataDelegacion.map(i => i.total),
                    backgroundColor: '#8b5cf6', // Violet
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // E. TÉCNICOS (Bar Vertical)
    if (document.getElementById('chartTecnico')) {
        new Chart(document.getElementById('chartTecnico'), {
            type: 'bar',
            data: {
                labels: dataTecnico.map(i => i.nombre_tecnico),
                datasets: [{
                    label: 'Servicios',
                    data: dataTecnico.map(i => i.total_servicios),
                    backgroundColor: '#374151', // Gray-700
                    borderRadius: 4,
                    order: 1
                }, {
                    // Si quisieras poner las horas como línea superpuesta (Opcional)
                    label: 'Horas (aprox)',
                    data: dataTecnico.map(i => i.total_horas),
                    type: 'line',
                    borderColor: '#f59e0b',
                    borderWidth: 2,
                    order: 0,
                    yAxisID: 'y1'
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cant. Servicios'
                        }
                    },
                    y1: {
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Horas'
                        }
                    }
                }
            }
        });
    }

    // F. REPUESTOS (Pie)
    if (document.getElementById('chartRepuestos')) {
        // Validación para evitar gráfico vacío
        const labelsRep = dataRepuestos.length ? dataRepuestos.map(i => i.origen) : ['Sin Datos'];
        const valuesRep = dataRepuestos.length ? dataRepuestos.map(i => i.total) : [1];
        const colorsRep = dataRepuestos.length ? ['#f97316', '#0ea5e9'] : ['#e5e7eb'];

        new Chart(document.getElementById('chartRepuestos'), {
            type: 'pie',
            data: {
                labels: labelsRep,
                datasets: [{
                    data: valuesRep,
                    backgroundColor: colorsRep,
                    borderWidth: 0
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true
            }
        });
    }
</script>