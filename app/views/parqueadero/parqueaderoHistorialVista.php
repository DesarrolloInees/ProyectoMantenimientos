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

                <div class="p-4">
                    <h3 class="font-bold text-gray-800 text-sm mb-2"><i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                        <?= htmlspecialchars($fac['nombre_punto']) ?></h3>

                    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
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

                    <button type="button"
                        onclick="abrirModalFoto('<?= BASE_URL . $fac['ruta_foto'] ?>', '<?= htmlspecialchars($fac['numero_factura']) ?>')"
                        class="w-full border border-blue-500 text-blue-600 hover:bg-blue-50 font-bold py-2 rounded-lg transition flex items-center justify-center gap-2 text-sm">
                        <i class="fas fa-image"></i> Ver Foto de Factura
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal para ver la foto -->
<div id="modalFoto"
    class="fixed inset-0 bg-black bg-opacity-90 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-2xl transform scale-95 transition-transform duration-300" id="modalContent">
        <!-- Header Modal -->
        <div class="flex justify-between items-center mb-2 text-white">
            <h3 class="font-bold text-sm" id="tituloModalFoto">Factura</h3>
            <button type="button" onclick="cerrarModalFoto()"
                class="text-white hover:text-red-400 text-3xl leading-none">&times;</button>
        </div>
        <!-- Imagen -->
        <div class="bg-white rounded-lg overflow-hidden flex justify-center items-center min-h-[200px]">
            <img id="imagenModal" src="" alt="Foto Factura" class="max-w-full max-h-[80vh] object-contain">
        </div>
    </div>
</div>

<script>
    // Se asume que BASE_URL está definida en el layout principal (plantillaVista.php) o index.php
    if (typeof window.BASE_URL === 'undefined') {
        window.BASE_URL = '<?= BASE_URL ?>';
    }

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

    // Cerrar modal al hacer clic afuera de la imagen
    $('#modalFoto').on('click', function (e) {
        if (e.target === this) {
            cerrarModalFoto();
        }
    });
</script>