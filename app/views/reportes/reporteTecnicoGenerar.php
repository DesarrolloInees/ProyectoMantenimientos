<?php
// app/views/reportes/reporteTecnicoGenerar.php
// Genera la Hoja 1 del Excel (Resumen por Tecnico + Tabla de Fallidos) como PDF.
// Variables esperadas del controlador:
//   $logoBase64, $rangoInicio, $rangoFin, $diaSemana, $esFestivo, $totalHorasDia,
//   $resumenTipos, $resumen, $jornada, $rendimiento, $tiposColumnas, $tecnicosOrdenados,
//   $totalesColumna, $totalGeneral, $fallidos, $totalFallidos,
//   $metaVerde, $metaAmarillo, $esSabado, $esDomingo
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Servicios por Técnico</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        thead {
            display: table-header-group;
        }

        tr,
        .no-corte,
        .bloque {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        table {
            border-collapse: collapse;
        }
    </style>
</head>

<body class="text-slate-800">

    <style>
        .dia-seccion:last-child {
            page-break-after: auto;
            break-after: auto;
        }
    </style>

    <?php foreach ($diasProcesados as $idxDia => $dia): ?>
        <?php
        $esUltimoDia = ($idxDia === array_key_last($diasProcesados));
        $sinMetaDia = !empty($dia['esNoLaboral']);
        $metaTotalDia = 0;
        foreach ($dia['rendimiento'] as $rMeta) {
            $metaTotalDia += (int)($rMeta['meta'] ?? 0);
        }
        ?>

        <!-- ENCABEZADO CORPORATIVO -->
        <div class="bloque dia-seccion relative border-t-[10px] border-blue-600 rounded-xl overflow-hidden shadow-sm border border-slate-200"
            style="<?php echo ($idxDia > 0) ? 'page-break-before: always;' : ''; ?>">
            <div class="absolute inset-y-0 right-0 w-1/3 bg-gradient-to-l from-blue-50 to-transparent"></div>

            <div class="relative flex items-center justify-between px-8 py-6">

                <div class="flex items-center gap-6">
                    <?php if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" class="h-20 w-auto object-contain" alt="Logo Empresa">
                    <?php endif; ?>

                    <div>
                        <div class="text-[9px] font-black uppercase tracking-[4px] text-blue-600 mb-1">
                                Reporte Diario
                            </div>
                            <h1 class="text-3xl font-black text-slate-800 leading-none tracking-tight">
                                SERVICIOS DEL DÍA <span class="text-blue-600">POR TÉCNICO</span>
                            </h1>
                            <p class="text-[11px] text-slate-500 mt-2 font-medium">
                                <?= $dia['diaSemana'] ?>
                                <?php if ($dia['esFestivo']): ?>
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 text-[9px] font-black uppercase">
                                        Festivo
                                    </span>
                                <?php endif; ?>
                                <?php if ($dia['esDomingo']): ?>
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 text-[9px] font-black uppercase">
                                        No laborable
                                    </span>
                                <?php elseif ($dia['esSabado']): ?>
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[9px] font-black uppercase">
                                        Jornada de sábado
                                    </span>
                                <?php endif; ?>
                            </p>
                            <?php if (!$sinMetaDia): ?>
                                <div class="mt-2 flex items-center gap-2 flex-wrap">
                                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 text-[9px] font-bold uppercase tracking-wide">
                                        <?= $dia['metaVerde'] ?> por técnico · Nueva Arq. <?= $dia['metaNuevaArquitectura'] ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                    </div>
                </div>

                <div class="text-right">
                    <div class="inline-block bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow">
                        <div class="text-[9px] uppercase tracking-widest opacity-80 leading-none mb-1">Fecha</div>
                        <div class="text-base font-black leading-none"><?= $dia['rangoInicio'] ?></div>
                    </div>
                    <div class="mt-3 text-[10px] text-slate-400 font-medium leading-tight">
                        Generado el <?= date('d/m/Y H:i') ?><br>
                        Documento confidencial
                    </div>
                </div>

            </div>
        </div>

    <div class="grid grid-cols-5 gap-3 mt-5 bloque">
        <?php
        $horasTxt = floor($dia['totalHorasDia'] / 60) . 'h ' . str_pad((string)($dia['totalHorasDia'] % 60), 2, '0', STR_PAD_LEFT) . 'm';
        $metaTxt = $sinMetaDia ? 'N/A' : (string)$dia['metaVerde'];
        if (!$sinMetaDia && !empty($dia['hayNuevaArquitectura'])) {
            $metaTxt = $dia['metaVerde'] . ' / ' . $dia['metaNuevaArquitectura'] . ' Nueva Arq.';
        }

        $totalServiciosDia = (int)($dia['resumenTipos']['total'] ?? 0);
        $serviciosSinFallidosDia = 0;
        foreach ($dia['rendimiento'] as $r) {
            $serviciosSinFallidosDia += (int)($r['sin_fallidos'] ?? 0);
        }
        $pctCumplimiento = ($metaTotalDia > 0) ? (int)round(($serviciosSinFallidosDia / $metaTotalDia) * 100) : 0;
        if ($metaTotalDia <= 0) {
            $pctColor = 'text-slate-400';
        } elseif ($pctCumplimiento >= 100) {
            $pctColor = 'text-emerald-700';
        } elseif ($pctCumplimiento >= 66) {
            $pctColor = 'text-amber-700';
        } else {
            $pctColor = 'text-rose-700';
        }

        $kpis = [
            ['Meta por técnico', $metaTxt, 'emerald-700', 'emerald-50', 'emerald-200'],
            ['Técnicos en ruta', number_format(count($dia['tecnicosOrdenados'])), 'blue-700', 'blue-50', 'blue-200'],
            ['Horas laboradas', $horasTxt, 'violet-700', 'violet-50', 'violet-200'],
            ['Servicios fallidos', number_format($dia['resumenTipos']['fallido'] ?? 0), 'rose-700', 'rose-50', 'rose-200'],
        ];
        ?>

        <!-- Servicios del día + Meta del día + % de cumplimiento -->
        <div class="border rounded-xl p-3.5 border-slate-200 bg-slate-50">
            <div class="text-[8.5px] uppercase font-black tracking-wide text-slate-400 leading-none mb-1.5">
                Servicios del día
            </div>
            <div class="flex items-end justify-between gap-2">
                <div class="text-2xl font-black text-slate-800 leading-none">
                    <?= number_format($totalServiciosDia) ?>
                </div>
                <div class="text-right leading-none">
                    <?php if ($metaTotalDia > 0): ?>
                        <div class="text-[13px] font-black text-emerald-700 leading-none">
                            Meta <?= number_format($metaTotalDia) ?>
                        </div>
                        <div class="text-[16px] font-black <?= $pctColor ?> leading-none mt-1"
                            title="Cumplimiento calculado con servicios sin fallidos">
                            <?= $pctCumplimiento ?>% cumpl.
                        </div>
                    <?php else: ?>
                        <div class="text-[10px] font-black text-slate-400 leading-none">Meta N/A</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php foreach ($kpis as $k): ?>
            <div class="border rounded-xl p-3.5 border-<?= $k[4] ?> bg-<?= $k[3] ?>">
                <div class="text-[8.5px] uppercase font-black tracking-wide text-<?= $k[4] ?> leading-none mb-1.5">
                    <?= $k[0] ?>
                </div>
                <div class="text-2xl font-black text-<?= $k[2] ?> leading-none"><?= $k[1] ?></div>
            </div>
        <?php endforeach; ?>
    </div>

<!-- DISTRIBUCION POR TIPO -->
    <div class="mt-5 bloque bg-slate-50 border border-slate-200 rounded-xl p-4">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600">Distribución por tipo de servicio</h2>
            <span class="text-[9px] text-slate-400 font-bold uppercase">
                Total: <?= number_format($dia['resumenTipos']['total'] ?? 0) ?> servicios
            </span>
        </div>

        <?php
        $totalBase = ($dia['resumenTipos']['total'] ?? 0) > 0 ? ($dia['resumenTipos']['total']) : 1;
        $barras = [
            ['Preventivo Básico', $dia['resumenTipos']['basico'] ?? 0, '#0ea5e9'],
            ['Preventivo Profundo', $dia['resumenTipos']['profundo'] ?? 0, '#6366f1'],
            ['Correctivo', $dia['resumenTipos']['correctivo'] ?? 0, '#f59e0b'],
            ['Fallido', $dia['resumenTipos']['fallido'] ?? 0, '#f43f5e'],
            ['Otros', $dia['resumenTipos']['otros'] ?? 0, '#94a3b8'],
        ];
        foreach ($barras as $b):
            $pct = round(($b[1] / $totalBase) * 100, 1);
            ?>
            <div class="flex items-center gap-3 mb-1.5">
                <div class="w-40 text-[10px] font-bold text-slate-600 text-right shrink-0"><?= $b[0] ?></div>
                <div class="flex-1 h-4 bg-white rounded-full border border-slate-200 overflow-hidden">
                    <div class="h-full rounded-full" style="width: <?= $pct ?>%; background: <?= $b[2] ?>;"></div>
                </div>
                <div class="w-24 shrink-0">
                    <span class="text-[11px] font-black text-slate-800"><?= number_format($b[1]) ?></span>
                    <span class="text-[9px] font-bold text-slate-400 ml-1"><?= $pct ?>%</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<!-- TABLA PRINCIPAL: RESUMEN POR TECNICO -->
    <div class="mt-5 bloque">
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600">
                Productividad por técnico del día
            </h2>
            <div class="flex items-center gap-3 text-[8.5px] font-bold text-slate-500">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Meta cumplida (≥ meta del técnico)
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span> Por debajo (≥ 2/3 de la meta)
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> Bajo meta
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-rose-300"></span> Fallidos (marcados en la barra)
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-[10px] text-center">
                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="px-3 py-2.5 text-left uppercase tracking-wider text-[9px] w-52">Nombre del Técnico</th>
                        <th class="px-3 py-2.5 uppercase tracking-wider text-[9px] w-24">
                            Servicios
                            <div class="text-[7px] font-medium opacity-70 normal-case tracking-normal">
                                total
                            </div>
                        </th>
                        <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16">
                            Fallidos
                            <div class="text-[7px] font-medium opacity-70 normal-case tracking-normal">
                                del total
                            </div>
                        </th>
                        <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-20">
                            Meta
                            <div class="text-[7px] font-medium opacity-70 normal-case tracking-normal">
                                esperada
                            </div>
                        </th>
                        <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16">
                            Entrada
                        </th>
                        <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16">
                            Salida
                        </th>
                        <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16">
                            Duración
                        </th>
                        <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-28">
                            Desplazamiento
                            <div class="text-[7px] font-medium opacity-70 normal-case tracking-normal">
                                entre servicios
                            </div>
                        </th>
                        <?php foreach ($dia['tiposColumnas'] as $tipo): ?>
                            <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15"
                                style="min-width: 78px;"><?= htmlspecialchars($tipo) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($dia['tecnicosOrdenados'] as $tec): ?>
                        <?php
                        $fila = $dia['resumen'][$tec] ?? [];
                        $totalTec = array_sum($fila);
                        $jor = $dia['jornada'][$tec] ?? ['entrada' => null, 'salida' => null, 'minutos' => 0];
                        $rend = $dia['rendimiento'][$tec] ?? [
                            'servicios' => 0,
                            'sin_fallidos' => 0,
                            'fallidos' => 0,
                            'meta' => 0,
                            'meta_amarillo' => 0,
                            'es_na' => false,
                            'porcentaje' => 0,
                            'porcentaje_real' => 0,
                            'porcentaje_fallidos' => 0,
                            'ancho_fallidos' => 0,
                            'color' => 'bg-slate-300',
                            'texto' => 'text-slate-400',
                            'semaforo' => 'Sin meta'
                        ];

                        $durTxt = ($jor['minutos'] > 0)
                            ? floor($jor['minutos'] / 60) . 'h ' . str_pad((string)($jor['minutos'] % 60), 2, '0', STR_PAD_LEFT)
                            : '--';
                        ?>
                        <tr class="no-corte">
                            <td class="px-3 py-1.5 text-left bg-slate-50/60">
                                <span class="font-black text-slate-800 uppercase"><?= htmlspecialchars($tec) ?></span>
                            </td>

                            <?php $hayFallidosTec = ((int)($rend['fallidos'] ?? 0)) > 0; ?>
                            <td class="px-3 py-1.5">
                                <div class="flex flex-col items-center gap-1">
                                    <div class="text-[15px] font-black text-slate-900 leading-none">
                                        <?= number_format($rend['servicios']) ?>
                                    </div>
                                    <div class="flex w-28 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                        <div class="h-full <?= $rend['color'] ?>"
                                            style="width: <?= $rend['porcentaje'] ?>%"></div>
                                        <?php if ($hayFallidosTec): ?>
                                            <div class="h-full bg-rose-300"
                                                style="width: <?= $rend['ancho_fallidos'] ?>%"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[8px] font-black <?= $rend['texto'] ?> leading-none">
                                        <?php if ($rend['meta'] > 0): ?>
                                            <?= round($rend['porcentaje_real']) ?>%
                                        <?php else: ?>
                                            Sin meta
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td class="px-2 py-1.5 border-l border-slate-100">
                                <?php if ($hayFallidosTec): ?>
                                    <span class="inline-block min-w-[24px] px-2 py-0.5 rounded bg-rose-50 border border-rose-200 text-rose-700 font-black">
                                        <?= number_format($rend['fallidos']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300 font-bold">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-2 py-1.5 border-l border-slate-100">
                                <?php if ($rend['meta'] > 0): ?>
                                    <span class="text-[13px] font-black text-slate-900"><?= number_format($rend['meta']) ?></span>
                                    <?php if ($rend['es_na']): ?>
                                        <div class="text-[7px] font-black text-indigo-600 uppercase leading-none mt-0.5">Nueva Arq.</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-300 font-bold">--</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-2 py-1.5 border-l border-slate-100 font-bold text-slate-600">
                                <?= $jor['entrada'] !== null ? htmlspecialchars($jor['entrada']) : '--' ?>
                            </td>
                            <td class="px-2 py-1.5 border-l border-slate-100 font-bold text-slate-600">
                                <?= $jor['salida'] !== null ? htmlspecialchars($jor['salida']) : '--' ?>
                            </td>
                            <td class="px-2 py-1.5 border-l border-slate-100 font-black text-slate-800">
                                <?= $durTxt ?>
                            </td>

                            <?php
                            $dsp = $dia['desplazamientos'][$tec] ?? ['total_min' => 0, 'total_txt' => '--', 'tramos' => []];
                            ?>
                            <td class="px-2 py-1.5 border-l border-slate-100">
                                <div class="font-black text-slate-800 leading-none">
                                    <?= htmlspecialchars($dsp['total_txt'] ?? '--') ?>
                                </div>
                                <?php if (!empty($dsp['tramos'])): ?>
                                    <div class="text-[7.5px] font-semibold text-slate-400 leading-tight mt-0.5">
                                        <?= htmlspecialchars(implode(' · ', $dsp['tramos'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <?php foreach ($dia['tiposColumnas'] as $tipo):
                                $cant = $fila[$tipo] ?? 0;
                                ?>
                                <td class="px-1 py-1.5 border-l border-slate-100">
                                    <?php if ($cant > 0): ?>
                                        <span class="inline-block min-w-[22px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-800 font-black">
                                            <?= number_format($cant) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-300 font-bold">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <tfoot>
                    <?php
                    $totalDesp = (int)($dia['totalDesplazamientoDia'] ?? 0);
                    $totalDespTxt = '--';
                    if ($totalDesp > 0) {
                        $totalDespTxt = floor($totalDesp / 60) . 'h ' . str_pad((string)($totalDesp % 60), 2, '0', STR_PAD_LEFT) . 'm';
                    }
                    ?>
                    <tr class="bg-blue-600 text-white">
                        <td class="px-3 py-2.5 text-left font-black uppercase tracking-wider text-[10px]">TOTALES</td>
                        <td class="px-3 py-2.5 text-center font-black text-sm"><?= number_format($dia['totalGeneral']) ?></td>
                        <td class="px-2 py-2.5 text-center font-black border-l border-white/20 text-rose-100">
                            <?= number_format($dia['totalFallidos'] ?? 0) ?>
                        </td>
                        <td class="px-2 py-2.5 text-center font-black text-sm border-l border-white/20">
                            <?= $sinMetaDia ? '--' : number_format($metaTotalDia) ?>
                        </td>
                        <td class="px-2 py-2.5 text-center font-black border-l border-white/20 text-white/70">--</td>
                        <td class="px-2 py-2.5 text-center font-black border-l border-white/20 text-white/70">--</td>
                        <td class="px-2 py-2.5 text-center font-black border-l border-white/20">
                            <?= floor($dia['totalHorasDia'] / 60) . 'h ' . str_pad((string)($dia['totalHorasDia'] % 60), 2, '0', STR_PAD_LEFT) ?>
                        </td>
                        <td class="px-2 py-2.5 text-center font-black border-l border-white/20">
                            <?= $totalDespTxt ?>
                        </td>
                        <?php foreach ($dia['tiposColumnas'] as $tipo): ?>
                            <td class="px-1 py-2.5 text-center font-black border-l border-white/20">
                                <?= number_format($dia['totalesColumna'][$tipo] ?? 0) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="text-[8.5px] text-slate-400 italic mt-1.5">
            (-) = Sin actividad registrada. Entrada/Salida corresponden al primer ingreso y la última salida del día.
            Meta = servicios esperados del técnico según el día: hábil 6, sábado 3, Nueva Arquitectura
            <?= (int)$dia['metaNuevaArquitectura'] ?> (<?= $dia['esSabado'] ? 'sábado' : 'día hábil' ?>).
            La barra muestra los servicios sin fallidos (color sólido) y los fallidos (tono claro) sobre esa meta.
            Domingos y festivos no tienen meta. El porcentaje se calcula solo con servicios sin fallidos.
            Desplazamiento = tiempo entre la salida de un servicio y la entrada al siguiente del mismo técnico, descontando 1 hora de almuerzo (franja 12:00–14:00).
        </p>
    </div>

<!-- TABLA DE FALLIDOS -->
    <div class="mt-5 bloque">
        <div class="flex justify-between items-center mb-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 text-xs font-black">
                    !
                </div>
                <div>
                    <h2 class="text-xs font-black uppercase tracking-wider text-rose-700">Tabla de fallidos</h2>
                    <p class="text-[9px] text-slate-400 leading-none">Clientes con servicios fallidos en la jornada</p>
                </div>
            </div>
            <span class="text-[10px] font-black text-rose-700 bg-rose-50 border border-rose-200 px-3 py-1 rounded-full">
                Total fallidos: <?= number_format($dia['totalFallidos']) ?>
            </span>
        </div>

        <?php if (empty($dia['fallidos'])): ?>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-5 text-center">
                <p class="text-[12px] font-black text-emerald-700">
                    Excelente gestión: ningún cliente registra servicios fallidos en este periodo.
                </p>
            </div>
        <?php else: ?>

            <div class="rounded-xl border border-rose-200 overflow-hidden">
                <table class="w-full text-[10px] text-left">
                    <thead class="bg-rose-700 text-white">
                        <tr>
                            <th class="px-3 py-2 uppercase tracking-wider text-[9px] w-52">Delegación</th>
                            <th class="px-3 py-2 uppercase tracking-wider text-[9px]">Cliente</th>
                            <th class="px-3 py-2 uppercase tracking-wider text-[9px]">Novedad (revisión)</th>
                            <th class="px-3 py-2 uppercase tracking-wider text-[9px] text-right w-24">Fallidos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100">
                        <?php
                        $maxFila = 1;
                        foreach ($dia['fallidos'] as $items) {
                            foreach ($items as $it) {
                                if (is_array($it) && isset($it['total'])) {
                                    $maxFila = max($maxFila, (int)$it['total']);
                                }
                            }
                        }

                        foreach ($dia['fallidos'] as $delegacion => $items):
                            $items = is_array($items) ? $items : [];
                            $totalZona = 0;
                            foreach ($items as $it) {
                                if (is_array($it)) {
                                    $totalZona += (int)($it['total'] ?? 0);
                                }
                            }
                            ?>
                            <tr class="no-corte bg-rose-50/70">
                                <td class="px-3 py-1.5 font-black text-rose-800 uppercase">
                                    - <?= htmlspecialchars((string)$delegacion) ?>
                                </td>
                                <td class="px-3 py-1.5 text-[8.5px] text-rose-400 font-bold uppercase tracking-wide" colspan="2">
                                    Subtotal delegación
                                </td>
                                <td class="px-3 py-1.5 text-right font-black text-rose-700"><?= number_format($totalZona) ?></td>
                            </tr>

                            <?php foreach ($items as $it):
                                $itCli = (string)($it['cliente'] ?? 'Cliente');
                                $itPunto = !empty($it['punto'] ?? '') ? $it['punto'] : '';
                                $itTot = (int)($it['total'] ?? 0);
                                $itNov = trim((string)($it['novedad'] ?? ''));
                                ?>
                                <tr class="no-corte">
                                    <td class="px-3 py-1 pl-8"></td>
                                    <td class="px-3 py-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-slate-700 font-semibold"><?= htmlspecialchars($itCli) ?></span>
                                            <?php if ($itPunto !== ''): ?>
                                                <span class="text-slate-500 text-[10px] font-normal">(<?= htmlspecialchars($itPunto) ?>)</span>
                                            <?php endif; ?>
                                            <div
                                                class="flex-1 h-1.5 bg-rose-50 rounded-full overflow-hidden border border-rose-100 max-w-[320px]">
                                                <div class="h-full bg-rose-500 rounded-full"
                                                    style="width: <?= $maxFila > 0 ? ($itTot / $maxFila) * 100 : 0 ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-1 text-[8.5px] leading-tight max-w-[420px]">
                                        <?php if ($itNov !== ''): ?>
                                            <span class="text-slate-700 font-semibold"><?= htmlspecialchars($itNov) ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-300 italic">Sin novedad registrada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-1 text-right font-black text-rose-600">
                                        <?= ($itTot == 1) ? '-' : number_format($itTot) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-rose-800 text-white">
                            <td class="px-3 py-2 font-black uppercase tracking-wider" colspan="3">
                                TOTAL SERVICIOS FALLIDOS
                            </td>
                            <td class="px-3 py-2 text-right font-black text-sm"><?= number_format($dia['totalFallidos']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

</body>

</html>