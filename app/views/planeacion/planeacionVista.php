<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>

<style>
    .select2-container .select2-selection--single {
        height: 3rem !important;
        padding: 0.5rem !important;
        border-color: #d1d5db !important;
        border-radius: 0.5rem !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        right: 10px !important;
    }
    body { background-color: #f1f5f9; }

    /* ============================================================
       COLOR POR ESTADO -> resuelto aquí en la vista (no en la BD)
       frappe-gantt inyecta la clase custom_class en el <g class="bar-wrapper ...">
       ============================================================ */
    .bar-wrapper.estado-activo .bar        { fill: #22c55e !important; }
    .bar-wrapper.estado-aplazado .bar      { fill: #eab308 !important; }
    .bar-wrapper.estado-cancelado .bar     { fill: #ef4444 !important; }
    .bar-wrapper.estado-finalizado .bar    { fill: #3b82f6 !important; }
    .bar-wrapper.estado-sin-estado .bar    { fill: #94a3b8 !important; }

    /* Mismo mapa de colores para los badges de la lista lateral y modales */
    .badge-estado-activo      { background:#dcfce7; color:#166534; }
    .badge-estado-aplazado    { background:#fef9c3; color:#854d0e; }
    .badge-estado-cancelado   { background:#fee2e2; color:#991b1b; }
    .badge-estado-finalizado  { background:#dbeafe; color:#1e40af; }
    .badge-estado-sin-estado  { background:#f1f5f9; color:#475569; }
</style>

<div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <button onclick="window.history.back();"
            class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition">
            <i class="fas fa-arrow-left"></i>
        </button>
        <div>
            <h1 class="font-bold text-lg leading-tight">Planeación Estratégica</h1>
            <p class="text-blue-200 text-xs">Gantt de estrategias y tareas</p>
        </div>
    </div>
    <button onclick="abrirModalEstrategia()"
        class="bg-white text-blue-800 font-bold px-3 py-2 rounded-lg text-sm flex items-center gap-1 shadow hover:bg-blue-50">
        <i class="fas fa-plus"></i> Nueva estrategia
    </button>
</div>

<div class="max-w-6xl mx-auto p-3 space-y-4 mt-2">

    <!-- Barra de escala de tiempo -->
    <div class="flex gap-2">
        <button data-view="Day"   class="btn-escala px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm">Día</button>
        <button data-view="Week"  class="btn-escala px-4 py-2 rounded-lg border border-gray-300 bg-blue-800 text-white text-sm">Semana</button>
        <button data-view="Month" class="btn-escala px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm">Mes</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 overflow-x-auto">
        <svg id="gantt-chart"></svg>
        <p id="gantt-vacio" class="hidden text-center text-gray-400 py-10">
            Aún no hay estrategias registradas. Crea la primera con el botón "Nueva estrategia".
        </p>
    </div>
</div>

<!-- ================== MODAL: NUEVA ESTRATEGIA ================== -->
<div id="modalEstrategia" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-5 space-y-4">
        <div class="flex justify-between items-center border-b pb-2">
            <h2 class="font-bold text-gray-700">Nueva Estrategia</h2>
            <button onclick="cerrarModalEstrategia()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>

        <form id="formEstrategia" class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="nombre_estrategia" class="w-full border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Responsable</label>
                <select name="id_responsable" class="w-full border-gray-300 rounded-lg select2-modal">
                    <option value="">- Sin asignar -</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= htmlspecialchars($u['usuario_id']) ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado <span class="text-red-500">*</span></label>
                <select name="id_estado" class="w-full border-gray-300 rounded-lg select2-modal" required>
                    <option value="">- Seleccione -</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= htmlspecialchars($e['id_estado']) ?>"><?= htmlspecialchars($e['nombre_estado']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha inicial <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_inicial" class="w-full border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha final <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_final" class="w-full border-gray-300 rounded-lg" required>
                </div>
            </div>
            <button type="button" onclick="guardarEstrategia()"
                class="w-full bg-blue-800 hover:bg-blue-900 text-white font-bold py-3 rounded-lg mt-2">
                Guardar estrategia
            </button>
        </form>
    </div>
</div>

<!-- ================== MODAL: NUEVA TAREA / SUBTAREA ================== -->
<div id="modalTarea" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-5 space-y-4">
        <div class="flex justify-between items-center border-b pb-2">
            <h2 class="font-bold text-gray-700" id="tituloModalTarea">Nueva Tarea</h2>
            <button onclick="cerrarModalTarea()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>

        <form id="formTarea" class="space-y-3">
            <input type="hidden" name="id_estrategia" id="tarea_id_estrategia">
            <input type="hidden" name="id_tarea_padre" id="tarea_id_tarea_padre">

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="nombre_tarea" class="w-full border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Responsable</label>
                <select name="id_responsable" class="w-full border-gray-300 rounded-lg select2-modal">
                    <option value="">- Sin asignar -</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= htmlspecialchars($u['usuario_id']) ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado <span class="text-red-500">*</span></label>
                <select name="id_estado" class="w-full border-gray-300 rounded-lg select2-modal" required>
                    <option value="">- Seleccione -</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= htmlspecialchars($e['id_estado']) ?>"><?= htmlspecialchars($e['nombre_estado']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha inicial <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_inicial" class="w-full border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha final <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_final" class="w-full border-gray-300 rounded-lg" required>
                </div>
            </div>
            <button type="button" onclick="guardarTarea()"
                class="w-full bg-blue-800 hover:bg-blue-900 text-white font-bold py-3 rounded-lg mt-2">
                Guardar tarea
            </button>
        </form>
    </div>
</div>

<!-- ================== MODAL: DETALLE DE BARRA ================== -->
<div id="modalDetalle" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-sm p-5 space-y-3">
        <div class="flex justify-between items-center border-b pb-2">
            <h2 class="font-bold text-gray-700" id="detalle_nombre">-</h2>
            <button onclick="cerrarModalDetalle()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm"><strong class="text-gray-600">Responsable:</strong> <span id="detalle_responsable">-</span></p>
        <p class="text-sm flex items-center gap-2">
            <strong class="text-gray-600">Estado:</strong>
            <span id="detalle_estado_badge" class="text-xs font-bold px-2 py-0.5 rounded-full">-</span>
        </p>
        <p class="text-sm"><strong class="text-gray-600">Avance:</strong> <span id="detalle_avance">0</span>%</p>

        <button id="btn_agregar_subtarea" onclick="abrirModalSubtareaDesdeDetalle()"
            class="hidden w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 rounded-lg text-sm mt-2">
            <i class="fas fa-plus"></i> Agregar subtarea
        </button>
    </div>
</div>

<script>
    // Mapa de nombre de estado -> clase de color (misma idea de $mapaColores, resuelta en el cliente)
    const MAPA_CLASE_ESTADO = {
        'Activo': 'activo',
        'Aplazado': 'aplazado',
        'Cancelado': 'cancelado',
        'Finalizado': 'finalizado'
    };
    function claseEstado(nombreEstado) {
        return MAPA_CLASE_ESTADO[nombreEstado] || 'sin-estado';
    }

    let ganttChart = null;
    let filasGanttActuales = [];

    $(document).ready(function () {
        $('.select2-modal').select2({
            width: '100%',
            dropdownParent: $('body'),
            language: { noResults: function () { return "No se encontraron resultados"; } }
        });

        cargarGantt();

        $('.btn-escala').on('click', function () {
            $('.btn-escala').removeClass('bg-blue-800 text-white').addClass('bg-white');
            $(this).removeClass('bg-white').addClass('bg-blue-800 text-white');
            if (ganttChart) ganttChart.change_view_mode($(this).data('view'));
        });
    });

    function cargarGantt() {
        $.getJSON('index.php?pagina=planeacion&accion=datosGantt', function (filas) {
            filasGanttActuales = filas;

            if (!filas.length) {
                $('#gantt-chart').hide();
                $('#gantt-vacio').removeClass('hidden');
                return;
            }
            $('#gantt-chart').show();
            $('#gantt-vacio').addClass('hidden');

            // Le agregamos la custom_class con el color resuelto aquí en la vista
            const filasConClase = filas.map(f => ({
                ...f,
                custom_class: 'estado-' + claseEstado(f.estado)
            }));

            ganttChart = new Gantt("#gantt-chart", filasConClase, {
                view_mode: 'Week',
                language: 'es',
                on_click: function (barra) { mostrarDetalle(barra); },
                on_date_change: function (barra, inicio, fin) {
                    actualizarFechas(barra.id, inicio, fin);
                },
                on_progress_change: function (barra, progreso) {
                    actualizarAvance(barra.id, progreso);
                }
            });
        });
    }

    // ------------------- Detalle de una barra -------------------
    function mostrarDetalle(barra) {
        $('#detalle_nombre').text(barra.name);
        $('#detalle_responsable').text(barra.responsable || 'Sin asignar');
        $('#detalle_avance').text(barra.progress || 0);

        const clase = claseEstado(barra.estado);
        $('#detalle_estado_badge')
            .attr('class', 'text-xs font-bold px-2 py-0.5 rounded-full badge-estado-' + clase)
            .text(barra.estado || 'Sin estado');

        // Solo permitimos agregar subtarea si la barra clickeada es una estrategia o una tarea
        $('#btn_agregar_subtarea').data('idOrigen', barra.id).removeClass('hidden');

        $('#modalDetalle').removeClass('hidden');
    }
    function cerrarModalDetalle() { $('#modalDetalle').addClass('hidden'); }

    function abrirModalSubtareaDesdeDetalle() {
        const idOrigen = $('#btn_agregar_subtarea').data('idOrigen');
        cerrarModalDetalle();

        if (idOrigen.startsWith('estrategia_')) {
            const idEstrategia = idOrigen.replace('estrategia_', '');
            abrirModalTarea(idEstrategia, null);
        } else {
            const idTareaPadre = idOrigen.replace('tarea_', '');
            const fila = filasGanttActuales.find(f => f.id === idOrigen);
            // Buscamos a qué estrategia pertenece subiendo por dependencies (simplificado: recargamos y preguntamos al backend sería más robusto)
            const idEstrategiaRaiz = obtenerIdEstrategiaDeTarea(idOrigen);
            abrirModalTarea(idEstrategiaRaiz, idTareaPadre);
        }
    }

    function obtenerIdEstrategiaDeTarea(idGantt) {
        let actual = filasGanttActuales.find(f => f.id === idGantt);
        while (actual && actual.tipo === 'tarea') {
            actual = filasGanttActuales.find(f => f.id === actual.dependencies);
        }
        return actual ? actual.id.replace('estrategia_', '') : null;
    }

    // ------------------- Modal: Nueva Estrategia -------------------
    function abrirModalEstrategia() {
        $('#formEstrategia')[0].reset();
        $('.select2-modal').val('').trigger('change.select2');
        $('#modalEstrategia').removeClass('hidden');
    }
    function cerrarModalEstrategia() { $('#modalEstrategia').addClass('hidden'); }

    function guardarEstrategia() {
        const datos = $('#formEstrategia').serialize();

        if (!$('#formEstrategia')[0].checkValidity()) {
            $('#formEstrategia')[0].reportValidity();
            return;
        }

        $.post('index.php?pagina=planeacion&accion=crearEstrategia', datos, function (respuesta) {
            if (respuesta.status === 'success') {
                cerrarModalEstrategia();
                cargarGantt();
            } else {
                alert('⚠️ ' + (respuesta.message || 'No se pudo guardar.'));
            }
        }, 'json');
    }

    // ------------------- Modal: Nueva Tarea / Subtarea -------------------
    function abrirModalTarea(idEstrategia, idTareaPadre) {
        $('#formTarea')[0].reset();
        $('.select2-modal').val('').trigger('change.select2');
        $('#tarea_id_estrategia').val(idEstrategia);
        $('#tarea_id_tarea_padre').val(idTareaPadre || '');
        $('#tituloModalTarea').text(idTareaPadre ? 'Nueva Subtarea' : 'Nueva Tarea');
        $('#modalTarea').removeClass('hidden');
    }
    function cerrarModalTarea() { $('#modalTarea').addClass('hidden'); }

    function guardarTarea() {
        const datos = $('#formTarea').serialize();

        if (!$('#formTarea')[0].checkValidity()) {
            $('#formTarea')[0].reportValidity();
            return;
        }

        $.post('index.php?pagina=planeacion&accion=crearTarea', datos, function (respuesta) {
            if (respuesta.status === 'success') {
                cerrarModalTarea();
                cargarGantt();
            } else {
                alert('⚠️ ' + (respuesta.message || 'No se pudo guardar.'));
            }
        }, 'json');
    }

    // ------------------- Persistencia al interactuar con el Gantt -------------------
    function actualizarFechas(idGantt, fechaInicio, fechaFin) {
        const { tipo, id } = separarIdGantt(idGantt);
        $.post('index.php?pagina=planeacion&accion=actualizarFechas', {
            tipo: tipo,
            id: id,
            fecha_inicial: formatearFecha(fechaInicio),
            fecha_final: formatearFecha(fechaFin)
        });
    }

    function actualizarAvance(idGantt, progreso) {
        const { tipo, id } = separarIdGantt(idGantt);
        $.post('index.php?pagina=planeacion&accion=actualizarAvance', {
            tipo: tipo,
            id: id,
            avance_pct: progreso
        });
    }

    function separarIdGantt(idGantt) {
        if (idGantt.startsWith('estrategia_')) {
            return { tipo: 'estrategia', id: idGantt.replace('estrategia_', '') };
        }
        return { tipo: 'tarea', id: idGantt.replace('tarea_', '') };
    }

    function formatearFecha(fecha) {
        const d = new Date(fecha);
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    }
</script>