<?php if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado."); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="w-full max-w-7xl mx-auto">

    <div class="mb-8 border-b pb-4">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-database text-purple-600 mr-2"></i> Estado de la Base de Datos</h1>
        <p class="text-gray-500 text-sm">Resumen actual del inventario, máquinas y cobertura.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 font-bold uppercase">Total Máquinas</p>
            <p class="text-2xl font-extrabold text-purple-700"><?= number_format($kpis['total_maquinas']) ?></p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-500">
            <p class="text-xs text-gray-500 font-bold uppercase">Activas</p>
            <p class="text-2xl font-extrabold text-green-600"><?= number_format($kpis['maquinas_activas']) ?></p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 font-bold uppercase">Puntos / Sitios</p>
            <p class="text-2xl font-extrabold text-blue-600"><?= number_format($kpis['total_puntos']) ?></p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-gray-500">
            <p class="text-xs text-gray-500 font-bold uppercase">Delegaciones</p>
            <p class="text-2xl font-extrabold text-gray-600"><?= number_format($kpis['total_delegaciones']) ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 lg:col-span-2">
            <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Distribución de Máquinas por Delegación</h3>
            <div class="relative h-96 w-full">
                <canvas id="chartInventario"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
            <h3 class="text-gray-700 font-bold mb-4 border-b pb-2">Top 10 Clientes</h3>
            <div class="overflow-y-auto h-96">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-600">Cliente</th>
                            <th class="px-4 py-2 text-right text-gray-600">Máq.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($topClientes)): ?>
                            <?php foreach($topClientes as $cli): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-700 font-medium"><?= $cli['nombre_cliente'] ?></td>
                                <td class="px-4 py-3 text-right font-bold text-blue-600"><?= $cli['total'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr><td colspan="2" class="p-4 text-center text-gray-400">No hay datos de clientes</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100">
             <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-gray-700 font-bold">Actualidad de Visitas</h3>
                <span class="text-xs text-gray-400">Basado en 'ultima_visita'</span>
            </div>
            <div class="relative h-64 w-full flex justify-center">
                <canvas id="chartAntiguedad"></canvas>
            </div>
            <div class="mt-4 text-xs text-gray-500 text-center">
                Muestra qué tan recientes son las intervenciones en las máquinas activas.
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-100 flex flex-col justify-center items-center text-center">
             <div class="p-6 bg-blue-50 rounded-full mb-4">
                <i class="fas fa-server text-4xl text-blue-400"></i>
             </div>
             <h3 class="text-lg font-bold text-gray-700">Estado del Sistema</h3>
             <p class="text-gray-500 mt-2 px-6">La base de datos se actualiza en tiempo real con cada orden de servicio cerrada por los técnicos.</p>
        </div>
    </div>

</div>

<script>
    // 1. DATOS INVENTARIO (Gráfico Barras Apiladas)
    const dataInventario = <?= json_encode($datosInventario ?? []) ?>;
    
    if (document.getElementById('chartInventario') && dataInventario.length > 0) {
        const delegaciones = [...new Set(dataInventario.map(item => item.nombre_delegacion))];
        const tipos = [...new Set(dataInventario.map(item => item.nombre_tipo_maquina))];
        const colores = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'];

        const datasetsInv = tipos.map((tipo, index) => {
            return {
                label: tipo,
                data: delegaciones.map(del => {
                    const found = dataInventario.find(d => d.nombre_delegacion === del && d.nombre_tipo_maquina === tipo);
                    return found ? found.total : 0;
                }),
                backgroundColor: colores[index % colores.length],
                stack: 'Stack 0',
            };
        });

        new Chart(document.getElementById('chartInventario'), {
            type: 'bar',
            data: { labels: delegaciones, datasets: datasetsInv },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });
    }

    // 2. DATOS ANTIGÜEDAD (Gráfico Dona) - NUEVO
    const dataAntiguedad = <?= json_encode($antiguedad ?? []) ?>;

    if (document.getElementById('chartAntiguedad') && dataAntiguedad.length > 0) {
        new Chart(document.getElementById('chartAntiguedad'), {
            type: 'doughnut',
            data: {
                labels: dataAntiguedad.map(d => d.rango),
                datasets: [{
                    data: dataAntiguedad.map(d => d.total),
                    backgroundColor: [
                        '#10b981', // Verde (Al día)
                        '#f59e0b', // Amarillo (1-3 meses)
                        '#ef4444', // Rojo (Olvidada)
                        '#9ca3af'  // Gris (Sin fecha)
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }
</script>