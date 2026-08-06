<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Horas Extra</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=IBM+Plex+Mono:wght@400;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --azul: #1d4ed8;
            --azul-oscuro: #1e3a8a;
            --gris-bg: #f8fafc;
            --gris-borde: #e2e8f0;
            --gris-texto: #475569;
            --negro: #0f172a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: white;
            color: var(--negro);
            font-size: 11px;
            line-height: 1.5;
        }

        .page {
            width: 210mm;
            margin: 0 auto;
            padding: 8mm 10mm;
            background: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .header img {
            height: 60px;
            width: auto;
        }

        .header-title {
            text-align: right;
        }

        .header-title h1 {
            font-size: 18px;
            font-weight: 800;
            color: var(--azul-oscuro);
            text-transform: uppercase;
        }

        .header-title p {
            font-size: 10px;
            color: var(--gris-texto);
        }

        .divider {
            height: 3px;
            background: linear-gradient(to right, var(--azul) 0%, var(--azul-oscuro) 100%);
            margin-bottom: 15px;
            border-radius: 2px;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.report-table th {
            background: var(--azul-oscuro);
            color: white;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            text-align: left;
        }

        table.report-table td {
            padding: 6px 8px;
            border-bottom: 1px solid var(--gris-borde);
            vertical-align: top;
            font-size: 10px;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 8px;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-pendiente {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-aprobada {
            background: #dcfce7;
            color: #14532d;
        }

        .badge-rechazada {
            background: #ffe4e6;
            color: #9f1239;
        }

        .foto-container {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 4px;
        }

        .foto-container img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid var(--gris-borde);
        }

        .total-box {
            background: var(--gris-bg);
            border: 1px solid var(--gris-borde);
            padding: 10px;
            border-radius: 6px;
            text-align: right;
            font-size: 12px;
            font-weight: 700;
        }

        @media print {
            .no-break {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="header">
            <div>
                <img src="<?= BASE_URL ?>app/logos/logoTransBank.jpg" alt="Logo">
            </div>
            <div class="header-title">
                <h1>Reporte de Horas Extra</h1>
                <p>Rango: <strong><?= date('d/m/Y', strtotime($fechaInicio)) ?></strong> al
                    <strong><?= date('d/m/Y', strtotime($fechaFin)) ?></strong></p>
            </div>
        </div>

        <div class="divider"></div>

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
                    if ($r['estado_nombre'] === 'Aprobada')
                        $bClass = 'badge-aprobada';
                    if ($r['estado_nombre'] === 'Rechazada')
                        $bClass = 'badge-rechazada';
                    ?>
                    <tr class="no-break">
                        <td style="font-weight: 700;"><?= date('d/m/Y', strtotime($r['fecha_reporte'])) ?></td>
                        <td><strong><?= htmlspecialchars($r['nombre_tecnico']) ?></strong></td>
                        <td>
                            <strong><?= htmlspecialchars($r['nombre_punto']) ?></strong><br>
                            <span
                                style="font-size: 8px; color: var(--gris-texto);"><?= htmlspecialchars($r['nombre_cliente']) ?></span>
                        </td>
                        <td style="font-family: 'IBM Plex Mono', monospace; font-size: 9px;">
                            <?= date('H:i', strtotime($r['hora_inicio'])) ?> - <?= date('H:i', strtotime($r['hora_fin'])) ?>
                        </td>
                        <td style="text-align: center; font-weight: 700; font-family: 'IBM Plex Mono', monospace;">
                            <?= number_format($r['total_horas'], 2) ?>
                        </td>
                        <td>
                            "<?= htmlspecialchars($r['justificacion_tecnico']) ?>"
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

        <div class="total-box">
            <span>Total Horas Registradas en Periodo: </span>
            <span
                style="color: var(--azul); font-family: 'IBM Plex Mono', monospace; font-size: 14px;"><?= number_format($sumHoras, 2) ?>
                hrs</span>
        </div>
    </div>
</body>

</html>