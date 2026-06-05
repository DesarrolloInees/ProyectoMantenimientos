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
</style>

<div class="w-full max-w-5xl mx-auto px-2 py-4 md:py-6">
    <div
        class="bg-gradient-to-r from-gray-800 to-gray-700 rounded-xl p-5 mb-6 shadow-lg flex items-center justify-between">
        <h1 class="text-white font-bold text-xl"><i class="fas fa-truck-loading mr-2 text-indigo-400"></i> Registro de
            Transporte</h1>
    </div>

    <form action="index.php?pagina=transporteCrear&accion=guardar" method="POST" id="formInstalacion"
        enctype="multipart/form-data">

        <input type="hidden" name="texto_remision" id="texto_remision" value="SIN_REMISION">

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-info-circle"></i> Datos Generales</div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="form-label">Categoría del Servicio <span class="req">*</span></label>
                    <select id="categoria_servicio" name="categoria_servicio" class="form-control" required
                        onchange="cambiarContexto()">
                        <option value="">— Seleccione la categoría —</option>
                        <optgroup label="Prosegur">
                            <option value="Prosegur_Cobro">Prosegur - Con Cobro</option>
                            <option value="Prosegur_NoCobro">Prosegur - Sin Cobro</option>
                        </optgroup>
                        <optgroup label="Inees">
                            <option value="Inees">Transportes Inees (Internos)</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="form-label">Técnico Asignado <span class="req">*</span></label>
                    <select id="sel_tecnico" name="id_tecnico" class="form-control select2-field" required>
                        <option value="">— Seleccione —</option>
                        <?php foreach ($tecnicos as $t): ?>
                            <option value="<?= $t['id_tecnico'] ?>"><?= htmlspecialchars($t['nombre_tecnico']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Número Remisión</label>
                    <select id="sel_remision" name="id_control_remision" class="form-control select2-field">
                        <option value="">— Elija técnico primero —</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Fecha de Realización <span class="req">*</span></label>
                    <input type="date" name="fecha_instalacion" class="form-control" value="<?= date('Y-m-d') ?>"
                        required>
                </div>

                <div id="div_tipo_cobro" style="display:none;">
                    <label class="form-label">Tipo Servicio (Cobro)</label>
                    <select id="tipo_servicio_cobro" name="tipo_servicio_cobro" class="form-control">
                        <option value="">— Seleccione el servicio —</option>
                        <option value="Desmonte Definitivo">Desmonte Definitivo</option>
                        <option value="Desmonte Provicional">Desmonte Provicional</option>
                        <option value="Cambio - Instalación">Cambio de Máquina (Instalación)</option>
                        <option value="Cambio - Desinstalación">Cambio de Máquina (Desinstalación)</option>
                        <option value="Instalación Punto Nuevo">Instalación Punto Nuevo</option>
                        <option value="Traslados Prosegur">Traslados Prosegur</option>
                    </select>
                </div>
                <div id="div_tipo_nocobro" style="display:none;">
                    <label class="form-label">Tipo Servicio (No Cobro)</label>
                    <select name="tipo_servicio_nocobro" class="form-control">
                        <option value="Maquinas Para Bodegaje">Maquinas Para Bodegaje</option>
                        <option value="Máquinas Para Recuperar">Máquinas Para Recuperar</option>
                    </select>
                </div>
                <div id="div_tipo_inees" style="display:none;">
                    <label class="form-label">Tipo Servicio (Inees)</label>
                    <select name="tipo_servicio_inees" class="form-control">
                        <option value="Malaver">Malaver</option>
                        <option value="Internos Don Antonio Osorio">Internos Don Antonio Osorio</option>
                        <option value="Internos Inees">Internos Inees</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="bloque_inees" class="form-section" style="display:none;">
            <div class="form-section-title"><i class="fas fa-building"></i> Detalles Inees</div>
            <label class="form-label">Descripción de lo que se hizo</label>
            <textarea name="descripcion_inees" rows="3" class="form-control"
                placeholder="Detalle las actividades internas realizadas..."></textarea>
        </div>

        <div id="bloque_prosegur" style="display:none;">
            <div class="form-section">
                <div class="form-section-title"><i class="fas fa-box"></i> Logística y Producto</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="form-label">Lugar de Recogida</label>
                        <select name="lugar_recogida" class="form-control">
                            <option value="">— Seleccione —</option>
                            <option value="Innes">Innes</option>
                            <option value="Prosegur">Prosegur</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Fecha de Recogida</label>
                        <input type="date" name="fecha_recogida" class="form-control">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Producto a Enviar</label>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-sm">
                            <input type="radio" name="es_maquina" value="1" checked onchange="toggleProducto()"
                                class="w-4 h-4 text-indigo-600"> Máquina
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-sm">
                            <input type="radio" name="es_maquina" value="0" onchange="toggleProducto()"
                                class="w-4 h-4 text-indigo-600"> Otros
                        </label>
                    </div>

                    <div id="div_maquina" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Tipo de Máquina</label>
                            <select name="id_tipo_maquina" class="form-control select2-field w-full">
                                <option value="">— Seleccione máquina —</option>
                                <?php foreach ($tiposMaquina as $tm): ?>
                                    <option value="<?= $tm['id_tipo_maquina'] ?>">
                                        <?= htmlspecialchars($tm['nombre_tipo_maquina']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Serial Físico</label>
                            <input type="text" name="serial_maquina" class="form-control" placeholder="Ej: SN-12345">
                        </div>
                    </div>

                    <div id="div_otros" style="display:none;">
                        <label class="form-label">Descripción del Producto</label>
                        <input type="text" name="producto_otro" class="form-control"
                            placeholder="Describa qué transportó (Ej: Repuestos, Cables...)">
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
                                    <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre_cliente']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Punto Origen</label>
                            <select id="punto_origen" name="punto_origen" class="form-control select2-dinamico w-full">
                                <option value="">— Seleccione cliente primero —</option>
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
                                    <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre_cliente']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Punto Destino</label>
                            <select id="punto_destino" name="punto_destino"
                                class="form-control select2-dinamico w-full">
                                <option value="">— Seleccione cliente primero —</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-comment-alt"></i> Observaciones</div>
            <textarea name="notas" rows="2" class="form-control" placeholder="Notas opcionales..."></textarea>
        </div>

        <div class="form-section" id="seccion_evidencias" style="display:none;">
            <div class="form-section-title"><i class="fas fa-camera"></i> Evidencias Obligatorias</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Foto Remisión <span class="req req-evidencia">*</span></label>
                    <input type="file" name="foto_remision" id="foto_remision"
                        class="form-control !p-1 cursor-pointer file-evidencia" accept="image/*,application/pdf">
                </div>
                <div>
                    <label class="form-label">Foto Máquina <span class="req req-evidencia">*</span></label>
                    <input type="file" name="foto_maquina" id="foto_maquina"
                        class="form-control !p-1 cursor-pointer file-evidencia" accept="image/*">
                </div>
                <div>
                    <label class="form-label">Foto Chazos <span class="req req-evidencia">*</span></label>
                    <input type="file" name="foto_chazos" id="foto_chazos"
                        class="form-control !p-1 cursor-pointer file-evidencia" accept="image/*">
                </div>
            </div>
            <p class="text-xs text-red-500 mt-2 font-bold"><i class="fas fa-exclamation-triangle"></i> Estas fotos son
                obligatorias cuando se transporta una máquina.</p>
        </div>

        <div class="flex justify-center mt-6 mb-10">
            <button type="submit" id="btnGuardar"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition">
                <i class="fas fa-save mr-2"></i> GUARDAR REGISTRO
            </button>
        </div>

    </form>
</div>

<script>
    $(document).ready(function () {
        // Inicializar Select2 Estándar
        $('.select2-field').select2({ placeholder: 'Buscar...', allowClear: true, width: '100%' });

        // Inicializar Select2 Dinámicos (Permite escribir texto libre)
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

        // Remisiones por técnico
        $('#sel_tecnico').on('change', function () {
            var idTecnico = $(this).val();
            var $sel = $('#sel_remision');
            $sel.empty().append('<option value="">Cargando...</option>');
            if (!idTecnico) {
                $sel.empty().append('<option value="">— Elija técnico primero —</option>');
                return;
            }
            $.post('index.php?pagina=transporteCrear&accion=ajaxRemisiones', { id_tecnico: idTecnico }, function (data) {
                $sel.empty().append('<option value="">— Sin remisión —</option>');
                if (data && data.length > 0) {
                    $.each(data, function (i, r) { $sel.append('<option value="' + r.id_control + '">' + r.numero_remision + '</option>'); });
                }
            }, 'json');
        });

        // Puntos Dinámicos Origen
        $('#cliente_origen').on('change', function () { cargarPuntosDinamicos($(this).val(), '#punto_origen'); });

        // Puntos Dinámicos Destino
        $('#cliente_destino').on('change', function () { cargarPuntosDinamicos($(this).val(), '#punto_destino'); });
    });

    // Cargar puntos dinámicos
    function cargarPuntosDinamicos(valorCliente, selectorPunto) {
        var $selPunto = $(selectorPunto);
        $selPunto.empty().append('<option value="">Cargando puntos...</option>');

        if (!valorCliente || isNaN(valorCliente)) {
            $selPunto.empty().append('<option value="">— Escriba el nombre del punto —</option>');
            return;
        }

        $.post('index.php?pagina=transporteCrear&accion=ajaxPuntos', { id_cliente: valorCliente }, function (data) {
            $selPunto.empty().append('<option value="">— Seleccione o Escriba —</option>');
            if (data && data.length > 0) {
                $.each(data, function (i, p) {
                    $selPunto.append('<option value="' + p.id_punto + '">' + p.nombre_punto + '</option>');
                });
            }
        }, 'json');
    }

    // Capturar el texto de la remisión al seleccionarla
    $('#sel_remision').on('change', function () {
        var textoOpcion = $(this).find('option:selected').text();
        if (!$(this).val() || textoOpcion.includes('Sin remisión') || textoOpcion.includes('Elija técnico')) {
            $('#texto_remision').val('SIN_REMISION');
        } else {
            $('#texto_remision').val(textoOpcion);
        }
    });

    // Toggle de Categoría
    function cambiarContexto() {
        var cat = $('#categoria_servicio').val();

        // Ocultar todos los tipos primero
        $('#div_tipo_cobro, #div_tipo_nocobro, #div_tipo_inees').hide();
        $('#bloque_inees, #bloque_prosegur').hide();

        if (cat === 'Inees') {
            $('#div_tipo_inees').show();
            $('#bloque_inees').fadeIn();

            // Ocultamos evidencias para Inees
            $('#seccion_evidencias').hide();
            $('.file-evidencia').prop('required', false);
        } else if (cat === 'Prosegur_Cobro' || cat === 'Prosegur_NoCobro') {
            if (cat === 'Prosegur_Cobro') {
                $('#div_tipo_cobro').show();
            } else {
                $('#div_tipo_nocobro').show();
            }
            $('#bloque_prosegur').fadeIn();

            // Verificamos si es máquina para mostrar evidencias
            toggleProducto();
        } else {
            $('#seccion_evidencias').hide();
            $('.file-evidencia').prop('required', false);
        }
    }

    // Toggle Máquina vs Otro
    function toggleProducto() {
        var cat = $('#categoria_servicio').val();
        if (cat === 'Inees') return;

        var esMaquina = $('input[name="es_maquina"]:checked').val();
        if (esMaquina === '1') {
            $('#div_otros').hide();
            $('#div_maquina').show();

            // Mostrar y volver obligatorias las fotos
            $('#seccion_evidencias').show();
            $('.file-evidencia').prop('required', true);
        } else {
            $('#div_maquina').hide();
            $('#div_otros').show();

            // Ocultar y quitar obligatoriedad
            $('#seccion_evidencias').hide();
            $('.file-evidencia').prop('required', false);
        }
    }

    // Submit Seguro
    $('#formInstalacion').on('submit', function () {
        var btn = document.getElementById('btnGuardar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';
    });
</script>