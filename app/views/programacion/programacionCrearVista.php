<?php if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado."); ?>

<div class="w-full max-w-4xl mx-auto">

    <?php if ($mensajeExito): ?>
        <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-4 shadow-md border-l-4 border-green-500">
            <i class="fas fa-check-circle mr-2"></i><?= $mensajeExito ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($datosParaExcel)): ?>
        <div
            class="bg-indigo-50 border border-indigo-200 p-5 rounded-lg mb-6 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h3 class="font-bold text-indigo-900 text-lg"><i class="fas fa-file-excel mr-2 text-green-600"></i> ¡Tu
                    programación está lista!</h3>
                <p class="text-sm text-indigo-700">Descarga el archivo Excel con las rutas generadas para compartirlas con
                    los técnicos.</p>
            </div>
            <button type="button" id="btnExportarProgramacion"
                onclick='generarExcelProgramacion(<?= json_encode($datosParaExcel) ?>)'
                class="px-6 py-3 bg-green-600 text-white font-bold rounded-lg shadow-lg hover:bg-green-700 transition flex items-center">
                <span id="txtBotonProg"><i class="fas fa-download mr-2"></i> Descargar Excel Programado</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-4 shadow-md border-l-4 border-red-500">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?= implode('<br>', $errores) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($propuesta)): ?>
        <!-- ========================================= -->
        <!-- PREVISUALIZACIÓN -->
        <!-- ========================================= -->
        <div class="bg-white rounded-xl shadow-lg border-2 border-indigo-300 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 p-5 flex justify-between items-center text-white">
                <div>
                    <h2 class="text-2xl font-bold">
                        <i class="fas fa-calendar-check mr-2"></i> Previsualización de Rutas
                    </h2>
                    <p class="text-sm text-indigo-200 mt-1">Revise y edite antes de aprobar</p>
                </div>
                <div class="text-sm bg-indigo-800 px-4 py-2 rounded-lg">
                    <?= count($propuesta) ?> servicios programados
                </div>
            </div>

            <form action="<?= BASE_URL ?>programacionCrear" method="POST" id="formGuardar">
                <input type="hidden" name="accion" value="guardar_definitivo">

                <div class="overflow-x-auto max-h-[600px]">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-3">Fecha</th>
                                <th class="px-6 py-3">Técnico (Ruta)</th>
                                <th class="px-6 py-3">Zona</th>
                                <th class="px-6 py-3">Punto</th>
                                <th class="px-6 py-3 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaServicios">
                            <?php
                            $fechaActual = '';
                            $serviciosPorDia = [];
                            foreach ($propuesta as $item) {
                                $fecha = $item['fecha_visita'];
                                $serviciosPorDia[$fecha] = ($serviciosPorDia[$fecha] ?? 0) + 1;
                            }

                            foreach ($propuesta as $index => $item):
                                if ($fechaActual != $item['fecha_visita']):
                                    $fechaActual = $item['fecha_visita'];
                                    $fechaObj = new DateTime($fechaActual);
                                    $diaSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                    $nombreDia = $diaSemana[$fechaObj->format('w')];
                                    $nombreMes = $meses[(int) $fechaObj->format('n')];
                                    $cantidadDia = $serviciosPorDia[$fechaActual];
                                    ?>
                                    <tr class="bg-gradient-to-r from-indigo-100 to-blue-50 border-y-2 border-indigo-300">
                                        <td colspan="5" class="px-6 py-3 font-bold text-gray-800 text-sm uppercase tracking-wide">
                                            <i class="fas fa-calendar-day mr-2 text-indigo-600"></i>
                                            <?= $nombreDia ?>, <?= $fechaObj->format('d') ?> de <?= $nombreMes ?>
                                            <span class="ml-3 text-xs bg-indigo-600 text-white px-3 py-1 rounded-full">
                                                <?= $cantidadDia ?> servicio<?= $cantidadDia != 1 ? 's' : '' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <tr class="bg-white border-b hover:bg-blue-50 transition" id="fila_<?= $index ?>">
                                    <td class="px-6 py-4">
                                        <input type="date" name="final[<?= $index ?>][fecha_visita]"
                                            value="<?= htmlspecialchars($item['fecha_visita']) ?>"
                                            class="border border-gray-300 rounded px-2 py-1 text-xs w-full" required>
                                        <input type="hidden" name="final[<?= $index ?>][id_punto]"
                                            value="<?= $item['id_punto'] ?>">
                                    </td>
                                    <td class="px-6 py-4">
                                        <select name="final[<?= $index ?>][id_tecnico]"
                                            class="border border-gray-300 rounded px-2 py-1 text-xs w-full bg-white" required>
                                            <?php foreach ($listaTecnicos as $tec): ?>
                                                <option value="<?= $tec['id_tecnico'] ?>" <?= $tec['id_tecnico'] == $item['id_tecnico'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tec['nombre_tecnico']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-3 py-1 rounded-full">
                                            <?= htmlspecialchars($item['zona']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">
                                            <?= htmlspecialchars($item['nombre_punto']) ?>
                                            <?php if (!empty($item['es_fuera_servicio'])): ?>
                                                <span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-0.5 rounded ml-1"
                                                    title="Maquina fuera de servicio">
                                                    <i class="fas fa-power-off mr-1"></i>Fuera de Servicio
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            <?= htmlspecialchars($item['nombre_cliente']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button type="button" onclick="eliminarFila(<?= $index ?>)"
                                            class="text-red-600 hover:text-red-800 px-2 py-1 rounded">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-6 bg-gray-50 border-t flex justify-between items-center">
                    <button type="button" onclick="history.back();"
                        class="px-6 py-3 bg-indigo-100 text-indigo-700 hover:bg-indigo-200 rounded-lg font-bold transition">
                        <i class="fas fa-arrow-left mr-2"></i> Volver para Agregar Más
                    </button>
                    <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 font-bold shadow-lg">
                        <i class="fas fa-check-circle mr-2"></i> Aprobar y Crear Órdenes
                    </button>
                </div>
            </form>
        </div>

    <?php else: ?>

        <!-- ========================================= -->
        <!-- SECCION: MAQUINAS FUERA DE SERVICIO -->
        <!-- ========================================= -->
        <?php if (!empty($listaMaquinasInactivas)): ?>
            <div class="bg-white p-6 rounded-xl shadow-md border border-red-200 mb-6" id="seccionMaquinasInactivas">
                <h2 class="text-xl font-bold text-red-800 mb-2 border-b-2 border-red-500 pb-2">
                    <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i> Maquinas Fuera de Servicio
                    <span class="ml-2 text-sm font-normal text-red-600">(<?= count($listaMaquinasInactivas) ?>
                        encontradas)</span>
                </h2>
                <p class="text-sm text-gray-600 mb-4 bg-red-50 p-3 rounded border-l-4 border-red-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Selecciona una o varias máquinas para buscar <strong>puntos aledaños</strong> — si marcas máquinas de
                    <strong>zonas distintas</strong>, aparece un botón abajo para verlas todas juntas en el mismo modal.
                    <br><span class="text-xs text-gray-500">Los puntos que no se programen tendrán su máquina restaurada
                        automáticamente a "Operativo" al aprobar la programación.</span>
                </p>

                <?php
                $maquinasPorZona = [];
                foreach ($listaMaquinasInactivas as $maq) {
                    $zona = $maq['zona'] ?: 'Sin Zona';
                    $maquinasPorZona[$zona][] = $maq;
                }
                ?>

                <div class="space-y-4">
                    <?php foreach ($maquinasPorZona as $zona => $maquinas): ?>
                        <div class="border border-red-200 rounded-lg overflow-hidden">
                            <div class="bg-red-100 px-4 py-2 flex justify-between items-center">
                                <span class="font-bold text-red-800">
                                    <i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars($zona) ?>
                                    <span class="text-xs font-normal text-red-600">(<?= count($maquinas) ?>
                                        maquina<?= count($maquinas) > 1 ? 's' : '' ?>)</span>
                                </span>
                                <button type="button" onclick="buscarAledaniosZona('<?= htmlspecialchars($zona, ENT_QUOTES) ?>')"
                                    class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded font-semibold shadow-sm transition">
                                    <i class="fas fa-search mr-1"></i> Ver Aledaños
                                </button>
                            </div>

                            <div class="p-3 bg-white">
                                <?php foreach ($maquinas as $maq): ?>
                                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg mb-2 border border-red-100 hover:shadow-md transition"
                                        id="maquina_<?= $maq['id_punto'] ?>">
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox"
                                                class="w-5 h-5 text-red-600 rounded border-red-300 focus:ring-red-500 check-maquina-inactiva"
                                                value="<?= $maq['id_punto'] ?>" data-zona="<?= htmlspecialchars($maq['zona']) ?>"
                                                data-device="<?= htmlspecialchars($maq['device_id']) ?>"
                                                data-nombre="<?= htmlspecialchars($maq['nombre_punto']) ?>"
                                                onchange="toggleMaquinaSeleccion(<?= $maq['id_punto'] ?>)">
                                            <div>
                                                <div class="font-bold text-gray-900 text-sm">
                                                    <?= htmlspecialchars($maq['nombre_punto']) ?>
                                                    <span class="badge badge-danger ml-1 text-xs">FUERA DE SERVICIO</span>
                                                </div>
                                                <div class="text-xs text-gray-600">
                                                    <?= htmlspecialchars($maq['nombre_cliente'] ?? '') ?> |
                                                    <?= htmlspecialchars($maq['device_id'] ?? '') ?> |
                                                    <?= htmlspecialchars($maq['nombre_tipo_maquina'] ?? '') ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right flex items-center space-x-2">
                                            <button type="button"
                                                onclick="buscarAledaniosPunto(<?= $maq['id_punto'] ?>, '<?= htmlspecialchars($maq['zona'], ENT_QUOTES) ?>')"
                                                class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded font-semibold shadow-sm transition">
                                                <i class="fas fa-search-location mr-1"></i> Ver Aledaños
                                            </button>
                                            <button type="button"
                                                onclick="restaurarMaquinaIndividual('<?= htmlspecialchars($maq['device_id'], ENT_QUOTES) ?>')"
                                                class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded font-semibold shadow-sm transition"
                                                title="Volver a Operativo (no se va a programar)">
                                                <i class="fas fa-check-circle mr-1"></i> Restaurar a Operativo
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- La barra flotante "N máquinas · N zonas" se inyecta por JS cuando aplica -->
                <div id="resultadosAledanios" class="mt-4" style="display:none;"></div>
            </div>
        <?php else: ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg mb-6">
                <p class="font-bold text-green-800">
                    <i class="fas fa-check-circle mr-1"></i> No hay maquinas fuera de servicio actualmente.
                </p>
                <p class="text-sm text-green-700">
                    Puedes importar un Excel en <a href="<?= BASE_URL ?>importarEstadoMaquina"
                        class="underline font-bold">Importar Estado Maquinas</a> para marcarlas.
                </p>
            </div>
        <?php endif; ?>

        <!-- ========================================= -->
        <!-- PASO 1: DELEGACIÓN Y CLIENTES (reparado) -->
        <!-- ========================================= -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 mb-4 border-b-2 border-indigo-500 pb-2">
                <i class="fas fa-map-marked-alt mr-2 text-indigo-600"></i> Programación de Rutas por Semana
            </h2>

            <form method="GET" action="<?= BASE_URL ?>programacionCrear" id="formDelegacion" class="mb-6">
                <input type="hidden" name="pagina" value="programacionCrear">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-building mr-1"></i> Delegación *
                        </label>
                        <select name="delegacion" onchange="document.getElementById('formDelegacion').submit()"
                            class="w-full border-gray-300 rounded-lg shadow-sm" required>
                            <option value="">-- Seleccione una delegación --</option>
                            <?php foreach ($listaDelegaciones as $del): ?>
                                <option value="<?= $del['id_delegacion'] ?>" <?= $delegacionSeleccionada == $del['id_delegacion'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($del['nombre_delegacion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!empty($delegacionSeleccionada) && !empty($listaClientes)): ?>
                        <div class="md:col-span-2">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-semibold text-gray-700">
                                    <i class="fas fa-users mr-1"></i> Clientes a Incluir
                                </label>
                                <div class="space-x-2">
                                    <button type="button" onclick="seleccionarTodosClientes(true)"
                                        class="text-xs bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1 rounded font-semibold">
                                        <i class="fas fa-check-double mr-1"></i> Todos
                                    </button>
                                    <button type="button" onclick="seleccionarTodosClientes(false)"
                                        class="text-xs bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded font-semibold">
                                        <i class="fas fa-times mr-1"></i> Ninguno
                                    </button>
                                    <button type="submit"
                                        class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded font-semibold">
                                        <i class="fas fa-sync-alt mr-1"></i> Aplicar
                                    </button>
                                </div>
                            </div>
                            <div class="border border-gray-300 rounded-lg p-3 bg-white max-h-48 overflow-y-auto">
                                <?php foreach ($listaClientes as $cliente): ?>
                                    <label class="flex items-start space-x-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                        <input type="checkbox" name="clientes[]" value="<?= $cliente['id_cliente'] ?>"
                                            <?= in_array($cliente['id_cliente'], $clientesSeleccionados) ? 'checked' : '' ?>
                                            class="mt-1 w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 checkbox-cliente">
                                        <span class="text-sm text-gray-700 flex-1">
                                            <strong><?= htmlspecialchars($cliente['nombre_cliente']) ?></strong>
                                            <span class="text-gray-500">(<?= $cliente['puntos_pendientes'] ?> puntos
                                                pendientes)</span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Por defecto están TODOS seleccionados.</strong>
                                Desmarca los clientes que NO quieres programar y pulsa "Aplicar".
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($delegacionSeleccionada) && !empty($conteoZonas)): ?>
                        <div class="md:col-span-3 bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-sm font-bold text-blue-800 mb-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Puntos Pendientes por Zona (<?= array_sum($conteoZonas) ?> total):
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($conteoZonas as $zona => $total): ?>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full">
                                        <?= htmlspecialchars($zona) ?>: <?= $total ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($clientesSeleccionados) < count($listaClientes)): ?>
                                <p class="text-xs text-orange-600 mt-2">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <?= (count($listaClientes) - count($clientesSeleccionados)) ?> cliente(s) excluidos del cálculo
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (!empty($delegacionSeleccionada) && !empty($listaZonas)): ?>

                <!-- ========================================= -->
                <!-- PASO 2: CONFIGURAR CALENDARIO SEMANAL -->
                <!-- ========================================= -->
                <form action="<?= BASE_URL ?>programacionCrear" method="POST" id="formCalendario">
                    <input type="hidden" name="accion" value="previsualizar">
                    <input type="hidden" name="delegacion" value="<?= htmlspecialchars($delegacionSeleccionada) ?>">
                    <?php foreach ($clientesSeleccionados as $cliente_id): ?>
                        <input type="hidden" name="clientes[]" value="<?= htmlspecialchars($cliente_id) ?>">
                    <?php endforeach; ?>

                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border-2 border-blue-300 mb-6 mt-6">
                        <h3 class="font-bold text-blue-900 mb-4 text-lg">
                            <i class="fas fa-calendar-week mr-2"></i> Configuración del Calendario Semanal
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="text-sm font-semibold text-gray-700 mb-2 block">Fecha de Inicio *</label>
                                <input type="date" name="fecha_inicio" required
                                    value="<?= date('Y-m-d', strtotime('monday this week')) ?>"
                                    class="w-full border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-700 mb-2 block">Número de Semanas *</label>
                                <input type="number" name="semanas" min="1" max="12" value="1" required
                                    class="w-full border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-gray-700 mb-2 block">Máximo Servicios por Día *</label>
                                <input type="number" name="max_servicios" min="1" max="20" value="8" required
                                    class="w-full border-gray-300 rounded-lg">
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-route mr-2 text-indigo-600"></i>
                                Planificación Semanal de Rutas
                            </h4>
                            <p class="text-sm text-gray-600 mb-4 bg-blue-50 p-3 rounded border-l-4 border-blue-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Ejemplo:</strong> Juan Pérez puede trabajar <strong>Lunes: Sur + Sur Oriente</strong>,
                                <strong>Martes: Sur Occidente + Sur</strong>, etc.
                                <br><span class="text-xs">Cada día puede tener diferentes combinaciones de zonas para el mismo
                                    técnico.</span>
                            </p>

                            <div class="space-y-4">
                                <?php
                                $diasConfig = [
                                    'lunes' => ['color' => 'blue', 'bg' => 'bg-blue-50', 'border' => 'border-blue-300'],
                                    'martes' => ['color' => 'green', 'bg' => 'bg-green-50', 'border' => 'border-green-300'],
                                    'miercoles' => ['color' => 'yellow', 'bg' => 'bg-yellow-50', 'border' => 'border-yellow-300'],
                                    'jueves' => ['color' => 'orange', 'bg' => 'bg-orange-50', 'border' => 'border-orange-300'],
                                    'viernes' => ['color' => 'red', 'bg' => 'bg-red-50', 'border' => 'border-red-300'],
                                    'sabado' => ['color' => 'purple', 'bg' => 'bg-purple-50', 'border' => 'border-purple-300']
                                ];
                                foreach ($diasConfig as $dia => $config):
                                    ?>
                                    <div
                                        class="border-2 <?= $config['border'] ?> rounded-lg p-4 <?= $config['bg'] ?> transition hover:shadow-md">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                            <div class="md:col-span-1">
                                                <div class="flex items-center mb-2">
                                                    <i
                                                        class="fas fa-calendar-day mr-2 text-<?= $config['color'] ?>-600 text-xl"></i>
                                                    <span
                                                        class="font-bold text-gray-800 text-lg capitalize"><?= ucfirst($dia) ?></span>
                                                </div>
                                                <select name="tecnico_<?= $dia ?>" id="tecnico_<?= $dia ?>"
                                                    class="w-full border-gray-300 rounded-lg text-sm bg-white shadow-sm"
                                                    onchange="toggleZonas('<?= $dia ?>')">
                                                    <option value="">-- Sin programar este día --</option>
                                                    <?php foreach ($listaTecnicos as $tec): ?>
                                                        <option value="<?= $tec['id_tecnico'] ?>">
                                                            <?= htmlspecialchars($tec['nombre_tecnico']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                                    <i class="fas fa-map-marked-alt mr-1"></i> Zonas a Recorrer este Día
                                                </label>
                                                <div id="zonas_container_<?= $dia ?>"
                                                    class="border border-gray-300 rounded-lg p-3 bg-gray-50 max-h-40 overflow-y-auto opacity-50 pointer-events-none">
                                                    <?php foreach ($listaZonas as $zona): ?>
                                                        <label
                                                            class="flex items-start space-x-2 p-2 hover:bg-white rounded cursor-pointer zona-label-<?= $dia ?>">
                                                            <input type="checkbox" name="zonas_<?= $dia ?>[]"
                                                                value="<?= htmlspecialchars($zona) ?>"
                                                                class="mt-1 w-4 h-4 text-<?= $config['color'] ?>-600 rounded border-gray-300 focus:ring-<?= $config['color'] ?>-500 checkbox-zona-<?= $dia ?>"
                                                                onchange="updatePreview('<?= $dia ?>')">
                                                            <span class="text-sm text-gray-700 flex-1">
                                                                <strong><?= htmlspecialchars($zona) ?></strong>
                                                                <span class="text-gray-500"> - <?= $conteoZonas[$zona] ?? 0 ?> puntos
                                                                    pendientes</span>
                                                            </span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                                <p class="text-xs text-gray-600 mt-1 flex items-center">
                                                    <i class="fas fa-check-square mr-1"></i> Marca las zonas que deseas incluir en
                                                    este día
                                                </p>
                                            </div>
                                        </div>

                                        <div id="preview_<?= $dia ?>" class="mt-3 hidden">
                                            <div class="bg-white p-3 rounded border border-<?= $config['color'] ?>-200">
                                                <p class="text-xs font-bold text-gray-700 mb-1">
                                                    <i class="fas fa-route mr-1 text-<?= $config['color'] ?>-600"></i>
                                                    Ruta configurada para <span class="capitalize"><?= $dia ?></span>:
                                                </p>
                                                <div id="preview_text_<?= $dia ?>" class="text-xs text-gray-600"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-6 text-center">
                            <button type="submit"
                                class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-10 py-4 rounded-lg font-bold text-lg shadow-xl hover:from-blue-700 hover:to-blue-800">
                                <i class="fas fa-magic mr-2"></i> Generar Programación de Rutas
                            </button>
                        </div>
                    </div>
                </form>

            <?php elseif (!empty($delegacionSeleccionada)): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
                    <p class="font-bold text-yellow-800">No hay zonas disponibles en esta delegación</p>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<!-- MODAL DE PUNTOS ALEDAÑOS -->
<div id="modalAledanios" class="fixed inset-0 z-50 hidden bg-black bg-opacity-60 flex items-center justify-center p-4">
    <div
        class="bg-gray-50 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-300">
        <div
            class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-6 py-4 flex justify-between items-center shadow-md">
            <h3 class="font-bold text-lg flex items-center" id="modalAledaniosTitulo">
                <i class="fas fa-map-marked-alt mr-2 text-blue-200"></i> Puntos Aledaños
            </h3>
            <button type="button" onclick="cerrarModalAledanios()"
                class="text-white hover:text-red-300 transition focus:outline-none">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 bg-gray-50" id="modalAledaniosContenido"></div>
        <div class="bg-white px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" onclick="cerrarModalAledanios()"
                class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold transition shadow-sm">
                Cancelar
            </button>
            <button type="button" onclick="agregarAledaniosDesdeModal()"
                class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold shadow-md transition flex items-center">
                <i class="fas fa-plus-circle mr-2"></i> Agregar a Programación
            </button>
        </div>
    </div>
</div>

<!-- ============ CONFIG + LIBRERÍAS + MÓDULOS JS (orden importa) ============ -->
<script>
    window.ProgConfig = {
        baseUrl: <?= json_encode(BASE_URL) ?>,
        tecnicos: <?= json_encode($listaTecnicos) ?>
    };
</script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>js/programacion/core.js"></script>
<script src="<?= BASE_URL ?>js/programacion/clientes-delegacion.js"></script>
<script src="<?= BASE_URL ?>js/programacion/calendario-semanal.js"></script>
<script src="<?= BASE_URL ?>js/programacion/maquinas-inactivas.js"></script>
<script src="<?= BASE_URL ?>js/programacion/modal-aledanios.js"></script>
<script src="<?= BASE_URL ?>js/programacion/previsualizacion.js"></script>
<script src="<?= BASE_URL ?>js/programacion/excel-export.js"></script>