<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Número de Remisión #<?= $instalacion['numero_remision'] ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }

        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header-col {
            display: table-cell;
            vertical-align: middle;
        }

        .logo {
            max-width: 150px;
            max-height: 60px;
        }

        .title-box {
            text-align: right;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0 0 0;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            color: #fff;
        }

        .bg-inst {
            background-color: #059669;
        }

        .bg-des {
            background-color: #dc2626;
        }

        .bg-tras {
            background-color: #d97706;
        }

        .section {
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }

        .section-title {
            background-color: #f3f4f6;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 12px;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            text-transform: uppercase;
        }

        .section-body {
            padding: 12px;
            display: table;
            width: 100%;
            box-sizing: border-box;
        }

        .row {
            display: table-row;
        }

        .col {
            display: table-cell;
            width: 50%;
            padding: 4px 10px 8px 0;
        }

        .label {
            font-size: 10px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }

        .value {
            font-size: 13px;
            color: #111827;
            font-weight: 500;
        }

        .full-width {
            width: 100%;
            display: block;
            margin-bottom: 10px;
        }

        .observaciones {
            background-color: #f9fafb;
            padding: 10px;
            border-left: 4px solid #3b82f6;
            font-style: italic;
            font-size: 12px;
            color: #4b5563;
            min-height: 40px;
        }

        .footer {
            margin-top: 50px;
            display: table;
            width: 100%;
            text-align: center;
        }

        .no-break {
            page-break-inside: avoid;
        }

        .firmas {
            display: table;
            width: 100%;
            margin-top: 40px;
            table-layout: fixed;
        }

        .firma-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 20px;
        }

        .firma-espacio {
            height: 70px;
            margin-bottom: 5px;
        }

        .img-firma {
            max-width: 180px;
            max-height: 70px;
            display: block;
            margin: 0 auto;
        }

        .firma-linea {
            border-top: 1px solid #374151;
            width: 80%;
            margin: 0 auto 5px auto;
        }

        .firma-nombre {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
        }

        .firma-rol {
            font-size: 10px;
            color: #6b7280;
        }

        .footer-strip {
            margin-top: 30px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }

        .footer-strip span {
            margin: 0 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="header-col">
            <?php if (!empty($logoBase64)): ?>
                <img src="<?= $logoBase64 ?>" class="logo" alt="Logo">
            <?php else: ?>
                <h2 style="margin:0; color:#1e3a8a;">SISTEMA</h2>
            <?php endif; ?>
        </div>
        <div class="header-col title-box">
            <h1 class="title">Reporte de Operación</h1>
            <p class="subtitle">Consecutivo:
                <strong>#<?= str_pad($instalacion['id_instalacion'], 5, '0', STR_PAD_LEFT) ?></strong>
            </p>
            <p class="subtitle" style="margin-top:2px;">Fecha Solicitud:
                <?= date('d/m/Y', strtotime($instalacion['fecha_solicitud'])) ?>
            </p>
        </div>
    </div>

    <?php
    // Ahora leemos el ID de la operación
    $idOp = intval($instalacion['id_estado_operacion'] ?? 5);
    
    $claseBadge = 'bg-inst';
    $textoOperacion = 'INSTALACIÓN'; // Por defecto

    // Validamos con los IDs reales (5, 6, 7)
    if ($idOp === 6) {
        $claseBadge = 'bg-des';
        $textoOperacion = 'DESINSTALACIÓN';
    } elseif ($idOp === 7) {
        $claseBadge = 'bg-tras';
        $textoOperacion = 'CAMBIO DE MÁQUINA';
    } elseif ($idOp === 5) {
        $claseBadge = 'bg-inst';
        $textoOperacion = 'INSTALACIÓN';
    } else {
        // Fallback: Si por alguna razón es otro ID, imprimimos el nombre de la BD
        $textoOperacion = strtoupper($instalacion['nombre_operacion'] ?? 'OPERACIÓN');
    }
    ?>

    <div class="section">
        <div class="section-title">1. Información de la Operación</div>
        <div class="section-body">
            <div class="row">
                <div class="col">
                    <span class="label">Tipo de Operación</span>
                    <span class="badge <?= $claseBadge ?>"><?= $textoOperacion ?></span>
                </div>
                <div class="col">
                    <span class="label">Técnico Asignado</span>
                    <span class="value"><?= htmlspecialchars($instalacion['nombre_tecnico'] ?? 'N/A') ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <span class="label">Fecha Ejecución</span>
                    <span
                        class="value"><?= !empty($instalacion['fecha_ejecucion']) ? date('d/m/Y', strtotime($instalacion['fecha_ejecucion'])) : 'Pendiente' ?></span>
                </div>
                <div class="col">
                    <span class="label">Número de Remisión</span>
                    <span
                        class="value"><?= htmlspecialchars($instalacion['numero_remision'] ?? 'Sin remisión asignada') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">2. Datos de la Máquina</div>
        <div class="section-body">
            <div class="row">
                <div class="col">
                    <span class="label">Serial Físico</span>
                    <span class="value"><?= htmlspecialchars($instalacion['serial_maquina'] ?: 'N/A') ?></span>
                </div>
                <div class="col">
                    <span class="label">Tipo de Máquina</span>
                    <span class="value"><?= htmlspecialchars($instalacion['nombre_tipo_maquina'] ?? 'N/A') ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col" style="width: 100%; display:block;">
                    <span class="label">Valor del Servicio</span>
                    <span class="value">$ <?= number_format($instalacion['valor_servicio'], 0, '', '.') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">3. Detalles de Ubicación</div>
        <div class="section-body">
            <div class="row">
                <div class="col">
                    <span class="label">Delegación Origen</span>
                    <span class="value"><?= htmlspecialchars($instalacion['delegacion_origen'] ?? 'N/A') ?></span>
                </div>
                <div class="col">
                    <span class="label">Delegación Destino</span>
                    <span class="value"><?= htmlspecialchars($instalacion['delegacion_destino'] ?? 'N/A') ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <span class="label">Cliente Destino</span>
                    <span class="value"><?= htmlspecialchars($instalacion['nombre_cliente'] ?? 'N/A') ?></span>
                </div>
                <div class="col">
                    <span class="label">Punto Destino</span>
                    <span class="value"><?= htmlspecialchars($instalacion['nombre_punto'] ?? 'N/A') ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col" style="width: 100%; display:block;">
                    <span class="label">Dirección Punto Destino</span>
                    <span class="value"><?= htmlspecialchars($instalacion['direccion_punto'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- NUEVA SECCIÓN DE CAPACITACIÓN (Se muestra solo si hubo capacitación) -->
    <?php if (isset($instalacion['incluye_capacitacion']) && $instalacion['incluye_capacitacion'] == 1): ?>
        <div class="section">
            <div class="section-title">4. Capacitación al Cliente</div>
            <div class="section-body">
                <div class="row">
                    <div class="col" style="width: 100%; display:block; padding-bottom: 8px;">
                        <span class="label">Tema de Capacitación</span>
                        <span
                            class="value"><?= htmlspecialchars($instalacion['tema_capacitacion'] ?: 'No especificado') ?></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <span class="label">Cantidad de Asistentes</span>
                        <span class="value"><?= htmlspecialchars($instalacion['cantidad_asistentes'] ?: 'N/A') ?></span>
                    </div>
                    <div class="col">
                        <span class="label">Duración (Horas)</span>
                        <span class="value"><?= htmlspecialchars($instalacion['horas_capacitacion'] ?: 'N/A') ?> Hrs</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-title">
            <?= (isset($instalacion['incluye_capacitacion']) && $instalacion['incluye_capacitacion'] == 1) ? '5' : '4' ?>.
            Observaciones y Comentarios
        </div>
        <div class="section-body" style="padding-top: 5px;">
            <div class="observaciones">
                <?= nl2br(htmlspecialchars($instalacion['comentarios'] ?: 'Ninguna observación registrada.')) ?>
            </div>
        </div>
    </div>

    <!-- Reemplaza el footer antiguo por este: -->
    <div class="no-break">
        <div class="firmas">
            <div class="firma-box">
                <div class="firma-espacio">
                    <?php if (!empty($firmaTecnicoSrc)): ?>
                        <img src="<?= $firmaTecnicoSrc ?>" alt="Firma Técnico" class="img-firma">
                    <?php endif; ?>
                </div>
                <div class="firma-linea"></div>
                <div class="firma-nombre">
                    <?= htmlspecialchars($instalacion['nombre_tecnico'] ?? 'TÉCNICO NO ASIGNADO') ?>
                </div>
                <div class="firma-rol">Técnico de Servicio</div>
            </div>

            <div class="firma-box">
                <div class="firma-espacio">
                    <!-- Espacio en blanco para que el cliente firme a mano -->
                </div>
                <div class="firma-linea"></div>
                <div class="firma-nombre">
                    <?= htmlspecialchars($instalacion['nombre_cliente'] ?? 'RESPONSABLE EN SITIO') ?>
                </div>
                <div class="firma-rol">Recibido y Aprobado</div>
            </div>
        </div>

        <div class="footer-strip">
            <span>INEES — Reporte Técnico de Operación</span>
            <span class="remision-footer">Remisión
                #<?= htmlspecialchars($instalacion['numero_remision'] ?? 'N/A') ?></span>
            <span><?= date('d/m/Y', strtotime($instalacion['fecha_solicitud'])) ?></span>
        </div>
    </div>

</body>

</html>