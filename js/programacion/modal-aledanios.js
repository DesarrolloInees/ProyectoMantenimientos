/**
 * js/programacion/modal-aledanios.js
 * Modal para ver y seleccionar puntos aledaños de una o varias zonas
 * (el backend ya soporta múltiples zonas separadas por coma).
 */
(function () {
    function abrir() {
        document.getElementById('modalAledanios').classList.remove('hidden');
    }

    function cerrar() {
        document.getElementById('modalAledanios').classList.add('hidden');
    }

    function buscarAledaniosPunto(idPunto, zona) {
        lanzar(idPunto, zona);
    }

    function lanzar(idPunto, cadenaZonas) {
        abrir();
        const titulo = document.getElementById('modalAledaniosTitulo');
        const contenedor = document.getElementById('modalAledaniosContenido');

        titulo.innerHTML = `<i class="fas fa-map-marked-alt mr-2 text-blue-200"></i> Puntos en Zona(s): <span class="text-blue-100 ml-1">${Prog.util.escapeHtml(cadenaZonas)}</span>`;
        contenedor.innerHTML = `
            <div class="text-center p-12">
                <i class="fas fa-spinner fa-spin fa-3x text-indigo-500 mb-4"></i>
                <p class="text-gray-600 font-semibold text-lg">Buscando puntos cercanos...</p>
            </div>`;

        const url = `${Prog.config.baseUrl}index.php?pagina=programacionCrear&accion=puntos_aledanios&id_punto=${idPunto}&zona=${encodeURIComponent(cadenaZonas)}`;
        fetch(url)
            .then(resp => resp.json())
            .then(data => {
                if (data.error) {
                    contenedor.innerHTML = `<div class="bg-red-100 text-red-800 p-4 rounded-lg font-bold"><i class="fas fa-exclamation-triangle mr-2"></i> ${Prog.util.escapeHtml(data.error)}</div>`;
                    return;
                }
                dibujar(data.puntos, contenedor);
            })
            .catch(err => {
                contenedor.innerHTML = `<div class="bg-red-100 text-red-800 p-4 rounded-lg font-bold"><i class="fas fa-times-circle mr-2"></i> Error de conexión: ${Prog.util.escapeHtml(err.message)}</div>`;
            });
    }

    function dibujar(puntos, contenedor) {
        if (!puntos || puntos.length === 0) {
            contenedor.innerHTML = `
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg shadow-sm">
                    <p class="font-bold text-yellow-800 text-lg"><i class="fas fa-info-circle mr-2"></i> No hay puntos pendientes en esta zona.</p>
                </div>`;
            return;
        }

        const inactivasMarcadasIds = Prog.util.qsa('.check-maquina-inactiva:checked').map(cb => cb.value);
        const hoy = new Date().toISOString().split('T')[0];

        let html = `
            <div class="flex justify-between items-center mb-5 bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                <p class="text-gray-800 font-semibold text-sm">Selecciona los puntos y asigna fecha/técnico:</p>
                <div class="space-x-2">
                    <button type="button" onclick="marcarTodosAledaniosModal(true)" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-800 px-3 py-1.5 rounded-lg font-bold border"><i class="fas fa-check-double mr-1"></i> Todos</button>
                    <button type="button" onclick="marcarTodosAledaniosModal(false)" class="text-xs bg-white hover:bg-gray-50 text-gray-600 px-3 py-1.5 rounded-lg font-bold border"><i class="fas fa-times mr-1"></i> Ninguno</button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">`;

        puntos.forEach(punto => {
            const badgeFueraServicio = punto.fuera_de_servicio == 1
                ? '<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-[10px] font-bold ml-1 border border-red-200">FUERA DE SERVICIO</span>'
                : '';
            const fechaUltimoMto = punto.fecha_ultima_visita ? punto.fecha_ultima_visita.split(' ')[0] : 'Sin Registro';
            const estaMarcadoPrevio = inactivasMarcadasIds.includes(String(punto.id_punto)) ? 'checked' : '';

            html += `
                <div class="p-4 bg-white border border-gray-200 rounded-xl hover:shadow-md transition w-full flex flex-col justify-between">
                    <div>
                        <div class="flex items-start space-x-3">
                            <input type="checkbox"
                                class="mt-1 w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 check-aledanio-modal shadow-sm"
                                value="${punto.id_punto}" data-punto-id="${punto.id_punto}" ${estaMarcadoPrevio}>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-900 text-sm mb-1 break-words">
                                    ${Prog.util.escapeHtml(punto.nombre_punto)} ${badgeFueraServicio}
                                </div>
                                <div class="text-xs text-gray-600 space-y-1">
                                    <div><i class="fas fa-user-tie text-gray-400 w-4"></i> ${Prog.util.escapeHtml(punto.nombre_cliente)}</div>
                                    <div><i class="fas fa-map-marker-alt text-red-400 w-4"></i> ${Prog.util.escapeHtml(punto.direccion || 'Sin Dirección')}</div>
                                    <div><i class="fas fa-microchip text-gray-400 w-4"></i> ${Prog.util.escapeHtml(punto.device_id || 'S/N')} (${Prog.util.escapeHtml(punto.tipo_maquina || 'N/A')})</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-t border-gray-100 flex justify-between items-center text-[11px] text-gray-500 font-semibold">
                            <span><i class="fas fa-calendar-check text-green-500 mr-1"></i> Último: ${fechaUltimoMto}</span>
                            <span class="px-2 py-0.5 bg-orange-50 text-orange-600 rounded-full"><i class="fas fa-clock mr-1"></i> ${punto.dias_sin_visita || '0'} días</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-2 gap-2 bg-gray-50 p-2 rounded-lg">
                        <div>
                            <label class="text-[10px] font-bold text-gray-600 block mb-1">Fecha Visita:</label>
                            <input type="date" id="fecha_modal_${punto.id_punto}" value="${hoy}" class="w-full text-xs border border-gray-300 rounded px-2 py-1 bg-white">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-600 block mb-1">Técnico:</label>
                            <select id="tecnico_modal_${punto.id_punto}" class="w-full text-xs border border-gray-300 rounded px-2 py-1 bg-white">
                                <option value="">-- Seleccionar --</option>
                                ${Prog.util.opcionesTecnicos()}
                            </select>
                        </div>
                    </div>
                </div>`;
        });

        html += `</div>`;
        contenedor.innerHTML = html;
    }

    function marcarTodos(marcar) {
        Prog.util.qsa('.check-aledanio-modal').forEach(cb => { cb.checked = marcar; });
    }

    /** 
     * INTEGRA AMBAS FORMAS:
     * 1. Si está configurando la semana (#formCalendario), inyecta los puntos como campos ocultos para enviarse con la semana.
     * 2. Si ya está en la previsualización (#formGuardar), envía todo junto a la vista previa.
     */
    function agregarDesdeModal() {
        const seleccionados = Prog.util.qsa('.check-aledanio-modal:checked');
        if (seleccionados.length === 0) {
            alert('No has seleccionado ningún punto.');
            return;
        }

        let faltanDatos = false;
        const listaServiciosNuevos = [];

        seleccionados.forEach(cb => {
            const idPunto = cb.dataset.puntoId;
            const fechaVal = document.getElementById(`fecha_modal_${idPunto}`)?.value || '';
            const tecnicoVal = document.getElementById(`tecnico_modal_${idPunto}`)?.value || '';

            if (!fechaVal || !tecnicoVal) {
                faltanDatos = true;
            } else {
                listaServiciosNuevos.push({ id_punto: idPunto, fecha_visita: fechaVal, id_tecnico: tecnicoVal });
            }
        });

        if (faltanDatos) {
            alert('⚠️ Asigna Fecha de Visita y Técnico a todos los puntos seleccionados.');
            return;
        }

        const formCalendario = document.getElementById('formCalendario');

        // CASO A: Estamos en la vista de Programación Semanal Normal
        if (formCalendario) {
            // Eliminar inyecciones previas del modal para evitar duplicados
            Prog.util.qsa('.input-extra-modal').forEach(el => el.remove());

            listaServiciosNuevos.forEach((item) => {
                formCalendario.appendChild(Prog.util.el('input', { type: 'hidden', class: 'input-extra-modal', name: 'puntos_aledanios_extra[]', value: item.id_punto }));
            });

            alert(`✅ Se vincularon ${listaServiciosNuevos.length} punto(s) inactivo(s) a la programación semanal.`);
            cerrar();
            return;
        }

        // CASO B: Estamos en la Previsualización Diaria
        const formGuardar = document.getElementById('formGuardar');
        if (formGuardar) {
            Prog.util.qsa('#formGuardar tbody tr[id^="fila_"]').forEach(fila => {
                const idPunto = fila.querySelector('input[name*="[id_punto]"]')?.value;
                const fechaVal = fila.querySelector('input[name*="[fecha_visita]"]')?.value;
                const tecnicoVal = fila.querySelector('select[name*="[id_tecnico]"]')?.value;
                if (idPunto && fechaVal && tecnicoVal && !listaServiciosNuevos.some(s => s.id_punto == idPunto)) {
                    listaServiciosNuevos.push({ id_punto: idPunto, fecha_visita: fechaVal, id_tecnico: tecnicoVal });
                }
            });
        }

        // Enviar formulario directo a la previsualización
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${Prog.config.baseUrl}index.php?pagina=programacionCrear`;
        form.appendChild(Prog.util.el('input', { type: 'hidden', name: 'accion', value: 'previsualizar_diario' }));

        listaServiciosNuevos.forEach((item, index) => {
            form.appendChild(Prog.util.el('input', { type: 'hidden', name: `servicios_diarios[${index}][id_punto]`, value: item.id_punto }));
            form.appendChild(Prog.util.el('input', { type: 'hidden', name: `servicios_diarios[${index}][fecha_visita]`, value: item.fecha_visita }));
            form.appendChild(Prog.util.el('input', { type: 'hidden', name: `servicios_diarios[${index}][id_tecnico]`, value: item.id_tecnico }));
        });

        document.body.appendChild(form);
        cerrar();
        form.submit();
    }

    Prog.modalAledanios = { abrir, cerrar, lanzar };

    window.abrirModalAledanios = abrir;
    window.cerrarModalAledanios = cerrar;
    window.buscarAledaniosPunto = buscarAledaniosPunto;
    window.marcarTodosAledaniosModal = marcarTodos;
    window.agregarAledaniosDesdeModal = agregarDesdeModal;
})();