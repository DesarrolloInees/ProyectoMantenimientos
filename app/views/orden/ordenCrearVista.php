<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<style>
    .select2-container .select2-selection--single {
        height: 100% !important;
        /* Ajustar altura */
        padding: 0.25rem !important;
        border-color: #d1d5db !important;
        /* Gris de Tailwind */
        border-radius: 0.25rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 0 !important;
        bottom: 0 !important;
        height: 100% !important;
    }

    /* Para que el buscador tenga foco correcto */
    .select2-search__field {
        outline: none !important;
    }

    /* CORRECCIÓN VITAL PARA SELECT2 EN MODALES */
    .select2-container--open {
        z-index: 99999999 !important;
        /* Por encima de todo */
    }

    /* Ajuste para que el input del buscador sea visible y accesible */
    .select2-search__field {
        z-index: 99999999 !important;
    }
</style>

<div class="w-full bg-white shadow-xl rounded-lg p-2 md:p-6">

    <form action="index.php?pagina=ordenCrear&accion=guardar" method="POST" id="formServicios">

        <div
            class="bg-gradient-to-r from-gray-800 to-gray-700 p-4 rounded-lg mb-6 flex flex-wrap gap-4 items-center text-white shadow-md">
            <div>
                <label class="block text-xs font-bold text-gray-300 uppercase mb-1">Fecha del Reporte</label>
                <input type="date" name="fecha_reporte" value="<?= date('Y-m-d') ?>"
                    class="text-gray-900 border-none p-2 rounded w-40 font-bold focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-2">
                <!-- Badge con contador -->
                <div class="bg-blue-600 text-white text-xs font-bold py-1 px-3 rounded-full shadow-lg">
                    <span id="contadorFilasDisplay">0</span> Servicios agregados
                </div>

                <button type="button" onclick="agregarFila()"
                    class="bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-6 rounded-full shadow-2xl transition transform hover:scale-110 flex items-center gap-2">
                    <i class="fas fa-plus text-xl"></i>
                    <span class="font-bold">Agregar Servicio</span>
                </button>

            </div>
        </div>


        <div class="overflow-x-auto shadow-inner rounded-lg border border-gray-200" style="max-height: 60vh; overflow-y: auto;">
            <table class="min-w-max text-xs w-full">
                <thead class="sticky top-0 z-20 bg-gray-100">
                    <tr class="text-gray-600 uppercase tracking-wider border-b-2 border-gray-300 h-10">
                        <!-- ... tus columnas ... -->
                        <th class="px-2 sticky left-0 bg-gray-100 z-10 w-8">#</th>
                        <th class="px-2 w-32">Remisión</th>
                        <th class="px-2 w-48">Ubicación (Cliente/Punto)</th>
                        <th class="px-2 w-40">Máquina / Device ID</th>
                        <th class="px-2 w-40">Modalidad Operativa</th>
                        <th class="px-2 w-40">Técnico que Realizo el Servicio</th>
                        <th class="px-2 w-40">Resultado Mantenimiento</th>
                        <th class="px-2 w-32">Tiempos Servicio</th>
                        <th class="px-2 w-24 text-center">Duración Servicio</th>
                        <th class="px-2 w-32"> Valor Servicio</th>
                        <th class="px-2 w-32">Repuestos</th>
                        <th class="px-2 w-32">Estado Final</th>
                        <th class="px-2 w-32">Calificación Servicio</th>
                        <th class="px-2 w-48">¿Qué se Realizo?</th>


                        <th class="px-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="contenedorFilas" class="divide-y divide-gray-100 bg-white">
                    <!-- Las filas se generarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>

        <div class="mt-8 text-center pb-8">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-4 px-12 rounded-xl shadow-xl transform hover:scale-105 transition text-lg">
                <i class="fas fa-save mr-2"></i> GUARDAR REPORTE COMPLETO
            </button>
        </div>

    </form>
</div>

<div id="modalRepuestos" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg p-6 transform scale-100 transition-transform">

        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex justify-between">
            <span>🛠️ Gestión de Repuestos</span>
            <button type="button" onclick="cerrarModal()" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </h3>

        <input type="hidden" id="modal_fila_actual">

        <div class="space-y-4">
            <div class="flex gap-2 items-center">

                <div class="flex-grow w-2/3">
                    <select id="select_repuesto_modal" class="w-full border rounded p-2 text-sm">
                        <option value="">- Buscar Repuesto -</option>
                    </select>
                </div>

                <div class="w-1/3">
                    <select id="select_origen_modal" class="w-full border rounded p-2 text-xs bg-gray-100 font-bold text-gray-700 h-[38px]">
                        <option value="INEES">INEES</option>
                        <option value="PROSEGUR">PROSEGUR</option>
                    </select>
                </div>

                <button type="button" onclick="agregarRepuestoALista()" class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 shadow transition h-[38px]">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <ul id="lista_repuestos_visual" class="border rounded p-2 h-48 overflow-y-auto bg-gray-50 text-sm">
                <li class="text-gray-400 text-center italic mt-10">No hay repuestos seleccionados.</li>
            </ul>
        </div>

        <div class="mt-6 text-right border-t pt-4">
            <button type="button" onclick="guardarCambiosModal()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 shadow-lg transform hover:scale-105 transition">
                <i class="fas fa-check mr-2"></i> Confirmar Cambios
            </button>
        </div>
    </div>
