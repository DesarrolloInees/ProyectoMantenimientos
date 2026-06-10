<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
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
    }

    .form-control {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.875rem;
        min-height: 42px;
        outline: none;
        transition: all 0.2s;
    }

    .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }

    .req {
        color: #ef4444;
        margin-left: 2px;
    }

    /* Resaltar caja de tarifa para el administrativo */
    .caja-tarifa {
        background: #ecfdf5;
        border-color: #34d399;
    }
</style>

<div class="w-full max-w-5xl mx-auto px-2 py-4 md:py-6">
    <div
        class="bg-gradient-to-r from-gray-800 to-gray-700 rounded-xl p-5 mb-6 shadow-lg flex items-center justify-between">
        <div>
            <h1 class="text-white font-bold text-xl"><i class="fas fa-edit mr-2 text-amber-400"></i> Editar Transporte
                #<?= $instalacion['id_instalacion'] ?></h1>
            <p class="text-gray-300 text-sm mt-1">Revisa los datos del técnico y asigna la tarifa de cobro
                correspondiente.</p>
        </div>
        <a href="index.php?pagina=transporteVer"
            class="text-white bg-gray-600 hover:bg-gray-500 px-4 py-2 rounded-lg text-sm font-bold transition"><i
                class="fas fa-arrow-left mr-1"></i> Regresar</a>
    </div>

    <form action="index.php?pagina=transporteEditar&accion=actualizar" method="POST" id="formInstalacion">
        <input type="hidden" name="id_instalacion" value="<?= $instalacion['id_instalacion'] ?>">

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-info-circle"></i> Datos Generales</div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="form-label">Categoría del Servicio <span class="req">*</span></label>
                    <select id="categoria_servicio" name="categoria_servicio" class="form-control" required
                        onchange="cambiarContexto()">
                        <option value="">— Seleccione la categoría —</option>
                        <optgroup label="Prosegur">
                            <option value="Prosegur_Cobro" <?= $instalacion['categoria_servicio'] == 'Prosegur_Cobro' ? 'selected' : '' ?>>Prosegur - Con Cobro</option>
                            <option value="Prosegur_NoCobro" <?= $instalacion['categoria_servicio'] == 'Prosegur_NoCobro' ? 'selected' : '' ?>>Prosegur - Sin Cobro</option>
                        </optgroup>
                        <optgroup label="Inees">
                            <option value="Inees" <?= $instalacion['categoria_servicio'] == 'Inees' ? 'selected' : '' ?>>
                                Transportes Inees (Internos)</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="form-label">Técnico Asignado <span class="req">*</span></label>
                    <select id="sel_tecnico" name="id_tecnico" class="form-control select2-field" required>
                        <option value="">— Seleccione —</option>
                        <?php foreach ($tecnicos as $t): ?>
                            <option value="<?= $t['id_tecnico'] ?>" <?= $t['id_tecnico'] == $instalacion['id_tecnico'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre_tecnico']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Número Remisión</label>
                    <select id="sel_remision" name="id_control_remision" class="form-control select2-field">
                        <option value="">— Sin remisión —</option>
                        <?php foreach ($remisionesTecnico as $r): ?>
                            <option value="<?= $r['id_control'] ?>" <?= $r['id_control'] == $instalacion['id_control_remision'] ? 'selected' : '' ?>><?= htmlspecialchars($r['numero_remision']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Fecha de Realización <span class="req">*</span></label>
                    <input type="date" name="fecha_instalacion" class="form-control"
                        value="<?= htmlspecialchars($instalacion['fecha_instalacion']) ?>" required>
                </div>

                <div id="div_tipo_cobro" style="display:none;">
                    <label class="form-label">Tipo Servicio (Cobro)</label>
                    <select name="tipo_servicio_cobro" class="form-control">
                        <?php $tsc = $instalacion['tipo_servicio_nombre']; ?>
                        <option value="Desmonte Definitivo" <?= $tsc == 'Desmonte Definitivo' ? 'selected' : '' ?>>Desmonte
                            Definitivo</option>
                        <option value="Desmonte Provicional" <?= $tsc == 'Desmonte Provicional' ? 'selected' : '' ?>>
                            Desmonte Provicional</option>
                        <option value="Cambio - Instalación" <?= $tsc == 'Cambio - Instalación' ? 'selected' : '' ?>>Cambio
                            de Máquina (Instalación)</option>
                        <option value="Cambio - Desinstalación" <?= $tsc == 'Cambio - Desinstalación' ? 'selected' : '' ?>>
                            Cambio de Máquina (Desinstalación)</option>
                        <option value="Instalación Punto Nuevo" <?= $tsc == 'Instalación Punto Nuevo' ? 'selected' : '' ?>>
                            Instalación Punto Nuevo</option>
                        <option value="Traslados Prosegur" <?= $tsc == 'Traslados Prosegur' ? 'selected' : '' ?>>Traslados
                            Prosegur</option>
                    </select>
                </div>
                <div id="div_tipo_nocobro" style="display:none;">
                    <label class="form-label">Tipo Servicio (No Cobro)</label>
                    <select name="tipo_servicio_nocobro" class="form-control">
                        <?php $tsnc = $instalacion['tipo_servicio_nombre']; ?>
                        <option value="Maquinas Para Bodegaje" <?= $tsnc == 'Maquinas Para Bodegaje' ? 'selected' : '' ?>>
                            Maquinas Para Bodegaje</option>
                        <option value="Máquinas Para Recuperar" <?= $tsnc == 'Máquinas Para Recuperar' ? 'selected' : '' ?>>Máquinas Para Recuperar</option>
                    </select>
                </div>
                <div id="div_tipo_inees" style="display:none;">
                    <label class="form-label">Tipo Servicio (Inees)</label>
                    <select name="tipo_servicio_inees" class="form-control">
                        <?php $tsi = $instalacion['tipo_servicio_nombre']; ?>
                        <option value="Malaver" <?= $tsi == 'Malaver' ? 'selected' : '' ?>>Malaver</option>
                        <option value="Internos Don Antonio Osorio" <?= $tsi == 'Internos Don Antonio Osorio' ? 'selected' : '' ?>>Internos Don Antonio Osorio</option>
                        <option value="Internos Inees" <?= $tsi == 'Internos Inees' ? 'selected' : '' ?>>Internos Inees
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section caja-tarifa">
            <div class="form-section-title text-emerald-700 !border-emerald-200"><i class="fas fa-hand-holding-usd"></i>
                Tarifa de Cobro</div>
            <div class="w-full md:w-1/3">
                <label class="form-label text-emerald-800">Valor Final del Servicio</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                    <input type="text" name="valor_servicio"
                        class="form-control pl-7 font-bold text-lg text-emerald-600"
                        value="<?= number_format($instalacion['valor_servicio'] ?? 0, 0, '', '.') ?>"
                        oninput="formatearValor(this)">
                </div>
                <p class="text-xs text-emerald-600 mt-1">Escribe la tarifa a cobrar por esta operación.</p>
            </div>
        </div>

        <div id="bloque_inees" class="form-section" style="display:none;">
            <div class="form-section-title"><i class="fas fa-building"></i> Detalles Inees</div>
            <label class="form-label">Descripción de lo que se hizo</label>
            <textarea name="descripcion_inees" rows="3"
                class="form-control"><?= htmlspecialchars($instalacion['descripcion_inees'] ?? '') ?></textarea>
        </div>

        <div id="bloque_prosegur" style="display:none;">

            <div class="form-section">
                <div class="form-section-title"><i class="fas fa-box"></i> Logística y Producto</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="form-label">Lugar de Recogida</label>
                        <select name="lugar_recogida" class="form-control">
                            <option value="">— Seleccione —</option>
                            <option value="Innes" <?= $instalacion['lugar_recogida'] == 'Innes' ? 'selected' : '' ?>>Innes
                            </option>
                            <option value="Prosegur" <?= $instalacion['lugar_recogida'] == 'Prosegur' ? 'selected' : '' ?>>
                                Prosegur</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Fecha de Recogida</label>
                        <input type="date" name="fecha_recogida" class="form-control"
                            value="<?= htmlspecialchars($instalacion['fecha_recogida'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Producto a Enviar</label>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-sm">
                            <input type="radio" name="es_maquina" value="1" <?= $instalacion['es_maquina'] == 1 ? 'checked' : '' ?> onchange="toggleProducto()" class="w-4 h-4 text-indigo-600"> Máquina
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-sm">
                            <input type="radio" name="es_maquina" value="0" <?= $instalacion['es_maquina'] == 0 ? 'checked' : '' ?> onchange="toggleProducto()" class="w-4 h-4 text-indigo-600"> Otros
                        </label>
                    </div>

                    <div id="div_maquina" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Tipo de Máquina</label>
                            <select name="id_tipo_maquina" class="form-control select2-field w-full">
                                <option value="">— Seleccione máquina —</option>
                                <?php foreach ($tiposMaquina as $tm): ?>
                                    <option value="<?= $tm['id_tipo_maquina'] ?>"
                                        <?= $tm['id_tipo_maquina'] == $instalacion['id_tipo_maquina'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tm['nombre_tipo_maquina']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Serial Físico</label>
                            <input type="text" name="serial_maquina" class="form-control"
                                value="<?= htmlspecialchars($instalacion['serial_maquina'] ?? '') ?>">
                        </div>
                    </div>

                    <div id="div_otros" style="display:none;">
                        <label class="form-label">Descripción del Producto</label>
                        <input type="text" name="producto_otro" class="form-control"
                            value="<?= htmlspecialchars($instalacion['producto_otro'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title"><i class="fas fa-map-marker-alt"></i> Origen y Destino</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Datos de Origen</h4>
                        <div class="mb-3">
                            <label class="form-label">Cliente Origen</label>
                            <select id="cliente_origen" name="cliente_origen"
                                class="form-control select2-dinamico w-full">
                                <option value="">— Seleccione o Escriba —</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id_cliente'] ?>"
                                        <?= $c['id_cliente'] == $instalacion['id_cliente_origen'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre_cliente']) ?></option>
                                <?php endforeach; ?>
                                <?php if (empty($instalacion['id_cliente_origen']) && !empty($instalacion['cliente_origen_texto'])): ?>
                                    <option value="<?= htmlspecialchars($instalacion['cliente_origen_texto']) ?>" selected>
                                        <?= htmlspecialchars($instalacion['cliente_origen_texto']) ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Punto Origen</label>
                            <select id="punto_origen" name="punto_origen" class="form-control select2-dinamico w-full">
                                <?php if (!empty($instalacion['punto_origen_texto'])): ?>
                                    <option value="<?= htmlspecialchars($instalacion['punto_origen_texto']) ?>" selected>
                                        <?= htmlspecialchars($instalacion['punto_origen_texto']) ?></option>
                                <?php else: ?>
                                    <option value="">— Seleccione cliente primero —</option>
                                <?php endif; ?>
                                <?php foreach ($puntosOrigen as $p): ?>
                                    <option value="<?= $p['id_punto'] ?>" <?= $p['id_punto'] == $instalacion['id_punto_origen'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre_punto']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Datos de Destino</h4>
                        <div class="mb-3">
                            <label class="form-label">Cliente Destino</label>
                            <select id="cliente_destino" name="cliente_destino"
                                class="form-control select2-dinamico w-full">
                                <option value="">— Seleccione o Escriba —</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id_cliente'] ?>"
                                        <?= $c['id_cliente'] == $instalacion['id_cliente_destino'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre_cliente']) ?></option>
                                <?php endforeach; ?>
                                <?php if (empty($instalacion['id_cliente_destino']) && !empty($instalacion['cliente_destino_texto'])): ?>
                                    <option value="<?= htmlspecialchars($instalacion['cliente_destino_texto']) ?>" selected>
                                        <?= htmlspecialchars($instalacion['cliente_destino_texto']) ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Punto Destino</label>
                            <select id="punto_destino" name="punto_destino"
                                class="form-control select2-dinamico w-full">
                                <?php if (!empty($instalacion['punto_destino_texto'])): ?>
                                    <option value="<?= htmlspecialchars($instalacion['punto_destino_texto']) ?>" selected>
                                        <?= htmlspecialchars($instalacion['punto_destino_texto']) ?></option>
                                <?php else: ?>
                                    <option value="">— Seleccione cliente primero —</option>
                                <?php endif; ?>
                                <?php foreach ($puntosDestino as $p): ?>
                                    <option value="<?= $p['id_punto'] ?>"
                                        <?= $p['id_punto'] == $instalacion['id_punto_destino'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['nombre_punto']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-comment-alt"></i> Observaciones Generales</div>
            <textarea name="notas" rows="2"
                class="form-control"><?= htmlspecialchars($instalacion['notas'] ?? '') ?></textarea>
        </div>

        <div class="flex justify-center mt-6 mb-10">
            <button type="submit" id="btnGuardar"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition">
                <i class="fas fa-save mr-2"></i> ACTUALIZAR REGISTRO Y TARIFA
            </button>
        </div>

    </form>
</div>

<script>
    $(document).ready(function () {
        // Inicializar Select2
        $('.select2-field').select2({ placeholder: 'Buscar...', allowClear: true, width: '100%' });

        $('.select2-dinamico').select2({
            tags: true,
            placeholder: 'Seleccione de la lista o escriba uno nuevo...',
            allowClear: true,
            width: '100%',
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') return null;
                return { id: term, text: term, newTag: true }
            }
        });

        // Eventos para cargar combos
        $('#sel_tecnico').on('change', function () {
            var idTecnico = $(this).val();
            var $sel = $('#sel_remision');
            $sel.empty().append('<option value="">Cargando...</option>');
            if (!idTecnico) {
                $sel.empty().append('<option value="">— Elija técnico primero —</option>'); return;
            }
            $.post('index.php?pagina=transporteEditar&accion=ajaxRemisiones', { id_tecnico: idTecnico }, function (data) {
                $sel.empty().append('<option value="">— Sin remisión —</option>');
                if (data && data.length > 0) {
                    $.each(data, function (i, r) { $sel.append('<option value="' + r.id_control + '">' + r.numero_remision + '</option>'); });
                }
            }, 'json');
        });

        $('#cliente_origen').on('change', function () { cargarPuntosDinamicos($(this).val(), '#punto_origen'); });
        $('#cliente_destino').on('change', function () { cargarPuntosDinamicos($(this).val(), '#punto_destino'); });

        // Ejecutar funciones al arrancar para mostrar bloques según la BD
        cambiarContexto();
        toggleProducto();
    });

    function cargarPuntosDinamicos(valorCliente, selectorPunto) {
        var $selPunto = $(selectorPunto);
        $selPunto.empty().append('<option value="">Cargando puntos...</option>');
        if (!valorCliente || isNaN(valorCliente)) {
            $selPunto.empty().append('<option value="">— Escriba el nombre del punto —</option>');
            return;
        }
        $.post('index.php?pagina=transporteEditar&accion=ajaxPuntos', { id_cliente: valorCliente }, function (data) {
            $selPunto.empty().append('<option value="">— Seleccione o Escriba —</option>');
            if (data && data.length > 0) {
                $.each(data, function (i, p) {
                    $selPunto.append('<option value="' + p.id_punto + '">' + p.nombre_punto + '</option>');
                });
            }
        }, 'json');
    }

    function cambiarContexto() {
        var cat = $('#categoria_servicio').val();
        $('#div_tipo_cobro, #div_tipo_nocobro, #div_tipo_inees').hide();
        $('#bloque_inees, #bloque_prosegur').hide();

        if (cat === 'Inees') {
            $('#div_tipo_inees').show();
            $('#bloque_inees').fadeIn();
        } else if (cat === 'Prosegur_Cobro' || cat === 'Prosegur_NoCobro') {
            if (cat === 'Prosegur_Cobro') $('#div_tipo_cobro').show();
            else $('#div_tipo_nocobro').show();
            $('#bloque_prosegur').fadeIn();
        }
    }

    function toggleProducto() {
        var esMaquina = $('input[name="es_maquina"]:checked').val();
        if (esMaquina === '1') {
            $('#div_otros').hide();
            $('#div_maquina').show();
        } else {
            $('#div_maquina').hide();
            $('#div_otros').show();
        }
    }

    function formatearValor(input) {
        var num = parseInt(input.value.replace(/[^\d]/g, '') || '0', 10);
        input.value = num.toLocaleString('es-CO');
    }

    $('#formInstalacion').on('submit', function () {
        var btn = document.getElementById('btnGuardar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...';
    });
</script>