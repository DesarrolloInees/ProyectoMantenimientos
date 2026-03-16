<!-- ═══════════════════════════════════════════════════════════════
     VISTA: tecnicoProgramacionVista.php
     Descripción: Programación diaria del técnico logueado
     ═══════════════════════════════════════════════════════════════ -->

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
    /* ── Variables de color ── */
    :root {
        --azul-oscuro: #1e3a5f;
        --azul-medio: #2563eb;
        --azul-claro: #dbeafe;
        --verde: #16a34a;
        --verde-claro: #dcfce7;
        --gris-fondo: #f8fafc;
        --gris-borde: #e2e8f0;
        --texto-oscuro: #1e293b;
        --texto-suave: #64748b;
    }

    /* ── Contenedor principal ── */
    .prog-wrapper {
        background: var(--gris-fondo);
        padding: 1.5rem;
        border-radius: 12px;
    }

    /* ── Header con gradiente ── */
    .prog-header {
        background: linear-gradient(135deg, var(--azul-oscuro) 0%, var(--azul-medio) 100%);
        padding: 1.25rem 1.5rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        box-shadow: 0 4px 15px rgba(30, 58, 95, 0.25);
    }

    .prog-header h2 {
        color: #ffffff;
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ── Selector de fecha ── */
    .fecha-container {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .fecha-container label {
        color: #bfdbfe;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    #fechaFiltro {
        background: rgba(255, 255, 255, 0.95);
        border: none;
        border-radius: 6px;
        padding: 0.45rem 0.75rem;
        font-weight: 700;
        color: var(--texto-oscuro);
        font-size: 0.9rem;
        cursor: pointer;
        transition: box-shadow 0.2s;
    }

    #fechaFiltro:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.6);
    }

    /* ── Tarjetas de resumen ── */
    .stats-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .stat-card {
        flex: 1;
        min-width: 140px;
        background: #ffffff;
        border-radius: 10px;
        padding: 1rem 1.25rem;
        border-left: 4px solid var(--azul-medio);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .stat-card.verde {
        border-left-color: var(--verde);
    }

    .stat-card.naranja {
        border-left-color: #f97316;
    }

    .stat-card .stat-numero {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--texto-oscuro);
        line-height: 1;
    }

    .stat-card .stat-label {
        font-size: 0.75rem;
        color: var(--texto-suave);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* ── Tabla ── */
    .tabla-container {
        background: #ffffff;
        border-radius: 10px;
        padding: 1.25rem;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
        overflow: hidden;
    }

    #tablaProgramacion {
        width: 100% !important;
        font-size: 0.875rem;
        color: var(--texto-oscuro);
    }

    #tablaProgramacion thead th {
        background: var(--azul-oscuro) !important;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.85rem 1rem !important;
        border: none !important;
        white-space: nowrap;
    }

    #tablaProgramacion tbody tr {
        transition: background 0.15s;
        border-bottom: 1px solid var(--gris-borde) !important;
    }

    #tablaProgramacion tbody tr:hover {
        background: #eff6ff !important;
    }

    #tablaProgramacion tbody td {
        padding: 0.85rem 1rem !important;
        vertical-align: middle;
        border: none !important;
    }

    /* ── Badges ── */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        font-size: 0.73rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-azul {
        background: var(--azul-claro);
        color: #1d4ed8;
    }

    .badge-verde {
        background: var(--verde-claro);
        color: #15803d;
    }

    .badge-gris {
        background: #f1f5f9;
        color: var(--texto-suave);
    }

    /* ── Info de máquina ── */
    .maquina-info strong {
        display: block;
        font-weight: 700;
        color: var(--texto-oscuro);
    }

    .maquina-info small {
        color: var(--texto-suave);
        font-size: 0.75rem;
    }

    /* ── Cliente info ── */
    .cliente-info strong {
        display: block;
        font-weight: 700;
    }

    .cliente-info small {
        color: var(--texto-suave);
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-top: 0.1rem;
    }

    /* ── Botón Atender ── */
    .btn-atender {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: linear-gradient(135deg, #16a34a, #15803d);
        color: #ffffff;
        font-weight: 700;
        font-size: 0.8rem;
        padding: 0.5rem 1rem;
        border-radius: 7px;
        border: none;
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3);
    }

    .btn-atender:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.4);
    }

    .btn-atender:active {
        transform: translateY(0);
    }

    /* ── Estado vacío personalizado ── */
    .estado-vacio {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--texto-suave);
    }

    .estado-vacio i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
        display: block;
    }

    .estado-vacio p {
        font-size: 0.95rem;
        margin: 0;
    }

    /* ── Alert de error/debug ── */
    .alert-debug {
        background: #fef3c7;
        border: 1px solid #fbbf24;
        border-radius: 8px;
        padding: 0.85rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        color: #92400e;
        display: none;
    }

    /* ── Loading ── */
    .loading-overlay {
        display: none;
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.75);
        border-radius: 10px;
        justify-content: center;
        align-items: center;
        z-index: 10;
    }

    .loading-overlay.activo {
        display: flex;
    }

    .spinner {
        width: 36px;
        height: 36px;
        border: 4px solid var(--azul-claro);
        border-top-color: var(--azul-medio);
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* ── Responsive ── */
    @media (max-width: 640px) {
        .prog-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .stats-row {
            gap: 0.75rem;
        }

        .stat-card {
            min-width: 120px;
        }
    }

    /* ── Override DataTables ── */
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid var(--gris-borde);
        border-radius: 6px;
        padding: 0.35rem 0.65rem;
        font-size: 0.85rem;
        outline: none;
        transition: border-color 0.2s;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: var(--azul-medio);
    }

    .dataTables_wrapper .dataTables_length select {
        border: 1px solid var(--gris-borde);
        border-radius: 6px;
        padding: 0.3rem 0.5rem;
        font-size: 0.85rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--azul-medio) !important;
        color: #fff !important;
        border-radius: 6px;
        border: none !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--azul-claro) !important;
        color: var(--azul-oscuro) !important;
        border: none !important;
        border-radius: 6px;
    }


    /* ── Modal Nuevo Servicio ── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.7);
        z-index: 50;
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.activo {
        display: flex;
    }

    .modal-content {
        background: #fff;
        width: 100%;
        max-width: 500px;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--gris-borde);
        padding-bottom: 1rem;
        margin-bottom: 1rem;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        color: var(--azul-oscuro);
    }

    .btn-cerrar {
        background: none;
        border: none;
        font-size: 1.25rem;
        color: var(--texto-suave);
        cursor: pointer;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--texto-oscuro);
        margin-bottom: 0.3rem;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid var(--gris-borde);
        border-radius: 6px;
        font-size: 0.9rem;
        outline: none;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: var(--azul-medio);
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        border-top: 1px solid var(--gris-borde);
        padding-top: 1rem;
        margin-top: 1.5rem;
    }

    .btn-cancelar {
        background: #f1f5f9;
        color: var(--texto-oscuro);
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-guardar {
        background: var(--azul-medio);
        color: #fff;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
    }

    /* ── Ajustes para Select2 ── */
    .select2-container {
        width: 100% !important;
    }
    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid var(--gris-borde);
        border-radius: 6px;
        outline: none;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        color: var(--texto-oscuro);
        font-size: 0.9rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--azul-medio);
    }
