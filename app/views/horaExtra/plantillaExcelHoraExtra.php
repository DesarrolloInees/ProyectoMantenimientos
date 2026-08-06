<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>REGISTRO DE ASISTENCIA</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        th, td {
            border: 1px solid #000000;
            padding: 6px 8px;
            vertical-align: middle;
            background-color: #ffffff;
            color: #000000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .bg-header { background-color: #dbeafe; }
        .bg-gray { background-color: #f1f5f9; }
        .title-main { font-size: 11pt; font-weight: bold; text-align: center; }
        .nota-box { font-size: 8pt; text-align: justify; line-height: 1.4; }
        .row-normal { height: 24px; }
        .row-tall { height: 40px; }
    </style>
</head>
<body>

<table>
    <!-- ANCHOS FIJOS DE COLUMNA (esto es lo que evita que Excel "aplaste" la tabla) -->
    <colgroup>
        <col style="width:90px;">   <!-- A: DIA -->
        <col style="width:90px;">   <!-- B: FECHA -->
        <col style="width:70px;">   <!-- C: ENTRADA -->
        <col style="width:70px;">   <!-- D: SALIDA -->
        <col style="width:150px;">  <!-- E: NUMERO MAQUINA / PUNTO -->
        <col style="width:80px;">   <!-- F: TOTAL HRS -->
        <col style="width:160px;">  <!-- G: DETALLE -->
        <col style="width:160px;">  <!-- H: DETALLE -->
        <col style="width:160px;">  <!-- I: DETALLE -->
    </colgroup>

    <!-- ENCABEZADO SUPERIOR -->
    <tr class="row-tall">
        <td colspan="2" class="text-center" style="padding:4px;">
            <!--
                Coloca aquí la ruta/URL de tu logo.
                Puede ser una ruta relativa ("assets/logo_inees.png"),
                una URL absoluta, o un data URI en base64 si prefieres
                incrustar la imagen directamente en el archivo.
            -->
                
            <img src="<?= htmlspecialchars($logoPath ?? 'app/logos/logoInees.jpg') ?>"
                    alt="INEES"
                    style="max-height:55px; max-width:170px; display:block; margin:0 auto;">
        </td>
        <td colspan="7" class="title-main">
            COORDINACIÓN DE OPERACIONES TÉCNICAS<br>
            <span style="font-size: 9pt;">FORMATO REGISTRO DE ASISTENCIA PARA EL RECONOCIMIENTO DE HORAS EXTRAS, RECARGOS NOCTURNOS, DOMINICALES FESTIVOS Y COMPENSATORIOS</span>
        </td>
    </tr>

    <!-- METADATOS TÉCNICO Y PERIODO -->
    <tr class="row-normal">
        <td class="font-bold bg-gray">NOMBRE COMPLETO:</td>
        <td colspan="3"><?= htmlspecialchars($nombreTecnico) ?></td>
        <td class="font-bold bg-gray">CÓDIGO:</td>
        <td>N/A</td>
        <td class="font-bold bg-gray">PERIODO REGISTRO:</td>
        <td colspan="2"><?= htmlspecialchars($periodoRegistrado) ?></td>
    </tr>
    <tr class="row-normal">
        <td class="font-bold bg-gray">CARGO:</td>
        <td colspan="3">TÉCNICO DE SERVICIO / MANTENIMIENTO</td>
        <td class="font-bold bg-gray">CLIENTE:</td>
        <td colspan="4">VARIOS / SEGÚN REPORTE</td>
    </tr>

    <!-- NOTA INSTITUCIONAL DE ADVERTENCIA -->
    <tr>
        <td colspan="9" class="nota-box" style="padding:8px;">
            <b>NOTA:</b> ESCRIBA DETALLADAMENTE EN LETRA IMPRENTA SUS DATOS, LOS HORARIOS, LAS ACTIVIDADES EJECUTADAS Y EL SITIO EXACTO. TOTALICE LAS HORAS ORDINARIAS DEL PERIODO, FESTIVAS O DOMINICALES EN SU RESPECTIVA CASILLA. RECUERDE QUE EL FORMATO MAL DILIGENCIADO O CON TACHONES Y ENMENDADURAS SERÁ DEVUELTO. CONSERVE COPIA DE ESTE FORMATO. REGISTRE SU ASISTENCIA DIARIA, ESTA SERÁ USADA PARA RENDIR INFORMES DE GESTIÓN. ENTREGUE EL FORMATO DILIGENCIADO INMEDIATAMENTE TERMINADO EL PERIODO.
        </td>
    </tr>

    <!-- CABECERA DE LA TABLA DE REGISTROS -->
    <tr class="bg-header font-bold text-center row-normal">
        <td rowspan="2">DÍA</td>
        <td rowspan="2">FECHA D/M/A</td>
        <td colspan="3">DETALLAR HORA DE ENTRADA Y SALIDA</td>
        <td rowspan="2">TOTAL (HRS)</td>
        <td colspan="3" rowspan="2">DETALLAR CLARAMENTE EL LUGAR Y LA ACTIVIDAD EJECUTADA</td>
    </tr>
    <tr class="bg-header font-bold text-center row-normal">
        <td>ENTRADA</td>
        <td>SALIDA</td>
        <td>NÚMERO MÁQUINA / PUNTO</td>
    </tr>

    <!-- REGISTROS DINÁMICOS -->
    <?php
    $totalGeneralHoras = 0;
    if (!empty($reportes)):
        foreach ($reportes as $r):
            $totalGeneralHoras += (float)$r['total_horas'];
            $timestamp = strtotime($r['fecha_reporte']);

            // Días en español
            $diasSemana = ['DOMINGO', 'LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO'];
            $nombreDia = $diasSemana[date('w', $timestamp)];
    ?>
        <tr class="row-tall">
            <td class="text-center font-bold"><?= $nombreDia ?></td>
            <td class="text-center"><?= date('d/m/Y', $timestamp) ?></td>
            <td class="text-center"><?= date('H:i', strtotime($r['hora_inicio'])) ?></td>
            <td class="text-center"><?= date('H:i', strtotime($r['hora_fin'])) ?></td>
            <td class="text-center"><?= htmlspecialchars($r['nombre_punto']) ?></td>
            <td class="text-center font-bold"><?= number_format($r['total_horas'], 2) ?></td>
            <td colspan="3">
                <b>Cliente:</b> <?= htmlspecialchars($r['nombre_cliente']) ?> -
                <b>Detalle:</b> <?= htmlspecialchars($r['justificacion_tecnico']) ?>
            </td>
        </tr>
    <?php
        endforeach;
    else:
    ?>
        <!-- Filas en blanco de relleno si no hay datos (mismo estilo que el formato en papel) -->
        <?php for ($i = 0; $i < 8; $i++): ?>
            <tr class="row-tall">
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td colspan="3">&nbsp;</td>
            </tr>
        <?php endfor; ?>
    <?php endif; ?>

    <!-- BLOQUE INFERIOR DE TOTALES Y FIRMAS -->
    <tr>
        <td colspan="6" rowspan="3" style="vertical-align: bottom; height: 100px;">
            <table style="width: 100%; border: none;">
                <tr style="border: none;">
                    <td style="border: none; width: 33%; text-align: center; background-color:#ffffff;" class="font-bold">
                        _______________________<br>FIRMA FUNCIONARIO
                    </td>
                    <td style="border: none; width: 33%; text-align: center; background-color:#ffffff;" class="font-bold">
                        _______________________<br>FIRMA JEFE
                    </td>
                    <td style="border: none; width: 33%; text-align: center; background-color:#ffffff;" class="font-bold">
                        _______________________<br>FIRMA SUPERVISOR
                    </td>
                </tr>
            </table>
        </td>
        <td colspan="2" class="font-bold bg-gray">TOTAL DOMINICALES:</td>
        <td class="text-right font-bold">0.00</td>
    </tr>
    <tr>
        <td colspan="2" class="font-bold bg-gray">TOTAL ORDINARIAS:</td>
        <td class="text-right font-bold"><?= number_format($totalGeneralHoras, 2) ?></td>
    </tr>
    <tr>
        <td colspan="2" class="font-bold bg-gray">TOTAL CON RECARGO NOCT:</td>
        <td class="text-right font-bold">0.00</td>
    </tr>

    <!-- OBSERVACIONES -->
    <tr>
        <td colspan="9" style="height: 50px; vertical-align: top;">
            <b>OBSERVACIONES:</b>
        </td>
    </tr>
</table>

</body>
</html>