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

    .file-upload-btn {
        position: relative;
        overflow: hidden;
    }

    .file-upload-btn input[type="file"] {
        position: absolute;
        top: 0;
        right: 0;
        min-width: 100%;
        min-height: 100%;
        font-size: 100px;
        text-align: right;
        filter: alpha(opacity=0);
        opacity: 0;
        outline: none;
        background: white;
        cursor: inherit;
        display: block;
    }
</style>

<div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center gap-3">
    <button onclick="window.history.back();"
        class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition">
        <i class="fas fa-arrow-left"></i>
    </button>
    <div>
        <h1 class="font-bold text-lg leading-tight">Reportar Horas Extra</h1>
        <p class="text-blue-200 text-xs">Registro adicional por cliente y punto</p>
    </div>
</div>

<div class="max-w-lg mx-auto p-3 space-y-4 mt-2 mb-24">

    <form action="index.php?pagina=horaExtraCrear&accion=guardar" method="POST" id="formHorasExtraMovil" enctype="multipart/form-data">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-4">
            
            <div class="flex items-center gap-2 border-b pb-2">
                <i class="fas fa-clock text-blue-500 text-lg"></i>
                <h2 class="font-bold text-gray-700 text-sm uppercase">Detalles del Tiempo</h2>
            </div>

            <!-- Fecha -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha <span class="text-red-500">*</span></label>
                <input type="date" name="fecha_reporte" id="fecha_reporte" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"
                    class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500">
            </div>

            <!-- Horas -->
            <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora Inicio <span class="text-red-500">*</span></label>
                    <input type="time" name="hora_inicio" id="hora_inicio" required
                        class="w-full bg-white border border-gray-300 rounded-lg p-2 text-sm text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora Fin <span class="text-red-500">*</span></label>
                    <input type="time" name="hora_fin" id="hora_fin" required
                        class="w-full bg-white border border-gray-300 rounded-lg p-2 text-sm text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500">
                </div>
                <div class="col-span-2 pt-2 border-t border-gray-200 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500 uppercase">Total Calculado:</span>
                    <strong id="display_total_horas" class="text-blue-600 bg-blue-100 px-3 py-1 rounded-full text-sm">0h 0m</strong>
                </div>
            </div>

            <div class="flex items-center gap-2 border-b pb-2 pt-2">
                <i class="fas fa-building text-blue-500 text-lg"></i>
                <h2 class="font-bold text-gray-700 text-sm uppercase">Ubicación del Servicio</h2>
            </div>

            <!-- Cliente -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Cliente</label>
                <select name="id_cliente" id="id_cliente" class="w-full border-gray-300 rounded-lg select2-movil">
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

            <!-- Justificación -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Justificación <span class="text-red-500">*</span></label>
                <textarea name="justificacion" id="justificacion" rows="3" required placeholder="Describe por qué te quedaste hasta tarde..."
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500"></textarea>
            </div>

            <!-- Adjuntar Evidencias Fotográficas -->
            <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-4 mt-2">
                <div class="flex items-center gap-2 mb-2 border-b pb-2">
                    <i class="fas fa-camera text-indigo-500 text-lg"></i>
                    <h2 class="font-bold text-gray-700 text-sm uppercase">Evidencia Fotográfica</h2>
                </div>

                <div class="border border-dashed border-gray-300 rounded-lg p-4 bg-white text-center file-upload-btn transition hover:bg-gray-100 relative">
                    <i class="fas fa-images text-gray-400 text-3xl mb-2"></i>
                    <p class="text-sm font-bold text-gray-700">Toca para agregar fotos</p>
                    <span id="badge_fotos" class="hidden bg-green-100 text-green-700 px-3 py-1 mt-2 rounded-full text-xs font-bold relative z-10">
                        0 seleccionadas
                    </span>
                    <input type="file" id="input_evidencias_helper" accept="image/*" multiple
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                </div>

                <!-- Input real que se enviará con el formulario -->
                <input type="file" name="evidencias[]" id="evidencias_real" accept="image/*" multiple class="hidden">

                <div id="preview_container" class="flex flex-wrap gap-3 mt-3 justify-center"></div>
            </div>

        </div>
    </form>
</div>

<!-- Botón Flotante -->
<div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
    <button type="button" onclick="validarYEnviarHorasExtra()"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2">
        <i class="fas fa-paper-plane"></i> ENVIAR REPORTE
    </button>
</div>

