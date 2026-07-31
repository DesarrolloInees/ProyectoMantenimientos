<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Repuestos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .card-wrapper {
            display: block;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 1.5rem;
        }

        .card {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            padding: 24px;
        }

        .tecnico-page {
            width: 100% !important;
            page-break-after: always;
            break-after: page;
        }

        .repuestos-grid {
            column-count: 3;
            column-gap: 1.5rem;
            width: 100%;
        }

        .repuesto-item {
            break-inside: avoid;
            page-break-inside: avoid;
            margin-bottom: 0.5rem;
            padding: 0.4rem 0.3rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body class="p-4 text-slate-800">

    <!-- PORTADA / ENCABEZADO -->
    <div class="card-wrapper avoid-break">
        <div class="card flex justify-between items-center border-t-8 border-indigo-600 relative overflow-hidden">
            <div>
                <h1 class="text-4xl font-black text-indigo-700 tracking-tight">REPORTE DE <span
                        class="text-slate-800">INVENTARIO</span></h1>
                <p class="text-slate-500 mt-2 font-medium">Estado actual del stock asignado a técnicos en campo.</p>
                <div
                    class="mt-3 inline-block bg-indigo-50 border border-indigo-100 px-4 py-1.5 rounded-md text-sm font-bold text-indigo-700">
                    Corte al: <?= $fechaReporte ?>
                </div>
            </div>
            <?php if (!empty($logoBase64)): ?>
                <img src="<?= $logoBase64 ?>" class="h-16 object-contain" alt="Logo">
            <?php endif; ?>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-4 gap-4 mb-6 avoid-break">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl">📦
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400">Total Piezas Asignadas</p>
                <p class="text-2xl font-black text-slate-800"><?= number_format($kpis['total_piezas']) ?></p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 text-2xl">
                👷</div>
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400">Técnicos con Stock</p>
                <p class="text-2xl font-black text-slate-800"><?= $kpis['tecnicos_con_stock'] ?></p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-2xl">
                🔖</div>
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400">Referencias Activas</p>
                <p class="text-2xl font-black text-slate-800"><?= $kpis['referencias_distintas'] ?></p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-rose-200 shadow-sm flex items-center gap-4 bg-rose-50/30">
            <div class="w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 text-2xl">⚠️
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-rose-500">Repuestos Agotados</p>
                <p class="text-2xl font-black text-rose-700"><?= $kpis['repuestos_agotados'] ?></p>
            </div>
        </div>
    </div>

    <!-- CONSOLIDADO DE REPUESTOS -->
    <div class="card-wrapper">
        <div class="card">
            <h3 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">
                <i class="text-indigo-500 mr-2">📊</i> Consolidado Global de Repuestos en Calle
            </h3>

            <div class="flex flex-row gap-6">
                <!-- Tabla Izquierda -->
                <div class="w-1/2">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[9px]">
                            <tr>
                                <th class="py-2 px-3 rounded-tl-lg">Repuesto</th>
                                <th class="py-2 px-3 text-center">Ref.</th>
                                <th class="py-2 px-3 text-center">Técnicos</th>
                                <th class="py-2 px-3 text-right rounded-tr-lg">Cantidad Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($consolidado_izq as $item): ?>
                                <tr class="avoid-break hover:bg-slate-50">
                                    <td class="py-1.5 px-3 font-medium text-slate-700">
                                        <?= $item['nombre_repuesto'] ?>
                                    </td>
                                    <td class="py-1.5 px-3 text-center text-[10px] text-slate-500">
                                        <?= $item['codigo_referencia'] ?: 'N/A' ?>
                                    </td>
                                    <td class="py-1.5 px-3 text-center">
                                        <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px]">
                                            <?= $item['tecnicos_lo_tienen'] ?>
                                        </span>
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-black text-indigo-600">
                                        <?= $item['total_asignado'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tabla Derecha -->
                <div class="w-1/2">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[9px]">
                            <tr>
                                <th class="py-2 px-3 rounded-tl-lg">Repuesto</th>
                                <th class="py-2 px-3 text-center">Ref.</th>
                                <th class="py-2 px-3 text-center">Técnicos</th>
                                <th class="py-2 px-3 text-right rounded-tr-lg">Cantidad Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($consolidado_der as $item): ?>
                                <tr class="avoid-break hover:bg-slate-50">
                                    <td class="py-1.5 px-3 font-medium text-slate-700">
                                        <?= $item['nombre_repuesto'] ?>
                                    </td>
                                    <td class="py-1.5 px-3 text-center text-[10px] text-slate-500">
                                        <?= $item['codigo_referencia'] ?: 'N/A' ?>
                                    </td>
                                    <td class="py-1.5 px-3 text-center">
                                        <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px]">
                                            <?= $item['tecnicos_lo_tienen'] ?>
                                        </span>
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-black text-indigo-600">
                                        <?= $item['total_asignado'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SALTO DE PÁGINA ANTES DEL DESGLOSE -->
        <div class="page-break"></div>

        <!-- INVENTARIO POR TÉCNICO -->
        <!-- INVENTARIO POR TÉCNICO -->
        <div class="card-wrapper">
            <h3 class="text-2xl font-black text-slate-800 mb-4 px-2">Desglose por Técnico</h3>

            <?php foreach ($inventarioTecnicos as $nombreTecnico => $repuestos): ?>
                <!-- Cada técnico en su propia página, ocupando el 100% del ancho -->
                <div class="tecnico-page w-full" style="page-break-after: always; break-after: page;">

                    <!-- ENCABEZADO DEL TÉCNICO (ancho completo) -->
                    <div
                        class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 mb-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 text-base">
                                👷</div>
                            <div>
                                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight"><?= $nombreTecnico ?></h3>
                                <p class="text-[10px] text-slate-400 font-medium">Inventario asignado en campo</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-100 px-3 py-1 rounded-md font-bold">
                                <?= count($repuestos) ?> Referencias
                            </span>
                            <?php
                            $totalPiezasTecnico = array_sum(array_column($repuestos, 'cantidad_actual'));
                            ?>
                            <span
                                class="text-xs bg-slate-100 text-slate-700 border border-slate-200 px-3 py-1 rounded-md font-black">
                                Total Piezas: <?= $totalPiezasTecnico ?>
                            </span>
                        </div>
                    </div>

                    <!-- CONTENEDOR CON 3 COLUMNAS AUTOMÁTICAS (ancho completo) -->
                    <div class="repuestos-grid" style="column-count: 3; column-gap: 1.5rem; width: 100%;">
                        <?php foreach ($repuestos as $rep):
                            $colorCant = $rep['cantidad_actual'] > 0 ? 'text-emerald-600' : 'text-rose-500';
                            ?>
                            <div class="repuesto-item"
                                style="break-inside: avoid; page-break-inside: avoid; margin-bottom: 0.5rem; padding: 0.4rem 0.3rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                                <div style="flex: 1;">
                                    <div class="font-medium text-slate-700 text-[10px] leading-tight">
                                        <?= $rep['nombre_repuesto'] ?>
                                    </div>
                                    <?php if (!empty($rep['codigo_referencia'])): ?>
                                        <div class="text-[8px] text-slate-400 font-mono">Ref: <?= $rep['codigo_referencia'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-2 whitespace-nowrap">
                                    <span class="font-bold px-2 py-0.5 rounded text-[10px] <?= $colorCant ?>">
                                        <?= $rep['cantidad_actual'] ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>

</html>