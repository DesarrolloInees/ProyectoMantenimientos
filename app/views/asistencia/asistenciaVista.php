<?php if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado."); ?>

<!-- Carga de FontAwesome y Google Fonts para mejor tipografía -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Estilos personalizados para darle "Alma y Vida" */
    .asistencia-container {
        font-family: 'Inter', sans-serif;
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
    }

    .card-modern {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .card-header-modern {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .empleado-header {
        background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
        border-bottom: 2px solid #2a5298;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .pill-total {
        background: #107c41;
        color: #fff;
        border-radius: 30px;
        padding: 6px 16px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .table-modern {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-modern thead th {
        background-color: #344767;
        color: #ffffff;
        font-weight: 500;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        padding: 12px;
        border: none;
    }

    .table-modern tbody tr {
        transition: all 0.2s ease;
    }

    .table-modern tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.002);
    }

    .table-modern td {
        vertical-align: middle;
        padding: 8px 12px;
        border-bottom: 1px solid #e9ecef;
    }

    .input-modern {
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 0.9rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        width: 100%;
    }

    .input-modern:focus {
        border-color: #2a5298;
        box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25);
        outline: none;
    }

    .input-calc {
        background-color: #e9ecef;
        font-weight: 600;
        color: #495057;
        text-align: center;
    }

    .row-festivo {
        background-color: #fff3cd !important;
    }

    .badge-cargo {
        background-color: #e0e7ff;
        color: #3730a3;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .btn-gradient {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        transition: transform 0.2s;
    }

    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(17, 153, 142, 0.3);
        color: white;
    }

    /* ============ NUEVO: tablas de nómina con recargos ============ */
    .tabla-scroll {
        max-height: 70vh;
        overflow: auto;
        border-top: 1px solid #e9ecef;
    }

    .table-nomina {
        width: 100%;
        min-width: 1850px;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
        font-size: 0.82rem;
    }

    .table-nomina thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background-color: #385d22;
        color: #fff;
        font-weight: 600;
        font-size: 0.68rem;
        line-height: 1.15;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 8px 6px;
        border: none;
        text-align: center;
        vertical-align: middle;
    }

    .table-nomina thead tr.fila-grupos th {
        top: 0;
        font-size: 0.72rem;
        padding: 6px;
    }

    .table-nomina tbody td {
        padding: 4px 6px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
        text-align: center;
        background-color: #fff;
    }

    .table-nomina tbody tr:hover td {
        background-color: #f8f9fa;
    }

    .table-nomina tbody tr.row-festivo td {
        background-color: #fff2cc !important;
    }

    /* Encabezados por grupo (mismos colores de la plantilla del Excel) */
    .grupo-jornada {
        background-color: #1f4e78 !important;
    }

    .grupo-extra {
        background-color: #107c41 !important;
    }

    .grupo-recargo {
        background-color: #7030a0 !important;
    }

    .grupo-otros {
        background-color: #996600 !important;
    }

    .th-base {
        background-color: #2f5597 !important;
    }

    .th-recargo {
        background-color: #385d22 !important;
    }

    /* Celda fija de la fecha (para no perder el día al desplazarse) */
    .table-nomina .col-fecha {
        position: sticky;
        left: 0;
        z-index: 6;
        min-width: 175px;
        box-shadow: 2px 0 4px rgba(0, 0, 0, 0.06);
    }

    .table-nomina thead .col-fecha {
        z-index: 7;
        background-color: #385d22;
    }

    .table-nomina tbody .col-fecha {
        background-color: #f8f9fa;
        text-align: left;
    }

    .input-grid {
        width: 100%;
        min-width: 74px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        padding: 4px 5px;
        font-size: 0.8rem;
        text-align: center;
    }

    .input-grid:focus {
        border-color: #2a5298;
        box-shadow: 0 0 0 0.15rem rgba(42, 82, 152, 0.25);
        outline: none;
    }

    .input-readonly {
        background-color: #e9ecef;
        font-weight: 700;
        color: #495057;
    }

    .col-manual input {
        background-color: #fffbe6;
        border-color: #e0c200;
    }

    .col-dinero {
        font-weight: 700;
        color: #107c41;
        min-width: 90px;
        white-space: nowrap;
    }

    .chk-domfest {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #996600;
    }

    /* ============ NUEVO: barra de configuración y panel de tarifas ============ */
    .config-bar,
    .acta-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: center;
        padding: 10px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.82rem;
        color: #334155;
    }

    .acta-bar {
        background: #ffffff;
    }

    .config-input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 4px 6px;
        font-size: 0.8rem;
        color: #0f172a;
    }

    .config-input-money {
        width: 110px;
        font-weight: 700;
        color: #107c41;
    }

    .acta-bar .config-input {
        width: 300px;
    }

    .btn-tasas {
        margin-left: auto;
        background: #7030a0;
        color: #fff;
        border: none;
        border-radius: 20px;
        padding: 5px 14px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-tasas:hover {
        background: #5a2582;
    }

    .tarifas-panel {
        background: #f5f3ff;
        border-bottom: 1px solid #ddd6fe;
        padding: 12px 16px;
    }

    .tarifas-titulo {
        font-size: 0.78rem;
        font-weight: 700;
        color: #5a2582;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .tarifas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .tarifa-item {
        background: #fff;
        border: 1px solid #ddd6fe;
        border-radius: 8px;
        padding: 6px 10px;
        min-width: 150px;
    }

    .tarifa-item label {
        display: block;
        font-size: 0.68rem;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 2px;
    }

    .tarifa-input {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .tarifa-input input {
        width: 78px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        padding: 3px 5px;
        font-size: 0.82rem;
        font-weight: 700;
        color: #5a2582;
        text-align: right;
    }

    /* ============ NUEVO: resumen del empleado y panel general ============ */
    .empleado-resumen,
    .panel-general {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 12px 16px;
        background: #ffffff;
        border-top: 2px solid #e2e8f0;
    }

    .res-item {
        flex: 1 1 150px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
    }

    .res-item span {
        display: block;
        font-size: 0.68rem;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.3px;
    }

    .res-item b {
        font-size: 1rem;
        color: #1e293b;
    }

    .res-verde b {
        color: #107c41;
    }

    .res-morado b {
        color: #7030a0;
    }

    .res-item.res-total {
        background: #0f172a;
        border-color: #0f172a;
    }

    .res-item.res-total span {
        color: #cbd5e1;
    }

    .res-item.res-total b {
        color: #38ef7d;
        font-size: 1.15rem;
    }

    .panel-general {
        border-top: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 18px;
    }
</style>

<div class="asistencia-container">
    <div class="card card-modern">
        <div class="card-header-modern">
            <h5 class="mb-0"><i class="fas fa-fingerprint me-2"></i>
                <?= $titulo ?? 'Procesador de Asistencias (Huellero + Servicios)' ?></h5>
            <span class="badge bg-light text-dark"><i class="fas fa-file-excel me-1"></i> Plantilla con recargos</span>
        </div>
        <div class="card-body">
            <!-- Formulario de Subida -->
            <form id="formHuellero" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="archivo_huellero" class="form-label fw-bold text-secondary">Sube el archivo CSV del
                            reloj biométrico</label>
                        <input type="file" class="form-control form-control-lg" name="archivo_huellero"
                            id="archivo_huellero" accept=".csv" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100 py-2" id="btnProcesar"
                            style="background-color: #2a5298; border:none; border-radius: 8px;">
                            <i class="fas fa-cogs"></i> Procesar Datos
                        </button>
                    </div>
                </div>
            </form>

            <div id="alertaMensaje" class="alert d-none shadow-sm rounded" role="alert"></div>

            <!-- Contenedor de Edición en Vivo -->
            <div id="editorContainer" class="d-none">
                <hr class="my-4">
                <div
                    class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border-start border-4 border-success">
                    <div>
                        <h4 class="text-success mb-1"><i class="fas fa-edit"></i> Edición de Nómina en Vivo</h4>
                        <p class="text-muted small mb-0">
                            Base 100% + recargos, horas extra nocturnas al 175%, dom/fest automático y recargos
                            ordinarios. Todo se recalcula solo y puedes sobrescribir cualquier celda antes de exportar.
                        </p>
                    </div>
                    <button id="btnGuardarDescargar" class="btn btn-gradient">
                        <i class="fas fa-file-excel fs-5 me-2"></i> Exportar Consolidado
                    </button>
                </div>

                <!-- Totales generales: igual que la hoja "Resumen Extras" del Excel -->
                <div class="panel-general">
                    <div class="res-item"><span>Empleados</span><b id="tgl-empleados">0</b></div>
                    <div class="res-item"><span>Horas extra (todos)</span><b id="tgl-horas-extras">00:00</b></div>
                    <div class="res-item res-verde"><span>$ Horas extra</span><b id="tgl-valor-extras">$0</b></div>
                    <div class="res-item res-morado"><span>$ Recargos y dom/fest</span><b
                            id="tgl-valor-recargos">$0</b></div>
                    <div class="res-item res-total"><span>Total general a pagar</span><b id="tgl-total">$0</b></div>
                </div>

                <!-- Aquí se inyectan las tarjetas por empleado -->
                <div id="tablasEmpleados"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Configuración que consumen los módulos de js/asistencia/*.js
    window.AsistenciaConfig = {
        baseUrl: '<?= BASE_URL ?>',
        urls: {
            procesar: '<?= BASE_URL ?>index.php?pagina=asistencia&accion=procesarArchivo',
            guardar: '<?= BASE_URL ?>index.php?pagina=asistencia&accion=guardarEdicion',
            descargar: '<?= BASE_URL ?>index.php?pagina=asistencia&accion=descargarExcel'
        },
        tasas: <?= json_encode($tasasDefecto ?? [], JSON_UNESCAPED_UNICODE) ?>,
        horario: <?= json_encode($horarioDefecto ?? [], JSON_UNESCAPED_UNICODE) ?>,
        divisor: <?= (int) (class_exists('AsistenciaControlador') ? AsistenciaControlador::DIVISOR_HORAS_MES : 210) ?>
    };
</script>
<script src="<?= BASE_URL ?>js/asistencia/core.js?v=1"></script>
<script src="<?= BASE_URL ?>js/asistencia/calculo.js?v=1"></script>
<script src="<?= BASE_URL ?>js/asistencia/render.js?v=1"></script>
<script src="<?= BASE_URL ?>js/asistencia/eventos.js?v=1"></script>
<script src="<?= BASE_URL ?>js/asistencia/exportar.js?v=1"></script>
