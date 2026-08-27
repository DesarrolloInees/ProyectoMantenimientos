<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Parqueaderos - Modo Horizontal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
        }
        .page-break { 
            page-break-after: always; 
        }
    </style>
</head>
<body class="bg-white p-4 text-gray-800">

    <!-- Encabezado del PDF -->
    <div class="border-b-2 border-blue-800 pb-3 mb-4 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-blue-900 leading-none">REPORTE DE PARQUEADEROS</h1>
            <p class="text-xs text-gray-500 mt-1">Auditoría y control de comprobantes de gastos</p>
        </div>
        <div class="text-right">
            <span class="block text-[10px] text-gray-400 font-bold uppercase">Rango de fechas</span>
            <span class="text-xs font-semibold text-gray-700 bg-gray-100 px-2 py-1 rounded border border-gray-200">
                <?= date('d/m/Y', strtotime($fechaInicio)) ?> — <?= date('d/m/Y', strtotime($fechaFin)) ?>
            </span>
        </div>
    </div>

    <!-- Métrica y Tarjetas Rápidas -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-200 flex justify-between items-center">
            <span class="text-xs font-bold text-gray-600 uppercase">Total Comprobantes Registrados:</span>
            <span class="text-base font-bold text-gray-900"><?= count($datos) ?></span>
        </div>
        <div class="bg-blue-50 p-2.5 rounded-lg border border-blue-200 flex justify-between items-center">
            <span class="text-xs font-bold text-blue-700 uppercase">Monto Total Acumulado:</span>
            <span class="text-lg font-extrabold text-blue-900">$<?= number_format($totalGeneral, 2, ',', '.') ?></span>
        </div>
    </div>

    <!-- Tabla Principal en Modo Apaisado (Gana mucho más espacio para el texto) -->
    <table class="w-full text-xs text-left border-collapse mb-6">
        <thead>
            <tr class="bg-blue-800 text-white uppercase font-bold text-[11px]">
                <th class="p-2 border border-blue-800 text-center w-10">N°</th>
                <th class="p-2 border border-blue-800">Fecha Servicio</th>
                <th class="p-2 border border-blue-800">Técnico Asignado</th>
                <th class="p-2 border border-blue-800">Punto / Sede Visitada</th>
                <th class="p-2 border border-blue-800 text-center">Horario</th>
                <th class="p-2 border border-blue-800 text-center">N° Factura</th>
                <th class="p-2 border border-blue-800 text-right">Valor ($)</th>
                <th class="p-2 border border-blue-800 text-center w-16">Vista Previa</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($datos as $index => $item): ?>
                <?php 
                    $rutaFisicaFoto = __DIR__ . '/../../../' . $item['ruta_foto'];
                    $base64Foto = '';
                    if (!empty($item['ruta_foto']) && file_exists($rutaFisicaFoto)) {
                        $ext = pathinfo($rutaFisicaFoto, PATHINFO_EXTENSION);
                        $base64Foto = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($rutaFisicaFoto));
                    }
                ?>
                <tr class="<?= ($index % 2 === 0) ? 'bg-white' : 'bg-gray-50' ?> border-b border-gray-200">
                    <td class="p-1.5 border text-center font-bold text-gray-500"><?= $index + 1 ?></td>
                    <td class="p-1.5 border font-semibold"><?= date('d/m/Y', strtotime($item['fecha_servicio'])) ?></td>
                    <td class="p-1.5 border font-semibold text-blue-900"><?= htmlspecialchars($item['nombre_tecnico']) ?></td>
                    <td class="p-1.5 border"><?= htmlspecialchars($item['nombre_punto']) ?></td>
                    <td class="p-1.5 border text-center font-mono text-[11px]">
                        <?= date('H:i', strtotime($item['hora_inicio'])) ?> - <?= date('H:i', strtotime($item['hora_fin'])) ?>
                    </td>
                    <td class="p-1.5 border text-center font-mono font-bold"><?= htmlspecialchars($item['numero_factura']) ?></td>
                    <td class="p-1.5 border text-right font-bold text-green-700">$<?= number_format($item['valor_factura'], 2, ',', '.') ?></td>
                    <td class="p-1 border text-center">
                        <?php if ($base64Foto): ?>
                            <img src="<?= $base64Foto ?>" class="w-10 h-10 object-cover rounded border border-gray-300 mx-auto">
                        <?php else: ?>
                            <span class="text-[9px] text-gray-400 italic">Sin foto</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="bg-blue-100 font-bold text-blue-900">
                <td colspan="6" class="p-2 text-right uppercase border border-blue-200">Total General Gastado:</td>
                <td class="p-2 text-right text-sm border border-blue-200">$<?= number_format($totalGeneral, 2, ',', '.') ?></td>
                <td class="border border-blue-200"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Salto de Página para Galería/Anexo -->
    <div class="page-break"></div>

    <!-- ANEXO FOTOGRÁFICO EN GRID DE 3 COLUMNAS (Ideal para modo apaisado) -->
    <div class="border-b-2 border-blue-800 pb-2 mb-4 flex justify-between items-center">
        <h2 class="text-lg font-bold text-blue-900">ANEXO DE COMPROBANTES Y FACTURAS</h2>
        <span class="text-xs text-gray-500">Muestrario de imágenes adjuntas</span>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <?php foreach ($datos as $index => $item): ?>
            <?php 
                $rutaFisicaFoto = __DIR__ . '/../../../' . $item['ruta_foto'];
                if (!empty($item['ruta_foto']) && file_exists($rutaFisicaFoto)):
                    $ext = pathinfo($rutaFisicaFoto, PATHINFO_EXTENSION);
                    $base64Foto = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($rutaFisicaFoto));
            ?>
                <div class="border border-gray-300 rounded-lg p-2.5 bg-gray-50 flex flex-col items-center">
                    <div class="w-full text-[11px] font-bold text-gray-700 border-b pb-1 mb-1.5 flex justify-between">
                        <span>#<?= $index + 1 ?> — Factura: <?= htmlspecialchars($item['numero_factura']) ?></span>
                        <span class="text-blue-700"><?= date('d/m/Y', strtotime($item['fecha_servicio'])) ?></span>
                    </div>
                    <div class="text-[10px] text-gray-600 w-full mb-2 space-y-0.5">
                        <p class="truncate"><strong>Técnico:</strong> <?= htmlspecialchars($item['nombre_tecnico']) ?></p>
                        <p class="truncate"><strong>Punto:</strong> <?= htmlspecialchars($item['nombre_punto']) ?></p>
                        <p><strong>Valor:</strong> $<?= number_format($item['valor_factura'], 2, ',', '.') ?></p>
                    </div>
                    <div class="w-full h-44 flex items-center justify-center bg-white border border-gray-200 rounded p-1">
                        <img src="<?= $base64Foto ?>" class="max-h-full max-w-full object-contain">
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

</body>
</html>