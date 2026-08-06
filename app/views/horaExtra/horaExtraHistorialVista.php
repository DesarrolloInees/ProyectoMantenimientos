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
                // Definir color del Badge según estado
                $badgeColor = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                $iconoEstado = 'fa-clock';
                if ($he['estado_nombre'] === 'Aprobada') {
                    $badgeColor = 'bg-green-100 text-green-800 border-green-200';
                    $iconoEstado = 'fa-check-circle';
                } elseif ($he['estado_nombre'] === 'Rechazada') {
                    $badgeColor = 'bg-red-100 text-red-800 border-red-200';
                    $iconoEstado = 'fa-times-circle';
                }

                // Array de fotos
                $fotosArray = !empty($he['rutas_fotos']) ? explode('||', $he['rutas_fotos']) : [];
            ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Header de la tarjeta -->
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-600">
                        <i class="far fa-calendar-alt mr-1 text-blue-600"></i>
                        <?= date('d/m/Y', strtotime($he['fecha_reporte'])) ?>
                    </span>
                    <span class="px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase flex items-center gap-1 <?= $badgeColor ?>">
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
                                <?= date('h:i A', strtotime($he['hora_inicio'])) ?> - <?= date('h:i A', strtotime($he['hora_fin'])) ?>
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

                    <!-- Observación del supervisor (Si fue rechazada o comentada) -->
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
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Galería para ver Evidencias -->
<div id="modalFotos"
    class="fixed inset-0 bg-black bg-opacity-90 hidden z-[100] justify-center items-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative w-full max-w-2xl transform scale-95 transition-transform duration-300 space-y-3" id="modalContent">
        <!-- Header Modal -->
        <div class="flex justify-between items-center text-white">
            <h3 class="font-bold text-sm" id="tituloModalFoto">Evidencias</h3>
            <button type="button" onclick="cerrarModalFotos()"
                class="text-white hover:text-red-400 text-3xl leading-none">&times;</button>
        </div>

        <!-- Contenedor de Galería -->
        <div class="bg-white rounded-xl overflow-hidden p-3 flex flex-wrap gap-2 justify-center max-h-[75vh] overflow-y-auto" id="galeriaContenedor">
            <!-- Las imágenes se inyectan dinámicamente -->
        </div>
    </div>
</div>

<script>
    if (typeof window.BASE_URL === 'undefined') {
        window.BASE_URL = '<?= BASE_URL ?>';
    }

    function abrirModalFotos(rutasArray, fechaReporte) {
        $('#tituloModalFoto').text('Evidencias - Reporte ' + fechaReporte);
        const contenedor = $('#galeriaContenedor');
        contenedor.empty();

        rutasArray.forEach(function(ruta) {
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

    // Cerrar modal al hacer clic afuera
    $('#modalFotos').on('click', function (e) {
        if (e.target === this) {
            cerrarModalFotos();
        }
    });
</script>