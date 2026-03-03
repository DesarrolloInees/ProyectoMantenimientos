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
</style>

<!-- ══════════════════════════ HTML ══════════════════════════ -->
<div class="prog-wrapper">

    <!-- Header -->
    <div class="prog-header">
        <h2>
            <i class="fas fa-calendar-check"></i>
            Mis Servicios del Día
        </h2>
        <div class="fecha-container">
            <label for="fechaFiltro">Fecha de consulta</label>
            <input type="date" id="fechaFiltro">
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

</div>

<!-- ══════════════════════════ SCRIPTS ══════════════════════════ -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
    $(document).ready(function() {

        // ── Setear fecha ANTES de que DataTable haga su primera petición ──
        var hoy = new Date();
        var fechaHoy = hoy.getFullYear() + '-' +
            String(hoy.getMonth() + 1).padStart(2, '0') + '-' +
            String(hoy.getDate()).padStart(2, '0');
        $('#fechaFiltro').val(fechaHoy);

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
            columns: [{
                    data: 'nombre_cliente',
                    render: function(data, type, row) {
                        var cliente = data ? data : '<span class="text-gray-400">Sin cliente</span>';
                        return '<div class="cliente-info"><strong>' + cliente + '</strong></div>';
                    }
                },
                {
                    data: 'nombre_punto',
                    render: function(data, type, row) {
                        var punto = data ? data : 'Sin punto';
                        var direccion = row.direccion_punto ? row.direccion_punto : '';
                        var dir = direccion ?
                            '<small><i class="fas fa-map-marker-alt"></i> ' + direccion + '</small>' :
                            '';
                        return '<div class="cliente-info"><strong>' + punto + '</strong>' + dir + '</div>';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        var tipo = row.nombre_tipo_maquina ? row.nombre_tipo_maquina : 'N/A';
                        var device = row.device_id ?
                            '<small>Device ID: ' + row.device_id + '</small>' :
                            '<small>Sin Device ID</small>';
                        return '<div class="maquina-info"><strong>' + tipo + '</strong>' + device + '</div>';
                    }
                },
                {
                    data: 'tipo_mantenimiento',
                    render: function(data) {
                        if (!data) return '<span class="badge badge-gris">Sin definir</span>';
                        return '<span class="badge badge-azul"><i class="fas fa-tools"></i> ' + data + '</span>';
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: function(data, type, row) {
                        return '<button class="btn-atender" onclick="abrirReporteMovil(' + row.id_ordenes_servicio + ')">' +
                            '<i class="fas fa-clipboard-check"></i> Atender' +
                            '</button>';
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

    // ── Función para abrir el reporte móvil ───────────────────────────
    function abrirReporteMovil(idOrden) {
        // Aquí navegas a la página del reporte. Ajusta la ruta según tu sistema.
        window.location.href = 'index.php?pagina=tecnicoReporte&accion=index&orden=' + idOrden;

        // Si prefieres abrir en modal o en nueva pestaña, comenta la línea de arriba y usa:
        // window.open('index.php?pagina=tecnicoReporte&accion=index&orden=' + idOrden, '_blank');
    }
</script>