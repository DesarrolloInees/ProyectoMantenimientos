<!-- En el head de tu plantilla principal -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* =========================================
       1. ARQUITECTURA DE CAPAS (Z-INDEX)
       ========================================= */

    /* Z-INDEX MAPA DEL SITIO:
       - Tabla Header (Sticky): 10
       - Select2 (Tabla):       40  <-- (Lo bajamos para que quede DEBAJO del menú)
       - Sidebar (Plantilla):   50  <-- (Este es el jefe de la UI principal)
       - Modal (Overlay):       60  <-- (Debe tapar el menú)
       - Select2 (Modal):       9999 <-- (Debe verse encima del modal)
    */

    /* Regla para los Select2 de la TABLA (Hijos del body) */
    body>.select2-container--open {
        z-index: 40 !important;
        /* Menor que el Sidebar (50) */
    }

    /* Regla para los Select2 del MODAL (Hijos de #modalRepuestos) */
    #modalRepuestos .select2-container--open {
        z-index: 9999 !important;
        /* Altísimo para ganar al Modal */
    }

    /* El Modal en sí mismo */
    #modalRepuestos {
        z-index: 60 !important;
        /* Mayor que el Sidebar */
    }

    /* Asegurar que Select2 funcione dentro del modal */
    .select2-container {
        z-index: auto;
        /* Dejar que el contexto decida, excepto cuando abre */
    }

    

    /* Ajuste específico para Select2 (Cliente y Punto) para que coincida con los nativos */
    .select2-container--default .select2-selection--single {
        height: 28px !important;
        /* Misma altura exacta */
        min-height: 28px !important;
        padding: 0px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 4px !important;
        display: flex !important;
        align-items: center !important;
        background-color: #ffffff !important;
    }

    /* Texto interno de Select2 */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        padding-left: 6px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #374151 !important;
    }

    /* Flecha de Select2 */
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 26px !important;
        top: 1px !important;
    }

    /* 4. Colores para diferenciar columnas visualmente */
    #tablaEdicion select[name*="[id_tecnico]"] {
        color: #4338ca !important;
        font-weight: bold;
        background-color: #eef2ff !important;
    }

    #tablaEdicion select[name*="[id_manto]"] {
        color: #1e40af !important;
        font-weight: bold;
        background-color: #eff6ff !important;
    }

    #tablaEdicion select[name*="[id_maquina]"] {
        color: #0369a1 !important;
        font-family: monospace;
        font-weight: bold;
    }

    #tablaEdicion input[name*="[valor]"] {
        color: #15803d !important;
        font-weight: bold;
        background-color: #f0fdf4 !important;
    }

    /* Estilos generales */
    .hidden {
        display: none !important;
    }

    .flex {
        display: flex !important;
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
            <p class="text-sm text-blue-600 font-bold">Fecha Lote: <?= $fecha ?></p>
        </div>

        <div class="space-x-2">
            <button type="button" onclick="exportarExcelLimpio()" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow">
                <i class="fas fa-file-excel mr-2"></i> Excel Limpio
            </button>

            <a href="<?= BASE_URL ?>inicio" class="bg-gray-500 text-white px-4 py-2 rounded font-bold hover:bg-gray-600">
                <i class="fas fa-arrow-left"></i> Volver
            </a>

            <button type="button" onclick="exportarExcelNovedades()" class="bg-red-600 text-white px-4 py-2 rounded font-bold hover:bg-red-700 shadow ml-2">
                <i class="fas fa-file-contract mr-2"></i> Reporte Novedades
            </button>
        </div>


    </div>

    <form action="<?= BASE_URL ?>ordenDetalle" method="POST">

        <input type="hidden" name="accion" value="guardarCambios">

        <input type="hidden" name="fecha_origen" value="<?= $fecha ?>">

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
                        <th class="p-2 border bg-red-900 text-white w-10 text-center" title="Marcar Novedad">⚠️</th>

                        <th class="p-2 border bg-green-800 w-24 text-white">9. Valor</th>
                        <th class="p-2 border bg-gray-700">10. Repuestos</th>
                        <th class="p-2 border bg-gray-700 text-gray-300">11. Rem</th>

                        <th class="p-2 border">12. Entra</th>
                        <th class="p-2 border">13. Sale</th>
                        <th class="p-2 border bg-orange-600 text-white w-20" title="Tiempo desde servicio anterior">🔁 Desplaz.</th>


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
                                        class="select2-cliente w-full border rounded p-1 text-[10px]"> <?php foreach ($listaClientes as $c): ?>
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
                                    <select id="sel_punto_<?= $idFila ?>"
                                        name="servicios[<?= $idFila ?>][id_punto]"
                                        onchange="cargarMaquinas(<?= $idFila ?>, this.value)"
                                        class="select2-punto w-full border rounded p-1 text-[10px]">
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

                                <td class="p-1 text-center bg-gray-50">
                                    <input type="hidden"
                                        name="servicios[<?= $idFila ?>][tiene_novedad]"
                                        id="input_novedad_<?= $idFila ?>"
                                        value="<?= $s['tiene_novedad'] ?>">

                                    <button type="button"
                                        onclick="toggleNovedad(<?= $idFila ?>)"
                                        id="btn_novedad_<?= $idFila ?>"
                                        class="w-full h-8 rounded border shadow-sm transition-colors duration-300 flex items-center justify-center <?= $s['tiene_novedad'] == 1 ? 'bg-red-500 border-red-700 text-white' : 'bg-gray-100 border-gray-300 text-gray-300 hover:bg-gray-200' ?>">

                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                </td>

                                <!-- 9. VALOR -->
                                <td class="p-1 bg-green-50">
                                    <input type="text" name="servicios[<?= $idFila ?>][valor]"
                                        id="input_valor_<?= $idFila ?>"
                                        value="<?= number_format($s['valor_servicio'], 0, ',', '.') ?>"
                                        class="w-full border rounded text-right font-bold text-green-700 text-sm">
                                </td>
                                <!-- 14. REPUESTOS -->
                                <td class="p-1 bg-gray-50 text-center align-middle">
                                    <?php
                                    // Los repuestos ahora vienen en DOS formatos:
                                    // 1. $s['repuestos_texto'] → "Batería, Cable USB (PROSEGUR)"
                                    // 2. $s['repuestos_json'] → '[{"id":"123","nombre":"Batería","origen":"INEES"},...]'

                                    $jsonRepuestos = $s['repuestos_json'] ?? '[]';
                                    $textoRepuestos = $s['repuestos_texto'] ?? '';

                                    // Contar repuestos
                                    $arrayRepuestos = json_decode($jsonRepuestos, true) ?: [];
                                    $cantidadRepuestos = count($arrayRepuestos);
                                    ?>

                                    <!-- 👇 BOTÓN CORREGIDO (quita los ... y pon clases completas) 👇 -->
                                    <button type="button" onclick="abrirModalRepuestos(<?= $idFila ?>)"
                                        class="bg-white border border-gray-300 hover:bg-blue-50 text-gray-700 text-[10px] px-2 py-1 rounded w-full shadow-sm transition flex items-center justify-center gap-1">
                                        <i class="fas fa-tools text-blue-500"></i>
                                        <span id="btn_texto_<?= $idFila ?>">
                                            <?= $cantidadRepuestos > 0 ? $cantidadRepuestos . ' Items' : 'Gest. Repuestos' ?>
                                        </span>
                                    </button>

                                    <!-- JSON para el modal -->
                                    <input type="hidden"
                                        name="servicios[<?= $idFila ?>][json_repuestos]"
                                        id="input_json_<?= $idFila ?>"
                                        value='<?= htmlspecialchars($jsonRepuestos, ENT_QUOTES, 'UTF-8') ?>'>

                                    <!-- Texto para compatibilidad -->
                                    <input type="hidden"
                                        id="input_db_<?= $idFila ?>"
                                        value='<?= htmlspecialchars($textoRepuestos, ENT_QUOTES, 'UTF-8') ?>'>
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

        <div class="fixed bottom-6 right-6 z-40">
            <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-4 px-6 rounded-full shadow-2xl flex items-center gap-2 transform hover:scale-105 transition duration-300 border-2 border-white">
                <i class="fas fa-save text-xl"></i>
                <span class="text-lg">GUARDAR CAMBIOS</span>
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
    // ==========================================
    // 1. CONFIGURACIÓN E INICIALIZACIÓN MAESTRA
    // ==========================================

    // Lista maestra de repuestos (desde PHP)
    const catalogoRepuestos = <?= json_encode($listaRepuestos ?? []) ?>;
    let repuestosTemporales = []; // Variable global para el modal

    $(document).ready(function() {
        console.log("Iniciando Sistema Completo...");

        // ---------------------------------------------
        // A. INICIALIZAR BUSCADOR DE CLIENTES (TABLA)
        // ---------------------------------------------
        $('.select2-cliente').select2({
            width: '100%',
            language: {
                noResults: () => "No encontrado"
            }
        });

        // ---------------------------------------------
        // B. INICIALIZAR BUSCADOR DE PUNTOS (TABLA)
        // ---------------------------------------------
        $('.select2-punto').select2({
            width: '100%',
            language: {
                noResults: () => "No encontrado"
            }
        });

        // ---------------------------------------------
        // C. LÓGICA MÁGICA: CARGA AUTOMÁTICA DE PUNTOS
        // ---------------------------------------------
        // Esto detecta cuando intentas abrir el select de puntos
        $(document).on('select2:opening', '.select2-punto', function(e) {
            let select = $(this);
            let idFila = select.attr('id').replace('sel_punto_', '');

            // Si ya cargamos los datos (data-loaded="true"), dejamos que se abra normal
            if (select.attr('data-loaded') === 'true') {
                return;
            }

            // Si NO están cargados:
            // 1. Detenemos la apertura para que no muestre "Cargando..." o una lista vacía
            e.preventDefault();

            // 2. Buscamos el cliente seleccionado en esa misma fila
            // Usamos .closest('tr') para encontrar la fila y luego buscar el cliente ahí
            let filaTR = select.closest('tr');
            let selectCliente = filaTR.find('.select2-cliente');
            let idCliente = selectCliente.val();

            if (idCliente) {
                // 3. Llamamos a la función de carga
                cargarPuntos(idFila, idCliente, true, function() {
                    // Callback: Cuando termine de cargar, abrimos el select automáticamente
                    select.select2('open');
                });
            } else {
                alert("⚠️ Por favor seleccione primero un cliente.");
            }
        });

        // ---------------------------------------------
        // D. INICIALIZAR MODAL DE REPUESTOS
        // ---------------------------------------------
        // Destruir instancia previa si existe
        if ($('#select_repuesto_modal').data('select2')) {
            $('#select_repuesto_modal').select2('destroy');
        }

        $('#select_repuesto_modal').select2({
            width: '100%',
            dropdownParent: $('#modalRepuestos'), // CRUCIAL PARA MODALES
            placeholder: "- Buscar Repuesto -",
            allowClear: true,
            language: {
                noResults: () => "No se encontró el repuesto"
            }
        });

        // Llenar el select del modal de repuestos
        const selectRep = document.getElementById('select_repuesto_modal');
        if (selectRep) {
            let html = '<option value="">- Buscar Repuesto -</option>';
            catalogoRepuestos.forEach(r => {
                html += `<option value="${r.id_repuesto}">${r.nombre_repuesto}</option>`;
            });
            selectRep.innerHTML = html;
        }

        // Corrección Z-Index para Select2
        $('head').append('<style>.select2-container--open { z-index: 99999999 !important; }</style>');

        // Ejecutar cálculos iniciales
        calcularDesplazamientos();
        iniciarPaginacion();
    });

    // Función para verificar si una cadena está vacía (reemplaza empty() de PHP)
    function isEmpty(str) {
        return (!str || str.trim() === '');
    }

    // Función para convertir texto plano a array de repuestos
    function convertirTextoARepuestos(texto) {
        const arrayTemp = [];
        if (isEmpty(texto)) return arrayTemp;

        const items = texto.split(',');
        const palabrasIgnorar = ['NO', 'NINGUNO', 'NINGUNA', 'SIN REPUESTOS', 'N/A', 'NA', '.', '-', '0', 'VACIO'];

        items.forEach(item => {
            const itemLimpio = item.trim();
            if (isEmpty(itemLimpio) || palabrasIgnorar.includes(itemLimpio.toUpperCase())) return;

            let origen = 'INEES';
            let nombre = itemLimpio;

            if (itemLimpio.toUpperCase().includes('(PROSEGUR)')) {
                origen = 'PROSEGUR';
                nombre = itemLimpio.replace(/\(PROSEGUR\)/gi, '').trim();
            } else if (itemLimpio.toUpperCase().includes('(INEES)')) {
                origen = 'INEES';
                nombre = itemLimpio.replace(/\(INEES\)/gi, '').trim();
            }

            // Buscar si el repuesto existe en el catálogo
            const repuestoEnCatalogo = catalogoRepuestos.find(r =>
                r.nombre_repuesto.toLowerCase() === nombre.toLowerCase() ||
                r.nombre_repuesto.toLowerCase().includes(nombre.toLowerCase())
            );

            arrayTemp.push({
                id: repuestoEnCatalogo ? repuestoEnCatalogo.id_repuesto : '',
                nombre: nombre,
                origen: origen
            });
        });

        return arrayTemp;
    }

    // Función para combinar arrays sin duplicados
    function combinarRepuestos(existentes, nuevos) {
        const combinados = [...existentes];

        nuevos.forEach(nuevo => {
            // Evitar duplicados por nombre
            const existe = combinados.some(existente =>
                existente.nombre.toLowerCase() === nuevo.nombre.toLowerCase() &&
                existente.origen === nuevo.origen
            );

            if (!existe) {
                combinados.push(nuevo);
            }
        });

        return combinados;
    }

    function abrirModalRepuestos(idFila) {
        console.log("Abriendo modal para fila:", idFila);

        // 1. Guardar qué fila estamos editando
        document.getElementById('modal_fila_actual').value = idFila;

        // 2. Recuperar de DOS FUENTES:
        const inputJson = document.getElementById(`input_json_${idFila}`);
        const inputDb = document.getElementById(`input_db_${idFila}`);

        let repuestosExistentes = [];
        let repuestosNuevos = [];

        // A. Cargar repuestos del JSON del modal
        try {
            repuestosNuevos = inputJson && inputJson.value ? JSON.parse(inputJson.value) : [];
        } catch (e) {
            console.error("Error parseando JSON del modal", e);
            repuestosNuevos = [];
        }

        // B. Cargar repuestos de la BD (texto plano)
        const textoBD = inputDb ? inputDb.value : '';
        if (textoBD && textoBD.trim() !== '') {
            try {
                // Intentar parsear como JSON primero
                repuestosExistentes = JSON.parse(textoBD);
            } catch (e) {
                // Si no es JSON válido, es texto plano
                repuestosExistentes = convertirTextoARepuestos(textoBD);
            }
        }

        // C. COMBINAR ambos arrays
        repuestosTemporales = combinarRepuestos(repuestosExistentes, repuestosNuevos);

        console.log("Repuestos combinados:", repuestosTemporales);

        // 3. Renderizar y Mostrar
        renderizarListaVisual();
        const modal = document.getElementById('modalRepuestos');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function borrarRepuestoTemporal(index) {
        if (confirm("¿Seguro que quieres eliminar este repuesto?")) {
            repuestosTemporales.splice(index, 1);
            renderizarListaVisual();
        }
    }

    function cerrarModal() {
        const modal = document.getElementById('modalRepuestos');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
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

    function renderizarListaVisual() {
        const ul = document.getElementById('lista_repuestos_visual');
        if (!ul) return;

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
        const inputJson = document.getElementById(`input_json_${idFila}`);
        const inputDb = document.getElementById(`input_db_${idFila}`);
        const btnTexto = document.getElementById(`btn_texto_${idFila}`);

        if (!inputJson || !inputDb || !btnTexto) {
            console.error("Elementos no encontrados para fila:", idFila);
            return;
        }

        // 1. Guardar el JSON actualizado
        inputJson.value = JSON.stringify(repuestosTemporales);

        // 2. También actualizar el input de texto para la BD
        const textoParaBD = repuestosTemporales.map(item => {
            if (item.origen === 'PROSEGUR') {
                return `${item.nombre} (PROSEGUR)`;
            } else {
                return item.nombre;
            }
        }).join(', ');

        inputDb.value = textoParaBD;

        // 3. Actualizar botón visual
        const button = btnTexto.parentElement;
        if (repuestosTemporales.length > 0) {
            btnTexto.innerText = `${repuestosTemporales.length} Items`;
            button.classList.add('bg-blue-100', 'border-blue-400');
        } else {
            btnTexto.innerText = "Gest. Repuestos";
            button.classList.remove('bg-blue-100', 'border-blue-400');
        }

        console.log("Guardados cambios para fila:", idFila, repuestosTemporales);
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
    // 4. FUNCIÓN CARGAR PUNTOS MEJORADA (Compatible con Select2)
    // ==========================================
    // ==========================================
    // 2. FUNCIÓN CARGAR PUNTOS (ARREGLADA)
    // ==========================================
    function cargarPuntos(idFila, idCliente, mantenerValorActual = false, callback = null) {
        let selPunto = $(`#sel_punto_${idFila}`); // Usamos jQuery para Select2
        let selMaq = document.getElementById(`sel_maq_${idFila}`);

        let valorPrevio = selPunto.val();

        // Si es cambio de cliente (no mantener), limpiamos visualmente
        if (!mantenerValorActual) {
            selPunto.html('<option>Cargando...</option>');
            selMaq.innerHTML = '<option>Esperando punto...</option>';
        }

        let fd = new FormData();
        fd.append('accion', 'ajaxObtenerPuntos');
        fd.append('id_cliente', idCliente);

        fetch('index.php?pagina=ordenDetalle', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                let options = '<option value="">- Seleccione -</option>';
                data.forEach(p => {
                    options += `<option value="${p.id_punto}" data-full="${p.nombre_punto}">${p.nombre_punto}</option>`;
                });

                // 1. Actualizamos el HTML del select original
                selPunto.html(options);

                // 2. Lógica de selección
                if (mantenerValorActual && valorPrevio) {
                    selPunto.val(valorPrevio); // Restaurar valor en el HTML
                    selPunto.attr('data-loaded', 'true'); // IMPORTANTE: Marcar como cargado para que el evento no se repita
                } else if (data.length > 0) {
                    // Si es cambio de cliente, seleccionar el primero
                    selPunto.val(data[0].id_punto);
                    cargarMaquinas(idFila, data[0].id_punto);
                }

                // 3. AVISAR A SELECT2 QUE EL CONTENIDO CAMBIÓ (CRUCIAL)
                selPunto.trigger('change.select2');

                // 4. Ejecutar callback (abrir el menú)
                if (callback) callback();
            })
            .catch(error => console.error("Error cargando puntos:", error));
    }

    // --- AGREGA ESTA NUEVA FUNCIÓN JUSTO DEBAJO ---
    function verificarCargaPuntos(idFila) {
        let selPunto = document.getElementById(`sel_punto_${idFila}`);

        // Si ya tiene el atributo data-loaded, no hacemos nada (ya cargó)
        if (selPunto.getAttribute('data-loaded') === 'true') return;

        // Obtenemos el cliente seleccionado en esa fila
        // Nota: Buscamos el select de cliente por su atributo name
        let selCliente = document.querySelector(`select[name="servicios[${idFila}][id_cliente]"]`);
        let idCliente = selCliente ? selCliente.value : null;

        if (idCliente) {
            console.log("Cargando puntos completos para fila " + idFila);
            // Llamamos a cargarPuntos indicando TRUE para mantener el valor actual
            cargarPuntos(idFila, idCliente, true);
        }
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
// 5. EXCEL LIMPIO (CORREGIDO: VALOR CON COMAS)
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
        // Ignorar filas sin ID (como la de "No hay datos")
        if (!fila.id.startsWith('fila_')) return;

        let idFila = fila.id.replace('fila_', '');

        // --- EXTRACCIÓN SEGURA POR SELECTORES (NO POR POSICIÓN) ---

        // 1. Identificadores y Textos Simples
        let inputRemision = fila.querySelector('input[name*="[remision]"]');
        let txtRemision = inputRemision ? inputRemision.value : "";

        let inputFecha = fila.querySelector('input[name*="[fecha_individual]"]');
        let txtFecha = inputFecha ? inputFecha.value : "";

        let txtObs = fila.querySelector('textarea[name*="[obs]"]');
        let obs = txtObs ? txtObs.value : "";

        // 2. Selects (Helper para sacar texto limpio)
        const getSelectText = (partialName) => {
            let sel = fila.querySelector(`select[name*="${partialName}"]`);
            if (!sel || sel.selectedIndex < 0) return "";
            // Preferir data-full si existe, sino el texto visible
            return sel.options[sel.selectedIndex].getAttribute('data-full') || sel.options[sel.selectedIndex].text.trim();
        };

        let cliente     = getSelectText('[id_cliente]');
        let punto       = getSelectText('[id_punto]');
        let tecnico     = getSelectText('[id_tecnico]');
        let servicio    = getSelectText('[id_manto]');
        let modalidad   = getSelectText('[id_modalidad]');
        let estado      = getSelectText('[id_estado]');
        let calif       = getSelectText('[id_calif]');

        // 3. Máquina (Limpieza de ID)
        let selMaq = fila.querySelector('select[name*="[id_maquina]"]');
        let device_id = "";
        let tipoMaquinatxt = "";

        if (selMaq && selMaq.selectedIndex >= 0) {
            let rawText = selMaq.options[selMaq.selectedIndex].text.trim();
            // Separar por paréntesis para obtener solo el ID: "12345 (Modelo)" -> "12345"
            device_id = rawText.split('(')[0].trim();
        }
        
        let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
        tipoMaquinatxt = divTipo ? divTipo.innerText : "";

        // 4. Delegación
        let divDelegacion = document.getElementById(`td_delegacion_${idFila}`);
        let delegacion = divDelegacion ? divDelegacion.innerText : "SIN ASIGNAR";

        // 5. Checkbox Preventivo/Correctivo
        let txtServicio = servicio.toLowerCase();
        let esPrevBasico   = txtServicio.includes('basico') || txtServicio.includes('básico') ? "X" : "";
        let esPrevProfundo = txtServicio.includes('profundo') || txtServicio.includes('completo') ? "X" : "";
        let esCorrectivo   = txtServicio.includes('correctivo') || txtServicio.includes('reparacion') ? "X" : "";
        
        if (!esPrevBasico && !esPrevProfundo && !esCorrectivo && txtServicio.includes('preventivo')) esPrevBasico = "X";

        // 6. Valor (RESTAURADO: PUNTOS A COMAS) ⭐⭐⭐
        let inputValor = fila.querySelector('input[name*="[valor]"]');
        let valorRaw = inputValor ? inputValor.value : "0";
        let valorExcel = "";
        
        if (valorRaw) {
            // Aquí está la lógica que pediste restaurar: 
            // Reemplaza todos los puntos (.) por comas (,)
            valorExcel = valorRaw.toString().replace(/\./g, ','); 
        }

        // 7. Horas y Desplazamiento
        let inputEntrada = fila.querySelector('input[name*="[entrada]"]');
        let inputSalida  = fila.querySelector('input[name*="[salida]"]');
        let horaEntrada  = inputEntrada ? inputEntrada.value : "";
        let horaSalida   = inputSalida ? inputSalida.value : "";
        let duracion     = calcularDuracion(horaEntrada, horaSalida); 

        let spanDesplaz = document.getElementById(`desplazamiento_${idFila}`);
        let desplazamiento = spanDesplaz ? spanDesplaz.innerText.replace('Err H.', '') : "";

        // 8. Repuestos
        let inputRepDB = document.getElementById(`input_db_${idFila}`);
        let repuestos = inputRepDB ? inputRepDB.value : "";
        
        // Limpieza de textos basura en repuestos
        if (repuestos.match(/Gest\. Repuestos|Items|sin repuestos|ninguno|n\/a|vacío/i)) {
            repuestos = "";
        }

        // --- OBJETO DE DATOS ---
        let datos = {
            device_id: device_id,
            remision: txtRemision,
            cliente: cliente,
            punto: punto,
            esPrevBasico: esPrevBasico,
            esPrevProfundo: esPrevProfundo,
            esCorrectivo: esCorrectivo,
            valor: valorExcel, // ✅ Valor con comas
            obs: obs,
            delegacion: delegacion,
            fecha: txtFecha,
            tecnico: tecnico,
            tipoMaquina: tipoMaquinatxt,
            servicio: servicio,
            horaEntrada: horaEntrada,
            horaSalida: horaSalida,
            duracion: duracion,
            desplazamiento: desplazamiento,
            repuestos: repuestos,
            estado: estado,
            calificacion: calif,
            modalidad: modalidad
        };

        // Agrupar
        if (!serviciosPorDelegacion[delegacion]) {
            serviciosPorDelegacion[delegacion] = [];
        }
        serviciosPorDelegacion[delegacion].push(datos);
    });

    // --- GENERAR EXCEL ---
    let workbook = XLSX.utils.book_new();
    let hayDatos = Object.keys(serviciosPorDelegacion).length > 0;

    if (!hayDatos) {
        alert("No hay datos válidos para exportar.");
        return;
    }

    for (let delegacion in serviciosPorDelegacion) {
        let lista = serviciosPorDelegacion[delegacion];
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
        ws['!cols'] = [
            { wch: 15 }, { wch: 12 }, { wch: 25 }, { wch: 25 },
            { wch: 8 }, { wch: 8 }, { wch: 8 },
            { wch: 12 }, { wch: 35 }, { wch: 15 }, { wch: 12 }, { wch: 20 },
            { wch: 15 }, { wch: 20 }, { wch: 10 }, { wch: 10 },
            { wch: 10 }, { wch: 12 }, { wch: 40 }, { wch: 15 },
            { wch: 15 }, { wch: 15 }
        ];

        let nombreHoja = delegacion.replace(/[:\\/?*\[\]]/g, "").substring(0, 30) || "General";
        XLSX.utils.book_append_sheet(workbook, ws, nombreHoja);
    }

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

    // ==========================================
    // 8. GESTIÓN DE NOVEDADES
    // ==========================================

    // Función para cambiar el estado visual y del input oculto
    function toggleNovedad(idFila) {
        let input = document.getElementById(`input_novedad_${idFila}`);
        let btn = document.getElementById(`btn_novedad_${idFila}`);

        // Convertimos a entero para verificar (por si viene como string "0")
        let estadoActual = parseInt(input.value);

        if (estadoActual === 0) {
            // ACTIVAR NOVEDAD
            input.value = 1;
            btn.classList.remove('bg-gray-100', 'border-gray-300', 'text-gray-300', 'hover:bg-gray-200');
            btn.classList.add('bg-red-500', 'border-red-700', 'text-white', 'animate-pulse'); // animate-pulse es opcional para efecto visual
            setTimeout(() => btn.classList.remove('animate-pulse'), 500); // Quitar pulso
        } else {
            // DESACTIVAR NOVEDAD
            input.value = 0;
            btn.classList.remove('bg-red-500', 'border-red-700', 'text-white');
            btn.classList.add('bg-gray-100', 'border-gray-300', 'text-gray-300', 'hover:bg-gray-200');
        }
    }

    // ==========================================
// 9. EXCEL DE NOVEDADES (REPARADO)
// ==========================================
function exportarExcelNovedades() {
    if (typeof XLSX === 'undefined') {
        alert("Librería SheetJS no cargada.");
        return;
    }

    let tabla = document.getElementById("tablaEdicion");
    let filas = Array.from(tabla.querySelectorAll('tbody tr'));
    let listaNovedades = [];

    filas.forEach((fila) => {
        // Ignorar filas que no sean de datos
        if (!fila.id.startsWith('fila_')) return;
        
        let idFila = fila.id.replace('fila_', '');

        // 1. Verificar si tiene el flag de Novedad activo (input hidden value="1")
        // Buscamos el input por su ID específico para ser precisos
        let inputNovedad = document.getElementById(`input_novedad_${idFila}`);
        if (!inputNovedad || inputNovedad.value != "1") return;

        // --- EXTRACCIÓN DE DATOS ---
        
        // Delegación
        let divDel = document.getElementById(`td_delegacion_${idFila}`);
        let delegacion = divDel ? divDel.innerText : "SIN ASIGNAR";

        // Cliente
        let selCli = fila.querySelector('select[name*="[id_cliente]"]');
        let cliente = selCli ? (selCli.options[selCli.selectedIndex].getAttribute('data-full') || selCli.options[selCli.selectedIndex].text) : "";

        // Punto
        let selPunto = fila.querySelector('select[name*="[id_punto]"]');
        let punto = selPunto ? (selPunto.options[selPunto.selectedIndex].getAttribute('data-full') || selPunto.options[selPunto.selectedIndex].text) : "";

        // Máquina (ID y Tipo)
        let selMaq = fila.querySelector('select[name*="[id_maquina]"]');
        let deviceID = "";
        if(selMaq && selMaq.selectedIndex >= 0) {
            deviceID = selMaq.options[selMaq.selectedIndex].text.split('(')[0].trim();
        }
        
        let divTipo = document.getElementById(`td_tipomaq_${idFila}`);
        let tipoMaq = divTipo ? divTipo.innerText : "";

        // Remisión (Aquí estaba el error frecuente, ahora busca por name parcial)
        let inputRem = fila.querySelector('input[name*="[remision]"]');
        let remision = inputRem ? inputRem.value : "";

        // Técnico
        let selTec = fila.querySelector('select[name*="[id_tecnico]"]');
        let tecnico = selTec ? selTec.options[selTec.selectedIndex].text : "";

        // Observación
        let txtObs = fila.querySelector('textarea[name*="[obs]"]');
        let obs = txtObs ? txtObs.value : "";
        
        // Fecha Individual
        let inputFecha = fila.querySelector('input[name*="[fecha_individual]"]');
        let fecha = inputFecha ? inputFecha.value : "";

        listaNovedades.push({
            "Delegación": delegacion,
            "Cliente": cliente,
            "Punto": punto,
            "Device ID": deviceID,
            "Tipo Máquina": tipoMaq,
            "Remisión": remision,
            "Técnico": tecnico,
            "Observación": obs,
            "Fecha": fecha
        });
    });

    if (listaNovedades.length === 0) {
        alert("¡Excelente! No hay novedades marcadas para generar reporte.");
        return;
    }

    // GENERAR EXCEL
    let wb = XLSX.utils.book_new();
    let ws = XLSX.utils.json_to_sheet(listaNovedades);

    // Ajustar anchos
    ws['!cols'] = [
        { wch: 15 }, // Delegación
        { wch: 25 }, // Cliente
        { wch: 25 }, // Punto
        { wch: 15 }, // Device ID
        { wch: 15 }, // Tipo
        { wch: 12 }, // Remisión
        { wch: 20 }, // Técnico
        { wch: 50 }, // Observación
        { wch: 12 }  // Fecha
    ];

    XLSX.utils.book_append_sheet(wb, ws, "Novedades");
        XLSX.writeFile(wb, `Novedades_${"<?= $_GET['fecha'] ?>"}.xlsx`);
}
</script>