</style>

<!-- ══════════════════════════ HTML ══════════════════════════ -->
<div class="prog-wrapper">

    <!-- Header -->
    <div class="prog-header">
        <h2>
            <i class="fas fa-calendar-check"></i>
            Mis Servicios del Día
        </h2>
        <div style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="fecha-container">
                <label for="fechaFiltro">Fecha de consulta</label>
                <input type="date" id="fechaFiltro">
            </div>
            <button onclick="abrirModalNuevoServicio()" style="background: #10b981; color: white; border: none; padding: 0.45rem 1rem; border-radius: 6px; font-weight: 700; cursor: pointer; height: fit-content;">
                <i class="fas fa-plus"></i> Agregar Extra
            </button>
        </div>
    </div>


    <!-- Alert de debug (solo aparece si hay error de sesión) -->
    <div class="alert-debug" id="alertDebug">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <span id="alertDebugTexto"></span>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-numero" id="statTotal">—</span>
            <span class="stat-label">Total servicios</span>
        </div>
        <div class="stat-card verde">
            <span class="stat-numero" id="statFecha">—</span>
            <span class="stat-label">Fecha seleccionada</span>
        </div>
        <div class="stat-card naranja">
            <span class="stat-numero" id="statPuntos">—</span>
            <span class="stat-label">Puntos distintos</span>
        </div>
    </div>

    <!-- Tabla -->
    <div class="tabla-container" style="position: relative;">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
        </div>

        <table id="tablaProgramacion" class="display responsive nowrap w-full">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Punto / Dirección</th>
                    <th>Máquina</th>
                    <th>Tipo Mantenimiento</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="modal-overlay" id="modalExtra">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle text-green-600"></i> Agendar Servicio Extra</h3>
            <button class="btn-cerrar" onclick="cerrarModalNuevoServicio()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="form-group">
            <label>Fecha de Visita</label>
            <input type="date" id="extraFecha" class="form-control">
        </div>

        <div class="form-group">
            <label>1. Cliente</label>
            <select id="extraCliente" class="form-control">
                <option value="">Cargando clientes...</option>
            </select>
        </div>

        <div class="form-group">
            <label>2. Punto</label>
            <select id="extraPunto" class="form-control" disabled>
                <option value="">Seleccione primero un cliente</option>
            </select>
        </div>

        <div class="form-group">
            <label>3. Máquina</label>
            <select id="extraMaquina" class="form-control" disabled>
                <option value="">Seleccione primero un punto</option>
            </select>
        </div>

        <div class="form-group">
            <label>4. Tipo de Mantenimiento</label>
            <select id="extraMantenimiento" class="form-control">
                <option value="">Cargando mantenimientos...</option>
            </select>
        </div>

        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModalNuevoServicio()">Cancelar</button>
            <button class="btn-guardar" onclick="guardarServicioExtra()">Agendar Servicio</button>
        </div>
    </div>