<script>
    // Almacén dinámico para los archivos
    const dtArchivos = new DataTransfer();

    // Guardar una copia original de los puntos para poder filtrar en JS
    let listaPuntosOriginal = [];

    $(document).ready(function () {
        $('.select2-movil').select2({
            width: '100%',
            language: { noResults: function () { return "No se encontraron resultados"; } }
        });

        // Guardar estructura de los puntos originales
        $('#id_punto option').each(function() {
            if ($(this).val() !== "") {
                listaPuntosOriginal.push({
                    val: $(this).val(),
                    text: $(this).text(),
                    cliente: $(this).data('cliente')
                });
            }
        });

        // FILTRAR PUNTOS CONFORME AL CLIENTE
        $('#id_cliente').on('change', function() {
            const idClienteSeleccionado = $(this).val();
            const selectPunto = $('#id_punto');

            selectPunto.empty().append('<option value="">- Seleccione el Punto -</option>');

            listaPuntosOriginal.forEach(function(punto) {
                if (!idClienteSeleccionado || punto.cliente == idClienteSeleccionado) {
                    selectPunto.append(new Option(punto.text, punto.val, false, false));
                    // Mantener el atributo data-cliente en las opciones filtradas
                    selectPunto.find(`option[value="${punto.val}"]`).attr('data-cliente', punto.cliente);
                }
            });

            selectPunto.val('').trigger('change.select2');
        });

        // AUTO-SELECCIONAR CLIENTE SI SE ELIGE UN PUNTO DIRECTAMENTE
        $('#id_punto').on('change', function() {
            const valPunto = $(this).val();
            if (valPunto && !$('#id_cliente').val()) {
                const puntoObj = listaPuntosOriginal.find(p => p.val == valPunto);
                if (puntoObj && puntoObj.cliente) {
                    $('#id_cliente').val(puntoObj.cliente).trigger('change.select2');
                    // Restaurar opción del punto luego de disparar el cambio de cliente
                    $('#id_punto').val(valPunto).trigger('change.select2');
                }
            }
        });

        // CÁLCULO EN TIEMPO REAL DE HORAS
        $('#hora_inicio, #hora_fin').on('change input', function() {
            const ini = $('#hora_inicio').val();
            const fin = $('#hora_fin').val();
            if (ini && fin) {
                let [hIni, mIni] = ini.split(':').map(Number);
                let [hFin, mFin] = fin.split(':').map(Number);
                let m1 = (hIni * 60) + mIni;
                let m2 = (hFin * 60) + mFin;
                if (m2 < m1) m2 += (24 * 60);
                let diff = m2 - m1;
                let h = Math.floor(diff / 60);
                let m = diff % 60;
                $('#display_total_horas').text(`${h}h ${m}m`);
            } else {
                $('#display_total_horas').text('0h 0m');
            }
        });

        // MANEJO Y CARGA PROGRESIVA DE ARCHIVOS
        $('#input_evidencias_helper').on('change', function(e) {
            const files = e.target.files;

            for (let i = 0; i < files.length; i++) {
                dtArchivos.items.add(files[i]);
            }

            // Asignar al input oculto real
            document.getElementById('evidencias_real').files = dtArchivos.files;

            // Resetear el helper para poder subir de nuevo si quieren
            $(this).val('');

            actualizarVistaPreviaFotos();
        });
    });

    // RENDERIZAR VISTA PREVIA CON BOTÓN PARA ELIMINAR
    function actualizarVistaPreviaFotos() {
        const container = $('#preview_container');
        container.empty();

        const totalArchivos = dtArchivos.files.length;

        if (totalArchivos > 0) {
            $('#badge_fotos').text(`${totalArchivos} foto(s) seleccionada(s)`).removeClass('hidden').addClass('inline-block');

            Array.from(dtArchivos.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    const htmlFoto = `
                        <div class="relative group w-20 h-20">
                            <img src="${evt.target.result}" class="w-20 h-20 object-cover rounded-lg border border-gray-300 shadow-sm">
                            <button type="button" onclick="eliminarFoto(${index})" 
                                class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs shadow-md hover:bg-red-700 transition">
                                &times;
                            </button>
                        </div>
                    `;
                    container.append(htmlFoto);
                };
                reader.readAsDataURL(file);
            });
        } else {
            $('#badge_fotos').addClass('hidden').removeClass('inline-block');
        }
    }

    // ELIMINAR UNA FOTO ESPECÍFICA
    function eliminarFoto(index) {
        dtArchivos.items.remove(index);
        document.getElementById('evidencias_real').files = dtArchivos.files;
        actualizarVistaPreviaFotos();
    }

    // VALIDACIÓN GENERAL ANTES DE ENVIAR
    function validarYEnviarHorasExtra() {
        const fecha = $('#fecha_reporte').val();
        const ini = $('#hora_inicio').val();
        const fin = $('#hora_fin').val();
        const punto = $('#id_punto').val();
        const just = $('#justificacion').val().trim();

        if (!fecha || !ini || !fin || !punto || !just) {
            alert('⚠️ Completa todos los campos obligatorios (*).');
            return;
        }

        $('#formHorasExtraMovil').submit();
    }
</script>