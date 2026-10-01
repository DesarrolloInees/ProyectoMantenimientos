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

<!-- ENCABEZADO CORPORATIVO -->
    <div class="bloque relative border-t-[10px] border-blue-600 rounded-xl overflow-hidden shadow-sm border border-slate-200">
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
                            <?= $diaSemana ?>
                            <?php if ($esFestivo): ?>
                                <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 text-[9px] font-black uppercase">
                                    Festivo
                                </span>
                            <?php endif; ?>
                            <?php if ($esDomingo): ?>
                                <span class="ml-1 px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 text-[9px] font-black uppercase">
                                    No laborable
                                </span>
                            <?php elseif ($esSabado): ?>
                                <span class="ml-1 px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[9px] font-black uppercase">
                                    Jornada de sábado
                                </span>
                            <?php endif; ?>
                        </p>
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow">
                    <div class="text-[9px] uppercase tracking-widest opacity-80 leading-none mb-1">Fecha</div>
                    <div class="text-base font-black leading-none"><?= $rangoInicio ?></div>
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
        $horasTxt = floor($totalHorasDia / 60) . 'h ' . str_pad((string)($totalHorasDia % 60), 2, '0', STR_PAD_LEFT) . 'm';
        $metaTxt = $esDomingo ? 'N/A' : (string)$metaVerde;

        $kpis = [
            ['Servicios del día', number_format($resumenTipos['total'] ?? 0), 'slate-800', 'slate-100', 'slate-400'],
            ['Meta por técnico', $metaTxt, 'emerald-700', 'emerald-50', 'emerald-200'],
            ['Técnicos en ruta', number_format(count($tecnicosOrdenados)), 'blue-700', 'blue-50', 'blue-200'],
            ['Horas laboradas', $horasTxt, 'violet-700', 'violet-50', 'violet-200'],
            ['Servicios fallidos', number_format($resumenTipos['fallido'] ?? 0), 'rose-700', 'rose-50', 'rose-200'],
        ];
        foreach ($kpis as $k):
            ?>
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
                Total: <?= number_format($resumenTipos['total'] ?? 0) ?> servicios
            </span>
        </div>

        <?php
        $totalBase = ($resumenTipos['total'] ?? 0) > 0 ? ($resumenTipos['total']) : 1;
        $barras = [
            ['Preventivo Básico', $resumenTipos['basico'] ?? 0, '#0ea5e9'],
            ['Preventivo Profundo', $resumenTipos['profundo'] ?? 0, '#6366f1'],
            ['Correctivo', $resumenTipos['correctivo'] ?? 0, '#f59e0b'],
            ['Fallido', $resumenTipos['fallido'] ?? 0, '#f43f5e'],
            ['Otros', $resumenTipos['otros'] ?? 0, '#94a3b8'],
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
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Meta cumplida (≥ <?= $metaVerde ?>)
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span> Por debajo (≥ <?= $metaAmarillo ?>)
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> Bajo meta
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-[10px] text-center">
                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="px-3 py-2.5 text-left uppercase tracking-wider text-[9px] w-52">Nombre del Técnico</th>
                        <th class="px-3 py-2.5 uppercase tracking-wider text-[9px] w-40">
                            Servicios
                            <div class="text-[7px] font-medium opacity-70 normal-case tracking-normal">
                                vs meta <?= $metaVerde ?>
                            </div>
                        </th>
                        <?php foreach ($tiposColumnas as $tipo): ?>
                            <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15"
                                style="min-width: 78px;"><?= htmlspecialchars($tipo) ?></th>
                        <?php endforeach; ?>
                        <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16">
                            Entrada
                        </th>
                        <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16">
                            Salida
                        </th>
                        <th class="px-2 py-2.5 uppercase tracking-wider text-[8px] font-bold border-l border-white/15 w-16">
                            Duración
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($tecnicosOrdenados as $tec): ?>
                        <?php
                        $fila = $resumen[$tec] ?? [];
                        $totalTec = array_sum($fila);
                        $jor = $jornada[$tec] ?? ['entrada' => null, 'salida' => null, 'minutos' => 0];
                        $rend = $rendimiento[$tec] ?? [
                            'porcentaje' => 0,
                            'porcentaje_real' => 0,
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

                            <td class="px-3 py-1.5">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span class="text-[12px] font-black text-slate-900"><?= number_format($totalTec) ?></span>
                                    <div class="w-16 h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                        <div class="h-full <?= $rend['color'] ?> rounded-full"
                                            style="width: <?= $rend['porcentaje'] ?>%"></div>
                                    </div>
                                    <span class="text-[9px] font-black <?= $rend['texto'] ?> w-11 text-right">
                                        <?= round($rend['porcentaje_real']) ?>%
                                    </span>
                                </div>
                            </td>

                            <?php foreach ($tiposColumnas as $tipo):
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
                            <td class="px-2 py-1.5 border-l border-slate-100 font-bold text-slate-600">
                                <?= $jor['entrada'] !== null ? htmlspecialchars($jor['entrada']) : '--' ?>
                            </td>
                            <td class="px-2 py-1.5 border-l border-slate-100 font-bold text-slate-600">
                                <?= $jor['salida'] !== null ? htmlspecialchars($jor['salida']) : '--' ?>
                            </td>
                            <td class="px-2 py-1.5 border-l border-slate-100 font-black text-slate-800">
                                <?= $durTxt ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <tfoot>
                    <tr class="bg-blue-600 text-white">
                        <td class="px-3 py-2.5 text-left font-black uppercase tracking-wider text-[10px]">TOTALES</td>
                        <td class="px-3 py-2.5 text-center font-black text-sm"><?= number_format($totalGeneral) ?></td>
                        <?php foreach ($tiposColumnas as $tipo): ?>
                            <td class="px-1 py-2.5 text-center font-black border-l border-white/20">
                                <?= number_format($totalesColumna[$tipo] ?? 0) ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="px-2 py-2.5 text-center font-black border-l border-white/20 text-white/70">--</td>
                        <td class="px-2 py-2.5 text-center font-black border-l border-white/20 text-white/70">--</td>
                        <td class="px-2 py-2.5 text-center font-black border-l border-white/20">
                            <?= floor($totalHorasDia / 60) . 'h ' . str_pad((string)($totalHorasDia % 60), 2, '0', STR_PAD_LEFT) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="text-[8.5px] text-slate-400 italic mt-1.5">
            (-) = Sin actividad registrada. Entrada/Salida corresponden al primer ingreso y la última salida del día.
            Duración = horas laboradas. La barra mide el cumplimiento de la meta diaria
            (<?= $esSabado ? 'Sábado: verde ≥ ' . $metaVerde : 'Día hábil: verde ≥ ' . $metaVerde ?> servicios,
            amarillo ≥ <?= $metaAmarillo ?>, rojo por debajo).
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
                Total fallidos: <?= number_format($totalFallidos) ?>
            </span>
        </div>

        <?php if (empty($fallidos)): ?>
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
                            <th class="px-3 py-2 uppercase tracking-wider text-[9px] w-56">Delegación</th>
                            <th class="px-3 py-2 uppercase tracking-wider text-[9px]">Cliente</th>
                            <th class="px-3 py-2 uppercase tracking-wider text-[9px] text-right w-28">Fallidos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100">
                        <?php
                        $maxFila = 1;
                        foreach ($fallidos as $items) {
                            foreach ($items as $it) {
                                if (is_array($it) && isset($it['total'])) {
                                    $maxFila = max($maxFila, (int)$it['total']);
                                }
                            }
                        }

                        foreach ($fallidos as $delegacion => $items):
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
                                <td class="px-3 py-1.5 text-[8.5px] text-rose-400 font-bold uppercase tracking-wide">
                                    Subtotal delegación
                                </td>
                                <td class="px-3 py-1.5 text-right font-black text-rose-700"><?= number_format($totalZona) ?></td>
                            </tr>

                            <?php foreach ($items as $it):
                                $itCli = (string)($it['cliente'] ?? 'Cliente');
                                $itTot = (int)($it['total'] ?? 0);
                                ?>
                                <tr class="no-corte">
                                    <td class="px-3 py-1 pl-8"></td>
                                    <td class="px-3 py-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-slate-700 font-semibold"><?= htmlspecialchars($itCli) ?></span>
                                            <div
                                                class="flex-1 h-1.5 bg-rose-50 rounded-full overflow-hidden border border-rose-100 max-w-[320px]">
                                                <div class="h-full bg-rose-500 rounded-full"
                                                    style="width: <?= $maxFila > 0 ? ($itTot / $maxFila) * 100 : 0 ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-1 text-right font-black text-rose-600"><?= number_format($itTot) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-rose-800 text-white">
                            <td class="px-3 py-2 font-black uppercase tracking-wider" colspan="2">
                                TOTAL SERVICIOS FALLIDOS
                            </td>
                            <td class="px-3 py-2 text-right font-black text-sm"><?= number_format($totalFallidos) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>