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

    /* Esto fuerza a que el texto largo termine en "..." */
    .select2-container .select2-selection--single .select2-selection__rendered {
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        padding-right: 20px !important;
        /* Espacio para que no choque con la X */
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
                        <th class="px-2 w-48">Información Servicio</th>
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

        <div class="flex gap-2 items-center">

            <div class="flex-1 min-w-0">
                <select id="select_repuesto_modal" class="w-full border rounded p-2 text-sm">
                    <option value="">- Buscar Repuesto -</option>
                </select>
            </div>

            <div class="w-20 flex-shrink-0">
                <input type="number" id="cantidad_repuesto_modal" value="1" min="1"
                    class="w-full border rounded p-2 text-sm text-center font-bold bg-gray-50 h-[38px]"
                    placeholder="Cant.">
            </div>

            <div class="w-1/3 flex-shrink-0" style="max-width: 130px;">
                <select id="select_origen_modal"
                    class="w-full border rounded p-2 text-xs bg-gray-100 font-bold text-gray-700 h-[38px]">
                    <option value="INEES">INEES</option>
                    <option value="PROSEGUR">PROSEGUR</option>
                </select>
            </div>

            <button type="button" onclick="agregarRepuestoALista()"
                class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 shadow transition h-[38px] flex-shrink-0">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        <ul id="lista_repuestos_visual" class="border rounded p-2 h-48 overflow-y-auto bg-gray-50 text-sm">
            <li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>
        </ul>


        <div class="mt-6 text-right border-t pt-4">
            <button type="button" onclick="guardarCambiosModal()"
                class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 shadow-lg transform hover:scale-105 transition">
                <i class="fas fa-check mr-2"></i> Confirmar Cambios
            </button>
        </div>
    </div>
</div>

<div id="pantallaCargaGuardando" class="fixed inset-0 z-[999999] hidden bg-gray-900 bg-opacity-90 flex flex-col items-center justify-center">
    <div class="animate-spin rounded-full h-32 w-32 border-t-4 border-b-4 border-blue-500 mb-6"></div>
    <h2 class="text-3xl font-bold text-white text-center">Guardando Servicios...</h2>
    <p class="text-gray-300 mt-4 text-lg text-center max-w-md">
        Por favor, no cierres ni recargues esta ventana hasta que el proceso haya finalizado. Esto puede tomar unos segundos.
    </p>
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
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/config.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/ajaxUtils.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/uiUtils.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/timeManager.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/filaManager.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/repuestosManager.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/storageManager.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/app.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/crearNotificaciones.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/orden/ordenCrear/validadorRemisiones.js?v=<?php echo time(); ?>"></script>

<script>
    $(document).ready(function() {

        // Detectar cambio en la fecha
        $('input[name="fecha_reporte"]').on('change', function() {
            var fecha = $(this).val();
            cargarProgramacion(fecha);
        });

        // Cargar al inicio si ya hay fecha (por si recargan la página)
        var fechaInicial = $('input[name="fecha_reporte"]').val();
        if (fechaInicial) {
            cargarProgramacion(fechaInicial);
        }
    });

    function cargarProgramacion(fecha) {
        // Feedback visual (Opcional)
        console.log("Buscando programación para: " + fecha);

        $.ajax({
            url: 'index.php?pagina=ordenCrear&accion=ajaxProgramacion',
            type: 'POST',
            dataType: 'json',
            data: {
                fecha: fecha
            },
            success: function(response) {

                // Limpiar tabla actual (Opcional: Si quieres que reemplace todo)
                // $('#contenedorFilas').empty(); 
                // filas = []; // Si usas un array global en filaManager.js para rastrear índices

                if (response.status && response.data.length > 0) {

                    // Limpiamos la tabla para no acumular basura si cambia la fecha varias veces
                    $('#contenedorFilas').empty();

                    // Alerta suave (Toast)
                    // alert('Se encontraron ' + response.data.length + ' servicios programados.');

                    // 🔥 Le agregamos 'async' a la función del forEach
                    response.data.forEach(async function(servicio, index) {

                        // 1. Agregamos una fila vacía (Llamamos a tu función existente)
                        agregarFila();

                        // 2. Esperamos un micro-momento para que el DOM exista, 
                        // o buscamos la última fila agregada.
                        var $fila = $('#contenedorFilas tr:last');
                        // OBTENEMOS EL ÍNDICE REAL QUE GENERÓ agregarFila()
                        // Si tu filaManager usa un contador global, búscalo en el atributo name
                        // Ejemplo: name="filas[0][id_tecnico]" -> El índice es 0

                        var nameAttribute = $fila.find('select').first().attr('name');
                        var match = nameAttribute.match(/filas\[(\d+)\]/);
                        var indiceReal = match ? match[1] : index;

                        // Input hidden de orden previa
                        if ($fila.find('input.orden-previa').length === 0) {
                            var inputHidden = `<input type="hidden" class="orden-previa" name="filas[${indiceReal}][id_orden_previa]" value="${servicio.id_ordenes_servicio}">`;
                            $fila.find('td:first').append(inputHidden);
                        }

                        // --- AQUÍ VIENE LA MAGIA ---

                        // Técnico
                        $fila.find('select[name*="[id_tecnico]"]').val(servicio.id_tecnico).trigger('change');

                        // Cliente (Lo asignamos SIN trigger, para nosotros controlar el AJAX a mano)
                        $fila.find('select[name*="[id_cliente]"]').val(servicio.id_cliente);

                        // Esperamos pacientemente a que lleguen los puntos (así se demore 5 segundos)
                        await window.AjaxUtils.cargarPuntos(indiceReal, servicio.id_cliente);

                        // Ahora SÍ, con los puntos ya cargados, seleccionamos el punto
                        $fila.find('select[name*="[id_punto]"]').val(servicio.id_punto);

                        // Esperamos pacientemente a que lleguen las máquinas
                        await window.AjaxUtils.cargarMaquinas(indiceReal, servicio.id_punto);

                        // Seleccionamos la máquina y rellenamos el Device ID
                        $fila.find('select[name*="[id_maquina]"]').val(servicio.id_maquina);
                        window.FilaManager.rellenarDeviceId(indiceReal, servicio.id_maquina);

                        // Tipo Mantenimiento
                        $fila.find('select[name*="[tipo_servicio]"]').val(servicio.id_tipo_mantenimiento);

                        // Modalidad (Si tienes el campo visible)
                        // $fila.find('select[name*="[id_modalidad]"]').val(servicio.id_modalidad);

                        // Iluminar la fila para indicar que vino de programación
                        $fila.addClass('bg-blue-50');
                    });
                }
            },
            error: function(err) {
                console.error("Error cargando programación", err);
            }
        });
    }


    // ==========================================
    // MOTOR DE GUARDADO MASIVO (JSON) PARA CREAR
    // ==========================================
    

    async function ejecutarGuardadoJSONCrear() {
        const filas = document.querySelectorAll('#contenedorFilas tr');
        const fechaReporte = document.querySelector('input[name="fecha_reporte"]').value;

        // Mostrar tu bonita pantalla de carga oscura que ya tenías diseñada
        const pantallaCarga = document.getElementById('pantallaCargaGuardando');
        if(pantallaCarga) pantallaCarga.classList.remove('hidden');

        const btnSave = document.getElementById('btnGuardarFijo');
        btnSave.disabled = true;

        let filasData = [];

        // Extraemos los datos de la misma forma inteligente que en edición
        filas.forEach(fila => {
            let filaDatos = {};
            
            let elementos = fila.querySelectorAll('input, select, textarea');
            elementos.forEach(el => {
                if (el.name) {
                    // Extrae el nombre final, ej: filas[0][id_maquina] -> id_maquina
                    let match = el.name.match(/\[([a-zA-Z0-9_]+)\]$/);
                    if (match && match[1]) {
                        filaDatos[match[1]] = el.value;
                    }
                }
            });
            
            // Si la fila tiene el input oculto de orden programada, lo capturamos
            let inputPrevia = fila.querySelector('.orden-previa');
            if (inputPrevia) {
                filaDatos['id_orden_previa'] = inputPrevia.value;
            }

            filasData.push(filaDatos);
        });

        // Preparamos el paquete engañando a PHP
        const formData = new FormData();
        formData.append('fecha_reporte', fechaReporte);
        formData.append('json_data', JSON.stringify(filasData));

        try {
            // Fíjate que ahora apuntamos a la nueva acción "ajaxGuardarJSON"
            const response = await fetch('index.php?pagina=ordenCrear&accion=ajaxGuardarJSON', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'ok') {
                alert("✅ " + data.msg);
                window.location.reload(); // Recarga limpia tras el éxito
            } else if (data.status === 'warning') {
                alert("⚠️ " + data.msg);
                window.location.reload(); 
            } else {
                alert("❌ Error: " + data.msg);
            }
        } catch (error) {
            console.error("Error guardando:", error);
            alert("❌ Ocurrió un error de conexión con el servidor.");
        } finally {
            if(pantallaCarga) pantallaCarga.classList.add('hidden');
            btnSave.disabled = false;
        }
    }
</script>