</div>

</div>

<!-- ══════════════════════════ SCRIPTS ══════════════════════════ -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {

        // ── Setear fecha ANTES de que DataTable haga su primera petición ──
        var hoy = new Date();
        var fechaHoy = hoy.getFullYear() + '-' +
            String(hoy.getMonth() + 1).padStart(2, '0') + '-' +
            String(hoy.getDate()).padStart(2, '0');
        $('#fechaFiltro').val(fechaHoy);
        // ── Inicializar Select2 ────────────────────────────────────────
        $('#extraCliente, #extraPunto, #extraMaquina, #extraMantenimiento').select2({
            dropdownParent: $('#modalExtra') // Fundamental para modales custom o Bootstrap
        });

        // ── Inicializar DataTable ──────────────────────────────────────
        var tabla = $('#tablaProgramacion').DataTable({
            responsive: true,
            processing: false, // Usamos nuestro propio loader
            serverSide: false,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                emptyTable: '<div class="estado-vacio"><i class="fas fa-calendar-times"></i><p>No hay servicios programados para esta fecha.</p></div>',
                zeroRecords: '<div class="estado-vacio"><i class="fas fa-search"></i><p>No se encontraron resultados con ese filtro.</p></div>'
            },
            ajax: {
                url: 'index.php?pagina=tecnicoProgramacion&accion=ajaxObtenerProgramacion',
                type: 'POST',
                data: function(d) {
                    d.fecha = $('#fechaFiltro').val();
                },
                beforeSend: function() {
                    $('#loadingOverlay').addClass('activo');
                    $('#alertDebug').hide();
                },
                dataSrc: function(respuesta) {
                    $('#loadingOverlay').removeClass('activo');

                    // Si el servidor devuelve un error de sesión, lo mostramos
                    if (respuesta.error) {
                        $('#alertDebugTexto').text(respuesta.error +
                            (respuesta.debug ? ' | ' + respuesta.debug : ''));
                        $('#alertDebug').show();
                    }

                    var datos = respuesta.data || [];

                    // Actualizar tarjetas de resumen
                    actualizarStats(datos);

                    return datos;
                },
                error: function(xhr, error, thrown) {
                    $('#loadingOverlay').removeClass('activo');
                    $('#alertDebugTexto').text('Error de comunicación con el servidor: ' + thrown);
                    $('#alertDebug').show();
                    return [];
                }
            },
            columns: [
                {
                    // 1. Cliente
                    data: 'nombre_cliente',
                    render: function(data, type, row) {
                        var cliente = data ? data : '<span class="text-gray-400">Sin cliente</span>';
                        return '<div class="cliente-info"><strong>' + cliente + '</strong></div>';
                    }
                },
                {
                    // 2. Punto / Dirección
                    data: 'nombre_punto',
                    render: function(data, type, row) {
                        var punto = data ? data : 'Sin punto';
                        var direccion = row.direccion_punto ? row.direccion_punto : '';
                        var dir = direccion ? '<small><i class="fas fa-map-marker-alt"></i> ' + direccion + '</small>' : '';
                        return '<div class="cliente-info"><strong>' + punto + '</strong>' + dir + '</div>';
                    }
                },
                {
                    // 3. Máquina (Esta la habíamos borrado por error)
                    data: null,
                    render: function(data, type, row) {
                        var tipo = row.nombre_tipo_maquina ? row.nombre_tipo_maquina : 'N/A';
                        var device = row.device_id ? '<small>Device ID: ' + row.device_id + '</small>' : '<small>Sin Device ID</small>';
                        return '<div class="maquina-info"><strong>' + tipo + '</strong>' + device + '</div>';
                    }
                },
                {
                    // 4. Tipo Mantenimiento
                    data: 'tipo_mantenimiento',
                    render: function(data) {
                        if (!data) return '<span class="badge badge-gris">Sin definir</span>';
                        return '<span class="badge badge-azul"><i class="fas fa-tools"></i> ' + data + '</span>';
                    }
                },
                {
                    // 5. Acción (Aquí van los botones de Atender y Borrar)
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: function(data, type, row) {
                        return '<div style="display:flex; gap:0.5rem; justify-content:center;">' +
                            '<button class="btn-atender" onclick="abrirReporteMovil(' + row.id_ordenes_servicio + ')">' +
                            '<i class="fas fa-clipboard-check"></i> Atender</button>' +
                            '<button onclick="eliminarServicio(' + row.id_ordenes_servicio + ')" style="background:#dc2626; color:white; border:none; padding:0.5rem 0.75rem; border-radius:7px; cursor:pointer;" title="Cancelar servicio">' +
                            '<i class="fas fa-trash-alt"></i></button>' +
                            '</div>';
                    }
                }
            ],
            order: [
                [0, 'asc']
            ],
            pageLength: 25
        });

        // ── Recargar al cambiar fecha ──────────────────────────────────
        $('#fechaFiltro').on('change', function() {
            tabla.ajax.reload();
        });

        // ── Actualizar tarjetas de resumen ─────────────────────────────
        function actualizarStats(datos) {
            var fecha = $('#fechaFiltro').val();

            // Formatear fecha para mostrar
            var partes = fecha.split('-');
            var fechaFormateada = partes[2] + '/' + partes[1] + '/' + partes[0];

            // Contar puntos únicos
            var puntosUnicos = [...new Set(datos.map(function(r) {
                return r.nombre_punto;
            }))].length;

            $('#statTotal').text(datos.length);
            $('#statFecha').text(fechaFormateada);
            $('#statPuntos').text(puntosUnicos);
        }
    });

    // ── Función para eliminar un servicio ────────────────────────────
    function eliminarServicio(idOrden) {
        if (confirm("¿Estás seguro de que Prosegur canceló este servicio? Esta acción lo borrará de tu programación de forma definitiva.")) {
            $('#loadingOverlay').addClass('activo');

            $.ajax({
                url: 'index.php?pagina=tecnicoProgramacion&accion=ajaxEliminarServicio',
                type: 'POST',
                data: {
                    id_orden: idOrden
                },
                dataType: 'json',
                success: function(res) {
                    $('#loadingOverlay').removeClass('activo');
                    if (res.success) {
                        // Recargar el datatable para reflejar el borrado
                        $('#tablaProgramacion').DataTable().ajax.reload(null, false);
                    } else {
                        alert("Error: " + res.msj);
                    }
                },
                error: function() {
                    $('#loadingOverlay').removeClass('activo');
                    alert("Error de comunicación con el servidor al intentar eliminar.");
                }
            });
        }
    }


    // ── LÓGICA DEL MODAL DE SERVICIO EXTRA ────────────────────────────
    
    function abrirModalNuevoServicio() {
        $('#extraFecha').val($('#fechaFiltro').val()); 
        $('#modalExtra').addClass('activo');
        
        if($('#extraCliente option').length <= 1) {
            cargarClientes();
            cargarMantenimientos();
        }
    }

    function cerrarModalNuevoServicio() {
        $('#modalExtra').removeClass('activo');
        
        // Resetear selects y avisarle a Select2
        $('#extraCliente').val('').trigger('change');
        $('#extraPunto').html('<option value="">Seleccione primero un cliente</option>').prop('disabled', true).trigger('change');
        $('#extraMaquina').html('<option value="">Seleccione primero un punto</option>').prop('disabled', true).trigger('change');
        $('#extraMantenimiento').val('').trigger('change');
    }

    function cargarClientes() {
        $.post('index.php?pagina=tecnicoProgramacion&accion=ajaxObtenerClientes', function(data) {
            let html = '<option value="">-- Seleccione Cliente --</option>';
            data.forEach(c => { html += `<option value="${c.id_cliente}">${c.nombre_cliente}</option>`; });
            $('#extraCliente').html(html).trigger('change'); // <- Avisar a Select2
        });
    }

    function cargarMantenimientos() {
        $.post('index.php?pagina=tecnicoProgramacion&accion=ajaxObtenerTiposMantenimiento', function(data) {
            let html = '<option value="">-- Seleccione Tipo --</option>';
            data.forEach(t => { html += `<option value="${t.id_tipo_mantenimiento}">${t.nombre_completo}</option>`; });
            $('#extraMantenimiento').html(html).trigger('change'); // <- Avisar a Select2
        });
    }

    // Cascada Cliente -> Punto
    $('#extraCliente').on('change', function() {
        let id_cliente = $(this).val();
        
        if(!id_cliente) {
            $('#extraPunto').html('<option value="">Seleccione primero un cliente</option>').prop('disabled', true).trigger('change');
            $('#extraMaquina').html('<option value="">Seleccione primero un punto</option>').prop('disabled', true).trigger('change');
            return;
        }

        $('#extraPunto').html('<option value="">Cargando puntos...</option>').prop('disabled', false).trigger('change');
        
        $.post('index.php?pagina=tecnicoProgramacion&accion=ajaxObtenerPuntos', {id_cliente: id_cliente}, function(data) {
            let html = '<option value="">-- Seleccione Punto --</option>';
            data.forEach(p => { html += `<option value="${p.id_punto}">${p.nombre_punto} (${p.direccion || 'Sin dir'})</option>`; });
            $('#extraPunto').html(html).trigger('change'); // <- Avisar a Select2
        });
    });

    // Cascada Punto -> Máquina
    $('#extraPunto').on('change', function() {
        let id_punto = $(this).val();
        
        if(!id_punto) {
            $('#extraMaquina').html('<option value="">Seleccione primero un punto</option>').prop('disabled', true).trigger('change');
            return;
        }

        $('#extraMaquina').html('<option value="">Cargando máquinas...</option>').prop('disabled', false).trigger('change');
        
        $.post('index.php?pagina=tecnicoProgramacion&accion=ajaxObtenerMaquinas', {id_punto: id_punto}, function(data) {
            let html = '<option value="">-- Seleccione Máquina --</option>';
            data.forEach(m => { html += `<option value="${m.id_maquina}">${m.device_id} - ${m.nombre_tipo_maquina}</option>`; });
            $('#extraMaquina').html(html).trigger('change'); // <- Avisar a Select2
        });
    });

    function guardarServicioExtra() {
        let data = {
            fecha_visita: $('#extraFecha').val(),
            id_cliente: $('#extraCliente').val(),
            id_punto: $('#extraPunto').val(),
            id_maquina: $('#extraMaquina').val(),
            id_tipo_mantenimiento: $('#extraMantenimiento').val()
        };

        if(!data.fecha_visita || !data.id_cliente || !data.id_punto || !data.id_maquina || !data.id_tipo_mantenimiento) {
            alert("Por favor, complete todos los campos.");
            return;
        }

        $('#modalExtra button').prop('disabled', true).text('Guardando...');

        $.ajax({
            url: 'index.php?pagina=tecnicoProgramacion&accion=ajaxGuardarExtra',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    cerrarModalNuevoServicio();
                    // Recargar el datatable para ver el nuevo servicio
                    $('#tablaProgramacion').DataTable().ajax.reload(null, false);
                    alert("¡Servicio agendado crack!");
                } else {
                    alert("Error: " + res.msj);
                }
            },
            error: function() {
                alert("Error de red al guardar.");
            },
            complete: function() {
                $('.btn-guardar').prop('disabled', false).text('Agendar Servicio');
                $('.btn-cancelar').prop('disabled', false);
            }
        });
    }

    // ── Función para abrir el reporte móvil ───────────────────────────
    function abrirReporteMovil(idOrden) {
        // Aquí navegas a la página del reporte. Ajusta la ruta según tu sistema.
        window.location.href = 'index.php?pagina=tecnicoReporte&accion=index&orden=' + idOrden;

        // Si prefieres abrir en modal o en nueva pestaña, comenta la línea de arriba y usa:
        // window.open('index.php?pagina=tecnicoReporte&accion=index&orden=' + idOrden, '_blank');
    }
</script>