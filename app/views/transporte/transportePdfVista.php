<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Transporte #<?= $instalacion['id_instalacion'] ?></title>
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

        .bg-cobro {
            background-color: #059669;
        }

        .bg-nocobro {
            background-color: #2563eb;
        }

        .bg-inees {
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

        .col-full {
            display: block;
            width: 100%;
            padding: 4px 0 8px 0;
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

        .observaciones {
            background-color: #f9fafb;
            padding: 10px;
            border-left: 4px solid #3b82f6;
            font-style: italic;
            font-size: 12px;
            color: #4b5563;
            min-height: 40px;
        }

        /* Evidencias */
        .evidencia-container {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-top: 10px;
        }

        .evidencia-box {
            display: table-cell;
            text-align: center;
            padding: 5px;
            border: 1px dashed #d1d5db;
        }

        .evidencia-img {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
        }

        .evidencia-titulo {
            font-size: 10px;
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 5px;
            text-transform: uppercase;
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
                <h2 style="margin:0; color:#1e3a8a;">SISTEMA LOGÍSTICO</h2>
            <?php endif; ?>
        </div>
        <div class="header-col title-box">
            <h1 class="title">Reporte de Operación</h1>
            <p class="subtitle">Consecutivo:
                <strong>#<?= str_pad($instalacion['id_instalacion'], 5, '0', STR_PAD_LEFT) ?></strong></p>
            <p class="subtitle" style="margin-top:2px;">Fecha Realización:
                <?= date('d/m/Y', strtotime($instalacion['fecha_instalacion'])) ?></p>
        </div>
    </div>

    <?php
    // Configurar Badge de Categoría
    $cat = $instalacion['categoria_servicio'];
    $claseBadge = 'bg-cobro';
    $textoCat = 'PROSEGUR - COBRO';

    if ($cat === 'Prosegur_NoCobro') {
        $claseBadge = 'bg-nocobro';
        $textoCat = 'PROSEGUR - SIN COBRO';
    } elseif ($cat === 'Inees') {
        $claseBadge = 'bg-inees';
        $textoCat = 'INEES (INTERNO)';
    }
    ?>

    <div class="section">
        <div class="section-title">1. Información General</div>
        <div class="section-body">
            <div class="row">
                <div class="col">
                    <span class="label">Categoría del Servicio</span>
                    <span class="badge <?= $claseBadge ?>"><?= $textoCat ?></span>
                </div>
                <div class="col">
                    <span class="label">Tipo de Servicio</span>
                    <span
                        class="value font-bold"><?= htmlspecialchars($instalacion['tipo_servicio_nombre'] ?: 'N/A') ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <span class="label">Técnico Asignado</span>
                    <span class="value"><?= htmlspecialchars($instalacion['nombre_tecnico'] ?: 'N/A') ?></span>
                </div>
                <div class="col">
                    <span class="label">Número de Remisión</span>
                    <span
                        class="value"><?= htmlspecialchars($instalacion['numero_remision'] ?: 'Sin remisión asignada') ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($cat === 'Inees' && !empty($instalacion['descripcion_inees'])): ?>
        <div class="section">
            <div class="section-title">2. Actividades Internas (Inees)</div>
            <div class="section-body">
                <div class="col-full">
                    <span class="label">Descripción de lo realizado</span>
                    <span class="value"><?= nl2br(htmlspecialchars($instalacion['descripcion_inees'])) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($cat !== 'Inees'): ?>
        <div class="section">
            <div class="section-title">2. Logística y Producto</div>
            <div class="section-body">
                <div class="row">
                    <div class="col">
                        <span class="label">Lugar de Recogida</span>
                        <span class="value"><?= htmlspecialchars($instalacion['lugar_recogida'] ?: 'N/A') ?></span>
                    </div>
                    <div class="col">
                        <span class="label">Fecha de Recogida</span>
                        <span
                            class="value"><?= !empty($instalacion['fecha_recogida']) ? date('d/m/Y', strtotime($instalacion['fecha_recogida'])) : 'N/A' ?></span>
                    </div>
                </div>

                <div class="row">
                    <?php if ($instalacion['es_maquina'] == 1): ?>
                        <div class="col">
                            <span class="label">Tipo de Máquina</span>
                            <span class="value"><?= htmlspecialchars($instalacion['nombre_tipo_maquina'] ?: 'N/A') ?></span>
                        </div>
                        <div class="col">
                            <span class="label">Serial Físico</span>
                            <span
                                class="value font-bold"><?= htmlspecialchars($instalacion['serial_maquina'] ?: 'N/A') ?></span>
                        </div>
                    <?php else: ?>
                        <div class="col-full">
                            <span class="label">Producto / Elementos Transportados</span>
                            <span class="value"><?= htmlspecialchars($instalacion['producto_otro'] ?: 'N/A') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="row">
                    <div class="col-full" style="margin-top:10px; border-top:1px dashed #ccc; padding-top:10px;">
                        <span class="label text-emerald-600">Tarifa de Cobro del Servicio</span>
                        <span class="value font-bold" style="font-size:16px;">$
                            <?= number_format($instalacion['valor_servicio'], 0, '', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">3. Origen y Destino</div>
            <div class="section-body">
                <?php
                $origenCliente = htmlspecialchars($instalacion['cliente_origen_nombre'] ?: ($instalacion['cliente_origen_texto'] ?: 'N/A'));
                $origenPunto = htmlspecialchars($instalacion['punto_origen_nombre'] ?: ($instalacion['punto_origen_texto'] ?: 'N/A'));

                $destinoCliente = htmlspecialchars($instalacion['cliente_destino_nombre'] ?: ($instalacion['cliente_destino_texto'] ?: 'N/A'));
                $destinoPunto = htmlspecialchars($instalacion['punto_destino_nombre'] ?: ($instalacion['punto_destino_texto'] ?: 'N/A'));
                ?>
                <div class="row">
                    <div class="col">
                        <span class="label">Cliente Origen</span>
                        <span class="value"><?= $origenCliente ?></span><br>
                        <span class="label" style="margin-top:5px;">Punto Origen</span>
                        <span class="value"><?= $origenPunto ?></span>
                    </div>
                    <div class="col">
                        <span class="label">Cliente Destino</span>
                        <span class="value font-bold"><?= $destinoCliente ?></span><br>
                        <span class="label" style="margin-top:5px;">Punto Destino</span>
                        <span class="value font-bold"><?= $destinoPunto ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-title">Observaciones</div>
        <div class="section-body" style="padding-top: 5px;">
            <div class="observaciones">
                <?= nl2br(htmlspecialchars($instalacion['notas'] ?: 'Ninguna observación registrada.')) ?>
            </div>
        </div>
    </div>

    <?php if (!empty($evidenciaRemision) || !empty($evidenciaMaquina) || !empty($evidenciaChazos)): ?>
        <div class="section no-break">
            <div class="section-title"><i class="fas fa-camera"></i> Evidencias del Servicio</div>
            <div class="section-body">
                <div class="evidencia-container">
                    <?php if (!empty($evidenciaRemision)): ?>
                        <div class="evidencia-box">
                            <div class="evidencia-titulo">Remisión</div>
                            <?php if (strpos($evidenciaRemision, 'application/pdf') !== false): ?>
                                <span style="font-size:10px; color:#666;">(Documento PDF Adjunto)</span>
                            <?php else: ?>
                                <img src="<?= $evidenciaRemision ?>" class="evidencia-img">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($evidenciaMaquina)): ?>
                        <div class="evidencia-box">
                            <div class="evidencia-titulo">Máquina</div>
                            <img src="<?= $evidenciaMaquina ?>" class="evidencia-img">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($evidenciaChazos)): ?>
                        <div class="evidencia-box">
                            <div class="evidencia-titulo">Chazos</div>
                            <img src="<?= $evidenciaChazos ?>" class="evidencia-img">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
                <div class="firma-espacio"></div>
                <div class="firma-linea"></div>
                <div class="firma-nombre">
                    <?= $cat !== 'Inees' ? $destinoCliente : 'SUPERVISOR INEES' ?>
                </div>
                <div class="firma-rol">Recibido y Aprobado</div>
            </div>
        </div>

        <div class="footer-strip">
            <span>SISTEMA LOGÍSTICO Y TRANSPORTES</span>
            <span class="remision-footer">Remisión
                #<?= htmlspecialchars($instalacion['numero_remision'] ?? 'N/A') ?></span>
            <span>Generado el: <?= date('d/m/Y H:i') ?></span>
        </div>
    </div>

</body>

</html>