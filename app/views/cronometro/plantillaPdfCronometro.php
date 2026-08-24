<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Rendimiento - Flota Motorizada</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            color: #0f172a;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            padding: 15px 0;
        }

        .page {
            width: 297mm; /* Ancho landscape */
            min-height: 210mm; /* Alto landscape */
            background: #ffffff;
            padding: 8mm 12mm;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg, #4f46e5, #7c3aed, #4f46e5) 1;
            margin-bottom: 16px;
        }

        .header img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }

        .header-text {
            text-align: right;
        }

        .header-text h1 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.3px;
            background: linear-gradient(135deg, #3730a3, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }

        .header-text .sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }

        .header-text .sub strong {
            color: #0f172a;
            font-weight: 700;
        }

        .header-text .meta-badge {
            display: inline-block;
            background: #eef2ff;
            color: #3730a3;
            padding: 3px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 10px;
            margin-top: 4px;
            border: 1px solid #c7d2fe;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .summary-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px 14px;
            border: 1px solid #e9edf4;
            text-align: center;
        }

        .summary-card .number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.2;
        }

        .summary-card .number.primary { color: #4f46e5; }
        .summary-card .number.green { color: #16a34a; }
        .summary-card .number.amber { color: #d97706; }
        .summary-card .number.rose { color: #dc2626; }

        .summary-card .label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            margin-top: 2px;
        }

        .summary-card .icon {
            font-size: 16px;
            display: block;
            margin-bottom: 2px;
        }

        .chart-container {
            width: 100%;
            margin-bottom: 16px;
            text-align: center;
            background: #fafcff;
            border-radius: 12px;
            padding: 5px;
            border: 1px solid #e9edf4;
        }

        .chart-container svg {
            width: 100%;
            height: auto;
            max-height: 320px;
        }

        .table-wrapper {
            /* 🔥 FORZA A QUE LA TABLA COMIENCE SIEMPRE EN UNA PÁGINA NUEVA */
            page-break-before: always;
            break-before: page;
            
            border-radius: 10px;
            border: 1px solid #e9edf4;
            overflow: hidden;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 5px;
            text-align: center;
            border-bottom: 2px solid #d1d9e6;
        }

        th.text-left {
            text-align: left;
        }

        td {
            padding: 7px 5px;
            border-bottom: 1px solid #eef2f6;
            text-align: center;
            font-size: 9px;
            vertical-align: middle;
        }

        td.text-left {
            text-align: left;
            font-weight: 600;
            color: #0f172a;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .progress-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
        }

        .progress-bar-bg {
            width: 60px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
        }

        .progress-label {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 9px;
            min-width: 35px;
            text-align: right;
        }

        .badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
            white-space: nowrap;
        }

        .badge-green {
            background: #dcfce7;
            color: #166534;
        }

        .badge-amber {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-red {
            background: #fee2e2;
            color: #991b1b;
        }

        .footer-stats {
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px 18px;
            border: 1px solid #e9edf4;
            font-size: 10px;
            color: #475569;
        }

        .footer-stats strong {
            color: #0f172a;
            font-weight: 700;
        }

        .footer-stats .highlight {
            color: #4f46e5;
            font-weight: 800;
            font-size: 13px;
            font-family: 'JetBrains Mono', monospace;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 12mm 15mm; /* Márgenes nativos del PDF */
            }
            
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .page {
                box-shadow: none;
                border-radius: 0;
                padding: 0; /* Quitamos padding interno para evitar saltos raros */
                width: 100%;
                min-height: auto;
            }

            /* Reglas estrictas para evitar que la tabla se rompa mal */
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid; /* Evita que una fila se corte por la mitad */
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group; /* Repite el encabezado en cada página nueva */
            }
            
            tfoot {
                display: table-footer-group;
            }

            /* Preservar colores de fondo en Chrome/Puppeteer */
            .summary-card, .badge, .progress-bar-fill, th, .meta-badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Quitar la sombra y borde de la gráfica en PDF para ahorrar espacio */
            .chart-container {
                border: none;
                padding: 0;
                margin-bottom: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- HEADER -->
        <div class="header">
            <div>
                <img src="<?= BASE_URL ?>app/logos/logoInees.jpg" alt="Logo INEES">
            </div>
            <div class="header-text">
                <h1>Rendimiento de Flota</h1>
                <div class="sub">
                    Periodo: <strong><?= date('d/m/Y', strtotime($fechaInicio)) ?></strong> al
                    <strong><?= date('d/m/Y', strtotime($fechaFin)) ?></strong>
                </div>
                <span class="meta-badge">🎯 Meta: <?= $metaTotalServicios ?> servicios / técnico</span>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="summary-grid">
            <?php
                $totalTecnicos = count($rendimientoRaw);
                $totalFinalizados = array_sum(array_column($rendimientoRaw, 'total_finalizados'));
                $totalEnProgreso = array_sum(array_column($rendimientoRaw, 'total_en_progreso'));
                $tecnicosMetaCumplida = 0;
                foreach ($rendimientoRaw as $r) {
                    $pct = ($metaTotalServicios > 0) ? round(($r['total_finalizados'] / $metaTotalServicios) * 100, 1) : 0;
                    if ($pct >= 100) $tecnicosMetaCumplida++;
                }
            ?>
            <div class="summary-card">
                <span class="icon">👷</span>
                <div class="number primary"><?= $totalTecnicos ?></div>
                <div class="label">Técnicos Activos</div>
            </div>
            <div class="summary-card">
                <span class="icon">✅</span>
                <div class="number green"><?= $totalFinalizados ?></div>
                <div class="label">Servicios Finalizados</div>
            </div>
            <div class="summary-card">
                <span class="icon">⏳</span>
                <div class="number amber"><?= $totalEnProgreso ?></div>
                <div class="label">En Progreso</div>
            </div>
            <div class="summary-card">
                <span class="icon">🏆</span>
                <div class="number rose"><?= $tecnicosMetaCumplida ?></div>
                <div class="label">Meta Cumplida</div>
            </div>
        </div>

        <!-- GRÁFICO SVG -->
        <div class="chart-container">
            <?= $svgGrafica ?>
        </div>

        <!-- TABLA -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="text-left">Técnico</th>
                        <?php foreach ($tipos as $tipo): ?>
                            <th><?= htmlspecialchars($tipo['nombre_completo']) ?></th>
                        <?php endforeach; ?>
                        <th>✅ Finalizados</th>
                        <th>⏳ En Progreso</th>
                        <th>🎯 Meta</th>
                        <th>📊 Cumplimiento</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rendimientoRaw as $r):
                        $finalizados = (int) $r['total_finalizados'];
                        $pct = ($metaTotalServicios > 0) ? round(($finalizados / $metaTotalServicios) * 100, 1) : 0;

                        if ($pct >= 100) { $barColor = '#22c55e'; $claseBadge = 'badge-green'; $textoEstado = '🏆 Meta'; }
                        elseif ($pct >= 70) { $barColor = '#eab308'; $claseBadge = 'badge-amber'; $textoEstado = '⚡ Aceptable'; }
                        else { $barColor = '#ef4444'; $claseBadge = 'badge-red'; $textoEstado = '⚠️ Crítico'; }

                        $barWidth = min($pct, 100);
                    ?>
                    <tr>
                        <td class="text-left"><?= htmlspecialchars($r['nombre_tecnico']) ?></td>
                        <?php foreach ($tipos as $tipo):
                            $alias = 'tipo_' . $tipo['id_tipo_mantenimiento'];
                            $valor = isset($r[$alias]) ? (int)$r[$alias] : 0;
                        ?>
                            <td><?= $valor ?></td>
                        <?php endforeach; ?>
                        <td><strong><?= $finalizados ?></strong></td>
                        <td><?= (int) $r['total_en_progreso'] ?></td>
                        <td><?= $metaTotalServicios ?></td>
                        <td>
                            <div class="progress-wrap">
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: <?= $barWidth ?>%; background: <?= $barColor ?>;"></div>
                                </div>
                                <span class="progress-label" style="color: <?= $barColor ?>;"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td><span class="badge <?= $claseBadge ?>"><?= $textoEstado ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- FOOTER -->
        <div class="footer-stats">
            <span>📋 <strong><?= $totalTecnicos ?></strong> técnicos evaluados · <strong><?= $totalFinalizados + $totalEnProgreso ?></strong> servicios totales</span>
            <span>🎯 <span class="highlight"><?= $tecnicosMetaCumplida ?></span> de <?= $totalTecnicos ?> técnicos cumplieron la meta</span>
        </div>
    </div>
</body>
</html>