<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </h3>

        <input type="hidden" id="modal_fila_actual">

        <div class="space-y-4">
            <div class="flex gap-2">
                <div class="flex-grow">
                    <select id="select_repuesto_id" class="w-full border rounded p-2 text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500">
                        <option value="">- Buscar Repuesto -</option>
                    </select>
                </div>

                <select id="select_origen" class="border rounded p-2 text-sm bg-gray-100 font-bold text-gray-700">
                    <option value="INEES">INEES</option>
                    <option value="PROSEGUR">PROSEGUR</option>
                </select>

                <button onclick="agregarRepuestoAlGrid()" class="bg-blue-600 text-white px-4 rounded hover:bg-blue-700 shadow transition">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <ul id="lista_repuestos_modal" class="border rounded p-2 h-40 overflow-y-auto bg-gray-50 text-sm">
                <li class="text-gray-400 text-center italic mt-10">No hay repuestos agregados aún.</li>
            </ul>
        </div>

        <div class="mt-6 text-right">
            <button onclick="guardarRepuestosModal()" class="bg-green-600 text-white px-6 py-2 rounded font-bold hover:bg-green-700 shadow-lg transform hover:scale-105 transition">
                Confirmar y Cerrar
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
            <select name="filas[${id}][tecnico_asignado]" onchange="tecnicoAsignado(${id})" class="w-full border rounded p-1 font-semibold">                
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
                <input type="time" name="filas[${id}][hora_in]" id="in_${id}" onchange="calcTiempo(${id})" class="border rounded p-0.5 w-20">
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-[10px]">Salida:</span> 
                <input type="time" name="filas[${id}][hora_out]" id="out_${id}" onchange="calcTiempo(${id})" class="border rounded p-0.5 w-20">
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

    // --- FUNCIONES DEL MODAL DE REPUESTOS ---
    function abrirModalRepuestos(id) {
        console.log('Abriendo modal para fila:', id);
        document.getElementById('modalRepuestos').classList.remove('hidden');
        document.getElementById('modalRepuestos').classList.add('flex');
        document.getElementById('modal_fila_actual').value = id;
        renderizarListaModal(id);
    }

    function cerrarModal() {
        document.getElementById('modalRepuestos').classList.add('hidden');
        document.getElementById('modalRepuestos').classList.remove('flex');
    }

    // Actualizar función agregar
    function agregarRepuestoAlGrid() {
        const idFila = document.getElementById('modal_fila_actual').value;

        const selectRep = document.getElementById('select_repuesto_id');
        const idRepuesto = selectRep.value;
        const nombreRepuesto = selectRep.options[selectRep.selectedIndex].text;

        const origen = document.getElementById('select_origen').value;

        if (!idRepuesto) {
            alert('Por favor seleccione un repuesto de la lista');
            return;
        }

        if (!almacenRepuestos[idFila]) almacenRepuestos[idFila] = [];

        // Guardamos ID, Nombre y Origen
        almacenRepuestos[idFila].push({
            id: idRepuesto,
            nombre: nombreRepuesto,
            origen: origen
        });

        // Resetear select
        selectRep.value = "";
        renderizarListaModal(idFila);
    }

    function renderizarListaModal(id) {
        const lista = almacenRepuestos[id] || [];
        const ul = document.getElementById('lista_repuestos_modal');

        if (!ul) {
            console.error('No se encontró la lista de repuestos');
            return;
        }

        ul.innerHTML = '';

        if (lista.length === 0) {
            ul.innerHTML = '<li class="text-gray-400 text-center italic mt-10">No hay repuestos agregados aún.</li>';
            return;
        }

        lista.forEach((item, index) => {
            ul.innerHTML += `
        <li class="flex justify-between items-center bg-white p-2 mb-1 border rounded shadow-sm">
            <span>
                <b class="text-xs ${item.origen === 'INEES' ? 'text-blue-600' : 'text-orange-600'}">[${item.origen}]</b> 
                ${item.nombre}
            </span>
            <button onclick="borrarRepuesto(${id}, ${index})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        </li>`;
        });
    }

    function borrarRepuesto(idFila, index) {
        if (almacenRepuestos[idFila]) {
            almacenRepuestos[idFila].splice(index, 1);
            renderizarListaModal(idFila);
        }
    }

    function guardarRepuestosModal() {
        const id = document.getElementById('modal_fila_actual').value;
        const lista = almacenRepuestos[id] || [];

        // Actualizar botón en el grid
        const btn = document.getElementById(`count_rep_${id}`);
        if (btn) {
            btn.innerText = `${lista.length} Items`;
            if (lista.length > 0) {
                btn.parentElement.classList.add('bg-blue-100', 'border-blue-300');
            } else {
                btn.parentElement.classList.remove('bg-blue-100', 'border-blue-300');
            }
        }

        // Guardar JSON en input oculto
        const jsonInput = document.getElementById(`json_rep_${id}`);
        if (jsonInput) {
            jsonInput.value = JSON.stringify(lista);
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

    // --- INICIALIZACIÓN ---
    document.addEventListener("DOMContentLoaded", function() {
        console.log('DOM cargado, inicializando filas...');
        // Agregar 3 filas iniciales
        for (let i = 0; i < 3; i++) {
            agregarFila();
        }
        const selRep = document.getElementById('select_repuesto_id');
        listaRepuestosBD.forEach(r => {
            selRep.innerHTML += `<option value="${r.id_repuesto}">${r.nombre_repuesto}</option>`;
        });
    });
</script>