</div>



<script>
    // DATOS MAESTROS desde PHP
    const listaClientes = <?= json_encode($clientes ?? []) ?>;
    const listaMantos = <?= json_encode($tiposManto ?? []) ?>;
    const listaTecnicos = <?= json_encode($tecnicos ?? []) ?>;
    const listaPuntos = <?= json_encode($puntos ?? []) ?>;
    // --- NUEVO: LISTAS DINÁMICAS ---
    const listaEstados = <?= json_encode($estados ?? []) ?>;
    const listaCalif = <?= json_encode($califs ?? []) ?>;
    const listaRepuestosBD = <?= json_encode($listaRepuestos ?? []) ?>;

    console.log('=== VERIFICACIÓN DE DATOS ===');
    console.log('Clientes cargados:', listaClientes);
    console.log('Mantenimientos cargados:', listaMantos);
    console.log('Técnicos cargados:', listaTecnicos);

    let contadorFilas = 0;
    let almacenRepuestos = {};
</script>

<script>
    // --- FUNCIÓN PRINCIPAL PARA AGREGAR FILAS ---
    function agregarFila() {
        contadorFilas++;
        const id = contadorFilas;

        console.log('Agregando fila:', id);

        // Generar options
        let optCli = '<option value="">- Cliente -</option>';
        listaClientes.forEach(c => {
            optCli += `<option value="${c.id_cliente}">${c.nombre_cliente}</option>`;
        });

        let optTec = '<option value="">- Técnico -</option>';
        listaTecnicos.forEach(c => {
            optTec += `<option value="${c.id_tecnico}">${c.nombre_tecnico}</option>`;
        });

        let optMan = '<option value="">- Resultado Mantenimiento -</option>';
        listaMantos.forEach(m => {
            optMan += `<option value="${m.id_tipo_mantenimiento}">${m.nombre_completo}</option>`;
        });

        // Generar Options de Estados (Dinámico)
        let optEst = '';
        listaEstados.forEach(e => {
            optEst += `<option value="${e.id_estado}">${e.nombre_estado}</option>`;
        });

        // Generar Options de Calificación (Dinámico)
        let optCal = '';
        listaCalif.forEach(c => {
            optCal += `<option value="${c.id_calificacion}">${c.nombre_calificacion}</option>`;
        });

        const html = `
    <tr id="fila_${id}" class="hover:bg-blue-50 transition">
        <td class="px-2 py-3 text-center font-bold text-gray-400 sticky left-0 bg-white">${id}</td>
        
        <td class="px-2">
            <input type="text" name="filas[${id}][remision]" placeholder="Num Remisión" class="w-full border border-gray-300 rounded p-1 text-center font-bold focus:border-blue-500">
        </td>

        <!-- UBICACIÓN: CLIENTE -> PUNTO -->
        <td class="px-2 space-y-1">
            <select name="filas[${id}][id_cliente]" 
                    id="select_cliente_${id}"  
                    onchange="cargarPuntos(${id}, this.value)" 
                    class="mi-select2 w-full border ..."> ${optCli}
            </select>

            <select name="filas[${id}][id_punto]" 
                    id="select_punto_${id}" 
                    onchange="cargarMaquinas(${id}, this.value)" 
                    class="mi-select2 w-full border ..." disabled> <option value="">- Primero seleccione cliente -</option>
            </select>
        </td>

        <!-- MÁQUINA: MÁQUINA -> DEVICE ID -->
        <td class="px-2 space-y-1">
            <select name="filas[${id}][id_maquina]" id="select_maquina_${id}" onchange="rellenarDeviceId(${id}, this.value)" class="w-full border rounded p-1 text-xs bg-yellow-50" disabled>
                <option value="">- Primero seleccione punto -</option>
            </select>
            <input type="text" name="filas[${id}][device_id]" id="device_id_${id}" readonly class="w-full border p-1 text-xs text-center bg-gray-100 font-mono" placeholder="Device ID se llenará automáticamente">
        </td>

        
        <td class="p-1">
            <select name="filas[${id}][id_modalidad]"  id="select_modalidad_${id}" 
                class="w-full border rounded p-1 text-xs bg-gray-50 font-bold text-gray-700"
                onchange="calcularPrecio(${id})"   disabled>
                    <option value="1">URBANO</option>
                    <option value="2">INTERURBANO</option>
            </select>
        </td>

        <td class="px-2">
            <select name="filas[${id}][id_tecnico]" class="w-full border rounded p-1 font-semibold">                
                ${optTec}
            </select>
        </td>

        <td class="px-2">
            <select name="filas[${id}][tipo_servicio]" onchange="calcularPrecio(${id})" class="w-full border rounded p-1 font-semibold">                
                ${optMan}
            </select>
        </td>

        <td class="px-2 space-y-1">
    <div class="flex items-center justify-between">
        <span class="text-gray-400 text-[10px]">Entrada:</span> 
        <input type="text" 
                name="filas[${id}][hora_in]" 
                id="in_${id}" 
                placeholder="HH:MM" 
                class="border border-gray-300 rounded p-0.5 w-20 text-center font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
    </div>
    <div class="flex items-center justify-between">
        <span class="text-gray-400 text-[10px]">Salida:</span> 
        <input type="text" 
                name="filas[${id}][hora_out]" 
                id="out_${id}" 
                placeholder="HH:MM" 
                class="border border-gray-300 rounded p-0.5 w-20 text-center font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
    </div>
</td>

        <td class="px-2 text-center">
            <input type="text" id="duracion_${id}" readonly class="w-full text-center bg-transparent font-mono text-xs font-bold text-gray-600" placeholder="00:00">
        </td>

        <td class="px-2">
            <div class="relative">
                <span class="absolute left-2 top-1 text-gray-400">$</span>
                <input type="text" name="filas[${id}][valor]" class="w-full border rounded pl-4 pr-1 py-1 text-right font-mono text-green-700 font-bold bg-green-50" placeholder="0" value="0">
            </div>
        </td>

        <td class="px-2 text-center">
            <button type="button" onclick="abrirModalRepuestos(${id})" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs px-2 py-1 rounded border border-gray-300 w-full">
                <i class="fas fa-tools"></i> <span id="count_rep_${id}">0 Items</span>
            </button>
            
            <input type="hidden" name="filas[${id}][json_repuestos]" id="json_rep_${id}" value="[]">
        </td>

        <td class="px-2">
            <select name="filas[${id}][estado]" class="w-full border rounded p-1 text-[10px]">
                ${optEst}
            </select>
        </td>

        <td class="px-2">
            <select name="filas[${id}][calif]" class="w-full border rounded p-1 text-[10px]">
                ${optCal}
            </select>
        </td>

        <td class="px-2">
            <textarea name="filas[${id}][obs]" rows="2" class="w-full border rounded p-1 text-xs resize-none" placeholder="Describa las actividades realizadas (Limpieza, ajustes, cambios...)"></textarea>
        </td>

        <td class="px-2 text-center">
            <button type="button" onclick="eliminarFila(${id})" class="text-red-400 hover:text-red-600">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>`;

        // NUEVO: Actualizar display del contador
        document.getElementById('contadorFilasDisplay').innerText = contadorFilas;

        // NUEVO: Auto-scroll a la nueva fila
        setTimeout(() => {
            document.getElementById(`fila_${id}`).scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }, 100);

        const contenedor = document.getElementById('contenedorFilas');
        if (contenedor) {
            contenedor.insertAdjacentHTML('beforeend', html);
            activarSelect2(`#select_cliente_${id}`);
            activarSelect2(`#select_punto_${id}`);
            activarSelect2(`select_maquina_${id}`);
            activarInputHora(`#in_${id}`, id);
            activarInputHora(`#out_${id}`, id);

            console.log('Fila agregada correctamente');
        } else {
            console.error('No se encontró el contenedor de filas');
        }
    }

    // También actualizar al eliminar
    function eliminarFila(id) {
        const fila = document.getElementById(`fila_${id}`);
        if (fila) {
            fila.remove();
            delete almacenRepuestos[id];
            console.log('Fila eliminada:', id);

            // NUEVO: Actualizar contador
            const filasActuales = document.querySelectorAll('#contenedorFilas tr').length;
            document.getElementById('contadorFilasDisplay').innerText = filasActuales;
        }
    }

    // --- FUNCIONES AJAX PARA CARGAR DATOS ---
    async function enviarAjax(accion, datos) {
        try {
            const formData = new FormData();
            formData.append('accion', accion);
            for (const key in datos) {
                formData.append(key, datos[key]);
            }

            const respuesta = await fetch('index.php?pagina=ordenCrear', {
                method: 'POST',
                body: formData
            });

            return await respuesta.json();
        } catch (error) {
            console.error('Error en AJAX:', error);
            return [];
        }
    }

    // 1. CARGAR PUNTOS (Con Zona Oculta)
    async function cargarPuntos(id, idCliente) {
        limpiarFilaDesde(id, 'punto');
        if (!idCliente) return;

        const selectPunto = document.getElementById(`select_punto_${id}`);
        selectPunto.innerHTML = '<option value="">Cargando...</option>';
        selectPunto.disabled = true;

        const puntos = await enviarAjax('ajaxPuntos', {
            id_cliente: idCliente
        });

        let options = '<option value="">- Seleccione Punto -</option>';
        puntos.forEach(punto => {
            const codigo = punto.codigo_1 ? punto.codigo_1 : 'S/C';
            // CAMBIO: Guardamos la MODALIDAD en data-modalidad
            // (Asegúrate que tu modelo PHP esté devolviendo id_modalidad)
            options += `<option value="${punto.id_punto}" data-modalidad="${punto.id_modalidad}">
                            ${punto.nombre_punto} - (${codigo})
                        </option>`;
        });

        selectPunto.innerHTML = options;
        selectPunto.disabled = false;
        activarSelect2(`#select_punto_${id}`);
    }

    // 2. CARGAR MÁQUINAS (Actualiza Modalidad y Auto-selecciona Máquina)
    async function cargarMaquinas(id, idPunto) {
        limpiarFilaDesde(id, 'maquina');
        if (!idPunto) return;

        // A. LÓGICA DE MODALIDAD (Mantenemos esto igual)
        const selPunto = document.getElementById(`select_punto_${id}`);
        // Nota: Si usas Select2, asegúrate de recuperar el atributo correctamente. 
        // Con Select2 a veces el 'selectedOptions' cambia, pero usando el objeto DOM nativo suele funcionar.
        // Si tienes problemas con select2, usa: $(`#select_punto_${id}`).find(':selected').data('modalidad')
        const opcionPunto = selPunto.options[selPunto.selectedIndex];
        const modalidadDefecto = opcionPunto.getAttribute('data-modalidad') || 1;

        const selectModalidad = document.getElementById(`select_modalidad_${id}`);
        selectModalidad.value = modalidadDefecto;
        selectModalidad.disabled = false;

        // B. CARGAR MÁQUINAS
        const selectMaquina = document.getElementById(`select_maquina_${id}`);
        selectMaquina.innerHTML = '<option value="">Cargando...</option>';

        const maquinas = await enviarAjax('ajaxMaquinas', {
            id_punto: idPunto
        });

        let options = ''; // Ya no ponemos la opción vacía por defecto si queremos auto-seleccionar
        if (maquinas.length === 0) {
            options = '<option value="">- No hay máquinas -</option>';
        }

        maquinas.forEach(m => {
            options += `<option value="${m.id_maquina}" data-device="${m.device_id}" data-tipo="${m.id_tipo_maquina}">
                            ${m.nombre_tipo_maquina} (${m.device_id})
                        </option>`;
        });

        selectMaquina.innerHTML = options;
        selectMaquina.disabled = false;

        // --- C. AUTO-SELECCIONAR LA PRIMERA MÁQUINA ---
        if (maquinas.length > 0) {
            // 1. Seleccionamos visualmente la primera opción
            selectMaquina.selectedIndex = 0;

            // 2. Obtenemos el ID de esa máquina
            const idPrimeraMaquina = maquinas[0].id_maquina;

            // 3. Ejecutamos la función que rellena el Device ID y calcula el precio
            rellenarDeviceId(id, idPrimeraMaquina);
        }
    }

    // 3. RELLENAR DEVICE ID y PREPARAR PRECIO
    function rellenarDeviceId(id, idMaquina) {
        const selectMaquina = document.getElementById(`select_maquina_${id}`);
        const selectedOption = selectMaquina.options[selectMaquina.selectedIndex];

        const deviceId = selectedOption.getAttribute('data-device') || '';
        document.getElementById(`device_id_${id}`).value = deviceId;

        // Si ya habían seleccionado servicio, recalculamos precio
        calcularPrecio(id);
    }

    async function calcularPrecio(id) {
        const fila = document.getElementById(`fila_${id}`);

        // 1. CAMBIO: Leemos directamente el valor del Select de Modalidad
        const selectModalidad = document.getElementById(`select_modalidad_${id}`);
        const idModalidad = selectModalidad.value;

        const selectMaquina = document.getElementById(`select_maquina_${id}`);
        const tipoMaq = selectMaquina.options[selectMaquina.selectedIndex]?.getAttribute('data-tipo');

        const selectServicio = fila.querySelector(`select[name="filas[${id}][tipo_servicio]"]`);
        const idManto = selectServicio.value;

        const inputValor = fila.querySelector(`input[name="filas[${id}][valor]"]`);

        // Validamos que tengamos los 3 datos
        if (idModalidad && tipoMaq && idManto) {
            inputValor.value = "...";

            const res = await enviarAjax('ajaxCalcularPrecio', {
                id_maquina_tipo: tipoMaq,
                id_manto: idManto,
                id_modalidad: idModalidad // Enviamos lo que dice el select
            });

            if (res && res.precio) {
                inputValor.value = new Intl.NumberFormat('es-CO').format(res.precio);
            } else {
                inputValor.value = 0;
            }
        }
    }

    // 5. CALCULAR TIEMPO (Diferencia entre Entrada y Salida)
    function calcTiempo(id) {
        const horaIn = document.getElementById(`in_${id}`).value;
        const horaOut = document.getElementById(`out_${id}`).value;
        const inputDuracion = document.getElementById(`duracion_${id}`);

        if (horaIn && horaOut) {
            // Usamos una fecha base para poder restar
            const d1 = new Date(`2000-01-01T${horaIn}:00`);
            const d2 = new Date(`2000-01-01T${horaOut}:00`);

            let diffMs = d2 - d1;

            // Si sale negativo, es porque pasó de medianoche (ej: 23:00 a 01:00)
            if (diffMs < 0) {
                diffMs += 24 * 60 * 60 * 1000;
            }

            const diffMins = Math.floor(diffMs / 60000);
            const horas = Math.floor(diffMins / 60);
            const minutos = diffMins % 60;

            // Formato 00:00
            const hStr = horas.toString().padStart(2, '0');
            const mStr = minutos.toString().padStart(2, '0');

            inputDuracion.value = `${hStr}:${mStr}`;
            inputDuracion.classList.add('text-green-600', 'font-bold'); // Feedback visual
        } else {
            inputDuracion.value = "";
        }
    }

    function limpiarFilaDesde(id, desde) {
        if (desde === 'punto' || desde === 'maquina') {
            const selectMaquina = document.getElementById(`select_maquina_${id}`);
            selectMaquina.innerHTML = '<option value="">- Primero seleccione punto -</option>';
            selectMaquina.disabled = true;
            document.getElementById(`device_id_${id}`).value = '';
        }
        if (desde === 'punto') {
            const selectPunto = document.getElementById(`select_punto_${id}`);
            selectPunto.innerHTML = '<option value="">- Primero seleccione cliente -</option>';
            selectPunto.disabled = true;
        }
    }

    // --- FUNCIONES BÁSICAS ---
    function eliminarFila(id) {
        const fila = document.getElementById(`fila_${id}`);
        if (fila) {
            fila.remove();
            delete almacenRepuestos[id];
            console.log('Fila eliminada:', id);
        }
    }

    // ==========================================
    // 🛠️ LÓGICA DE REPUESTOS (CON SELECT2 Y FILTRO)
    // ==========================================

    // Variable global para almacenar repuestos por ID de fila
    // Estructura: { '1': [{id:1, nombre:'...', origen:'INEES'}], '2': [] }


    document.addEventListener("DOMContentLoaded", function() {
        // 1. Inicializar Select2 EN EL MODAL
        $('#select_repuesto_modal').select2({
            width: '100%',
            dropdownParent: $('#modalRepuestos'), // 🔥 CLAVE PARA QUE FUNCIONE EN MODAL
            placeholder: "- Escriba para buscar -",
            allowClear: true,
            language: {
                noResults: () => "No se encontró el repuesto"
            }
        });

        // 2. Llenar el Select una sola vez al inicio para no recargar el DOM
        const select = document.getElementById('select_repuesto_modal');
        // listaRepuestosBD viene de tu PHP
        listaRepuestosBD.forEach(r => {
            const option = new Option(r.nombre_repuesto, r.id_repuesto, false, false);
            select.add(option);
        });
    });

    function abrirModalRepuestos(idFila) {
        // 1. Guardar qué fila estamos editando
        document.getElementById('modal_fila_actual').value = idFila;

        // 2. Asegurar que el array para esta fila existe
        if (!almacenRepuestos[idFila]) {
            // Intentar leer del input hidden si venimos de un borrador restaurado
            const hiddenInput = document.getElementById(`json_rep_${idFila}`);
            if (hiddenInput && hiddenInput.value && hiddenInput.value !== '[]') {
                try {
                    almacenRepuestos[idFila] = JSON.parse(hiddenInput.value);
                } catch (e) {
                    almacenRepuestos[idFila] = [];
                }
            } else {
                almacenRepuestos[idFila] = [];
            }
        }

        // 3. Limpiar selección del Select2
        $('#select_repuesto_modal').val(null).trigger('change');

        // 4. Renderizar lista
        renderizarListaVisual(idFila);

        // 5. Mostrar Modal
        document.getElementById('modalRepuestos').classList.remove('hidden');
        document.getElementById('modalRepuestos').classList.add('flex');
    }

    function cerrarModal() {
        document.getElementById('modalRepuestos').classList.add('hidden');
        document.getElementById('modalRepuestos').classList.remove('flex');
    }

    function agregarRepuestoALista() {
        const idFila = document.getElementById('modal_fila_actual').value;

        // Obtener datos del Select2 (jQuery)
        const idRepuesto = $('#select_repuesto_modal').val();
        const dataSelect = $('#select_repuesto_modal').select2('data');
        const nombreRepuesto = dataSelect[0]?.text;

        const origen = document.getElementById('select_origen_modal').value;

        if (!idRepuesto) {
            alert("⚠️ Por favor busque y seleccione un repuesto.");
            return;
        }

        // Agregar al array global
        if (!almacenRepuestos[idFila]) almacenRepuestos[idFila] = [];

        almacenRepuestos[idFila].push({
            id: idRepuesto,
            nombre: nombreRepuesto,
            origen: origen
        });

        // Limpiar buscador y re-renderizar
        $('#select_repuesto_modal').val(null).trigger('change');
        renderizarListaVisual(idFila);
    }

    function borrarRepuestoTemporal(idFila, index) {
        if (almacenRepuestos[idFila]) {
            almacenRepuestos[idFila].splice(index, 1);
            renderizarListaVisual(idFila);
        }
    }

    function renderizarListaVisual(idFila) {
        const ul = document.getElementById('lista_repuestos_visual');
        ul.innerHTML = '';

        const lista = almacenRepuestos[idFila] || [];

        if (lista.length === 0) {
            ul.innerHTML = '<li class="text-gray-400 text-center italic mt-10 flex flex-col items-center"><i class="fas fa-box-open text-2xl mb-2"></i><span>No hay repuestos agregados.</span></li>';
            return;
        }

        lista.forEach((item, index) => {
            const bgBadge = item.origen === 'INEES' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800';

            ul.innerHTML += `
            <li class="flex justify-between items-center bg-gray-50 p-2 mb-2 border rounded shadow-sm hover:bg-white transition">
                <div class="flex items-center gap-2 overflow-hidden">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded ${bgBadge}">${item.origen}</span>
                    <span class="text-xs text-gray-700 truncate font-medium" title="${item.nombre}">${item.nombre}</span>
                </div>
                <button type="button" onclick="borrarRepuestoTemporal('${idFila}', ${index})" class="text-red-400 hover:text-red-600 px-2">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </li>
        `;
        });
    }

    function guardarCambiosModal() {
        const idFila = document.getElementById('modal_fila_actual').value;
        const lista = almacenRepuestos[idFila] || [];

        // 1. Actualizar Input Oculto en la tabla (para que se envíe al backend)
        const jsonInput = document.getElementById(`json_rep_${idFila}`);
        if (jsonInput) {
            jsonInput.value = JSON.stringify(lista);
        }

        // 2. Actualizar Botón Visual en la tabla
        const btnTexto = document.getElementById(`count_rep_${idFila}`);
        if (btnTexto) {
            btnTexto.innerText = `${lista.length} Items`;

            // Cambiar color del botón si tiene items
            const btnPadre = btnTexto.parentElement; // El botón <button>
            if (lista.length > 0) {
                btnPadre.classList.remove('bg-gray-200', 'text-gray-700');
                btnPadre.classList.add('bg-blue-600', 'text-white', 'border-blue-700');
            } else {
                btnPadre.classList.add('bg-gray-200', 'text-gray-700');
                btnPadre.classList.remove('bg-blue-600', 'text-white', 'border-blue-700');
            }
        }

        cerrarModal();
    }


    // Función para convertir un Select normal en uno con Buscador
    function activarSelect2(selector) {
        $(selector).select2({
            width: '100%', // Que ocupe todo el ancho de la celda
            language: {
                noResults: function() {
                    return "No se encontraron resultados";
                }
            }
        });
    }

    function activarHora(selector, idFila) {
        flatpickr(selector, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i", // Formato 24 horas (ej: 14:30)
            time_24hr: true, // Obliga al reloj de 24h
            locale: "es",
            defaultHour: 12,
            minuteIncrement: 5, // Salta de 5 en 5 minutos (opcional, ayuda a ser más rápido)
            onClose: function(selectedDates, dateStr, instance) {
                // Cuando cierra el reloj, disparamos el cálculo de tiempo manualmente
                calcTiempo(idFila);
            }
        });
    }

    function activarInputHora(selector, idFila) {
        // 1. Aplicar la máscara: Ella escribe 1430 y se ve 14:30
        $(selector).mask('00:00');

        // 2. Validar cuando ella salga de la casilla (evento blur)
        $(selector).on('blur', function() {
            const valor = $(this).val();

            // Si está vacío, no hacemos nada
            if (valor === '') return;

            // Validar formato hh:mm
            const partes = valor.split(':');
            const horas = parseInt(partes[0]);
            const minutos = parseInt(partes[1]);

            let esValido = true;

            // Reglas: Horas 0-23, Minutos 0-59, y que tenga los 5 caracteres
            if (valor.length !== 5 || isNaN(horas) || isNaN(minutos)) esValido = false;
            if (horas < 0 || horas > 23) esValido = false;
            if (minutos < 0 || minutos > 59) esValido = false;

            if (!esValido) {
                alert('⚠️ Hora inválida. Use formato 24 horas (00:00 a 23:59). Ej: 14:30');
                $(this).val(''); // Borrar si está mal
                $(this).addClass('border-red-500 bg-red-50');
            } else {
                $(this).removeClass('border-red-500 bg-red-50');
                // Si está bien, calculamos el tiempo automáticamente
                calcTiempo(idFila);
            }
        });
    }

    // --- INICIALIZACIÓN COMPLETA ---
    document.addEventListener("DOMContentLoaded", function() {
        console.log('DOM cargado. Iniciando sistema...');

        // 1. CARGAR OPCIONES DEL SELECT DE REPUESTOS (Tu código original)
        const selRep = document.getElementById('select_repuesto_id');
        if (selRep) {
            listaRepuestosBD.forEach(r => {
                selRep.innerHTML += `<option value="${r.id_repuesto}">${r.nombre_repuesto}</option>`;
            });
        }

        // 2. ACTIVAR EL BUSCADOR SELECT2 EN EL MODAL (¡Nuevo!)
        // Esto habilita la búsqueda por texto en los repuestos
        $('#select_repuesto_id').select2({
            width: '100%',
            dropdownParent: $('#modalRepuestos'), // CRÍTICO: Sin esto, el buscador no funciona en el modal
            placeholder: "- Buscar Repuesto -",
            language: {
                noResults: () => "No se encontró el repuesto"
            }
        });

        // 3. LÓGICA DE INICIO INTELIGENTE (Auto-Guardado vs Filas Nuevas)
        const hayBorrador = localStorage.getItem(CLAVE_GUARDADO);

        if (hayBorrador) {
            // Si hay algo guardado, llamamos a la función de restaurar
            // (Ella se encarga de preguntar y limpiar si es necesario)
            verificarYRestaurar();
        } else {
            // Si NO hay nada guardado, iniciamos con las 3 filas vacías por defecto
            console.log("No hay borrador, iniciando filas vacías.");
            for (let i = 0; i < 3; i++) {
                agregarFila();
            }
        }

        // 4. ACTIVAR EL TIMER DE AUTO-GUARDADO
        // Guardará cambios cada 5 segundos
        setInterval(guardarProgresoLocal, 5000);

        // 5. LIMPIAR BORRADOR AL ENVIAR FORMULARIO
        // Si guarda exitosamente, ya no necesitamos el borrador
        const form = document.getElementById('formServicios');
        if (form) {
            form.addEventListener('submit', function() {
                localStorage.removeItem(CLAVE_GUARDADO);
            });
        }
    });



    // ==========================================
    // 🛡️ MÓDULO DE AUTO-GUARDADO (SALVAVIDAS)
    // ==========================================

    const CLAVE_GUARDADO = 'borrador_orden_servicios';

    // 1. FUNCIÓN PARA GUARDAR (Se ejecuta cada 5 segundos si hubo cambios)
    function guardarProgresoLocal() {
        const filas = [];
        const filasHTML = document.querySelectorAll('#contenedorFilas tr');

        filasHTML.forEach(tr => {
            const idFila = tr.id.replace('fila_', '');

            // Obtenemos los valores de esa fila
            const filaData = {
                id: idFila,
                remision: tr.querySelector(`input[name="filas[${idFila}][remision]"]`)?.value || '',
                id_cliente: $(`#select_cliente_${idFila}`).val(), // Select2 usa jQuery val()
                id_punto: $(`#select_punto_${idFila}`).val(),
                id_maquina: $(`#select_maquina_${idFila}`).val(), // OJO: Esto guarda el ID de la máquina
                modalidad: document.getElementById(`select_modalidad_${idFila}`)?.value,
                id_tecnico: tr.querySelector(`select[name="filas[${idFila}][id_tecnico]"]`)?.value,
                tipo_servicio: tr.querySelector(`select[name="filas[${idFila}][tipo_servicio]"]`)?.value,
                hora_in: document.getElementById(`in_${idFila}`)?.value,
                hora_out: document.getElementById(`out_${idFila}`)?.value,
                valor: tr.querySelector(`input[name="filas[${idFila}][valor]"]`)?.value,
                estado: tr.querySelector(`select[name="filas[${idFila}][estado]"]`)?.value,
                calif: tr.querySelector(`select[name="filas[${idFila}][calif]"]`)?.value,
                obs: tr.querySelector(`textarea[name="filas[${idFila}][obs]"]`)?.value
            };
            filas.push(filaData);
        });

        const datosGlobales = {
            fecha: new Date().getTime(), // Para saber cuándo se guardó
            filas: filas,
            repuestos: almacenRepuestos // Guardamos también los repuestos
        };

        localStorage.setItem(CLAVE_GUARDADO, JSON.stringify(datosGlobales));

        // Feedback visual discreto en la consola
        // console.log("Borrador auto-guardado", new Date().toLocaleTimeString());
    }

    // 2. FUNCIÓN PARA RESTAURAR
    async function verificarYRestaurar() {
        const borrador = localStorage.getItem(CLAVE_GUARDADO);

        if (borrador) {
            const datos = JSON.parse(borrador);
            // Si el borrador es de hace más de 24 horas, mejor lo ignoramos (opcional)
            // Pero aquí preguntaremos siempre.

            if (datos.filas.length > 0 && confirm(`⚠️ Hemos encontrado un borrador con ${datos.filas.length} servicios no guardados. \n\n¿Deseas recuperarlos?`)) {

                // A. Limpiar tabla actual
                document.getElementById('contenedorFilas').innerHTML = '';
                contadorFilas = 0;
                almacenRepuestos = datos.repuestos || {};

                // B. Reconstruir filas (Una por una y esperando las cargas AJAX)
                // Usamos un bucle for..of para poder usar await
                for (const fila of datos.filas) {
                    // 1. Crear estructura visual
                    agregarFila();
                    const idActual = contadorFilas; // El ID que acaba de generar agregarFila()

                    // 2. Llenar datos simples
                    document.querySelector(`input[name="filas[${idActual}][remision]"]`).value = fila.remision;

                    // Cliente
                    $(`#select_cliente_${idActual}`).val(fila.id_cliente).trigger('change');

                    // 3. Esperar a que carguen los puntos (CRÍTICO)
                    if (fila.id_cliente) {
                        await cargarPuntos(idActual, fila.id_cliente); // Esperamos a AJAX
                        $(`#select_punto_${idActual}`).val(fila.id_punto).trigger('change');
                    }

                    // 4. Esperar a que carguen las máquinas
                    if (fila.id_punto) {
                        await cargarMaquinas(idActual, fila.id_punto); // Esperamos a AJAX
                        // Selección manual de la máquina guardada
                        const selMaq = document.getElementById(`select_maquina_${idActual}`);
                        selMaq.value = fila.id_maquina;
                        // Forzar relleno de Device ID y Precio
                        rellenarDeviceId(idActual, fila.id_maquina);
                    }

                    // 5. Resto de campos
                    document.getElementById(`select_modalidad_${idActual}`).value = fila.modalidad;
                    document.querySelector(`select[name="filas[${idActual}][id_tecnico]"]`).value = fila.id_tecnico;
                    document.querySelector(`select[name="filas[${idActual}][tipo_servicio]"]`).value = fila.tipo_servicio;
                    document.getElementById(`in_${idActual}`).value = fila.hora_in;
                    document.getElementById(`out_${idActual}`).value = fila.hora_out;
    
                    // ACTIVAR MÁSCARA AL RESTAURAR
                    activarInputHora(`#in_${idActual}`, idActual); // <--- IMPORTANTE
    
                    calcTiempo(idActual);

                    document.querySelector(`input[name="filas[${idActual}][valor]"]`).value = fila.valor;
                    document.querySelector(`select[name="filas[${idActual}][estado]"]`).value = fila.estado;
                    document.querySelector(`select[name="filas[${idActual}][calif]"]`).value = fila.calif;
                    document.querySelector(`textarea[name="filas[${idActual}][obs]"]`).value = fila.obs;

                    // Actualizar botón de repuestos visualmente
                    const btnRep = document.getElementById(`count_rep_${idActual}`);
                    const numRep = almacenRepuestos[idActual] ? almacenRepuestos[idActual].length : 0;
                    btnRep.innerText = `${numRep} Items`;
                    if (numRep > 0) btnRep.parentElement.classList.add('bg-blue-100', 'border-blue-300');

                    // IMPORTANTE: Actualizar el input oculto de repuestos para que se envíe al guardar
                    const jsonInput = document.getElementById(`json_rep_${idActual}`);
                    if (jsonInput && almacenRepuestos[idActual]) {
                        jsonInput.value = JSON.stringify(almacenRepuestos[idActual]);
                    }
                }
                alert("✅ Información recuperada con éxito.");
            } else {
                // Si dice que no, borramos el borrador viejo
                localStorage.removeItem(CLAVE_GUARDADO);
            }
        }
    }

    // 3. INICIAR SISTEMA
    document.addEventListener("DOMContentLoaded", function() {
        // Intentar restaurar al cargar la página
        // Ponemos un pequeño timeout para asegurar que Select2 y todo esté listo
        setTimeout(verificarYRestaurar, 500);

        // Activar guardado automático cada 5 segundos
        setInterval(guardarProgresoLocal, 5000);

        // Limpiar borrador al enviar el formulario EXITOSAMENTE
        const form = document.getElementById('formServicios');
        form.addEventListener('submit', function() {
            // Solo limpiamos si el submit es válido (esto es optimista)
            // Lo ideal es limpiarlo en el PHP cuando redirecciona, pero aquí funciona bien
            localStorage.removeItem(CLAVE_GUARDADO);
        });
    });
</script>