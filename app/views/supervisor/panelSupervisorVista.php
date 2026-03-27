<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    .panel-wrapper {
        background: #f8fafc;
        padding: 1.5rem;
        border-radius: 12px;
    }

    .header-panel {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .btn-agregar {
        background: #2563eb;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-agregar:hover {
        background: #1d4ed8;
    }

    .tablero-kanban {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        padding-bottom: 20px;
        min-height: 600px;
    }

    /* Hicimos las columnas un poco más anchas para aprovechar el espacio al ser solo dos */
    .columna-kanban {
        background: #e2e8f0;
        border-radius: 8px;
        width: 400px;
        padding: 15px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
    }

    .col-header {
        font-weight: 800;
        text-align: center;
        padding-bottom: 12px;
        border-bottom: 3px solid #cbd5e1;
        margin-bottom: 15px;
        text-transform: uppercase;
        font-size: 0.9rem;
        color: #334155;
    }

    .contenedor-tarjetas {
        flex-grow: 1;
        overflow-y: auto;
    }

    .tarjeta-servicio {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border-left: 5px solid #94a3b8;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .tarjeta-servicio:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .tarjeta-servicio.pendiente {
        border-left-color: #eab308;
    }

    .tarjeta-servicio.finalizado {
        border-left-color: #22c55e;
    }

    .t-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .t-remision {
        font-weight: bold;
        color: #0f172a;
        font-size: 1rem;
    }

    .t-tecnico {
        font-size: 0.75rem;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: bold;
        color: #475569;
    }

    .t-body p {
        margin: 4px 0;
        font-size: 0.85rem;
        color: #475569;
    }

    .t-body strong {
        color: #1e293b;
    }

    .t-footer {
        margin-top: 10px;
        display: flex;
        gap: 5px;
        justify-content: flex-end;
    }

    .btn-detalle {
        background: #e2e8f0;
        border: none;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
        cursor: pointer;
        color: #334155;
    }

    .btn-detalle:hover {
        background: #cbd5e1;
    }

    .btn-cancelar {
        background: #fee2e2;
        border: none;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
        cursor: pointer;
        color: #b91c1c;
    }

    .btn-cancelar:hover {
        background: #fca5a5;
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
        color: #1e3a8a;
    }

    .btn-cerrar {
        background: none;
        border: none;
        font-size: 1.25rem;
        color: #64748b;
        cursor: pointer;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.3rem;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.9rem;
        outline: none;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        border-top: 1px solid #cbd5e1;
        padding-top: 1rem;
        margin-top: 1.5rem;
    }

    .btn-cerrar-modal {
        background: #f1f5f9;
        color: #1e293b;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-guardar-modal {
        background: #2563eb;
        color: #fff;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        font-size: 0.9rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>

<div class="panel-wrapper">
    <div class="header-panel">
        <h3 style="margin:0; color:#1e293b;"><i class="fas fa-desktop"></i> Monitor de Servicios (Hoy)</h3>
        <div>
            <span id="indicador-act" style="font-size: 12px; color: #16a34a; font-weight:bold; display:none; margin-right: 15px;">
                <i class="fas fa-sync fa-spin"></i> Actualizando...
            </span>
            <button class="btn-agregar" onclick="abrirModalNuevoServicio()">
                <i class="fas fa-plus"></i> Nuevo Servicio
            </button>
        </div>
    </div>
    <div class="modal-overlay" id="modalNuevoServicio">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Nuevo Servicio</h3>
            <button class="btn-cerrar" onclick="cerrarModalNuevoServicio()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="form-group">
            <label>Fecha de Visita</label>
            <input type="date" id="nsFecha" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
            <label>Técnico Asignado</label>
            <select id="nsTecnico" class="form-control"><option value="">Cargando...</option></select>
        </div>
        <div class="form-group">
            <label>1. Cliente</label>
            <select id="nsCliente" class="form-control"><option value="">Cargando...</option></select>
        </div>
        <div class="form-group">
            <label>2. Punto</label>
            <select id="nsPunto" class="form-control" disabled><option value="">Seleccione un cliente</option></select>
        </div>
        <div class="form-group">
            <label>3. Máquina</label>
            <select id="nsMaquina" class="form-control" disabled><option value="">Seleccione un punto</option></select>
        </div>
        <div class="form-group">
            <label>4. Tipo de Mantenimiento</label>
            <select id="nsMantenimiento" class="form-control"><option value="">Cargando...</option></select>
        </div>

        <div class="modal-footer">
            <button class="btn-cerrar-modal" onclick="cerrarModalNuevoServicio()">Cancelar</button>
            <button class="btn-guardar-modal" onclick="guardarServicioSupervisor()">Agendar Servicio</button>
        </div>
    </div>
</div>

    <div class="tablero-kanban">
        <div class="columna-kanban">
            <div class="col-header" style="border-bottom-color: #fde047;">🕒 Programados  (<span id="count-pendientes">0</span>)</div>
            <div id="col-pendientes" class="contenedor-tarjetas"></div>
        </div>

        <div class="columna-kanban">
            <div class="col-header" style="border-bottom-color: #86efac;">✅ Finalizados  (<span id="count-finalizados">0</span>)</div>
            <div id="col-finalizados" class="contenedor-tarjetas"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    $(document).ready(function() {
        cargarServiciosSupervisor();
        setInterval(cargarServiciosSupervisor, 10000);
    });

    function cargarServiciosSupervisor() {
        $('#indicador-act').fadeIn(200);

        $.ajax({
            url: 'index.php?pagina=panelSupervisor&accion=ajaxObtenerServicios',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    renderizarTablero(res.data);
                } else {
                    console.error("Error BD:", res.msj);
                }
            },
            error: function() {
                console.error("Error de conexión AJAX al actualizar tablero.");
            },
            complete: function() {
                setTimeout(() => $('#indicador-act').fadeOut(200), 1000);
            }
        });
    }

    function renderizarTablero(servicios) {
        // Limpiamos las dos columnas
        $('#col-pendientes, #col-finalizados').empty();

        let cPendientes = 0,
            cFinalizados = 0;

        servicios.forEach(servicio => {
            // LÓGICA DE ESTADOS: Tomamos el campo que usas en BD
            let idEstado = parseInt(servicio.id_estado_maquina);

            // Si por alguna razón viene null, lo forzamos a 2 (Programado) por seguridad
            if (isNaN(idEstado)) {
                idEstado = 2;
            }

            let nombreCliente = servicio.cliente || servicio.nombre_cliente || 'SIN CLIENTE';

            // El botón cancelar solo aparece si el estado es 2 (Programado)
            let botonesAccion = ``;
            if (idEstado === 2) {
                botonesAccion += `<button class="btn-cancelar" onclick="cancelarServicio(${servicio.id_ordenes_servicio})"><i class="fas fa-times"></i> Cancelar</button>`;
            }

            let tarjetaHtml = `
                <div class="tarjeta-servicio">
                    <div class="t-header">
                        <span class="t-remision">#${servicio.numero_remision || 'N/A'}</span>
                        <span class="t-tecnico"><i class="fas fa-user-cog"></i> ${servicio.nombre_tecnico || 'Sin Asignar'}</span>
                    </div>
                    <div class="t-body">
                        <p><strong>Cliente:</strong> ${nombreCliente}</p>
                        <p><strong>Punto:</strong> ${servicio.nombre_punto || 'No especificado'}</p>
                    </div>
                    <div class="t-footer">
                        ${botonesAccion}
                    </div>
                </div>
            `;

            // Acomodamos en la columna según tu regla de negocio
            if (idEstado === 2) {
                // ESTADO 2 = PROGRAMADO (Se va a Pendientes)
                let jTarjeta = $(tarjetaHtml).addClass('pendiente');
                $('#col-pendientes').append(jTarjeta);
                cPendientes++;
            } else if (idEstado === 1) {
                // ESTADO 1 = FINALIZADO (Se va a la derecha)
                let jTarjeta = $(tarjetaHtml).addClass('finalizado');
                $('#col-finalizados').append(jTarjeta);
                cFinalizados++;
            }
        });

        // Actualizamos contadores
        $('#count-pendientes').text(cPendientes);
        $('#count-finalizados').text(cFinalizados);
    }

    function abrirModalNuevoServicio() {
        alert("Aquí abriremos el modal o la pantalla para crear un nuevo servicio.");
    }

    function cancelarServicio(idOrden) {
        if (confirm("¿Estás seguro de cancelar el servicio remisión #" + idOrden + "?")) {
            $.ajax({
                url: 'index.php?pagina=panelSupervisor&accion=ajaxCancelarServicio',
                type: 'POST',
                data: {
                    id_orden: idOrden
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        // Si todo sale bien, recargamos el tablero inmediatamente
                        cargarServiciosSupervisor();
                    } else {
                        alert("Error: " + res.msj);
                    }
                },
                error: function() {
                    alert("Error de conexión al intentar cancelar el servicio.");
                }
            });
        }
    }

    function verDetalle(idOrden) {
        alert("Abrir detalles de la orden ID: " + idOrden);
    }
    // ── LÓGICA DEL MODAL SUPERVISOR ──
    $(document).ready(function() {
        $('#nsTecnico, #nsCliente, #nsPunto, #nsMaquina, #nsMantenimiento').select2({ dropdownParent: $('#modalNuevoServicio') });
    });

    function abrirModalNuevoServicio() {
        $('#modalNuevoServicio').addClass('activo');
        
        // Cargar listas si están vacías
        if($('#nsTecnico option').length <= 1) {
            $.post('index.php?pagina=panelSupervisor&accion=ajaxObtenerTecnicos', function(data) {
                let html = '<option value="">-- Seleccione Técnico --</option>';
                data.forEach(t => { html += `<option value="${t.id_tecnico}">${t.nombre_tecnico}</option>`; });
                $('#nsTecnico').html(html).trigger('change');
            });
            $.post('index.php?pagina=panelSupervisor&accion=ajaxObtenerClientes', function(data) {
                let html = '<option value="">-- Seleccione Cliente --</option>';
                data.forEach(c => { html += `<option value="${c.id_cliente}">${c.nombre_cliente}</option>`; });
                $('#nsCliente').html(html).trigger('change');
            });
            $.post('index.php?pagina=panelSupervisor&accion=ajaxObtenerTiposMantenimiento', function(data) {
                let html = '<option value="">-- Seleccione Tipo --</option>';
                data.forEach(t => { html += `<option value="${t.id_tipo_mantenimiento}">${t.nombre_completo}</option>`; });
                $('#nsMantenimiento').html(html).trigger('change');
            });
        }
    }

    function cerrarModalNuevoServicio() {
        $('#modalNuevoServicio').removeClass('activo');
        $('#nsTecnico, #nsCliente, #nsMantenimiento').val('').trigger('change');
        $('#nsPunto').html('<option value="">Seleccione un cliente</option>').prop('disabled', true).trigger('change');
        $('#nsMaquina').html('<option value="">Seleccione un punto</option>').prop('disabled', true).trigger('change');
    }

    // Cascada Cliente -> Punto
    $('#nsCliente').on('change', function() {
        let id_cliente = $(this).val();
        if(!id_cliente) {
            $('#nsPunto').html('<option value="">Seleccione un cliente</option>').prop('disabled', true).trigger('change');
            $('#nsMaquina').html('<option value="">Seleccione un punto</option>').prop('disabled', true).trigger('change');
            return;
        }
        $('#nsPunto').html('<option value="">Cargando puntos...</option>').prop('disabled', false).trigger('change');
        $.post('index.php?pagina=panelSupervisor&accion=ajaxObtenerPuntos', {id_cliente: id_cliente}, function(data) {
            let html = '<option value="">-- Seleccione Punto --</option>';
            data.forEach(p => { html += `<option value="${p.id_punto}">${p.nombre_punto} (${p.direccion || 'Sin dir'})</option>`; });
            $('#nsPunto').html(html).trigger('change');
        });
    });

    // Cascada Punto -> Máquina
    $('#nsPunto').on('change', function() {
        let id_punto = $(this).val();
        if(!id_punto) {
            $('#nsMaquina').html('<option value="">Seleccione un punto</option>').prop('disabled', true).trigger('change');
            return;
        }
        $('#nsMaquina').html('<option value="">Cargando máquinas...</option>').prop('disabled', false).trigger('change');
        $.post('index.php?pagina=panelSupervisor&accion=ajaxObtenerMaquinas', {id_punto: id_punto}, function(data) {
            let html = '<option value="">-- Seleccione Máquina --</option>';
            data.forEach(m => { html += `<option value="${m.id_maquina}">${m.device_id} - ${m.nombre_tipo_maquina}</option>`; });
            $('#nsMaquina').html(html).trigger('change');
        });
    });

    function guardarServicioSupervisor() {
        let data = {
            fecha_visita: $('#nsFecha').val(),
            id_tecnico: $('#nsTecnico').val(),
            id_cliente: $('#nsCliente').val(),
            id_punto: $('#nsPunto').val(),
            id_maquina: $('#nsMaquina').val(),
            id_tipo_mantenimiento: $('#nsMantenimiento').val()
        };

        if(!data.id_tecnico || !data.id_cliente || !data.id_punto || !data.id_maquina || !data.id_tipo_mantenimiento) {
            alert("Por favor, complete todos los campos.");
            return;
        }

        $('.btn-guardar-modal').prop('disabled', true).text('Guardando...');

        $.ajax({
            url: 'index.php?pagina=panelSupervisor&accion=ajaxGuardarServicio',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    cerrarModalNuevoServicio();
                    cargarServiciosSupervisor(); // Recarga el Kanban al instante
                } else {
                    alert("Error: " + res.msj);
                }
            },
            error: function() { alert("Error de red al guardar."); },
            complete: function() { $('.btn-guardar-modal').prop('disabled', false).text('Agendar Servicio'); }
        });
    }
</script>