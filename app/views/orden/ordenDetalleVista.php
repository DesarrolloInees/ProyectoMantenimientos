<style>
    /* Asegura que las opciones del Select2 salgan ENCIMA del modal */
    .select2-container--open {
        z-index: 9999999 !important;
    }

    /* Ajuste para que el modal no corte contenido */
    #modalRepuestos {
        z-index: 50;
    }
</style>

<div class="flex justify-between items-center mt-4 bg-gray-100 p-3 rounded border">
    <div class="text-sm font-bold text-gray-700">
        Mostrando <span id="infoPagina"></span>
    </div>
    <div class="space-x-2">
        <button type="button" onclick="cambiarPagina(-1)" class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
            <i class="fas fa-chevron-left"></i> Anterior
        </button>
        <span id="indicadorPagina" class="font-bold text-lg px-3">1</span>
        <button type="button" onclick="cambiarPagina(1)" class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    <div class="text-sm text-gray-500">
        Total Servicios: <span id="totalRegistros">0</span>
    </div>
</div>

<div class="bg-white p-4 rounded shadow-lg w-full">

    <div class="flex flex-wrap justify-between items-center mb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">🛠️ Edición Maestra de Servicios</h2>
            <p class="text-sm text-blue-600 font-bold">Fecha Lote: <?= $_GET['fecha'] ?></p>
        </div>

        <div class="space-x-2">
            <button type="button" onclick="exportarExcelLimpio()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow">
                <i class="fas fa-file-excel mr-2"></i> Excel Limpio
            </button>
            <a href="index.php?pagina=ordenVer" class="bg-gray-500 text-white px-4 py-2 rounded font-bold hover:bg-gray-600">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <form action="index.php?pagina=ordenDetalle&accion=guardarCambios" method="POST">
        <input type="hidden" name="fecha_origen" value="<?= $_GET['fecha'] ?>">

        <div class="overflow-x-auto shadow-inner border rounded" style="max-height: 80vh;">
            <table class="min-w-max text-xs bg-white border-collapse" id="tablaEdicion">

                <thead class="bg-gray-800 text-white uppercase tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="p-2 border bg-gray-900 w-24 text-gray-400">1. Cliente</th>
                        <th class="p-2 border bg-gray-900 w-24 text-gray-400">2. Punto</th>
                        <th class="p-2 border bg-gray-900 w-20 text-gray-400">3. Fecha</th>
                        <th class="p-2 border bg-indigo-900 w-32 text-indigo-100">4. Técnico</th>

                        <th class="p-2 border bg-blue-700 w-40 text-yellow-300 font-bold border-yellow-500 border-l-4">5. Servicio</th>
                        <th class="p-2 border bg-blue-700 w-20 text-yellow-300 font-bold">6. Zona</th>
                        <th class="p-2 border bg-blue-700 w-32 text-yellow-300 font-bold">7. Máquina</th>
                        <th class="p-2 border bg-blue-700 w-64 text-yellow-300 font-bold border-yellow-500 border-r-4">8. ¿Qué se hizo?</th>

                        <th class="p-2 border bg-green-800 w-24 text-white">9. Valor</th>
                        <th class="p-2 border bg-gray-700 text-gray-300">10. Rem</th>

                        <th class="p-2 border">11. Entra</th>
                        <th class="p-2 border">12. Sale</th>
                        <th class="p-2 border bg-orange-600 text-white w-20" title="Tiempo desde servicio anterior">🔁 Desplaz.</th>

                        <th class="p-2 border bg-gray-700">13. Repuestos</th>
                        <th class="p-2 border bg-gray-700">14. Est/Calif</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($servicios)): ?>
                        <tr>
                            <td colspan="15" class="p-4 text-center text-red-500">No hay datos.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($servicios as $s): ?>
                            <?php $idFila = $s['id_ordenes_servicio']; ?>

                            <tr class="hover:bg-blue-50 transition fila-servicio"
                                data-idtecnico="<?= $s['id_tecnico'] ?>"
                                data-idtipomaquina="<?= $s['id_tipo_maquina'] ?>"
                                id="fila_<?= $idFila ?>">

                                <!-- 1. CLIENTE -->
                                <td class="p-1">
                                    <select name="servicios[<?= $idFila ?>][id_cliente]"
                                        onchange="cargarPuntos(<?= $idFila ?>, this.value)"
                                        class="w-full border rounded p-1 text-[10px]">
                                        <?php foreach ($listaClientes as $c): ?>
                                            <option value="<?= $c['id_cliente'] ?>"
                                                data-full="<?= $c['nombre_cliente'] ?>"
                                                <?= $c['id_cliente'] == $s['id_cliente'] ? 'selected' : '' ?>>
                                                <?= substr($c['nombre_cliente'], 0, 20) ?>...
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>


                                <!-- 2. PUNTO -->
                                <td class="p-1">
                                    <select id="sel_punto_<?= $idFila ?>" name="servicios[<?= $idFila ?>][id_punto]"
                                        onchange="cargarMaquinas(<?= $idFila ?>, this.value)"
                                        class="w-full border rounded p-1 text-[10px]">
                                        <option value="<?= $s['id_punto'] ?? '' ?>"
                                            data-full="<?= $s['nombre_punto'] ?>" selected>
                                            <?= substr($s['nombre_punto'], 0, 20) ?>...
                                        </option>
                                    </select>
                                    <div id="td_delegacion_<?= $idFila ?>" class="hidden"><?= $s['delegacion'] ?></div>
                                </td>

                                <!-- 3. FECHA -->
                                <td class="p-1"><input type="date" name="servicios[<?= $idFila ?>][fecha_individual]" value="<?= $s['fecha_visita'] ?>" class="w-full border rounded text-[10px]"></td>

                                <!-- 4. TÉCNICO -->
                                <td class="p-1 bg-indigo-50">
                                    <select name="servicios[<?= $idFila ?>][id_tecnico]" class="w-full border rounded p-1 font-bold text-indigo-800" onchange="calcularDesplazamientos()">
                                        <?php foreach ($listaTecnicos as $t): ?>
                                            <option value="<?= $t['id_tecnico'] ?>" <?= $t['id_tecnico'] == $s['id_tecnico'] ? 'selected' : '' ?>>
                                                <?= $t['nombre_tecnico'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- 5. SERVICIO -->
                                <td class="p-1 bg-blue-50 border-l-4 border-blue-200">
                                    <select name="servicios[<?= $idFila ?>][id_manto]"
                                        id="sel_servicio_<?= $idFila ?>"
                                        onchange="actualizarTarifa(<?= $idFila ?>)"
                                        class="w-full border rounded p-1 font-bold text-blue-900 text-xs">
                                        <?php foreach ($listaMantos as $m): ?>
                                            <option value="<?= $m['id_tipo_mantenimiento'] ?>" <?= $m['id_tipo_mantenimiento'] == $s['id_manto'] ? 'selected' : '' ?>>
                                                <?= $m['nombre_completo'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- 6. ZONA/MODALIDAD (AHORA ES SELECT) ⭐ -->
                                <td class="p-1 bg-blue-50">
                                    <select name="servicios[<?= $idFila ?>][id_modalidad]"
                                        id="sel_modalidad_<?= $idFila ?>"
                                        onchange="actualizarTarifa(<?= $idFila ?>)"
                                        class="w-full border rounded p-1 font-bold text-gray-700 text-xs">
                                        <?php foreach ($listaModalidades as $mod): ?>
                                            <option value="<?= $mod['id_modalidad'] ?>" <?= $mod['id_modalidad'] == $s['id_modalidad'] ? 'selected' : '' ?>>
                                                <?= $mod['nombre_modalidad'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- 7. MÁQUINA -->
                                <td class="p-1 bg-blue-50">
                                    <select id="sel_maq_<?= $idFila ?>"
                                        name="servicios[<?= $idFila ?>][id_maquina]"
                                        onchange="actualizarTipoMaquina(<?= $idFila ?>)"
                                        class="w-full border rounded p-1 font-mono text-blue-600 font-bold text-xs">
                                        <option value="<?= $s['id_maquina'] ?>"
                                            data-tipo="<?= $s['nombre_tipo_maquina'] ?>"
                                            data-idtipomaquina="<?= $s['id_tipo_maquina'] ?>"
                                            selected>
                                            <?= $s['device_id'] ?>
                                        </option>
                                    </select>
                                    <div id="td_tipomaq_<?= $idFila ?>" class="text-[9px] text-gray-400"><?= $s['nombre_tipo_maquina'] ?></div>
                                </td>

                                <!-- 8. OBSERVACIONES -->
                                <td class="p-1 bg-blue-50 border-r-4 border-blue-200">
                                    <textarea name="servicios[<?= $idFila ?>][obs]" rows="3" class="w-full border rounded text-xs p-1 shadow-inner focus:bg-white transition"><?= $s['que_se_hizo'] ?></textarea>
                                </td>

                                <!-- 9. VALOR -->
                                <td class="p-1 bg-green-50">
                                    <input type="text" name="servicios[<?= $idFila ?>][valor]"
                                        id="input_valor_<?= $idFila ?>"
                                        value="<?= number_format($s['valor_servicio'], 0, ',', '.') ?>"
                                        class="w-full border rounded text-right font-bold text-green-700 text-sm">
                                </td>

                                <!-- 10. REMISIÓN -->
                                <td class="p-1"><input type="text" name="servicios[<?= $idFila ?>][remision]" value="<?= $s['numero_remision'] ?>" class="w-16 border rounded text-center text-[10px]"></td>

                                <!-- 11. ENTRADA -->
                                <td class="p-1">
                                    <input type="time" name="servicios[<?= $idFila ?>][entrada]"
                                        id="hora_entrada_<?= $idFila ?>"
                                        value="<?= $s['hora_entrada'] ?>"
                                        onchange="calcularDesplazamientos()"
                                        class="w-full border rounded text-xs">
                                </td>

                                <!-- 12. SALIDA -->
                                <td class="p-1">
                                    <input type="time" name="servicios[<?= $idFila ?>][salida]"
                                        id="hora_salida_<?= $idFila ?>"
                                        value="<?= $s['hora_salida'] ?>"
                                        onchange="calcularDesplazamientos()"
                                        class="w-full border rounded text-xs">
                                </td>

                                <!-- 13. DESPLAZAMIENTO (MOVIDO AQUÍ) ⭐ -->
                                <td class="p-1 bg-orange-50 text-center align-middle">
                                    <span id="desplazamiento_<?= $idFila ?>" class="text-[10px] font-bold text-gray-400">-</span>
                                </td>

                                <!-- 14. REPUESTOS -->
                                <td class="p-1 bg-gray-50 text-center align-middle">
                                    <?php
                                    $jsonParaModal = '[]';
                                    $textoBD = $s['repuestos_usados'] ?? '';
                                    $arrayTemp = [];

                                    // PALABRAS QUE SIGNIFICAN "VACÍO" (Para que no cree items falsos)
                                    $palabrasIgnorar = ['NO', 'NINGUNO', 'NINGUNA', 'SIN REPUESTOS', 'N/A', 'NA', '.', '-', '0', 'VACIO'];

                                    if (!empty($textoBD)) {
                                        $items = explode(',', $textoBD);

                                        foreach ($items as $item) {
                                            $itemLimpio = trim($item);

                                            // 1. Si está vacío o es una palabra de la lista negra, SALTÁTELO
                                            if (empty($itemLimpio) || in_array(strtoupper($itemLimpio), $palabrasIgnorar)) {
                                                continue;
                                            }

                                            // 2. Detectar origen (INEES o PROSEGUR)
                                            $origen = 'INEES';
                                            if (strpos(strtoupper($itemLimpio), '(PROSEGUR)') !== false) {
                                                $origen = 'PROSEGUR';
                                                $nombre = str_ireplace('(PROSEGUR)', '', $itemLimpio);
                                            } elseif (strpos(strtoupper($itemLimpio), '(INEES)') !== false) {
                                                $origen = 'INEES';
                                                $nombre = str_ireplace('(INEES)', '', $itemLimpio);
                                            } else {
                                                $nombre = $itemLimpio;
                                            }

                                            // 3. Agregar solo si quedó un nombre válido
                                            if (!empty(trim($nombre))) {
                                                $arrayTemp[] = [
                                                    'id' => '',
                                                    'nombre' => trim($nombre),
                                                    'origen' => $origen
                                                ];
                                            }
                                        }
                                        $jsonParaModal = json_encode($arrayTemp, JSON_UNESCAPED_UNICODE);
                                    }
                                    ?>

                                    <button type="button"
                                        onclick="abrirModalRepuestos(<?= $idFila ?>)"
                                        class="bg-white border border-gray-300 hover:bg-blue-50 text-gray-700 text-[10px] px-2 py-1 rounded w-full shadow-sm transition flex items-center justify-center gap-1">
                                        <i class="fas fa-tools text-blue-500"></i>
                                        <span id="btn_texto_<?= $idFila ?>">
                                            <?= !empty($arrayTemp) ? count($arrayTemp) . ' Items' : 'Gest. Repuestos' ?>
                                        </span>
                                    </button>

                                    <input type="hidden"
                                        name="servicios[<?= $idFila ?>][json_repuestos]"
                                        id="input_json_<?= $idFila ?>"
                                        value='<?= $jsonParaModal ?>'>
                                </td>

                                <!-- 15. ESTADO/CALIFICACIÓN -->
                                <td class="p-1">
                                    <select name="servicios[<?= $idFila ?>][id_estado]" class="w-full text-[9px] border mb-1">
                                        <?php foreach ($listaEstados as $e): ?>
                                            <option value="<?= $e['id_estado'] ?>" <?= $e['id_estado'] == $s['id_estado'] ? 'selected' : '' ?>><?= $e['nombre_estado'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="servicios[<?= $idFila ?>][id_calif]" class="w-full text-[9px] border">
                                        <?php foreach ($listaCalifs as $c): ?>
                                            <option value="<?= $c['id_calificacion'] ?>" <?= $c['id_calificacion'] == $s['id_calif'] ? 'selected' : '' ?>><?= $c['nombre_calificacion'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-center pb-8 sticky bottom-0 bg-white border-t p-2 z-20">
            <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-10 rounded-full shadow-xl">
                <i class="fas fa-save mr-2"></i> GUARDAR TODO
            </button>
        </div>
    </form>
</div>

<div class="flex justify-between items-center mt-4 bg-gray-100 p-3 rounded border">
    <div class="text-sm font-bold text-gray-700">
        Mostrando <span id="infoPagina"></span>
    </div>
    <div class="space-x-2">
        <button type="button" onclick="cambiarPagina(-1)" class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
            <i class="fas fa-chevron-left"></i> Anterior
        </button>
        <span id="indicadorPagina" class="font-bold text-lg px-3">1</span>
        <button type="button" onclick="cambiarPagina(1)" class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    <div class="text-sm text-gray-500">
        Total Servicios: <span id="totalRegistros">0</span>
    </div>

    <div id="modalRepuestos" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg p-6 transform scale-100 transition-transform">

            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between">
                <span>🛠️ Gestión de Repuestos</span>
                <button type="button" onclick="cerrarModal()" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </h3>

            <input type="hidden" id="modal_fila_actual">

            <div class="space-y-4">
                <div class="flex gap-2">
                    <div class="flex-grow">
                        <select id="select_repuesto_modal" class="w-full border rounded p-2 text-sm">
                            <option value="">- Buscar Repuesto -</option>
                        </select>
                    </div>

                    <select id="select_origen_modal" class="border rounded p-2 text-sm bg-gray-100 font-bold text-gray-700">
                        <option value="INEES">INEES</option>
                        <option value="PROSEGUR">PROSEGUR</option>
                    </select>

                    <button type="button" onclick="agregarRepuestoALista()" class="bg-blue-600 text-white px-4 rounded hover:bg-blue-700 shadow transition">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <ul id="lista_repuestos_visual" class="border rounded p-2 h-40 overflow-y-auto bg-gray-50 text-sm">
                    <li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>
                </ul>
            </div>

            <div class="mt-6 text-right">
                <button type="button" onclick="guardarCambiosModal()" class="bg-green-600 text-white px-6 py-2 rounded font-bold hover:bg-green-700 shadow-lg transform hover:scale-105 transition">
                    Confirmar Cambios
                </button>
            </div>
        </div>
    </div>
</div>



<script>
    // 1. CARGAMOS LA LISTA MAESTRA DE REPUESTOS DESDE PHP
    // (Asegúrate de que $listaRepuestos esté disponible en tu vista PHP)
    const catalogoRepuestos = <?= json_encode($listaRepuestos ?? []) ?>;
    // Variable temporal para manipular los repuestos en el modal
    let repuestosTemporales = [];

    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar Select2 en el modal
        $('#select_repuesto_modal').select2({
            width: '100%',
            dropdownParent: $('#modalRepuestos'), // CRÍTICO para modales
            placeholder: "Buscar repuesto...",
            language: {
                noResults: () => "No encontrado"
            }
        });

        // Llenar el select del modal una sola vez
        const select = document.getElementById('select_repuesto_modal');
        let html = '<option value="">- Buscar Repuesto -</option>';
        catalogoRepuestos.forEach(r => {
            html += `<option value="${r.id_repuesto}">${r.nombre_repuesto}</option>`;
        });
        select.innerHTML = html;
    });

    // ==========================================
    // LÓGICA DEL MODAL
    // ==========================================

    function abrirModalRepuestos(idFila) {
        // 1. Guardar qué fila estamos editando
        document.getElementById('modal_fila_actual').value = idFila;

        // 2. Recuperar lo que ya tenga esa fila (del input oculto)
        const inputJson = document.getElementById(`input_json_${idFila}`);
        const valorActual = inputJson.value;

        try {
            repuestosTemporales = valorActual ? JSON.parse(valorActual) : [];
        } catch (e) {
            console.error("Error parseando JSON existente", e);
            repuestosTemporales = [];
        }

        // 3. Renderizar y Mostrar
        renderizarListaVisual();
        document.getElementById('modalRepuestos').classList.remove('hidden');
        document.getElementById('modalRepuestos').classList.add('flex');
    }

    function cerrarModal() {
        document.getElementById('modalRepuestos').classList.add('hidden');
        document.getElementById('modalRepuestos').classList.remove('flex');
        // Limpiar selección
        $('#select_repuesto_modal').val(null).trigger('change');
    }

    function agregarRepuestoALista() {
        // Obtener datos del Select2
        const idRepuesto = $('#select_repuesto_modal').val();
        const dataSelect = $('#select_repuesto_modal').select2('data');
        const nombreRepuesto = dataSelect[0]?.text;
        const origen = document.getElementById('select_origen_modal').value;

        if (!idRepuesto) {
            alert("Por favor seleccione un repuesto");
            return;
        }

        // Agregar al array temporal
        repuestosTemporales.push({
            id: idRepuesto,
            nombre: nombreRepuesto,
            origen: origen
        });

        // Limpiar y renderizar
        $('#select_repuesto_modal').val(null).trigger('change');
        renderizarListaVisual();
    }

    function borrarRepuestoTemporal(index) {
        repuestosTemporales.splice(index, 1);
        renderizarListaVisual();
    }

    function renderizarListaVisual() {
        const ul = document.getElementById('lista_repuestos_visual');
        ul.innerHTML = '';

        if (repuestosTemporales.length === 0) {
            ul.innerHTML = '<li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>';
            return;
        }

        repuestosTemporales.forEach((item, index) => {
            const colorOrigen = item.origen === 'INEES' ? 'text-blue-600' : 'text-orange-600';
            ul.innerHTML += `
                <li class="flex justify-between items-center bg-white p-2 mb-1 border rounded shadow-sm">
                    <span class="text-xs">
                        <b class="${colorOrigen}">[${item.origen}]</b> ${item.nombre}
                    </span>
                    <button type="button" onclick="borrarRepuestoTemporal(${index})" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </li>
            `;
        });
    }

    function guardarCambiosModal() {
        const idFila = document.getElementById('modal_fila_actual').value;

        // 1. Guardar JSON en el input oculto de la fila
        const inputJson = document.getElementById(`input_json_${idFila}`);
        inputJson.value = JSON.stringify(repuestosTemporales);

        // 2. Actualizar el texto del botón visualmente para dar feedback
        const btnTexto = document.getElementById(`btn_texto_${idFila}`);
        const cantidad = repuestosTemporales.length;

        if (cantidad > 0) {
            btnTexto.innerText = `${cantidad} Items`;
            btnTexto.parentElement.classList.add('bg-blue-100', 'border-blue-400');
        } else {
            btnTexto.innerText = "Gest. Repuestos";
            btnTexto.parentElement.classList.remove('bg-blue-100', 'border-blue-400');
        }

        cerrarModal();
    }
    // ==========================================
    // 1. CONFIGURACIÓN INICIAL
    // ==========================================
    let paginaActual = 1;
    const filasPorPagina = 6;
    let totalFilas = 0;
    let totalPaginas = 0;

    document.addEventListener("DOMContentLoaded", function() {
        // Ejecutamos cálculo al inicio
        calcularDesplazamientos();
        iniciarPaginacion();
    });

    // ==========================================
    // 2. ACTUALIZAR TARIFA
    // ==========================================
    function actualizarTarifa(idFila) {
        const inputValor = document.getElementById(`input_valor_${idFila}`);
        const selectMaquina = document.getElementById(`sel_maq_${idFila}`);
        const selectServicio = document.getElementById(`sel_servicio_${idFila}`);
        const selectModalidad = document.getElementById(`sel_modalidad_${idFila}`);

        if (!selectMaquina || !selectServicio || !selectModalidad) return;

        // Obtenemos IDs
        const opcionMaquina = selectMaquina.options[selectMaquina.selectedIndex];
        const idTipoMaquina = opcionMaquina ? opcionMaquina.getAttribute('data-idtipomaquina') : '';
        const idTipoMantenimiento = selectServicio.value;
        const idModalidad = selectModalidad.value;

        if (!idTipoMaquina || !idTipoMantenimiento) return;

        const formData = new FormData();
        formData.append('accion', 'ajaxObtenerPrecio');
        formData.append('id_tipo_maquina', idTipoMaquina);
        formData.append('id_tipo_mantenimiento', idTipoMantenimiento);
        formData.append('id_modalidad', idModalidad);

        fetch('index.php?pagina=ordenDetalle', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                let precio = data.precio || 0;
                inputValor.value = new Intl.NumberFormat('es-CO').format(precio);
                inputValor.style.backgroundColor = "#bbf7d0";
                setTimeout(() => inputValor.style.backgroundColor = "", 500);
            })
            .catch(err => console.error(err));
    }

    function calcularDesplazamientos() {
        console.clear();
        console.log("--- INICIANDO CÁLCULO DE DESPLAZAMIENTOS (CORREGIDO) ---");

        let filas = Array.from(document.querySelectorAll('.fila-servicio'));

        // 1. Extraer datos crudos
        let datosCrudos = filas.map(fila => {
            let idFila = fila.id.replace('fila_', '');
            let selectTecnico = fila.querySelector(`select[name^="servicios"][name$="[id_tecnico]"]`);
            let tecnicoVal = selectTecnico ? selectTecnico.value : "0";

            let entrada = document.getElementById(`hora_entrada_${idFila}`).value;
            let salida = document.getElementById(`hora_salida_${idFila}`).value;

            return {
                idFila: idFila,
                tecnico: parseInt(tecnicoVal) || 0,
                horaEntradaTexto: entrada,
                horaSalidaTexto: salida,
                minutosEntrada: horaAMinutos(entrada),
                minutosSalida: horaAMinutos(salida)
            };
        });

        // ⭐⭐⭐ FILTRO NUEVO: ELIMINAR DUPLICADOS ⭐⭐⭐
        // Usamos un Mapa para dejar solo una copia de cada ID
        let datosUnicos = [];
        const map = new Map();
        for (const item of datosCrudos) {
            if (!map.has(item.idFila)) {
                map.set(item.idFila, true); // Marcamos como visto
                datosUnicos.push(item); // Lo guardamos
            }
        }
        let datos = datosUnicos;
        // ⭐⭐⭐ FIN DEL FILTRO ⭐⭐⭐

        // 2. Ordenar datos
        datos.sort((a, b) => {
            if (a.tecnico !== b.tecnico) return a.tecnico - b.tecnico;
            let minA = a.minutosEntrada !== null ? a.minutosEntrada : 99999;
            let minB = b.minutosEntrada !== null ? b.minutosEntrada : 99999;
            return minA - minB;
        });

        // 3. Comparar (El resto sigue igual...)
        for (let i = 0; i < datos.length; i++) {
            // ... (toda tu lógica de comparación que ya tenías)
            let actual = datos[i];
            let span = document.getElementById(`desplazamiento_${actual.idFila}`);
            if (!span) continue;

            // ... Pega aquí el resto del código del `for` que te pasé antes ...
            // (Si quieres te lo copio completo abajo para que solo sea copiar y pegar)

            // Reset visual
            span.className = "text-[10px] font-bold block";
            span.innerText = "-";

            if (i === 0 || datos[i - 1].tecnico !== actual.tecnico) {
                span.innerText = "00:00"; // Inicio
                span.classList.add("text-gray-400");
                continue;
            }

            let previo = datos[i - 1];

            if (actual.minutosEntrada === null || previo.minutosSalida === null) {
                span.innerText = "--";
                continue;
            }

            let diff = actual.minutosEntrada - previo.minutosSalida;

            if (diff < 0) {
                span.innerText = "Err H.";
                span.classList.add("text-red-500", "font-bold");
            } else {
                let h = Math.floor(diff / 60);
                let m = diff % 60;
                span.innerText = (h > 0 ? `${h}h ` : "") + `${m}m`; // Formato corto

                if (diff > 60) {
                    span.classList.add("text-red-600", "bg-red-100", "px-1", "rounded");
                } else {
                    span.classList.add("text-green-600");
                }
            }
        }
    }

    function horaAMinutos(hora) {
        if (!hora) return null;
        let partes = hora.split(':');
        if (partes.length < 2) return null;
        return (parseInt(partes[0]) * 60) + parseInt(partes[1]);
    }

    // ==========================================
    // 4. FUNCIONES AJAX DE CARGA
    // ==========================================
    function cargarPuntos(idFila, idCliente) {
        let selPunto = document.getElementById(`sel_punto_${idFila}`);
        let selMaq = document.getElementById(`sel_maq_${idFila}`);

        selPunto.innerHTML = '<option>Cargando...</option>';
        selMaq.innerHTML = '<option>Esperando punto...</option>';

        let fd = new FormData();
        fd.append('accion', 'ajaxObtenerPuntos');
        fd.append('id_cliente', idCliente);

        fetch('index.php?pagina=ordenDetalle', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                selPunto.innerHTML = '<option value="">- Seleccione -</option>';
                data.forEach(p => {
                    selPunto.innerHTML += `<option value="${p.id_punto}" data-full="${p.nombre_punto}">${p.nombre_punto}</option>`;
                });

                if (data.length > 0) {
                    selPunto.value = data[0].id_punto;
                    cargarMaquinas(idFila, data[0].id_punto);
                }
            });
    }

    function cargarMaquinas(idFila, idPunto) {
        let selMaq = document.getElementById(`sel_maq_${idFila}`);
        selMaq.innerHTML = '<option>Cargando...</option>';

        let divDel = document.getElementById(`td_delegacion_${idFila}`);
        let fdDel = new FormData();
        fdDel.append('accion', 'ajaxObtenerDelegacion');
        fdDel.append('id_punto', idPunto);
        fetch('index.php?pagina=ordenDetalle', {
                method: 'POST',
                body: fdDel
            })
            .then(r => r.json())
            .then(d => {
                if (divDel) divDel.innerText = d.delegacion;
            });

        let fd = new FormData();
        fd.append('accion', 'ajaxObtenerMaquinas');
        fd.append('id_punto', idPunto);

        fetch('index.php?pagina=ordenDetalle', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                selMaq.innerHTML = '<option value="">- Seleccione -</option>';
                data.forEach(m => {
                    selMaq.innerHTML += `<option value="${m.id_maquina}" data-tipo="${m.nombre_tipo_maquina}" data-idtipomaquina="${m.id_tipo_maquina}">
                        ${m.device_id} (${m.nombre_tipo_maquina})
                    </option>`;
                });

                if (data.length > 0) {
                    selMaq.value = data[0].id_maquina;
                    actualizarTipoMaquina(idFila);
                    actualizarTarifa(idFila);
                }
            });
    }

    function actualizarTipoMaquina(idFila) {
        let selMaq = document.getElementById(`sel_maq_${idFila}`);
        let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
        if (selMaq.selectedIndex >= 0) {
            let opt = selMaq.options[selMaq.selectedIndex];
            if (divTipo) divTipo.innerText = opt.getAttribute('data-tipo') || '';
        }
    }

    // ==========================================
