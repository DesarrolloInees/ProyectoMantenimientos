<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
    /* Estilos para los inputs de archivos tipo botón */
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
        cursor: pointer;
        display: block;
        z-index: 20;
    }
</style>

<div class="w-full px-4 md:px-6">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-edit text-blue-600 mr-2"></i> Editar Complementos del Servicio</h2>
            <p class="text-gray-500 text-sm">Completar información faltante para la Orden #<?= htmlspecialchars($datosOrden['id_ordenes_servicio']) ?></p>
        </div>
        <button type="button" onclick="window.history.back();" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded transition">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
    </div>

    <form action="index.php?pagina=servicioEditar&accion=guardar" method="POST" enctype="multipart/form-data" class="space-y-6" id="formEdicionServicio">
        <input type="hidden" name="id_ordenes_servicio" value="<?= htmlspecialchars($datosOrden['id_ordenes_servicio']) ?>">
        <input type="hidden" name="numero_remision" value="<?= htmlspecialchars($datosOrden['numero_remision'] ?? '') ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Soporte Remoto</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-headset text-gray-400"></i>
                    </div>
                    <input type="text" name="soporte_remoto" value="<?= htmlspecialchars($datosOrden['soporte_remoto'] ?? '') ?>" placeholder="Nombre de quien apoyó..." class="w-full border border-gray-300 rounded-lg py-3 pl-10 pr-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Núm. Máquina</label>
                    <input type="text" name="numero_maquina" value="<?= htmlspecialchars($datosOrden['numero_maquina'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Serial Máquina</label>
                    <input type="text" name="serial_maquina" value="<?= htmlspecialchars($datosOrden['serial_maquina'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Serial Router</label>
                    <input type="text" name="serial_router" value="<?= htmlspecialchars($datosOrden['serial_router'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Serial UPS</label>
                    <input type="text" name="serial_ups" value="<?= htmlspecialchars($datosOrden['serial_ups'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Administrador del Punto</label>
                <input type="text" name="administrador_punto" value="<?= htmlspecialchars($datosOrden['administrador_punto'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Celular Encargado</label>
                <input type="text" name="celular_encargado" value="<?= htmlspecialchars($datosOrden['celular_encargado'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Estado Inicial</label>
                <select name="id_estado_inicial" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">- Seleccione Estado -</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= $est['id_estado'] ?>" <?= (isset($datosOrden['id_estado_inicial']) && $datosOrden['id_estado_inicial'] == $est['id_estado']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($est['nombre_estado']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-orange-600 mb-2">Pendientes</label>
                <textarea name="pendientes" rows="2" class="w-full bg-orange-50 border border-orange-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-orange-500"><?= htmlspecialchars($datosOrden['pendientes'] ?? '') ?></textarea>
            </div>
        </div>


        <?php
        // Clasificamos las fotos que vienen de la BD
        $fotosAntes = [];
        $fotosRemision = [];
        $fotosDespues = [];
        $fotoFirma = [];

        if (isset($evidencias) && is_array($evidencias)) {
            foreach ($evidencias as $ev) {
                if ($ev['tipo_evidencia'] == 'antes') $fotosAntes[] = $ev;
                elseif ($ev['tipo_evidencia'] == 'remision') $fotosRemision[] = $ev;
                elseif ($ev['tipo_evidencia'] == 'despues') $fotosDespues[] = $ev;
                elseif ($ev['tipo_evidencia'] == 'firma') $fotoFirma[] = $ev;
            }
        }
        $totalFotosExistentes = count($evidencias);
        
        // FUNCIÓN PARA ARMAR LA RUTA PERFECTA
        function armarRutaImagen($rutaBD) {
            $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            $rutaLimpia = ltrim($rutaBD, '/');
            
            // EL TRUCO: Como las fotos se guardaron físicamente dentro de "app/uploads",
            // le decimos al navegador que incluya "app/" en la URL.
            if (strpos($rutaLimpia, 'app/') !== 0) {
                $rutaLimpia = 'app/' . $rutaLimpia;
            }
            
            return $base . '/' . $rutaLimpia;
        }
        ?>

        <?php if ($totalFotosExistentes > 0): ?>
            <div class="mt-6 border-t pt-6">
                <h3 class="text-lg font-bold text-gray-700 mb-4"><i class="fas fa-photo-video text-green-500 mr-2"></i> Evidencia Actual (<?= $totalFotosExistentes ?> fotos)</h3>
                <p class="text-sm text-gray-500 mb-4">Estas son las fotos que ya están vinculadas a este servicio.</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <h4 class="text-xs font-bold text-gray-600 uppercase mb-2 border-b pb-1">Antes (<?= count($fotosAntes) ?>)</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($fotosAntes as $foto): ?>
                                <?php $rutaFinal = armarRutaImagen($foto['ruta_archivo']); ?>
                                <a href="<?= $rutaFinal ?>" target="_blank">
                                    <img src="<?= $rutaFinal ?>"
                                        onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=Error'; this.title='Imagen no encontrada en el servidor';"
                                        class="w-16 h-16 object-cover rounded shadow hover:scale-110 transition cursor-pointer border border-gray-300 bg-white"
                                        alt="Foto Antes">
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($fotosAntes)): ?><span class="text-xs text-gray-400 italic">No hay fotos</span><?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <h4 class="text-xs font-bold text-gray-600 uppercase mb-2 border-b pb-1">Remisión (<?= count($fotosRemision) ?>)</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($fotosRemision as $foto): ?>
                                <?php $rutaFinal = armarRutaImagen($foto['ruta_archivo']); ?>
                                <a href="<?= $rutaFinal ?>" target="_blank">
                                    <img src="<?= $rutaFinal ?>"
                                        onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=Error'; this.title='Imagen no encontrada en el servidor';"
                                        class="w-16 h-16 object-cover rounded shadow hover:scale-110 transition cursor-pointer border border-gray-300 bg-white"
                                        alt="Remisión">
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($fotosRemision)): ?><span class="text-xs text-gray-400 italic">No hay fotos</span><?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <h4 class="text-xs font-bold text-gray-600 uppercase mb-2 border-b pb-1">Después (<?= count($fotosDespues) ?>)</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($fotosDespues as $foto): ?>
                                <?php $rutaFinal = armarRutaImagen($foto['ruta_archivo']); ?>
                                <a href="<?= $rutaFinal ?>" target="_blank">
                                    <img src="<?= $rutaFinal ?>"
                                        onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=Error'; this.title='Imagen no encontrada en el servidor';"
                                        class="w-16 h-16 object-cover rounded shadow hover:scale-110 transition cursor-pointer border border-gray-300 bg-white"
                                        alt="Foto Después">
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($fotosDespues)): ?><span class="text-xs text-gray-400 italic">No hay fotos</span><?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        <?php endif; ?>

        <div class="mt-6 border-t pt-6">
            <h3 class="text-lg font-bold text-gray-700 mb-4"><i class="fas fa-camera text-blue-500 mr-2"></i> Anexar Evidencia Fotográfica</h3>
            <p class="text-sm text-gray-500 mb-4">Límite de subida: <strong>10 fotos en total</strong> por sesión de edición.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <div class="border border-dashed border-gray-400 rounded-lg p-4 bg-gray-50 text-center file-upload-btn hover:bg-gray-100 transition relative overflow-hidden">
                        <i class="fas fa-images text-gray-400 text-3xl mb-2"></i>
                        <p class="text-sm font-bold text-gray-700">1. Fotos del "Antes"</p>
                        <span id="badge_fotos_antes" class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-bold inline-block relative z-10 mt-2">0 seleccionadas</span>
                        <input type="file" name="fotos_antes[]" id="fotos_antes" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    </div>
                    <div id="preview_antes" class="flex flex-wrap gap-2 mt-2 justify-center"></div>
                </div>

                <div>
                    <div class="border border-dashed border-gray-400 rounded-lg p-4 bg-gray-50 text-center file-upload-btn hover:bg-gray-100 transition relative overflow-hidden">
                        <i class="fas fa-file-signature text-gray-400 text-3xl mb-2"></i>
                        <p class="text-sm font-bold text-gray-700">2. Foto de Remisión</p>
                        <span id="badge_foto_remision" class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-bold inline-block relative z-10 mt-2">0 seleccionadas</span>
                        <input type="file" name="foto_remision[]" id="foto_remision" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    </div>
                    <div id="preview_remision" class="flex flex-wrap gap-2 mt-2 justify-center"></div>
                </div>

                <div>
                    <div class="border border-dashed border-gray-400 rounded-lg p-4 bg-gray-50 text-center file-upload-btn hover:bg-gray-100 transition relative overflow-hidden">
                        <i class="fas fa-check-double text-gray-400 text-3xl mb-2"></i>
                        <p class="text-sm font-bold text-gray-700">3. Fotos del "Después"</p>
                        <span id="badge_fotos_despues" class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-bold inline-block relative z-10 mt-2">0 seleccionadas</span>
                        <input type="file" name="fotos_despues[]" id="fotos_despues" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    </div>
                    <div id="preview_despues" class="flex flex-wrap gap-2 mt-2 justify-center"></div>
                </div>
            </div>

            <div class="text-center pt-4 mt-4 border-t border-gray-200">
                <span class="text-sm font-bold text-gray-600">Total a subir ahora: <span id="total_fotos_count" class="text-blue-600 text-lg">0</span>/10</span>
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        const MAX_FOTOS = 10;

        function inicializarPrecarga(inputId, previewId, badgeId) {
            $(`#${inputId}`).on('change', function(e) {
                const archivos = e.target.files;
                const previewContainer = $(`#${previewId}`);
                const badge = $(`#${badgeId}`);

                // Sumar el total de los 3 inputs
                let totalActual =
                    ($('#fotos_antes')[0].files.length || 0) +
                    ($('#foto_remision')[0].files.length || 0) +
                    ($('#fotos_despues')[0].files.length || 0);

                // Validar límite
                if (totalActual > MAX_FOTOS) {
                    alert(`❌ Límite excedido. Solo puedes subir un máximo de ${MAX_FOTOS} imágenes en total. Actualmente seleccionaste ${totalActual}.`);
                    $(this).val(''); // Resetea el input actual que hizo que se pasara del límite
                    previewContainer.empty();
                    badge.text('0 seleccionadas');
                    actualizarContadorGlobal();
                    return;
                }

                // Actualizar UI
                badge.text(`${archivos.length} seleccionadas`);
                previewContainer.empty();

                // Generar las miniaturas
                Array.from(archivos).forEach(file => {
                    if (!file.type.match('image.*')) return;

                    const reader = new FileReader();
                    reader.onload = (function(theFile) {
                        return function(event) {
                            const img = `<img class="w-16 h-16 object-cover rounded shadow border border-gray-300" src="${event.target.result}" title="${escape(theFile.name)}" />`;
                            previewContainer.append(img);
                        };
                    })(file);
                    reader.readAsDataURL(file);
                });

                actualizarContadorGlobal();
            });
        }

        function actualizarContadorGlobal() {
            let total =
                ($('#fotos_antes')[0].files.length || 0) +
                ($('#foto_remision')[0].files.length || 0) +
                ($('#fotos_despues')[0].files.length || 0);

            let counterDisplay = $('#total_fotos_count');
            counterDisplay.text(total);

            // Cambiar color si se acerca al límite
            if (total === MAX_FOTOS) {
                counterDisplay.removeClass('text-blue-600').addClass('text-red-600');
            } else {
                counterDisplay.removeClass('text-red-600').addClass('text-blue-600');
            }
        }

        // Inicializamos los tres inputs
        inicializarPrecarga('fotos_antes', 'preview_antes', 'badge_fotos_antes');
        inicializarPrecarga('foto_remision', 'preview_remision', 'badge_foto_remision');
        inicializarPrecarga('fotos_despues', 'preview_despues', 'badge_fotos_despues');

        // Prevenir el envío del form si por alguna razón manipulan el HTML y pasan el límite
        $('#formEdicionServicio').on('submit', function(e) {
            let total = parseInt($('#total_fotos_count').text());
            if (total > MAX_FOTOS) {
                e.preventDefault();
                alert(`No puedes guardar. Has excedido el límite de ${MAX_FOTOS} imágenes.`);
            }
        });
    });
</script>