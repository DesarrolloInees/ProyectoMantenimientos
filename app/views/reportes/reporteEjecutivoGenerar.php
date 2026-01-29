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

        /* ESTILO NUEVO: AHORRO DE TINTA */
        .portada-container {
            min-height: 190mm; /* Crecera si es necesario */
            height: auto;      /* Importante para adaptarse */
            background-color: #ffffff;
            height: 190mm;
            /* Fondo blanco */
            color: #1e293b;
            /* Texto gris oscuro (Slate 800) */
            position: relative;
            /* Bordes decorativos finos en lugar de fondo completo */
            border-top: 12px solid #3b82f6;
            /* Azul corporativo */
            border-bottom: 12px solid #334155;
            /* Gris oscuro */
            display: flex;
            flex-col: column;
            justify-content: center;
        }

        /* Quitamos el patrón de puntos para limpiar la impresión */
        .bg-pattern {
            display: none;
        }

        /* Estilo para la etiquetita de fecha en cada card */
        .date-badge {
            font-size: 9px;
            color: #94a3b8;
            /* slate-400 */
            background-color: #f8fafc;
            /* slate-50 */
            border: 1px solid #f1f5f9;
            /* slate-100 */
            padding: 2px 8px;
            border-radius: 6px;
            font-family: 'Courier New', Courier, monospace;
            /* Toque técnico */
            white-space: nowrap;
        }
    </style>
</head>

