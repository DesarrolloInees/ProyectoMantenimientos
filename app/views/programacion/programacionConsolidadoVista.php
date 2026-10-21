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
                <p class="text-2xl font-bold text-indigo-700" id="contadorServicios"><?= count($consolidado) ?></p>
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
            <h2 class="text-lg font-bold text-gray-800" id="tituloConsolidado">
                <i class="fas fa-list-alt mr-2 text-indigo-600"></i> Rutas Programadas (<span id="contadorConsolidado"><?= count($consolidado) ?></span>)
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
                        <th class="px-4 py-3 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="tbodyConsolidado">
                    <?php if (empty($consolidado)): ?>
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 font-bold">
                                No se encontraron rutas programadas en este rango de fechas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($consolidado as $row): ?>
                            <tr id="fila_orden_<?= (int) $row['id_orden'] ?>"
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
                                <td class="px-4 py-2 text-center">
                                    <button type="button"
                                        onclick="abrirModalEditarOrden(<?= (int) $row['id_orden'] ?>)"
                                        class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-2 py-1 rounded transition"
                                        title="Editar esta orden de programación">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button type="button"
                                        onclick="eliminarOrdenConsolidado(<?= (int) $row['id_orden'] ?>, <?= htmlspecialchars(json_encode($row['nombre_punto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)"
                                        class="text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition"
                                        title="Eliminar esta orden de la programación">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE ORDEN -->
<div id="modalEditarOrden" class="fixed inset-0 z-50 hidden bg-black bg-opacity-60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden border border-gray-200">
        <!-- Encabezado -->
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 text-white px-6 py-4 flex justify-between items-center shadow-md">
            <h3 class="font-bold text-lg flex items-center">
                <i class="fas fa-pen mr-2 text-indigo-200"></i> Editar Servicio Programado
            </h3>
            <button type="button" onclick="cerrarModalEditarOrden()" class="text-white hover:text-red-300 transition text-2xl font-bold focus:outline-none">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Contenido del formulario -->
        <div class="p-6 overflow-y-auto flex-1 bg-gray-50">
            <form id="formEditarOrden" class="space-y-4">
                <input type="hidden" name="id_orden" id="edit_id_orden">
                <input type="hidden" name="id_punto_original" id="edit_id_punto_original">

                <!-- Fecha de Visita -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-calendar mr-1 text-indigo-600"></i> Fecha de Visita *
                    </label>
                    <input type="date" name="fecha_visita" id="edit_fecha_visita"
                        class="w-full border-gray-300 rounded-lg shadow-sm py-2 px-3" required>
                </div>

                <!-- Técnico -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-user-tie mr-1 text-indigo-600"></i> Técnico *
                    </label>
                    <select name="id_tecnico" id="edit_id_tecnico"
                        class="w-full border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-white" required>
                        <option value="">-- Seleccionar técnico --</option>
                    </select>
                </div>

                <!-- Cliente -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-users mr-1 text-indigo-600"></i> Cliente *
                    </label>
                    <select name="id_cliente" id="edit_id_cliente"
                        class="w-full border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-white" required
                        onchange="cargarPuntosCliente()">
                        <option value="">-- Seleccionar cliente --</option>
                    </select>
                </div>

                <!-- Punto -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-map-marker-alt mr-1 text-indigo-600"></i> Punto *
                    </label>
                    <select name="id_punto" id="edit_id_punto"
                        class="w-full border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-white" required>
                        <option value="">-- Primero seleccione un cliente --</option>
                    </select>
                </div>

                <!-- Información del punto seleccionado -->
                <div id="puntoInfo" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mt-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-bold text-gray-800" id="edit_nombre_punto"></p>
                            <p class="text-xs text-gray-600 flex items-center" id="edit_zona_punto">
                                <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded mr-2">
                                    <i class="fas fa-map-marker-alt mr-1"></i> Zona: <span id="edit_zona_valor"></span>
                                </span>
                            </p>
                            <p class="text-xs text-gray-600 flex items-center" id="edit_direccion_punto">
                                <span class="text-gray-600 text-[10px]">
                                    <i class="fas fa-location-dot mr-1"></i> <span id="edit_direccion_valor"></span>
                                </span>
                            </p>
                            <p class="text-xs text-gray-600 flex items-center" id="edit_device_punto">
                                <span class="text-gray-600 text-[10px]">
                                    <i class="fas fa-microchip mr-1"></i> Device: <span id="edit_device_valor"></span>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Máquina -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-microchip mr-1 text-gray-600"></i> Máquina
                    </label>
                    <select name="id_maquina" id="edit_id_maquina"
                        class="w-full border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-white">
                        <option value="">-- Sin asignar --</option>
                    </select>
                </div>

                <!-- Modalidad -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-cogs mr-1 text-gray-600"></i> Modalidad
                    </label>
                    <select name="id_modalidad" id="edit_id_modalidad"
                        class="w-full border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-white">
                        <option value="">-- Sin asignar --</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Pie del modal -->
        <div class="bg-white px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" onclick="cerrarModalEditarOrden()"
                class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold text-sm transition">
                Cancelar
            </button>
            <button type="button" onclick="guardarOrdenEditada()"
                class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-sm shadow-md transition flex items-center">
                <i class="fas fa-save mr-2"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>

<!-- Opciones de técnicos para el modal de edición -->
    <script>
        window.ProgConsolidadoData = <?= json_encode($consolidado, JSON_UNESCAPED_UNICODE) ?>;
        window.tecnicosConsolidado = <?= json_encode($listaTecnicos, JSON_UNESCAPED_UNICODE) ?>;
        window.modalidadesConsolidado = <?= json_encode($listaModalidades ?? [], JSON_UNESCAPED_UNICODE) ?>;
        window.BASE_URL = <?= json_encode(BASE_URL) ?>;
        window.ProgConfig = { baseUrl: window.BASE_URL, tecnicos: window.tecnicosConsolidado };
    </script>
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>js/programacion/core.js"></script>
<script src="<?= BASE_URL ?>js/programacion/consolidado-export.js"></script>
<script src="<?= BASE_URL ?>js/programacion/consolidado-editar.js"></script>