<!-- ============================================== -->
<!-- ORDEN DE SERVICIO - CREAR (VISTA OPTIMIZADA) -->
<!-- ============================================== -->

<!-- LIBRERÍAS EXTERNAS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<!-- ESTILOS PERSONALIZADOS -->
<style>
    .select2-container .select2-selection--single {
        height: 100% !important;
        padding: 0.25rem !important;
        border-color: #d1d5db !important;
        border-radius: 0.25rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 0 !important;
        bottom: 0 !important;
        height: 100% !important;
    }

    .select2-search__field {
        outline: none !important;
    }

    .select2-container--open {
        z-index: 99999999 !important;
    }

    .select2-search__field {
        z-index: 99999999 !important;
    }
</style>

<!-- CONTENIDO PRINCIPAL -->
<div class="w-full bg-white shadow-xl rounded-lg p-2 md:p-6">

    <form action="index.php?pagina=ordenCrear&accion=guardar" method="POST" id="formServicios">

        <!-- HEADER CON FECHA -->
        <div class="bg-gradient-to-r from-gray-800 to-gray-700 p-4 rounded-lg mb-6 flex flex-wrap gap-4 items-center text-white shadow-md">
            <div>
                <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Fecha del Reporte</label>
                <input type="date" name="fecha_reporte" value="<?= date('Y-m-d') ?>"
                    class="text-gray-900 border-none p-2 rounded w-40 font-bold focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- BOTÓN FLOTANTE AGREGAR -->
        <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-2">
            <div class="bg-blue-600 text-white text-xs font-bold py-1 px-3 rounded-full shadow-lg">
                <span id="contadorFilasDisplay">0</span> Servicios agregados
            </div>

            <button type="button" onclick="agregarFila()"
                class="bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-6 rounded-full shadow-2xl transition transform hover:scale-110 flex items-center gap-2">
                <i class="fas fa-plus text-xl"></i>
                <span class="font-bold">Agregar Servicio</span>
            </button>
        </div>

        <!-- TABLA DE SERVICIOS -->
        <div class="overflow-x-auto shadow-inner rounded-lg border border-gray-200" style="max-height: 60vh; overflow-y: auto;">
            <table class="min-w-max text-xs w-full">
                <thead class="sticky top-0 z-20 bg-gray-100">
                    <tr class="text-gray-600 uppercase tracking-wider border-b-2 border-gray-300 h-10">
                        <th class="px-2 sticky left-0 bg-gray-100 z-10 w-8">#</th>
                        <th class="px-2 w-40">Técnico</th>
                        <th class="px-2 w-32">Remisión</th>
                        <th class="px-2 w-48">Ubicación (Cliente/Punto)</th>
                        <th class="px-2 w-40">Máquina / Device ID</th>
                        <th class="px-2 w-40">Modalidad Operativa</th>
                        <th class="px-2 w-40">Resultado Mantenimiento</th>
                        <th class="px-2 w-32">Tiempos Servicio</th>
                        <th class="px-2 w-24 text-center">Duración</th>
                        <th class="px-2 w-32">Valor Servicio</th>
                        <th class="px-2 w-32">Repuestos</th>
                        <th class="px-2 w-32">Estado Final</th>
                        <th class="px-2 w-32">Calificación</th>
                        <th class="px-2 w-48">¿Qué se Realizó?</th>
                        <th class="px-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="contenedorFilas" class="divide-y divide-gray-100 bg-white">
                    <!-- Filas dinámicas -->
                </tbody>
            </table>
        </div>

        <!-- BOTÓN GUARDAR -->
        <div class="mt-8 text-center pb-8">
            <button type="button" id="btnGuardarFijo"
                class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-4 px-12 rounded-xl shadow-xl transform hover:scale-105 transition text-lg">
                <i class="fas fa-save mr-2"></i> GUARDAR REPORTE COMPLETO
            </button>
        </div>

    </form>
</div>

<!-- MODAL DE REPUESTOS -->
<div id="modalRepuestos" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg p-6 transform scale-100 transition-transform">

        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between">
            <span>🛠️ Gestión de Repuestos</span>
            <button type="button" onclick="cerrarModal()" class="text-gray-400 hover:text-red-500">
                <i class="fas fa-times"></i>
            </button>
        </h3>

        <input type="hidden" id="modal_fila_actual">

        <div class="space-y-4">
            <div class="flex gap-2 items-center">
                <div class="flex-grow w-2/3">
                    <select id="select_repuesto_modal" class="w-full border rounded p-2 text-sm">
                        <option value="">- Buscar Repuesto -</option>
                    </select>
                </div>

                <div class="w-20">
                    <input type="number" id="cantidad_repuesto_modal" value="1" min="1"
                        class="w-full border rounded p-2 text-sm text-center font-bold bg-gray-50 h-[38px]"
                        placeholder="Cant.">
                </div>

                <div class="w-1/3">
                    <select id="select_origen_modal"
                        class="w-full border rounded p-2 text-xs bg-gray-100 font-bold text-gray-700 h-[38px]">
                        <option value="INEES">INEES</option>
                        <option value="PROSEGUR">PROSEGUR</option>
                    </select>
                </div>

                <button type="button" onclick="agregarRepuestoALista()"
                    class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 shadow transition h-[38px]">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <ul id="lista_repuestos_visual" class="border rounded p-2 h-48 overflow-y-auto bg-gray-50 text-sm">
                <li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>
            </ul>
        </div>

        <div class="mt-6 text-right border-t pt-4">
            <button type="button" onclick="guardarCambiosModal()"
                class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 shadow-lg transform hover:scale-105 transition">
                <i class="fas fa-check mr-2"></i> Confirmar Cambios
            </button>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- INYECCIÓN DE DATOS PHP A JAVASCRIPT -->
<!-- ============================================== -->
<script>
    // Datos maestros desde PHP (se inyectan globalmente)
    const listaClientes = <?= json_encode($clientes ?? []) ?>;
    const listaMantos = <?= json_encode($tiposManto ?? []) ?>;
    const listaTecnicos = <?= json_encode($tecnicos ?? []) ?>;
    const listaEstados = <?= json_encode($estados ?? []) ?>;
    const listaCalif = <?= json_encode($califs ?? []) ?>;
    const listaRepuestosBD = <?= json_encode($listaRepuestos ?? []) ?>;
    const FESTIVOS_DB = <?= json_encode($listaFestivos ?? []) ?>;
</script>

<!-- ============================================== -->
<!-- MÓDULOS JAVASCRIPT (EN ORDEN DE DEPENDENCIA) -->
<!-- ============================================== -->

<!-- NOTA: Cambia estas rutas según tu estructura de archivos -->
<!-- Ejemplo: Si los guardas en /assets/js/modulos/ -->
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/config.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/ajaxUtils.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/uiUtils.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/timeManager.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/filaManager.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/repuestosManager.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/storageManager.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/app.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/crearNotificaciones.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/validadorRemisiones.js"></script>