<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Servicio #<?= $datosOrden['numero_remision'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --verde: #16a34a;
            --verde-oscuro: #14532d;
            --verde-light: #dcfce7;
            --verde-mid: #bbf7d0;
            --azul: #1d4ed8;
            --azul-oscuro: #1e3a8a;
            --azul-light: #dbeafe;
            --azul-mid: #bfdbfe;
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
            font-size: 13px;
            line-height: 1.6;
        }

        /* ─── PÁGINA ─── */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 10mm 12mm;
            background: white;
        }

        /* ─── HEADER ─── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            margin-bottom: 20px;
            gap: 16px;
        }

        .header-marca {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header-marca img {
            height: 90px;
            width: auto;
            margin-bottom: 6px;
        }

        .header-marca .subtitulo {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--verde);
        }

        .header-remision {
            text-align: right;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header-remision .num {
            font-size: 26px;
            font-weight: 800;
            color: var(--negro);
            line-height: 1;
        }

        .header-remision .num span {
            color: var(--azul);
        }

        .header-remision .fecha {
            font-size: 11px;
            color: var(--gris-texto);
            margin-top: 4px;
        }

        /* Línea divisora tricolor */
        .divider {
            height: 4px;
            background: linear-gradient(to right, var(--verde) 40%, var(--azul) 60%, var(--azul-oscuro) 100%);
            border-radius: 2px;
            margin-bottom: 20px;
        }

        /* ─── BADGE TIPO SERVICIO ─── */
        .badge-tipo {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, var(--azul), var(--azul-oscuro));
            color: white;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 99px;
        }

        /* ─── SECCIÓN TÍTULOS ─── */
        .section-title {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gris-texto);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gris-borde);
        }

        /* ─── GRID PRINCIPAL (cliente + ejecución) ─── */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
        }

        .card {
            background: var(--gris-bg);
            border: 1px solid var(--gris-borde);
            border-radius: 10px;
            padding: 14px;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--verde);
            border-radius: 10px 0 0 10px;
        }

        .card.azul::before {
            background: var(--azul);
        }

        .card-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--verde);
            margin-bottom: 8px;
        }

        .card.azul .card-label {
            color: var(--azul);
        }

        .card-nombre {
            font-size: 16px;
            font-weight: 700;
            color: var(--negro);
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .card-sub {
            font-size: 11px;
            color: var(--gris-texto);
            margin-bottom: 2px;
        }

        .card-sub strong {
            color: var(--negro);
            font-weight: 600;
        }

        .card-divider {
            border: none;
            border-top: 1px solid var(--gris-borde);
            margin: 10px 0;
        }

        .card-mini-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gris-texto);
            margin-bottom: 3px;
        }

        /* ─── TÉCNICO DESTACADO ─── */
        .tecnico-nombre {
            font-size: 14px;
            font-weight: 700;
            color: var(--azul);
        }

        .horas-row {
            display: flex;
            gap: 16px;
            margin-top: 6px;
        }

        .hora-box {
            flex: 1;
            background: white;
            border: 1px solid var(--azul-mid);
            border-radius: 6px;
            padding: 6px 10px;
            text-align: center;
        }

        .hora-box .h-label {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--azul);
        }

        .hora-box .h-valor {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 15px;
            font-weight: 600;
            color: var(--negro);
        }

        /* Calificación pill */
        .calificacion-pill {
            display: inline-block;
            background: var(--verde-light);
            color: var(--verde-oscuro);
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 99px;
            border: 1px solid var(--verde-mid);
        }

        /* ─── TABLA EQUIPOS ─── */
        .equipos-grid {
            background: var(--gris-bg);
            border: 1px solid var(--gris-borde);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .equipos-header {
            background: linear-gradient(to right, var(--verde-oscuro), var(--azul-oscuro));
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .equipos-header h3 {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: white;
        }

        .equipos-body {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
        }

        .equipo-cell {
            padding: 10px 14px;
            border-right: 1px solid var(--gris-borde);
            border-bottom: 1px solid var(--gris-borde);
        }

        .equipo-cell:last-child,
        .equipo-cell:nth-child(4) {
            border-right: none;
        }

        .equipo-cell .ec-label {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gris-texto);
            margin-bottom: 3px;
        }

        .equipo-cell .ec-valor {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            font-weight: 600;
            color: var(--negro);
        }

        /* ─── DIAGNÓSTICO ─── */
        .diagnostico-box {
            border: 1px solid var(--gris-borde);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .diag-header {
            background: linear-gradient(to right, var(--verde-oscuro), var(--azul-oscuro));
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .diag-header h3 {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: white;
        }

        .estado-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: linear-gradient(135deg, #f0fdf4 0%, #eff6ff 100%);
        }

        .estado-cell {
            padding: 12px 16px;
            border-right: 1px solid var(--gris-borde);
        }

        .estado-cell:last-child {
            border-right: none;
        }

        .estado-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gris-texto);
            margin-bottom: 4px;
        }

        .estado-valor {
            font-size: 14px;
            font-weight: 700;
            color: var(--negro);
        }

        .estado-valor.verde {
            color: var(--verde);
        }

        .actividades-box {
            padding: 14px 16px;
            background: white;
            border-top: 1px solid var(--gris-borde);
        }

        .actividades-box .act-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gris-texto);
            margin-bottom: 6px;
        }

        .actividades-box p {
            color: #334155;
            white-space: pre-line;
            line-height: 1.7;
        }

        /* ─── PENDIENTES ─── */
        .pendientes-box {
            padding: 12px 16px;
            background: #fffbeb;
            border-top: 3px solid #f59e0b;
        }

        .pendientes-box .pend-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #92400e;
            margin-bottom: 4px;
        }

        .pendientes-box p {
            color: #78350f;
            white-space: pre-line;
        }

        /* ─── NOVEDADES ─── */
        .novedades-box {
            padding: 12px 16px;
            background: #fff1f2;
            border-top: 3px solid #e11d48;
        }

        .novedades-box .nov-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #9f1239;
            margin-bottom: 6px;
        }

        .novedades-box ul {
            list-style: none;
            padding: 0;
        }

        .novedades-box ul li {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #be123c;
            font-weight: 600;
            font-size: 12px;
            padding: 3px 0;
        }

        .novedades-box ul li::before {
            content: '▸';
            color: #e11d48;
        }

        .novedades-box .nov-detalle {
            margin-top: 8px;
            padding: 8px 10px;
            background: white;
            border-left: 3px solid #e11d48;
            border-radius: 0 4px 4px 0;
            font-size: 11px;
            color: #9f1239;
        }

        /* ─── EVIDENCIA FOTOGRÁFICA ─── */
        .evidencia-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            margin-top: 8px;
        }

        .evidencia-header h2 {
            font-size: 14px;
            font-weight: 800;
            color: var(--negro);
            letter-spacing: -0.3px;
        }

        .evidencia-header .ev-line {
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, var(--verde), var(--azul));
            border-radius: 2px;
        }

        .foto-grupo {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .foto-grupo-title {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: white;
            background: linear-gradient(to right, var(--verde), var(--azul));
            display: inline-block;
            padding: 3px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .foto-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .foto-item {
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            overflow: hidden;
            background: white;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .foto-item img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
        }

        .foto-error {
            font-size: 8px;
            color: #e11d48;
            word-break: break-all;
            padding: 4px 6px;
        }

        /* ─── SIN EVIDENCIA ─── */
        .sin-evidencia {
            text-align: center;
            padding: 24px;
            background: var(--gris-bg);
            border: 2px dashed var(--gris-borde);
            border-radius: 10px;
            margin-top: 16px;
        }

        .sin-evidencia p {
            color: var(--gris-texto);
            font-size: 12px;
        }

        /* ─── FOOTER / FIRMAS ─── */
        .firmas {
            margin-top: 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            page-break-inside: avoid;
        }

        .firma-box {
            text-align: center;
        }

        .firma-linea {
            border-top: 1.5px solid var(--negro);
            margin-bottom: 6px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .firma-nombre {
            font-size: 12px;
            font-weight: 700;
            color: var(--negro);
        }

        .firma-rol {
            font-size: 10px;
            color: var(--gris-texto);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .footer-strip {
            margin-top: 20px;
            padding: 8px 14px;
            background: linear-gradient(to right, var(--verde-oscuro), var(--azul-oscuro));
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-strip span {
            font-size: 9px;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 0.5px;
        }

        .footer-strip .remision-footer {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            font-weight: 600;
            color: white;
        }

        /* ─── IMAGEN DE FIRMA ─── */
        .img-firma {
            max-height: 65px;
            max-width: 180px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        /* Contenedor fijo para que las líneas queden alineadas haya o no firma */
        .firma-espacio {
            height: 70px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            margin-bottom: 5px;
        }

        /* ─── PRINT ─── */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .page-break {
                page-break-before: always;
            }

            .no-break {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="page">

        <!-- HEADER -->
        <div class="header">
            <div class="header-marca">
                <img src="<?= BASE_URL ?>app/logos/logoIneesSinFondo.png" alt="Logo-Inees">
                <span class="subtitulo">Reporte Técnico de Servicio</span>
            </div>
            <div class="header-remision">
                <div class="num">Remisión <span>#<?= $datosOrden['numero_remision'] ?></span></div>
                <div class="fecha">Fecha: <?= date('d/m/Y', strtotime($datosOrden['fecha_visita'])) ?></div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- CLIENTE + EJECUCIÓN -->
        <div class="grid-2 no-break">
            <!-- Cliente -->
            <div class="card">
                <div class="card-label">Datos del Cliente</div>
                <div class="card-nombre"><?= $datosOrden['nombre_cliente'] ?></div>
                <div class="card-sub">📍 <?= $datosOrden['nombre_punto'] ?></div>
                <div class="card-sub">Delegación: <strong><?= $datosOrden['delegacion'] ?></strong></div>
                <hr class="card-divider">
                <div class="card-mini-label">Contacto en Sitio</div>
                <div class="card-sub">
                    <strong><?= !empty($datosOrden['administrador_punto']) ? $datosOrden['administrador_punto'] : 'No registrado' ?></strong>
                    &nbsp;☎️ <?= !empty($datosOrden['celular_encargado']) ? $datosOrden['celular_encargado'] : 'N/A' ?>
                </div>
            </div>

            <!-- Ejecución -->
            <div class="card azul">
                <div class="card-label">Ejecución del Servicio</div>
                <div class="card-mini-label">Técnico Asignado</div>
                <div class="tecnico-nombre"><?= $datosOrden['nombre_tecnico'] ?></div>

                <div class="horas-row" style="margin-bottom:10px;">
                    <div class="hora-box">
                        <div class="h-label">Entrada</div>
                        <div class="h-valor"><?= $datosOrden['hora_entrada'] ?></div>
                    </div>
                    <div class="hora-box">
                        <div class="h-label">Salida</div>
                        <div class="h-valor"><?= $datosOrden['hora_salida'] ?></div>
                    </div>
                    <div class="hora-box">
                        <div class="h-label">Duración</div>
                        <div class="h-valor"><?= $datosOrden['tiempo_servicio'] ?>h</div>
                    </div>
                </div>

                <hr class="card-divider">
                <div class="card-mini-label">Calificación del Cliente</div>
                <span class="calificacion-pill"><?= $datosOrden['nombre_calificacion'] ?: 'Pendiente' ?></span>
            </div>
        </div>

        <!-- EQUIPOS E INVENTARIO -->
        <div class="equipos-grid no-break">
            <div class="equipos-header">
                <h3>Detalles de Equipos e Inventario</h3>
                <span class="badge-tipo"><?= $datosOrden['tipo_servicio'] ?></span>
            </div>
            <div class="equipos-body">
                <div class="equipo-cell">
                    <div class="ec-label">Device ID</div>
                    <div class="ec-valor"><?= $datosOrden['device_id'] ?: 'N/A' ?></div>
                </div>
                <div class="equipo-cell">
                    <div class="ec-label">N° Máquina</div>
                    <div class="ec-valor"><?= $datosOrden['numero_maquina'] ?: 'N/A' ?></div>
                </div>
                <div class="equipo-cell">
                    <div class="ec-label">Tipo Máquina</div>
                    <div class="ec-valor"><?= $datosOrden['nombre_tipo_maquina'] ?: 'N/E' ?></div>
                </div>
                <div class="equipo-cell">
                    <div class="ec-label">Serial Máquina</div>
                    <div class="ec-valor"><?= $datosOrden['serial_maquina'] ?: 'N/A' ?></div>
                </div>
                <div class="equipo-cell">
                    <div class="ec-label">Serial Router</div>
                    <div class="ec-valor"><?= $datosOrden['serial_router'] ?: 'N/A' ?></div>
                </div>
                <div class="equipo-cell" style="border-right:none;">
                    <div class="ec-label">Serial UPS</div>
                    <div class="ec-valor"><?= $datosOrden['serial_ups'] ?: 'N/A' ?></div>
                </div>
            </div>
        </div>

        <!-- DIAGNÓSTICO Y MANTENIMIENTO -->
        <div class="diagnostico-box no-break">
            <div class="diag-header">
                <h3>Diagnóstico y Mantenimiento</h3>
            </div>
            <div class="estado-grid">
                <div class="estado-cell">
                    <div class="estado-label">Estado Inicial (Diagnóstico)</div>
                    <div class="estado-valor"><?= $datosOrden['estado_inicial'] ?: 'No registrado' ?></div>
                </div>
                <div class="estado-cell">
                    <div class="estado-label">Estado Final de Operación</div>
                    <div class="estado-valor verde"><?= $datosOrden['estado_maquina'] ?></div>
                </div>
            </div>
            <div class="actividades-box">
                <div class="act-label">Actividades Realizadas</div>
                <p><?= !empty($datosOrden['observaciones']) ? $datosOrden['observaciones'] : 'Sin observaciones registradas.' ?></p>
            </div>

            <?php if (!empty($datosOrden['pendientes'])): ?>
                <div class="pendientes-box">
                    <div class="pend-label">⚠ Pendientes / Recomendaciones</div>
                    <p><?= $datosOrden['pendientes'] ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($novedades) || !empty($datosOrden['detalle_novedad'])): ?>
                <div class="novedades-box">
                    <div class="nov-label">🔴 Novedades Reportadas</div>
                    <?php if (!empty($novedades)): ?>
                        <ul>
                            <?php foreach ($novedades as $nov): ?>
                                <li><?= htmlspecialchars($nov['nombre_novedad']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($datosOrden['detalle_novedad'])): ?>
                        <div class="nov-detalle"><strong>Detalle:</strong> <?= htmlspecialchars($datosOrden['detalle_novedad']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php
        // Extraer y procesar la firma en Base64
        $firmaEvidencia = array_filter($evidencias, fn($e) => $e['tipo_evidencia'] === 'firma');
        $firmaSrc = null;

        if (!empty($firmaEvidencia)) {
            $fotoFirma = reset($firmaEvidencia); // Tomamos el primer registro de firma
            $rutaEnBD  = $fotoFirma['ruta_archivo'];
            $pos       = strpos($rutaEnBD, 'uploads/');
            $rutaLimpia = ($pos !== false) ? substr($rutaEnBD, $pos) : ltrim($rutaEnBD, '/');
            $rutaFisica = realpath(__DIR__ . '/../../' . $rutaLimpia);

            if ($rutaFisica && file_exists($rutaFisica) && !is_dir($rutaFisica)) {
                $extension = strtolower(pathinfo($rutaFisica, PATHINFO_EXTENSION));
                $mime = ($extension === 'jpg') ? 'jpeg' : $extension;
                $data = file_get_contents($rutaFisica);
                $firmaSrc = 'data:image/' . $mime . ';base64,' . base64_encode($data);
            }
        }

        // 2. Procesar la firma automática del Técnico
        $firmaTecnicoSrc = null;

        if (!empty($datosOrden['ruta_firma'])) {
            $rutaBDTecnico = $datosOrden['ruta_firma'];
            $posTecnico = strpos($rutaBDTecnico, 'uploads/');
            $rutaLimpiaTecnico = ($posTecnico !== false) ? substr($rutaBDTecnico, $posTecnico) : ltrim($rutaBDTecnico, '/');
            $rutaFisicaTecnico = realpath(__DIR__ . '/../../' . $rutaLimpiaTecnico);

            if ($rutaFisicaTecnico && file_exists($rutaFisicaTecnico) && !is_dir($rutaFisicaTecnico)) {
                $extTecnico = strtolower(pathinfo($rutaFisicaTecnico, PATHINFO_EXTENSION));
                $mimeTecnico = ($extTecnico === 'jpg') ? 'jpeg' : $extTecnico;
                $dataTecnico = file_get_contents($rutaFisicaTecnico);
                $firmaTecnicoSrc = 'data:image/' . $mimeTecnico . ';base64,' . base64_encode($dataTecnico);
            }
        }

        ?>


        <!-- FIRMAS -->
        <div class="firmas no-break">
            <div class="firma-box">
                <div class="firma-espacio">
                    <?php if ($firmaTecnicoSrc): ?>
                        <img src="<?= $firmaTecnicoSrc ?>" alt="Firma Técnico" class="img-firma">
                    <?php endif; ?>
                </div>
                <div class="firma-linea"></div>
                <div class="firma-nombre"><?= $datosOrden['nombre_tecnico'] ?></div>
                <div class="firma-rol">Técnico de Servicio</div>
            </div>
            <div class="firma-box">
                <div class="firma-espacio">
                    <?php if ($firmaSrc): ?>
                        <img src="<?= $firmaSrc ?>" alt="Firma Cliente" class="img-firma">
                    <?php endif; ?>
                </div>
                <div class="firma-linea"></div>
                <div class="firma-nombre"><?= !empty($datosOrden['administrador_punto']) ? $datosOrden['administrador_punto'] : 'Responsable en Sitio' ?></div>
                <div class="firma-rol">Recibido y Aprobado</div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer-strip">
            <span>INEES — Reporte Técnico de Servicio</span>
            <span class="remision-footer">Remisión #<?= $datosOrden['numero_remision'] ?></span>
            <span><?= date('d/m/Y', strtotime($datosOrden['fecha_visita'])) ?></span>
        </div>


        <!-- ─── EVIDENCIA FOTOGRÁFICA ─── -->
        <?php if (!empty($evidencias)): ?>
            <div class="page-break"></div>

            <div class="evidencia-header">
                <h2>Evidencia Fotográfica</h2>
                <div class="ev-line"></div>
            </div>

            <?php
            $fotosAntes   = array_filter($evidencias, fn($e) => $e['tipo_evidencia'] === 'antes');
            $fotosDurante = array_filter($evidencias, fn($e) => $e['tipo_evidencia'] === 'componentes');
            $fotosDespues = array_filter($evidencias, fn($e) => $e['tipo_evidencia'] === 'despues');

            $grupos = [
                'Antes del Servicio'     => $fotosAntes,
                'Componentes / Durante'  => $fotosDurante,
                'Después del Servicio'   => $fotosDespues
            ];
            ?>

            <?php foreach ($grupos as $tituloGrupo => $fotos): ?>
                <?php if (count($fotos) > 0): ?>
                    <div class="foto-grupo no-break">
                        <div class="foto-grupo-title"><?= $tituloGrupo ?></div>
                        <div class="foto-grid">
                            <?php foreach ($fotos as $foto): ?>
                                <?php
                                $rutaEnBD  = $foto['ruta_archivo'];
                                $pos       = strpos($rutaEnBD, 'uploads/');
                                $rutaLimpia = ($pos !== false) ? substr($rutaEnBD, $pos) : ltrim($rutaEnBD, '/');
                                $rutaFisica = realpath(__DIR__ . '/../../' . $rutaLimpia);

                                $imgSrc = '';
                                if ($rutaFisica && file_exists($rutaFisica) && !is_dir($rutaFisica)) {
                                    $extension = strtolower(pathinfo($rutaFisica, PATHINFO_EXTENSION));
                                    $mime = ($extension === 'jpg') ? 'jpeg' : $extension;
                                    $data = file_get_contents($rutaFisica);
                                    $imgSrc = 'data:image/' . $mime . ';base64,' . base64_encode($data);
                                } else {
                                    $imgSrc = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
                                }
                                ?>
                                <div class="foto-item">
                                    <img src="<?= $imgSrc ?>" alt="Evidencia">
                                    <?php if (!$rutaFisica || !file_exists($rutaFisica) || is_dir($rutaFisica)): ?>
                                        <div class="foto-error">Error: <?= __DIR__ . '/../../' . $rutaLimpia ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="sin-evidencia">
                <p>📷 No se adjuntaron evidencias fotográficas para este servicio.</p>
            </div>
        <?php endif; ?>

    </div><!-- /page -->
</body>

</html>