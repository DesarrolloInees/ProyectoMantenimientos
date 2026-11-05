<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="w-full px-4 md:px-6">

    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100 mb-8">
        <div class="mb-6 border-b pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-chart-line text-blue-600 mr-2"></i> Reporte Ejecutivo Operativo</h1>
                <p class="text-gray-500 text-sm">Dashboard sincronizado con Reporte PDF (Fallidos & KPIs).</p>
            </div>

            <?php if (!empty($datosDia)): ?>
                <a href="<?= BASE_URL ?>generarReporte?accion=configurar&inicio=<?= $filtros['fecha_inicio'] ?>&fin=<?= $filtros['fecha_fin'] ?>"
                    class="bg-gray-800 text-white px-5 py-2 rounded-lg font-bold hover:bg-gray-900 shadow flex items-center gap-2 transform hover:scale-105 transition text-sm">
                    <i class="fas fa-file-pdf"></i> Generar PDF
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
                    <i class="fas fa-filter mr-2"></i> Actualizar
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm mb-6">
            <p class="text-sm text-yellow-700"><i class="fas fa-info-circle mr-1"></i> <?= htmlspecialchars($mensaje) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($datosDia)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-500 flex flex-col items-center justify-center">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Servicios</p>
                <p class="text-3xl font-extrabold text-blue-600 mt-1"><?= number_format($totalServicios) ?></p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-emerald-500 flex flex-col items-center justify-center">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Servicios Esperados (Meta)</p>
                <p class="text-3xl font-extrabold text-emerald-600 mt-1"><?= number_format($metaServicios['esperados'] ?? 0) ?></p>
                <p class="text-[10px] text-gray-500 mt-1">
                    Realizados: <span class="font-bold text-gray-700"><?= number_format($totalServicios) ?></span>
                    · Cumplimiento: <span class="font-bold <?= ($cumplimientoMeta >= 100) ? 'text-emerald-600' : (($cumplimientoMeta >= 80) ? 'text-amber-600' : 'text-rose-600') ?>"><?= number_format($cumplimientoMeta, 1) ?>%</span>
                </p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-red-500 flex flex-col items-center justify-center">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider"> Servicios Con Novedad</p>
                <p class="text-3xl font-extrabold text-red-600 mt-1"><?= number_format($datosNovedad['con_novedad'] ?? 0) ?></p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow border-l-4 border-rose-600 flex flex-col items-center justify-center">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Puntos Críticos (>2 Fallos)</p>
                <p class="text-3xl font-extrabold text-rose-600 mt-1"><?= count($datosPuntosCriticos) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Evolución Diaria de Servicios</h3>
                <div class="relative h-64 w-full">
                    <canvas id="chartDias"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-rose-100">
                <div class="flex justify-between items-center mb-4 border-b border-rose-100 pb-2">
                    <h3 class="text-rose-700 font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Top Puntos Críticos</h3>
                    <span class="text-[10px] bg-rose-100 text-rose-600 px-2 py-1 rounded-full font-bold">Tipo: Fallido (> 2)</span>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="chartPuntosCriticos"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Distribución por Tipo de Servicio</h3>
                <div class="relative h-64 w-full flex justify-center">
                    <canvas id="chartTipo"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Participación por Delegación</h3>
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
                <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Gestión de Repuestos (Origen)</h3>
                <div class="relative h-64 w-full flex justify-center">
                    <canvas id="chartRepuestos"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 lg:col-span-2">
                <div class="flex justify-between items-start mb-4 border-b pb-2 gap-3">
                    <div>
                        <h3 class="text-gray-700 font-bold"><i class="fas fa-tags text-indigo-500 mr-1"></i> Principales Novedades por Delegación</h3>
                        <p class="text-[11px] text-gray-400">Top 3 de novedades en servicios no fallidos</p>
                    </div>
                    <span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded-full font-bold whitespace-nowrap">Sin fallidos</span>
                </div>
                <div class="relative h-96 w-full">
                    <canvas id="chartNovedadesDelegacion"></canvas>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-amber-100 lg:col-span-2">
                <div class="flex justify-between items-start mb-4 border-b border-amber-100 pb-2 gap-3">
                    <div>
                        <h3 class="text-amber-700 font-bold"><i class="fas fa-users text-amber-500 mr-1"></i> Clientes con Más Novedades por Delegación</h3>
                        <p class="text-[11px] text-gray-400">Top 3 de clientes por delegación y sus 3 novedades más frecuentes</p>
                    </div>
                    <span class="text-[10px] bg-amber-50 text-amber-600 px-2 py-1 rounded-full font-bold whitespace-nowrap">Top 3</span>
                </div>

                <?php if (empty($clientesNovedades)): ?>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-8 text-center">
                        <p class="text-sm font-bold text-emerald-700">No hay novedades registradas por cliente en el período.</p>
                    </div>
                <?php else: ?>
                    <div class="relative h-[520px] w-full mb-6">
                        <canvas id="chartClientesNovedades"></canvas>
                    </div>

                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-amber-50 text-amber-700 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider w-36">Delegación</th>
                                    <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider">Cliente</th>
                                    <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider text-center w-24">Servicios</th>
                                    <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider">Top 3 novedades</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($clientesNovedades as $delegacion => $clientes): ?>
                                    <?php foreach ($clientes as $indice => $cliente): ?>
                                        <tr class="hover:bg-amber-50/40">
                                            <?php if ($indice === 0): ?>
                                                <td class="px-3 py-2 align-top font-bold text-gray-700" rowspan="<?= count($clientes) ?>"><?= htmlspecialchars($delegacion) ?></td>
                                            <?php endif; ?>
                                            <td class="px-3 py-2 align-top font-semibold text-gray-800"><?= htmlspecialchars($cliente['cliente']) ?></td>
                                            <td class="px-3 py-2 align-top text-center">
                                                <span class="inline-flex items-center justify-center rounded-full bg-amber-600 px-2.5 py-0.5 text-xs font-bold text-white"><?= (int) $cliente['total'] ?></span>
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <div class="flex flex-wrap gap-1.5">
                                                    <?php foreach ($cliente['novedades'] as $nov): ?>
                                                        <span class="inline-flex items-center gap-1 rounded-full border border-amber-100 bg-amber-50 px-2 py-1 text-[11px] text-amber-800">
                                                            <span><?= htmlspecialchars($nov['nombre']) ?></span>
                                                            <span class="rounded-full bg-amber-600 px-1.5 py-0.5 text-[10px] font-bold text-white"><?= (int) $nov['total'] ?></span>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-md border border-rose-100 lg:col-span-2">
                <div class="flex justify-between items-start mb-4 border-b border-rose-100 pb-2 gap-3">
                    <div>
                        <h3 class="text-rose-700 font-bold"><i class="fas fa-exclamation-circle mr-1"></i> Novedades en Servicios Fallidos</h3>
                        <p class="text-[11px] text-gray-400">Top 6 de novedades por delegación en servicios fallidos</p>
                    </div>
                    <span class="text-[10px] bg-rose-50 text-rose-600 px-2 py-1 rounded-full font-bold whitespace-nowrap">Solo fallidos</span>
                </div>

                <?php if (empty($datosNovedadesFallidasPorDelegacion)): ?>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-8 text-center">
                        <p class="text-sm font-bold text-emerald-700">No hay novedades registradas en servicios fallidos para el período.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-rose-50 text-rose-700 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider w-40">Delegación</th>
                                    <th class="px-3 py-2 text-xs font-bold uppercase tracking-wider">Principales novedades</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($datosNovedadesFallidasPorDelegacion as $delegacion => $items): ?>
                                    <tr class="hover:bg-rose-50/40">
                                        <td class="px-3 py-2 align-top font-bold text-gray-700"><?= htmlspecialchars($delegacion) ?></td>
                                        <td class="px-3 py-2">
                                            <div class="flex flex-wrap gap-1.5">
                                                <?php foreach ($items as $item): ?>
                                                    <span class="inline-flex items-center gap-1 rounded-full border border-rose-100 bg-rose-50 px-2 py-1 text-[11px] text-rose-700">
                                                        <span><?= htmlspecialchars($item['nombre']) ?></span>
                                                        <span class="rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white"><?= (int) $item['total'] ?></span>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    <?php endif; ?>

