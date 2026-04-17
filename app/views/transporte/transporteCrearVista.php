<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

<style>
    /* ── Select2 ajuste de altura ── */
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
        text-overflow: ellipsis !important;
        padding-right: 24px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
    }

    .select2-container--open {
        z-index: 99999 !important;
    }

    /* ── Secciones del formulario ── */
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

    .form-section-title i {
        color: #374151;
        font-size: 0.9rem;
    }

    /* ── Etiquetas y campos ── */
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
        color: #111827;
        background: #fff;
        transition: border-color 0.15s, box-shadow 0.15s;
        outline: none;
        min-height: 42px;
    }

    .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }

    .form-control[readonly],
    .form-control:disabled {
        background: #f9fafb;
        color: #6b7280;
        cursor: not-allowed;
    }

    /* ── Tipos de operación (toggle) ── */
    .tipo-operacion-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .tipo-btn {
        flex: 1;
        min-width: 120px;
        padding: 0.65rem 1rem;
        border-radius: 0.5rem;
        border: 2px solid #e5e7eb;
        background: #f9fafb;
        color: #6b7280;
        font-size: 0.8rem;
        font-weight: 700;
        text-align: center;
        cursor: pointer;
        transition: all 0.18s;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .tipo-btn:hover {
        border-color: #9ca3af;
        background: #f3f4f6;
        color: #374151;
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

    /* ── Pill de info readonly ── */
    .info-pill {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.55rem 0.85rem;
        min-height: 42px;
        font-size: 0.8rem;
        color: #374151;
    }

    .info-pill i {
        color: #9ca3af;
        flex-shrink: 0;
    }

    .info-pill span {
        font-weight: 500;
    }

    /* ── Badge de estado ── */
    .badge-operacion {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.2rem 0.65rem;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    /* ── Botón guardar ── */
    .btn-guardar {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
        font-weight: 700;
        font-size: 0.9rem;
        padding: 0.85rem 2.5rem;
        border-radius: 0.75rem;
        border: none;
        cursor: pointer;
        letter-spacing: 0.05em;
        transition: opacity 0.15s, transform 0.15s;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
    }

    .btn-guardar:hover {
        opacity: 0.92;
        transform: translateY(-1px);
    }

    .btn-guardar:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    /* ── Valor dinámico ── */
    #displayValor {
        font-size: 1.4rem;
        font-weight: 800;
        color: #059669;
        letter-spacing: -0.01em;
    }

    /* ── Responsive grid ── */
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

    .grid-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }

    @media (max-width: 768px) {

        .grid-2,
        .grid-3,
        .grid-4 {
            grid-template-columns: 1fr;
        }

        .tipo-operacion-group {
            flex-direction: column;
        }

        .tipo-btn {
            min-width: unset;
        }
    }

    @media (min-width: 768px) and (max-width: 1024px) {
        .grid-3 {
            grid-template-columns: repeat(2, 1fr);
        }

        .grid-4 {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="w-full max-w-5xl mx-auto px-2 py-4 md:py-6">

    <div class="bg-gradient-to-r from-gray-800 to-gray-700 rounded-xl p-5 mb-6 shadow-lg flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-white font-bold text-xl tracking-tight">
                <i class="fas fa-tools mr-2 text-indigo-400"></i>
                Registro de Instalación / Desinstalación
            </h1>
            <p class="text-gray-400 text-xs mt-1">Complete todos los campos requeridos (<span class="text-red-400">*</span>) antes de guardar.</p>
        </div>
        <div id="badgeOperacion" class="badge-operacion bg-emerald-100 text-emerald-800">
            <i class="fas fa-circle text-emerald-500" style="font-size:0.5rem"></i>
            Instalación
        </div>
    </div>

    <form action="index.php?pagina=transporteCrear&accion=guardar" method="POST" id="formInstalacion">

        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-tag"></i> Tipo de Operación y Fechas
            </div>

            <div class="mb-4">
                <label class="form-label">Tipo de Operación <span class="req">*</span></label>
                <input type="hidden" name="tipo_operacion" id="tipo_operacion" value="instalacion">
                <div class="tipo-operacion-group">
                    <button type="button" class="tipo-btn active-instalacion" data-valor="instalacion"
                        onclick="seleccionarTipo(this)">
                        <i class="fas fa-plus-circle mr-1"></i> Instalación
                    </button>
                    <button type="button" class="tipo-btn" data-valor="desinstalacion"
                        onclick="seleccionarTipo(this)">
                        <i class="fas fa-minus-circle mr-1"></i> Desinstalación
                    </button>
                    <button type="button" class="tipo-btn" data-valor="traslado"
                        onclick="seleccionarTipo(this)">
                        <i class="fas fa-exchange-alt mr-1"></i> Traslado
                    </button>
                </div>
            </div>

            <div class="grid-3">
                <div>
                    <label class="form-label" for="fecha_solicitud">Fecha Solicitud <span class="req">*</span></label>
                    <input type="date" id="fecha_solicitud" name="fecha_solicitud"
                        class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="form-label" for="fecha_ejecucion">Fecha Ejecución</label>
                    <input type="date" id="fecha_ejecucion" name="fecha_ejecucion"
                        class="form-control">
                </div>
                <div>
                    <label class="form-label" for="sel_tecnico">Técnico Asignado <span class="req">*</span></label>
                    <select id="sel_tecnico" name="id_tecnico" class="form-control select2-field" required>
                        <option value="">— Seleccione técnico —</option>
                        <?php foreach ($tecnicos as $t): ?>
                            <option value="<?= $t['id_tecnico'] ?>"><?= htmlspecialchars($t['nombre_tecnico']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-file-alt"></i> Número de Remisión
            </div>

            <div class="grid-2">
                <div>
                    <label class="form-label" for="sel_remision">Remisión del Técnico</label>
                    <select id="sel_remision" name="id_control_remision" class="form-control select2-field">
                        <option value="">— Seleccione técnico primero —</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fas fa-info-circle"></i>
                        Solo aparecen las remisiones disponibles del técnico seleccionado.
                    </p>
                </div>
                <div class="flex flex-col justify-center">
                    <div class="info-pill">
                        <i class="fas fa-hashtag"></i>
                        <div>
                            <div class="text-xs text-gray-400 font-semibold uppercase tracking-wide">Número seleccionado</div>
                            <span id="textoRemision" class="font-bold text-gray-700">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-desktop"></i> Datos de la Máquina
            </div>

            <div class="grid-3">
                <div>
                    <label class="form-label" for="serial_maquina">Serial Físico de la Máquina</label>
                    <input type="text" id="serial_maquina" name="serial_maquina"
                        class="form-control" placeholder="Ej: SN-2024-00456"
                        maxlength="40">
                </div>
                <div>
                    <label class="form-label" for="sel_tipo_maquina">Tipo de Máquina <span class="req">*</span></label>
                    <select id="sel_tipo_maquina" name="id_tipo_maquina" class="form-control select2-field" required>
                        <option value="">— Seleccione tipo —</option>
                        <?php foreach ($tiposMaquina as $tm): ?>
                            <option value="<?= $tm['id_tipo_maquina'] ?>"><?= htmlspecialchars($tm['nombre_tipo_maquina']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="sel_tipo_servicio">Descripción del Servicio</label>
                    <select id="sel_tipo_servicio" name="id_tipo_servicio" class="form-control select2-field">
                        <option value="">— Seleccione servicio —</option>
                        <?php foreach ($tiposServicio as $ts): ?>
                            <option value="<?= $ts['id_tipo_servicio'] ?>"><?= htmlspecialchars($ts['nombre_servicio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-map-marker-alt"></i> Origen y Destino
            </div>

            <div class="grid-2 mb-4">
                <div>
                    <label class="form-label" for="sel_delegacion_origen">Delegación Origen <span class="req">*</span></label>
                    <select id="sel_delegacion_origen" name="id_delegacion_origen" class="form-control select2-field" required>
                        <option value="">— Seleccione delegación —</option>
                        <?php foreach ($delegaciones as $d): ?>
                            <option value="<?= $d['id_delegacion'] ?>"><?= htmlspecialchars($d['nombre_delegacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Dirección de Origen</label>
                    <div class="info-pill">
                        <i class="fas fa-warehouse"></i>
                        <span><?= htmlspecialchars($dirOrigen) ?></span>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label class="form-label" for="sel_delegacion_destino">Delegación Destino</label>
                    <select id="sel_delegacion_destino" name="id_delegacion_destino" class="form-control select2-field">
                        <option value="">— Seleccione delegación —</option>
                        <?php foreach ($delegaciones as $d): ?>
                            <option value="<?= $d['id_delegacion'] ?>"><?= htmlspecialchars($d['nombre_delegacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <p class="text-xs text-gray-400 italic">
                        <i class="fas fa-lightbulb text-yellow-400 mr-1"></i>
                        El punto de destino (cliente, nombre, dirección) se define en la sección siguiente.
                    </p>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-building"></i> Punto de Destino (Cliente / Punto)
            </div>

            <div class="grid-3 mb-4">
                <div>
                    <label class="form-label" for="sel_cliente">Cliente</label>
                    <select id="sel_cliente" name="id_cliente" class="form-control select2-field">
                        <option value="">— Seleccione cliente —</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre_cliente']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="sel_punto">Punto</label>
                    <select id="sel_punto" name="id_punto" class="form-control select2-field">
                        <option value="">— Seleccione cliente primero —</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Dirección del Punto</label>
                    <input type="text" id="direccion_punto" class="form-control" readonly
                        placeholder="Se completa al elegir punto...">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-dollar-sign"></i> Valor y Observaciones
            </div>

            <div class="grid-2">
                <div>
                    <label class="form-label" for="valor_servicio">Valor del Servicio</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">$</span>
                        <input type="text" id="valor_servicio" name="valor_servicio"
                            class="form-control pl-7" placeholder="0"
                            oninput="formatearValor(this)">
                    </div>
                    <div class="mt-2">
                        <span id="displayValor">$ 0</span>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="comentarios">Comentarios / Observaciones</label>
                    <textarea id="comentarios" name="comentarios" rows="3"
                        class="form-control resize-none"
                        placeholder="Anote cualquier novedad o detalle adicional del servicio..."></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-center mt-6 mb-10">
            <button type="submit" id="btnGuardar" class="btn-guardar">
                <i class="fas fa-save mr-2"></i> GUARDAR REGISTRO
            </button>
        </div>

    </form>
</div>

<script>
    const BASE_URL_APP = '<?= BASE_URL ?>';
</script>

<script>
    $(document).ready(function() {

        // ── Inicializar Select2 en todos los campos marcados ──
        $('.select2-field').select2({
            placeholder: 'Buscar...',
            allowClear: true,
            width: '100%'
        });

        // ── Cuando cambia el técnico → cargar sus remisiones ──
        $('#sel_tecnico').on('change', function() {
            var idTecnico = $(this).val();
            var $sel = $('#sel_remision');

            $sel.empty().append('<option value="">Cargando...</option>');

            if (!idTecnico) {
                $sel.empty().append('<option value="">— Seleccione técnico primero —</option>');
                return;
            }

            $.post('index.php?pagina=transporteCrear&accion=ajaxRemisiones', {
                id_tecnico: idTecnico
            }, function(data) {
                $sel.empty().append('<option value="">— Sin remisión —</option>');
                if (data && data.length > 0) {
                    $.each(data, function(i, r) {
                        $sel.append('<option value="' + r.id_control + '">' + r.numero_remision + '</option>');
                    });
                } else {
                    $sel.append('<option value="" disabled>Sin remisiones disponibles</option>');
                }
                $sel.trigger('change.select2');
            }, 'json').fail(function() {
                $sel.empty().append('<option value="" disabled>Error al cargar remisiones</option>');
                $sel.trigger('change.select2');
            });
        });

        // ── Cuando cambia la remisión → mostrar el número ──
        $('#sel_remision').on('change', function() {
            var texto = $(this).find('option:selected').text();
            $('#textoRemision').text(texto || '—');
        });

        // ── Cuando cambia el cliente → cargar puntos ──
        $('#sel_cliente').on('change', function() {
            var idCliente = $(this).val();
            var $selPunto = $('#sel_punto');

            $selPunto.empty().append('<option value="">Cargando puntos...</option>');
            $('#direccion_punto').val('');

            if (!idCliente) {
                $selPunto.empty().append('<option value="">— Seleccione cliente primero —</option>');
                return;
            }

            $.post('index.php?pagina=transporteCrear&accion=ajaxPuntos', {
                id_cliente: idCliente
            }, function(data) {
                $selPunto.empty().append('<option value="">— Seleccione punto —</option>');
                if (data && data.length > 0) {
                    $.each(data, function(i, p) {
                        $selPunto.append('<option value="' + p.id_punto + '" data-dir="' + (p.direccion || '') + '">' +
                            p.nombre_punto + '</option>');
                    });
                } else {
                    $selPunto.append('<option value="" disabled>Sin puntos para este cliente</option>');
                }
                $selPunto.trigger('change.select2');
            }, 'json');
        });

        // ── Cuando cambia el punto → mostrar dirección ──
        $('#sel_punto').on('change', function() {
            var $opt = $(this).find('option:selected');
            var dir = $opt.data('dir') || '';

            if (dir) {
                $('#direccion_punto').val(dir);
            } else {
                var idPunto = $(this).val();
                if (!idPunto) {
                    $('#direccion_punto').val('');
                    return;
                }

                $.post('index.php?pagina=transporteCrear&accion=ajaxDetallePunto', {
                    id_punto: idPunto
                }, function(data) {
                    $('#direccion_punto').val(data.direccion || '');
                }, 'json');
            }
        });

    });

    // ── Selección de tipo de operación ──
    function seleccionarTipo(btn) {
        var valor = btn.dataset.valor;

        // Quitar clases activas de todos
        document.querySelectorAll('.tipo-btn').forEach(function(b) {
            b.classList.remove('active-instalacion', 'active-desinstalacion', 'active-traslado');
        });

        // Agregar clase al seleccionado
        btn.classList.add('active-' + valor);

        // Actualizar hidden input
        document.getElementById('tipo_operacion').value = valor;

        // Actualizar badge header
        var badge = document.getElementById('badgeOperacion');
        var iconos = {
            instalacion: {
                cls: 'bg-emerald-100 text-emerald-800',
                icon: 'fas fa-circle text-emerald-500',
                txt: 'Instalación'
            },
            desinstalacion: {
                cls: 'bg-red-100 text-red-800',
                icon: 'fas fa-circle text-red-500',
                txt: 'Desinstalación'
            },
            traslado: {
                cls: 'bg-amber-100 text-amber-800',
                icon: 'fas fa-circle text-amber-500',
                txt: 'Traslado'
            }
        };
        var cfg = iconos[valor];
        badge.className = 'badge-operacion ' + cfg.cls;
        badge.innerHTML = '<i class="' + cfg.icon + '" style="font-size:0.5rem"></i> ' + cfg.txt;
    }

    // ── Formateo de valor monetario ──
    function formatearValor(input) {
        var raw = input.value.replace(/[^\d]/g, '');
        var num = parseInt(raw || '0', 10);
        var formateado = num.toLocaleString('es-CO');

        input.value = formateado;
        document.getElementById('displayValor').textContent = '$ ' + formateado;
    }

    // ── Validación antes de submit ──
    document.getElementById('formInstalacion').addEventListener('submit', function(e) {
        var tipo = document.getElementById('tipo_operacion').value;
        var tecnico = document.getElementById('sel_tecnico').value;
        var tipoMaq = document.getElementById('sel_tipo_maquina').value;
        var delOrig = document.getElementById('sel_delegacion_origen').value;

        if (!tipo || !tecnico || !tipoMaq || !delOrig) {
            e.preventDefault();
            alert('⚠️ Por favor complete los campos obligatorios:\n• Tipo de operación\n• Técnico\n• Tipo de máquina\n• Delegación origen');
            return;
        }

        // Deshabilitar botón para evitar doble submit
        var btn = document.getElementById('btnGuardar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';
    });
</script>