// 5. EXCEL LIMPIO (CORREGIDO)
// ==========================================
function exportarExcelLimpio() {
    if (typeof XLSX === 'undefined') {
        alert("Error: Librería SheetJS no cargada.");
        return;
    }

    let tabla = document.getElementById("tablaEdicion");
    let filas = Array.from(tabla.querySelectorAll('tbody tr'));
    let serviciosPorDelegacion = {};

    filas.forEach((fila, index) => {
        let celdas = fila.querySelectorAll('td');

        // Si la fila es de "No hay datos", ignorar
        if (celdas.length < 14) return;

        // ... (Lógica de extracción de textos igual que antes) ...
        let delegacionTxt = obtenerTextoDeDiv(celdas[1]);
        let tipoMaqTxt = obtenerTextoDeDiv(celdas[6]);

        // Lógica Preventivo/Correctivo
        let txtServicio = obtenerTexto(celdas[4]).toLowerCase();
        let esPrevBasico = txtServicio.includes('basico') || txtServicio.includes('básico');
        let esPrevProfundo = txtServicio.includes('profundo') || txtServicio.includes('completo');
        let esCorrectivo = txtServicio.includes('correctivo') || txtServicio.includes('reparacion');
        if (!esPrevBasico && !esPrevProfundo && !esCorrectivo && txtServicio.includes('preventivo')) esPrevBasico = true;

        // Duración y Repuestos
        let entrada = obtenerValorInput(celdas[10]);
        let salida = obtenerValorInput(celdas[11]);
        let duracionCalc = calcularDuracion(entrada, salida);

        // ⭐ CAPTURAR DESPLAZAMIENTO (Está en la columna índice 12)
        let desplazamientoTxt = celdas[12].innerText.trim();

        // ⭐ NUEVO: Limpiar "Err H." en Desplazamiento
        if (desplazamientoTxt.includes("Err H.")) {
            desplazamientoTxt = "";
        }

        let txtRepuestos = "";
        if (celdas[13]) {
            txtRepuestos = celdas[13].getAttribute('data-full') || celdas[13].innerText.trim();
        }

        // ⭐ NUEVO: Limpiar "Gest. Repuestos"
        if (txtRepuestos.includes("Gest. Repuestos")) {
            txtRepuestos = "";
        }

        // Limpieza estándar (sin, no, ningun, n/a)
        if (txtRepuestos.match(/(sin|no|ningun|n\/a)/i)) txtRepuestos = "";

        let datos = {
            device_id: obtenerTextoSelect(celdas[6]),
            remision: obtenerValorInput(celdas[9]),
            cliente: obtenerTextoSelect(celdas[0]),
            punto: obtenerTextoSelect(celdas[1]),
            esPrevBasico: esPrevBasico ? "X" : "",
            esPrevProfundo: esPrevProfundo ? "X" : "",
            esCorrectivo: esCorrectivo ? "X" : "",
            valor: obtenerValorInput(celdas[8]),
            obs: obtenerValorTextArea(celdas[7]),
            delegacion: delegacionTxt || "SIN ASIGNAR",
            fecha: obtenerValorInput(celdas[2]),
            tecnico: obtenerTextoSelect(celdas[3]),
            tipoMaquina: tipoMaqTxt,
            servicio: obtenerTextoSelect(celdas[4]),
            horaEntrada: entrada,
            horaSalida: salida,
            duracion: duracionCalc,
            desplazamiento: desplazamientoTxt, // Ya viene limpio si tenía Err H.
            repuestos: txtRepuestos,           // Ya viene limpio si tenía Gest. Repuestos
            estado: obtenerTextoSelect(celdas[14], 0),
            calificacion: obtenerTextoSelect(celdas[14], 1),
            modalidad: obtenerTextoSelect(celdas[5])
        };

        let keyDel = datos.delegacion;
        if (!serviciosPorDelegacion[keyDel]) {
            serviciosPorDelegacion[keyDel] = [];
        }
        serviciosPorDelegacion[keyDel].push(datos);
    });

    // CREAR EXCEL
    let workbook = XLSX.utils.book_new();
    let hayDatos = Object.keys(serviciosPorDelegacion).length > 0;

    if (!hayDatos) {
        alert("No hay datos válidos para exportar.");
        return;
    }

    for (let delegacion in serviciosPorDelegacion) {
        let lista = serviciosPorDelegacion[delegacion];

        // ⭐ AGREGAMOS LA COLUMNA 'Desplazamiento' EN LA MATRIZ
        let matriz = [
            [
                'Device_id', 'Número de Remisión', 'Cliente', 'Nombre Punto',
                'Preventivo Básico', 'Preventivo Profundo', 'Correctivo',
                'Tarifa', 'Observaciones', 'Delegación', 'Fecha', 'Técnico',
                'Tipo de Máquina', 'Tipo de Servicio', 'Hora Entrada', 'Hora Salida',
                'Duración', 'Desplazamiento', 'Repuestos', 'Estado de la Máquina',
                'Calificación del Servicio', 'Modalidad Operativa'
            ]
        ];

        lista.forEach(d => {
            matriz.push([
                d.device_id, d.remision, d.cliente, d.punto,
                d.esPrevBasico, d.esPrevProfundo, d.esCorrectivo,
                d.valor, d.obs, d.delegacion, d.fecha, d.tecnico,
                d.tipoMaquina, d.servicio, d.horaEntrada, d.horaSalida,
                d.duracion, d.desplazamiento, d.repuestos, d.estado,
                d.calificacion, d.modalidad
            ]);
        });

        let ws = XLSX.utils.aoa_to_sheet(matriz);
        
        // Ajustamos anchos de columna
        ws['!cols'] = [
            { wch: 15 }, { wch: 12 }, { wch: 25 }, { wch: 25 },
            { wch: 8 },  { wch: 8 },  { wch: 8 },  { wch: 12 },
            { wch: 35 }, { wch: 15 }, { wch: 12 }, { wch: 20 },
            { wch: 15 }, { wch: 20 }, { wch: 10 }, { wch: 10 },
            { wch: 10 }, { wch: 12 }, { wch: 30 }, { wch: 15 }, 
            { wch: 15 }, { wch: 15 }
        ];

        let nombreHoja = delegacion.replace(/[:\\/?*\[\]]/g, "").substring(0, 30) || "Hoja1";
        XLSX.utils.book_append_sheet(workbook, ws, nombreHoja);
    }

    // 5. DESCARGAR EL ARCHIVO
    let fecha = "<?= $_GET['fecha'] ?>";
    XLSX.writeFile(workbook, `${fecha}.xlsx`);
}
    // ==========================================
    // 6. UTILIDADES BLINDADAS (Anti-Error)
    // ==========================================

    // Obtener texto de select o texto plano
    // ==========================================
    // NUEVA VERSIÓN: Busca el nombre completo real
    // ==========================================
    function obtenerTextoSelect(celda, index = 0) {
        if (!celda) return "";
        let selects = celda.querySelectorAll('select');

        if (selects && selects[index]) {
            let sel = selects[index];
            if (sel.selectedIndex >= 0) {
                let opcion = sel.options[sel.selectedIndex];
                // TRUCO: Si existe data-full, úsalo. Si no, usa el texto normal.
                return opcion.getAttribute('data-full') || opcion.text.trim();
            }
        }
        return celda.innerText.trim();
    }

    // Obtener value de input
    function obtenerValorInput(celda) {
        if (!celda) return ""; // BLINDAJE
        let input = celda.querySelector('input');
        return input ? input.value : "";
    }

    // Obtener value de textarea
    function obtenerValorTextArea(celda) {
        if (!celda) return ""; // BLINDAJE
        let txt = celda.querySelector('textarea');
        return txt ? txt.value : "";
    }

    // Obtener texto general
    function obtenerTexto(celda) {
        if (!celda) return ""; // BLINDAJE: ESTO CORRIGE TU ERROR ESPECÍFICO
        let el = celda.querySelector('input, textarea, select');
        if (el) {
            if (el.tagName === 'SELECT') return el.options[el.selectedIndex].text;
            return el.value;
        }
        return celda.innerText.trim();
    }

    // Obtener texto de un DIV oculto
    function obtenerTextoDeDiv(celda) {
        if (!celda) return "";
        let div = celda.querySelector('div');
        return div ? div.innerText.trim() : "";
    }

    function calcularDuracion(e, s) {
        if (!e || !s) return "";
        let mE = horaAMinutos(e);
        let mS = horaAMinutos(s);
        let diff = mS - mE;
        if (diff < 0) diff += 1440;
        let h = Math.floor(diff / 60);
        let m = (diff % 60).toString().padStart(2, '0');
        return `${h}:${m}`;
    }

    // ==========================================
    // 7. PAGINACIÓN
    // ==========================================
    function iniciarPaginacion() {
        const filas = document.querySelectorAll('#tablaEdicion tbody tr');
        if (filas.length <= 1 && filas[0].innerText.includes("No hay datos")) return;

        totalFilas = filas.length;
        document.getElementById('totalRegistros').innerText = totalFilas;
        totalPaginas = Math.ceil(totalFilas / filasPorPagina);
        mostrarPagina(paginaActual);
    }

    function cambiarPagina(dir) {
        let nueva = paginaActual + dir;
        if (nueva > 0 && nueva <= totalPaginas) {
            paginaActual = nueva;
            mostrarPagina(paginaActual);
        }
    }

    function mostrarPagina(pag) {
        let filas = document.querySelectorAll('#tablaEdicion tbody tr');
        let inicio = (pag - 1) * filasPorPagina;
        let fin = inicio + filasPorPagina;
        filas.forEach((tr, i) => {
            tr.style.display = (i >= inicio && i < fin) ? 'table-row' : 'none';
        });
        document.getElementById('indicadorPagina').innerText = `${pag} / ${totalPaginas}`;
        let finM = fin > totalFilas ? totalFilas : fin;
        document.getElementById('infoPagina').innerText = `${inicio + 1} - ${finM} de ${totalFilas}`;
    }
</script>