<style>
    /* Contenedor principal del Select2 */
    .select2-container .select2-selection--single {
        height: 52px !important;
        /* Alto para dedo gordo */
        border-radius: 0.75rem !important;
        /* rounded-xl */
        border: 1px solid #d1d5db !important;
        /* border-gray-300 */
        background-color: #f9fafb !important;
        /* bg-gray-50 */
        display: flex !important;
        align-items: center !important;
        padding-left: 10px;
    }

    /* Texto dentro del select */
    .select2-container .select2-selection--single .select2-selection__rendered {
        font-size: 1rem !important;
        /* text-base */
        font-weight: 500 !important;
        /* font-medium */
        color: #374151 !important;
        /* text-gray-700 */
        width: 100%;
    }

    /* La flechita del Select2 */
    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 50px !important;
        right: 10px !important;
    }

    /* Cuando está deshabilitado */
    .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: #f3f4f6 !important;
        /* bg-gray-100 */
        color: #9ca3af !important;
        cursor: not-allowed;
    }
</style>

<div class="w-full max-w-md mx-auto pb-24">

    <div class="bg-indigo-600 -mx-4 -mt-4 px-6 pt-8 pb-10 rounded-b-[2rem] shadow-lg mb-6 relative">
        <h2 class="text-white text-xl font-bold flex items-center">
            <i class="fas fa-mobile-alt mr-3 text-indigo-200"></i> Historial Técnico
        </h2>
        <p class="text-indigo-100 text-sm mt-1 ml-7 opacity-80">Consulta rápida de servicios anteriores</p>
    </div>

    <div class="px-2 space-y-5">

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                1. Selecciona el Cliente
            </label>
            <select id="movilCliente" class="w-full select2-movil">
                <option value="">-- Buscar Cliente... --</option>
                <?php foreach ($listaClientes as $c): ?>
                    <option value="<?= $c['id_cliente'] ?>"><?= $c['nombre_cliente'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                2. Selecciona el Punto
            </label>
            <select id="movilPunto" disabled class="w-full select2-movil">
                <option value="">Esperando cliente...</option>
            </select>
        </div>

        <button id="btnBuscarMovil" onclick="buscarServiciosMovil()" disabled
            class="w-full bg-indigo-300 text-white font-bold py-4 px-6 rounded-xl shadow transition-all transform flex justify-center items-center gap-2 cursor-not-allowed">
            <span>CONSULTAR</span> <i class="fas fa-arrow-right"></i>
        </button>

    </div>

    <div class="flex items-center gap-4 my-8 px-2">
        <div class="h-px bg-gray-300 flex-1"></div>
        <span class="text-gray-400 text-xs font-bold uppercase tracking-widest">Resultados</span>
        <div class="h-px bg-gray-300 flex-1"></div>
    </div>

    <div id="contenedorResultados" class="px-2 space-y-4 min-h-[200px]">
        <div class="flex flex-col items-center justify-center text-gray-400 py-8 opacity-60">
            <i class="fas fa-clipboard-list text-5xl mb-4 text-gray-300"></i>
            <p class="text-sm text-center">Selecciona un cliente y un punto<br>para ver el historial.</p>
        </div>
    </div>
</div>

<script>
    const URL_CONTROLADOR = '<?= BASE_URL ?>ordenMovil';

    $(document).ready(function() {

        // --- 1. INICIALIZAR SELECT2 ---
        $('.select2-movil').select2({
            width: '100%',
            language: "es", // Asegúrate de tener el JS de idioma, si no, quita esta línea
            placeholder: "Escribe para buscar..."
        });

        // --- 2. Lógica Cliente -> Cargar Puntos ---
        // Nota: Con Select2 usamos 'select2:select' o 'change'
        $('#movilCliente').on('change', function() {
            let idCliente = $(this).val();
            let $selectPunto = $('#movilPunto');
            let $btn = $('#btnBuscarMovil');

            // Resetear UI
            $selectPunto.empty().append('<option value="">Cargando puntos...</option>');
            $selectPunto.prop('disabled', true).trigger('change'); // trigger change actualiza visualmente el Select2

            $btn.prop('disabled', true).removeClass('bg-indigo-600 shadow-lg').addClass('bg-indigo-300 cursor-not-allowed');
            $('#contenedorResultados').empty();

            if (!idCliente) {
                $selectPunto.empty().append('<option value="">Esperando cliente...</option>').trigger('change');
                return;
            }

            // AJAX para pedir puntos
            $.post(URL_CONTROLADOR, {
                accion: 'cargarPuntos',
                id_cliente: idCliente
            }, function(respuesta) {

                // Validación segura del JSON
                let data = (typeof respuesta === 'object') ? respuesta : JSON.parse(respuesta);

                $selectPunto.empty();

                if (data.length > 0) {
                    $selectPunto.append('<option value="">-- Escribe para buscar punto --</option>');
                    data.forEach(p => {
                        // Creamos la opción
                        let option = new Option(p.nombre_punto, p.id_punto, false, false);
                        $selectPunto.append(option);
                    });

                    // Habilitar Select2
                    $selectPunto.prop('disabled', false);
                } else {
                    $selectPunto.append('<option value="">Sin puntos asignados</option>');
                }

                // 🔥 IMPORTANTE: Avisar a Select2 que los datos cambiaron
                $selectPunto.trigger('change');

            }).fail(function() {
                alert("Error de conexión al cargar puntos");
            });
        });

        // --- 3. Activar botón cuando se elija punto ---
        $('#movilPunto').on('change', function() {
            let val = $(this).val();
            let $btn = $('#btnBuscarMovil');

            if (val) {
                $btn.prop('disabled', false).removeClass('bg-indigo-300 cursor-not-allowed').addClass('bg-indigo-600 hover:bg-indigo-700 transform hover:scale-105 shadow-lg cursor-pointer');
            } else {
                $btn.prop('disabled', true).addClass('bg-indigo-300 cursor-not-allowed').removeClass('bg-indigo-600 hover:bg-indigo-700 transform hover:scale-105 shadow-lg');
            }
        });
    });

    // Función Buscar (Sin cambios)
    function buscarServiciosMovil() {
        let idCliente = $('#movilCliente').val();
        let idPunto = $('#movilPunto').val();

        if (!idCliente || !idPunto) return;

        $('#contenedorResultados').html(`
            <div class="flex flex-col items-center justify-center py-10">
                <i class="fas fa-circle-notch fa-spin text-4xl text-indigo-500 mb-3"></i>
                <p class="text-gray-500 font-medium">Buscando historial...</p>
            </div>
        `);

        $.post(URL_CONTROLADOR, {
            accion: 'buscarHistorial',
            id_cliente: idCliente,
            id_punto: idPunto
        }, function(htmlRespuesta) {
            $('#contenedorResultados').html(htmlRespuesta).hide().fadeIn();
        }).fail(function() {
            $('#contenedorResultados').html('<p class="text-red-500 text-center">Error al consultar datos.</p>');
        });
    }
</script>