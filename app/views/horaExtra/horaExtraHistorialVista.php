<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<style>
    body {
        background-color: #f1f5f9;
        padding-bottom: 30px;
    }
</style>

<!-- HEADER FIJO MÓVIL -->
<div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <button onclick="window.history.back();"
            class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition">
            <i class="fas fa-arrow-left"></i>
        </button>
        <div>
            <h1 class="font-bold text-lg leading-tight">Mis Horas Extra</h1>
            <p class="text-blue-200 text-xs">Historial de reportes creados</p>
        </div>
    </div>
    <a href="index.php?pagina=horaExtraCrear"
        class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center transition shadow"
        title="Reportar Nuevas Horas Extra">
        <i class="fas fa-plus"></i>
    </a>
</div>

<div class="max-w-lg mx-auto p-3 mt-2 space-y-4">
    <?php if (empty($reportesHE)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center mt-10">
            <i class="fas fa-business-time text-gray-300 text-5xl mb-3"></i>
            <h2 class="text-gray-500 font-bold text-lg">Sin registros</h2>
            <p class="text-gray-400 text-sm mt-1">Aún no has registrado ninguna hora extra.</p>
        </div>
    <?php else: ?>
        <?php foreach ($reportesHE as $he): ?>
            <?php
            $badgeColor = 'bg-yellow-100 text-yellow-800 border-yellow-200';
            $iconoEstado = 'fa-clock';
            if ($he['estado_nombre'] === 'Aprobada') {
                $badgeColor = 'bg-green-100 text-green-800 border-green-200';
                $iconoEstado = 'fa-check-circle';
            } elseif ($he['estado_nombre'] === 'Rechazada') {
                $badgeColor = 'bg-red-100 text-red-800 border-red-200';
                $iconoEstado = 'fa-times-circle';
            }

            $fotosArray = !empty($he['rutas_fotos']) ? explode('||', $he['rutas_fotos']) : [];
            ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Header de la tarjeta -->
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-600">
                        <i class="far fa-calendar-alt mr-1 text-blue-600"></i>
                        <?= date('d/m/Y', strtotime($he['fecha_reporte'])) ?>
                    </span>
                    <span
                        class="px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase flex items-center gap-1 <?= $badgeColor ?>">
                        <i class="fas <?= $iconoEstado ?>"></i> <?= htmlspecialchars($he['estado_nombre']) ?>
                    </span>
                </div>

                <div class="p-4 space-y-3">
                    <!-- Ubicación -->
                    <div>
                        <?php if (!empty($he['nombre_cliente'])): ?>
                            <span class="block text-[10px] font-bold text-blue-600 uppercase leading-none">
                                <?= htmlspecialchars($he['nombre_cliente']) ?>
                            </span>
                        <?php endif; ?>
                        <h3 class="font-bold text-gray-800 text-sm mt-0.5">
                            <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                            <?= htmlspecialchars($he['nombre_punto'] ?: 'Sin punto asignado') ?>
                        </h3>
                    </div>

                    <!-- Métricas de horario y total -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-gray-50 p-2 rounded border border-gray-100">
                            <span class="block text-gray-400 uppercase font-bold text-[9px]">Horario</span>
                            <span class="text-gray-700 font-semibold">
                                <?= date('h:i A', strtotime($he['hora_inicio'])) ?> -
                                <?= date('h:i A', strtotime($he['hora_fin'])) ?>
                            </span>
                        </div>
                        <div class="bg-blue-50 p-2 rounded border border-blue-100 text-right">
                            <span class="block text-blue-500 uppercase font-bold text-[9px]">Total Horas</span>
                            <span class="text-blue-700 font-bold text-sm">
                                <?= number_format($he['total_horas'], 2) ?> hrs
                            </span>
                        </div>
                    </div>

                    <!-- Justificación -->
                    <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                        <span class="block text-gray-400 uppercase font-bold text-[9px] mb-0.5">Justificación:</span>
                        <p class="text-xs text-gray-700 italic leading-snug">
                            "<?= htmlspecialchars($he['justificacion_tecnico']) ?>"
                        </p>
                    </div>

                    <!-- Observación del supervisor -->
                    <?php if (!empty($he['observacion_supervisor'])): ?>
                        <div class="bg-red-50 p-2.5 rounded-lg border border-red-100">
                            <span class="block text-red-500 uppercase font-bold text-[9px] mb-0.5">Nota Supervisor:</span>
                            <p class="text-xs text-red-700 font-medium leading-snug">
                                <?= htmlspecialchars($he['observacion_supervisor']) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Botón para ver fotos -->
                    <?php if (count($fotosArray) > 0): ?>
                        <button type="button"
                            onclick='abrirModalFotos(<?= json_encode($fotosArray) ?>, "<?= date('d/m/Y', strtotime($he['fecha_reporte'])) ?>")'
                            class="w-full border border-blue-500 text-blue-600 hover:bg-blue-50 font-bold py-2 rounded-lg transition flex items-center justify-center gap-2 text-sm mt-2">
                            <i class="fas fa-images"></i> Ver Evidencias (<?= count($fotosArray) ?>)
                        </button>
                    <?php endif; ?>

                    <!-- Botón Editar DENTRO del cuerpo de la tarjeta (Si no está aprobada) -->
                    <?php if ($he['estado_nombre'] !== 'Aprobada'): ?>
                        <button type="button" onclick='abrirModalEditar(<?= json_encode($he) ?>)'
                            class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition flex items-center justify-center gap-2 text-sm mt-2 shadow">
                            <i class="fas fa-edit"></i> Corregir / Editar Reporte
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Galería para ver Evidencias -->
<div id="modalFotos"
    class="fixed inset-0 bg-black bg-opacity-90 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-2xl transform scale-95 transition-transform duration-300 space-y-3"
        id="modalContent">
        <div class="flex justify-between items-center text-white">
            <h3 class="font-bold text-sm" id="tituloModalFoto">Evidencias</h3>
            <button type="button" onclick="cerrarModalFotos()"
                class="text-white hover:text-red-400 text-3xl leading-none">&times;</button>
        </div>
        <div class="bg-white rounded-xl overflow-hidden p-3 flex flex-wrap gap-2 justify-center max-h-[75vh] overflow-y-auto"
            id="galeriaContenedor"></div>
    </div>
</div>

<!-- Modal Editar Horas Extra -->
<div id="modalEditar"
    class="fixed inset-0 bg-black/80 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300"
        id="modalContentEditar">
        <div class="bg-amber-600 text-white p-4 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-base"><i class="fas fa-edit mr-1"></i> Corregir Reporte y Fotos</h3>
                <p class="text-amber-100 text-xs">Ajusta horarios, justificación y sube o renueva fotos</p>
            </div>
            <button type="button" onclick="cerrarModalEditar()"
                class="text-white hover:text-red-200 text-2xl leading-none">&times;</button>
        </div>

        <form id="formEditarHE" enctype="multipart/form-data" class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
            <input type="hidden" id="edit_id_registro" name="id_registro">

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha del Reporte</label>
                <input type="date" id="edit_fecha_reporte" name="fecha_reporte" required
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hora Inicio</label>
                    <input type="time" id="edit_hora_inicio" name="hora_inicio" required
                        onchange="calcularTotalHorasEdit()"
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hora Fin</label>
                    <input type="time" id="edit_hora_fin" name="hora_fin" required onchange="calcularTotalHorasEdit()"
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500">
                </div>
            </div>

            <div class="bg-amber-50 p-3 rounded-lg border border-amber-200 text-center">
                <span class="block text-amber-700 font-bold text-xs uppercase">Total Horas Recalculadas:</span>
                <span id="edit_total_horas_text" class="text-xl font-extrabold text-amber-800">0.00 hrs</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Justificación del Trabajo</label>
                <textarea id="edit_justificacion" name="justificacion_tecnico" rows="2" required
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm outline-none focus:border-amber-500"></textarea>
            </div>

            <!-- Evidencias Actuales / Subir Nuevas Fotos -->
            <div class="border-t pt-3 space-y-2">
                <label class="block text-xs font-bold text-gray-700 uppercase">Evidencias Fotográficas</label>

                <!-- Fotos existentes guardadas en el servidor -->
                <div id="edit_fotos_existentes" class="flex flex-wrap gap-3 mb-2"></div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">
                        Agregar fotos adicionales (JPG, PNG, WEBP):
                    </label>
                    <!-- AQUÍ ESTÁ EL CAMBIO: onchange="previsualizarNuevasFotos(this)" -->
                    <input type="file" id="nuevas_evidencias" name="nuevas_evidencias[]" multiple accept="image/*"
                        onchange="previsualizarNuevasFotos(this)"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 cursor-pointer">
                </div>

                <!-- AQUÍ SE MOSTRARÁN LAS PREVISUALIZACIONES AL SELECCIONAR -->
                <div id="preview_nuevas_evidencias" class="flex flex-wrap gap-2 pt-2"></div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="cerrarModalEditar()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2 rounded-lg text-sm transition">
                    Cancelar
                </button>
                <button type="button" onclick="guardarEdicionHE()"
                    class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2 rounded-lg text-sm shadow transition flex items-center gap-1.5">
                    <i class="fas fa-paper-plane"></i> Reenviar a Revisión
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    if (typeof window.BASE_URL === 'undefined') {
        window.BASE_URL = '<?= BASE_URL ?>';
    }

    // Modal Galería Fotos
    function abrirModalFotos(rutasArray, fechaReporte) {
        $('#tituloModalFoto').text('Evidencias - Reporte ' + fechaReporte);
        const contenedor = $('#galeriaContenedor');
        contenedor.empty();

        rutasArray.forEach(function (ruta) {
            const urlCompleta = window.BASE_URL + ruta;
            contenedor.append(`
                <div class="w-full sm:w-48 h-48 bg-gray-100 rounded-lg overflow-hidden border border-gray-300 flex items-center justify-center">
                    <a href="${urlCompleta}" target="_blank">
                        <img src="${urlCompleta}" class="max-w-full max-h-full object-cover" alt="Evidencia HE">
                    </a>
                </div>
            `);
        });

        $('#modalFotos').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalFotos').removeClass('opacity-0').addClass('opacity-100');
            $('#modalContent').removeClass('scale-95').addClass('scale-100');
        }, 10);
    }

    function cerrarModalFotos() {
        $('#modalFotos').removeClass('opacity-100').addClass('opacity-0');
        $('#modalContent').removeClass('scale-100').addClass('scale-95');
        setTimeout(() => {
            $('#modalFotos').removeClass('flex').addClass('hidden');
            $('#galeriaContenedor').empty();
        }, 300);
    }

    // Previsualizar imágenes antes de guardar
    function previsualizarNuevasFotos(input) {
        const previewContainer = $('#preview_nuevas_evidencias');
        previewContainer.empty(); // Limpiar previsualizaciones anteriores

        if (input.files && input.files.length > 0) {
            previewContainer.append('<span class="w-full text-[10px] font-bold text-amber-700 uppercase">Nuevas fotos a subir:</span>');

            Array.from(input.files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        previewContainer.append(`
                        <div class="w-16 h-16 rounded-lg border-2 border-amber-400 overflow-hidden relative shadow-sm" id="prev_box_${index}">
                            <img src="${e.target.result}" class="w-full h-full object-cover" title="${file.name}">
                            <span class="absolute bottom-0 inset-x-0 bg-amber-600/80 text-white text-[8px] text-center font-bold truncate px-0.5">NUEVA</span>
                        </div>
                    `);
                    };

                    reader.readAsDataURL(file); // Convertir imagen local a Data URL
                }
            });
        }
    }

    // Limpiar la previsualización al abrir la modal
    function abrirModalEditar(data) {
        $('#edit_id_registro').val(data.id_registro_he);
        $('#edit_fecha_reporte').val(data.fecha_reporte);
        $('#edit_hora_inicio').val(data.hora_inicio);
        $('#edit_hora_fin').val(data.hora_fin);
        $('#edit_justificacion').val(data.justificacion_tecnico);
        $('#nuevas_evidencias').val(''); // Resetear campo de archivos
        $('#preview_nuevas_evidencias').empty(); // Limpiar previews anteriores

        // Cargar fotos existentes guardadas previamente en servidor
        const contExistentes = $('#edit_fotos_existentes');
        contExistentes.empty();

        if (data.fotos_detalle) {
            const fotosArray = data.fotos_detalle.split('||');
            fotosArray.forEach(function (item) {
                const partes = item.split('::');
                const idEvidencia = partes[0];
                const ruta = partes[1];
                const urlCompleta = window.BASE_URL + ruta;

                contExistentes.append(`
                <div class="w-20 h-20 rounded-lg border border-gray-300 overflow-hidden relative group" id="foto_box_${idEvidencia}">
                    <img src="${urlCompleta}" class="w-full h-full object-cover">
                    <button type="button" onclick="eliminarFotoEvidencia(${idEvidencia}, ${data.id_registro_he})"
                        class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow hover:bg-red-700 transition"
                        title="Eliminar esta foto">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            });
        } else {
            contExistentes.html('<span class="text-xs text-gray-400 italic">Sin fotos adjuntas previamente.</span>');
        }

        calcularTotalHorasEdit();

        $('#modalEditar').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalEditar').removeClass('opacity-0').addClass('opacity-100');
            $('#modalContentEditar').removeClass('scale-95').addClass('scale-100');
        }, 10);
    }

    function eliminarFotoEvidencia(idEvidencia, idRegistro) {
        if (!confirm('¿Seguro que deseas eliminar esta imagen del reporte?')) return;

        $.ajax({
            url: 'index.php?pagina=horaExtraHistorial&accion=ajaxEliminarEvidencia',
            type: 'POST',
            data: {
                id_evidencia: idEvidencia,
                id_registro: idRegistro
            },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $(`#foto_box_${idEvidencia}`).fadeOut(300, function () {
                        $(this).remove();
                        if ($('#edit_fotos_existentes').children().length === 0) {
                            $('#edit_fotos_existentes').html('<span class="text-xs text-gray-400 italic">Sin fotos adjuntas previamente.</span>');
                        }
                    });
                } else {
                    alert('❌ ' + res.msj);
                }
            },
            error: function () {
                alert('❌ Error de comunicación con el servidor.');
            }
        });
    }

    function cerrarModalEditar() {
        $('#modalEditar').removeClass('opacity-100').addClass('opacity-0');
        $('#modalContentEditar').removeClass('scale-100').addClass('scale-95');
        setTimeout(() => {
            $('#modalEditar').removeClass('flex').addClass('hidden');
        }, 300);
    }

    function calcularTotalHorasEdit() {
        const ini = $('#edit_hora_inicio').val();
        const fin = $('#edit_hora_fin').val();

        if (ini && fin) {
            let d1 = new Date("2000-01-01 " + ini);
            let d2 = new Date("2000-01-01 " + fin);

            if (d2 <= d1) {
                d2.setDate(d2.getDate() + 1); // Maneja cruce de medianoche
            }

            let diff = (d2 - d1) / 1000 / 60 / 60;
            $('#edit_total_horas_text').text(diff.toFixed(2) + ' hrs');
        }
    }

    function guardarEdicionHE() {
        const formElement = document.getElementById('formEditarHE');
        const formData = new FormData(formElement); // Permite enviar tanto los textos como los archivos binarios

        $.ajax({
            url: 'index.php?pagina=horaExtraHistorial&accion=ajaxEditarRegistro',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    alert('✅ ' + res.msj);
                    location.reload();
                } else {
                    alert('❌ ' + res.msj);
                }
            },
            error: function () {
                alert('❌ Error al intentar procesar los cambios y subir las fotos.');
            }
        });
    }

    // Cerrar modales al hacer clic afuera
    $('#modalFotos, #modalEditar').on('click', function (e) {
        if (e.target === this) {
            cerrarModalFotos();
            cerrarModalEditar();
        }
    });
</script>