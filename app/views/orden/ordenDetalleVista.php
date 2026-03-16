<?php

/**
 * Vista: Edición Maestra de Servicios
 * Rediseño: industrial-elegante, responsivo, mínimo CSS extra
 */
?>

<style>
    /* ======================================
   FUENTE Y VARIABLES
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

    .det-header .fecha-badge {
        font-family: 'JetBrains Mono', monospace;
        font-size: .72rem;
        font-weight: 600;
        color: var(--c-brand);
        background: var(--c-brand-lt);
        border: 1px solid #bfdbfe;
        padding: 2px 10px;
        border-radius: 20px;
    }

    .det-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem .9rem;
        border-radius: var(--radius);
        font-size: .78rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all .15s;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn:hover {
        filter: brightness(.93);
        transform: translateY(-1px);
    }

    .btn-green {
        background: #16a34a;
        color: #fff;
    }

    .btn-red {
        background: #dc2626;
        color: #fff;
    }

    .btn-gray {
        background: #4b5563;
        color: #fff;
    }

    .btn-blue {
        background: var(--c-brand);
        color: #fff;
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
    }

    .pag-bar .info {
        font-size: .75rem;
        color: var(--c-muted);
        font-weight: 500;
    }

    .pag-bar .info b {
        color: var(--c-text);
    }

    .pag-controls {
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    .pag-btn {
        width: 32px;
        height: 32px;
        border: 1px solid var(--c-border);
        background: var(--c-surface);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: .8rem;
        color: var(--c-text);
        transition: all .15s;
    }

    .pag-btn:hover {
        background: var(--c-brand);
        color: #fff;
        border-color: var(--c-brand);
    }

    .pag-label {
        font-family: 'JetBrains Mono', monospace;
        font-size: .78rem;
        font-weight: 700;
        color: var(--c-brand);
        padding: 0 .5rem;
        min-width: 60px;
        text-align: center;
    }

    /* ======================================
   CONTENEDOR DE TABLA
====================================== */
    .tabla-wrapper {
        overflow-x: auto;
        border: 1px solid var(--c-border);
        border-radius: var(--radius);
        background: var(--c-surface);
        box-shadow: var(--shadow-sm);
        max-height: 65vh;
        overflow-y: auto;
    }

    /* ======================================
   TABLA
====================================== */
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

    /* Filas */
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

    /* Celdas con fondo de acento */
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
   SELECT2 — AJUSTE PARA TABLA DENSA
