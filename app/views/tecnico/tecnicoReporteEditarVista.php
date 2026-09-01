<div class="container mx-auto p-2 sm:p-4 max-w-3xl mb-24">
    <!-- Encabezado de la Orden -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
        <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-2">
            <i class="fas fa-edit text-amber-500 mr-2"></i> Editando Servicio #<?= $orden['id_ordenes_servicio'] ?>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
            <div><span class="font-semibold text-gray-600">Cliente:</span> <?= htmlspecialchars($orden['nombre_cliente'] ?? 'N/A') ?></div>
            <div><span class="font-semibold text-gray-600">Punto:</span> <?= htmlspecialchars($orden['nombre_punto'] ?? 'N/A') ?></div>
            <div class="sm:col-span-2 text-gray-500"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($orden['direccion_punto'] ?? 'N/A') ?></div>
        </div>
    </div>

    <!-- Cargar repuestos desde PHP a JavaScript de forma segura -->
    <script>
        window.repuestosGuardadosDesdeBD = <?= json_encode($repuestosGuardados ?? []) ?>;
        
        $(document).ready(function() {
            if(window.repuestosGuardadosDesdeBD && window.repuestosGuardadosDesdeBD.length > 0) {
                repuestosSeleccionados = window.repuestosGuardadosDesdeBD.map(r => ({
                    id: r.id,
                    nombre: r.nombre,
                    cantidad: r.cantidad,
                    origen: r.origen
                }));
                renderizarListaRepuestos();
            }
        });
    </script>

    <form id="formReporteMovil" action="index.php?pagina=tecnicoReporteEditar&accion=actualizar" method="POST" class="space-y-6">
        <input type="hidden" name="id_ordenes_servicio" value="<?= $orden['id_ordenes_servicio'] ?>">
        <input type="hidden" name="json_repuestos" id="json_repuestos" value="">

        <!-- SECCIÓN 1: Tiempos y Remisión -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-clock text-blue-500 mr-1"></i> Tiempos y Remisión</h3>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Hora Entrada *</label>
                    <input type="time" name="hora_entrada" id="hora_entrada" required 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           value="<?= htmlspecialchars($reporteGuardado['hora_entrada'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Hora Salida *</label>
                    <input type="time" name="hora_salida" id="hora_salida" required 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           value="<?= htmlspecialchars($reporteGuardado['hora_salida'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tiempo de Servicio</label>
                <input type="text" name="tiempo_servicio" id="tiempo_servicio" readonly 
                       class="w-full bg-gray-100 border-gray-300 rounded-md shadow-sm text-gray-600 font-bold"
                       value="<?= htmlspecialchars($reporteGuardado['tiempo_servicio'] ?? '00:00') ?>">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nº Remisión *</label>
                <select name="numero_remision" class="w-full border-gray-300 rounded-md shadow-sm select2-movil" required>
                    <option value="">Seleccione...</option>
                    <?php if (!empty($reporteGuardado['numero_remision'])): ?>
                        <option value="<?= $reporteGuardado['numero_remision'] ?>" selected><?= $reporteGuardado['numero_remision'] ?> (Guardada)</option>
                    <?php endif; ?>
                    <?php foreach ($remisiones as $rem): ?>
                        <?php if($rem['numero_remision'] !== $reporteGuardado['numero_remision']): ?>
                            <option value="<?= $rem['numero_remision'] ?>"><?= $rem['numero_remision'] ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- SECCIÓN 2: Datos de la Máquina -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-server text-blue-500 mr-1"></i> Datos de la Máquina</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nº Máquina</label>
                    <input type="text" name="numero_maquina" class="w-full border-gray-300 rounded-md shadow-sm"
                           value="<?= htmlspecialchars($reporteGuardado['numero_maquina'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Serial Máquina</label>
                    <input type="text" name="serial_maquina" class="w-full border-gray-300 rounded-md shadow-sm"
                           value="<?= htmlspecialchars($reporteGuardado['serial_maquina'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Serial Router</label>
                    <input type="text" name="serial_router" class="w-full border-gray-300 rounded-md shadow-sm"
                           value="<?= htmlspecialchars($reporteGuardado['serial_router'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Serial UPS</label>
                    <input type="text" name="serial_ups" class="w-full border-gray-300 rounded-md shadow-sm"
                           value="<?= htmlspecialchars($reporteGuardado['serial_ups'] ?? '') ?>">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Estado Inicial</label>
                    <select name="id_estado_inicial" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Seleccione...</option>
                        <?php foreach ($estados as $est): ?>
                            <option value="<?= $est['id_estado'] ?>" <?= ($reporteGuardado['id_estado_inicial'] == $est['id_estado']) ? 'selected' : '' ?>>
                                <?= $est['nombre_estado'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: Detalles del Mantenimiento -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-tools text-blue-500 mr-1"></i> Detalles del Trabajo</h3>
            
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de Servicio *</label>
                <select name="id_tipo_mantenimiento" class="w-full border-gray-300 rounded-md shadow-sm select2-movil" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($tiposManto as $tipo): ?>
                        <option value="<?= $tipo['id_tipo_mantenimiento'] ?>" <?= ($reporteGuardado['id_tipo_mantenimiento'] == $tipo['id_tipo_mantenimiento']) ? 'selected' : '' ?>>
                            <?= $tipo['nombre_completo'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Actividades Realizadas *</label>
                <textarea name="actividades_realizadas" rows="3" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500"><?= htmlspecialchars($reporteGuardado['actividades_realizadas'] ?? '') ?></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Pendientes</label>
                <textarea name="pendientes" rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500"><?= htmlspecialchars($reporteGuardado['pendientes'] ?? '') ?></textarea>
            </div>

            <div class="mb-4">
                <label class="flex items-center space-x-2 text-sm font-semibold text-gray-700">
                    <input type="checkbox" name="tiene_novedad" id="tiene_novedad" class="rounded text-blue-600 focus:ring-blue-500" <?= !empty($reporteGuardado['tiene_novedad']) ? 'checked' : '' ?>>
                    <span>¿Hubo alguna novedad en el servicio?</span>
                </label>
            </div>
            
            <div id="contenedor_novedad" class="<?= empty($reporteGuardado['tiene_novedad']) ? 'hidden' : '' ?>">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Detalle de la Novedad</label>
                <textarea name="detalle_novedad" rows="2" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 border-red-200"><?= htmlspecialchars($reporteGuardado['detalle_novedad'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- SECCIÓN 4: Repuestos -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-bold text-gray-700"><i class="fas fa-cogs text-blue-500 mr-1"></i> Repuestos Usados</h3>
                <span id="badge_repuestos" class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">0</span>
            </div>
            
            <ul id="lista_repuestos_agregados" class="space-y-2 mb-3">
                <!-- Se llena mediante JS -->
            </ul>

            <button type="button" id="btn_abrir_repuestos" class="w-full bg-gray-50 hover:bg-gray-100 text-blue-600 font-semibold py-2 px-4 border border-blue-200 rounded-md shadow-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-plus-circle"></i> Modificar Repuestos
            </button>
        </div>

        <!-- SECCIÓN 5: Cierre del Servicio -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-flag-checkered text-blue-500 mr-1"></i> Cierre</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Estado Final *</label>
                    <select name="id_estado_maquina" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($estados as $est): ?>
                            <option value="<?= $est['id_estado'] ?>" <?= ($reporteGuardado['id_estado_maquina'] == $est['id_estado']) ? 'selected' : '' ?>>
                                <?= $est['nombre_estado'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Calificación del Servicio</label>
                    <select name="id_calificacion" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Seleccione...</option>
                        <?php foreach ($calificaciones as $cal): ?>
                            <option value="<?= $cal['id_calificacion'] ?>" <?= ($reporteGuardado['id_calificacion'] == $cal['id_calificacion']) ? 'selected' : '' ?>>
                                <?= $cal['nombre_calificacion'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Soporte Remoto (Opcional)</label>
                <input type="text" name="soporte_remoto" placeholder="Nombre de quien dio soporte" class="w-full border-gray-300 rounded-md shadow-sm"
                       value="<?= htmlspecialchars($reporteGuardado['soporte_remoto'] ?? '') ?>">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Administrador del Punto</label>
                    <input type="text" name="administrador_punto" class="w-full border-gray-300 rounded-md shadow-sm"
                           value="<?= htmlspecialchars($reporteGuardado['administrador_punto'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Celular Encargado</label>
                    <input type="tel" name="celular_encargado" class="w-full border-gray-300 rounded-md shadow-sm"
                           value="<?= htmlspecialchars($reporteGuardado['celular_encargado'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- SECCIÓN 6: Evidencias Fotográficas -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-camera text-blue-500 mr-1"></i> Evidencias Fotográficas</h3>
            <p class="text-xs text-gray-500 mb-4">Las fotos se guardan automáticamente al seleccionarlas.</p>

            <!-- FOTOS ANTES -->
            <div class="mb-5 border-b pb-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-semibold text-gray-700">Fotos ANTES</label>
                    <span id="badge_fotos_antes" class="text-[10px] bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full font-bold">0 subidas</span>
                </div>
                <input type="file" id="fotos_antes" accept="image/*" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-2">
                <div id="preview_antes" class="flex flex-wrap gap-2 mt-2"></div>
            </div>

            <!-- FOTO REMISIÓN -->
            <div class="mb-5 border-b pb-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-semibold text-gray-700">Foto REMISIÓN</label>
                    <span id="badge_foto_remision" class="text-[10px] bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full font-bold">0 subidas</span>
                </div>
                <input type="file" id="foto_remision" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-2">
                <div id="preview_remision" class="flex flex-wrap gap-2 mt-2"></div>
            </div>

            <!-- FOTOS DESPUÉS -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-semibold text-gray-700">Fotos DESPUÉS</label>
                    <span id="badge_fotos_despues" class="text-[10px] bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full font-bold">0 subidas</span>
                </div>
                <input type="file" id="fotos_despues" accept="image/*" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-2">
                <div id="preview_despues" class="flex flex-wrap gap-2 mt-2"></div>
            </div>
        </div>

        <!-- SECCIÓN 7: Firma -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="font-bold text-gray-700 mb-3"><i class="fas fa-signature text-blue-500 mr-1"></i> Firma del Cliente</h3>
            <p class="text-xs text-amber-600 font-semibold mb-2"><i class="fas fa-info-circle"></i> Ya existe una firma guardada. Dibuja aquí solo si deseas reemplazarla.</p>
            
            <div class="border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 touch-none relative overflow-hidden" style="height: 200px;">
                <canvas id="canvas_firma" class="w-full h-full absolute top-0 left-0"></canvas>
            </div>
            
            <div class="flex justify-end mt-2">
                <button type="button" onclick="limpiarFirma()" class="text-xs text-red-500 hover:text-red-700 font-semibold px-3 py-1">
                    <i class="fas fa-eraser"></i> Limpiar Pizarra
                </button>
            </div>
            <input type="hidden" name="firma_base64" id="firma_base64">
        </div>

        <!-- Botón de Envío -->
        <button type="button" onclick="validarYActualizar()" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded-lg shadow-lg text-lg flex items-center justify-center gap-2 transition duration-200">
            <i class="fas fa-sync-alt"></i> Actualizar Reporte
        </button>
    </form>
</div>

<!-- Modal de Repuestos -->
<div id="modalRepuestos" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md flex flex-col max-h-[90vh]">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="font-bold text-gray-800"><i class="fas fa-tools text-blue-500"></i> Agregar Repuesto</h3>
            <button type="button" onclick="cerrarModalRepuestos()" class="text-gray-400 hover:text-red-500 transition text-xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-4 overflow-y-auto">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Catálogo de Repuestos</label>
                <select id="select_repuesto_modal" class="w-full border-gray-300 rounded-md">
                    <option value="">Seleccione o busque...</option>
                    <?php foreach ($inventario as $inv): ?>
                        <option value="<?= $inv['id_repuesto'] ?>" data-nombre="<?= htmlspecialchars($inv['nombre_repuesto']) ?>">
                            <?= $inv['nombre_repuesto'] ?> <?= !empty($inv['codigo_referencia']) ? '('.$inv['codigo_referencia'].')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Origen</label>
                    <select id="select_origen_modal" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="INEES">INEES</option>
                        <option value="PROSEGUR">PROSEGUR</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Cantidad Sugerida</label>
                    <input type="number" id="cantidad_repuesto_modal" min="1" value="1" class="w-full border-gray-300 rounded-md shadow-sm text-center font-bold">
                </div>
            </div>
        </div>
        
        <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
            <button type="button" onclick="cerrarModalRepuestos()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 font-semibold transition">Cancelar</button>
            <button type="button" onclick="agregarRepuesto()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold transition flex items-center gap-2">
                <i class="fas fa-plus"></i> Agregar
            </button>
        </div>
    </div>
</div>

<!-- Scripts Necesarios -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/tecnico/tecnicoReporte.js"></script>
<script src="js/tecnico/validacionesEditar.js"></script>
<script>
    // Mostrar/Ocultar Novedad Dinámicamente
    $('#tiene_novedad').on('change', function() {
        if($(this).is(':checked')) {
            $('#contenedor_novedad').slideDown();
        } else {
            $('#contenedor_novedad').slideUp();
            $('textarea[name="detalle_novedad"]').val('');
        }
    });
</script>