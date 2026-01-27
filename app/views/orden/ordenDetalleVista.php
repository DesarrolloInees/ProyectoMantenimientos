<style>
    /* Ajuste para que los Select2 de la tabla se vean bien */
    .select2-container--default .select2-selection--single {
        height: 28px !important;
        min-height: 28px !important;
        padding: 0px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 4px !important;
        display: flex !important;
        align-items: center !important;
        background-color: #ffffff !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        padding-left: 6px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #374151 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 26px !important;
        top: 1px !important;
    }

    /* Colores visuales para identificar columnas rápidamente */
    #tablaEdicion select[name*="[id_tecnico]"] {
        color: #4338ca !important;
        font-weight: bold;
        background-color: #eef2ff !important;
    }

    #tablaEdicion select[name*="[id_manto]"] {
        color: #1e40af !important;
        font-weight: bold;
        background-color: #eff6ff !important;
    }

    #tablaEdicion select[name*="[id_maquina]"] {
        color: #0369a1 !important;
        font-family: monospace;
        font-weight: bold;
    }

    #tablaEdicion input[name*="[valor]"] {
        color: #15803d !important;
        font-weight: bold;
        background-color: #f0fdf4 !important;
    }

    /* Z-Index Fix para Select2 dentro de esta vista específica */
    .select2-dropdown {
        z-index: 1050 !important;
        /* Encima de tablas, debajo del Navbar (9000) */
    }
</style>

<div class="bg-white p-4 rounded shadow-lg w-full mb-20">
    <div class="flex flex-wrap justify-between items-center mb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">🛠️ Edición Maestra de Servicios</h2>
            <p class="text-sm text-blue-600 font-bold">Fecha Lote: <?= $fecha ?></p>
        </div>

        <div class="space-x-2">
            <button type="button" onclick="exportarExcelLimpio()"
                class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow transition">
                <i class="fas fa-file-excel mr-2"></i> Excel Limpio
            </button>

            <a href="<?= BASE_URL ?>inicio"
                class="bg-gray-500 text-white px-4 py-2 rounded font-bold hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left"></i> Volver
            </a>

            <button type="button" onclick="exportarExcelNovedades()"
                class="bg-red-600 text-white px-4 py-2 rounded font-bold hover:bg-red-700 shadow ml-2 transition">
                <i class="fas fa-file-contract mr-2"></i> Reporte Novedades
            </button>
        </div>
    </div>

    <div class="flex justify-between items-center mt-4 bg-gray-50 p-3 rounded border border-gray-200">
        <div class="text-sm font-bold text-gray-700">
            Mostrando <span id="infoPaginaTop"></span>
        </div>
        <div class="space-x-2">
            <button type="button" onclick="cambiarPagina(-1)" class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span id="indicadorPaginaTop" class="font-bold text-lg px-3">1</span>
            <button type="button" onclick="cambiarPagina(1)" class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="text-sm text-gray-500">
            Total: <span id="totalRegistrosTop">0</span>
        </div>
    </div>

    <form action="<?= BASE_URL ?>ordenDetalle" method="POST">
        <input type="hidden" name="accion" value="guardarCambios">
        <input type="hidden" name="fecha_origen" value="<?= $fecha ?>">

        <div class="overflow-x-auto shadow-inner border rounded mt-4" style="max-height: 70vh;">
            <table class="min-w-max text-xs bg-white border-collapse" id="tablaEdicion">
                <thead class="bg-gray-800 text-white uppercase tracking-wider sticky top-0 z-20">
                    <tr>
                        <th class="p-2 border bg-gray-900 w-24 text-gray-400">1. Cliente</th>
                        <th class="p-2 border bg-gray-900 w-24 text-gray-400">2. Punto</th>
                        <th class="p-2 border bg-gray-900 w-20 text-gray-400">3. Fecha</th>
                        <th class="p-2 border bg-indigo-900 w-32 text-indigo-100">4. Técnico</th>
                        <th class="p-2 border bg-blue-700 w-40 text-yellow-300 font-bold border-yellow-500 border-l-4">5. Servicio</th>
                        <th class="p-2 border bg-blue-700 w-20 text-yellow-300 font-bold">6. Zona</th>
                        <th class="p-2 border bg-blue-700 w-32 text-yellow-300 font-bold">7. Máquina</th>
                        <th class="p-2 border bg-blue-700 w-64 text-yellow-300 font-bold border-yellow-500 border-r-4">8. ¿Qué se hizo?</th>
                        <th class="p-2 border bg-red-900 text-white w-10 text-center" title="Marcar Novedad">⚠️</th>
                        <th class="p-2 border bg-green-800 w-24 text-white">9. Valor</th>
                        <th class="p-2 border bg-gray-700">10. Repuestos</th>
                        <th class="p-2 border bg-gray-700 text-gray-300">11. Rem</th>
                        <th class="p-2 border">12. Entra</th>
                        <th class="p-2 border">13. Sale</th>
                        <th class="p-2 border bg-orange-600 text-white w-20" title="Tiempo desde servicio anterior">🔁 Desplaz.</th>
                        <th class="p-2 border bg-gray-700">14. Est/Calif</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($servicios)): ?>
                        <tr>
                            <td colspan="16" class="p-4 text-center text-red-500 font-bold">No hay datos para esta fecha.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($servicios as $s): ?>
                            <?php $idFila = $s['id_ordenes_servicio']; ?>
                            <?php include __DIR__ . '/partials/detalleFila.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="fixed bottom-6 right-6 z-40">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-4 px-6 rounded-full shadow-2xl flex items-center gap-2 transform hover:scale-105 transition duration-300 border-4 border-white">
                <i class="fas fa-save text-xl"></i>
                <span class="text-lg">GUARDAR CAMBIOS</span>
            </button>
        </div>
    </form>

    <div class="flex justify-between items-center mt-4 bg-gray-50 p-3 rounded border border-gray-200">
        <div class="text-sm font-bold text-gray-700">
            Mostrando <span id="infoPagina"></span>
        </div>
        <div class="space-x-2">
            <button type="button" onclick="cambiarPagina(-1)" class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">Anterior</button>
            <span id="indicadorPagina" class="font-bold text-lg px-3">1</span>
            <button type="button" onclick="cambiarPagina(1)" class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">Siguiente</button>
        </div>
        <div class="text-sm text-gray-500">
            Total Servicios: <span id="totalRegistros">0</span>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/modalRepuestos.php'; ?>
<?php include __DIR__ . '/partials/modalNovedades.php'; ?>

<script>
    // Inyección de variables PHP a JS
    window.DetalleConfig = window.DetalleConfig || {};
    window.DetalleConfig.catalogoRepuestos = <?= json_encode($listaRepuestos ?? []) ?>;
    window.DetalleConfig.FESTIVOS_DB = <?= json_encode($listaFestivos ?? []) ?>;
    window.DetalleConfig.listaNovedades = <?= json_encode($listaNovedades ?? []) ?>;
</script>

<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleConfig.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleAjax.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleFechaUtils.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleExcel.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleRepuestos.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleNovedades.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleDesplazamientos.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detallePaginacion.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleNotificaciones.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleApp.js"></script>

<script>
    $(document).ready(function() {
        if (window.DetalleApp && window.DetalleApp.init) {
            window.DetalleApp.init();
        } else {
            console.error("⚠️ DetalleApp no se pudo inicializar.");
        }
    });
</script>