====================================== */

    /* Contenedor base */
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
        background-color: #fff !important;
    }

    #tablaEdicion .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 24px !important;
        padding-left: 6px !important;
        padding-right: 22px !important;
        font-size: .7rem !important;
        font-family: 'DM Sans', sans-serif !important;
        color: var(--c-text) !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    #tablaEdicion .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 24px !important;
        top: 1px !important;
    }

    /* Focus ring */
    #tablaEdicion .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--c-brand) !important;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .12) !important;
    }

    /* Acentos por columna vía clase heredada */
    #tablaEdicion .select2-tecnico .select2-selection__rendered {
        color: var(--c-indigo) !important;
        font-weight: 700 !important;
    }

    #tablaEdicion .select2-maquina .select2-selection__rendered,
    #tablaEdicion .select2-remision .select2-selection__rendered {
        font-family: 'JetBrains Mono', monospace !important;
        font-size: .63rem !important;
    }

    /* Dropdown */
    .select2-dropdown {
        z-index: 9999 !important;
        border: 1px solid var(--c-border) !important;
        border-radius: 6px !important;
        box-shadow: var(--shadow-md) !important;
    }

    .select2-search--dropdown input {
        font-size: .73rem !important;
        padding: .3rem .5rem !important;
    }

    .select2-results__option {
        font-size: .72rem !important;
        padding: .32rem .6rem !important;
    }

    .select2-results__option--highlighted {
        background: var(--c-brand) !important;
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
        transition: border-color .15s, box-shadow .15s;
        outline: none;
    }

    #tablaEdicion select:focus,
    #tablaEdicion input:focus {
        border-color: var(--c-brand);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
    }

    /* Tipografías específicas */
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

    /* Textarea */
    #tablaEdicion textarea {
        width: 100%;
        min-height: 52px;
        border: 1px solid var(--c-border);
        border-radius: 5px;
        padding: .25rem .4rem;
        font-size: .7rem;
        font-family: 'DM Sans', sans-serif;
        resize: vertical;
        transition: border-color .15s;
    }

    #tablaEdicion textarea:focus {
        border-color: var(--c-brand);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
        outline: none;
    }

    /* Sub-textos en celdas */
    .cell-sub {
        font-size: .6rem;
        color: var(--c-muted);
        margin-top: 2px;
        line-height: 1.2;
    }

    .cell-sub.warn {
        color: var(--c-red);
    }

    .cell-sub.brand {
        color: var(--c-brand);
    }

    .cell-sub.amber {
        color: var(--c-amber);
    }

    /* Error de tarifa */
    .error-tarifa-faltante td {
        background: #fff5f5 !important;
    }

    input.tarifa-error {
        border-color: var(--c-red) !important;
        background: var(--c-red-lt) !important;
        color: var(--c-red) !important;
    }

    /* ======================================
   BOTÓN REPUESTOS
====================================== */
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
        transition: all .15s;
    }

    .btn-repuestos.has-items {
        background: var(--c-brand-lt);
        color: var(--c-brand);
        border-color: #93c5fd;
    }

    .btn-repuestos:hover {
        filter: brightness(.95);
    }

    /* Badge de viáticos */
    .viaticos-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: .6rem;
        font-weight: 700;
        color: var(--c-orange);
        margin-top: 2px;
    }

    /* Select2 ajuste */
    .select2-container--default .select2-selection--single {
        height: 26px !important;
        min-height: 26px !important;
        border: 1px solid var(--c-border) !important;
        border-radius: 5px !important;
        display: flex !important;
        align-items: center !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 24px !important;
        padding-left: 6px !important;
        font-size: .71rem !important;
        font-weight: 600 !important;
        color: var(--c-text) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 24px !important;
        top: 1px !important;
    }

    .select2-dropdown {
        z-index: 1050 !important;
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

    /* ======================================
   RESPONSIVO
====================================== */
    @media (max-width: 768px) {
        .det-header {
            flex-direction: column;
            align-items: flex-start;
            gap: .75rem;
        }

        .det-actions {
            width: 100%;
        }

        .btn {
            flex: 1;
            justify-content: center;
        }

        .pag-bar {
            flex-direction: column;
            align-items: center;
            gap: .4rem;
        }
    }

    /* Scrollbar personalizado */
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
</style>

<!-- ======================================
     LAYOUT PRINCIPAL
====================================== -->
<div style="background:var(--c-bg); min-height:100vh; padding: 0 0 5rem;">

    <!-- HEADER -->
    <div class="det-header">
        <div style="display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;">
            <h2>
                <span style="font-size:1.3rem;">🛠️</span>
                Edición de Servicios
            </h2>
            <span class="fecha-badge"><?= $fecha ?></span>
        </div>

        <div class="det-actions">
            <button type="button" onclick="exportarExcelLimpio()" class="btn btn-green">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button type="button" onclick="exportarExcelNovedades()" class="btn btn-red">
                <i class="fas fa-file-contract"></i> Novedades
            </button>
            <a href="<?= BASE_URL ?>inicio" class="btn btn-gray">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- CONTENIDO -->
    <div style="padding: 1rem 1.25rem; display:flex; flex-direction:column; gap:.75rem;">

        <!-- PAGINACIÓN TOP -->
        <div class="pag-bar">
            <div class="info">Mostrando <b><span id="infoPaginaTop">–</span></b></div>
            <div class="pag-controls">
                <button type="button" onclick="cambiarPagina(-1)" class="pag-btn">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="pag-label" id="indicadorPaginaTop">1</span>
                <button type="button" onclick="cambiarPagina(1)" class="pag-btn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="info">Total: <b><span id="totalRegistrosTop">0</span></b></div>
        </div>

        <!-- FORMULARIO + TABLA -->
        <form id="formEdicionMaestra" action="<?= BASE_URL ?>ordenDetalle" method="POST">
            <input type="hidden" name="accion" value="guardarCambios">
            <input type="hidden" name="fecha_origen" value="<?= $fecha ?>">

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

                    <tbody>
                        <?php if (empty($servicios)): ?>
                            <tr>
                                <td colspan="16" style="padding:3rem; text-align:center; color:var(--c-muted);">
                                    <i class="fas fa-inbox" style="font-size:2rem; opacity:.3; display:block; margin-bottom:.5rem;"></i>
                                    No hay servicios para esta fecha.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($servicios as $s): ?>
                                <?php $idFila = $s['id_ordenes_servicio']; ?>
                                <?php include __DIR__ . '/partials/detalleFila.php'; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- GUARDAR FLOTANTE -->
            <button type="button" class="btn-save-float" onclick="procesarGuardado()">
                <i class="fas fa-save"></i>
                GUARDAR CAMBIOS
            </button>
        </form>

        <!-- PAGINACIÓN BOTTOM -->
        <div class="pag-bar">
            <div class="info">Mostrando <b><span id="infoPagina">–</span></b></div>
            <div class="pag-controls">
                <button type="button" onclick="cambiarPagina(-1)" class="pag-btn">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="pag-label" id="indicadorPagina">1</span>
                <button type="button" onclick="cambiarPagina(1)" class="pag-btn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="info">Total: <b><span id="totalRegistros">0</span></b></div>
        </div>

    </div><!-- /padding wrapper -->
</div><!-- /layout -->

<!-- MODALES -->
<?php include __DIR__ . '/partials/modalRepuestos.php'; ?>
<?php include __DIR__ . '/partials/modalNovedades.php'; ?>

<!-- INYECCIÓN PHP → JS -->
<script>
    window.DetalleConfig = window.DetalleConfig || {};
    window.DetalleConfig.catalogoRepuestos = <?= json_encode($listaRepuestos  ?? []) ?>;
    window.DetalleConfig.FESTIVOS_DB = <?= json_encode($listaFestivos   ?? []) ?>;
    window.DetalleConfig.listaNovedades = <?= json_encode($listaNovedades  ?? []) ?>;
</script>

<!-- SCRIPTS MÓDULOS -->
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleConfig.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleAjax.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleFechaUtils.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleExcel.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleRepuestos.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleNovedades.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleDesplazamientos.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detallePaginacion.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleNotificaciones.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleApp.js?v=<?= time() ?>"></script>

<script>
    $(document).ready(function() {
        if (window.DetalleApp && window.DetalleApp.init) {
            window.DetalleApp.init();
        } else {
            console.error("⚠️ DetalleApp no se pudo inicializar.");
        }
    });

    // ==========================================
    // NUEVO: INTERCEPTOR DE GUARDADO
    // ==========================================
    function procesarGuardado() {
        // 1. Verificamos que el sistema de notificaciones exista
        if (!window.DetalleNotificaciones || !window.DetalleNotificaciones.mostrarModalConfirmacion) {
            console.warn("Sistema de notificaciones no cargado. Guardado tradicional fallback.");
            document.getElementById('formEdicionMaestra').submit();
            return;
        }

        // 2. Llamamos al modal (que por dentro ejecuta validarCamposEstrictos automáticamente)
        window.DetalleNotificaciones.mostrarModalConfirmacion(
            "¿Estás seguro de que deseas guardar todos los cambios de esta página?",
            function() {
                // Si el usuario dice que SÍ (Confirmar), se ejecuta esto:
                
                // Opcional: Contamos las filas para la notificación
                const cantFilas = document.querySelectorAll('#tablaEdicion tbody tr[id^="fila_"]').length;
                window.DetalleNotificaciones.notificarEnviandoFormulario(cantFilas);
                
                // Deshabilitamos el botón para evitar doble clic
                const btnSave = document.querySelector('.btn-save-float');
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> GUARDANDO...';
                btnSave.style.pointerEvents = 'none';
                btnSave.style.opacity = '0.7';

                // Enviamos el formulario de verdad
                document.getElementById('formEdicionMaestra').submit();
            }
        );
    }
</script>