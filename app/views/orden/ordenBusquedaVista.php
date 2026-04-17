<?php

/**
 * Vista: Buscador Avanzado de Servicios
 * Diseño: Industrial-Elegante (Sincronizado con ordenDetalleVista)
 */
?>

<style>
    /* ======================================
       FUENTE Y VARIABLES (Misma base que Detalle)
    ====================================== */
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap');

    :root {
        --c-bg: #f4f5f7;
        --c-surface: #ffffff;
        --c-border: #e2e5ea;
        --c-muted: #8892a0;
        --c-text: #1a2233;
        --c-brand: #2563eb;
        --c-brand-lt: #eff6ff;
        --c-green: #16a34a;
        --c-green-lt: #f0fdf4;
        --c-amber: #d97706;
        --c-amber-lt: #fffbeb;
        --c-red: #dc2626;
        --c-red-lt: #fef2f2;
        --c-indigo: #4338ca;
        --c-indigo-lt: #eef2ff;
        --c-orange: #ea580c;
        --radius: 8px;
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, .08);
        --shadow-md: 0 4px 16px rgba(0, 0, 0, .10);
    }

    body,
    html {
        font-family: 'DM Sans', sans-serif;
        background: var(--c-bg);
    }

    /* ======================================
       HEADER PANEL
    ====================================== */
    .det-header {
        background: var(--c-surface);
        border-bottom: 1px solid var(--c-border);
        padding: 1rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 30;
        box-shadow: var(--shadow-sm);
    }

    .det-header h2 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--c-text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    /* ======================================
       PANEL DE BÚSQUEDA (NUEVO)
    ====================================== */
    .search-card {
        background: var(--c-surface);
        border: 1px solid var(--c-border);
        border-radius: var(--radius);
        padding: 1.25rem;
        box-shadow: var(--shadow-sm);
        margin-bottom: 1rem;
    }

    .search-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 160px), 1fr));
        gap: 1rem;
        align-items: end;
    }

    .search-group {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }

    .search-group label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--c-muted);
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .search-input {
        width: 100%;
        border: 1px solid var(--c-border);
        border-radius: 5px;
        padding: 0.4rem 0.5rem;
        font-size: 0.8rem;
        font-family: 'DM Sans', sans-serif;
        color: var(--c-text);
        background: #fff;
        transition: all 0.2s;
        height: 34px;
        /* Altura unificada */
    }

    .search-input:focus {
        border-color: var(--c-brand);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
        outline: none;
    }

    .btn-search {
        background: var(--c-indigo);
        color: #fff;
        border: none;
        height: 34px;
        border-radius: 5px;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: all 0.2s;
    }

    .btn-search:hover {
        background: #312e81;
        transform: translateY(-1px);
    }

    .btn-excel {
        background: var(--c-green);
        color: #fff;
        border: none;
        height: 34px;
        width: 34px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-excel:hover {
        background: #15803d;
        transform: translateY(-1px);
    }

    /* Select2 en Buscador */
    .search-card .select2-container--default .select2-selection--single {
        height: 34px !important;
        border: 1px solid var(--c-border) !important;
        border-radius: 5px !important;
        display: flex !important;
        align-items: center !important;
    }

    .search-card .select2-container--default .select2-selection--single .select2-selection__rendered {
        font-size: 0.8rem !important;
        color: var(--c-text) !important;
        padding-left: 0.5rem !important;
    }

    /* ======================================
       PAGINACIÓN
    ====================================== */
    .pag-bar {
        background: var(--c-surface);
        border: 1px solid var(--c-border);
        border-radius: var(--radius);
        padding: .6rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        flex-wrap: wrap;
        margin-bottom: 0.75rem;
    }

    .pag-bar .info {
        font-size: .75rem;
        color: var(--c-muted);
        font-weight: 500;
    }

    .pag-bar .info b {
        color: var(--c-text);
    }

    .pag-bar .info.highlight b {
        color: var(--c-brand);
        font-size: 0.85rem;
    }

    /* ======================================
       TABLA Y CONTENEDOR (Igual a Detalle)
    ====================================== */
    .tabla-wrapper {
        overflow-x: auto;
        border: 1px solid var(--c-border);
        border-radius: var(--radius);
        background: var(--c-surface);
        box-shadow: var(--shadow-sm);
        max-height: 60vh;
        overflow-y: auto;
    }

    #tablaEdicion {
        width: max-content;
        min-width: 100%;
        border-collapse: collapse;
        font-size: .72rem;
    }

    #tablaEdicion thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    #tablaEdicion thead tr th {
        background: #1e293b;
        color: #94a3b8;
        font-family: 'DM Sans', sans-serif;
        font-size: .65rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        padding: .6rem .5rem;
        border-right: 1px solid #334155;
        white-space: nowrap;
    }

    /* Columnas con acento de color */
    #tablaEdicion thead th.col-tecnico {
        background: #1e1b4b;
        color: #a5b4fc;
    }

    #tablaEdicion thead th.col-servicio {
        background: #1e3a5f;
        color: #93c5fd;
        border-left: 3px solid #3b82f6;
    }

    #tablaEdicion thead th.col-valor {
        background: #14532d;
        color: #86efac;
    }

    #tablaEdicion thead th.col-nov {
        background: #7f1d1d;
        color: #fca5a5;
    }

    #tablaEdicion tbody tr {
        border-bottom: 1px solid var(--c-border);
        transition: background .12s;
    }

    #tablaEdicion tbody tr:hover {
        background: #f8faff;
    }

    #tablaEdicion tbody tr:nth-child(even) {
        background: #fafbfc;
    }

    #tablaEdicion tbody tr:nth-child(even):hover {
        background: #f0f5ff;
    }

    #tablaEdicion td {
        padding: .35rem .4rem;
        vertical-align: middle;
        border-right: 1px solid #f0f0f0;
    }

    #tablaEdicion td.bg-tecnico {
        background: var(--c-indigo-lt) !important;
    }

    #tablaEdicion td.bg-servicio {
        background: var(--c-brand-lt) !important;
        border-left: 3px solid #93c5fd;
    }

    #tablaEdicion td.bg-valor {
        background: var(--c-green-lt) !important;
    }

    #tablaEdicion td.bg-desp {
        background: var(--c-amber-lt) !important;
    }

    #tablaEdicion td.bg-obs {
        background: var(--c-brand-lt) !important;
        border-right: 3px solid #93c5fd;
    }

    /* ======================================
       CONTROLES EN TABLA
    ====================================== */
    #tablaEdicion select,
    #tablaEdicion input[type="text"],
    #tablaEdicion input[type="date"],
    #tablaEdicion input[type="time"] {
        width: 100%;
        border: 1px solid var(--c-border);
        border-radius: 5px;
        padding: .22rem .4rem;
        font-size: .71rem;
        font-family: 'DM Sans', sans-serif;
        color: var(--c-text);
        background: #fff;
        outline: none;
    }

    #tablaEdicion select:focus,
    #tablaEdicion input:focus {
        border-color: var(--c-brand);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
    }

    #tablaEdicion select[name*="[id_tecnico]"] {
        color: var(--c-indigo) !important;
        font-weight: 700;
    }

    #tablaEdicion select[name*="[id_maquina]"],
    #tablaEdicion select[name*="[remision]"] {
        font-family: 'JetBrains Mono', monospace;
        font-size: .65rem;
    }

    #tablaEdicion input[name*="[valor]"] {
        color: var(--c-green) !important;
        font-weight: 700;
        text-align: right;
    }

    #tablaEdicion textarea {
        width: 100%;
        min-height: 52px;
        border: 1px solid var(--c-border);
        border-radius: 5px;
        padding: .25rem .4rem;
        font-size: .7rem;
        resize: vertical;
    }

    /* Select2 Tabla Densa */
    #tablaEdicion .select2-container {
        min-width: 0 !important;
    }

    #tablaEdicion .select2-container--default .select2-selection--single {
        height: 26px !important;
        min-height: 26px !important;
        border: 1px solid var(--c-border) !important;
        border-radius: 5px !important;
        display: flex !important;
        align-items: center !important;
    }

    #tablaEdicion .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 24px !important;
        padding-left: 6px !important;
        font-size: .7rem !important;
        color: var(--c-text) !important;
    }

    #tablaEdicion .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 24px !important;
        top: 1px !important;
    }

    /* ======================================
       BOTÓN GUARDAR FLOTANTE
    ====================================== */
    .btn-save-float {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 50;
        background: var(--c-brand);
        color: #fff;
        font-family: 'DM Sans', sans-serif;
        font-size: .9rem;
        font-weight: 700;
        padding: .9rem 1.6rem;
        border-radius: 50px;
        border: 3px solid #fff;
        box-shadow: 0 8px 24px rgba(37, 99, 235, .35);
        display: flex;
        align-items: center;
        gap: .5rem;
        cursor: pointer;
        transition: all .2s;
    }

    .btn-save-float:hover {
        background: #1d4ed8;
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(37, 99, 235, .4);
    }

    /* Scrollbar */
    .tabla-wrapper::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .tabla-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .tabla-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .tabla-wrapper::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Botón Repuestos y Badges (Heredado) */
    .btn-repuestos {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .3rem;
        width: 100%;
        padding: .28rem .4rem;
        border-radius: 5px;
        border: 1px solid var(--c-border);
        font-size: .65rem;
        font-weight: 600;
        cursor: pointer;
        background: #fff;
        color: var(--c-muted);
    }

    .btn-repuestos.has-items {
        background: var(--c-brand-lt);
        color: var(--c-brand);
        border-color: #93c5fd;
    }

    .viaticos-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: .6rem;
        font-weight: 700;
        color: var(--c-orange);
        margin-top: 2px;
    }

    .cell-sub {
        font-size: .6rem;
        color: var(--c-muted);
        margin-top: 2px;
        line-height: 1.2;
    }
