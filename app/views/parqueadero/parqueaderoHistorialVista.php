<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<style>
    body {
        background-color: #f1f5f9;
        padding-bottom: 30px;
    }
</style>

<!-- HEADER FIJO -->
<div class="bg-blue-800 text-white p-4 shadow-md sticky top-0 z-40 flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <button onclick="window.history.back();"
            class="text-white bg-blue-700 hover:bg-blue-600 p-2 rounded-full w-10 h-10 flex items-center justify-center transition">
            <i class="fas fa-arrow-left"></i>
        </button>
        <div>
            <h1 class="font-bold text-lg leading-tight">Mis Parqueaderos</h1>
            <p class="text-blue-200 text-xs">Historial de facturas subidas</p>
        </div>
    </div>
    <a href="index.php?pagina=parqueaderoCrear"
        class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center transition shadow">
        <i class="fas fa-plus"></i>
    </a>
</div>

<div class="max-w-lg mx-auto p-3 mt-2 space-y-4">
    <?php if (empty($facturas)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center mt-10">
            <i class="fas fa-receipt text-gray-300 text-5xl mb-3"></i>
            <h2 class="text-gray-500 font-bold text-lg">Sin registros</h2>
            <p class="text-gray-400 text-sm mt-1">Aún no has subido ninguna factura de parqueadero.</p>
        </div>
    <?php else: ?>
        <?php foreach ($facturas as $fac): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500"><i class="far fa-calendar-alt mr-1"></i>
                        <?= date('d/m/Y', strtotime($fac['fecha_servicio'])) ?></span>
                    <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-[10px] font-bold uppercase">Factura:
                        <?= htmlspecialchars($fac['numero_factura']) ?></span>
                </div>

                <div class="p-4 space-y-3">
                    <h3 class="font-bold text-gray-800 text-sm"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                        <?= htmlspecialchars($fac['nombre_punto']) ?>
                    </h3>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-gray-50 p-2 rounded border border-gray-100">
                            <span class="block text-gray-400 uppercase font-bold text-[9px]">Horario</span>
                            <span class="text-gray-700 font-semibold"><?= date('h:i A', strtotime($fac['hora_inicio'])) ?> -
                                <?= date('h:i A', strtotime($fac['hora_fin'])) ?></span>
                        </div>
                        <div class="bg-green-50 p-2 rounded border border-green-100 text-right">
                            <span class="block text-green-500 uppercase font-bold text-[9px]">Valor Pagado</span>
                            <span
                                class="text-green-700 font-bold text-sm">$<?= number_format($fac['valor_factura'], 2, ',', '.') ?></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                            onclick="abrirModalFoto('<?= BASE_URL . $fac['ruta_foto'] ?>', '<?= htmlspecialchars($fac['numero_factura']) ?>')"
                            class="border border-blue-500 text-blue-600 hover:bg-blue-50 font-bold py-2 rounded-lg transition flex items-center justify-center gap-1.5 text-xs">
                            <i class="fas fa-image"></i> Ver Foto
                        </button>

                        <button type="button" onclick='abrirModalEditar(<?= json_encode($fac) ?>)'
                            class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition flex items-center justify-center gap-1.5 text-xs shadow">
                            <i class="fas fa-edit"></i> Editar Factura
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal para ver la foto -->
<div id="modalFoto"
    class="fixed inset-0 bg-black bg-opacity-90 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-2xl transform scale-95 transition-transform duration-300" id="modalContent">
        <div class="flex justify-between items-center mb-2 text-white">
            <h3 class="font-bold text-sm" id="tituloModalFoto">Factura</h3>
            <button type="button" onclick="cerrarModalFoto()"
                class="text-white hover:text-red-400 text-3xl leading-none">&times;</button>
        </div>
        <div class="bg-white rounded-lg overflow-hidden flex justify-center items-center min-h-[200px]">
            <img id="imagenModal" src="" alt="Foto Factura" class="max-w-full max-h-[80vh] object-contain">
        </div>
    </div>
</div>

<!-- Modal Editar Factura Parqueadero -->
<div id="modalEditar"
    class="fixed inset-0 bg-black/80 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300"
        id="modalContentEditar">
        <div class="bg-amber-600 text-white p-4 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-base"><i class="fas fa-edit mr-1"></i> Corregir Factura Parqueadero</h3>
                <p class="text-amber-100 text-xs">Modifica datos o cambia la imagen del recibo</p>
            </div>
            <button type="button" onclick="cerrarModalEditar()"
                class="text-white hover:text-red-200 text-2xl leading-none">&times;</button>
        </div>

        <form id="formEditarParqueadero" enctype="multipart/form-data"
            class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
            <input type="hidden" id="edit_id_factura" name="id_factura">

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Punto Visitado <span
                        class="text-red-500">*</span></label>
                <select id="edit_id_punto" name="id_punto" required
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500">
                    <option value="">- Seleccionar punto -</option>
                    <?php if (!empty($puntos)): ?>
                        <?php foreach ($puntos as $p): ?>
                            <option value="<?= $p['id_punto'] ?>"><?= htmlspecialchars($p['nombre_punto']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha del Servicio <span
                        class="text-red-500">*</span></label>
                <input type="date" id="edit_fecha_servicio" name="fecha_servicio" required
                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hora Inicio <span
                            class="text-red-500">*</span></label>
                    <input type="time" id="edit_hora_inicio" name="hora_inicio" required
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hora Fin <span
                            class="text-red-500">*</span></label>
                    <input type="time" id="edit_hora_fin" name="hora_fin" required
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">N° Factura <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="edit_numero_factura" name="numero_factura" required
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Valor Pagado ($) <span
                            class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="edit_valor_factura" name="valor_factura" required
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-semibold outline-none focus:border-amber-500">
                </div>
            </div>

            <!-- Imagen Actual y Cambio de Imagen -->
            <div class="border-t pt-3 space-y-2">
                <label class="block text-xs font-bold text-gray-700 uppercase">Foto de la Factura</label>

                <div class="flex items-center gap-3">
                    <!-- Foto Actual con botón de eliminación -->
                    <div id="container_foto_actual"
                        class="w-20 h-20 rounded-lg border border-gray-300 overflow-hidden relative shadow-sm">
                        <img id="edit_foto_actual" src="" class="w-full h-full object-cover">
                        <button type="button" onclick="eliminarFotoFacturaActual()"
                            class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] shadow hover:bg-red-700 transition"
                            title="Eliminar foto actual">
                            <i class="fas fa-times"></i>
                        </button>
                        <span
                            class="absolute bottom-0 inset-x-0 bg-gray-800/80 text-white text-[8px] text-center font-bold uppercase">ACTUAL</span>
                    </div>

                    <!-- Previsualización de Nueva Foto -->
                    <div id="edit_preview_nueva_container"
                        class="hidden w-20 h-20 rounded-lg border-2 border-amber-400 overflow-hidden relative shadow-sm">
                        <img id="edit_preview_nueva" src="" class="w-full h-full object-cover">
                        <button type="button" onclick="cancelarNuevaFoto()"
                            class="absolute top-1 right-1 bg-gray-700 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] shadow hover:bg-gray-800 transition"
                            title="Quitar nueva foto seleccionada">
                            <i class="fas fa-times"></i>
                        </button>
                        <span
                            class="absolute bottom-0 inset-x-0 bg-amber-600/90 text-white text-[8px] text-center font-bold uppercase">NUEVA</span>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Cargar/Reemplazar foto de
                        factura:</label>
                    <input type="file" id="edit_foto_factura" name="foto_factura" accept="image/*"
                        onchange="previsualizarFotoEdicion(this)"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 cursor-pointer">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="cerrarModalEditar()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2 rounded-lg text-sm transition">
                    Cancelar
                </button>
                <button type="button" onclick="guardarEdicionParqueadero()"
                    class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2 rounded-lg text-sm shadow transition flex items-center gap-1.5">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    if (typeof window.BASE_URL === 'undefined') {
        window.BASE_URL = '<?= BASE_URL ?>';
    }

    // Modal Ver Foto
    function abrirModalFoto(rutaCompleta, numeroFactura) {
        $('#tituloModalFoto').text('Factura N° ' + numeroFactura);
        $('#imagenModal').attr('src', rutaCompleta);

        $('#modalFoto').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalFoto').removeClass('opacity-0').addClass('opacity-100');
            $('#modalContent').removeClass('scale-95').addClass('scale-100');
        }, 10);
    }

    function cerrarModalFoto() {
        $('#modalFoto').removeClass('opacity-100').addClass('opacity-0');
        $('#modalContent').removeClass('scale-100').addClass('scale-95');

        setTimeout(() => {
            $('#modalFoto').removeClass('flex').addClass('hidden');
            $('#imagenModal').attr('src', '');
        }, 300);
    }

    // Modal Editar
    function abrirModalEditar(data) {
        $('#edit_id_factura').val(data.id_factura_parqueadero);
        $('#edit_id_punto').val(data.id_punto);
        $('#edit_fecha_servicio').val(data.fecha_servicio);
        $('#edit_hora_inicio').val(data.hora_inicio);
        $('#edit_hora_fin').val(data.hora_fin);
        $('#edit_numero_factura').val(data.numero_factura);
        $('#edit_valor_factura').val(data.valor_factura);

        // Foto actual
        $('#edit_foto_actual').attr('src', window.BASE_URL + data.ruta_foto);
        $('#edit_foto_factura').val('');
        $('#edit_preview_nueva_container').addClass('hidden');

        $('#modalEditar').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modalEditar').removeClass('opacity-0').addClass('opacity-100');
            $('#modalContentEditar').removeClass('scale-95').addClass('scale-100');
        }, 10);
    }

    function eliminarFotoFacturaActual() {
    const idFactura = $('#edit_id_factura').val();
    if (!idFactura) return;

    if (!confirm('¿Seguro que deseas eliminar la foto actual de la factura? Deberás subir una nueva foto para guardar.')) return;

    $.ajax({
        url: 'index.php?pagina=parqueaderoHistorial&accion=ajaxEliminarFoto',
        type: 'POST',
        data: { id_factura: idFactura },
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                $('#container_foto_actual').fadeOut(300, function() {
                    $(this).addClass('hidden');
                });
                alert('⚠️ ' + res.msj);
            } else {
                alert('❌ ' + res.msj);
            }
        },
        error: function () {
            alert('❌ Error de conexión al eliminar la imagen.');
        }
    });
}

function cancelarNuevaFoto() {
    $('#edit_foto_factura').val('');
    $('#edit_preview_nueva_container').addClass('hidden');
}

    function cerrarModalEditar() {
        $('#modalEditar').removeClass('opacity-100').addClass('opacity-0');
        $('#modalContentEditar').removeClass('scale-100').addClass('scale-95');

        setTimeout(() => {
            $('#modalEditar').removeClass('flex').addClass('hidden');
        }, 300);
    }

    function previsualizarFotoEdicion(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#edit_preview_nueva').attr('src', e.target.result);
                $('#edit_preview_nueva_container').removeClass('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            $('#edit_preview_nueva_container').addClass('hidden');
        }
    }

    function guardarEdicionParqueadero() {
        const formElement = document.getElementById('formEditarParqueadero');
        const formData = new FormData(formElement);

        $.ajax({
            url: 'index.php?pagina=parqueaderoHistorial&accion=ajaxEditarFactura',
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
                alert('❌ Error al intentar procesar los cambios.');
            }
        });
    }

    // Cerrar modales al hacer clic afuera
    $('#modalFoto, #modalEditar').on('click', function (e) {
        if (e.target === this) {
            cerrarModalFoto();
            cerrarModalEditar();
        }
    });
</script>