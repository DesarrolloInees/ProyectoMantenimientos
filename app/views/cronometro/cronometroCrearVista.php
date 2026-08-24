<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<style>
    .select2-container .select2-selection--single {
        height: 3rem !important;
        padding: 0.5rem !important;
        border-color: #d1d5db !important;
        border-radius: 0.5rem !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        right: 10px !important;
    }
    body {
        padding-bottom: 90px;
        background-color: #f1f5f9;
    }
    .cronometro-fuente {
        font-family: 'Courier New', Courier, monospace;
        font-variant-numeric: tabular-nums;
    }
</style>

<div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center gap-3">
    <button onclick="window.history.back();"
        class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition">
        <i class="fas fa-arrow-left"></i>
    </button>
    <div>
        <h1 class="font-bold text-lg leading-tight">Control de Servicio</h1>
        <p class="text-blue-200 text-xs">Monitoreo en tiempo real</p>
    </div>
</div>

<div class="max-w-lg mx-auto p-3 space-y-4 mt-2 mb-24">

    <?php if ($servicioActivo): ?>
        <!-- PANTALLA: CRONÓMETRO EN CURSO -->
        <?php $yaModificado = ($servicioActivo['servicio_modificado'] == 1); ?>

        <script>
            const catalogoTiempos = <?= json_encode($catalogoTiempos) ?>;
        </script>

        <form action="index.php?pagina=cronometroCrear&accion=finalizar" method="POST" id="formFinalizar">
            <input type="hidden" name="id_monitoreo" value="<?= $servicioActivo['id_monitoreo'] ?>">
            <input type="hidden" name="hora_inicio_real" value="<?= $servicioActivo['hora_inicio'] ?>">
            <input type="hidden" name="id_tipo_mantenimiento_inicial" value="<?= $servicioActivo['id_tipo_mantenimiento'] ?>">

            <!-- INPUT OCULTO REAL: Garantiza enviar el valor incluso si el select está disabled -->
            <input type="hidden" name="id_tipo_mantenimiento_final" id="hidden_tipo_mantenimiento_final" value="<?= $servicioActivo['id_tipo_mantenimiento'] ?>">

            <div class="bg-white rounded-xl shadow-md border border-blue-200 p-6 text-center space-y-4">
                
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 text-blue-600 rounded-full mb-2">
                    <i class="fas fa-stopwatch text-3xl animate-pulse"></i>
                </div>
                
                <h2 class="text-gray-500 font-bold uppercase text-xs">Tiempo Transcurrido</h2>
                
                <div class="bg-gray-900 text-green-400 rounded-lg py-4 px-2 shadow-inner">
                    <span id="reloj_digital" class="text-5xl font-bold cronometro-fuente tracking-widest" data-inicio="<?= str_replace('-', '/', $servicioActivo['hora_inicio']) ?>">
                        00:00:00
                    </span>
                </div>

                <div class="text-left bg-gray-50 p-4 rounded-lg border border-gray-200 mt-4 text-sm space-y-3">
                    <p><strong class="text-gray-700">Cliente:</strong> <?= htmlspecialchars($servicioActivo['nombre_cliente']) ?></p>
                    <p><strong class="text-gray-700">Punto:</strong> <?= htmlspecialchars($servicioActivo['nombre_punto']) ?></p>
                    
                    <div class="pt-2 border-t border-gray-200">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase">Servicio Realizado <span class="text-red-500">*</span></label>
                            
                            <!-- AVISO DE LÍMITE ALCANZADO -->
                            <span id="badge_limite_cambio" class="<?= $yaModificado ? 'inline-block' : 'hidden' ?> bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-200">
                                <i class="fas fa-lock text-[9px] mr-1"></i> Cambio registrado (1/1)
                            </span>
                        </div>

                        <select id="select_tipo_mantenimiento_visual" class="w-full border-gray-300 rounded-lg select2-movil" <?= $yaModificado ? 'disabled' : '' ?>>
                            <?php foreach ($tiposMantenimiento as $tipo): ?>
                                <option value="<?= htmlspecialchars($tipo['id_tipo_mantenimiento']) ?>" 
                                    <?= ($tipo['id_tipo_mantenimiento'] == $servicioActivo['id_tipo_mantenimiento']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['nombre_completo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Contenedor Dinámico para Justificación -->
                    <div id="contenedor_justificacion" class="hidden pt-3 transition-all duration-300">
                        <div class="bg-red-50 p-3 rounded-lg border border-red-200 shadow-inner">
                            <p class="text-xs text-red-600 font-bold mb-2">
                                <i class="fas fa-exclamation-triangle"></i> Tiempo excedido para este tipo de servicio. Por favor justifica el retraso:
                            </p>
                            <textarea name="justificacion_retraso" id="justificacion_retraso" rows="2" 
                                class="w-full bg-white border border-red-300 rounded-lg p-2 text-sm text-gray-800 outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500" 
                                placeholder="Ej: No abrían la puerta, fallas adicionales encontradas..."></textarea>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Botón para Finalizar -->
            <div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
                <button type="button" onclick="confirmarFinalizar()"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2">
                    <i class="fas fa-flag-checkered"></i> FINALIZAR SERVICIO
                </button>
            </div>
        </form>

    <?php else: ?>
        <!-- PANTALLA: INICIAR NUEVO SERVICIO -->
        <form action="index.php?pagina=cronometroCrear&accion=iniciar" method="POST" id="formIniciar">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-4">
                <div class="flex items-center gap-2 border-b pb-2">
                    <i class="fas fa-play-circle text-green-500 text-lg"></i>
                    <h2 class="font-bold text-gray-700 text-sm uppercase">Nuevo Servicio</h2>
                </div>

                <!-- Cliente -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Cliente <span class="text-red-500">*</span></label>
                    <select name="id_cliente" id="id_cliente" class="w-full border-gray-300 rounded-lg select2-movil" required>
                        <option value="">- Seleccione Cliente -</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?= htmlspecialchars($cli['id_cliente']) ?>">
                                <?= htmlspecialchars($cli['nombre_cliente']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Punto -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Punto Visitado <span class="text-red-500">*</span></label>
                    <select name="id_punto" id="id_punto" class="w-full border-gray-300 rounded-lg select2-movil" required>
                        <option value="">- Seleccione el Punto -</option>
                        <?php foreach ($puntos as $punto): ?>
                            <option value="<?= htmlspecialchars($punto['id_punto']) ?>" data-cliente="<?= $punto['id_cliente'] ?>">
                                <?= htmlspecialchars($punto['nombre_punto']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tipo Mantenimiento -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tipo de Mantenimiento <span class="text-red-500">*</span></label>
                    <select name="id_tipo_mantenimiento" id="id_tipo_mantenimiento" class="w-full border-gray-300 rounded-lg select2-movil" required>
                        <option value="">- Seleccione Tipo -</option>
                        <?php foreach ($tiposMantenimiento as $tipo): ?>
                            <option value="<?= htmlspecialchars($tipo['id_tipo_mantenimiento']) ?>">
                                <?= htmlspecialchars($tipo['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>

        <!-- Botón para Iniciar -->
        <div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
            <button type="button" onclick="validarYIniciar()"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2">
                <i class="fas fa-play"></i> INICIAR CRONÓMETRO
            </button>
        </div>
    <?php endif; ?>

</div>

<script>
    let listaPuntosOriginal = [];

    $(document).ready(function () {
        $('.select2-movil').select2({
            width: '100%',
            language: { noResults: function () { return "No se encontraron resultados"; } }
        });

        <?php if (!$servicioActivo): ?>
            // Logica de filtrado Cliente -> Punto
            $('#id_punto option').each(function() {
                if ($(this).val() !== "") {
                    listaPuntosOriginal.push({
                        val: $(this).val(),
                        text: $(this).text(),
                        cliente: $(this).data('cliente')
                    });
                }
            });

            $('#id_cliente').on('change', function() {
                const idClienteSeleccionado = $(this).val();
                const selectPunto = $('#id_punto');
                selectPunto.empty().append('<option value="">- Seleccione el Punto -</option>');
                listaPuntosOriginal.forEach(function(punto) {
                    if (!idClienteSeleccionado || punto.cliente == idClienteSeleccionado) {
                        selectPunto.append(new Option(punto.text, punto.val, false, false));
                        selectPunto.find(`option[value="${punto.val}"]`).attr('data-cliente', punto.cliente);
                    }
                });
                selectPunto.val('').trigger('change.select2');
            });

            $('#id_punto').on('change', function() {
                const valPunto = $(this).val();
                if (valPunto && !$('#id_cliente').val()) {
                    const puntoObj = listaPuntosOriginal.find(p => p.val == valPunto);
                    if (puntoObj && puntoObj.cliente) {
                        $('#id_cliente').val(puntoObj.cliente).trigger('change.select2');
                        $('#id_punto').val(valPunto).trigger('change.select2');
                    }
                }
            });
        <?php else: ?>
            // Lógica del Cronómetro Vivo
            iniciarReloj();

            // Sincronizar el select visual con el input oculto al cargar
            $('#hidden_tipo_mantenimiento_final').val($('#select_tipo_mantenimiento_visual').val());

            // Evento al cambiar el tipo de servicio
            $('#select_tipo_mantenimiento_visual').on('change', function() {
                const nuevoTipo = $(this).val();
                const idMonitoreo = $('input[name="id_monitoreo"]').val();

                // Actualizar el input hidden real
                $('#hidden_tipo_mantenimiento_final').val(nuevoTipo);

                // Evaluar si requiere justificación localmente
                evaluarJustificacion();

                // Enviar actualización por AJAX
                $.ajax({
                    url: 'index.php?pagina=cronometroCrear&accion=actualizarTipoVivo',
                    type: 'POST',
                    data: {
                        id_monitoreo: idMonitoreo,
                        id_tipo_mantenimiento: nuevoTipo
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert('ℹ️ ' + response.message);
                            
                            // Bloquear el select permanentemente
                            $('#select_tipo_mantenimiento_visual').prop('disabled', true).trigger('change.select2');
                            $('#badge_limite_cambio').removeClass('hidden').addClass('inline-block');
                        } else if (response.status === 'limit_reached') {
                            alert('⚠️ ' + response.message);
                            
                            // Si por algún motivo falló el bloqueo visual, forzamos el bloqueo
                            $('#select_tipo_mantenimiento_visual').prop('disabled', true).trigger('change.select2');
                            $('#badge_limite_cambio').removeClass('hidden').addClass('inline-block');
                        }
                    },
                    error: function() {
                        console.error(' Error de comunicación al actualizar el tipo de servicio.');
                    }
                });
            });
        <?php endif; ?>
    });

    // Función que evalúa los minutos reales vs los minutos de la meta seleccionada
    function evaluarJustificacion() {
        const spanReloj = document.getElementById('reloj_digital');
        if (!spanReloj) return;

        const idTipoSeleccionado = $('#id_tipo_mantenimiento_final').val();
        // Si el tipo no está en el catálogo, usa 60 por defecto
        const metaMinutos = catalogoTiempos[idTipoSeleccionado] || 60;
        
        const fechaInicio = new Date(spanReloj.getAttribute('data-inicio')).getTime();
        const ahora = new Date().getTime();
        
        // Minutos transcurridos
        const minutosTranscurridos = Math.floor((ahora - fechaInicio) / (1000 * 60));

        // Si ya pasamos la meta, exigimos justificación
        if (minutosTranscurridos > metaMinutos) {
            $('#contenedor_justificacion').removeClass('hidden');
            $('#justificacion_retraso').prop('required', true);
        } else {
            $('#contenedor_justificacion').addClass('hidden');
            $('#justificacion_retraso').prop('required', false).val('');
        }
    }

    function iniciarReloj() {
        const spanReloj = document.getElementById('reloj_digital');
        if (!spanReloj) return;

        const fechaInicio = new Date(spanReloj.getAttribute('data-inicio')).getTime();

        setInterval(function() {
            const ahora = new Date().getTime();
            const diferencia = ahora - fechaInicio;

            let horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
            let segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

            horas = (horas < 10) ? "0" + horas : horas;
            minutos = (minutos < 10) ? "0" + minutos : minutos;
            segundos = (segundos < 10) ? "0" + segundos : segundos;

            spanReloj.innerText = horas + ":" + minutos + ":" + segundos;

            // NUEVO: Cada minuto evaluamos si el técnico cruzó el límite sin darse cuenta
            if (segundos === "00") {
                evaluarJustificacion();
            }

        }, 1000);

        // Hacemos una evaluación inicial por si abren la app y ya van tarde
        evaluarJustificacion();
    }

    function validarYIniciar() {
        const cliente = $('#id_cliente').val();
        const punto = $('#id_punto').val();
        const tipo = $('#id_tipo_mantenimiento').val();

        if (!cliente || !punto || !tipo) {
            alert('⚠️ Por favor completa todos los campos para iniciar.');
            return;
        }

        if(confirm('¿Estás seguro de iniciar el servicio ahora mismo?')) {
            $('#formIniciar').submit();
        }
    }

    function confirmarFinalizar() {
        // Validar si la caja de justificación está visible y vacía
        const cajaVisible = !$('#contenedor_justificacion').hasClass('hidden');
        const justificacionTexto = $('#justificacion_retraso').val().trim();

        if (cajaVisible && justificacionTexto === '') {
            alert('⚠️ El tiempo real supera el estimado para este servicio. Por favor, escribe la justificación del retraso.');
            $('#justificacion_retraso').focus();
            return;
        }

        if(confirm('¿Terminaste el servicio? Esto detendrá el cronómetro permanentemente.')) {
            $('#formFinalizar').submit();
        }
    }
</script>