</style>

<div style="background:var(--c-bg); min-height:100vh; padding: 0 0 5rem;">

    <div class="det-header">
        <h2><span style="font-size:1.3rem; color:var(--c-brand);">🔍</span> Búsqueda Avanzada de Servicios</h2>
    </div>

    <div style="padding: 1rem 1.25rem;">

        <div class="search-card">
            <div class="search-grid">

                <div class="search-group">
                    <label>Remisión</label>
                    <input type="text" id="busqRemision" class="search-input" placeholder="# Remisión">
                </div>

                <div class="search-group">
                    <label>Cliente</label>
                    <select id="busqCliente" class="select2-search w-full">
                        <option value="">Todos los clientes</option>
                        <?php foreach ($listaClientes as $c): ?>
                            <option value="<?= $c['id_cliente'] ?>"><?= $c['nombre_cliente'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="search-group">
                    <label>Punto (Auto)</label>
                    <select id="busqPunto" class="select2-search w-full">
                        <option value="">Seleccione Cliente...</option>
                    </select>
                </div>

                <div class="search-group">
                    <label>Delegación</label>
                    <select id="busqDelegacion" class="select2-search w-full">
                        <option value="">Todas</option>
                        <?php foreach ($listaDelegaciones as $d): ?>
                            <option value="<?= $d['id_delegacion'] ?>"><?= $d['nombre_delegacion'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="search-group">
                    <label>Desde</label>
                    <input type="date" id="busqFechaInicio" class="search-input">
                </div>

                <div class="search-group">
                    <label>Hasta</label>
                    <input type="date" id="busqFechaFin" class="search-input">
                </div>

                <div class="search-group" style="flex-direction: row; gap: 0.5rem;">
                    <button type="button" onclick="realizarBusqueda()" class="btn-search" style="flex: 1;">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" onclick="exportarExcelLimpio()" class="btn-excel" title="Descargar Reporte General">
                        <i class="fas fa-file-excel"></i>
                    </button>
                </div>

            </div>
        </div>

        <div class="pag-bar">
            <div class="info highlight">Resultados encontrados: <b id="totalRegistros">0</b></div>
            <div class="info">Filtrado actual: <b id="infoPagina">Esperando búsqueda...</b></div>
        </div>

        <form id="formEdicionMaestra" action="<?= BASE_URL ?>ordenDetalle" method="POST">

            <input type="hidden" name="accion" value="guardarCambios">
            <input type="hidden" name="es_busqueda" value="1">
            <input type="hidden" name="fecha_origen" value="<?= date('Y-m-d') ?>">

            <div class="tabla-wrapper">
                <table id="tablaEdicion">
                    <thead>
                        <tr>
                            <th class="col-cliente">Cliente</th>
                            <th class="col-punto">Punto</th>
                            <th style="width:90px">Fecha</th>
                            <th class="col-tecnico">Técnico</th>
                            <th class="col-servicio">Servicio</th>
                            <th style="width:90px">Zona</th>
                            <th style="width:130px">Máquina</th>
                            <th class="col-servicio" style="width:220px">¿Qué se hizo?</th>
                            <th class="col-nov" title="Novedad" style="width:36px">⚠️</th>
                            <th class="col-valor" style="width:100px">Valor</th>
                            <th style="width:90px">Repuestos</th>
                            <th style="width:90px">Remisión</th>
                            <th style="width:80px">Entrada</th>
                            <th style="width:80px">Salida</th>
                            <th class="col-desp" style="width:72px; background:#92400e; color:#fde68a;">⏱ Despl.</th>
                            <th style="width:100px">Estado/Calif.</th>
                        </tr>
                    </thead>

                    <tbody id="resultadosBusqueda">
                        <tr>
                            <td colspan="16" style="padding:4rem; text-align:center; color:var(--c-muted);">
                                <div style="display:flex; flex-direction:column; align-items:center; gap:0.5rem;">
                                    <i class="fas fa-search" style="font-size:2.5rem; opacity:.2;"></i>
                                    <p style="font-size:0.9rem; margin:0;">Usa los filtros superiores y presiona <b>Buscar</b> o <b>Enter</b> para cargar órdenes.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn-save-float" onclick="procesarGuardado()">
                <i class="fas fa-save"></i> GUARDAR CAMBIOS
            </button>
        </form>

    </div>
</div>

<?php include __DIR__ . '/partials/modalRepuestos.php'; ?>
<?php include __DIR__ . '/partials/modalNovedades.php'; ?>

<script>
    window.DetalleConfig = window.DetalleConfig || {};
    window.DetalleConfig.BASE_URL = '<?= BASE_URL ?>';
    window.DetalleConfig.catalogoRepuestos = <?= json_encode($listaRepuestos ?? []) ?>;
    window.DetalleConfig.FESTIVOS_DB = <?= json_encode($listaFestivos ?? []) ?>;
    window.DetalleConfig.listaNovedades = <?= json_encode($listaNovedades ?? []) ?>;
</script>

<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleConfig.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleAjax.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleFechaUtils.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleExcel.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleRepuestos.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleNovedades.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleDesplazamientos.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleNotificaciones.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleApp.js?v=<?= time() ?>"></script>

<script>
    $(document).ready(function() {
        // Inicializar Select2 en los filtros
        $('.select2-search').select2({
            width: '100%',
            language: "es"
        });

        // ==========================================
        // CASACADA: CLIENTE -> PUNTO
        // ==========================================
        $('#busqCliente').on('change', function() {
            let idCliente = $(this).val();
            let $selectPunto = $('#busqPunto');

            $selectPunto.empty().append('<option value="">Cargando...</option>').trigger('change');

            if (!idCliente) {
                $selectPunto.empty().append('<option value="">Seleccione Cliente...</option>').trigger('change');
                return;
            }

            $.post('<?= BASE_URL ?>ordenDetalle', {
                accion: 'ajaxObtenerPuntos',
                id_cliente: idCliente
            }, function(data) {
                $selectPunto.empty();
                if (data && data.length > 0) {
                    $selectPunto.append('<option value="">Todos los puntos</option>');
                    data.forEach(p => {
                        $selectPunto.append(new Option(p.nombre_punto, p.id_punto, false, false));
                    });
                } else {
                    $selectPunto.append('<option value="">Sin puntos asignados</option>');
                }
                $selectPunto.trigger('change');
            }, 'json').fail(function() {
                $selectPunto.empty().append('<option value="">Error al cargar</option>').trigger('change');
            });
        });

        // Presionar ENTER para buscar
        $('.search-input').on('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                $(this).blur();
                realizarBusqueda();
            }
        });

        // Inicializar Apps Globales
        if (window.DetalleApp && window.DetalleApp.init) {
            window.DetalleApp.init();
        }
    });

    // ==========================================
    // FUNCIÓN DE BÚSQUEDA
    // ==========================================
    function realizarBusqueda() {
        let remision = $('#busqRemision').val();
        let cliente = $('#busqCliente').val();
        let punto = $('#busqPunto').val();
        let delegacion = $('#busqDelegacion').val();
        let fechaInicio = $('#busqFechaInicio').val();
        let fechaFin = $('#busqFechaFin').val();

        $('#resultadosBusqueda').html('<tr><td colspan="16" style="padding:4rem; text-align:center; color:var(--c-brand);"><i class="fas fa-spinner fa-spin" style="font-size:2rem; margin-bottom:0.5rem;"></i><br><b>Buscando servicios...</b></td></tr>');

        $.post('<?= BASE_URL ?>ordenDetalle', {
            accion: 'ajaxBuscarOrdenes',
            remision: remision,
            id_cliente: cliente,
            id_punto: punto,
            id_delegacion: delegacion,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin
        }, function(htmlRespuesta) {
            $('#resultadosBusqueda').html(htmlRespuesta);

            // Inicializar Select2 en los nuevos selects generados
            if (jQuery().select2) {
                $('#resultadosBusqueda select').not('.no-select2').select2({
                    width: '100%',
                    language: "es"
                });
            }

            // Actualizar contadores
            let total = $('#resultadosBusqueda tr[id^="fila_"]').length;
            $('#totalRegistros').text(total);
            $('#infoPagina').text(`Filtro aplicado (${fechaInicio || 'Inicio'} a ${fechaFin || 'Fin'})`);

            // REPARAR EL TEMA DE LAS REMISIONES PARA LAS FILAS INYECTADAS
            $('#resultadosBusqueda tr[id^="fila_"]').each(function() {
                let $selectTecnico = $(this).find('select[name*="[id_tecnico]"]');
                let $selectRemision = $(this).find('select[name*="[remision]"]');

                if ($selectTecnico.length > 0 && $selectRemision.length > 0) {
                    let idTecnico = $selectTecnico.val();
                    let remisionActual = $selectRemision.val();

                    $.post('<?= BASE_URL ?>ordenDetalle', {
                        accion: 'ajaxObtenerRemisiones',
                        id_tecnico: idTecnico,
                        remision_actual: remisionActual
                    }, function(data) {
                        $selectRemision.empty();
                        $selectRemision.append(new Option("S/N", "", false, false));

                        if (data && data.length > 0) {
                            data.forEach(r => {
                                let isSelected = (r.numero_remision == remisionActual);
                                $selectRemision.append(new Option(r.numero_remision, r.numero_remision, isSelected, isSelected));
                            });
                        }
                        $selectRemision.trigger('change.select2');
                    }, 'json');
                }
            });

        }).fail(function() {
            $('#resultadosBusqueda').html('<tr><td colspan="16" style="padding:2rem; text-align:center; color:var(--c-red); font-weight:bold;">❌ Error de conexión con el servidor.</td></tr>');
            $('#totalRegistros').text("0");
        });
    }

    // ==========================================
    // EXPORTAR EXCEL BÚSQUEDA (El script que ya tenías)
    // ==========================================
    

    // ==========================================
    // MAGIA IA Y GUARDADO (Misma lógica de Detalle)
    // ==========================================
    async function mejorarTextoIA(boton, idFila) {
        // ... misma lógica intacta ...
        const textarea = document.getElementById(`obs_${idFila}`);
        const textoOriginal = textarea.value.trim();

        if (textoOriginal === '') {
            alert("⚠️ No hay texto escrito para mejorar.");
            return;
        }

        const iconoOriginal = boton.innerHTML;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        boton.disabled = true;

        try {
            const formData = new FormData();
            formData.append('accion', 'ajaxMejorarTextoIA');
            formData.append('texto', textoOriginal);

            const response = await fetch(window.DetalleConfig.BASE_URL + 'ordenDetalle', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'ok') {
                textarea.value = data.texto_mejorado;
                textarea.style.backgroundColor = 'var(--c-green-lt)';
                setTimeout(() => {
                    textarea.style.backgroundColor = '';
                }, 1500);
            } else {
                alert("❌ Error procesando con IA: " + data.msg);
            }
        } catch (error) {
            console.error(error);
            alert("❌ Error de conexión.");
        } finally {
            boton.innerHTML = iconoOriginal;
            boton.disabled = false;
        }
    }

    // ==========================================
    // INTERCEPTOR DE GUARDADO (MODO JSON PARA BUSCADOR)
    // ==========================================
    function procesarGuardado() {
        if (!window.DetalleNotificaciones || !window.DetalleNotificaciones.mostrarModalConfirmacion) {
            ejecutarGuardadoJSON();
            return;
        }

        window.DetalleNotificaciones.mostrarModalConfirmacion(
            "¿Estás seguro de que deseas guardar todos los cambios de tu búsqueda actual?",
            function() {
                ejecutarGuardadoJSON();
            }
        );
    }

    // ==========================================
    // EL MOTOR JSON (¡Con recarga inteligente!)
    // ==========================================
    async function ejecutarGuardadoJSON() {
        const filas = document.querySelectorAll('#tablaEdicion tbody tr[id^="fila_"]');
        
        if (filas.length === 0) {
            alert("⚠️ No hay servicios en la tabla para guardar.");
            return;
        }

        if (window.DetalleNotificaciones && window.DetalleNotificaciones.notificarEnviandoFormulario) {
            window.DetalleNotificaciones.notificarEnviandoFormulario(filas.length);
        }
        
        // Bloquear botón visualmente
        const btnSave = document.querySelector('.btn-save-float');
        const originalHTML = btnSave.innerHTML;
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> GUARDANDO...';
        btnSave.style.pointerEvents = 'none';
        btnSave.style.opacity = '0.7';

        // 1. Recolectar datos y armar el objeto limpio
        let serviciosData = {};
        let fechaOrigen = document.querySelector('input[name="fecha_origen"]').value;

        filas.forEach(fila => {
            let idOrden = fila.id.split('_')[1];
            let filaDatos = {};
            
            // Atrapamos inputs, selects y textareas
            let elementos = fila.querySelectorAll('input, select, textarea');
            elementos.forEach(el => {
                if (el.name) {
                    let match = el.name.match(/\[([a-zA-Z0-9_]+)\]$/);
                    if (match && match[1]) {
                        filaDatos[match[1]] = el.value;
                    }
                }
            });
            
            serviciosData[idOrden] = filaDatos;
        });

        // 2. Preparar el paquete (FormData)
        const formData = new FormData();
        formData.append('accion', 'ajaxGuardarCambiosJSON'); // Usamos la misma función del backend
        formData.append('fecha_origen', fechaOrigen);
        formData.append('json_data', JSON.stringify(serviciosData));

        // 3. Enviar vía fetch
        try {
            const response = await fetch(window.DetalleConfig.BASE_URL + 'ordenDetalle', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'ok') {
                alert("✅ " + data.msg);
                
                // TRUCO SENIOR: Refrescamos la búsqueda actual sin recargar la página
                // para que el usuario no pierda los filtros que tiene escritos arriba
                realizarBusqueda(); 
                
            } else if (data.status === 'warning') {
                alert("⚠️ " + data.msg);
                realizarBusqueda(); // También refrescamos por si hubo filas buenas
            } else {
                alert("❌ Error: " + data.msg);
            }
        } catch (error) {
            console.error("Error guardando:", error);
            alert("❌ Error de red o del servidor al intentar guardar.");
        } finally {
            // Restaurar botón siempre
            btnSave.innerHTML = originalHTML;
            btnSave.style.pointerEvents = 'auto';
            btnSave.style.opacity = '1';
        }
    }
</script>