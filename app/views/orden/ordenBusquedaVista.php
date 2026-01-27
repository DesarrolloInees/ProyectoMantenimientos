<style>
    /* Estilos locales necesarios */
    .select2-container--default .select2-selection--single {
        height: 32px !important;
        /* Un poco más alto para el buscador */
        padding: 2px !important;
        display: flex !important;
        align-items: center !important;
        border-color: #d1d5db !important;
    }

    /* Colores columnas */
    #resultadosBusqueda select[name*="[id_tecnico]"] {
        color: #4338ca;
        background-color: #eef2ff;
        font-weight: bold;
    }

    #resultadosBusqueda select[name*="[id_manto]"] {
        color: #1e40af;
        background-color: #eff6ff;
        font-weight: bold;
    }
</style>

<div class="bg-white p-6 rounded shadow-md w-full mb-10">

    <div class="border-b pb-4 mb-4">
        <h2 class="text-lg font-bold text-gray-700 flex items-center">
            <i class="fas fa-search mr-2 text-blue-500"></i> Búsqueda Avanzada de Órdenes
        </h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end bg-gray-50 p-4 rounded border border-gray-200">

        <div class="col-span-1">
            <label class="block text-xs font-bold text-gray-600 mb-1">Remisión:</label>
            <input type="text" id="busqRemision" class="w-full border border-gray-300 p-1.5 rounded text-sm focus:ring-2 focus:ring-blue-300 outline-none" placeholder="# Remisión">
        </div>

        <div class="col-span-1">
            <label class="block text-xs font-bold text-gray-600 mb-1">Cliente:</label>
            <select id="busqCliente" class="w-full border select2-basic text-sm">
                <option value="">Todos</option>
                <?php foreach ($listaClientes as $c): ?>
                    <option value="<?= $c['id_cliente'] ?>"><?= $c['nombre_cliente'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-span-1">
            <label class="block text-xs font-bold text-gray-600 mb-1">Delegación:</label>
            <select id="busqDelegacion" class="w-full border select2-basic text-sm">
                <option value="">Todas</option>
                <?php foreach ($listaDelegaciones as $d): ?>
                    <option value="<?= $d['id_delegacion'] ?>"><?= $d['nombre_delegacion'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-span-2 flex gap-2">
            <div class="w-1/2">
                <label class="block text-xs font-bold text-gray-600 mb-1">Desde:</label>
                <input type="date" id="busqFechaInicio" class="w-full border border-gray-300 p-1.5 rounded text-sm">
            </div>
            <div class="w-1/2">
                <label class="block text-xs font-bold text-gray-600 mb-1">Hasta:</label>
                <input type="date" id="busqFechaFin" class="w-full border border-gray-300 p-1.5 rounded text-sm">
            </div>
        </div>

        <div class="col-span-1 flex gap-2">
            <button type="button" onclick="realizarBusqueda()"
                class="flex-1 bg-indigo-600 text-white font-bold py-2 px-2 rounded hover:bg-indigo-700 shadow transition text-sm flex justify-center items-center gap-1">
                <i class="fas fa-search"></i> Buscar
            </button>

            <button type="button" onclick="exportarExcelLimpio()"
                class="bg-green-600 text-white font-bold py-2 px-3 rounded hover:bg-green-700 shadow transition text-sm"
                title="Descargar Reporte General">
                <i class="fas fa-file-excel"></i>
            </button>
        </div>
    </div>

    <div class="mt-3 w-full md:w-1/3 pl-4">
        <label class="block text-xs font-bold text-gray-600 mb-1">Punto (Filtrado por Cliente):</label>
        <select id="busqPunto" class="w-full border select2-basic text-sm">
            <option value="">Seleccione Cliente...</option>
        </select>
    </div>

    <div class="mt-6">
        <div class="flex justify-between items-center mb-2 bg-blue-50 p-2 rounded border border-blue-100">
            <div class="text-sm font-bold text-gray-700">
                Resultados: <span id="totalRegistros" class="text-blue-600">0</span>
            </div>
            <div class="text-xs text-gray-500">
                <span id="infoPagina">Esperando búsqueda...</span>
            </div>
        </div>

        <form action="<?= BASE_URL ?>ordenDetalle" method="POST" class="block w-full">
            <input type="hidden" name="accion" value="guardarCambios">
            <input type="hidden" name="es_busqueda" value="1">
            <input type="hidden" name="fecha_origen" value="<?= date('Y-m-d') ?>">

            <div class="w-full overflow-x-auto shadow-inner border rounded" style="max-height: 60vh;">
                <table class="min-w-max text-xs bg-white border-collapse" id="tablaEdicion">
                    <thead class="bg-gray-800 text-white uppercase tracking-wider sticky top-0 z-10">
                        <tr>
                            <th class="p-2 border bg-gray-900 w-24">1. Cliente</th>
                            <th class="p-2 border bg-gray-900 w-24">2. Punto</th>
                            <th class="p-2 border bg-gray-900 w-20">3. Fecha</th>
                            <th class="p-2 border bg-indigo-900 w-32">4. Técnico</th>
                            <th class="p-2 border bg-blue-700 w-40 text-yellow-300 font-bold">5. Servicio</th>
                            <th class="p-2 border bg-blue-700 w-20 text-yellow-300 font-bold">6. Zona</th>
                            <th class="p-2 border bg-blue-700 w-32 text-yellow-300 font-bold">7. Máquina</th>
                            <th class="p-2 border bg-blue-700 w-64 text-yellow-300 font-bold">8. ¿Qué se hizo?</th>
                            <th class="p-2 border bg-red-900 text-white w-10 text-center">⚠️</th>
                            <th class="p-2 border bg-green-800 w-24 text-white">9. Valor</th>
                            <th class="p-2 border bg-gray-700">10. Repuestos</th>
                            <th class="p-2 border bg-gray-700 text-gray-300">11. Rem</th>
                            <th class="p-2 border">12. Entra</th>
                            <th class="p-2 border">13. Sale</th>
                            <th class="p-2 border bg-orange-600 text-white w-20">🔁 Desplaz.</th>
                            <th class="p-2 border bg-gray-700 w-48">14. Est/Calif</th>
                        </tr>
                    </thead>
                    <tbody id="resultadosBusqueda" class="divide-y divide-gray-200">
                        <tr>
                            <td colspan="16" class="p-10 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
                                    <p>Usa los filtros superiores para encontrar órdenes.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="fixed bottom-6 right-6 z-40">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-6 rounded-full shadow-2xl flex items-center gap-2 transform hover:scale-105 transition duration-300 border-2 border-white">
                    <i class="fas fa-save"></i>
                    <span>GUARDAR CAMBIOS</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/partials/modalRepuestos.php'; ?>
<?php include __DIR__ . '/partials/modalNovedades.php'; ?>

<script>
    // Inicialización de Variables Globales
    window.DetalleConfig = window.DetalleConfig || {};
    window.DetalleConfig.catalogoRepuestos = <?= json_encode($listaRepuestos ?? []) ?>;
    window.DetalleConfig.FESTIVOS_DB = <?= json_encode($listaFestivos ?? []) ?>;
    window.DetalleConfig.listaNovedades = <?= json_encode($listaNovedades ?? []) ?>;
    window.DetalleConfig.filasPorPagina = 10;
    window.DetalleConfig.paginaActual = 1;

    $(document).ready(function() {
        // Inicializar Select2 en filtros
        $('.select2-basic').select2();

        // Inicializar App Lógica
        if (window.DetalleApp && window.DetalleApp.init) {
            window.DetalleApp.init();
        }

        // --- AJAX PUNTOS ---
        $('#busqCliente').on('change', function() {
            let idCliente = $(this).val();
            let $selectPunto = $('#busqPunto');

            $selectPunto.empty().append('<option value="">Cargando...</option>');

            if (!idCliente) {
                $selectPunto.empty().append('<option value="">Seleccione Cliente primero...</option>');
                return;
            }

            $.post('<?= BASE_URL ?>ordenDetalle', {
                accion: 'ajaxObtenerPuntos',
                id_cliente: idCliente
            }, function(data) {
                $selectPunto.empty().append('<option value="">Todos</option>');
                if (data && data.length > 0) {
                    data.forEach(p => {
                        $selectPunto.append(new Option(p.nombre_punto, p.id_punto));
                    });
                } else {
                    $selectPunto.append('<option value="">Sin puntos asignados</option>');
                }
                $selectPunto.trigger('change.select2');
            }, 'json').fail(function() {
                $selectPunto.empty().append('<option value="">Error al cargar</option>');
            });
        });
    });

    // Función de Búsqueda AJAX
    function realizarBusqueda() {
        let remision = $('#busqRemision').val();
        let cliente = $('#busqCliente').val();
        let punto = $('#busqPunto').val();
        let delegacion = $('#busqDelegacion').val();
        let fechaInicio = $('#busqFechaInicio').val();
        let fechaFin = $('#busqFechaFin').val();

        if (remision === '' && cliente === '' && punto === '' && delegacion === '' && fechaInicio === '' && fechaFin === '') {
            alert("⚠️ Por favor ingrese al menos un filtro.");
            return;
        }

        $('#resultadosBusqueda').html('<tr><td colspan="16" class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Buscando...</td></tr>');

        $.post('<?= BASE_URL ?>ordenDetalle', {
            accion: 'ajaxBuscarOrdenes',
            remision: remision,
            id_cliente: cliente,
            id_punto: punto,
            id_delegacion: delegacion,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin
        }, function(htmlRespuesta) {
            $('#resultadosBusqueda').html(htmlRespuesta);

            // Re-inicializar plugins visuales para los resultados nuevos
            $('#resultadosBusqueda select').select2();

            if (window.DetalleApp && window.DetalleApp.init) {
                window.DetalleApp.init();
            }
        }).fail(function() {
            $('#resultadosBusqueda').html('<tr><td colspan="16" class="text-center text-red-500 font-bold">Error de conexión.</td></tr>');
        });
    }
</script>

<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleConfig.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleAjax.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleFechaUtils.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleExcel.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleRepuestos.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleNovedades.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleDesplazamientos.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detallePaginacion.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleNotificaciones.js"></script>
<script src="<?= BASE_URL ?>js/orden/ordenDetalle/detalleApp.js"></script>