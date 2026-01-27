<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Ejecutivo</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .page-break {
            page-break-after: always;
        }

        .card-wrapper {
            break-inside: avoid;
            page-break-inside: avoid;
            display: inline-block;
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .card {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #f3f4f6;
            padding: 24px;
        }

        .portada-container {
            height: 190mm;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            position: relative;
            overflow: hidden;
        }

        .bg-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>

<body class="p-8 text-slate-800">

    <?php if ($this->seccionActiva('portada')): ?>
        <div class="portada-container w-full rounded-3xl shadow-2xl flex flex-col items-center justify-center text-white relative mb-8 card-wrapper">
            <div class="absolute inset-0 bg-pattern opacity-50"></div>

            <div class="z-10 text-center mt-[-40px]">
                <div class="uppercase tracking-[0.3em] text-sm opacity-80 mb-2">Documento Confidencial</div>
                <h1 class="text-6xl font-extrabold mb-2 tracking-tight">Reporte Ejecutivo</h1>
                <div class="mt-4 inline-block bg-white/10 backdrop-blur-md border border-white/20 px-6 py-2 rounded-full">
                    <span class="text-md font-mono">📅 <?= date('d/m/Y', strtotime($inicio)) ?> — <?= date('d/m/Y', strtotime($fin)) ?></span>
                </div>
            </div>

            <div class="absolute bottom-8 w-full px-8">
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-xl text-left h-48 overflow-hidden">
                        <div class="flex justify-between items-end mb-3 border-b border-white/20 pb-2">
                            <div class="text-xs uppercase opacity-75">Servicios</div>
                            <div class="text-2xl font-bold"><?= number_format($totalGlobalServicios) ?></div>
                        </div>
                        <div class="space-y-2 text-[10px]">
                            <?php foreach (array_slice($kpisDelegacion, 0, 5) as $k): ?>
                                <div class="flex items-center justify-between">
                                    <span class="truncate w-16"><?= $k['delegacion'] ?></span>
                                    <div class="flex items-center gap-2 flex-1 justify-end">
                                        <span class="font-bold"><?= $k['total'] ?></span>
                                        <span class="bg-white/20 px-1 rounded text-[9px] w-10 text-center"><?= $k['porcentaje'] ?>%</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-xl text-left h-48">
                        <div class="text-xs uppercase opacity-75 mb-3 border-b border-white/20 pb-2">
                            Promedio / Téc. <span class="normal-case opacity-50">(Diario)</span>
                        </div>
                        <div class="space-y-2 text-[10px]">
                            <?php foreach (array_slice($kpisDelegacion, 0, 5) as $k): ?>
                                <div class="flex justify-between items-center">
                                    <span><?= $k['delegacion'] ?></span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-12 h-1 bg-white/20 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-400" style="width: <?= ($k['promedio_diario'] / 8) * 100 ?>%"></div>
                                        </div>
                                        <span class="font-bold text-emerald-200 w-6 text-right"><?= $k['promedio_diario'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-xl text-left h-48">
                        <div class="flex justify-between items-end mb-3 border-b border-white/20 pb-2">
                            <div class="text-xs uppercase opacity-75">Novedades</div>
                            <div class="text-2xl font-bold text-red-200"><?= number_format($datosNovedad['con_novedad'] ?? 0) ?></div>
                        </div>
                        <div class="space-y-2 text-[10px]">
                            <?php foreach (array_slice($kpisDelegacion, 0, 5) as $k): ?>
                                <div class="flex justify-between items-center">
                                    <span><?= $k['delegacion'] ?></span>
                                    <span class="font-bold text-red-200"><?= $k['novedades'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-xl text-left h-48 overflow-hidden">
                        <div class="text-xs uppercase opacity-75 mb-3 border-b border-white/20 pb-2">Zonas Activas</div>
                        <div class="flex flex-wrap gap-2 content-start">
                            <?php foreach ($kpisDelegacion as $k): ?>
                                <span class="bg-indigo-500/30 border border-indigo-300/30 px-2 py-1 rounded text-[10px]">
                                    <?= $k['delegacion'] ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-break"></div>
    <?php endif; ?>

    <div class="columns-1 md:columns-2 gap-8 space-y-8">

        <?php if ($this->seccionActiva('tendencias')): ?>
            <div class="card-wrapper">
                <div class="card">
                    <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-xl">📅</div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Evolución Diaria</h3>
                            <p class="text-xs text-slate-400">Detalle por semanas operativas</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-[10px]">
                        <?php
                        $maxVal = 1;
                        foreach ($semanasGroup as $dias) {
                            foreach ($dias as $d) $maxVal = max($maxVal, $d['total']);
                        }

                        foreach ($semanasGroup as $nombreSemana => $dias): ?>
                            <div class="bg-slate-50 border border-slate-100 rounded-lg p-3">
                                <h4 class="font-bold text-blue-600 uppercase mb-2 border-b border-blue-100 pb-1"><?= $nombreSemana ?></h4>
                                <div class="space-y-2">
                                    <?php foreach ($dias as $d): $pct = ($d['total'] / $maxVal) * 100; ?>
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 font-medium text-slate-500"><?= $d['dia_nombre'] ?> <span class="text-[8px] opacity-70"><?= date('d', strtotime($d['fecha'])) ?></span></div>
                                            <div class="flex-1 h-2 bg-white rounded-full overflow-hidden border border-slate-100">
                                                <div class="h-full bg-blue-400 rounded-full" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <div class="w-6 text-right font-bold text-slate-700"><?= $d['total'] ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->seccionActiva('mantenimiento')): ?>
            <div class="card-wrapper mt-8">
                <div class="card">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 text-xl">🔧</div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Matriz: Tipos de Servicio</h3>
                            <p class="text-xs text-slate-400">Servicios por Delegación</p>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        <table class="w-full text-[9px] text-center">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="px-2 py-2 text-left bg-slate-100 border-b border-slate-200">Delegación</th>
                                    <?php foreach ($todosTiposMant as $t): ?>
                                        <th class="px-1 py-2 border-b border-slate-200 align-bottom w-20">
                                            <div class="text-[7px] leading-tight break-words whitespace-normal text-slate-600">
                                                <?= $t['nombre_completo'] ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="px-2 py-2 border-b border-slate-200 bg-slate-50 font-bold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($delegacionesListaMant as $del): $rowT = 0; ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-2 py-1 text-left font-bold text-slate-700 bg-slate-50/50"><?= $del ?></td>
                                        <?php foreach ($todosTiposMant as $t):
                                            $val = $matrizMant[$del][$t['nombre_completo']] ?? 0;
                                            $rowT += $val;
                                            $cls = $val == 0 ? 'text-slate-200' : 'text-purple-700 font-bold bg-purple-50';
                                        ?>
                                            <td class="px-1 py-1 <?= $cls ?>"><?= $val ?></td>
                                        <?php endforeach; ?>
                                        <td class="px-2 py-1 font-bold bg-slate-100"><?= $rowT ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->seccionActiva('maquinas')): ?>
            <div class="card-wrapper mt-8">
                <div class="card">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center text-teal-600 text-xl">🏧</div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Matriz: Tipos de Máquina</h3>
                            <p class="text-xs text-slate-400">Distribución de flota atendida</p>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        <table class="w-full text-[9px] text-center">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="px-2 py-2 text-left bg-slate-100 border-b border-slate-200">Delegación</th>
                                    <?php foreach ($tiposMaquinaCols as $tm): ?>
                                        <th class="px-1 py-2 border-b border-slate-200 max-w-[60px] truncate" title="<?= $tm['nombre_tipo_maquina'] ?>">
                                            <?= substr($tm['nombre_tipo_maquina'], 0, 8) ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="px-2 py-2 border-b border-slate-200 bg-slate-50 font-bold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($delegacionesListaMaquina as $del): $rowT = 0; ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-2 py-1 text-left font-bold text-slate-700 bg-slate-50/50"><?= $del ?></td>
                                        <?php foreach ($tiposMaquinaCols as $tm):
                                            $val = $matrizMaquina[$del][$tm['nombre_tipo_maquina']] ?? 0;
                                            $rowT += $val;
                                            $cls = $val == 0 ? 'text-slate-200' : 'text-teal-700 font-bold bg-teal-50';
                                        ?>
                                            <td class="px-1 py-1 <?= $cls ?>"><?= $val ?></td>
                                        <?php endforeach; ?>
                                        <td class="px-2 py-1 font-bold bg-slate-100"><?= $rowT ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

       <?php if ($this->seccionActiva('repuestos')): ?>
            <div class="card-wrapper mt-4">
                <div class="card bg-slate-50 border border-slate-200 p-4"> <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-lg">⚙️</div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Uso de Repuestos</h3>
                            <p class="text-[10px] text-slate-400">Top 10 más utilizados por delegación</p>
                        </div>
                    </div>

                    <div class="columns-1 md:columns-2 lg:columns-3 gap-2 space-y-2">
                        <?php foreach ($repuestosPorDelegacion as $nomDelegacion => $datos): ?>
                            <div class="break-inside-avoid page-break-inside-avoid bg-white rounded-lg shadow-sm border border-slate-200 p-2">
                                
                                <div class="flex justify-between items-center mb-1.5 border-b border-slate-100 pb-1">
                                    <h4 class="font-bold text-slate-700 text-[10px] uppercase truncate max-w-[70%]"><?= $nomDelegacion ?></h4>
                                    <span class="bg-orange-100 text-orange-700 text-[8px] px-1.5 py-0 rounded-full font-bold">Tot: <?= $datos['total_gral'] ?></span>
                                </div>

                                <ul class="space-y-0.5">
                                    <?php foreach ($datos['items'] as $item): ?>
                                        <li class="flex justify-between items-start text-[8px] gap-2 py-0.5 border-b border-slate-50 last:border-0">
                                            <span class="text-slate-600 text-left leading-tight flex-1">
                                                <?= $item['nombre'] ?>
                                            </span>
                                            <span class="font-bold text-slate-800 bg-slate-100 px-1 py-0 rounded shrink-0">
                                                <?= $item['cantidad'] ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->seccionActiva('tecnicos')): ?>
            <div class="card-wrapper">
                <div class="card pt-4 pb-4 px-5">
                    <div class="flex items-center gap-2 mb-3 border-b border-gray-100 pb-2">
                        <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 text-lg">👷</div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Productividad Técnica</h3>
                            <p class="text-[10px] text-slate-400">Promedio Diario (L-V vs Sáb)</p>
                        </div>
                    </div>

                    <table class="w-full text-[9px]">
                        <thead>
                            <tr class="text-slate-400 text-[8px] uppercase tracking-wider text-left">
                                <th class="pb-1 font-semibold">Técnico</th>
                                <th class="pb-1 text-center font-semibold w-16">Media L-V</th>
                                <th class="pb-1 text-center font-semibold w-16">Media Sáb</th>
                            </tr>
                        </thead>
                        <tbody class="space-y-0">
                            <?php foreach ($topTecnicos as $t):
                                $mediaLV = ($t['dias_trabajados_lv'] > 0) ? round($t['servicios_lv'] / $t['dias_trabajados_lv'], 1) : 0;
                                $mediaSab = ($t['dias_trabajados_sab'] > 0) ? round($t['servicios_sab'] / $t['dias_trabajados_sab'], 1) : 0;

                                $colorLV = $mediaLV >= 6 ? 'bg-emerald-500' : ($mediaLV >= 4 ? 'bg-amber-400' : 'bg-red-400');
                                $colorSab = $mediaSab >= 4 ? 'bg-blue-500' : ($mediaSab >= 2 ? 'bg-blue-300' : 'bg-slate-300');
                            ?>
                                <tr class="border-b border-slate-50 last:border-0">
                                    <td class="py-1.5 text-slate-700 font-medium">
                                        <div class="truncate max-w-[120px] leading-tight"><?= $t['nombre_tecnico'] ?></div>
                                        <div class="text-[8px] text-slate-400 leading-none">Tot: <?= $t['total_general'] ?></div>
                                    </td>

                                    <td class="py-1.5 px-1">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="font-bold text-slate-700 leading-none"><?= $mediaLV ?></span>
                                            <div class="w-full bg-slate-100 rounded-full h-1 overflow-hidden">
                                                <div class="h-full <?= $colorLV ?>" style="width: <?= min(($mediaLV / 10) * 100, 100) ?>%"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-1.5 px-1">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="font-bold text-slate-700 leading-none"><?= $mediaSab ?></span>
                                            <div class="w-full bg-slate-100 rounded-full h-1 overflow-hidden">
                                                <div class="h-full <?= $colorSab ?>" style="width: <?= min(($mediaSab / 8) * 100, 100) ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->seccionActiva('puntos_fallidos')): ?>
            <div class="card-wrapper">
                <div class="card border-l-4 border-l-red-500">
                    <div class="flex items-center gap-3 mb-6 pb-2">
                        <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600 text-xl">⚠️</div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Puntos Críticos</h3>
                            <p class="text-xs text-slate-400">Top fallas recurrentes</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <?php
                        $max = !empty($datosPuntosFallidos) ? max(array_column($datosPuntosFallidos, 'total_fallidos')) : 1;
                        foreach (array_slice($datosPuntosFallidos, 0, 7) as $pf):
                            $pct = ($pf['total_fallidos'] / $max) * 100;
                        ?>
                            <div class="flex items-center gap-3">
                                <div class="w-1/2 text-xs font-medium text-slate-600 truncate"><?= $pf['nombre_punto'] ?></div>
                                <div class="w-1/2 flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-red-50 rounded-full overflow-hidden">
                                        <div class="h-full bg-red-500" style="width: <?= $pct ?>%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-red-700 w-6 text-right"><?= $pf['total_fallidos'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->seccionActiva('delegaciones')): ?>
            <div class="card-wrapper">
                <div class="card">
                    <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                        <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-xl">🏢</div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Top Delegaciones</h3>
                            <p class="text-xs text-slate-400">Intervenciones y fuerza laboral</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3">
                        <?php
                        $max = !empty($datosDelegacion) ? max(array_column($datosDelegacion, 'total')) : 1;
                        foreach (array_slice($datosDelegacion, 0, 8) as $d):
                            $pct = ($d['total'] / $max) * 100;
                        ?>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-slate-600 w-1/3 truncate"><?= $d['nombre_delegacion'] ?></span>
                                <div class="w-2/3 flex items-center gap-3">
                                    <div class="flex-1 h-5 bg-indigo-50 rounded relative overflow-hidden flex items-center px-2">
                                        <div class="absolute top-0 left-0 h-full bg-indigo-500 opacity-20" style="width: <?= $pct ?>%"></div>
                                        <span class="relative text-[10px] font-bold text-indigo-700 leading-none"><?= $d['total'] ?> srv.</span>
                                    </div>
                                    <div class="flex items-center gap-1 bg-slate-100 px-2 py-1 rounded text-slate-500 text-[10px] font-semibold border border-slate-200" title="Técnicos activos">
                                        <span>👷</span>
                                        <span><?= $d['num_tecnicos'] ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->seccionActiva('calificaciones')): ?>
            <div class="card-wrapper">
                <div class="card border-l-4 border-l-amber-400">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 text-xl">⭐</div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Satisfacción</h3>
                            <p class="text-xs text-slate-400">Calidad percíbida</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <?php
                        $total = array_sum(array_column($datosCalificaciones, 'total'));
                        foreach ($datosCalificaciones as $c):
                            $pct = $total > 0 ? round(($c['total'] / $total) * 100, 1) : 0;
                        ?>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-bold text-amber-600"><?= $c['calificacion'] ?></span>
                                    <span class="text-slate-500"><?= $pct ?>%</span>
                                </div>
                                <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-400" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>