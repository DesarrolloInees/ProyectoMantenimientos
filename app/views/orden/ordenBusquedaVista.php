<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    body>.select2-container--open {
        z-index: 40 !important;
    }

    #modalRepuestos .select2-container--open {
        z-index: 9999 !important;
    }

    #modalRepuestos {
        z-index: 60 !important;
    }

    .select2-container--default .select2-selection--single {
        height: 28px !important;
        display: flex !important;
        align-items: center !important;
    }

    /* Colores de columnas */
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
</style>

<div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    
    <div class="col-span-1">
        <label class="block text-xs font-bold text-gray-700">Remisión:</label>
        <input type="text" id="busqRemision" class="w-full border p-1 rounded text-sm" placeholder="# Remisión">
    </div>

    <div class="col-span-1">
        <label class="block text-xs font-bold text-gray-700">Cliente:</label>
        <select id="busqCliente" class="w-full border p-1 rounded select2-basic text-sm">
            <option value="">Todos</option>
            <?php foreach ($listaClientes as $c): ?>
                <option value="<?= $c['id_cliente'] ?>"><?= $c['nombre_cliente'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-span-1">
        <label class="block text-xs font-bold text-gray-700">Delegación:</label>
        <select id="busqDelegacion" class="w-full border p-1 rounded select2-basic text-sm">
            <option value="">Todas</option>
            <?php foreach ($listaDelegaciones as $d): ?>
                <option value="<?= $d['id_delegacion'] ?>"><?= $d['nombre_delegacion'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-span-2 flex gap-2">
        <div class="w-1/2">
            <label class="block text-xs font-bold text-gray-700">Desde:</label>
            <input type="date" id="busqFechaInicio" class="w-full border p-1 rounded text-sm">
        </div>
        <div class="w-1/2">
            <label class="block text-xs font-bold text-gray-700">Hasta:</label>
            <input type="date" id="busqFechaFin" class="w-full border p-1 rounded text-sm">
        </div>
    </div>

    <div class="col-span-1 flex gap-2">
        <button type="button" onclick="realizarBusqueda()"
            class="flex-1 bg-indigo-600 text-white font-bold py-1.5 px-2 rounded hover:bg-indigo-700 shadow-lg text-sm flex justify-center items-center gap-1">
            <i class="fas fa-search"></i>
        </button>

        <button type="button" onclick="exportarExcelLimpio()"
            class="bg-green-600 text-white font-bold py-1.5 px-3 rounded hover:bg-green-700 shadow-lg text-sm"
            title="Descargar Reporte General">
            <i class="fas fa-file-excel"></i>
        </button>

        <button type="button" onclick="exportarExcelNovedades()"
            class="bg-orange-500 text-white font-bold py-1.5 px-3 rounded hover:bg-orange-600 shadow-lg text-sm"
            title="Descargar Solo Novedades">
            <i class="fas fa-exclamation-triangle"></i>
        </button>
    </div>
</div>

<div class="mt-2 w-1/3">
    <label class="block text-xs font-bold text-gray-700">Punto (Opcional):</label>
    <select id="busqPunto" class="w-full border p-1 rounded select2-basic text-sm">
        <option value="">Seleccione Cliente...</option>
    </select>
</div>

    <div class="flex justify-between items-center mb-4 bg-gray-100 p-3 rounded border">
        <div class="text-sm font-bold text-gray-700">
            Resultados: <span id="totalRegistros" class="text-blue-600">0</span>
        </div>

        <div class="space-x-2">
            <button type="button" onclick="cambiarPagina(-1)"
                class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-1 px-3 rounded shadow text-xs">
                <i class="fas fa-chevron-left"></i> Anterior
            </button>

            <span id="indicadorPagina" class="font-bold text-sm px-2 text-gray-700">1 / 1</span>

            <button type="button" onclick="cambiarPagina(1)"
                class="bg-white border hover:bg-gray-200 text-gray-800 font-bold py-1 px-3 rounded shadow text-xs">
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <div class="text-xs text-gray-500">
            <span id="infoPagina">Mostrando 0 - 0</span>
        </div>
    </div>

    <form action="<?= BASE_URL ?>ordenDetalle" method="POST" class="block w-full max-w-full">
        <input type="hidden" name="accion" value="guardarCambios">
        <input type="hidden" name="es_busqueda" value="1">
        <input type="hidden" name="fecha_origen" value="<?= date('Y-m-d') ?>">

        <div class="w-full overflow-x-auto shadow-inner border rounded" style="max-height: 70vh;">
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
                            <i class="fas fa-arrow-up mb-2 text-2xl"></i><br>
                            Usa los filtros superiores para encontrar órdenes.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="fixed bottom-6 right-6 z-40">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-4 px-6 rounded-full shadow-2xl flex items-center gap-2 transform hover:scale-105 transition duration-300 border-2 border-white">
                <i class="fas fa-save text-xl"></i>
                <span class="text-lg">GUARDAR CAMBIOS</span>
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/partials/modalRepuestos.php'; ?>
<?php include __DIR__ . '/partials/modalNovedades.php'; ?>

<script>
    window.DetalleConfig = window.DetalleConfig || {};
    window.DetalleConfig.catalogoRepuestos = <?= json_encode($listaRepuestos ?? []) ?>;
    window.DetalleConfig.FESTIVOS_DB = <?= json_encode($listaFestivos ?? []) ?>;
    window.DetalleConfig.listaNovedades = <?= json_encode($listaNovedades ?? []) ?>;

    // Configuración para la paginación (10 filas por página)
    window.DetalleConfig.filasPorPagina = 10;
    window.DetalleConfig.paginaActual = 1;
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

<script>
    $(document).ready(function() {
        // 1. Inicializar Select2 de filtros superiores
        $('.select2-basic').select2();

        // 2. Inicializar la App General
        if (window.DetalleApp && window.DetalleApp.init) {
            window.DetalleApp.init();
        }

        // ============================================================
        // LOGICA DE CARGA DE PUNTOS (Correcta y Limpia)
        // ============================================================
        $('#busqCliente').on('change', function() {
            let idCliente = $(this).val();
            let $selectPunto = $('#busqPunto');

            // Limpiamos el select y ponemos mensaje de carga
            $selectPunto.empty().append('<option value="">Cargando...</option>');

            if (!idCliente) {
                $selectPunto.empty().append('<option value="">Seleccione Cliente primero...</option>');
                return;
            }

            // Pedimos los datos al servidor
            $.post('<?= BASE_URL ?>ordenDetalle', {
                accion: 'ajaxObtenerPuntos',
                id_cliente: idCliente
            }, function(data) {
                // Limpiamos de nuevo
                $selectPunto.empty().append('<option value="">Todos</option>');

                // Llenamos con los datos recibidos
                if (data && data.length > 0) {
                    data.forEach(p => {
                        $selectPunto.append(new Option(p.nombre_punto, p.id_punto));
                    });
                } else {
                    $selectPunto.append('<option value="">Sin puntos asignados</option>');
                }

                // Forzamos a Select2 a refrescarse sin destruir el estilo
                $selectPunto.trigger('change.select2');

            }, 'json').fail(function() {
                $selectPunto.empty().append('<option value="">Error al cargar</option>');
            });
        });
    });

    function realizarBusqueda() {
        // Capturar valores
        let remision = $('#busqRemision').val();
        let cliente = $('#busqCliente').val();
        let punto = $('#busqPunto').val();
        
        // --- NUEVOS VALORES ---
        let delegacion = $('#busqDelegacion').val();
        let fechaInicio = $('#busqFechaInicio').val();
        let fechaFin = $('#busqFechaFin').val();

        // Validación simple: Que al menos haya un filtro puesto
        if (remision === '' && cliente === '' && punto === '' && delegacion === '' && fechaInicio === '' && fechaFin === '') {
            alert("⚠️ Por favor ingrese al menos un filtro (Fecha, Cliente, Delegación, etc).");
            return;
        }

        // Validar lógica de fechas (opcional pero recomendada)
        if(fechaInicio !== '' && fechaFin !== '' && fechaInicio > fechaFin) {
            alert("⚠️ La fecha de inicio no puede ser mayor a la fecha fin.");
            return;
        }

        $('#resultadosBusqueda').html('<tr><td colspan="16" class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Buscando...</td></tr>');

        // Enviar por AJAX
        $.post('<?= BASE_URL ?>ordenDetalle', {
            accion: 'ajaxBuscarOrdenes',
            remision: remision,
            id_cliente: cliente,
            id_punto: punto,
            // Enviamos los nuevos
            id_delegacion: delegacion,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin
        }, function(htmlRespuesta) {

            $('#resultadosBusqueda').html(htmlRespuesta);

            // Reinicializar componentes
            $('#resultadosBusqueda .select2-container').remove();
            $('#resultadosBusqueda select').select2();

            if (window.DetalleApp && window.DetalleApp.init) {
                window.DetalleApp.init();
            }

            window.DetalleConfig.paginaActual = 1;
            if (typeof iniciarPaginacion === 'function') iniciarPaginacion();

        }).fail(function() {
            $('#resultadosBusqueda').html('<tr><td colspan="16" class="text-center text-red-500 font-bold">Error de conexión con el servidor.</td></tr>');
        });
    }
</script>