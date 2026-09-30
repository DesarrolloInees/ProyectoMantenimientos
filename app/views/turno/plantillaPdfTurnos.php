<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

/**
 * Plantilla A4 del PDF de turnos.
 * Se renderiza con Browsershot (Node.js + Chrome), igual que las plantillas
 * de órdenes de servicio y de horas extra.
 * Variables esperadas: $registros, $periodo, $logoBase64.
 */
$registros = isset($registros) ? $registros : [];
$periodo = isset($periodo) ? $periodo : '';
$logoBase64 = isset($logoBase64) ? $logoBase64 : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Turnos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header td {
            vertical-align: middle;
        }

        .logo {
            height: 48px;
        }

        .titulo {
            text-align: right;
        }

        .titulo h1 {
            font-size: 16px;
            color: #1e3a8a;
            text-transform: uppercase;
        }

        .titulo p {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        .info-periodo {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            margin-bottom: 8px;
        }

        .info-periodo strong {
            color: #1e3a8a;
        }

        table.datos {
            width: 100%;
            border-collapse: collapse;
        }

        table.datos th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            padding: 5px 4px;
            border: 1px solid #1e3a8a;
        }

        table.datos td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            font-size: 10px;
            vertical-align: top;
        }

        table.datos tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .col-centro {
            text-align: center;
        }

        .fila-total td {
            background-color: #e2e8f0 !important;
            font-weight: bold;
        }

        .sin-datos {
            border: 1px solid #cbd5e1;
            padding: 18px;
            text-align: center;
            color: #64748b;
        }

        .firmas {
            width: 100%;
            margin-top: 40px;
        }

        .firmas td {
            text-align: center;
            padding-top: 22px;
            border-top: 1px solid #1f2937;
            font-size: 10px;
            font-weight: bold;
        }

        .firmas .nombre {
            display: block;
            margin-top: 4px;
            font-weight: normal;
            font-size: 9px;
            color: #64748b;
        }

        .pie {
            margin-top: 14px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>

<body>

    <table class="header">
        <tr>
            <td style="width: 30%;">
                <?php if ($logoBase64 !== ''): ?>
                    <img src="<?= $logoBase64 ?>" class="logo" alt="INEES">
                <?php else: ?>
                    <strong style="color:#1e3a8a;">INEES</strong>
                <?php endif; ?>
            </td>
            <td class="titulo" style="width: 70%;">
                <h1>Registro de entrada de turnos</h1>
                <p>Coordinación de Operaciones Técnicas - Motorizados</p>
            </td>
        </tr>
    </table>

    <div class="info-periodo">
        <strong>PERIODO:</strong> <?= htmlspecialchars($periodo) ?>
        &nbsp;|&nbsp; <strong>TOTAL REGISTROS:</strong> <?= count($registros) ?>
        &nbsp;|&nbsp; <strong>GENERADO:</strong> <?= date('d/m/Y H:i') ?>
    </div>

    <?php if (empty($registros)): ?>
        <div class="sin-datos">
            No hay marcaciones de entrada registradas en el periodo seleccionado.
        </div>
    <?php else: ?>
        <table class="datos">
            <thead>
                <tr>
                    <th style="width: 20%;">Técnico</th>
                    <th style="width: 10%;">Fecha</th>
                    <th style="width: 10%;">Hora entrada</th>
                    <th style="width: 30%;">Novedad</th>
                    <th style="width: 16%;">Registrado el</th>
                    <th style="width: 14%;">Ubicación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                    <?php
                    $tieneUbicacion = ($r['latitud'] !== null && $r['longitud'] !== null);
                    $ubicacion = $tieneUbicacion
                        ? number_format((float) $r['latitud'], 5) . ', ' . number_format((float) $r['longitud'], 5)
                        : 'Sin ubicación';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nombre_tecnico']) ?></td>
                        <td class="col-centro"><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                        <td class="col-centro"><strong><?= date('H:i', strtotime($r['hora_entrada'])) ?></strong></td>
                        <td>
                            <?= ($r['novedad'] !== null && $r['novedad'] !== '')
                                ? nl2br(htmlspecialchars($r['novedad']))
                                : '<span style="color:#94a3b8;">Sin novedad</span>' ?>
                        </td>
                        <td class="col-centro"><?= date('d/m/Y H:i', strtotime($r['fecha_registro'])) ?></td>
                        <td class="col-centro" style="font-size: 8px;"><?= htmlspecialchars($ubicacion) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fila-total">
                    <td colspan="6">TOTAL DE REGISTROS: <?= count($registros) ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <table class="firmas">
        <tr>
            <td style="width: 45%;">FIRMA SUPERVISOR<span class="nombre">Nombre y documento</span></td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;">FIRMA COORDINACIÓN<span class="nombre">Nombre y documento</span></td>
        </tr>
    </table>

    <p class="pie">
        "Hora entrada" es la reportada por el técnico. "Registrado el" es la fecha y hora real del sistema al guardar la
        marcación.
    </p>

</body>

</html>
