<?php
// Lista de todas las secciones posibles (sin incluir la portada)
$todasLasSecciones = [
    'delegaciones',
    'tendencias',
    'mantenimiento',
    'maquinas',
    'estados',
    'puntos_atendidos',
    'puntos_fallidos',
    'repuestos',
    'calificaciones',
    'tecnicos',
    'costos',
    'balance'
];

$seccionesActivasCount = 0;
foreach ($todasLasSecciones as $seccion) {
    if ($this->seccionActiva($seccion)) {
        $seccionesActivasCount++;
    }
}

// Si solo hay 1 sección activa, activamos el "Modo Único"
$esModoUnico = ($seccionesActivasCount === 1);
?>

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
            /* ELIMINASTE EL PADDING-BOTTOM AQUÍ */
        }

        /* Configuramos la página en formato horizontal A4 */
        @page {
            size: A4 landscape;
            margin: 10mm;
            /* Dejamos margen para que respire la impresión */
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .card-wrapper {
            page-break-after: always;
            break-after: page;
            /* 🔥 ELIMINAMOS EL AVOID: Dejamos que el navegador decida dónde cortar de forma natural si es muy largo */
            display: block;
            width: 100%;
            /* 🔥 Reducimos la altura base. 130mm * 1.3 de zoom = 169mm (Cabe perfecto en A4) */
            min-height: 130mm;
            box-sizing: border-box;
            margin-bottom: 2rem;
        }

        /* 🔥 Si es la última o única gráfica, evitamos que genere una página extra al final */
        .card-wrapper:last-child {
            page-break-after: auto !important;
            break-after: auto !important;
            margin-bottom: 0;
        }

        .card {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #f3f4f6;
            width: 100%;
            min-height: 130mm;
            /* Igualamos al wrapper */
            box-sizing: border-box;
            padding: 32px;
            zoom: 1.3;
        }

        /* === ESTILO PARA VISTA ÚNICA (GRÁFICAS GRANDES) === */
        .modo-unico-activo .card-wrapper {
            page-break-after: auto !important;
            break-after: auto !important;
        }

        .modo-unico-activo .card {
            /* 1.6 era demasiado colosal para A4. Lo dejamos en 1.4 para que se vea gigante pero no rompa la hoja */
            zoom: 1.4;
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

        /* === ESTILO PARA VISTA ÚNICA (GRÁFICAS GRANDES) === */
        .modo-unico-activo .card {
            /* El zoom funciona perfecto en navegadores basados en Chromium (Chrome/Edge) */
            zoom: 1.6;
            /* Aumenta el tamaño un 160%. Puedes ajustar este valor (ej. 1.8 o 2.0) */
        }

        /* Esto hace que cada sección inicie en una página nueva, 
       EXCEPTO la primera que se renderice */
        .seccion-reporte {
            page-break-before: always;
            break-before: page;
        }

        .seccion-reporte:first-child,
        .portada-container+.seccion-reporte {
            page-break-before: avoid;
            break-before: auto;
        }
    </style>
</head>

<body class="p-8 text-slate-800">



    <?php if ($this->seccionActiva('portada')): ?>
        <div class="portada-container w-full rounded-none flex flex-col items-center justify-between py-12 relative mb-8 card-wrapper"
            style="min-height: 190mm; height: auto;">

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
                    <span class="text-lg font-mono font-bold text-slate-600">📅 <?= date('d/m/Y', strtotime($inicio)) ?> —
                        <?= date('d/m/Y', strtotime($fin)) ?></span>
                </div>
            </div>

            <div class="w-full px-8 mb-4 flex-1 flex items-end">
            </div>

            <div class="w-full px-8 mb-4 flex-1 flex items-end">
                <div class="grid grid-cols-4 gap-4 w-full items-stretch">

                    <div
                        class="bg-white border border-slate-200 p-4 rounded-xl text-left flex flex-col shadow-sm min-h-[12rem]">
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

                    <div
                        class="bg-white border border-slate-200 p-4 rounded-xl text-left flex flex-col shadow-sm min-h-[12rem]">
                        <div class="text-xs uppercase font-bold text-slate-400 mb-2 border-b border-slate-100 pb-2">
                            Promedio / Téc. <span class="normal-case opacity-50">(Diario)</span>
                        </div>
                        <div class="space-y-1.5 text-[10px] flex-1">
                            <?php foreach ($kpisDelegacion as $k): ?>
                                <div class="flex justify-between items-center py-1 border-b border-slate-50 last:border-0">
                                    <span class="text-slate-600"><?= $k['delegacion'] ?></span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500"
                                                style="width: <?= ($k['promedio_diario'] / 8) * 100 ?>%"></div>
                                        </div>
                                        <span
                                            class="font-bold text-emerald-600 w-6 text-right"><?= $k['promedio_diario'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div
                        class="bg-white border border-slate-200 p-4 rounded-xl text-left flex flex-col shadow-sm min-h-[12rem]">
                        <div class="flex justify-between items-start border-b border-slate-100 pb-2 mb-2">
                            <div class="text-xs uppercase font-bold text-slate-400">Novedades</div>
                            <div class="text-3xl font-black text-red-500">
                                <?= number_format($datosNovedad['con_novedad'] ?? 0) ?>
                            </div>
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

                    <div
                        class="bg-white border border-slate-200 p-4 rounded-xl text-left flex flex-col shadow-sm min-h-[12rem]">
                        <div class="text-xs uppercase font-bold text-slate-400 mb-3 border-b border-slate-100 pb-2">
                            Delegaciones</div>
                        <div class="flex flex-wrap gap-2 content-start">
                            <?php foreach ($kpisDelegacion as $k): ?>
                                <span
                                    class="bg-slate-50 border border-slate-200 text-slate-600 px-2 py-1 rounded text-[9px] font-medium">
                                    <?= $k['delegacion'] ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>








    <?php endif; ?>


    <div class="flex flex-col w-full">

        <?php if ($this->seccionActiva('delegaciones')): ?>
            <div class="card-wrapper mt-4">
                <div class="card p-3">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-lg">
                                📊</div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Participación por Delegación</h3>
                                <p class="text-[9px] text-slate-400">Volumen, % Global y Fuerza Técnica</p>
                            </div>
                        </div>
                        <div class="date-badge text-lg font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="relative h-96 w-full pl-8 pr-2 pt-6 pb-12 border-l border-b border-slate-200 mx-auto">

                        <div class="absolute inset-0 pl-8 pointer-events-none flex flex-col justify-between opacity-10">
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <div class="w-full border-t border-slate-400 border-dashed h-0"></div>
                            <?php endfor; ?>
                            <div class="w-full h-0"></div>
                        </div>

                        <div
                            class="absolute left-0 top-0 h-full flex flex-col justify-between text-[8px] text-slate-300 font-bold pr-2 pb-12 pt-6">
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

                                        <div
                                            class="text-[8px] font-bold text-indigo-600 bg-indigo-50 px-1 rounded-sm mt-0.5 border border-indigo-100">
                                            <?= $k['porcentaje'] ?>%
                                        </div>

                                        <div class="flex items-center gap-0.5 mt-0.5 text-[7px] text-slate-400"
                                            title="Técnicos Activos">
                                            <span>👷</span> <?= $numTecnicos ?>
                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex justify-between items-center text-[8px] text-slate-400 bg-slate-50/50 p-1.5 rounded-lg border border-slate-100">
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
            <div class="card-wrapper w-full h-fit"
                style="break-after: page !important; page-break-after: always !important;">
                <div class="card p-5 flex flex-col justify-between">

                    <div class="flex justify-between items-start mb-4 border-b border-gray-100 pb-3 shrink-0">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-xl">
                                📅</div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 leading-tight">Evolución Diaria de Servicios
                                </h3>
                                <p class="text-[10px] text-slate-400">Detalle por semanas operativas del período</p>
                            </div>
                        </div>
                        <div class="date-badge text-xs font-bold text-slate-700 px-3 py-1.5">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-xs flex-1">
                        <?php
                        $maxVal = 1;
                        foreach ($semanasGroup as $dias) {
                            foreach ($dias as $d) {
                                $maxVal = max($maxVal, $d['total']);
                            }
                        }

                        foreach ($semanasGroup as $nombreSemana => $dias): ?>
                            <div
                                class="bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-sm flex flex-col justify-between h-fit break-inside-avoid">
                                <h4
                                    class="font-bold text-blue-600 uppercase mb-2 border-b border-blue-100 pb-1 text-[10px] tracking-wider shrink-0">
                                    <?= $nombreSemana ?>
                                </h4>

                                <div class="space-y-1.5 flex-1 flex flex-col justify-center">
                                    <?php foreach ($dias as $d):
                                        $pct = ($d['total'] / $maxVal) * 100;
                                        $numTecnicosDia = $d['num_tecnicos'] ?? 0;
                                        ?>
                                        <div class="flex items-center gap-2">
                                            <div class="w-9 font-bold text-slate-600 leading-none text-[10px]">
                                                <?= $d['dia_nombre'] ?>
                                                <div class="text-[8px] font-normal text-slate-400 mt-0.5">
                                                    <?= date('d', strtotime($d['fecha'])) ?>
                                                </div>
                                            </div>

                                            <div
                                                class="flex-1 h-2.5 bg-white rounded-full overflow-hidden border border-slate-200 shadow-inner">
                                                <div class="h-full bg-gradient-to-r from-blue-400 to-blue-500 rounded-full"
                                                    style="width: <?= $pct ?>%"></div>
                                            </div>

                                            <div class="w-5 text-right font-black text-slate-700 text-[10px]">
                                                <?= $d['total'] ?>
                                            </div>

                                            <div class="flex items-center gap-0.5 text-[9px] text-slate-500 bg-slate-200/50 px-1 py-0.5 rounded"
                                                title="Técnicos trabajando">
                                                <span>👷</span>
                                                <span class="font-bold"><?= $numTecnicosDia ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div
                        class="mt-4 flex justify-end items-center text-[10px] text-slate-500 bg-blue-50/50 px-3 py-1.5 rounded-lg border border-blue-100 shrink-0">
                        <div class="flex items-center gap-1 font-medium">
                            <span>👷</span> = Cantidad de técnicos activos operando ese día
                        </div>
                    </div>

                </div>
            </div>
        <?php endif; ?>



        <?php if ($this->seccionActiva('mantenimiento')): ?>
            <div class="card-wrapper w-full"
                style="page-break-before: always !important; break-before: page !important; margin-top: 0 !important; align-self: flex-start !important;">
                <div class="card py-3 px-4 flex flex-col justify-between">

                    <div class="flex justify-between items-start mb-2 border-b border-gray-100 pb-2 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 text-base">
                                🔧
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Servicios por Tipo de
                                    Mantenimiento</h3>
                                <p class="text-[9px] text-slate-400 leading-none">Distribución de cantidades y porcentajes
                                    por delegación</p>
                            </div>
                        </div>
                        <div class="date-badge text-[9px] font-bold text-slate-700 px-2 py-1">
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
                        <table class="w-full text-[9px] text-center border-collapse">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider shrink-0">
                                <tr>
                                    <th class="px-2 py-1 text-left bg-slate-100 border-b border-slate-200 text-[9px] w-36">
                                        Delegación
                                    </th>
                                    <?php foreach ($todosTiposMant as $t): ?>
                                        <th class="px-1 py-1 border-b border-slate-200 align-bottom">
                                            <div class="text-[8px] leading-tight break-words w-14 mx-auto text-slate-500">
                                                <?= $t['nombre_completo'] ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                    <th
                                        class="px-2 py-1 border-b border-slate-200 bg-slate-50 font-bold text-slate-700 text-[9px]">
                                        Total</th>
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
                                        <td
                                            class="px-2.5 py-0.5 text-left font-bold text-slate-700 bg-slate-50/50 text-[9px] border-r border-slate-100">
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
                                                $bgClass = $pct > 50 ? 'bg-purple-100 text-purple-900' : 'bg-purple-50 text-purple-700';
                                                $cls = "font-black $bgClass";
                                                $content = "<div class='text-[9px] leading-tight'>{$val}</div><div class='text-[7px] font-normal opacity-70 leading-none'>{$pct}%</div>";
                                            }
                                            ?>
                                            <td class="px-1 py-0.5 align-middle <?= $cls ?>">
                                                <?= $content ?>
                                            </td>
                                        <?php endforeach;
                                        $granTotalMant += $rowTotal;
                                        ?>

                                        <td class="px-2.5 py-0.5 font-bold bg-slate-100 text-slate-800 text-[9px]">
                                            <?= $rowTotal ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                            <tfoot class="bg-slate-100 font-bold text-slate-800 border-t border-slate-200 shrink-0">
                                <tr class="text-[9px]">
                                    <td class="px-2.5 py-1 text-left font-bold">TOTAL</td>
                                    <?php foreach ($todosTiposMant as $t):
                                        $totalCol = $totalesColumnaMant[$t['nombre_completo']];
                                        $pctGlobal = ($granTotalMant > 0) ? round(($totalCol / $granTotalMant) * 100, 1) : 0;
                                        ?>
                                        <td class="px-1 py-1">
                                            <div class="text-slate-800 text-[9px]"><?= $totalCol ?></div>
                                            <div class="text-[7px] text-purple-600 font-normal leading-none"><?= $pctGlobal ?>%
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="px-2.5 py-1 bg-slate-200 text-purple-900 font-black text-xs">
                                        <?= $granTotalMant ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        <?php endif; ?>




        <?php if ($this->seccionActiva('maquinas')): ?>
            <div class="card-wrapper">
                <div class="card p-6 flex flex-col justify-between">

                    <div class="flex justify-between items-start mb-4 border-b border-gray-100 pb-3 shrink-0">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center text-teal-600 text-xl">
                                🏧</div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 leading-tight">Servicios por Tipo de Máquina
                                </h3>
                                <p class="text-xs text-slate-400">Distribución de Delegaciones Atendidas</p>
                            </div>
                        </div>
                        <div class="date-badge text-xs font-bold text-slate-700 px-3 py-1.5">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <?php
                    $totalesColumna = [];
                    foreach ($tiposMaquinaCols as $tm) {
                        $totalesColumna[$tm['nombre_tipo_maquina']] = 0;
                    }
                    $granTotalGeneral = 0;
                    ?>

                    <div class="overflow-hidden rounded-xl border border-slate-200 flex-1">
                        <table class="w-full text-xs text-center border-collapse h-full">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider shrink-0">
                                <tr>
                                    <th class="px-3 py-2 text-left bg-slate-100 border-b border-slate-200 w-40 text-[10px]">
                                        Delegación
                                    </th>

                                    <?php foreach ($tiposMaquinaCols as $tm): ?>
                                        <th class="px-1 py-2 border-b border-slate-200 align-bottom"
                                            title="<?= $tm['nombre_tipo_maquina'] ?>">
                                            <div
                                                class="break-words text-[9px] w-16 mx-auto leading-tight font-extrabold text-slate-600">
                                                <?= $tm['nombre_tipo_maquina'] ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>

                                    <th
                                        class="px-3 py-2 border-b border-slate-200 bg-slate-100 font-bold text-slate-700 text-[10px] w-16">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($delegacionesListaMaquina as $del):
                                    $rowT = 0; ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td
                                            class="px-3 py-1.5 text-left font-bold text-slate-700 bg-slate-50/50 text-xs border-r border-slate-100">
                                            <div class="truncate max-w-[150px]" title="<?= $del ?>">
                                                <?= $del ?>
                                            </div>
                                        </td>

                                        <?php foreach ($tiposMaquinaCols as $tm):
                                            $nombreTipo = $tm['nombre_tipo_maquina'];
                                            $val = $matrizMaquina[$del][$nombreTipo] ?? 0;
                                            $rowT += $val;
                                            $totalesColumna[$nombreTipo] += $val;
                                            $cls = $val == 0 ? 'text-slate-200 font-normal' : 'text-teal-800 font-black bg-teal-50';
                                            ?>
                                            <td
                                                class="px-1 py-1.5 align-middle border-r border-slate-100/50 last:border-r-0 text-xs <?= $cls ?>">
                                                <?= $val == 0 ? '-' : $val ?>
                                            </td>
                                        <?php endforeach;
                                        $granTotalGeneral += $rowT;
                                        ?>
                                        <td class="px-3 py-1.5 font-extrabold bg-slate-100/50 text-slate-800 text-xs">
                                            <?= $rowT ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                            <tfoot class="bg-slate-100 font-black text-slate-800 border-t-2 border-slate-200 shrink-0">
                                <tr class="text-xs">
                                    <td
                                        class="px-3 py-2 text-left font-black tracking-wider border-r border-slate-200/50 text-[10px]">
                                        TOTALES</td>
                                    <?php foreach ($tiposMaquinaCols as $tm): ?>
                                        <td
                                            class="px-1 py-2 border-r border-slate-200/30 last:border-r-0 font-black text-slate-900 text-xs">
                                            <?= $totalesColumna[$tm['nombre_tipo_maquina']] ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="px-3 py-2 bg-teal-800 text-white font-black text-sm"><?= $granTotalGeneral ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        <?php endif; ?>


        <?php if ($this->seccionActiva('estados')): ?>
            <div class="card-wrapper">
                <div class="card p-6 flex flex-col justify-between">

                    <div class="flex justify-between items-start mb-6 border-b border-gray-100 pb-3 shrink-0">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">
                                ✅
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-800 leading-tight">Estado Final de la Máquina</h3>
                                <p class="text-xs text-slate-400">Resultado operativo tras la intervención técnica</p>
                            </div>
                        </div>
                        <div class="date-badge text-xs font-bold text-slate-700 px-3 py-1.5">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="space-y-5 flex-1 flex flex-col justify-center max-w-4xl mx-auto w-full">
                        <?php
                        $totalEstados = array_sum(array_column($datosEstadosFinales, 'total'));

                        foreach ($datosEstadosFinales as $e):
                            $nombre = $e['nombre_estado'];
                            $cantidad = $e['total'];
                            $pct = $totalEstados > 0 ? round(($cantidad / $totalEstados) * 100, 1) : 0;

                            // LÓGICA DE COLORES INTELIGENTE
                            $nombreMin = mb_strtolower($nombre);

                            if (strpos($nombreMin, 'operativ') !== false || strpos($nombreMin, 'funcional') !== false || strpos($nombreMin, 'buen') !== false) {
                                $colorBarra = 'bg-gradient-to-r from-emerald-400 to-emerald-500';
                                $colorTexto = 'text-emerald-700 bg-emerald-50 border border-emerald-100';
                            } elseif (strpos($nombreMin, 'limit') !== false || strpos($nombreMin, 'parcial') !== false || strpos($nombreMin, 'observacion') !== false) {
                                $colorBarra = 'bg-gradient-to-r from-amber-400 to-amber-500';
                                $colorTexto = 'text-amber-700 bg-amber-50 border border-amber-100';
                            } else {
                                $colorBarra = 'bg-gradient-to-r from-red-400 to-red-500';
                                $colorTexto = 'text-red-700 bg-red-50 border border-red-100';
                            }
                            ?>
                            <div class="bg-slate-50/50 p-3.5 rounded-xl border border-slate-100">
                                <div class="flex justify-between items-center text-xs mb-2">
                                    <span class="font-extrabold text-slate-700 text-sm tracking-tight"><?= $nombre ?></span>

                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-slate-400 font-bold bg-white px-2 py-0.5 rounded border border-slate-200">
                                            <?= $cantidad ?> serv.
                                        </span>
                                        <span class="font-black px-2 py-0.5 rounded text-xs <?= $colorTexto ?>">
                                            <?= $pct ?>%
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="h-3.5 w-full bg-white rounded-full overflow-hidden border border-slate-200 shadow-inner">
                                    <div class="h-full <?= $colorBarra ?> rounded-full transition-all duration-500"
                                        style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="shrink-0 mt-4"></div>

                </div>
            </div>
        <?php endif; ?>




        <?php if ($this->seccionActiva('puntos_atendidos')): ?>

            <?php
            // ==========================================
            // 1. PRE-CÁLCULO DE TOTALES
            // ==========================================
            $totalesColumnaPuntos = [];
            $granTotalPuntos = 0;

            // Inicializamos totales en 0
            foreach ($tiposMaquinaCols as $tm) {
                $totalesColumnaPuntos[$tm['nombre_tipo_maquina']] = 0;
            }

            // Recorremos los datos para sumar
            foreach ($delegacionesListaMaquina as $del) {
                foreach ($tiposMaquinaCols as $tm) {
                    $nombreTipo = $tm['nombre_tipo_maquina'];
                    $val = $matrizPuntosTipo[$del][$nombreTipo] ?? 0;

                    $totalesColumnaPuntos[$nombreTipo] += $val;
                    $granTotalPuntos += $val;
                }
            }
            ?>

            <div class="card-wrapper">
                <div class="card p-4 flex flex-col justify-between">

                    <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-slate-200 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-9 h-9 rounded-full bg-rose-50 flex items-center justify-center text-rose-500 text-sm">
                                📍</div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Puntos Atendidos</h3>
                                <p class="text-[10px] text-slate-400 leading-none">Detalle de cobertura geográfica por tipo
                                    de máquina</p>
                            </div>
                        </div>
                        <div class="date-badge text-[10px] font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 mb-2.5 shrink-0">
                        <div
                            class="flex items-center px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-[10px] shadow-sm">
                            <span class="mr-1">🛠️</span>
                            <span
                                class="font-extrabold text-slate-800 mr-1"><?= number_format($totalGlobalServicios ?? 0) ?></span>
                            <span class="text-[8.5px] text-slate-500">Servicios Atendidos</span>
                        </div>

                        <div
                            class="flex items-center px-2.5 py-1 bg-white border border-rose-200 rounded-lg text-[10px] shadow-sm">
                            <span class="mr-1">📍</span>
                            <span class="font-extrabold text-slate-800 mr-1"><?= number_format($granTotalPuntos) ?></span>
                            <span class="text-[8.5px] text-rose-500 font-bold">Puntos Atendidos</span>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        <table class="w-full text-[9.5px] text-center border-collapse">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider shrink-0">
                                <tr>
                                    <th
                                        class="px-2.5 py-2 text-left bg-slate-100 border-b border-slate-200 w-40 text-[9.5px]">
                                        Delegación
                                    </th>

                                    <?php foreach ($tiposMaquinaCols as $tm): ?>
                                        <th class="px-1 py-2 border-b border-slate-200 align-bottom">
                                            <div
                                                class="whitespace-normal break-words text-[8.5px] leading-tight w-14 mx-auto text-slate-500 font-extrabold">
                                                <?= $tm['nombre_tipo_maquina'] ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>

                                    <th
                                        class="px-2.5 py-2 border-b border-slate-200 bg-slate-50 font-bold text-slate-700 text-[9.5px] w-16">
                                        TOTAL
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($delegacionesListaMaquina as $del):
                                    $rowTotal = 0;
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td
                                            class="px-2.5 py-1 text-left font-bold text-slate-700 bg-slate-50/50 text-[9.5px] border-r border-slate-100">
                                            <div class="truncate max-w-[140px]" title="<?= $del ?>">
                                                <?= $del ?>
                                            </div>
                                        </td>

                                        <?php foreach ($tiposMaquinaCols as $tm):
                                            $nombreTipo = $tm['nombre_tipo_maquina'];
                                            $val = $matrizPuntosTipo[$del][$nombreTipo] ?? 0;
                                            $rowTotal += $val;

                                            $bg = $val > 0 ? 'bg-green-50 text-green-800 font-black' : 'text-slate-200 font-normal';
                                            ?>
                                            <td
                                                class="px-0.5 py-1 <?= $bg ?> border-r border-slate-100/50 last:border-r-0 text-[9.5px]">
                                                <?= $val > 0 ? $val : '-' ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <td class="px-2.5 py-1 font-extrabold bg-slate-100 text-slate-800 text-[9.5px]">
                                            <?= $rowTotal ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                            <tfoot class="bg-slate-100 font-black text-slate-800 border-t-2 border-slate-200 shrink-0">
                                <tr class="text-[9.5px]">
                                    <td
                                        class="px-2.5 py-2 text-left font-black tracking-wider border-r border-slate-200/50 text-[9.5px]">
                                        TOTALES</td>
                                    <?php foreach ($tiposMaquinaCols as $tm):
                                        $val = $totalesColumnaPuntos[$tm['nombre_tipo_maquina']];
                                        ?>
                                        <td
                                            class="px-0.5 py-2 border-r border-slate-200/30 last:border-r-0 font-black text-slate-900 text-[9.5px]">
                                            <?= $val ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="px-2.5 py-2 bg-rose-900 text-white font-black text-xs w-16">
                                        <?= $granTotalPuntos ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-2 pt-1 border-t border-slate-100 flex justify-between items-center shrink-0">
                        <p class="text-[8px] text-slate-400 font-medium">(-) = Sin actividad registrada en el período</p>
                    </div>

                </div>
            </div>
        <?php endif; ?>



        <?php if ($this->seccionActiva('puntos_fallidos')): ?>
            <div class="card-wrapper">
                <div class="card p-5 flex flex-col justify-between">

                    <div class="flex justify-between items-center mb-4 border-b border-slate-200 pb-2.5 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-base border border-blue-100">
                                📍
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Puntos Más Frecuentes</h3>
                                <p class="text-[10px] text-slate-400 leading-tight">Global por delegación (Top 5 puntos con
                                    mayor recurrencia)</p>
                            </div>
                        </div>
                        <div class="date-badge text-[10px] font-bold text-slate-700 px-2.5 py-1.5">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 flex-1">
                        <?php foreach ($puntosVisitadosAgrupados as $delegacion => $puntos): ?>
                            <div
                                class="bg-slate-50/60 rounded-xl border border-slate-200 p-3 flex flex-col justify-between h-fit">

                                <div class="flex justify-between items-center mb-2 pb-1 border-b border-slate-200/60">
                                    <h4 class="font-bold text-slate-800 text-[10px] uppercase truncate max-w-[70%]"
                                        title="<?= $delegacion ?>">
                                        <?= $delegacion ?>
                                    </h4>
                                    <span class="text-[8px] font-black text-blue-600 bg-blue-100/80 px-2 py-0.5 rounded-full">
                                        <?= count($puntos) ?> pts
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <?php
                                    $maxLocal = $puntos[0]['total'];
                                    $puntosMostrar = array_slice($puntos, 0, 5); // Top 5
                            
                                    foreach ($puntosMostrar as $p):
                                        $ancho = ($p['total'] / $maxLocal) * 100;
                                        ?>
                                        <div class="flex flex-col">
                                            <div class="flex justify-between items-start text-[9.5px] mb-1">
                                                <div class="flex-1 min-w-0 pr-2">
                                                    <div class="font-bold text-slate-700 leading-snug truncate"
                                                        title="<?= $p['punto'] ?>">
                                                        <?= $p['punto'] ?>
                                                    </div>
                                                    <div
                                                        class="text-[7.5px] font-medium text-slate-400 uppercase leading-none mt-0.5">
                                                        <?= $p['tipo'] ?>
                                                    </div>
                                                </div>
                                                <div class="font-extrabold text-blue-600 text-[10px] shrink-0">
                                                    <?= $p['total'] ?>
                                                </div>
                                            </div>

                                            <div class="w-full h-1.5 bg-slate-200/70 rounded-full overflow-hidden shadow-inner">
                                                <div class="h-full bg-blue-500 rounded-full" style="width: <?= $ancho ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if (count($puntos) > 5): ?>
                                        <div
                                            class="text-center text-[8px] font-bold text-slate-400 pt-1.5 border-t border-slate-100">
                                            +<?= count($puntos) - 5 ?> puntos más
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="shrink-0 mt-4"></div>

                </div>
            </div>
        <?php endif; ?>








        <?php if ($this->seccionActiva('repuestos')): ?>
            <div class="card-wrapper">
                <div class="card p-4 flex flex-col justify-between">

                    <div class="flex justify-between items-start mb-3 border-b border-gray-100 pb-2 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-base">
                                ⚙️
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Repuestos Usados en Servicios
                                </h3>
                                <p class="text-[9px] text-slate-400 leading-none">Distribución de consumos y origen de
                                    repuestos del período</p>
                            </div>
                        </div>
                        <div class="date-badge text-[9px] font-bold text-slate-700 px-2 py-1">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <?php
                    // Calcular porcentajes y cantidades para el Pastel
                    $totalRep = 0;
                    $inees = 0;
                    $prosegur = 0;

                    foreach ($datosOrigenRepuestos as $origen) {
                        $nombre = strtoupper($origen['origen']);
                        $cant = $origen['total'];
                        $totalRep += $cant;

                        if (strpos($nombre, 'INEES') !== false) {
                            $inees += $cant;
                        } else {
                            $prosegur += $cant;
                        }
                    }

                    $pctInees = $totalRep > 0 ? round(($inees / $totalRep) * 100) : 0;
                    ?>

                    <div class="grid grid-cols-3 gap-3 flex-1">

                        <div
                            class="break-inside-avoid bg-orange-50/20 rounded-xl border border-orange-100 p-2.5 flex flex-col justify-between h-fit min-h-[140px]">
                            <div class="border-b border-orange-100/50 pb-1 mb-2 shrink-0">
                                <h4 class="font-extrabold text-orange-700 text-[8.5px] uppercase tracking-wider">Origen de
                                    Repuestos</h4>
                            </div>

                            <div class="flex items-center justify-around gap-2.5 flex-1">
                                <div class="relative w-14 h-14 rounded-full shadow-inner border-2 border-white shrink-0"
                                    style="background: conic-gradient(#f97316 <?= $pctInees ?>%, #3b82f6 0);">
                                    <div
                                        class="absolute inset-0 m-auto w-8 h-8 bg-slate-50 rounded-full flex items-center justify-center text-[7px] font-bold text-slate-500 shadow-sm text-center leading-none">
                                        Total<br><?= $totalRep ?>
                                    </div>
                                </div>

                                <div class="text-[8.5px] space-y-1 flex-1">
                                    <div class="flex justify-between items-center border-b border-orange-100/30 pb-0.5">
                                        <div class="flex items-center gap-1 font-semibold text-slate-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> INEES
                                        </div>
                                        <div class="text-right leading-none">
                                            <span class="font-bold text-slate-700"><?= $pctInees ?>%</span>
                                            <span class="text-[7.5px] text-slate-400 block">(<?= $inees ?>)</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center pt-0.5">
                                        <div class="flex items-center gap-1 font-semibold text-slate-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> PROSEGUR
                                        </div>
                                        <div class="text-right leading-none">
                                            <span class="font-bold text-slate-700"><?= 100 - $pctInees ?>%</span>
                                            <span class="text-[7.5px] text-slate-400 block">(<?= $prosegur ?>)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php foreach ($repuestosPorDelegacion as $nomDelegacion => $datos): ?>
                            <div class="break-inside-avoid bg-white rounded-xl shadow-sm border border-slate-200 p-2.5 h-fit">

                                <div class="flex justify-between items-center mb-1.5 border-b border-slate-100 pb-1">
                                    <h4 class="font-bold text-slate-700 text-[8.5px] uppercase truncate max-w-[65%]"
                                        title="<?= $nomDelegacion ?>">
                                        <?= $nomDelegacion ?>
                                    </h4>
                                    <span
                                        class="bg-orange-100 text-orange-700 text-[7.5px] px-1.5 py-0.5 rounded-full font-black leading-none">
                                        Total: <?= $datos['total_gral'] ?>
                                    </span>
                                </div>

                                <ul class="space-y-[2px]">
                                    <?php foreach (array_slice($datos['items'], 0, 5) as $item): ?>
                                        <li
                                            class="flex justify-between items-start text-[8px] gap-2 py-0.5 border-b border-slate-50/50 last:border-0">
                                            <span
                                                class="text-slate-600 text-left leading-tight flex-1 break-words whitespace-normal pr-1">
                                                <?= $item['nombre'] ?>
                                            </span>
                                            <span
                                                class="font-bold text-slate-800 bg-slate-100 px-1 py-0 rounded shrink-0 text-[7.5px] leading-none">
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



        <?php if ($this->seccionActiva('calificaciones')): ?>
            <div class="card-wrapper mt-4">

                <div class="flex justify-between items-center mb-4 px-2">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 text-lg border border-amber-200">
                            ⭐</div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Calidad de Atención</h3>
                            <p class="text-xs text-slate-500">Satisfacción del cliente por Delegación</p>
                        </div>
                    </div>
                    <div class="date-badge text-xl font-bold text-slate-700">
                        <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                    </div>
                </div>

                <div class="columns-1 md:columns-2 lg:columns-3 gap-4 space-y-4">
                    <?php foreach ($calificacionesAgrupadas as $delegacion => $datos): ?>
                        <div class="break-inside-avoid bg-white rounded-xl shadow-sm border border-slate-200 p-3 mb-4">

                            <div class="flex justify-between items-center mb-2 pb-1 border-b border-slate-100">
                                <h4 class="font-extrabold text-slate-700 text-xs uppercase tracking-wide">
                                    <?= $delegacion ?>
                                </h4>
                                <span
                                    class="bg-amber-50 text-amber-700 text-[9.5px] font-bold px-1.5 py-0.5 rounded border border-amber-100">
                                    Total: <?= $datos['total_zona'] ?>
                                </span>
                            </div>

                            <div class="space-y-2">
                                <?php foreach ($datos['items'] as $item):
                                    $totalZona = $datos['total_zona'];
                                    $pct = ($totalZona > 0) ? round(($item['total'] / $totalZona) * 100, 1) : 0;

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
                                        <div class="flex justify-between text-[11px] mb-0.5">
                                            <span class="font-bold text-slate-600"><?= $item['nombre'] ?></span>
                                            <div class="flex gap-2">
                                                <span class="text-slate-400 font-medium text-[9.5px]">(<?= $item['total'] ?>)</span>
                                                <span class="font-extrabold <?= $colorTexto ?> text-[11px]"><?= $pct ?>%</span>
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
            <div class="card-wrapper">
                <div class="card bg-rose-50/40 border border-rose-200 p-5 flex flex-col justify-between">

                    <!-- Encabezado con letras agrandadas -->
                    <div class="flex justify-between items-center mb-4 border-b border-rose-200/60 pb-2.5 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-9 h-9 rounded-full bg-white border border-rose-200 flex items-center justify-center text-rose-600 text-lg shadow-sm">
                                ⚠️
                            </div>
                            <div>
                                <!-- Subimos el título a text-lg -->
                                <h3 class="text-lg font-bold text-rose-900 leading-tight">Puntos Fallidos</h3>
                                <!-- Subimos el subtexto a text-xs -->
                                <p class="text-xs text-rose-600 font-medium">Puntos con <strong>>= 2 Visitas
                                        Fallidas</strong></p>
                            </div>
                        </div>
                        <!-- Subimos la fecha a text-xl -->
                        <div class="date-badge text-xl font-bold text-slate-700">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <!-- GRID DE 3 COLUMNAS: Evita cortes raros en impresión -->
                    <div class="grid grid-cols-3 gap-4 flex-1">

                        <?php foreach ($puntosFallidosPorDelegacion as $nomDelegacion => $datos): ?>
                            <div class="break-inside-avoid bg-white rounded-xl shadow-sm border border-rose-100 p-3.5 h-fit">

                                <!-- Nombre de Delegación más grande (text-xs font-extrabold) -->
                                <div class="flex justify-between items-center mb-2.5 border-b border-slate-100 pb-1.5">
                                    <h4 class="font-extrabold text-slate-800 text-xs uppercase truncate max-w-[70%]"
                                        title="<?= $nomDelegacion ?>">
                                        <?= $nomDelegacion ?>
                                    </h4>
                                    <!-- Badge de total más grande (text-[9.5px]) -->
                                    <span class="bg-rose-100 text-rose-700 text-[9.5px] px-2 py-0.5 rounded-full font-black">
                                        Tot: <?= $datos['total_zona'] ?>
                                    </span>
                                </div>

                                <!-- Lista de puntos críticos -->
                                <ul class="space-y-2.5">
                                    <?php
                                    // Top 5 puntos críticos de esta zona
                                    foreach (array_slice($datos['items'], 0, 5) as $item):
                                        $ancho = min(($item['cantidad'] / 5) * 100, 100);
                                        ?>
                                        <li class="flex flex-col">
                                            <!-- Aumentamos la tipografía de la fila a text-[11px] -->
                                            <div class="flex justify-between items-start text-[11px] mb-1">
                                                <!-- Nombre del punto en negrita y más grande -->
                                                <span class="text-slate-700 font-bold flex-1 pr-2 break-all leading-tight"
                                                    title="<?= $item['nombre'] ?>">
                                                    <?= $item['nombre'] ?>
                                                </span>
                                                <!-- Cantidad de fallos más grande y en negrita -->
                                                <span class="font-extrabold text-rose-600 text-[11px] shrink-0">
                                                    <?= $item['cantidad'] ?>
                                                </span>
                                            </div>
                                            <!-- Barra de progreso un poco más gruesa (h-1.5) para que haga juego -->
                                            <div
                                                class="w-full h-1.5 bg-rose-50 rounded-full overflow-hidden border border-rose-100 shadow-inner">
                                                <div class="h-full bg-rose-500 rounded-full" style="width: <?= $ancho ?>%"></div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                            </div>
                        <?php endforeach; ?>

                    </div>

                    <!-- Mensaje de excelente gestión con letra de tamaño balanceado -->
                    <?php if (empty($puntosFallidosPorDelegacion)): ?>
                        <div
                            class="text-center py-8 text-sm text-slate-500 font-bold italic bg-white rounded-xl border border-rose-100 shadow-sm">
                            Excellent Gestión: Ningún punto supera los 2 servicios fallidos en este periodo. 🎉
                        </div>
                    <?php endif; ?>

                    <!-- Espacio inferior para balancear el pie de página -->
                    <div class="shrink-0 mt-4"></div>

                </div>
            </div>
        <?php endif; ?>




        <?php if ($this->seccionActiva('tecnicos')): ?>

            <div class="card-wrapper">
                <div class="card pt-4 pb-4 px-5 flex flex-col justify-between">

                    <div class="flex justify-between items-start mb-3 border-b border-gray-100 pb-2.5 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-9 h-9 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 text-lg border border-emerald-100">
                                👷
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight">Promedio Servicio por Técnico
                                </h3>
                                <p class="text-[10px] text-slate-400">Desglose de servicios por tipo y promedios diarios de
                                    productividad</p>
                            </div>
                        </div>
                        <div class="date-badge text-[10px] font-bold text-slate-700 px-2.5 py-1.5">
                            <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-slate-200 flex-1">
                        <table class="w-full text-[10px] text-center border-collapse">
                            <thead>
                                <tr
                                    class="text-slate-500 text-[8.5px] uppercase tracking-wider text-left border-b border-slate-200 align-bottom bg-slate-50 font-bold shrink-0">
                                    <th class="py-2 font-black pl-3 w-48 bg-slate-100">Técnico</th>

                                    <?php foreach ($todosTiposMant as $tm): ?>
                                        <th
                                            class="py-2 text-center font-black text-emerald-700 bg-emerald-50 border-r border-white align-bottom">
                                            <div
                                                class="w-16 mx-auto whitespace-normal break-words leading-tight text-[8px] flex items-end justify-center">
                                                <?= $tm['nombre_completo'] ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>

                                    <th
                                        class="py-2 text-center font-black w-14 border-l border-slate-200 align-bottom text-slate-700">
                                        <div class="w-12 mx-auto whitespace-normal leading-tight">Media<br>L-V</div>
                                    </th>
                                    <th class="py-2 text-center font-black w-14 align-bottom text-slate-700">
                                        <div class="w-12 mx-auto whitespace-normal leading-tight">Media<br>Sáb</div>
                                    </th>
                                    <th class="py-2 text-center font-black w-12 text-slate-700 bg-slate-100 align-bottom">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php
                                usort($topTecnicos, function ($a, $b) {
                                    $mediaA = ($a['dias_trabajados_lv'] > 0) ? ($a['servicios_lv'] / $a['dias_trabajados_lv']) : 0;
                                    $mediaB = ($b['dias_trabajados_lv'] > 0) ? ($b['servicios_lv'] / $b['dias_trabajados_lv']) : 0;
                                    if (abs($mediaA - $mediaB) < 0.01)
                                        return $b['total_general'] - $a['total_general'];
                                    return ($mediaA < $mediaB) ? 1 : -1;
                                });

                                foreach ($topTecnicos as $t):
                                    $mediaLV = ($t['dias_trabajados_lv'] > 0) ? round($t['servicios_lv'] / $t['dias_trabajados_lv'], 1) : 0;
                                    $mediaSab = ($t['dias_trabajados_sab'] > 0) ? round($t['servicios_sab'] / $t['dias_trabajados_sab'], 1) : 0;

                                    if ($mediaLV > 6) {
                                        $colorLV = 'bg-emerald-500';
                                    } elseif ($mediaLV >= 5) {
                                        $colorLV = 'bg-yellow-400';
                                    } elseif ($mediaLV >= 4) {
                                        $colorLV = 'bg-orange-400';
                                    } else {
                                        $colorLV = 'bg-red-500';
                                    }

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
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td
                                            class="py-1 pl-3 text-left text-slate-800 font-extrabold whitespace-nowrap text-[10px] border-r border-slate-100 bg-slate-50/50">
                                            <?= $t['nombre_tecnico'] ?>
                                        </td>

                                        <?php foreach ($todosTiposMant as $tm):
                                            $cant = $t['desglose'][$tm['nombre_completo']] ?? 0;
                                            $styleCell = $cant > 0 ? 'text-slate-800 font-black bg-emerald-50/20' : 'text-slate-200 font-normal';
                                            ?>
                                            <td
                                                class="py-1 text-center <?= $styleCell ?> border-r border-slate-100/50 last:border-r-0 text-[9.5px]">
                                                <?= $cant > 0 ? $cant : '-' ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <td class="py-1 px-1.5 border-l border-slate-100">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <span class="font-bold text-slate-700 text-[10px]"><?= $mediaLV ?></span>
                                                <div class="w-2 h-2 rounded-full shrink-0 <?= $colorLV ?>"></div>
                                            </div>
                                        </td>

                                        <td class="py-1 px-1.5">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <span class="font-bold text-slate-700 text-[10px]"><?= $mediaSab ?></span>
                                                <div class="w-2 h-2 rounded-full shrink-0 <?= $colorSab ?>"></div>
                                            </div>
                                        </td>

                                        <td
                                            class="py-1 px-1.5 text-center font-black text-slate-900 bg-slate-50/50 text-[10px]">
                                            <?= $t['total_general'] ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div> <?php endif; ?>





        <?php
        // ══════════════════════════════════════════════════════════════
// BLOQUE COMPARTIDO — Cálculos de costos y variables
// ══════════════════════════════════════════════════════════════
        $listaMotorizados = [];
        $listaNominaAdmin = [];
        $totalMotorizados = 0;
        $totalNominaAdmin = 0;
        $totalGastosGral = 0;

        if (!empty($listaCostos)) {
            foreach ($listaCostos as $c) {
                if (strpos($c['rol'], 'Técnico') !== false) {
                    $listaMotorizados[] = $c;
                    $totalMotorizados += $c['subtotal'];
                } else {
                    $listaNominaAdmin[] = $c;
                    $totalNominaAdmin += $c['subtotal'];
                }
            }
        }
        if (!empty($listaGastosGenerales)) {
            foreach ($listaGastosGenerales as $g) {
                $totalGastosGral += $g['valor'];
            }
        }

        $granTotal = $totalMotorizados + $totalNominaAdmin + $totalGastosGral;
        $baseCalculo = $granTotal > 0 ? $granTotal : 1;
        $pctMotorizados = round(($totalMotorizados / $baseCalculo) * 100, 1);
        $pctAdmin = round(($totalNominaAdmin / $baseCalculo) * 100, 1);
        $pctGastos = round(($totalGastosGral / $baseCalculo) * 100, 1);

        // Balance
        $valPreventivo = $ingresoPreventivo ?? 0;
        $valPreventivoProf = $ingresoPreventivoProf ?? 0;
        $valCorrectivo = $ingresoCorrectivo ?? 0;
        $valFallido = $ingresoFallido ?? 0;
        $valGarantia = $ingresoGarantia ?? 0;
        $valRepuestos = $ingresoRepuestos ?? 0;

        $totalIngresosBrutos = $valPreventivo + $valPreventivoProf + $valCorrectivo + $valFallido + $valGarantia + $valRepuestos;
        $totalEgresosOperativos = $totalMotorizados + $totalNominaAdmin + $totalGastosGral;
        $utilidadNeta = $totalIngresosBrutos - $totalEgresosOperativos;
        $margenUtilidad = ($totalIngresosBrutos > 0) ? round(($utilidadNeta / $totalIngresosBrutos) * 100, 1) : 0;

        $pctPrev = ($totalIngresosBrutos > 0) ? ($valPreventivo / $totalIngresosBrutos) * 100 : 0;
        $pctPrevProf = ($totalIngresosBrutos > 0) ? ($valPreventivoProf / $totalIngresosBrutos) * 100 : 0;
        $pctCorr = ($totalIngresosBrutos > 0) ? ($valCorrectivo / $totalIngresosBrutos) * 100 : 0;
        $pctFall = ($totalIngresosBrutos > 0) ? ($valFallido / $totalIngresosBrutos) * 100 : 0;
        $pctGaran = ($totalIngresosBrutos > 0) ? ($valGarantia / $totalIngresosBrutos) * 100 : 0;
        $pctRep = ($totalIngresosBrutos > 0) ? ($valRepuestos / $totalIngresosBrutos) * 100 : 0;

        $pctMoto = ($totalEgresosOperativos > 0) ? ($totalMotorizados / $totalEgresosOperativos) * 100 : 0;
        $pctAdm = ($totalEgresosOperativos > 0) ? ($totalNominaAdmin / $totalEgresosOperativos) * 100 : 0;
        $pctGral = ($totalEgresosOperativos > 0) ? ($totalGastosGral / $totalEgresosOperativos) * 100 : 0;
        ?>



        <!-- ══════════════════════════════════════════════════════════════
    SECCIÓN 1 — REPORTE DE COSTOS OPERATIVOS  (página propia)
══════════════════════════════════════════════════════════════ -->
        <?php if ($this->seccionActiva('costos')): ?>

            <div class="card-wrapper">
                <div class="card p-4 flex flex-col justify-between">

                    <div class="flex justify-between items-center mb-4 border-b border-gray-200 pb-2 shrink-0">
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tight">Reporte de Costos Operativos
                        </h2>
                        <div class="text-xs font-bold text-gray-400">
                            <?= date('d/m/Y', strtotime($inicio)) ?> — <?= date('d/m/Y', strtotime($fin)) ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-4 mb-4 items-stretch shrink-0">

                        <div
                            class="bg-white p-3 rounded-xl shadow-sm border border-emerald-100 flex flex-col justify-between">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="text-[9px] text-emerald-600 font-bold uppercase mb-0.5">Gastos Motorizados</p>
                                    <h3 class="text-sm font-black text-gray-800">
                                        $<?= number_format($totalMotorizados, 0, ',', '.') ?></h3>
                                </div>
                                <span class="text-lg">🏍️</span>
                            </div>
                            <div class="w-full bg-emerald-100 h-1 rounded-full">
                                <div style="width:<?= $pctMotorizados ?>%" class="h-full bg-emerald-500 rounded-full"></div>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-xl shadow-sm border border-blue-100 flex flex-col justify-between">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="text-[9px] text-blue-600 font-bold uppercase mb-0.5">Nómina Administrativa</p>
                                    <h3 class="text-sm font-black text-gray-800">
                                        $<?= number_format($totalNominaAdmin, 0, ',', '.') ?></h3>
                                </div>
                                <span class="text-lg">👥</span>
                            </div>
                            <div class="w-full bg-blue-100 h-1 rounded-full">
                                <div style="width:<?= $pctAdmin ?>%" class="h-full bg-blue-500 rounded-full"></div>
                            </div>
                        </div>

                        <div
                            class="bg-white p-3 rounded-xl shadow-sm border border-orange-100 flex flex-col justify-between">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="text-[9px] text-orange-600 font-bold uppercase mb-0.5">Gastos Administrativos
                                    </p>
                                    <h3 class="text-sm font-black text-gray-800">
                                        $<?= number_format($totalGastosGral, 0, ',', '.') ?></h3>
                                </div>
                                <span class="text-lg">🧾</span>
                            </div>
                            <div class="w-full bg-orange-100 h-1 rounded-full">
                                <div style="width:<?= $pctGastos ?>%" class="h-full bg-orange-500 rounded-full"></div>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-center">
                            <div
                                class="w-full h-5 bg-gray-100 rounded-md overflow-hidden flex shadow-inner border border-gray-200">
                                <div style="width: <?= $pctMotorizados ?>%"
                                    class="h-full bg-emerald-500 flex items-center justify-center text-[8px] font-bold text-white">
                                    <?= $pctMotorizados > 12 ? $pctMotorizados . '%' : '' ?>
                                </div>
                                <div style="width: <?= $pctAdmin ?>%"
                                    class="h-full bg-blue-500 flex items-center justify-center text-[8px] font-bold text-white">
                                    <?= $pctAdmin > 12 ? $pctAdmin . '%' : '' ?>
                                </div>
                                <div style="width: <?= $pctGastos ?>%"
                                    class="h-full bg-orange-500 flex items-center justify-center text-[8px] font-bold text-white">
                                    <?= $pctGastos > 12 ? $pctGastos . '%' : '' ?>
                                </div>
                            </div>
                            <div class="flex justify-between items-center mt-2 px-0.5">
                                <div class="flex gap-2">
                                    <div class="flex items-center gap-1 text-[7px] text-gray-500">
                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Motorizados
                                    </div>
                                    <div class="flex items-center gap-1 text-[7px] text-gray-500">
                                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div> Nómina Administrativa
                                    </div>
                                    <div class="flex items-center gap-1 text-[7px] text-gray-500">
                                        <div class="w-1.5 h-1.5 rounded-full bg-orange-500"></div> Gastos Administrativos
                                    </div>
                                </div>
                                <div class="text-[8px] font-bold text-slate-800">100%</div>
                            </div>
                        </div>

                    </div>

                    <div class="grid grid-cols-3 gap-4 flex-1 overflow-hidden">

                        <div class="bg-white rounded-xl shadow-sm border border-emerald-100 overflow-hidden flex flex-col">
                            <div
                                class="bg-emerald-50 px-3 py-2 border-b border-emerald-100 flex items-center gap-1.5 shrink-0">
                                <span class="text-emerald-600 text-sm">🏍️</span>
                                <h3 class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">1. Gastos
                                    Motorizados</h3>
                            </div>
                            <div class="p-2 flex-1 overflow-y-auto">
                                <table class="w-full text-[9px]">
                                    <thead>
                                        <tr class="text-gray-400 uppercase border-b border-emerald-50 text-[8px]">
                                            <th class="text-left py-1 pl-1">Técnico</th>
                                            <th class="text-right py-1">Rod.</th>
                                            <th class="text-right py-0.5">Sal.</th>
                                            <th class="text-right py-1">Gas.</th>
                                            <th class="text-right py-0.5">Ext.</th>
                                            <th class="text-right py-0.5">Bon.</th>
                                            <th class="text-right py-0.5">Rod.</th>
                                            <th class="text-right py-0.5">Gas.</th>
                                            <th class="text-right py-0.5">Com.</th>
                                            <th class="text-right py-1 pr-1 font-bold text-emerald-700">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <?php if (!empty($listaMotorizados)): ?>
                                            <?php foreach ($listaMotorizados as $m): ?>
                                                <tr>
                                                    <td class="py-0.5 pl-0.5 font-medium text-gray-700 truncate max-w-[50px]">
                                                        <?= $m['beneficiario'] ?>
                                                    </td>
                                                    <td class="py-0.5 text-right text-gray-500">
                                                        <?= $m['salario'] > 0 ? '$' . number_format($m['salario'], 0) : '-' ?>
                                                    </td>
                                                    <td class="py-0.5 text-right text-gray-500">
                                                        <?= $m['horas_extra'] > 0 ? '$' . number_format($m['horas_extra'], 0) : '-' ?>
                                                    </td>
                                                    <td class="py-0.5 text-right text-gray-500">
                                                        <?= $m['bono_meta'] > 0 ? '$' . number_format($m['bono_meta'], 0) : '-' ?>
                                                    </td>
                                                    <td class="py-0.5 text-right text-gray-500">
                                                        <?= $m['auxilio_rodamiento'] > 0 ? '$' . number_format($m['auxilio_rodamiento'], 0) : '-' ?>
                                                    </td>
                                                    <td class="py-0.5 text-right text-gray-500">
                                                        <?= $m['gasolina'] > 0 ? '$' . number_format($m['gasolina'], 0) : '-' ?>
                                                    </td>
                                                    <td class="py-0.5 text-right text-gray-500">
                                                        <?= $m['auxilio_comunicacion'] > 0 ? '$' . number_format($m['auxilio_comunicacion'], 0) : '-' ?>
                                                    </td>
                                                    <td class="py-0.5 pr-0.5 text-right font-bold text-emerald-600">
                                                        $<?= number_format($m['subtotal'], 0) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="bg-emerald-50 border-t border-emerald-100">
                                                <td colspan="7" class="text-right py-1 font-bold text-emerald-700 text-[7px]">
                                                    SUBTOTAL:</td>
                                                <td class="text-right py-1 pr-0.5 font-black text-emerald-800 text-[7px]">
                                                    $<?= number_format($totalMotorizados, 0) ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-gray-300">Sin registros</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                class="bg-emerald-50 border-t border-emerald-100 px-3 py-1.5 flex justify-between items-center shrink-0">
                                <span class="font-bold text-emerald-700 text-[9px]">SUBTOTAL:</span>
                                <span
                                    class="font-black text-emerald-800 text-[10px]">$<?= number_format($totalMotorizados, 0) ?></span>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-blue-100 overflow-hidden flex flex-col">
                            <div class="bg-blue-50 px-3 py-2 border-b border-blue-100 flex items-center gap-1.5 shrink-0">
                                <span class="text-blue-600 text-sm">👥</span>
                                <h3 class="text-[10px] font-bold text-blue-800 uppercase tracking-wider">2. Nómina Admin
                                </h3>
                            </div>
                            <div class="p-2 flex-1 overflow-y-auto">
                                <table class="w-full text-[9px]">
                                    <thead>
                                        <tr class="text-gray-400 uppercase border-b border-blue-50">
                                            <th class="text-left py-0.5 pl-0.5">Colaborador</th>
                                            <th class="text-left py-0.5">Cargo</th>
                                            <th class="text-right py-0.5">Salario</th>
                                            <th class="text-right py-0.5">H. Extra</th>
                                            <th class="text-right py-0.5">Bono</th>
                                            <th class="text-right py-0.5 pr-0.5">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <?php if (!empty($listaNominaAdmin)): ?>
                                            <?php foreach ($listaNominaAdmin as $a): ?>
                                                <tr>
                                                    <td class="py-0.5 pl-0.5 font-medium text-gray-700 truncate max-w-[60px]">
                                                        <?= $a['beneficiario'] ?>
                                                    </td>
                                                    <td class="py-0.5 text-gray-400 uppercase truncate max-w-[50px]">
                                                        <?= $a['rol'] ?></td>
                                                    <td class="py-0.5 text-right text-gray-500">
                                                        $<?= number_format($a['salario'] ?? 0, 0) ?></td>
                                                    <td class="py-0.5 text-right text-gray-500">
                                                        $<?= number_format($a['horas_extra'] ?? 0, 0) ?></td>
                                                    <td class="py-0.5 text-right text-gray-500">
                                                        $<?= number_format($a['bono_meta'] ?? 0, 0) ?></td>
                                                    <td class="py-0.5 pr-0.5 text-right font-bold text-blue-600">
                                                        $<?= number_format($a['subtotal'] ?? 0, 0) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="bg-blue-50 border-t border-blue-100">
                                                <td colspan="5" class="text-right py-1 font-bold text-blue-700 text-[7px]">
                                                    SUBTOTAL:</td>
                                                <td class="text-right py-1 pr-0.5 font-black text-blue-800 text-[7px]">
                                                    $<?= number_format($totalNominaAdmin, 0) ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-4 text-gray-300">Sin registros</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                class="bg-blue-50 border-t border-blue-100 px-3 py-1.5 flex justify-between items-center shrink-0">
                                <span class="font-bold text-blue-700 text-[9px]">SUBTOTAL:</span>
                                <span
                                    class="font-black text-blue-800 text-[10px]">$<?= number_format($totalNominaAdmin, 0) ?></span>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-orange-100 overflow-hidden flex flex-col">
                            <div
                                class="bg-orange-50 px-3 py-2 border-b border-orange-100 flex items-center gap-1.5 shrink-0">
                                <span class="text-orange-600 text-sm">🧾</span>
                                <h3 class="text-[10px] font-bold text-orange-800 uppercase tracking-wider">3. Gastos
                                    Administrativos</h3>
                            </div>
                            <div class="p-2 flex-1 overflow-y-auto">
                                <table class="w-full text-[9px]">
                                    <thead>
                                        <tr class="text-gray-400 uppercase border-b border-orange-50 text-[8px]">
                                            <th class="text-left py-1 pl-1">Concepto</th>
                                            <th class="text-left py-1 pl-1">Categoría</th>
                                            <th class="text-right py-1 pr-1">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <?php if (!empty($listaGastosGenerales)): ?>
                                            <?php foreach ($listaGastosGenerales as $g): ?>
                                                <tr>
                                                    <td class="py-0.5 pl-0.5 font-medium text-gray-700 truncate max-w-[80px]">
                                                        <?= $g['concepto'] ?>
                                                    </td>
                                                    <td class="py-0.5"><span
                                                            class="bg-gray-100 text-gray-500 px-0.5 rounded"><?= $g['categoria'] ?></span>
                                                    </td>
                                                    <td class="py-0.5 pr-0.5 text-right font-bold text-orange-600">
                                                        $<?= number_format($g['valor'], 0) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="bg-orange-50 border-t border-orange-100">
                                                <td colspan="2" class="text-right py-1 font-bold text-orange-700 text-[7px]">
                                                    SUBTOTAL:</td>
                                                <td class="text-right py-1 pr-0.5 font-black text-orange-800 text-[7px]">
                                                    $<?= number_format($totalGastosGral, 0) ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="text-center py-4 text-gray-300">Sin registros</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                class="bg-orange-50 border-t border-orange-100 px-3 py-1.5 flex justify-between items-center shrink-0">
                                <span class="font-bold text-orange-700 text-[9px]">SUBTOTAL:</span>
                                <span
                                    class="font-black text-orange-800 text-[10px]">$<?= number_format($totalGastosGral, 0) ?></span>
                            </div>
                        </div>

                    </div>

                    <div
                        class="bg-slate-800 rounded-xl shadow border border-slate-700 py-2.5 px-4 text-white flex justify-between items-center mt-4 shrink-0">
                        <div class="text-left">
                            <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest leading-none">Total
                                Egresos Operativos</p>
                            <p class="text-[8px] text-slate-400 leading-none mt-1">(Motorizados + Nómina Administrativa +
                                Gastos Administrativos)</p>
                        </div>
                        <h2 class="text-base font-black text-white leading-none">
                            $<?= number_format($granTotal, 0, ',', '.') ?></h2>
                    </div>

                </div>
            </div> <?php endif; ?>



        <!-- ══════════════════════════════════════════════════════════════
        SECCIÓN 2 — ESTADO DE RESULTADOS  (página propia)
══════════════════════════════════════════════════════════════ -->
        <?php if ($this->seccionActiva('balance')): ?>

            <div class="card-wrapper">

                <div class="card p-4 flex flex-col justify-between">

                    <div class="bg-slate-800 px-3 py-2 flex justify-between items-center text-white rounded-t-xl">
                        <div class="flex items-center gap-2">
                            <div class="bg-slate-700 p-1 rounded-md text-base">💰</div>
                            <div>
                                <h3 class="font-bold text-sm uppercase tracking-wide text-emerald-400">Estado de
                                    Resultados
                                </h3>
                                <p class="text-[8px] text-slate-400">Vista detallada por Columnas</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-[7px] uppercase tracking-widest text-slate-400">VALOR TOTAL DESPUES DE LA
                                RESTA
                                DE
                                LOS COSTOS</div>
                            <div
                                class="text-xl font-black <?= $utilidadNeta >= 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                                $ <?= number_format($utilidadNeta, 0, ',', '.') ?>
                            </div>
                        </div>
                    </div>

                    <div class="p-3">

                        <div class="flex flex-col gap-3 mt-2">

                            <div class="w-full">
                                <div class="flex justify-between items-end mb-1 border-b border-emerald-100 pb-0.5">
                                    <h4 class="font-bold text-emerald-700 uppercase text-[8px] flex items-center gap-1">
                                        <span class="text-xs">💰</span> Desglose de Ingresos
                                    </h4>
                                    <span class="font-black text-emerald-800 text-xs">$
                                        <?= number_format($totalIngresosBrutos, 0) ?></span>
                                </div>

                                <div class="grid grid-cols-6 gap-1">

                                    <div
                                        class="bg-emerald-50 border border-emerald-100 rounded p-1 flex flex-col justify-between text-center">
                                        <div>
                                            <div class="text-[6px] uppercase font-bold text-emerald-600 mb-0.5">
                                                Preventivo
                                            </div>
                                            <div class="text-[9px] font-black text-slate-700 leading-tight">
                                                $<?= number_format($valPreventivo, 0, ',', '.') ?></div>
                                            <div class="w-full bg-emerald-200 h-0.5 mt-0.5 rounded-full overflow-hidden">
                                                <div style="width: <?= $pctPrev ?>%" class="h-full bg-emerald-500">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center w-full mt-1 px-0.5">
                                            <span
                                                class="text-[6px] font-bold text-emerald-700 bg-emerald-100 px-0.5 rounded"><?= $cantPrev ?>
                                                Serv.</span>
                                            <span
                                                class="text-[6px] font-bold text-emerald-500"><?= round($pctPrev, 1) ?>%</span>
                                        </div>
                                    </div>

                                    <div
                                        class="bg-teal-50 border border-teal-100 rounded p-1 flex flex-col justify-between text-center">
                                        <div>
                                            <div class="text-[6px] uppercase font-bold text-teal-600 mb-0.5">Profundo
                                            </div>
                                            <div class="text-[9px] font-black text-slate-700 leading-tight">
                                                $<?= number_format($valPreventivoProf, 0, ',', '.') ?></div>
                                            <div class="w-full bg-teal-200 h-0.5 mt-0.5 rounded-full overflow-hidden">
                                                <div style="width: <?= $pctPrevProf ?>%" class="h-full bg-teal-500">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center w-full mt-1 px-0.5">
                                            <span
                                                class="text-[6px] font-bold text-teal-700 bg-teal-100 px-0.5 rounded"><?= $cantProf ?>
                                                Serv.</span>
                                            <span
                                                class="text-[6px] font-bold text-teal-500"><?= round($pctPrevProf, 1) ?>%</span>
                                        </div>
                                    </div>

                                    <div
                                        class="bg-blue-50 border border-blue-100 rounded p-1 flex flex-col justify-between text-center">
                                        <div>
                                            <div class="text-[6px] uppercase font-bold text-blue-600 mb-0.5">Correctivo
                                            </div>
                                            <div class="text-[9px] font-black text-slate-700 leading-tight">
                                                $<?= number_format($valCorrectivo, 0, ',', '.') ?></div>
                                            <div class="w-full bg-blue-200 h-0.5 mt-0.5 rounded-full overflow-hidden">
                                                <div style="width: <?= $pctCorr ?>%" class="h-full bg-blue-500"></div>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center w-full mt-1 px-0.5">
                                            <span
                                                class="text-[6px] font-bold text-blue-700 bg-blue-100 px-0.5 rounded"><?= $cantCorr ?>
                                                Serv.</span>
                                            <span
                                                class="text-[6px] font-bold text-blue-500"><?= round($pctCorr, 1) ?>%</span>
                                        </div>
                                    </div>

                                    <div
                                        class="bg-orange-50 border border-orange-100 rounded p-1 flex flex-col justify-between text-center">
                                        <div>
                                            <div class="text-[6px] uppercase font-bold text-orange-600 mb-0.5">Fallido
                                            </div>
                                            <div class="text-[9px] font-black text-slate-700 leading-tight">
                                                $<?= number_format($valFallido, 0, ',', '.') ?></div>
                                            <div class="w-full bg-orange-200 h-0.5 mt-0.5 rounded-full overflow-hidden">
                                                <div style="width: <?= $pctFall ?>%" class="h-full bg-orange-500"></div>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center w-full mt-1 px-0.5">
                                            <span
                                                class="text-[6px] font-bold text-orange-700 bg-orange-100 px-0.5 rounded"><?= $cantFall ?>
                                                Serv.</span>
                                            <span
                                                class="text-[6px] font-bold text-orange-500"><?= round($pctFall, 1) ?>%</span>
                                        </div>
                                    </div>

                                    <div
                                        class="bg-rose-50 border border-rose-100 rounded p-1 flex flex-col justify-between text-center">
                                        <div>
                                            <div class="text-[6px] uppercase font-bold text-rose-600 mb-0.5">Garantía
                                            </div>
                                            <div class="text-[9px] font-black text-slate-700 leading-tight">
                                                $<?= number_format($valGarantia, 0, ',', '.') ?></div>
                                            <div class="w-full bg-rose-200 h-0.5 mt-0.5 rounded-full overflow-hidden">
                                                <div style="width: <?= $pctGaran ?>%" class="h-full bg-rose-500"></div>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center w-full mt-1 px-0.5">
                                            <span
                                                class="text-[6px] font-bold text-rose-700 bg-rose-100 px-0.5 rounded"><?= $cantGaran ?>
                                                Serv.</span>
                                            <span
                                                class="text-[6px] font-bold text-rose-500"><?= round($pctGaran, 1) ?>%</span>
                                        </div>
                                    </div>

                                    <div
                                        class="bg-violet-50 border border-violet-100 rounded p-1 flex flex-col justify-between text-center">
                                        <div>
                                            <div class="text-[6px] uppercase font-bold text-violet-600 mb-0.5">Repuestos
                                            </div>
                                            <div class="text-[9px] font-black text-slate-700 leading-tight">
                                                $<?= number_format($valRepuestos, 0, ',', '.') ?></div>
                                            <div class="w-full bg-violet-200 h-0.5 mt-0.5 rounded-full overflow-hidden">
                                                <div style="width: <?= $pctRep ?>%" class="h-full bg-violet-500"></div>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center w-full mt-1 px-0.5">
                                            <span
                                                class="text-[6px] font-bold text-violet-700 bg-violet-100 px-0.5 rounded">-</span>
                                            <span
                                                class="text-[6px] font-bold text-violet-500"><?= round($pctRep, 1) ?>%</span>
                                        </div>
                                    </div>

                                </div>
                            </div>


                            <div class="w-full">
                                <div class="flex justify-between items-end mb-1 border-b border-rose-100 pb-0.5">
                                    <h4 class="font-bold text-rose-700 uppercase text-[8px] flex items-center gap-1">
                                        <span class="text-xs">📉</span> Desglose de Costos
                                    </h4>
                                    <span class="font-black text-rose-800 text-xs">$
                                        <?= number_format($totalEgresosOperativos, 0) ?></span>
                                </div>

                                <div class="grid grid-cols-3 gap-1">

                                    <div
                                        class="bg-white border border-emerald-100 shadow-sm rounded p-1.5 flex flex-col justify-between relative overflow-hidden">
                                        <div
                                            class="absolute top-0 right-0 w-8 h-8 bg-emerald-50 rounded-bl-full -mr-2 -mt-2 z-0">
                                        </div>
                                        <div class="relative z-10">
                                            <div class="text-[6px] uppercase font-bold text-emerald-600 mb-0.5">Personal
                                                Motorizado</div>
                                            <div class="text-xs font-black text-slate-700">
                                                $<?= number_format($totalMotorizados, 0, ',', '.') ?></div>
                                        </div>
                                        <div class="flex items-center gap-1 mt-1 relative z-10">
                                            <div class="flex-1 bg-gray-100 h-1 rounded-full overflow-hidden">
                                                <div style="width: <?= $pctMoto ?>%" class="h-full bg-emerald-500">
                                                </div>
                                            </div>
                                            <div class="text-[7px] font-bold text-emerald-600">
                                                <?= round($pctMoto, 1) ?>%
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="bg-white border border-blue-100 shadow-sm rounded p-1.5 flex flex-col justify-between relative overflow-hidden">
                                        <div
                                            class="absolute top-0 right-0 w-8 h-8 bg-blue-50 rounded-bl-full -mr-2 -mt-2 z-0">
                                        </div>
                                        <div class="relative z-10">
                                            <div class="text-[6px] uppercase font-bold text-blue-600 mb-0.5">Nómina
                                                Administrativa</div>
                                            <div class="text-xs font-black text-slate-700">
                                                $<?= number_format($totalNominaAdmin, 0, ',', '.') ?></div>
                                        </div>
                                        <div class="flex items-center gap-1 mt-1 relative z-10">
                                            <div class="flex-1 bg-gray-100 h-1 rounded-full overflow-hidden">
                                                <div style="width: <?= $pctAdm ?>%" class="h-full bg-blue-500"></div>
                                            </div>
                                            <div class="text-[7px] font-bold text-blue-600"><?= round($pctAdm, 1) ?>%
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="bg-white border border-orange-100 shadow-sm rounded p-1.5 flex flex-col justify-between relative overflow-hidden">
                                        <div
                                            class="absolute top-0 right-0 w-8 h-8 bg-orange-50 rounded-bl-full -mr-2 -mt-2 z-0">
                                        </div>
                                        <div class="relative z-10">
                                            <div class="text-[6px] uppercase font-bold text-orange-600 mb-0.5">Gastos
                                                Administrativos</div>
                                            <div class="text-xs font-black text-slate-700">
                                                $<?= number_format($totalGastosGral, 0, ',', '.') ?></div>
                                        </div>
                                        <div class="flex items-center gap-1 mt-1 relative z-10">
                                            <div class="flex-1 bg-gray-100 h-1 rounded-full overflow-hidden">
                                                <div style="width: <?= $pctGral ?>%" class="h-full bg-orange-500"></div>
                                            </div>
                                            <div class="text-[7px] font-bold text-orange-600"><?= round($pctGral, 1) ?>%
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>

                        <div
                            class="bg-slate-50 px-3 py-1 border-t border-slate-200 flex justify-between items-center rounded-b-xl">
                            <p class="text-[7px] text-slate-400 italic">* La utilidad neta se calcula antes de
                                impuestos.
                            </p>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[8px] font-bold text-slate-500 uppercase">Rentabilidad:</span>
                                <span
                                    class="text-[9px] font-black px-1.5 py-0.5 rounded border <?= $margenUtilidad >= 0 ? 'bg-emerald-100 text-emerald-600 border-emerald-200' : 'bg-rose-100 text-rose-600 border-rose-200' ?>">
                                    <?= $margenUtilidad ?>%
                                </span>
                            </div>
                        </div>

                    </div>

                </div>

            </div> <?php endif; ?>









    </div>

</body>

</html>