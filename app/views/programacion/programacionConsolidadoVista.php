<?php if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado."); ?>

<div class="w-full max-w-7xl mx-auto">
    <!-- FILTROS -->
    <div class="bg-white p-6 rounded-xl shadow-md border-t-4 border-indigo-600 mb-6">
        <form method="GET" action="index.php" class="flex flex-col md:flex-row items-end gap-4">
            <input type="hidden" name="pagina" value="programacionConsolidado">

            <div class="w-full md:w-1/5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>"
                    class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="w-full md:w-1/5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>"
                    class="w-full border-gray-300 rounded-lg shadow-sm">
            </div>

            <div class="w-full md:w-1/5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Delegación (Opcional)</label>
                <select name="delegacion" class="w-full border-gray-300 rounded-lg shadow-sm bg-white">
                    <option value="">-- Todas --</option>
                    <?php foreach ($listaDelegaciones as $del): ?>
                        <option value="<?= $del['id_delegacion'] ?>" <?= $delegacion == $del['id_delegacion'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($del['nombre_delegacion']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="w-full md:w-1/5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Técnico (Opcional)</label>
                <select name="tecnico" class="w-full border-gray-300 rounded-lg shadow-sm bg-white">
                    <option value="">-- Todos --</option>
                    <?php foreach ($listaTecnicos as $tec): ?>
                        <option value="<?= $tec['id_tecnico'] ?>" <?= $tecnico == $tec['id_tecnico'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tec['nombre_tecnico']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="w-full md:w-1/5 flex gap-2">
                <button type="submit"
                    class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg shadow hover:bg-indigo-700 transition">
                    <i class="fas fa-search mr-1"></i> Buscar
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($consolidado)):
        $tecnicosUnicos = array_unique(array_column($consolidado, 'id_tecnico'));
        $puntosUnicos = array_unique(array_column($consolidado, 'nombre_punto'));
        $zonasUnicas = array_unique(array_filter(array_column($consolidado, 'zona')));
        $fueraServicioCount = count(array_filter($consolidado, fn($r) => (int) ($r['activo_operativo'] ?? 1) === 0));
        ?>
        <!-- RESUMEN -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs text-gray-500 font-semibold uppercase">Servicios</p>
                <p class="text-2xl font-bold text-indigo-700"><?= count($consolidado) ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs text-gray-500 font-semibold uppercase">Técnicos Activos</p>
                <p class="text-2xl font-bold text-indigo-700"><?= count($tecnicosUnicos) ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs text-gray-500 font-semibold uppercase">Zonas Cubiertas</p>
                <p class="text-2xl font-bold text-indigo-700"><?= count($zonasUnicas) ?></p>
            </div>
            <div
                class="bg-white rounded-xl shadow-sm border <?= $fueraServicioCount > 0 ? 'border-red-300 bg-red-50' : 'border-gray-200' ?> p-4">
                <p
                    class="text-xs font-semibold uppercase <?= $fueraServicioCount > 0 ? 'text-red-600' : 'text-gray-500' ?>">
                    Aún Fuera de Servicio</p>
                <p class="text-2xl font-bold <?= $fueraServicioCount > 0 ? 'text-red-700' : 'text-indigo-700' ?>">
                    <?= $fueraServicioCount ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- RESULTADOS Y EXPORTACIÓN -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 p-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">
                <i class="fas fa-list-alt mr-2 text-indigo-600"></i> Rutas Programadas (<?= count($consolidado) ?>)
            </h2>
            <?php if (!empty($consolidado)): ?>
                <button type="button" onclick="exportarExcelMaestro()"
                    class="bg-green-600 text-white font-bold py-2 px-6 rounded-lg shadow hover:bg-green-700 transition flex items-center">
                    <i class="fas fa-file-excel mr-2"></i> Descargar Excel Maestro
                </button>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto max-h-[600px]">
            <table class="w-full text-sm text-left text-gray-600 whitespace-nowrap">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Ruta</th>
                        <th class="px-4 py-3">Técnico</th>
                        <th class="px-4 py-3">Zona</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Punto</th>
                        <th class="px-4 py-3">Device ID</th>
                        <th class="px-4 py-3">Estado Máquina</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($consolidado)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 font-bold">
                                No se encontraron rutas programadas en este rango de fechas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($consolidado as $row): ?>
                            <tr
                                class="border-b hover:bg-blue-50 transition <?= (int) ($row['activo_operativo'] ?? 1) === 0 ? 'bg-red-50' : '' ?>">
                                <td class="px-4 py-2 font-semibold text-gray-800"><?= htmlspecialchars($row['fecha_visita']) ?>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded">
                                        <?= htmlspecialchars($row['codigo_ruta'] ?? 'S/R') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['nombre_tecnico']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['zona']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['nombre_cliente']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($row['nombre_punto']) ?></td>
                                <td class="px-4 py-2 font-mono text-xs"><?= htmlspecialchars($row['device_id'] ?? 'N/A') ?></td>
                                <td class="px-4 py-2">
                                    <?php if ((int) ($row['activo_operativo'] ?? 1) === 0): ?>
                                        <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded"
                                            title="La máquina sigue marcada como fuera de servicio; no se ha restaurado">
                                            <i class="fas fa-power-off mr-1"></i> Aún Fuera de Servicio
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Operativa</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.ProgConsolidadoData = <?= json_encode($consolidado) ?>;
</script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>js/programacion/consolidado-export.js"></script>