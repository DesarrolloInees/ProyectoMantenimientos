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
            /* Un padding inferior general ayuda */
            padding-bottom: 50px;
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
            min-height: 190mm;
            /* Crecera si es necesario */
            height: auto;
            /* Importante para adaptarse */
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

        /* === ESTILOS PARA PIE DE PÁGINA === */
        /* === ESTILOS PARA PIE DE PÁGINA === */
        .footer-page {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px 32px;
            background: white;
            /* Importante que tenga fondo para que se lea bien */
            border-top: 1px solid #e2e8f0;
            z-index: 100;
            /* Z-index bajo */
        }

        @media print {
            .footer-page {
                position: fixed;
                bottom: 0;
            }

            /* Agrega margen inferior al body para que el contenido no se solape con el footer */
            body {
                margin-bottom: 20mm;
            }
        }

        /* === ESTILOS PARA NUMERACIÓN DE PÁGINAS === */
        .page-number {
            counter-increment: page;
        }

        .page-number::after {
            content: counter(page);
        }

        /* Reinicia el contador en la primera página después de la portada */
        .reset-page-counter {
            counter-reset: page 1;
        }

        .portada-container {
            min-height: 100vh;
            /* Forzar altura completa */
            height: 100vh;
        }

        /* === OCULTA EL PIE DE PÁGINA EN LA PORTADA === */
        .portada-container~.footer-page {
            display: none;
        }

        @media print {
            @page {
                margin-bottom: 20mm;
            }
        }
    </style>
</head>

