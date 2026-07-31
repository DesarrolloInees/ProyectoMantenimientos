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
        <h1 class="font-bold text-lg leading-tight">Registrar Parqueadero</h1>
        <p class="text-blue-200 text-xs">Subida de facturas</p>
    </div>
</div>

<div class="max-w-lg mx-auto p-3 space-y-4 mt-2 mb-24">

    <form action="index.php?pagina=parqueaderoCrear&accion=guardar" method="POST" id="formParqueaderoMovil"
        enctype="multipart/form-data">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-5">
            <div class="flex items-center gap-2 mb-1 border-b pb-2">
                <i class="fas fa-parking text-blue-500 text-lg"></i>
                <h2 class="font-bold text-gray-700 text-sm uppercase">Datos de la Factura</h2>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <!-- Fecha -->
                <!-- Fila 1: Fecha (Ancho completo) -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha <span
                            class="text-red-500">*</span></label>
                    <input type="date" name="fecha_servicio" id="fecha_servicio" required value="<?= date('Y-m-d') ?>"
                        class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500">
                </div>

                <!-- Fila 2: Horas (Mitad y Mitad) -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora Inicio <span
                                class="text-red-500">*</span></label>
                        <input type="time" name="hora_inicio" id="hora_inicio" required
                            class="w-full bg-white border border-gray-300 rounded-lg p-2 text-sm text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora Fin <span
                                class="text-red-500">*</span></label>
                        <input type="time" name="hora_fin" id="hora_fin" required
                            class="w-full bg-white border border-gray-300 rounded-lg p-2 text-sm text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Punto Visitado <span
                        class="text-red-500">*</span></label>
                <select name="id_punto" id="id_punto" class="w-full border-gray-300 rounded-lg select2-movil" required>
                    <option value="">- Seleccione el Punto -</option>
                    <?php if (!empty($puntos)): ?>
                        <?php foreach ($puntos as $punto): ?>
                            <option value="<?= htmlspecialchars($punto['id_punto']) ?>">
                                <?= htmlspecialchars($punto['nombre_punto']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Valor ($) <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="valor_factura" id="valor_factura" step="0.01" min="0"
                        placeholder="Ej: 15000" required
                        class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 shadow-sm outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">N° Factura <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="numero_factura" id="numero_factura" placeholder="Ej: A-12345" required
                        class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 shadow-sm outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-4 mt-4">
                <div class="flex items-center gap-2 mb-2 border-b pb-2">
                    <i class="fas fa-camera text-indigo-500 text-lg"></i>
                    <h2 class="font-bold text-gray-700 text-sm uppercase">Adjuntar Foto <span
                            class="text-red-500">*</span></h2>
                </div>

                <div
                    class="border border-dashed border-gray-300 rounded-lg p-4 bg-white text-center file-upload-btn transition hover:bg-gray-100 relative">
                    <i class="fas fa-file-invoice-dollar text-gray-400 text-3xl mb-2"></i>
                    <p class="text-sm font-bold text-gray-700">Toca para cargar la foto</p>
                    <span id="badge_foto_factura"
                        class="hidden bg-green-100 text-green-700 px-3 py-1 mt-2 rounded-full text-xs font-bold relative z-10">¡Foto
                        seleccionada!</span>
                    <input type="file" name="foto_factura" id="foto_factura" accept="image/*" required
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                </div>

                <div id="preview_factura_container" class="hidden mt-3 text-center">
                    <img id="preview_factura" src=""
                        class="max-h-48 mx-auto rounded-lg border border-gray-300 shadow-sm" alt="Vista previa">
                </div>
            </div>
        </div>
    </form>
</div>

<div
    class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
    <button type="button" onclick="validarYEnviarParqueadero()"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2">
        <i class="fas fa-cloud-upload-alt"></i> SUBIR FACTURA
    </button>
</div>

<script>
    $(document).ready(function () {
        $('.select2-movil').select2({
            width: '100%',
            language: { noResults: function () { return "No se encontraron resultados"; } }
        });

        $('#foto_factura').on('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                $('#badge_foto_factura').removeClass('hidden').addClass('inline-block');
                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#preview_factura').attr('src', e.target.result);
                    $('#preview_factura_container').removeClass('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                $('#badge_foto_factura').addClass('hidden').removeClass('inline-block');
                $('#preview_factura_container').addClass('hidden');
            }
        });
    });

    function validarYEnviarParqueadero() {
        const id_punto = $('#id_punto').val();
        const valor = $('#valor_factura').val();
        const numero = $('#numero_factura').val();
        const hora_fin = $('#hora_fin').val(); // <-- LÍNEA NUEVA
        const foto = $('#foto_factura')[0].files.length;

        // Se agregó !hora_fin a la validación
        if (!id_punto || !valor || !numero || !hora_fin || foto === 0) {
            alert('⚠️ Por favor, completa todos los campos obligatorios y adjunta la foto.');
            return;
        }

        $('#formParqueaderoMovil').submit();
    }
</script>