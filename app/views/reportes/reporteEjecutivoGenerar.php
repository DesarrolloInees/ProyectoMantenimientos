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
            background-color: #f3f4f6; /* Fondo gris suave */
            /* IMPORTANTE: Obliga a imprimir colores de fondo y gradientes */
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* CLASES UTILITARIAS PARA PDF */
        .page-break { page-break-after: always; }
        .break-inside-avoid { break-inside: avoid; }

        /* ESTILOS DE TARJETAS */
        .card {
            background-color: white;
            border-radius: 16px; /* Bordes muy redondos */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #f3f4f6;
            padding: 24px;
            margin-bottom: 24px;
        }

        /* PORTADA */
        .portada-container {
            height: 190mm; /* Altura ajustada para A4 Landscape */
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); /* Gradiente Azul-Indigo */
            position: relative;
            overflow: hidden;
        }

        /* Patrón de fondo sutil */
        .bg-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="p-8 text-slate-800">

    <?php if ($this->seccionActiva('portada')): ?>
    <div class="portada-container w-full rounded-3xl shadow-2xl flex flex-col items-center justify-center text-white relative mb-8">
        <div class="absolute inset-0 bg-pattern opacity-50"></div>
        
        <div class="z-10 text-center">
            <div class="uppercase tracking-[0.3em] text-sm opacity-80 mb-4">Documento Confidencial</div>
            <h1 class="text-7xl font-extrabold mb-2 tracking-tight">Reporte Ejecutivo</h1>
            <p class="text-3xl font-light opacity-90 text-blue-100">Operaciones & Mantenimiento</p>
            
            <div class="mt-12 inline-block bg-white/10 backdrop-blur-md border border-white/20 px-8 py-3 rounded-full">
                <span class="text-lg font-mono">📅 Periodo: <?= date('d/m/Y', strtotime($inicio)) ?> — <?= date('d/m/Y', strtotime($fin)) ?></span>
            </div>
        </div>

        <div class="absolute bottom-12 w-full px-16">
            <div class="grid grid-cols-4 gap-6">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-2xl text-center">
                    <div class="text-5xl font-bold mb-1"><?= number_format($totalServicios) ?></div>
                    <div class="text-xs uppercase tracking-wider opacity-75">Servicios Totales</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-2xl text-center">
                    <div class="text-5xl font-bold mb-1"><?= $mediaDiaria ?></div>
                    <div class="text-xs uppercase tracking-wider opacity-75">Promedio Diario</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-2xl text-center">
                    <div class="text-5xl font-bold mb-1 text-red-200"><?= number_format($datosNovedad['con_novedad'] ?? 0) ?></div>
                    <div class="text-xs uppercase tracking-wider opacity-75">Con Novedades</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-2xl text-center">
                    <div class="text-5xl font-bold mb-1 text-green-200"><?= count($datosDelegacion) ?></div>
                    <div class="text-xs uppercase tracking-wider opacity-75">Delegaciones</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-break"></div>
    <?php endif; ?>


    <div class="grid grid-cols-2 gap-8">

        <div class="flex flex-col gap-6">

            <?php if ($this->seccionActiva('tendencias')): ?>
            <div class="card break-inside-avoid">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-xl">📈</div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Evolución Diaria</h3>
                        <p class="text-xs text-slate-400">Volumen de servicios por día</p>
                    </div>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-400 text-xs uppercase tracking-wider text-left">
                            <th class="pb-3 font-semibold">Fecha</th>
                            <th class="pb-3 text-right font-semibold">Total</th>
                            <th class="pb-3 pl-4 font-semibold w-1/2">Visualización</th>
                        </tr>
                    </thead>
                    <tbody class="space-y-3">
                        <?php 
                        $max = !empty($datosDia) ? max(array_column($datosDia, 'total')) : 1;
                        foreach ($datosDia as $d): 
                            $pct = ($d['total'] / $max) * 100;
                        ?>
                        <tr class="group">
                            <td class="py-2 text-slate-600 font-medium"><?= date('d/m', strtotime($d['fecha_visita'])) ?></td>
                            <td class="py-2 text-right font-bold text-slate-800"><?= $d['total'] ?></td>
                            <td class="py-2 pl-4">
                                <div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full" style="width: <?= $pct ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($this->seccionActiva('mantenimiento')): ?>
            <div class="card break-inside-avoid">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 text-xl">🔧</div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Tipos de Mantenimiento</h3>
                        <p class="text-xs text-slate-400">Distribución operativa</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <?php 
                    $totalT = array_sum(array_column($datosTipo, 'total'));
                    foreach ($datosTipo as $t): 
                        $pct = $totalT > 0 ? round(($t['total'] / $totalT) * 100, 1) : 0;
                    ?>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-slate-700"><?= $t['tipo'] ?></span>
                            <span class="text-xs font-bold bg-purple-50 text-purple-700 px-2 py-1 rounded-md"><?= $t['total'] ?> (<?= $pct ?>%)</span>
                        </div>
                        <div class="h-3 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($this->seccionActiva('puntos_fallidos')): ?>
            <div class="card break-inside-avoid border-l-4 border-l-red-500">
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
            <?php endif; ?>

        </div>

        <div class="flex flex-col gap-6">

            <?php if ($this->seccionActiva('tecnicos')): ?>
            <div class="card break-inside-avoid">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">👷</div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Productividad Técnica</h3>
                        <p class="text-xs text-slate-400">Top Rendimiento (Servicios vs Horas)</p>
                    </div>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-400 text-xs uppercase tracking-wider text-left">
                            <th class="pb-3 font-semibold">Técnico</th>
                            <th class="pb-3 text-right font-semibold">Srv.</th>
                            <th class="pb-3 text-right font-semibold">Hrs.</th>
                            <th class="pb-3 pl-3 font-semibold w-1/3">Eficacia</th>
                        </tr>
                    </thead>
                    <tbody class="space-y-2">
                        <?php 
                        $max = !empty($topTecnicos) ? max(array_column($topTecnicos, 'total_servicios')) : 1;
                        foreach (array_slice($topTecnicos, 0, 12) as $t): 
                            $pct = ($t['total_servicios'] / $max) * 100;
                        ?>
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="py-2 text-slate-700 font-medium text-xs truncate max-w-[120px]"><?= $t['nombre_tecnico'] ?></td>
                            <td class="py-2 text-right font-bold text-emerald-600"><?= $t['total_servicios'] ?></td>
                            <td class="py-2 text-right text-slate-400 text-xs"><?= number_format($t['total_horas'], 1) ?></td>
                            <td class="py-2 pl-3">
                                <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500" style="width: <?= $pct ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($this->seccionActiva('delegaciones')): ?>
            <div class="card break-inside-avoid">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-xl">🏢</div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Top Delegaciones</h3>
                        <p class="text-xs text-slate-400">Intervenciones por zona</p>
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
                        <div class="w-2/3 flex items-center gap-2">
                             <div class="flex-1 h-4 bg-indigo-50 rounded text-center relative overflow-hidden">
                                 <div class="absolute top-0 left-0 h-full bg-indigo-500 opacity-20" style="width: <?= $pct ?>%"></div>
                                 <span class="relative text-[10px] font-bold text-indigo-700 leading-4 block"><?= $d['total'] ?></span>
                             </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($this->seccionActiva('calificaciones')): ?>
            <div class="card break-inside-avoid border-l-4 border-l-amber-400">
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
                    // Ordenar por estrellas si es posible
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
            <?php endif; ?>

        </div>
    </div>
    
    <div class="mt-8 text-center border-t border-slate-200 pt-4">
        <p class="text-xs text-slate-400">
            Reporte generado automáticamente por el Sistema de Gestión | <?= date('d/m/Y H:i:s') ?>
        </p>
    </div>

</body>
</html>