<body class="p-8 text-slate-800">

    <?php if ($this->seccionActiva('portada')): ?>
        <div class="portada-container w-full rounded-none flex flex-col items-center justify-between py-12 relative mb-8 card-wrapper" style="min-height: 190mm; height: auto;">
            
            <div class="z-10 text-center flex-shrink-0 mb-8">
                <div class="uppercase tracking-[0.4em] text-xs font-bold text-slate-400 mb-4">Documento Confidencial</div>
                
                <h1 class="text-6xl font-black text-slate-800 mb-2 tracking-tight leading-none">
                    REPORTE<br>
                    <span class="text-blue-600">EJECUTIVO</span>
                </h1>
                
                <div class="w-24 h-2 bg-blue-600 mx-auto my-6 rounded-full"></div>

                <div class="mt-2 inline-block bg-slate-50 border border-slate-200 px-8 py-3 rounded-full">
                    <span class="text-lg font-mono font-bold text-slate-600">📅 <?= date('d/m/Y', strtotime($inicio)) ?> — <?= date('d/m/Y', strtotime($fin)) ?></span>
                </div>
            </div>

            <div class="w-full px-8 mb-4 flex-1 flex items-end">
                <div class="grid grid-cols-4 gap-4 w-full items-stretch">
                    
                    <div class="bg-white border border-slate-200 p-4 rounded-xl text-left flex flex-col shadow-sm min-h-[12rem]">
                        <div class="flex justify-between items-start border-b border-slate-100 pb-2 mb-2">
                            <div class="text-xs uppercase font-bold text-slate-400">Total Servicios</div>
                            <div class="text-3xl font-black text-blue-600"><?= number_format($totalGlobalServicios) ?></div>
                        </div>
                        <div class="space-y-1.5 text-[10px] flex-1">
                            <?php foreach ($kpisDelegacion as $k): ?>
                                <div class="flex items-center justify-between border-b border-slate-50 last:border-0 py-1">
                                    <span class="truncate w-16 text-slate-600 font-medium"><?= $k['delegacion'] ?></span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-800"><?= $k['total'] ?></span>
                                        <span class="text-[9px] text-slate-400 w-8 text-right"><?= $k['porcentaje'] ?>%</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 p-4 rounded-xl text-left flex flex-col shadow-sm min-h-[12rem]">
                        <div class="text-xs uppercase font-bold text-slate-400 mb-2 border-b border-slate-100 pb-2">
                            Promedio / Téc. <span class="normal-case opacity-50">(Diario)</span>
                        </div>
                        <div class="space-y-1.5 text-[10px] flex-1">
                            <?php foreach ($kpisDelegacion as $k): ?>
                                <div class="flex justify-between items-center py-1 border-b border-slate-50 last:border-0">
                                    <span class="text-slate-600"><?= $k['delegacion'] ?></span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500" style="width: <?= ($k['promedio_diario'] / 8) * 100 ?>%"></div>
                                        </div>
                                        <span class="font-bold text-emerald-600 w-6 text-right"><?= $k['promedio_diario'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 p-4 rounded-xl text-left flex flex-col shadow-sm min-h-[12rem]">
                        <div class="flex justify-between items-start border-b border-slate-100 pb-2 mb-2">
                            <div class="text-xs uppercase font-bold text-slate-400">Novedades</div>
                            <div class="text-3xl font-black text-red-500"><?= number_format($datosNovedad['con_novedad'] ?? 0) ?></div>
                        </div>
                        <div class="space-y-1.5 text-[10px] flex-1">
                            <?php foreach ($kpisDelegacion as $k): ?>
                                <div class="flex justify-between items-center py-1 border-b border-slate-50 last:border-0">
                                    <span class="text-slate-600"><?= $k['delegacion'] ?></span>
                                    <span class="font-bold <?= $k['novedades'] > 0 ? 'text-red-500' : 'text-slate-300' ?>">
                                        <?= $k['novedades'] ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 p-4 rounded-xl text-left flex flex-col shadow-sm min-h-[12rem]">
                        <div class="text-xs uppercase font-bold text-slate-400 mb-3 border-b border-slate-100 pb-2">Delegaciones</div>
                        <div class="flex flex-wrap gap-2 content-start">
                            <?php foreach ($kpisDelegacion as $k): ?>
                                <span class="bg-slate-50 border border-slate-200 text-slate-600 px-2 py-1 rounded text-[9px] font-medium">
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



        <?php if ($this->seccionActiva('delegaciones')): ?>
            <div class="card-wrapper mt-4">
                <div class="card p-3">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-lg">📊</div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Participación por Delegación</h3>
                                <p class="text-[9px] text-slate-400">Volumen, % Global y Fuerza Técnica</p>
                            </div>
                        </div>
                        <div class="text-[9px] font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded border border-slate-100">
                            <?= date('d/m/y', strtotime($inicio)) ?> - <?= date('d/m/y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="relative h-44 w-full pl-8 pr-2 pt-6 pb-12 border-l border-b border-slate-200 mx-auto">

                        <div class="absolute inset-0 pl-8 pointer-events-none flex flex-col justify-between opacity-10">
                            <?php for ($i = 0; $i < 3; $i++): ?> <div class="w-full border-t border-slate-400 border-dashed h-0"></div>
                            <?php endfor; ?>
                            <div class="w-full h-0"></div>
                        </div>

                        <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-[8px] text-slate-300 font-bold pr-2 pb-12 pt-6">
                            <span>MAX</span>
                            <span>0</span>
                        </div>

                        <div class="flex items-end justify-around h-full w-full gap-2 relative z-10">
                            <?php
                            $valores = array_column($kpisDelegacion, 'total');
                            $maxValor = !empty($valores) ? max($valores) : 1;

                            foreach ($kpisDelegacion as $k):
                                $alturaBarra = ($k['total'] / $maxValor) * 100;
                                $esDestacado = $k['porcentaje'] > 20;
                                $colorBarra = $esDestacado ? 'bg-indigo-600' : 'bg-indigo-400';
                                $colorTexto = $esDestacado ? 'text-indigo-700 font-bold' : 'text-slate-500';

                                // AQUI ESTÁ LA SOLUCIÓN LIMPIA (Sin errores)
                                // Ya no buscamos en arrays raros, lo tomamos directo:
                                $numTecnicos = $k['num_tecnicos'] ?? 0
                            ?>
                                <div class="flex flex-col items-center justify-end h-full flex-1 group max-w-[35px]">
                                    <div class="mb-1 text-[9px] <?= $colorTexto ?>">
                                        <?= $k['total'] ?>
                                    </div>

                                    <div class="<?= $colorBarra ?> w-full rounded-t-sm shadow-sm transition-all duration-700"
                                        style="height: <?= $alturaBarra ?>%;">
                                    </div>

                                    <div class="absolute -bottom-10 flex flex-col items-center w-full">
                                        <div class="text-[7px] font-bold text-slate-600 uppercase truncate w-full text-center">
                                            <?= substr($k['delegacion'], 0, 8) ?>
                                        </div>

                                        <div class="text-[8px] font-bold text-indigo-600 bg-indigo-50 px-1 rounded-sm mt-0.5 border border-indigo-100">
                                            <?= $k['porcentaje'] ?>%
                                        </div>

                                        <div class="flex items-center gap-0.5 mt-0.5 text-[7px] text-slate-400" title="Técnicos Activos">
                                            <span>👷</span> <?= $numTecnicos ?>
                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-between items-center text-[8px] text-slate-400 bg-slate-50/50 p-1.5 rounded-lg border border-slate-100">
                        <div class="flex gap-3">
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-indigo-600 rounded-xs"></span> > 20% Part.
                            </div>
                            <div class="flex items-center gap-1">
                                👷 Técnicos Activos
                            </div>
                        </div>
                        <div>
                            Total Global: <strong class="text-slate-600"><?= number_format($totalGlobalServicios) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>




        <?php if ($this->seccionActiva('tendencias')): ?>
            <div class="card-wrapper mt-4">
                <div class="card p-3">
                    <div class="flex justify-between items-start mb-3 border-b border-gray-100 pb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-lg">📅</div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Evolución Diaria</h3>
                                <p class="text-[9px] text-slate-400">Detalle por semanas operativas</p>
                            </div>
                        </div>
                        <div class="text-[10px] font-bold text-slate-500"><?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?></div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-[9px]">
                        <?php
                        $maxVal = 1;
                        foreach ($semanasGroup as $dias) {
                            foreach ($dias as $d) $maxVal = max($maxVal, $d['total']);
                        }

                        foreach ($semanasGroup as $nombreSemana => $dias): ?>
                            <div class="bg-slate-50 border border-slate-100 rounded-lg p-2">
                                <h4 class="font-bold text-blue-600 uppercase mb-1.5 border-b border-blue-100 pb-1 text-[8px]"><?= $nombreSemana ?></h4>
                                <div class="space-y-1.5"> <?php foreach ($dias as $d): $pct = ($d['total'] / $maxVal) * 100; ?>
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-7 font-medium text-slate-500 leading-none">
                                                <?= $d['dia_nombre'] ?>
                                                <div class="text-[7px] opacity-70"><?= date('d', strtotime($d['fecha'])) ?></div>
                                            </div>
                                            <div class="flex-1 h-1.5 bg-white rounded-full overflow-hidden border border-slate-100">
                                                <div class="h-full bg-blue-400 rounded-full" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <div class="w-4 text-right font-bold text-slate-700 text-[8px]"><?= $d['total'] ?></div>
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
            <div class="card-wrapper mt-4">
                <div class="card p-2">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 text-lg">🔧</div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Matriz de Servicio</h3>
                                <p class="text-[9px] text-slate-400 leading-tight">Cantidades y porcentajes</p>
                            </div>
                        </div>
                        <div class="text-[10px] font-bold text-slate-400"><?= date('d/m/y', strtotime($inicio)) ?> - <?= date('d/m/y', strtotime($fin)) ?></div>
                    </div>

                    <?php
                    $totalesColumnaMant = [];
                    foreach ($todosTiposMant as $t) {
                        $totalesColumnaMant[$t['nombre_completo']] = 0;
                    }
                    $granTotalMant = 0;
                    ?>

                    <div class="overflow-hidden rounded-md border border-slate-200">
                        <table class="w-full text-[9px] text-center">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase">
                                <tr>
                                    <th class="px-1 py-1 text-left bg-slate-100 border-b border-slate-200">Deleg.</th>
                                    <?php foreach ($todosTiposMant as $t): ?>
                                        <th class="px-0.5 py-1 border-b border-slate-200 align-bottom">
                                            <div class="text-[6px] leading-none break-words w-10 mx-auto text-slate-500">
                                                <?= $t['nombre_completo'] ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="px-1 py-1 border-b border-slate-200 bg-slate-50 font-bold">Tot.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($delegacionesListaMant as $del):
                                    $rowTotal = 0;
                                    foreach ($todosTiposMant as $t) {
                                        $rowTotal += ($matrizMant[$del][$t['nombre_completo']] ?? 0);
                                    }
                                ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-1 py-0.5 text-left font-bold text-slate-700 bg-slate-50/50 text-[8px]">
                                            <?= substr($del, 0, 8) ?>
                                        </td>

                                        <?php foreach ($todosTiposMant as $t):
                                            $nombreTipo = $t['nombre_completo'];
                                            $val = $matrizMant[$del][$nombreTipo] ?? 0;
                                            $totalesColumnaMant[$nombreTipo] += $val;
                                            $pct = ($rowTotal > 0) ? round(($val / $rowTotal) * 100, 1) : 0;

                                            if ($val == 0) {
                                                $cls = 'text-slate-200';
                                                $content = '-';
                                            } else {
                                                $bgClass = $pct > 50 ? 'bg-purple-100 text-purple-800' : 'bg-purple-50 text-purple-700';
                                                $cls = "font-bold $bgClass";
                                                $content = "<div>{$val}</div><div class='text-[6px] font-normal opacity-70 leading-none'>{$pct}%</div>";
                                            }
                                        ?>
                                            <td class="px-0.5 py-0.5 align-middle <?= $cls ?>">
                                                <?= $content ?>
                                            </td>
                                        <?php endforeach;
                                        $granTotalMant += $rowTotal;
                                        ?>

                                        <td class="px-1 py-0.5 font-bold bg-slate-100 text-slate-800 text-[8px]">
                                            <?= $rowTotal ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                            <tfoot class="bg-slate-100 font-bold text-slate-800 border-t border-slate-200">
                                <tr class="text-[8px]">
                                    <td class="px-1 py-1 text-left">TOTAL</td>
                                    <?php foreach ($todosTiposMant as $t):
                                        $totalCol = $totalesColumnaMant[$t['nombre_completo']];
                                        $pctGlobal = ($granTotalMant > 0) ? round(($totalCol / $granTotalMant) * 100, 1) : 0;
                                    ?>
                                        <td class="px-0.5 py-1">
                                            <div class="text-slate-800"><?= $totalCol ?></div>
                                            <div class="text-[6px] text-purple-600 font-normal"><?= $pctGlobal ?>%</div>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="px-1 py-1 bg-slate-200 text-purple-900"><?= $granTotalMant ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>




        <?php if ($this->seccionActiva('maquinas')): ?>
            <div class="card-wrapper mt-8">
                <div class="card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center text-teal-600 text-xl">🏧</div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Matriz: Tipos de Máquina</h3>
                                <p class="text-xs text-slate-400">Distribución de Delegaciones Atendidas</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700"><?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?></div>
                    </div>

                    <?php
                    $totalesColumna = [];
                    foreach ($tiposMaquinaCols as $tm) {
                        $totalesColumna[$tm['nombre_tipo_maquina']] = 0;
                    }
                    $granTotalGeneral = 0;
                    ?>

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
                                            $nombreTipo = $tm['nombre_tipo_maquina'];
                                            $val = $matrizMaquina[$del][$nombreTipo] ?? 0;
                                            $rowT += $val;
                                            $totalesColumna[$nombreTipo] += $val;
                                            $cls = $val == 0 ? 'text-slate-200' : 'text-teal-700 font-bold bg-teal-50';
                                        ?>
                                            <td class="px-1 py-1 <?= $cls ?>"><?= $val ?></td>
                                        <?php endforeach;
                                        $granTotalGeneral += $rowT;
                                        ?>
                                        <td class="px-2 py-1 font-bold bg-slate-100"><?= $rowT ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-100 font-bold text-slate-800 border-t-2 border-slate-200">
                                <tr>
                                    <td class="px-2 py-2 text-left">TOTALES</td>
                                    <?php foreach ($tiposMaquinaCols as $tm): ?>
                                        <td class="px-1 py-2"><?= $totalesColumna[$tm['nombre_tipo_maquina']] ?></td>
                                    <?php endforeach; ?>
                                    <td class="px-2 py-2 bg-slate-200 text-teal-800"><?= $granTotalGeneral ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <?php if ($this->seccionActiva('estados')): ?>
            <div class="card-wrapper">
                <div class="card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">✅</div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Estado Final</h3>
                                <p class="text-xs text-slate-400">Resultado tras la intervención</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700"><?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?></div>
                    </div>

                    <div class="space-y-3">
                        <?php
                        $totalEstados = array_sum(array_column($datosEstadosFinales, 'total'));

                        foreach ($datosEstadosFinales as $e):
                            $nombre = $e['nombre_estado'];
                            $cantidad = $e['total'];
                            $pct = $totalEstados > 0 ? round(($cantidad / $totalEstados) * 100, 1) : 0;

                            // LÓGICA DE COLORES INTELIGENTE
                            // Convertimos a minúsculas para comparar fácil
                            $nombreMin = mb_strtolower($nombre);

                            if (strpos($nombreMin, 'operativ') !== false || strpos($nombreMin, 'funcional') !== false || strpos($nombreMin, 'buen') !== false) {
                                // Verde para cosas buenas
                                $colorBarra = 'bg-emerald-500';
                                $colorTexto = 'text-emerald-700';
                            } elseif (strpos($nombreMin, 'limit') !== false || strpos($nombreMin, 'parcial') !== false || strpos($nombreMin, 'observacion') !== false) {
                                // Amarillo/Naranja para advertencias
                                $colorBarra = 'bg-amber-400';
                                $colorTexto = 'text-amber-700';
                            } else {
                                // Rojo para todo lo demás (Fallas, Fuera de servicio, etc.)
                                $colorBarra = 'bg-red-500';
                                $colorTexto = 'text-red-700';
                            }
                        ?>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-bold text-slate-700"><?= $nombre ?></span>
                                    <div class="flex gap-2">
                                        <span class="text-slate-400 font-normal">(<?= $cantidad ?>)</span>
                                        <span class="font-bold <?= $colorTexto ?>"><?= $pct ?>%</span>
                                    </div>
                                </div>
                                <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full <?= $colorBarra ?>" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>









        <?php if ($this->seccionActiva('puntos_atendidos')): ?>
            <div class="card-wrapper mt-8">
                <div class="card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center text-rose-600 text-xl">📍</div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Cobertura de Puntos</h3>
                                <p class="text-xs text-slate-400">Puntos únicos atendidos por Tipo de Máquina</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700"><?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?></div>
                    </div>

                    <?php
                    $totalesColumnaPuntos = [];
                    foreach ($tiposMaquinaCols as $tm) {
                        $totalesColumnaPuntos[$tm['nombre_tipo_maquina']] = 0;
                    }
                    $granTotalPuntos = 0;
                    ?>

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
                                            $nombreTipo = $tm['nombre_tipo_maquina'];
                                            // AQUI USAMOS LA NUEVA VARIABLE $matrizPuntosTipo
                                            $val = $matrizPuntosTipo[$del][$nombreTipo] ?? 0;

                                            $rowT += $val;
                                            $totalesColumnaPuntos[$nombreTipo] += $val;

                                            // Color Rose para diferenciar
                                            $cls = $val == 0 ? 'text-slate-200' : 'text-rose-700 font-bold bg-rose-50';
                                        ?>
                                            <td class="px-1 py-1 <?= $cls ?>"><?= $val ?></td>
                                        <?php endforeach;
                                        $granTotalPuntos += $rowT;
                                        ?>
                                        <td class="px-2 py-1 font-bold bg-slate-100"><?= $rowT ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-100 font-bold text-slate-800 border-t-2 border-slate-200">
                                <tr>
                                    <td class="px-2 py-2 text-left">TOTALES</td>
                                    <?php foreach ($tiposMaquinaCols as $tm): ?>
                                        <td class="px-1 py-2"><?= $totalesColumnaPuntos[$tm['nombre_tipo_maquina']] ?></td>
                                    <?php endforeach; ?>
                                    <td class="px-2 py-2 bg-slate-200 text-rose-800"><?= $granTotalPuntos ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>



        <?php if ($this->seccionActiva('puntos_fallidos')): ?>
            <div class="card-wrapper">
                <div class="card border-l-4 border-l-red-500">
                    <div class="flex justify-between items-start mb-6 pb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600 text-xl">⚠️</div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Puntos Críticos</h3>
                                <p class="text-xs text-slate-400">Puntos Más Visitados</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700"><?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?></div>
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



        <?php if ($this->seccionActiva('calificaciones')): ?>
            <div class="card-wrapper">
                <div class="card border-l-4 border-l-amber-400">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 text-xl">⭐</div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Satisfacción</h3>
                                <p class="text-xs text-slate-400">Calidad percíbida</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700"><?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?></div>
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




        <?php if ($this->seccionActiva('repuestos')): ?>
            <div class="card-wrapper mt-4">
                <div class="card bg-slate-50 border border-slate-200 p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-lg">⚙️</div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Gestión de Repuestos</h3>
                                <p class="text-[10px] text-slate-400">Origen y Top 5 más usados</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">

                        <div class="col-span-1 flex flex-col items-center justify-center border-r border-slate-200 pr-2">
                            <?php
                            // Calcular porcentajes y cantidades para el Pastel
                            $totalRep = 0;
                            $inees = 0;
                            $prosegur = 0;

                            foreach ($datosOrigenRepuestos as $origen) {
                                $nombre = strtoupper($origen['origen']);
                                $cant = $origen['total'];
                                $totalRep += $cant;

                                if (strpos($nombre, 'INEES') !== false) $inees += $cant;
                                else $prosegur += $cant;
                            }

                            $pctInees = $totalRep > 0 ? round(($inees / $totalRep) * 100) : 0;
                            ?>

                            <div class="relative w-24 h-24 rounded-full shadow-inner border-4 border-white"
                                style="background: conic-gradient(#f97316 <?= $pctInees ?>%, #3b82f6 0);">
                                <div class="absolute inset-0 m-auto w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center text-[9px] font-bold text-slate-500 shadow-sm">
                                    Total<br><?= $totalRep ?>
                                </div>
                            </div>

                            <div class="mt-3 text-[9px] space-y-1.5 w-full px-1">
                                <div class="flex justify-between items-center border-b border-slate-100 pb-1">
                                    <div class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-orange-500"></span> INEES
                                    </div>
                                    <div class="text-right leading-none">
                                        <span class="block font-bold text-slate-700"><?= $pctInees ?>%</span>
                                        <span class="block text-[8px] text-slate-400">(<?= $inees ?> und)</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center pt-1">
                                    <div class="flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span> PROSEGUR
                                    </div>
                                    <div class="text-right leading-none">
                                        <span class="block font-bold text-slate-700"><?= 100 - $pctInees ?>%</span>
                                        <span class="block text-[8px] text-slate-400">(<?= $prosegur ?> und)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-2">
                            <div class="columns-1 md:columns-2 gap-2 space-y-2">
                                <?php foreach ($repuestosPorDelegacion as $nomDelegacion => $datos): ?>
                                    <div class="break-inside-avoid bg-white rounded-lg shadow-sm border border-slate-200 p-2">
                                        <div class="flex justify-between items-center mb-1.5 border-b border-slate-100 pb-1">
                                            <h4 class="font-bold text-slate-700 text-[9px] uppercase truncate max-w-[70%]"><?= $nomDelegacion ?></h4>
                                            <span class="bg-orange-100 text-orange-700 text-[8px] px-1.5 py-0 rounded-full font-bold">Tot: <?= $datos['total_gral'] ?></span>
                                        </div>

                                        <ul class="space-y-0.5">
                                            <?php foreach (array_slice($datos['items'], 0, 5) as $item): ?>
                                                <li class="flex justify-between items-start text-[8px] gap-2 py-0.5 border-b border-slate-50 last:border-0">
                                                    <span class="text-slate-600 text-left leading-tight flex-1 truncate">
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
                </div>
            </div>
        <?php endif; ?>







        <?php if ($this->seccionActiva('tecnicos')): ?>
            <div class="card-wrapper">
                <div class="card pt-4 pb-4 px-5">
                    <div class="flex justify-between items-start mb-3 border-b border-gray-100 pb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 text-lg">👷</div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Productividad Técnica</h3>
                                <p class="text-[10px] text-slate-400">Promedio Diario (L-V y Sáb)</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700"><?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?></div>
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
                            <?php
                            // --- ORDENAMIENTO POR PRODUCTIVIDAD L-V DESCENDENTE ---
                            usort($topTecnicos, function ($a, $b) {
                                $mediaA = ($a['dias_trabajados_lv'] > 0) ? ($a['servicios_lv'] / $a['dias_trabajados_lv']) : 0;
                                $mediaB = ($b['dias_trabajados_lv'] > 0) ? ($b['servicios_lv'] / $b['dias_trabajados_lv']) : 0;

                                // Si tienen el mismo promedio, desempatamos por total de servicios
                                if (abs($mediaA - $mediaB) < 0.01) {
                                    return $b['total_general'] - $a['total_general'];
                                }

                                return ($mediaA < $mediaB) ? 1 : -1; // Orden Descendente
                            });

                            foreach ($topTecnicos as $t):
                                $mediaLV = ($t['dias_trabajados_lv'] > 0) ? round($t['servicios_lv'] / $t['dias_trabajados_lv'], 1) : 0;
                                $mediaSab = ($t['dias_trabajados_sab'] > 0) ? round($t['servicios_sab'] / $t['dias_trabajados_sab'], 1) : 0;

                                // --- LÓGICA DE COLORES LUNES A VIERNES ---
                                if ($mediaLV > 6) {
                                    $colorLV = 'bg-emerald-500';
                                } elseif ($mediaLV >= 5) {
                                    $colorLV = 'bg-yellow-400';
                                } elseif ($mediaLV >= 4) {
                                    $colorLV = 'bg-orange-400';
                                } else {
                                    $colorLV = 'bg-red-500';
                                }

                                // --- LÓGICA DE COLORES SÁBADO ---
                                if ($mediaSab > 3) {
                                    $colorSab = 'bg-emerald-500';
                                } elseif ($mediaSab >= 2.5) {
                                    $colorSab = 'bg-yellow-400';
                                } elseif ($mediaSab >= 2) {
                                    $colorSab = 'bg-orange-400';
                                } else {
                                    $colorSab = 'bg-red-500';
                                }
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
                                                <div class="h-full <?= $colorLV ?>" style="width: <?= min(($mediaLV / 8) * 100, 100) ?>%"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-1.5 px-1">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="font-bold text-slate-700 leading-none"><?= $mediaSab ?></span>
                                            <div class="w-full bg-slate-100 rounded-full h-1 overflow-hidden">
                                                <div class="h-full <?= $colorSab ?>" style="width: <?= min(($mediaSab / 5) * 100, 100) ?>%"></div>
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












    </div>

</body>

</html>