<body class="p-8 text-slate-800">



    <?php if ($this->seccionActiva('portada')): ?>
        <div class="portada-container w-full rounded-none flex flex-col items-center justify-between py-12 relative mb-8 card-wrapper" style="min-height: 190mm; height: auto;">

            <div class="z-10 text-center flex-shrink-0 mb-8">

                <div class="mb-6 flex justify-center">
                    <img src="<?= $logoBase64 ?>" class="h-20 object-contain  opacity-80" alt="Logo Empresa">
                </div>


                <h1 class="text-6xl font-black text-green-600 mb-2 tracking-tight leading-none">
                    REPORTE<br>
                    <span class="text-blue-600">EJECUTIVO</span>
                </h1>

                <div class="w-24 h-2 bg-blue-600 mx-auto my-6 rounded-full"></div>

                <div class="mt-2 inline-block bg-slate-50 border border-slate-200 px-8 py-3 rounded-full">
                    <span class="text-lg font-mono font-bold text-slate-600">📅 <?= date('d/m/Y', strtotime($inicio)) ?> — <?= date('d/m/Y', strtotime($fin)) ?></span>
                </div>
            </div>

            <div class="w-full px-8 mb-4 flex-1 flex items-end">
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





    <!-- REINICIA EL CONTADOR DE PÁGINAS DESPUÉS DE LA PORTADA -->
    <div class="reset-page-counter"></div>

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
                        <div class="date-badge text-lg font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="relative h-44 w-full pl-8 pr-2 pt-6 pb-12 border-l border-b border-slate-200 mx-auto">

                        <div class="absolute inset-0 pl-8 pointer-events-none flex flex-col justify-between opacity-10">
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <div class="w-full border-t border-slate-400 border-dashed h-0"></div>
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
                        <div class="flex flex-col">
                            <span class="text-sm uppercase tracking-wide text-slate-500 font-semibold">Total Global</span>
                            <strong class="text-xl text-slate-800 tracking-tight">
                                <?= number_format($totalGlobalServicios) ?>
                            </strong>
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
                        <div class="date-badge text-lg font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
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
                                <div class="space-y-1.5">
                                    <?php foreach ($dias as $d):
                                        $pct = ($d['total'] / $maxVal) * 100;
                                        // NUEVO: Número de técnicos que trabajaron ese día
                                        $numTecnicosDia = $d['num_tecnicos'] ?? 0;
                                    ?>
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-7 font-medium text-slate-500 leading-none">
                                                <?= $d['dia_nombre'] ?>
                                                <div class="text-[7px] opacity-70"><?= date('d', strtotime($d['fecha'])) ?></div>
                                            </div>
                                            <div class="flex-1 h-1.5 bg-white rounded-full overflow-hidden border border-slate-100">
                                                <div class="h-full bg-blue-400 rounded-full" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <div class="w-4 text-right font-bold text-slate-700 text-[8px]"><?= $d['total'] ?></div>
                                            <!-- NUEVO: Icono de técnicos -->
                                            <div class="flex items-center gap-0.5 text-[7px] text-slate-400" title="Técnicos trabajando">
                                                <span>👷</span><?= $numTecnicosDia ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- NUEVA LEYENDA EXPLICATIVA -->
                    <div class="mt-3 flex justify-end items-center text-[7px] text-slate-400 bg-blue-50/30 px-2 py-1 rounded">
                        <div class="flex items-center gap-1">
                            <span>👷</span> = Técnicos activos ese día
                        </div>
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
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Servicios por Tipo de Mantenimiento </h3>
                                <p class="text-[9px] text-slate-400 leading-tight">Cantidades y porcentajes</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
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
                                    <th class="px-1 py-1 text-left bg-slate-100 border-b border-slate-200">Delegación.</th>
                                    <?php foreach ($todosTiposMant as $t): ?>
                                        <th class="px-0.5 py-1 border-b border-slate-200 align-bottom">
                                            <div class="text-[6px] leading-none break-words w-10 mx-auto text-slate-500">
                                                <?= $t['nombre_completo'] ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="px-1 py-1 border-b border-slate-200 bg-slate-50 font-bold">Total.</th>
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
                                            <div class="truncate hover:text-clip hover:whitespace-normal transition-all duration-200"
                                                title="<?= htmlspecialchars($del, ENT_QUOTES) ?>">
                                                <?= $del ?>
                                            </div>
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
                                <h3 class="text-lg font-bold text-slate-800">Servicios por Tipo de Máquina</h3>
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
                <div class="card p-3"> <!-- Reducir padding -->
                    <div class="flex justify-between items-start mb-3"> <!-- Reducir margen inferior -->
                        <div class="flex items-center gap-2"> <!-- Reducir gap -->
                            <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 text-lg">✅</div> <!-- Reducir tamaño -->
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Estado Final de la Máquina</h3> <!-- Texto más pequeño -->
                                <p class="text-[10px] text-slate-400 leading-tight">Resultado tras la intervención</p> <!-- Texto más pequeño -->
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="space-y-2"> <!-- Menor espacio entre elementos -->
                        <?php
                        $totalEstados = array_sum(array_column($datosEstadosFinales, 'total'));

                        foreach ($datosEstadosFinales as $e):
                            $nombre = $e['nombre_estado'];
                            $cantidad = $e['total'];
                            $pct = $totalEstados > 0 ? round(($cantidad / $totalEstados) * 100, 1) : 0;

                            // LÓGICA DE COLORES INTELIGENTE
                            $nombreMin = mb_strtolower($nombre);

                            if (strpos($nombreMin, 'operativ') !== false || strpos($nombreMin, 'funcional') !== false || strpos($nombreMin, 'buen') !== false) {
                                $colorBarra = 'bg-emerald-500';
                                $colorTexto = 'text-emerald-700';
                            } elseif (strpos($nombreMin, 'limit') !== false || strpos($nombreMin, 'parcial') !== false || strpos($nombreMin, 'observacion') !== false) {
                                $colorBarra = 'bg-amber-400';
                                $colorTexto = 'text-amber-700';
                            } else {
                                $colorBarra = 'bg-red-500';
                                $colorTexto = 'text-red-700';
                            }
                        ?>
                            <div>
                                <div class="flex justify-between text-[10px] mb-0.5"> <!-- Texto más pequeño y menos margen -->
                                    <span class="font-bold text-slate-700 truncate pr-2"><?= $nombre ?></span> <!-- Permitir truncado -->
                                    <div class="flex gap-1 whitespace-nowrap"> <!-- Menor gap -->
                                        <span class="text-slate-400 font-normal">(<?= $cantidad ?>)</span>
                                        <span class="font-bold <?= $colorTexto ?>"><?= $pct ?>%</span>
                                    </div>
                                </div>
                                <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden"> <!-- Barra más delgada -->
                                    <div class="h-full <?= $colorBarra ?>" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>




        <?php if ($this->seccionActiva('puntos_atendidos')): ?>

            <?php
            // ==========================================
            // 1. PRE-CÁLCULO DE TOTALES
            // ==========================================
            // Calculamos esto PRIMERO para tener el número 919 listo para el encabezado

            $totalesColumnaPuntos = [];
            $granTotalPuntos = 0; // Esta variable acumulará el 919

            // Inicializamos totales en 0
            foreach ($tiposMaquinaCols as $tm) {
                $totalesColumnaPuntos[$tm['nombre_tipo_maquina']] = 0;
            }

            // Recorremos los datos para sumar
            foreach ($delegacionesListaMaquina as $del) {
                foreach ($tiposMaquinaCols as $tm) {
                    $nombreTipo = $tm['nombre_tipo_maquina'];
                    // Obtenemos el valor de la matriz o 0 si no existe
                    $val = $matrizPuntosTipo[$del][$nombreTipo] ?? 0;

                    // Sumamos
                    $totalesColumnaPuntos[$nombreTipo] += $val;
                    $granTotalPuntos += $val;
                }
            }
            ?>

            <div class="card-wrapper mt-3">
                <div class="card p-2">
                    <div class="flex items-center justify-between mb-2 pb-1 border-b border-slate-200">
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 rounded bg-rose-50 flex items-center justify-center text-rose-500 text-[10px]">📍</div>
                            <h3 class="text-xs font-bold text-slate-800 truncate">Puntos Atendidos</h3>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-1">
                            <div class="flex items-center px-1 py-0.5 bg-white border border-slate-200 rounded text-[9px]">
                                <span class="text-[9px] mr-0.5">🛠️</span>
                                <span class="font-bold text-slate-800"><?= number_format($totalGlobalServicios ?? 0) ?></span>
                                <span class="text-[6px] text-slate-500 ml-0.5">Servicios Atendidos</span>
                            </div>

                            <div class="flex items-center px-1 py-0.5 bg-white border border-rose-200 rounded text-[9px]">
                                <span class="text-[9px] mr-0.5">📍</span>
                                <span class="font-bold text-slate-800"><?= number_format($granTotalPuntos) ?></span>
                                <span class="text-[6px] text-rose-500 ml-0.5">Puntos Atendidos</span>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-sm border border-slate-200">
                        <table class="w-full text-[7px] text-center border-collapse">
                            <thead class="bg-slate-50 text-slate-600 font-semibold">
                                <tr>
                                    <th class="px-1 py-2 text-left bg-slate-100 border-b border-slate-200 align-bottom w-24">
                                        Delegación
                                    </th>

                                    <?php foreach ($tiposMaquinaCols as $tm): ?>
                                        <th class="px-0.5 py-2 border-b border-slate-200 align-bottom w-[50px]">
                                            <div class="whitespace-normal break-words text-[8px] leading-[1.1] uppercase text-center flex items-end justify-center h-full min-h-[25px]">
                                                <?= $tm['nombre_tipo_maquina'] ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>

                                    <th class="px-1 py-2 border-b border-slate-200 bg-slate-50 font-bold align-bottom w-[40px]">
                                        <div class="flex items-end justify-center h-full">TOTAL</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($delegacionesListaMaquina as $del):
                                    $rowTotal = 0;
                                ?>
                                    <tr class="hover:bg-slate-50 border-b border-slate-100 last:border-0">
                                        <td class="px-1 py-1 text-left font-bold text-slate-700 bg-slate-50/50 truncate max-w-[100px]" title="<?= $del ?>">
                                            <?= $del ?>
                                        </td>

                                        <?php foreach ($tiposMaquinaCols as $tm):
                                            $nombreTipo = $tm['nombre_tipo_maquina'];
                                            $val = $matrizPuntosTipo[$del][$nombreTipo] ?? 0;
                                            $rowTotal += $val;

                                            // Estilo: Verde si hay datos, gris claro si es 0
                                            $bg = $val > 0 ? 'bg-green-50/40 text-green-700 font-bold' : 'text-slate-300';
                                        ?>
                                            <td class="px-0.5 py-1 <?= $bg ?> border-l border-slate-50 border-dashed">
                                                <?= $val > 0 ? $val : '-' ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <td class="px-1 py-1 font-bold bg-slate-100 text-slate-800 border-l border-slate-200">
                                            <?= $rowTotal ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                            <tfoot class="bg-slate-100 font-bold text-slate-800 border-t-2 border-slate-200">
                                <tr>
                                    <td class="px-1 py-1 text-left text-[8px]">TOTALES</td>
                                    <?php foreach ($tiposMaquinaCols as $tm):
                                        $val = $totalesColumnaPuntos[$tm['nombre_tipo_maquina']];
                                    ?>
                                        <td class="px-0.5 py-1 border-l border-slate-200 border-dashed">
                                            <?= $val ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="px-1 py-1 bg-slate-200 text-rose-800 border-l border-slate-300">
                                        <?= $granTotalPuntos ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-1 pt-1 border-t border-slate-100 flex justify-between items-center">
                        <p class="text-[6px] text-slate-400">(-) = Sin actividad</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>



        <?php if ($this->seccionActiva('puntos_fallidos')): ?>
            <div class="card-wrapper mt-4">
                <div class="card border-t-2 border-t-blue-500 p-3">
                    <div class="flex justify-between items-center mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-base border border-blue-100">
                                📍
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Puntos Más Frecuentes</h3>
                                <p class="text-[9px] text-slate-400 leading-tight">Global por delegación</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="columns-2 md:columns-3 gap-3 space-y-3">
                        <?php foreach ($puntosVisitadosAgrupados as $delegacion => $puntos): ?>
                            <div class="break-inside-avoid bg-slate-50/50 rounded border border-slate-200 p-2">

                                <!-- Encabezado delegación -->
                                <div class="flex justify-between items-center mb-1.5">
                                    <h4 class="font-bold text-slate-700 text-[9px] uppercase truncate max-w-[70%]" title="<?= $delegacion ?>">
                                        <?= strlen($delegacion) > 15 ? substr($delegacion, 0, 13) . '...' : $delegacion ?>
                                    </h4>
                                    <span class="text-[8px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">
                                        <?= count($puntos) ?> pts
                                    </span>
                                </div>

                                <!-- Lista de puntos -->
                                <div class="space-y-1.5">
                                    <?php
                                    $maxLocal = $puntos[0]['total'];
                                    $puntosMostrar = array_slice($puntos, 0, 5); // Mostrar solo top 5 por delegación

                                    foreach ($puntosMostrar as $p):
                                        $ancho = ($p['total'] / $maxLocal) * 100;
                                    ?>
                                        <div class="flex flex-col">
                                            <div class="flex justify-between items-center text-[8px] mb-0.5">
                                                <div class="flex-1 min-w-0 pr-1">
                                                    <div class="font-semibold text-slate-600 truncate leading-tight" title="<?= $p['punto'] ?>">
                                                        <?= strlen($p['punto']) > 20 ? substr($p['punto'], 0, 18) . '...' : $p['punto'] ?>
                                                    </div>
                                                    <div class="text-[6px] text-slate-400 uppercase truncate">
                                                        <?= $p['tipo'] ?>
                                                    </div>
                                                </div>
                                                <div class="font-bold text-blue-600 text-[9px] shrink-0">
                                                    <?= $p['total'] ?>
                                                </div>
                                            </div>

                                            <!-- Barra de progreso -->
                                            <div class="w-full h-0.5 bg-slate-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-blue-500 rounded-full" style="width: <?= $ancho ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- Mostrar si hay más puntos -->
                                    <?php if (count($puntos) > 5): ?>
                                        <div class="text-center text-[7px] text-slate-400 pt-1 border-t border-slate-100">
                                            +<?= count($puntos) - 5 ?> puntos más
                                        </div>
                                    <?php endif; ?>
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
                                <h3 class="text-base font-bold text-slate-800">Repuestos Usados en Servicios</h3>
                                <p class="text-[10px] text-slate-400">Origen y Top 5 más usados</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700"><?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?></div>
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
                                            <span class="bg-orange-100 text-orange-700 text-[8px] px-1.5 py-0 rounded-full font-bold">Total: <?= $datos['total_gral'] ?></span>
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



        <?php if ($this->seccionActiva('calificaciones')): ?>
            <div class="card-wrapper mt-4">

                <div class="flex justify-between items-center mb-4 px-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 text-lg border border-amber-200">⭐</div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Calidad de Atención</h3>
                            <p class="text-[10px] text-slate-500">Satisfacción del cliente por Delegación</p>
                        </div>
                    </div>
                    <div class="date-badge text-lg font-bold text-slate-700">
                        <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                    </div>
                </div>

                <div class="columns-1 md:columns-2 lg:columns-3 gap-4 space-y-4">
                    <?php foreach ($calificacionesAgrupadas as $delegacion => $datos): ?>
                        <div class="break-inside-avoid bg-white rounded-xl shadow-sm border border-slate-200 p-3 mb-4">

                            <div class="flex justify-between items-center mb-2 pb-1 border-b border-slate-100">
                                <h4 class="font-bold text-slate-700 text-[10px] uppercase tracking-wide">
                                    <?= $delegacion ?>
                                </h4>
                                <span class="bg-amber-50 text-amber-700 text-[8px] font-bold px-1.5 py-0.5 rounded border border-amber-100">
                                    Total: <?= $datos['total_zona'] ?>
                                </span>
                            </div>

                            <div class="space-y-2">
                                <?php foreach ($datos['items'] as $item):
                                    $totalZona = $datos['total_zona'];
                                    $pct = ($totalZona > 0) ? round(($item['total'] / $totalZona) * 100, 1) : 0;

                                    // Color dinámico según la calificación (Opcional, pero se ve pro)
                                    // Asumiendo que 'Excelente' o 'Bueno' son los mejores
                                    $colorBarra = 'bg-amber-400';
                                    $colorTexto = 'text-amber-700';

                                    $nombreMin = mb_strtolower($item['nombre']);
                                    if (strpos($nombreMin, 'mal') !== false || strpos($nombreMin, 'pésim') !== false) {
                                        $colorBarra = 'bg-red-400';
                                        $colorTexto = 'text-red-700';
                                    } elseif (strpos($nombreMin, 'excelente') !== false) {
                                        $colorBarra = 'bg-emerald-400';
                                        $colorTexto = 'text-emerald-700';
                                    }
                                ?>
                                    <div>
                                        <div class="flex justify-between text-[9px] mb-0.5">
                                            <span class="font-medium text-slate-600"><?= $item['nombre'] ?></span>
                                            <div class="flex gap-2">
                                                <span class="text-slate-400">(<?= $item['total'] ?>)</span>
                                                <span class="font-bold <?= $colorTexto ?>"><?= $pct ?>%</span>
                                            </div>
                                        </div>
                                        <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full <?= $colorBarra ?>" style="width: <?= $pct ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        <?php endif; ?>



        <?php if ($this->seccionActiva('puntos_mas_fallidos')): ?>
            <div class="card-wrapper mt-4">
                <div class="card bg-rose-50 border border-rose-200 p-4">

                    <div class="flex justify-between items-start mb-4 border-b border-rose-200 pb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-white border border-rose-200 flex items-center justify-center text-rose-600 text-lg shadow-sm">⚠️</div>
                            <div>
                                <h3 class="text-base font-bold text-rose-900">Puntos Fallidos </h3>
                                <p class="text-[10px] text-rose-600">Puntos con <strong>>= 2 Visitas Fallidas</strong> </p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="columns-1 md:columns-2 lg:columns-3 gap-4 space-y-4">

                        <?php foreach ($puntosFallidosPorDelegacion as $nomDelegacion => $datos): ?>
                            <div class="break-inside-avoid bg-white rounded-lg shadow-sm border border-rose-100 p-3">

                                <div class="flex justify-between items-center mb-2 border-b border-slate-50 pb-1">
                                    <h4 class="font-bold text-slate-700 text-[10px] uppercase truncate max-w-[70%]">
                                        <?= $nomDelegacion ?>
                                    </h4>
                                    <span class="bg-rose-100 text-rose-700 text-[9px] px-2 py-0.5 rounded-full font-bold">
                                        Tot: <?= $datos['total_zona'] ?>
                                    </span>
                                </div>

                                <ul class="space-y-2">
                                    <?php
                                    // Top 5 puntos críticos de esta zona
                                    foreach (array_slice($datos['items'], 0, 5) as $item):
                                        // Barra visual (Escala: si tiene 5 fallos o más se llena la barra)
                                        $ancho = min(($item['cantidad'] / 5) * 100, 100);
                                    ?>
                                        <li class="flex flex-col">
                                            <div class="flex justify-between text-[9px] mb-0.5">
                                                <span class="text-slate-600 font-medium w-full pr-2 break-all leading-tight" title="<?= $item['nombre'] ?>">
                                                    <?= $item['nombre'] ?>
                                                </span>
                                                <span class="font-bold text-rose-600">
                                                    <?= $item['cantidad'] ?>
                                                </span>
                                            </div>
                                            <div class="w-full h-1 bg-rose-50 rounded-full overflow-hidden">
                                                <div class="h-full bg-rose-500" style="width: <?= $ancho ?>%"></div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>

                    </div>

                    <?php if (empty($puntosFallidosPorDelegacion)): ?>
                        <div class="text-center py-6 text-xs text-slate-400 italic">
                            Excelente gestión: Ningún punto supera los 2 servicios fallidos en este periodo.
                        </div>
                    <?php endif; ?>

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
                                <h3 class="text-base font-bold text-slate-800">Promedio Servicio por Técnico</h3>
                                <p class="text-[10px] text-slate-400">Servicios por Tipo y Promedios</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <table class="w-full text-[9px]">
                        <thead>
                            <tr class="text-slate-400 text-[8px] uppercase tracking-wider text-left border-b border-slate-200 align-bottom">
                                <th class="pb-2 font-semibold pl-1">Técnico</th>

                                <?php foreach ($todosTiposMant as $tm): ?>
                                    <th class="pb-2 text-center font-semibold text-emerald-600 bg-emerald-50/50 px-1 border-r border-white align-bottom">

                                        <div class="w-16 mx-auto whitespace-normal break-words leading-none text-[7px] flex items-end justify-center h-full">
                                            <?= $tm['nombre_completo'] ?>
                                        </div>
                                    </th>
                                <?php endforeach; ?>

                                <th class="pb-2 text-center font-semibold w-12 border-l border-slate-100 align-bottom">
                                    <div class="w-10 mx-auto whitespace-normal leading-none">Media<br>L-V</div>
                                </th>
                                <th class="pb-2 text-center font-semibold w-12 align-bottom">
                                    <div class="w-10 mx-auto whitespace-normal leading-none">Media<br>Sáb</div>
                                </th>
                                <th class="pb-2 text-center font-semibold w-10 text-slate-600 bg-slate-50 align-bottom">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php
                            // Ordenamiento por productividad
                            usort($topTecnicos, function ($a, $b) {
                                $mediaA = ($a['dias_trabajados_lv'] > 0) ? ($a['servicios_lv'] / $a['dias_trabajados_lv']) : 0;
                                $mediaB = ($b['dias_trabajados_lv'] > 0) ? ($b['servicios_lv'] / $b['dias_trabajados_lv']) : 0;
                                if (abs($mediaA - $mediaB) < 0.01) return $b['total_general'] - $a['total_general'];
                                return ($mediaA < $mediaB) ? 1 : -1;
                            });

                            foreach ($topTecnicos as $t):
                                $mediaLV = ($t['dias_trabajados_lv'] > 0) ? round($t['servicios_lv'] / $t['dias_trabajados_lv'], 1) : 0;
                                $mediaSab = ($t['dias_trabajados_sab'] > 0) ? round($t['servicios_sab'] / $t['dias_trabajados_sab'], 1) : 0;

                                // --- LÓGICA DE COLORES LUNES A VIERNES (CORREGIDA) ---
                                if ($mediaLV > 6) {
                                    $colorLV = 'bg-emerald-500'; // Verde (> 6)
                                } elseif ($mediaLV >= 5) {
                                    $colorLV = 'bg-yellow-400';  // Amarillo (5 - 6)
                                } elseif ($mediaLV >= 4) {
                                    $colorLV = 'bg-orange-400';  // Naranja (4 - 5)
                                } else {
                                    $colorLV = 'bg-red-500';     // Rojo (< 4)
                                }

                                // --- LÓGICA DE COLORES SÁBADO (MEDIA JORNADA) ---
                                if ($mediaSab > 3) {
                                    $colorSab = 'bg-emerald-500'; // Verde (> 3)
                                } elseif ($mediaSab >= 2.5) {
                                    $colorSab = 'bg-yellow-400';  // Amarillo (2.5 - 3)
                                } elseif ($mediaSab >= 2) {
                                    $colorSab = 'bg-orange-400';  // Naranja (2 - 2.5)
                                } else {
                                    $colorSab = 'bg-red-500';     // Rojo (< 2)
                                }
                            ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-1.5 pl-1 text-slate-700 font-bold truncate max-w-[100px]">
                                        <?= $t['nombre_tecnico'] ?>
                                    </td>

                                    <?php foreach ($todosTiposMant as $tm):
                                        $cant = $t['desglose'][$tm['nombre_completo']] ?? 0;
                                        $styleCell = $cant > 0 ? 'text-slate-700 font-bold' : 'text-slate-200';
                                    ?>
                                        <td class="py-1 text-center <?= $styleCell ?> border-r border-slate-50 text-[8px]">
                                            <?= $cant > 0 ? $cant : '-' ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="py-1 px-1 border-l border-slate-100">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="font-bold text-slate-700"><?= $mediaLV ?></span>
                                            <div class="w-1.5 h-1.5 rounded-full <?= $colorLV ?>"></div>
                                        </div>
                                    </td>

                                    <td class="py-1 px-1">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="font-bold text-slate-700"><?= $mediaSab ?></span>
                                            <div class="w-1.5 h-1.5 rounded-full <?= $colorSab ?>"></div>
                                        </div>
                                    </td>

                                    <td class="py-1 px-1 text-center font-black text-slate-800 bg-slate-50/50">
                                        <?= $t['total_general'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- === PIE DE PÁGINA GLOBAL (Aparece en todas las páginas excepto portada) === -->
    <div class="footer-page">
        <div class="flex justify-between items-center">
            <div class="uppercase tracking-[0.4em] text-[10px] font-bold text-slate-400">
                Documento Confidencial
            </div>

            <div class="text-[10px] font-bold text-slate-500">
                <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
            </div>

            <!-- NUMERACIÓN DE PÁGINA -->
            <div class="text-[10px] font-mono font-bold text-slate-600 bg-slate-50 px-3 py-1 rounded border border-slate-200">
                Página <span class="page-number"></span>
            </div>
        </div>
    </div>

</body>

</html>