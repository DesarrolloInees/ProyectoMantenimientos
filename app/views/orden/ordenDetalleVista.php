<!-- ============================================== -->
<!-- DETALLE DE SERVICIOS - VISTA OPTIMIZADA -->
<!-- ============================================== -->

<!-- LIBRERÍAS EXTERNAS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<!-- ESTILOS PERSONALIZADOS -->
<style>
    /* Z-INDEX HIERARCHY */
    body>.select2-container--open {
        z-index: 40 !important;
    }

    #modalRepuestos .select2-container--open {
        z-index: 9999 !important;
    }

    #modalRepuestos {
        z-index: 60 !important;
    }

    .select2-container {
        z-index: auto;
    }

    /* SELECT2 STYLING */
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
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 26px !important;
        top: 1px !important;
    }

    /* COLUMN COLORING */
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

    .hidden {
        display: none !important;
    }

    .flex {
        display: flex !important;
    }

    /* Esto hace que Select2 corte el texto con "..." en lugar de ensancharse */
.select2-container .select2-selection--single .select2-selection__rendered {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    padding-right: 10px !important;
    max-width: 100% !important;
}
</style>

<!-- CONTENIDO PRINCIPAL -->
<div class="bg-white p-4 rounded shadow-lg w-full">

    <!-- HEADER -->
    <div class="flex flex-wrap justify-between items-center mb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">🛠️ Edición Maestra de Servicios</h2>
            <p class="text-sm text-blue-600 font-bold">Fecha Lote: <?= $fecha ?></p>
        </div>

        <div class="space-x-2">
            <button type="button" onclick="exportarExcelLimpio()"
                class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow">
                <i class="fas fa-file-excel mr-2"></i> Excel Limpio
            </button>

            <a href="<?= BASE_URL ?>inicio"
                class="bg-gray-500 text-white px-4 py-2 rounded font-bold hover:bg-gray-600">
                <i class="fas fa-arrow-left"></i> Volver
            </a>

            <button type="button" onclick="exportarExcelNovedades()"
                class="bg-red-600 text-white px-4 py-2 rounded font-bold hover:bg-red-700 shadow ml-2">
                <i class="fas fa-file-contract mr-2"></i> Reporte Novedades
            </button>
        </div>
    </div>

    <!-- PAGINACIÓN SUPERIOR -->
    <div class="flex justify-between items-center mt-4 bg-gray-100 p-3 rounded border">
        <div class="text-sm font-bold text-gray-700">
            Mostrando <span id="infoPagina"></span>
        </div>
        <div class="space-x-2">
            <button type="button" onclick="cambiarPagina(-1)"
                class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>
            <span id="indicadorPagina" class="font-bold text-lg px-3">1</span>
            <button type="button" onclick="cambiarPagina(1)"
                class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="text-sm text-gray-500">
            Total Servicios: <span id="totalRegistros">0</span>
        </div>
    </div>

    <!-- FORMULARIO PRINCIPAL -->
    <form action="<?= BASE_URL ?>ordenDetalle" method="POST">
        <input type="hidden" name="accion" value="guardarCambios">
        <input type="hidden" name="fecha_origen" value="<?= $fecha ?>">

        <!-- TABLA DE SERVICIOS -->
        <div class="overflow-x-auto shadow-inner border rounded" style="max-height: 80vh;">
            <table class="min-w-max text-xs bg-white border-collapse" id="tablaEdicion">
                <thead class="bg-gray-800 text-white uppercase tracking-wider sticky top-0 z-10">
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
                            <td colspan="16" class="p-4 text-center text-red-500">No hay datos.</td>
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

        <!-- BOTÓN GUARDAR FLOTANTE -->
        <div class="fixed bottom-6 right-6 z-40">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-4 px-6 rounded-full shadow-2xl flex items-center gap-2 transform hover:scale-105 transition duration-300 border-2 border-white">
                <i class="fas fa-save text-xl"></i>
                <span class="text-lg">GUARDAR CAMBIOS</span>
            </button>
        </div>
    </form>

    <!-- PAGINACIÓN INFERIOR -->
    <div class="flex justify-between items-center mt-4 bg-gray-100 p-3 rounded border">
        <div class="text-sm font-bold text-gray-700">
            Mostrando <span id="infoPagina"></span>
        </div>
        <div class="space-x-2">
            <button type="button" onclick="cambiarPagina(-1)"
                class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>
            <span id="indicadorPagina" class="font-bold text-lg px-3">1</span>
            <button type="button" onclick="cambiarPagina(1)"
                class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow">
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="text-sm text-gray-500">
            Total Servicios: <span id="totalRegistros">0</span>
        </div>
    </div>
</div>

<!-- MODAL DE REPUESTOS -->
<?php include __DIR__ . '/partials/modalRepuestos.php'; ?>
<?php include __DIR__ . '/partials/modalNovedades.php'; ?>

<!-- ============================================== -->
<!-- INYECCIÓN DE DATOS PHP A JAVASCRIPT -->
<!-- ============================================== -->
<script>
    // 1. Inicializamos el objeto global ANTES de cargar nada más
    window.DetalleConfig = window.DetalleConfig || {};

    // 2. Inyectamos los datos de PHP al objeto global DIRECTAMENTE
    // Usamos 'window.' en lugar de 'const' para evitar errores de "Identifier already declared"
    window.DetalleConfig.catalogoRepuestos = <?= json_encode($listaRepuestos ?? []) ?>;
    window.DetalleConfig.FESTIVOS_DB = <?= json_encode($listaFestivos ?? []) ?>;
    window.DetalleConfig.listaNovedades    = <?= json_encode($listaNovedades ?? []) ?>;

    // Debug para verificar carga
    console.log("✅ Datos PHP inyectados:", window.DetalleConfig);
</script>

<script src="<?php echo BASE_URL; ?>js/orden/ordenDetalle/detalleConfig.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenDetalle/detalleAjax.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenDetalle/detalleFechaUtils.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenDetalle/detalleExcel.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenDetalle/detalleRepuestos.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleNovedades.js?v=<?= time() ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenDetalle/detalleDesplazamientos.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenDetalle/detallePaginacion.js"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenDetalle/detalleNotificaciones.js"></script>

<script src="<?php echo BASE_URL; ?>js/orden/ordenDetalle/detalleApp.js"></script>

<script>
    $(document).ready(function() {
        // Ahora sí funcionará porque todos los scripts anteriores ya cargaron
        if (window.DetalleApp && window.DetalleApp.init) {
            window.DetalleApp.init();
        } else {
            console.error("⚠️ DetalleApp sigue fallando.");
        }
    });
</script>