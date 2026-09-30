<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

$totalRegistros = (int) ($totales['registros'] ?? 0);
$totalNovedades = (int) ($totales['novedades'] ?? 0);
$totalUbicacion = (int) ($totales['ubicacion'] ?? 0);
$totalTecnicos  = (int) ($totales['tecnicos'] ?? 0);

// Filtros actuales, para armar los enlaces de exportación
$queryFiltros = 'fecha_inicio=' . urlencode($fechaInicio) . '&fecha_fin=' . urlencode($fechaFin);
if (!empty($idTecnico)) {
    $queryFiltros .= '&id_tecnico=' . (int) $idTecnico;
}
$urlExcel = 'index.php?pagina=turnoExcel&accion=generar&' . $queryFiltros;
$urlPdf   = 'index.php?pagina=turnoPdf&accion=generar&' . $queryFiltros;
?>

<div class="p-4 md:p-6 max-w-[1400px] mx-auto space-y-6">

    <!-- Encabezado -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">
            <i class="fas fa-user-clock text-blue-600 mr-2"></i>Reporte de Turnos
        </h1>
        <p class="text-gray-500 text-xs md:text-sm mt-1">
            Entradas de turno reportadas por los técnicos motorizados, con novedad y ubicación.
        </p>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="pagina" value="turnoReportes">

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha inicio</label>
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>"
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha fin</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>"
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Técnico</label>
                <select name="id_tecnico"
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500">
                    <option value="">- Todos los técnicos -</option>
                    <?php foreach ($tecnicos as $tec): ?>
                        <option value="<?= (int) $tec['id_tecnico'] ?>" <?= ($idTecnico == $tec['id_tecnico']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tec['nombre_tecnico']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="index.php?pagina=turnoReportes"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm"
                    title="Limpiar filtros">
                    <i class="fas fa-undo"></i>
                </a>
            </div>

            <div class="flex flex-wrap gap-2 sm:col-span-2 md:col-span-4 justify-end border-t pt-3 mt-1">
                <a href="<?= $urlExcel ?>"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm">
                    <i class="fas fa-file-excel"></i> Reporte Excel
                </a>
                <a href="<?= $urlPdf ?>" target="_blank"
                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2 text-sm">
                    <i class="fas fa-file-pdf"></i> Reporte PDF
                </a>
            </div>
        </form>
    </div>

    <!-- Tarjetas resumen -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-600 rounded-xl p-4 text-white shadow-md">
            <p class="text-blue-100 text-xs font-semibold uppercase">Registros</p>
            <h3 class="text-2xl font-bold"><?= $totalRegistros ?></h3>
        </div>
        <div class="bg-amber-500 rounded-xl p-4 text-white shadow-md">
            <p class="text-amber-100 text-xs font-semibold uppercase">Con novedad</p>
            <h3 class="text-2xl font-bold"><?= $totalNovedades ?></h3>
        </div>
        <div class="bg-emerald-600 rounded-xl p-4 text-white shadow-md">
            <p class="text-emerald-100 text-xs font-semibold uppercase">Con ubicación</p>
            <h3 class="text-2xl font-bold"><?= $totalUbicacion ?></h3>
        </div>
        <div class="bg-indigo-600 rounded-xl p-4 text-white shadow-md">
            <p class="text-indigo-100 text-xs font-semibold uppercase">Técnicos</p>
            <h3 class="text-2xl font-bold"><?= $totalTecnicos ?></h3>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 text-sm">
                <i class="fas fa-list-check text-blue-600 mr-1"></i> Marcaciones del periodo
            </h3>
            <span class="text-xs text-gray-500">
                <?= date('d/m/Y', strtotime($fechaInicio)) ?> al <?= date('d/m/Y', strtotime($fechaFin)) ?>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase">Técnico</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase">Fecha</th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase">Hora entrada</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase">Novedad</th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase">Registrado el</th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase">Ubicación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                No hay marcaciones en este periodo.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                        foreach ($registros as $r):
                            ?>
                            <tr class="hover:bg-blue-50/50 transition">
                                <td class="px-3 py-2.5 font-semibold text-gray-800">
                                    <?= htmlspecialchars($r['nombre_tecnico']) ?>
                                    <?php if (!empty($r['codigo_ruta'])): ?>
                                        <span class="block text-[10px] text-gray-400 font-normal">
                                            Ruta <?= htmlspecialchars($r['codigo_ruta']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2.5 text-gray-600">
                                    <?= date('d/m/Y', strtotime($r['fecha'])) ?>
                                    <span class="block text-[10px] text-gray-400">
                                        <?= $diasSemana[(int) date('w', strtotime($r['fecha']))] ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <span class="font-bold text-blue-700">
                                        <?= date('H:i', strtotime($r['hora_entrada'])) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-700 max-w-md">
                                    <?= ($r['novedad'] !== null && trim((string) $r['novedad']) !== '')
                                        ? nl2br(htmlspecialchars($r['novedad']))
                                        : '<span class="text-gray-300">—</span>' ?>
                                </td>
                                <td class="px-3 py-2.5 text-center text-gray-500 text-xs">
                                    <?= date('d/m/Y H:i', strtotime($r['fecha_registro'])) ?>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <?php if ($r['latitud'] !== null && $r['longitud'] !== null): ?>
                                        <a href="https://www.google.com/maps?q=<?= $r['latitud'] ?>,<?= $r['longitud'] ?>"
                                            target="_blank"
                                            class="text-emerald-600 hover:text-emerald-700 font-semibold text-xs">
                                            <i class="fas fa-map-marker-alt"></i> Ver mapa
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-300 text-xs">Sin ubicación</span>
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
