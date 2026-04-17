<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* ── Mismos estilos que en Crear ── */
    .select2-container .select2-selection--single {
        height: 100% !important;
        min-height: 42px !important;
        display: flex !important;
        align-items: center !important;
        border-color: #d1d5db !important;
        border-radius: 0.5rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        padding-right: 24px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
    }

    .form-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
    }

    .form-section-title {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-label {
        display: block;
        font-size: 0.72rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.3rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .form-label .req {
        color: #ef4444;
        margin-left: 2px;
    }

    .form-control {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.875rem;
        min-height: 42px;
        outline: none;
    }

    .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }

    .form-control[readonly] {
        background: #f9fafb;
        color: #6b7280;
        cursor: not-allowed;
    }

    .tipo-operacion-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .tipo-btn {
        flex: 1;
        padding: 0.65rem 1rem;
        border-radius: 0.5rem;
        border: 2px solid #e5e7eb;
        background: #f9fafb;
        color: #6b7280;
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        text-transform: uppercase;
    }

    .tipo-btn.active-instalacion {
        border-color: #059669;
        background: #ecfdf5;
        color: #065f46;
    }

    .tipo-btn.active-desinstalacion {
        border-color: #dc2626;
        background: #fef2f2;
        color: #991b1b;
    }

    .tipo-btn.active-traslado {
        border-color: #d97706;
        background: #fffbeb;
        color: #92400e;
    }

    .btn-guardar {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
        font-weight: 700;
        padding: 0.85rem 2.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
    }

    .btn-guardar:hover {
        opacity: 0.92;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    @media (max-width: 768px) {

        .grid-2,
        .grid-3 {
            grid-template-columns: 1fr;
        }

        .tipo-operacion-group {
            flex-direction: column;
        }
    }
</style>

<div class="w-full max-w-5xl mx-auto px-2 py-4 md:py-6">
    <div class="bg-gradient-to-r from-gray-800 to-gray-700 rounded-xl p-5 mb-6 shadow-lg flex justify-between items-center">
        <div>
            <h1 class="text-white font-bold text-xl"><i class="fas fa-edit text-amber-400 mr-2"></i> Editar Registro #<?= $instalacion['id_instalacion'] ?></h1>
            <p class="text-gray-400 text-xs mt-1">Actualice los datos necesarios y guarde los cambios.</p>
        </div>
        <a href="transporteVer" class="text-white text-sm hover:underline"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <form action="index.php?pagina=transporteEditar&accion=actualizar" method="POST" id="formInstalacion">
        <input type="hidden" name="id_instalacion" value="<?= $instalacion['id_instalacion'] ?>">

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-tag"></i> Tipo y Fechas</div>
            <div class="mb-4">
                <label class="form-label">Tipo de Operación <span class="req">*</span></label>
                <input type="hidden" name="tipo_operacion" id="tipo_operacion" value="<?= htmlspecialchars($instalacion['tipo_operacion']) ?>">
                <div class="tipo-operacion-group">
                    <button type="button" class="tipo-btn <?= $instalacion['tipo_operacion'] == 'instalacion' ? 'active-instalacion' : '' ?>" data-valor="instalacion" onclick="seleccionarTipo(this)"><i class="fas fa-plus-circle"></i> Instalación</button>
                    <button type="button" class="tipo-btn <?= $instalacion['tipo_operacion'] == 'desinstalacion' ? 'active-desinstalacion' : '' ?>" data-valor="desinstalacion" onclick="seleccionarTipo(this)"><i class="fas fa-minus-circle"></i> Desinstalación</button>
                    <button type="button" class="tipo-btn <?= $instalacion['tipo_operacion'] == 'traslado' ? 'active-traslado' : '' ?>" data-valor="traslado" onclick="seleccionarTipo(this)"><i class="fas fa-exchange-alt"></i> Traslado</button>
                </div>
            </div>
            <div class="grid-3">
                <div>
                    <label class="form-label">Fecha Solicitud <span class="req">*</span></label>
                    <input type="date" name="fecha_solicitud" class="form-control" value="<?= htmlspecialchars($instalacion['fecha_solicitud']) ?>" required>
                </div>
                <div>
                    <label class="form-label">Fecha Ejecución</label>
                    <input type="date" name="fecha_ejecucion" class="form-control" value="<?= htmlspecialchars($instalacion['fecha_ejecucion'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Técnico Asignado <span class="req">*</span></label>
                    <select id="sel_tecnico" name="id_tecnico" class="form-control select2-field" required>
                        <option value="">— Seleccione técnico —</option>
                        <?php foreach ($tecnicos as $t): ?>
                            <option value="<?= $t['id_tecnico'] ?>" <?= $t['id_tecnico'] == $instalacion['id_tecnico'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre_tecnico']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-file-alt"></i> Remisión</div>
            <div class="grid-2">
                <div>
                    <label class="form-label">Remisión del Técnico</label>
                    <select id="sel_remision" name="id_control_remision" class="form-control select2-field">
                        <option value="">— Sin remisión —</option>
                        <?php foreach ($remisionesTecnico as $r): ?>
                            <option value="<?= $r['id_control'] ?>" <?= $r['id_control'] == $instalacion['id_control_remision'] ? 'selected' : '' ?>><?= htmlspecialchars($r['numero_remision']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-desktop"></i> Datos Máquina</div>
            <div class="grid-3">
                <div>
                    <label class="form-label">Serial Físico</label>
                    <input type="text" name="serial_maquina" class="form-control" value="<?= htmlspecialchars($instalacion['serial_maquina']) ?>">
                </div>
                <div>
                    <label class="form-label">Tipo de Máquina <span class="req">*</span></label>
                    <select name="id_tipo_maquina" class="form-control select2-field" required>
                        <option value="">— Seleccione —</option>
                        <?php foreach ($tiposMaquina as $tm): ?>
                            <option value="<?= $tm['id_tipo_maquina'] ?>" <?= $tm['id_tipo_maquina'] == $instalacion['id_tipo_maquina'] ? 'selected' : '' ?>><?= htmlspecialchars($tm['nombre_tipo_maquina']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Tipo de Servicio</label>
                    <select name="id_tipo_servicio" class="form-control select2-field">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($tiposServicio as $ts): ?>
                            <option value="<?= $ts['id_tipo_servicio'] ?>" <?= $ts['id_tipo_servicio'] == $instalacion['id_tipo_servicio'] ? 'selected' : '' ?>><?= htmlspecialchars($ts['nombre_servicio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-map-marker-alt"></i> Delegaciones y Destino</div>
            <div class="grid-2 mb-4">
                <div>
                    <label class="form-label">Delegación Origen <span class="req">*</span></label>
                    <select name="id_delegacion_origen" class="form-control select2-field" required>
                        <?php foreach ($delegaciones as $d): ?>
                            <option value="<?= $d['id_delegacion'] ?>" <?= $d['id_delegacion'] == $instalacion['id_delegacion_origen'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nombre_delegacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Delegación Destino</label>
                    <select name="id_delegacion_destino" class="form-control select2-field">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($delegaciones as $d): ?>
                            <option value="<?= $d['id_delegacion'] ?>" <?= $d['id_delegacion'] == $instalacion['id_delegacion_destino'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nombre_delegacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid-3">
                <div>
                    <label class="form-label">Cliente</label>
                    <select id="sel_cliente" name="id_cliente" class="form-control select2-field">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id_cliente'] ?>" <?= $c['id_cliente'] == $instalacion['id_cliente'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre_cliente']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Punto</label>
                    <select id="sel_punto" name="id_punto" class="form-control select2-field">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($puntosCliente as $p): ?>
                            <option value="<?= $p['id_punto'] ?>" data-dir="<?= htmlspecialchars($p['direccion']) ?>" <?= $p['id_punto'] == $instalacion['id_punto'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre_punto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Dirección Punto</label>
                    <input type="text" id="direccion_punto" class="form-control" readonly value="<?= htmlspecialchars($instalacion['direccion_punto'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-dollar-sign"></i> Valor y Observaciones</div>
            <div class="grid-2">
                <div>
                    <label class="form-label">Valor del Servicio</label>
                    <input type="text" id="valor_servicio" name="valor_servicio" class="form-control" value="<?= number_format($instalacion['valor_servicio'], 0, '', '.') ?>" oninput="formatearValor(this)">
                </div>
                <div>
                    <label class="form-label">Comentarios</label>
                    <textarea name="comentarios" rows="3" class="form-control"><?= htmlspecialchars($instalacion['comentarios']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-center mt-6 mb-10">
            <button type="submit" class="btn-guardar"><i class="fas fa-save mr-2"></i> ACTUALIZAR REGISTRO</button>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('.select2-field').select2({
            width: '100%',
            allowClear: true,
            placeholder: 'Buscar...'
        });

        // Cargar remisiones al cambiar técnico
        $('#sel_tecnico').on('change', function() {
            var idTecnico = $(this).val();
            var $sel = $('#sel_remision');
            $sel.empty().append('<option value="">Cargando...</option>');
            if (!idTecnico) {
                $sel.empty().append('<option value="">— Seleccione —</option>');
                return;
            }
            $.post('index.php?pagina=transporteEditar&accion=ajaxRemisiones', {
                id_tecnico: idTecnico
            }, function(data) {
                $sel.empty().append('<option value="">— Sin remisión —</option>');
                $.each(data, function(i, r) {
                    $sel.append('<option value="' + r.id_control + '">' + r.numero_remision + '</option>');
                });
            }, 'json');
        });

        // Cargar puntos al cambiar cliente
        $('#sel_cliente').on('change', function() {
            var idCliente = $(this).val();
            var $selPunto = $('#sel_punto');
            $selPunto.empty().append('<option value="">Cargando...</option>');
            $('#direccion_punto').val('');
            if (!idCliente) {
                $selPunto.empty().append('<option value="">— Seleccione —</option>');
                return;
            }
            $.post('index.php?pagina=transporteEditar&accion=ajaxPuntos', {
                id_cliente: idCliente
            }, function(data) {
                $selPunto.empty().append('<option value="">— Seleccione —</option>');
                $.each(data, function(i, p) {
                    $selPunto.append('<option value="' + p.id_punto + '" data-dir="' + (p.direccion || '') + '">' + p.nombre_punto + '</option>');
                });
            }, 'json');
        });

        // Mostrar dirección al cambiar punto
        $('#sel_punto').on('change', function() {
            var dir = $(this).find('option:selected').data('dir');
            $('#direccion_punto').val(dir || '');
        });
    });

    function seleccionarTipo(btn) {
        var valor = btn.dataset.valor;
        $('.tipo-btn').removeClass('active-instalacion active-desinstalacion active-traslado');
        $(btn).addClass('active-' + valor);
        $('#tipo_operacion').val(valor);
    }

    function formatearValor(input) {
        var num = parseInt(input.value.replace(/[^\d]/g, '') || '0', 10);
        input.value = num.toLocaleString('es-CO');
    }
</script>