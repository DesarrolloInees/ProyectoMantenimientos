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

    .tipo-btn.active-cambio-de-maquina {
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
    <div
        class="bg-gradient-to-r from-gray-800 to-gray-700 rounded-xl p-5 mb-6 shadow-lg flex justify-between items-center">
        <div>
            <h1 class="text-white font-bold text-xl"><i class="fas fa-edit text-amber-400 mr-2"></i> Editar Registro
                #<?= $instalacion['id_instalacion'] ?></h1>
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
                <input type="hidden" name="id_estado_operacion" id="id_estado_operacion" value="<?= htmlspecialchars($instalacion['id_estado_operacion'] ?? '5') ?>">
                
                <div class="tipo-operacion-group">
                    <button type="button" class="tipo-btn" data-valor="5" data-tipo="instalacion" onclick="seleccionarTipo(this)">
                        <i class="fas fa-plus-circle mr-1"></i> Instalación
                    </button>
                    <button type="button" class="tipo-btn" data-valor="6" data-tipo="desinstalacion" onclick="seleccionarTipo(this)">
                        <i class="fas fa-minus-circle mr-1"></i> Desinstalación
                    </button>
                    <button type="button" class="tipo-btn" data-valor="7" data-tipo="traslado" onclick="seleccionarTipo(this)">
                        <i class="fas fa-exchange-alt mr-1"></i> Cambio Máquina
                    </button>
                </div>
            </div>
            
            <div class="grid-3">
                <div>
                    <label class="form-label">Fecha Solicitud <span class="req">*</span></label>
                    <input type="date" name="fecha_solicitud" class="form-control"
                        value="<?= htmlspecialchars($instalacion['fecha_solicitud']) ?>" required>
                </div>
                <div>
                    <label class="form-label">Fecha Ejecución</label>
                    <input type="date" name="fecha_ejecucion" class="form-control"
                        value="<?= htmlspecialchars($instalacion['fecha_ejecucion'] ?? '') ?>">
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
                    <input type="text" name="serial_maquina" class="form-control"
                        value="<?= htmlspecialchars($instalacion['serial_maquina']) ?>">
                </div>
                <div>
                    <label class="form-label">Tipo de Máquina <span class="req">*</span></label>
                    <select name="id_tipo_maquina" class="form-control select2-field" required>
                        <option value="">— Seleccione —</option>
                        <?php foreach ($tiposMaquina as $tm): ?>
                            <option value="<?= $tm['id_tipo_maquina'] ?>"
                                <?= $tm['id_tipo_maquina'] == $instalacion['id_tipo_maquina'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tm['nombre_tipo_maquina']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Reemplaza el de Tipo de Servicio con este botón -->
                <div>
                    <label class="form-label">Capacitación al Cliente</label>
                    <button type="button" onclick="toggleModalCapacitacion()"
                        class="w-full bg-indigo-50 border border-indigo-200 text-indigo-700 py-2 rounded-lg hover:bg-indigo-100 transition text-sm font-bold flex justify-center items-center gap-2 cursor-pointer"
                        style="min-height: 42px;">
                        <i class="fas fa-graduation-cap text-indigo-500"></i> Ver / Editar Capacitación
                    </button>
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
                            <option value="<?= $d['id_delegacion'] ?>"
                                <?= $d['id_delegacion'] == $instalacion['id_delegacion_origen'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nombre_delegacion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Delegación Destino</label>
                    <select name="id_delegacion_destino" class="form-control select2-field">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($delegaciones as $d): ?>
                            <option value="<?= $d['id_delegacion'] ?>"
                                <?= $d['id_delegacion'] == $instalacion['id_delegacion_destino'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nombre_delegacion']) ?>
                            </option>
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
                            <option value="<?= $p['id_punto'] ?>" data-dir="<?= htmlspecialchars($p['direccion']) ?>"
                                <?= $p['id_punto'] == $instalacion['id_punto'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre_punto']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Dirección Punto</label>
                    <input type="text" id="direccion_punto" class="form-control" readonly
                        value="<?= htmlspecialchars($instalacion['direccion_punto'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-dollar-sign"></i> Valor y Observaciones</div>
            <div class="grid-2">
                <div>
                    <label class="form-label">Valor del Servicio</label>
                    <input type="text" id="valor_servicio" name="valor_servicio" class="form-control"
                        value="<?= number_format($instalacion['valor_servicio'], 0, '', '.') ?>"
                        oninput="formatearValor(this)">
                </div>
                <div>
                    <label class="form-label">Comentarios</label>
                    <textarea name="comentarios" rows="3"
                        class="form-control"><?= htmlspecialchars($instalacion['comentarios']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-center mt-6 mb-10">
            <button type="submit" class="btn-guardar"><i class="fas fa-save mr-2"></i> ACTUALIZAR REGISTRO</button>
        </div>
    </form>


    <!-- Mini Modal Capacitación -->
    <div id="modalCapacitacion"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 transform transition-all">

            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-lg font-bold text-gray-800"><i
                        class="fas fa-chalkboard-teacher mr-2 text-indigo-500"></i>Detalles de Capacitación</h3>
                <button type="button" onclick="toggleModalCapacitacion()" class="text-gray-400 hover:text-red-500">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="incluye_capacitacion" id="incluye_capacitacion" form="formInstalacion"
                        value="1" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                        <?= ($instalacion['incluye_capacitacion'] == 1) ? 'checked' : '' ?>>
                    <span class="text-sm font-bold text-gray-700 uppercase tracking-wide">Sí, se brindó
                        capacitación</span>
                </label>
            </div>

            <!-- Campos (Si viene checked de la BD, no le ponemos opacidad) -->
            <div id="camposCapacitacion"
                class="space-y-4 transition-opacity <?= ($instalacion['incluye_capacitacion'] == 1) ? '' : 'opacity-50 pointer-events-none' ?>">
                <div>
                    <label class="form-label">Tema Principal</label>
                    <input type="text" name="tema_capacitacion" id="tema_capacitacion" form="formInstalacion"
                        class="form-control" placeholder="Ej: Uso básico, Limpieza, etc."
                        value="<?= htmlspecialchars($instalacion['tema_capacitacion'] ?? '') ?>">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">N° Asistentes</label>
                        <input type="number" name="cantidad_asistentes" id="cantidad_asistentes" form="formInstalacion"
                            class="form-control" placeholder="Ej: 3" min="1"
                            value="<?= htmlspecialchars($instalacion['cantidad_asistentes'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">Duración (Horas)</label>
                        <input type="number" step="0.5" name="horas_capacitacion" id="horas_capacitacion"
                            form="formInstalacion" class="form-control" placeholder="Ej: 1.5" min="0"
                            value="<?= htmlspecialchars($instalacion['horas_capacitacion'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="toggleModalCapacitacion()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-bold text-sm transition">CERRAR</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.select2-field').select2({
            width: '100%',
            allowClear: true,
            placeholder: 'Buscar...'
        });

        // Cargar remisiones al cambiar técnico
        $('#sel_tecnico').on('change', function () {
            var idTecnico = $(this).val();
            var $sel = $('#sel_remision');
            $sel.empty().append('<option value="">Cargando...</option>');
            if (!idTecnico) {
                $sel.empty().append('<option value="">— Seleccione —</option>');
                return;
            }
            $.post('index.php?pagina=transporteEditar&accion=ajaxRemisiones', {
                id_tecnico: idTecnico
            }, function (data) {
                $sel.empty().append('<option value="">— Sin remisión —</option>');
                $.each(data, function (i, r) {
                    $sel.append('<option value="' + r.id_control + '">' + r.numero_remision + '</option>');
                });
            }, 'json');
        });

        // Cargar puntos al cambiar cliente
        $('#sel_cliente').on('change', function () {
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
            }, function (data) {
                $selPunto.empty().append('<option value="">— Seleccione —</option>');
                $.each(data, function (i, p) {
                    $selPunto.append('<option value="' + p.id_punto + '" data-dir="' + (p.direccion || '') + '">' + p.nombre_punto + '</option>');
                });
            }, 'json');
        });

        // Mostrar dirección al cambiar punto
        $('#sel_punto').on('change', function () {
            var dir = $(this).find('option:selected').data('dir');
            $('#direccion_punto').val(dir || '');
        });
    });

    function toggleModalCapacitacion() {
        document.getElementById('modalCapacitacion').classList.toggle('hidden');
    }

    document.getElementById('incluye_capacitacion').addEventListener('change', function () {
        const divCampos = document.getElementById('camposCapacitacion');
        if (this.checked) {
            divCampos.classList.remove('opacity-50', 'pointer-events-none');
        } else {
            divCampos.classList.add('opacity-50', 'pointer-events-none');
            document.getElementById('tema_capacitacion').value = '';
            document.getElementById('cantidad_asistentes').value = '';
            document.getElementById('horas_capacitacion').value = '';
        }
    });

    // ── Inicializar el botón correcto al abrir Editar ──
    function initTipoOperacion() {
        var idGuardado = document.getElementById('id_estado_operacion').value;
        var btnEncontrado = document.querySelector('.tipo-btn[data-valor="' + idGuardado + '"]');
        
        if (btnEncontrado) {
            seleccionarTipo(btnEncontrado); // Activa el botón correcto
        } else {
            // Si por algún error no hay, seleccionamos el 5 por defecto
            seleccionarTipo(document.querySelector('.tipo-btn[data-valor="5"]'));
        }
    }

    // ── Selección de tipo de operación ──
    function seleccionarTipo(btn) {
        var idValor = btn.dataset.valor; 
        var tipoTxt = btn.dataset.tipo;  

        // Quitar clases activas de todos
        document.querySelectorAll('.tipo-btn').forEach(function (b) {
            b.classList.remove('active-instalacion', 'active-desinstalacion', 'active-traslado');
        });

        // Agregar clase al seleccionado
        btn.classList.add('active-' + tipoTxt);

        // Actualizar hidden input con el ID NUMÉRICO
        document.getElementById('id_estado_operacion').value = idValor;

        // Actualizar badge header
        var badge = document.getElementById('badgeOperacion');
        if(badge) {
            var iconos = {
                instalacion: { cls: 'bg-emerald-100 text-emerald-800', icon: 'fas fa-circle text-emerald-500', txt: 'Instalación' },
                desinstalacion: { cls: 'bg-red-100 text-red-800', icon: 'fas fa-circle text-red-500', txt: 'Desinstalación' },
                traslado: { cls: 'bg-amber-100 text-amber-800', icon: 'fas fa-circle text-amber-500', txt: 'Cambio de Máquina' }
            };
            var cfg = iconos[tipoTxt];
            badge.className = 'badge-operacion ' + cfg.cls;
            badge.innerHTML = '<i class="' + cfg.icon + '" style="font-size:0.5rem"></i> ' + cfg.txt;
        }
    }

    // Llamamos a la inicialización apenas cargue el documento
    document.addEventListener("DOMContentLoaded", function() {
        initTipoOperacion();
    });

    function formatearValor(input) {
        var num = parseInt(input.value.replace(/[^\d]/g, '') || '0', 10);
        input.value = num.toLocaleString('es-CO');
    }
</script>