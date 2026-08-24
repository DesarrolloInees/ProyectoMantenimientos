<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Horas Extra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            padding: 10mm 12mm;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .header img {
            height: 52px;
            width: auto;
            object-fit: contain;
        }

        .header-title {
            text-align: right;
        }

        .header-title h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.4px;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
        }

        .header-title p {
            font-size: 11px;
            color: #475569;
            font-weight: 500;
            margin-top: 2px;
        }

        .header-title p strong {
            color: #0f172a;
            font-weight: 700;
        }

        .divider {
            height: 3px;
            background: linear-gradient(90deg, #2563eb, #1e3a8a, #2563eb);
            border-radius: 4px;
            margin-bottom: 18px;
        }

        /* TABLA */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e9edf4;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        table.report-table th {
            background: #f8fafc;
            color: #1e293b;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 10px 10px;
            text-align: left;
            border-bottom: 2px solid #d1d9e6;
        }

        table.report-table td {
            padding: 10px 10px;
            border-bottom: 1px solid #eef2f6;
            vertical-align: middle;
            color: #0f172a;
        }

        table.report-table tr:last-child td {
            border-bottom: none;
        }

        .tecnico {
            font-weight: 700;
            color: #0f172a;
        }

        .cliente-detalle {
            font-size: 9px;
            color: #64748b;
            display: block;
            margin-top: 2px;
        }

        .horario {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            font-size: 10px;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-block;
            color: #0f172a;
        }

        .horas {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 12px;
            color: #1e3a8a;
            text-align: center;
        }

        /* BADGES */
        .badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
            white-space: nowrap;
        }

        .badge-pendiente {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-aprobada {
            background: #dcfce7;
            color: #14532d;
        }

        .badge-rechazada {
            background: #fee2e2;
            color: #991b1b;
        }

        /* FOTOS */
        .foto-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 6px;
        }

        .foto-container img {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            transition: transform 0.15s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .foto-container img:hover {
            transform: scale(1.08);
        }

        .justificacion {
            font-style: italic;
            color: #1e293b;
            font-weight: 450;
            font-size: 10px;
        }

        .justificacion::before {
            content: "“";
            font-size: 14px;
            color: #94a3b8;
        }

        .justificacion::after {
            content: "”";
            font-size: 14px;
            color: #94a3b8;
        }

        /* TOTAL */
        .total-box {
            margin-top: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .total-box .label {
            font-weight: 600;
            font-size: 12px;
            color: #1e293b;
            letter-spacing: 0.2px;
        }

        .total-box .value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 18px;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .total-box .value small {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            -webkit-text-fill-color: #475569;
            margin-left: 4px;
        }

        /* RESPONSIVE / PRINT */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .page {
                box-shadow: none;
                border-radius: 0;
                padding: 8mm 10mm;
                width: 100%;
            }
            .no-break {
                page-break-inside: avoid;
            }
            .foto-container img:hover {
                transform: none;
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
            <div class="header-title">
                <h1>Reporte de Horas Extra</h1>
                <p>Rango: <strong><?= date('d/m/Y', strtotime($fechaInicio)) ?></strong> al <strong><?= date('d/m/Y', strtotime($fechaFin)) ?></strong></p>
            </div>
        </div>

        <div class="divider"></div>

        <!-- TABLA -->
        <div class="table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Técnico</th>
                        <th>Cliente / Punto</th>
                        <th>Horario</th>
                        <th style="text-align: center;">Horas</th>
                        <th>Justificación</th>
                        <th style="text-align: center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sumHoras = 0;
                    foreach ($reportes as $r):
                        $sumHoras += (float) $r['total_horas'];
                        $bClass = 'badge-pendiente';
                        if ($r['estado_nombre'] === 'Aprobada') $bClass = 'badge-aprobada';
                        if ($r['estado_nombre'] === 'Rechazada') $bClass = 'badge-rechazada';
                    ?>
                    <tr class="no-break">
                        <td style="font-weight: 600; color: #0f172a;"><?= date('d/m/Y', strtotime($r['fecha_reporte'])) ?></td>
                        <td><span class="tecnico"><?= htmlspecialchars($r['nombre_tecnico']) ?></span></td>
                        <td>
                            <strong><?= htmlspecialchars($r['nombre_punto']) ?></strong>
                            <span class="cliente-detalle"><?= htmlspecialchars($r['nombre_cliente']) ?></span>
                        </td>
                        <td>
                            <span class="horario"><?= date('H:i', strtotime($r['hora_inicio'])) ?> – <?= date('H:i', strtotime($r['hora_fin'])) ?></span>
                        </td>
                        <td style="text-align: center;"><span class="horas"><?= number_format($r['total_horas'], 2) ?></span></td>
                        <td>
                            <span class="justificacion"><?= htmlspecialchars($r['justificacion_tecnico']) ?></span>
                            <?php if (!empty($r['fotos_base64'])): ?>
                                <div class="foto-container">
                                    <?php foreach ($r['fotos_base64'] as $imgBase64): ?>
                                        <img src="<?= $imgBase64 ?>" alt="Evidencia">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge <?= $bClass ?>"><?= $r['estado_nombre'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- TOTAL -->
        <div class="total-box">
            <span class="label">📊 Total Horas Registradas en el período</span>
            <span class="value"><?= number_format($sumHoras, 2) ?> <small>hrs</small></span>
        </div>
    </div>
</body>
</html>