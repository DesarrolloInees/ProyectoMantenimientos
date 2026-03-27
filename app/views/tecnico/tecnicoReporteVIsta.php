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
        cursor: inherit;
        display: block;
    }
</style>

<div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center gap-3">
    <button onclick="window.history.back();" class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition">
        <i class="fas fa-arrow-left"></i>
    </button>
    <div>
        <h1 class="font-bold text-lg leading-tight">Ejecutar Servicio</h1>
        <p class="text-blue-200 text-xs">Orden #<?= htmlspecialchars($orden['id_ordenes_servicio']) ?> | <?= date('d/m/Y', strtotime($orden['fecha_visita'])) ?></p>
    </div>
</div>

<div class="max-w-lg mx-auto p-3 space-y-4 mt-2 mb-24">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center gap-2 mb-3 border-b pb-2">
            <i class="fas fa-info-circle text-blue-500 text-lg"></i>
            <h2 class="font-bold text-gray-700 text-sm uppercase">Detalles de Asignación</h2>
        </div>
        <div class="grid grid-cols-1 gap-4 text-sm">
            <div>
                <span class="block text-xs text-gray-400 font-bold uppercase mb-1">Cliente / Punto</span>
                <span class="block font-bold text-gray-800 text-base leading-tight"><?= htmlspecialchars($orden['nombre_cliente']) ?></span>
                <span class="block text-gray-600 font-medium mt-1"><?= htmlspecialchars($orden['nombre_punto']) ?></span>
                <?php if (!empty($orden['direccion'])): ?>
                    <span class="block text-gray-400 text-xs mt-1"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($orden['direccion']) ?></span>
                <?php endif; ?>
            </div>
            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                <span class="block text-xs text-blue-400 font-bold uppercase mb-1">Máquina a intervenir</span>
                <span class="block font-bold text-gray-800"><?= htmlspecialchars($orden['nombre_tipo_maquina']) ?></span>
                <span class="block text-blue-600 font-mono text-xs mt-1 bg-white inline-block px-2 py-1 rounded border border-blue-200">
                    ID: <?= htmlspecialchars($orden['device_id'] ?: 'N/A') ?>
                </span>
            </div>
        </div>
    </div>

    <form action="index.php?pagina=tecnicoReporte&accion=guardar" method="POST" id="formReporteMovil" enctype="multipart/form-data">
        <input type="hidden" name="id_ordenes_servicio" value="<?= htmlspecialchars($orden['id_ordenes_servicio']) ?>">
        
        <input type="hidden" name="latitud_fin" id="latitud_fin" value="">
        <input type="hidden" name="longitud_fin" id="longitud_fin" value="">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-5">
            <div class="flex items-center gap-2 mb-1 border-b pb-2">
                <i class="fas fa-clipboard-check text-green-500 text-lg"></i>
                <h2 class="font-bold text-gray-700 text-sm uppercase">Llenar Reporte</h2>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tipo de Servicio</label>
                <select name="id_tipo_mantenimiento" class="w-full border-gray-300 rounded-lg select2-movil" required>
                    <option value="">- Seleccione Tipo -</option>
                    <?php foreach ($tiposManto as $tm): ?>
                        <option value="<?= htmlspecialchars($tm['id_tipo_mantenimiento']) ?>" <?= (isset($orden['id_tipo_mantenimiento']) && $orden['id_tipo_mantenimiento'] == $tm['id_tipo_mantenimiento']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tm['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora (Entrada)</label>
                    <input type="time" name="hora_entrada" id="hora_entrada" required class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hora (Salida)</label>
                    <input type="time" name="hora_salida" id="hora_salida" required class="w-full bg-white border border-gray-300 rounded-lg p-3 text-gray-800 font-bold shadow-sm outline-none focus:border-blue-500">
                </div>
                <div class="col-span-2 pt-2 border-t border-gray-200 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500 uppercase">Tiempo Total:</span>
                    <strong id="tiempo_total_display" class="text-blue-600 bg-blue-100 px-3 py-1 rounded-full text-sm">00:00 hrs</strong>
                    <input type="hidden" name="tiempo_servicio" id="tiempo_servicio" value="00:00">
                </div>
            </div>

            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 space-y-3">
                <div class="flex items-center gap-2 border-b border-blue-200 pb-1">
                    <i class="fas fa-microchip text-blue-500"></i>
                    <h3 class="font-bold text-blue-800 text-xs uppercase">Información de Equipos</h3>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-blue-600 uppercase mb-1">Máquina</label>
                        <input type="text" name="numero_maquina" placeholder="Ej: 159" class="w-full bg-white border border-blue-200 rounded-lg p-2 text-sm shadow-sm outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-600 uppercase mb-1">Serial</label>
                        <input type="text" name="serial_maquina" placeholder="Ej: 0.100.C2/..." class="w-full bg-white border border-blue-200 rounded-lg p-2 text-sm shadow-sm outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-600 uppercase mb-1">Serial router</label>
                        <input type="text" name="serial_router" placeholder=" " class="w-full bg-white border border-blue-200 rounded-lg p-2 text-sm shadow-sm outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-600 uppercase mb-1">Serial ups</label>
                        <input type="text" name="serial_ups" class="w-full bg-white border border-blue-200 rounded-lg p-2 text-sm shadow-sm outline-none focus:border-blue-500">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">N° Remisión </label>
                <select name="numero_remision" class="w-full border-gray-300 rounded-lg select2-movil" required>
                    <option value="">- Seleccione Remisión -</option>
                    <?php foreach ($remisiones as $rem): ?>
                        <option value="<?= htmlspecialchars($rem['numero_remision']) ?>"><?= htmlspecialchars($rem['numero_remision']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-3 space-y-3">
                <div class="flex justify-between items-center border-b border-gray-300 pb-2">
                    <h2 class="font-bold text-gray-700 text-sm uppercase"><i class="fas fa-box-open text-blue-500 mr-1 text-lg"></i> Componentes</h2>
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold" id="badge_repuestos">0</span>
                </div>

                <button type="button" id="btn_abrir_repuestos" class="w-full border-2 border-dashed border-blue-300 text-blue-600 font-bold py-3 rounded-lg hover:bg-blue-50 transition flex items-center justify-center gap-2 bg-white">
                    <i class="fas fa-plus"></i> Añadir Componente (Repuesto)
                </button>

                <ul id="lista_repuestos_agregados" class="space-y-2 mt-2"></ul>
                <input type="hidden" name="json_repuestos" id="json_repuestos" value="[]">
            </div>

            <div>
                <label class="block text-xs font-bold text-orange-600 uppercase mb-1">Pendientes</label>
                <textarea name="pendientes" rows="2" placeholder="N/A o detalle pendientes..." class="w-full bg-orange-50 border border-orange-200 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-orange-500"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nombre admin</label>
                    <input type="text" name="administrador_punto" placeholder="Juana ..." class="w-full bg-white border border-gray-300 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Celular</label>
                    <input type="tel" name="celular_encargado" placeholder="321257..." class="w-full bg-white border border-gray-300 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado inicial</label>
                <select name="id_estado_inicial" class="w-full border-gray-300 rounded-lg select2-movil" required>
                    <option value="">- Seleccione Estado Inicial -</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= htmlspecialchars($est['id_estado']) ?>"><?= htmlspecialchars($est['nombre_estado']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Estado final</label>
                <select name="id_estado_maquina" class="w-full border-gray-300 rounded-lg select2-movil" required>
                    <option value="">- Seleccione Estado -</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= htmlspecialchars($est['id_estado']) ?>"><?= htmlspecialchars($est['nombre_estado']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr class="border-gray-200 my-4">

            <div class="space-y-5">
                <h3 class="font-bold text-gray-400 text-xs uppercase text-center">--- Completar para el Sistema ---</h3>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Actividades Realizadas</label>
                    <textarea name="actividades_realizadas" rows="3" required placeholder="Describa el trabajo realizado..." class="w-full bg-gray-50 border border-gray-300 rounded-lg p-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Soporte Remoto</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-headset text-gray-400"></i>
                        </div>
                        <input type="text" name="soporte_remoto" id="soporte_remoto" placeholder="Nombre de quien apoyó..." class="w-full bg-white border border-gray-300 rounded-lg py-3 pl-10 pr-3 text-sm text-gray-800 shadow-sm outline-none focus:border-blue-500">
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-4 space-y-4">
                    <div class="flex items-center gap-2 mb-1 border-b pb-2">
                        <i class="fas fa-camera text-indigo-500 text-lg"></i>
                        <h2 class="font-bold text-gray-700 text-sm uppercase">Evidencia Fotográfica</h2>
                    </div>
                    <p class="text-xs text-gray-500">Requerido: 8 a 10 fotos en total.</p>

                    <div>
                        <div class="border border-dashed border-gray-300 rounded-lg p-3 bg-white text-center file-upload-btn transition hover:bg-gray-100 relative">
                            <i class="fas fa-images text-gray-400 text-2xl mb-2"></i>
                            <p class="text-sm font-bold text-gray-700">1. Fotos del "Antes"</p>
                            <span id="badge_fotos_antes" class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-bold inline-block relative z-10">0 seleccionadas</span>
                            <input type="file" name="fotos_antes[]" id="fotos_antes" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                        </div>
                        <div id="preview_antes" class="flex flex-wrap gap-2 mt-2 justify-center"></div>
                    </div>

                    <div>
                        <div class="border border-dashed border-gray-300 rounded-lg p-3 bg-white text-center file-upload-btn transition hover:bg-gray-100 relative">
                            <i class="fas fa-file-signature text-gray-400 text-2xl mb-2"></i>
                            <p class="text-sm font-bold text-gray-700">2. Foto de la Remisión</p>
                            <span id="badge_foto_remision" class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-bold inline-block relative z-10">0 seleccionadas</span>
                            <input type="file" name="foto_remision[]" id="foto_remision" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                        </div>
                        <div id="preview_remision" class="flex flex-wrap gap-2 mt-2 justify-center"></div>
                    </div>

                    <div>
                        <div class="border border-dashed border-gray-300 rounded-lg p-3 bg-white text-center file-upload-btn transition hover:bg-gray-100 relative">
                            <i class="fas fa-check-double text-gray-400 text-2xl mb-2"></i>
                            <p class="text-sm font-bold text-gray-700">3. Fotos del "Después"</p>
                            <span id="badge_fotos_despues" class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-bold inline-block relative z-10">0 seleccionadas</span>
                            <input type="file" name="fotos_despues[]" id="fotos_despues" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                        </div>
                        <div id="preview_despues" class="flex flex-wrap gap-2 mt-2 justify-center"></div>
                    </div>

                    <div class="text-center pt-2 border-t border-gray-200">
                        <span class="text-sm font-bold text-gray-600">Total Evidencias: <span id="total_fotos_count" class="text-red-600 text-lg">0</span>/10</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Firma / Calificación del Cliente</label>
                    <select name="id_calificacion" class="w-full border-gray-300 rounded-lg select2-movil" required>
                        <option value="">- Seleccione Calificación -</option>
                        <?php foreach ($calificaciones as $calif): ?>
                            <option value="<?= htmlspecialchars($calif['id_calificacion']) ?>"><?= htmlspecialchars($calif['nombre_calificacion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 space-y-3">
                    <div class="flex items-center gap-2 mb-1 border-b pb-2">
                        <i class="fas fa-signature text-blue-500 text-lg"></i>
                        <h2 class="font-bold text-gray-700 text-sm uppercase">Firma del Cliente</h2>
                    </div>

                    <div class="border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50 touch-none">
                        <canvas id="canvas_firma" width="400" height="200" class="w-full h-48 bg-white cursor-crosshair"></canvas>
                    </div>

                    <button type="button" onclick="limpiarFirma()" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 rounded-lg text-sm transition flex items-center justify-center gap-2">
                        <i class="fas fa-eraser"></i> Limpiar Firma
                    </button>

                    <input type="hidden" name="firma_base64" id="firma_base64">
                </div>
            </div>

        </div>
    </form>
</div>

<div class="fixed bottom-0 left-0 w-full bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)] p-4 z-40 border-t border-gray-200">
    <button type="button" onclick="validarYEnviar()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition text-lg flex items-center justify-center gap-2">
        <i class="fas fa-check-circle"></i> GUARDAR Y FINALIZAR
    </button>
</div>

<div id="modalRepuestos" class="fixed inset-0 bg-black bg-opacity-70 hidden z-[100] justify-center items-center px-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-95" id="modal_content_rep">
        <div class="bg-blue-800 text-white p-3 flex justify-between items-center">
            <h3 class="font-bold text-sm uppercase"><i class="fas fa-box-open mr-1"></i> Añadir Repuesto</h3>
            <button type="button" onclick="cerrarModalRepuestos()" class="text-white hover:text-red-300 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Repuesto (Desde su inventario)</label>
                <select id="select_repuesto_modal" class="w-full select2-modal">
                    <option value="">- Buscar Repuesto -</option>
                    <?php if (!empty($inventario)): ?>
                        <?php foreach ($inventario as $inv): ?>
                            <option value="<?= $inv['id_repuesto'] ?>" data-nombre="<?= htmlspecialchars($inv['nombre_repuesto']) ?>">
                                <?= htmlspecialchars($inv['nombre_repuesto']) ?> (Disp: <?= $inv['cantidad_actual'] ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Su inventario está vacío</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Origen</label>
                    <select id="select_origen_modal" class="w-full border border-gray-300 rounded-lg p-3 text-sm bg-gray-50 outline-none focus:border-blue-500">
                        <option value="INEES">INEES</option>
                        <option value="PROSEGUR">PROSEGUR</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Cantidad</label>
                    <input type="number" id="cantidad_repuesto_modal" value="1" min="1" class="w-full border border-gray-300 rounded-lg p-3 text-sm text-center font-bold outline-none focus:border-blue-500">
                </div>
            </div>
            <button type="button" onclick="agregarRepuesto()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg shadow transition mt-2 flex justify-center items-center gap-2">
                <i class="fas fa-plus-circle"></i> Agregar a la Lista
            </button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="<?= BASE_URL ?>js/tecnico/notificaciones.js"></script>
<script src="<?= BASE_URL ?>js/tecnico/tecnicoReporte.js"></script>
<script src="<?= BASE_URL ?>js/tecnico/autoGuardado.js"></script>
<script src="<?= BASE_URL ?>js/tecnico/validaciones.js"></script>