</div>

<script>
    // DATOS DESDE PHP
    const dataDias = <?= json_encode($datosDia ?? []) ?>;
    const dataTipo = <?= json_encode($datosTipo ?? []) ?>;
    
    // AQUI: Usamos los datos de puntos críticos filtrados (> 2 fallidos)
    // Limitamos a Top 10 para que la gráfica no explote si hay muchos
    const dataPuntosRaw = <?= json_encode($datosPuntosCriticos ?? []) ?>;
    const dataPuntosCriticos = dataPuntosRaw.slice(0, 10); 

    const dataDelegacion = <?= json_encode($datosDelegacion ?? []) ?>;
    const dataTecnico = <?= json_encode($datosHoras ?? []) ?>;
    const dataRepuestos = <?= json_encode($datosRepuestos ?? []) ?>;
    const dataNovedadesDelegacion = <?= json_encode($datosNovedadesPorDelegacion ?? []) ?>;
    const topNovedadesGlobales = <?= json_encode($topNovedadesGlobales ?? []) ?>;
    const dataClientesNovedades = <?= json_encode($clientesNovedades ?? []) ?>;

    // Configuración Global
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.color = '#64748b';

    // A. DIAS (Línea)
    if (document.getElementById('chartDias')) {
        new Chart(document.getElementById('chartDias'), {
            type: 'line',
            data: {
                labels: dataDias.map(i => i.fecha_visita),
                datasets: [{
                    label: 'Servicios',
                    data: dataDias.map(i => i.total),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 3
                }]
            },
            options: { maintainAspectRatio: false, responsive: true }
        });
    }

    // B. PUNTOS CRÍTICOS (Barra Horizontal - Reemplaza al Doughnut de Estado)
    if (document.getElementById('chartPuntosCriticos')) {
        if(dataPuntosCriticos.length > 0){
            new Chart(document.getElementById('chartPuntosCriticos'), {
                type: 'bar',
                data: {
                    labels: dataPuntosCriticos.map(i => i.nombre_punto),
                    datasets: [{
                        label: 'Cant. Fallidos',
                        data: dataPuntosCriticos.map(i => i.total_fallidos),
                        backgroundColor: '#f43f5e', // Rose-500
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y', // Barra Horizontal
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: { beginAtZero: true, grid: { display: true } },
                        y: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        } else {
            // Mensaje si no hay datos
            const ctx = document.getElementById('chartPuntosCriticos').getContext('2d');
            ctx.font = "14px Arial";
            ctx.fillStyle = "#9ca3af";
            ctx.textAlign = "center";
            ctx.fillText("¡Excelente! No hay puntos críticos (> 2 fallos)", ctx.canvas.width/2, ctx.canvas.height/2);
        }
    }

    // C. TIPO SERVICIO (Pie)
    if (document.getElementById('chartTipo')) {
        new Chart(document.getElementById('chartTipo'), {
            type: 'doughnut',
            data: {
                labels: dataTipo.map(i => i.tipo),
                datasets: [{
                    data: dataTipo.map(i => i.total),
                    backgroundColor: ['#ef4444', '#fff000','#f59e0b', '#3b82f6','#10b981' ],
                    borderWidth: 0
                }]
            },
            options: { maintainAspectRatio: false, responsive: true }
        });
    }

    // D. DELEGACIONES (Bar)
    if (document.getElementById('chartDelegacion')) {
        new Chart(document.getElementById('chartDelegacion'), {
            type: 'bar',
            data: {
                labels: dataDelegacion.map(i => i.nombre_delegacion),
                datasets: [{
                    label: 'Intervenciones',
                    data: dataDelegacion.map(i => i.total),
                    backgroundColor: '#8b5cf6',
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // E. TECNICOS
    if (document.getElementById('chartTecnico')) {
        new Chart(document.getElementById('chartTecnico'), {
            type: 'bar',
            data: {
                labels: dataTecnico.map(i => i.nombre_tecnico),
                datasets: [{
                    label: 'Servicios',
                    data: dataTecnico.map(i => i.total_servicios),
                    backgroundColor: '#334155',
                    borderRadius: 4,
                    order: 2
                }, {
                    label: 'Horas',
                    data: dataTecnico.map(i => i.total_horas),
                    type: 'line',
                    borderColor: '#fbbf24', // Amber
                    borderWidth: 2,
                    order: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    y: { beginAtZero: true },
                    y1: { position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    // F. REPUESTOS (Pie - Origen)
    if (document.getElementById('chartRepuestos')) {
        // Mapeo especial para ORIGEN
        const labelsRep = dataRepuestos.map(i => i.origen.toUpperCase());
        const valuesRep = dataRepuestos.map(i => i.total);
        
        // Colores específicos: INEES (Naranja), PROSEGUR (Azul)
        const colorsRep = labelsRep.map(l => l.includes('INEES') ? '#f97316' : '#3b82f6');

        new Chart(document.getElementById('chartRepuestos'), {
            type: 'pie',
            data: {
                labels: labelsRep,
                datasets: [{
                    data: valuesRep,
                    backgroundColor: colorsRep,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: { maintainAspectRatio: false, responsive: true }
        });
    }

    // G. PRINCIPALES NOVEDADES POR DELEGACIÓN (Solo no fallidos)
    if (document.getElementById('chartNovedadesDelegacion')) {
        const entradasDelegacion = Object.keys(dataNovedadesDelegacion).map(del => {
            const items = dataNovedadesDelegacion[del] || [];
            return {
                del: del,
                total: items.reduce((suma, item) => suma + (Number(item.total) || 0), 0)
            };
        }).sort((a, b) => b.total - a.total).slice(0, 12);

        const labelsNovedades = entradasDelegacion.map(e => e.del);
        const coloresNovedades = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#94a3b8'];

        const datasetsNovedades = topNovedadesGlobales.map((nombre, indice) => ({
            label: nombre,
            data: entradasDelegacion.map(e => {
                const encontrada = (dataNovedadesDelegacion[e.del] || []).find(item => item.nombre === nombre);
                return encontrada ? Number(encontrada.total) : 0;
            }),
            backgroundColor: coloresNovedades[indice % coloresNovedades.length],
            borderRadius: 2,
            stack: 'novedades'
        }));

        const otrasNovedades = entradasDelegacion.map(e => {
            const items = dataNovedadesDelegacion[e.del] || [];
            return items
                .filter(item => !topNovedadesGlobales.includes(item.nombre))
                .reduce((suma, item) => suma + (Number(item.total) || 0), 0);
        });

        if (otrasNovedades.some(valor => valor > 0)) {
            datasetsNovedades.push({
                label: 'Otras',
                data: otrasNovedades,
                backgroundColor: '#cbd5e1',
                borderRadius: 2,
                stack: 'novedades'
            });
        }

        if (labelsNovedades.length > 0 && datasetsNovedades.length > 0) {
            new Chart(document.getElementById('chartNovedadesDelegacion'), {
                type: 'bar',
                data: {
                    labels: labelsNovedades,
                    datasets: datasetsNovedades
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: { stacked: true, beginAtZero: true, grid: { display: true } },
                        y: { stacked: true, grid: { display: false } }
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                    }
                }
            });
        } else {
            const ctx = document.getElementById('chartNovedadesDelegacion').getContext('2d');
            ctx.font = "14px Arial";
            ctx.fillStyle = "#9ca3af";
            ctx.textAlign = "center";
            ctx.fillText("Sin novedades registradas en el período", ctx.canvas.width / 2, ctx.canvas.height / 2);
        }
    }

    // H. CLIENTES CON MÁS NOVEDADES POR DELEGACIÓN (Top 3)
    if (document.getElementById('chartClientesNovedades')) {
        const delegacionesClientes = Object.keys(dataClientesNovedades);
        const paletaDelegaciones = ['#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#8b5cf6', '#0ea5e9', '#f97316', '#14b8a6', '#e11d48', '#64748b'];

        const etiquetasClientes = [];
        const valoresClientes = [];
        const coloresClientes = [];
        const detalleNovedades = [];

        delegacionesClientes.forEach((del, indiceDel) => {
            const color = paletaDelegaciones[indiceDel % paletaDelegaciones.length];
            (dataClientesNovedades[del] || []).forEach(cli => {
                etiquetasClientes.push(`${cli.cliente} · ${del}`);
                valoresClientes.push(Number(cli.total) || 0);
                coloresClientes.push(color);
                detalleNovedades.push((cli.novedades || []).map(n => `${n.nombre} (${n.total})`));
            });
        });

        if (etiquetasClientes.length > 0) {
            new Chart(document.getElementById('chartClientesNovedades'), {
                type: 'bar',
                data: {
                    labels: etiquetasClientes,
                    datasets: [{
                        label: 'Servicios con novedad',
                        data: valoresClientes,
                        backgroundColor: coloresClientes,
                        borderRadius: 4,
                        maxBarThickness: 22
                    }]
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { display: true } },
                        y: {
                            grid: { display: false },
                            ticks: {
                                autoSkip: false,
                                font: { size: 10 },
                                callback: function (value) {
                                    const texto = this.getLabelForValue(value);
                                    return texto.length > 40 ? texto.slice(0, 38) + '…' : texto;
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `Servicios con novedad: ${ctx.raw}`,
                                afterLabel: (ctx) => {
                                    const items = detalleNovedades[ctx.dataIndex] || [];
                                    if (items.length === 0) return '';
                                    return ['Top novedades:', ...items.map(item => `• ${item}`)];
                                }
                            }
                        }
                    }
                }
            });
        }
    }
</script>