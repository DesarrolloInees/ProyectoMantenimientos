/**
 * js/programacion/calendario-semanal.js
 * PASO 2: Calendario semanal interactivo.
 * Permite seleccionar una o múltiples zonas por día y elegir manualmente
 * los puntos a visitar mediante un modal interactivo.
 */
(function () {
    const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];

    // Estado en memoria de los puntos seleccionados por cada día
    const puntosSeleccionadosPorDia = {
        lunes: [],
        martes: [],
        miercoles: [],
        jueves: [],
        viernes: [],
        sabado: []
    };

    let diaActualModal = null;
    let puntosCargadosEnModal = [];

    /**
     * Habilita/deshabilita la selección de zonas según haya técnico seleccionado
     */
    function toggleZonas(dia) {
        const selectTecnico = Prog.util.qs(`select[name="tecnico_${dia}"]`);
        const zonasContainer = document.getElementById(`zonas_container_${dia}`);
        const checkboxes = Prog.util.qsa(`.checkbox-zona-${dia}`);
        const preview = document.getElementById(`preview_${dia}`);
        const btnModal = document.getElementById(`btn_modal_puntos_${dia}`);
        const countSpan = document.getElementById(`count_puntos_${dia}`);

        if (selectTecnico && selectTecnico.value) {
            zonasContainer.classList.remove('opacity-50', 'pointer-events-none', 'bg-gray-50');
            zonasContainer.classList.add('bg-white');
        } else {
            zonasContainer.classList.add('opacity-50', 'pointer-events-none', 'bg-gray-50');
            zonasContainer.classList.remove('bg-white');
            checkboxes.forEach(cb => { cb.checked = false; });
            preview.classList.add('hidden');

            // Limpiar puntos seleccionados para este día si se desasigna el técnico
            puntosSeleccionadosPorDia[dia] = [];
            if (countSpan) countSpan.textContent = '0';
            const hiddenCont = document.getElementById(`hidden_puntos_dia_${dia}`);
            if (hiddenCont) hiddenCont.innerHTML = '';
            const badge = document.getElementById(`resumen_puntos_badge_${dia}`);
            if (badge) badge.classList.add('hidden');

            if (btnModal) {
                btnModal.disabled = true;
                btnModal.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
        updatePreview(dia);
    }

    /**
     * Actualiza el resumen de ruta configurada y el botón para elegir puntos
     */
    function updatePreview(dia) {
        const selectTecnico = Prog.util.qs(`select[name="tecnico_${dia}"]`);
        const checkboxes = Prog.util.qsa(`.checkbox-zona-${dia}:checked`);
        const preview = document.getElementById(`preview_${dia}`);
        const previewText = document.getElementById(`preview_text_${dia}`);
        const btnModal = document.getElementById(`btn_modal_puntos_${dia}`);
        const countSpan = document.getElementById(`count_puntos_${dia}`);
        const badge = document.getElementById(`resumen_puntos_badge_${dia}`);

        if (!selectTecnico) return;

        const tecnicoNombre = selectTecnico.options[selectTecnico.selectedIndex]?.text;
        const zonasSeleccionadas = checkboxes
            .map(cb => cb.parentElement.querySelector('strong')?.textContent.trim())
            .filter(Boolean);

        const puntosCount = (puntosSeleccionadosPorDia[dia] || []).length;
        if (countSpan) countSpan.textContent = puntosCount;

        if (selectTecnico.value && zonasSeleccionadas.length > 0) {
            // Habilitar botón para abrir modal
            if (btnModal) {
                btnModal.disabled = false;
                btnModal.classList.remove('opacity-50', 'cursor-not-allowed');
            }

            preview.classList.remove('hidden');
            previewText.innerHTML = `<strong>${Prog.util.escapeHtml(tecnicoNombre)}</strong> recorrerá: 
                <span class="font-semibold text-indigo-600">${zonasSeleccionadas.map(Prog.util.escapeHtml).join(' + ')}</span>`;

            // Actualizar badge de puntos seleccionados
            if (badge) {
                badge.classList.remove('hidden');
                if (puntosCount > 0) {
                    badge.innerHTML = `<span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1 rounded-full border border-indigo-300 flex items-center">
                        <i class="fas fa-check-circle mr-1 text-indigo-600"></i> ${puntosCount} punto${puntosCount > 1 ? 's' : ''} elegido${puntosCount > 1 ? 's' : ''}
                    </span>`;
                } else {
                    badge.innerHTML = `<span class="bg-amber-50 text-amber-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-amber-200 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-1 text-amber-500"></i> Sin puntos elegidos
                    </span>`;
                }
            }
        } else if (selectTecnico.value) {
            if (btnModal) {
                btnModal.disabled = true;
                btnModal.classList.add('opacity-50', 'cursor-not-allowed');
            }
            preview.classList.remove('hidden');
            previewText.innerHTML = `<strong>${Prog.util.escapeHtml(tecnicoNombre)}</strong> asignado - <span class="text-orange-600 font-semibold">Falta seleccionar zonas</span>`;
            if (badge) badge.classList.add('hidden');
        } else {
            if (btnModal) {
                btnModal.disabled = true;
                btnModal.classList.add('opacity-50', 'cursor-not-allowed');
            }
            preview.classList.add('hidden');
            if (badge) badge.classList.add('hidden');
        }
    }

    /**
     * Busca si un punto ya fue asignado en otro día para advertir al usuario
     */
    function buscarDiaAsignado(idPunto, diaExcluir) {
        for (const dia of DIAS) {
            if (dia !== diaExcluir && puntosSeleccionadosPorDia[dia].includes(String(idPunto))) {
                return dia;
            }
        }
        return null;
    }

    /**
     * Abre el modal interactivo de selección de puntos para el día indicado
     */
    function abrirModalPuntosDia(dia) {
        const selectTecnico = Prog.util.qs(`select[name="tecnico_${dia}"]`);
        const checkboxes = Prog.util.qsa(`.checkbox-zona-${dia}:checked`);

        if (!selectTecnico || !selectTecnico.value) {
            alert('⚠️ Primero debes seleccionar un técnico para este día.');
            return;
        }

        const zonas = checkboxes.map(cb => cb.value).filter(Boolean);
        if (zonas.length === 0) {
            alert('⚠️ Marca al menos una zona para este día antes de elegir los puntos.');
            return;
        }

        diaActualModal = dia;
        const diaNombre = dia.charAt(0).toUpperCase() + dia.slice(1);
        const tecnicoNombre = selectTecnico.options[selectTecnico.selectedIndex]?.text;

        // Configurar títulos del modal
        const modalTitulo = document.getElementById('modalDiaTitulo');
        const modalSubtitulo = document.getElementById('modalDiaSubtitulo');
        const modalContenido = document.getElementById('modalDiaContenido');
        const buscador = document.getElementById('buscadorPuntosModal');

        if (modalTitulo) {
            modalTitulo.innerHTML = `<i class="fas fa-calendar-check mr-2 text-indigo-200"></i> Seleccionar Puntos para <span class="text-yellow-300 ml-1 font-extrabold">${diaNombre}</span>`;
        }
        if (modalSubtitulo) {
            modalSubtitulo.innerHTML = `Técnico: <strong class="text-white">${Prog.util.escapeHtml(tecnicoNombre)}</strong> | Zona(s): <strong class="text-white">${zonas.map(Prog.util.escapeHtml).join(', ')}</strong>`;
        }
        if (buscador) buscador.value = '';

        // Mostrar cargando
        modalContenido.innerHTML = `
            <div class="text-center p-12">
                <i class="fas fa-spinner fa-spin fa-3x text-indigo-600 mb-4"></i>
                <p class="text-gray-600 font-semibold text-base">Cargando puntos de las zonas seleccionadas...</p>
                <p class="text-xs text-gray-400 mt-1">Filtrando por delegación y clientes activos</p>
            </div>`;

        // Mostrar modal
        document.getElementById('modalSeleccionPuntosDia').classList.remove('hidden');

        // Petición AJAX para obtener puntos
        const params = new URLSearchParams();
        params.append('pagina', 'programacionCrear');
        params.append('accion', 'obtener_puntos_zonas');
        params.append('delegacion', Prog.config.delegacion || '');
        params.append('zonas', zonas.join(','));

        if (Array.isArray(Prog.config.clientes) && Prog.config.clientes.length > 0) {
            params.append('clientes', Prog.config.clientes.join(','));
        }

        fetch(`${Prog.config.baseUrl}index.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    modalContenido.innerHTML = `<div class="bg-red-100 text-red-800 p-4 rounded-lg font-bold"><i class="fas fa-exclamation-triangle mr-2"></i> ${Prog.util.escapeHtml(data.error)}</div>`;
                    return;
                }
                puntosCargadosEnModal = data.puntos || [];
                renderizarPuntosEnModal(puntosCargadosEnModal);
            })
            .catch(err => {
                modalContenido.innerHTML = `<div class="bg-red-100 text-red-800 p-4 rounded-lg font-bold"><i class="fas fa-times-circle mr-2"></i> Error de conexión: ${Prog.util.escapeHtml(err.message)}</div>`;
            });
    }

    /**
     * Dibuja las tarjetas de puntos dentro del modal
     */
    function renderizarPuntosEnModal(puntos) {
        const modalContenido = document.getElementById('modalDiaContenido');
        if (!puntos || puntos.length === 0) {
            modalContenido.innerHTML = `
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg shadow-sm text-center">
                    <p class="font-bold text-yellow-800 text-base"><i class="fas fa-info-circle mr-2"></i> No se encontraron puntos pendientes en la(s) zona(s) seleccionada(s).</p>
                    <p class="text-xs text-yellow-700 mt-1">Verifica que los clientes tengan puntos pendientes o que no estén ya programados en otras órdenes activas.</p>
                </div>`;
            actualizarContadorModal();
            return;
        }

        const seleccionadosPrevios = puntosSeleccionadosPorDia[diaActualModal] || [];

        let html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="gridCardsPuntosModal">`;

        puntos.forEach(p => {
            const idStr = String(p.id_punto);
            const estaMarcado = seleccionadosPrevios.includes(idStr);
            const otroDia = buscarDiaAsignado(idStr, diaActualModal);

            const badgeFuera = (p.fuera_de_servicio == 1)
                ? `<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-[10px] font-bold ml-1 border border-red-200"><i class="fas fa-power-off mr-1"></i>FUERA DE SERVICIO</span>`
                : '';

            const badgeOtroDia = otroDia
                ? `<span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded text-[10px] font-bold ml-1 border border-amber-300" title="Ya estaba marcado para ${otroDia}"><i class="fas fa-calendar-day mr-1"></i>En ${otroDia.toUpperCase()}</span>`
                : '';

            const badgeZona = `<span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold border border-indigo-200"><i class="fas fa-map-marker-alt mr-1"></i>${Prog.util.escapeHtml(p.zona)}</span>`;

            const checkedAttr = estaMarcado ? 'checked' : '';
            const borderStyle = estaMarcado ? 'border-indigo-500 ring-2 ring-indigo-200 bg-indigo-50/30' : 'border-gray-200 bg-white';

            const textoBusqueda = (
                (p.nombre_punto || '') + ' ' +
                (p.nombre_cliente || '') + ' ' +
                (p.direccion || '') + ' ' +
                (p.zona || '') + ' ' +
                (p.device_id || '')
            ).toLowerCase();

            html += `
                <div class="card-punto-modal p-3.5 border ${borderStyle} rounded-xl hover:shadow-md transition flex flex-col justify-between"
                        id="card_punto_${p.id_punto}"
                        data-id="${p.id_punto}"
                        data-texto="${Prog.util.escapeHtml(textoBusqueda)}">
                    <div>
                        <div class="flex items-start space-x-3">
                            <input type="checkbox"
                                class="mt-1 w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 check-punto-modal cursor-pointer shadow-sm"
                                value="${p.id_punto}"
                                ${checkedAttr}
                                onchange="onToggleCheckPuntoModal(this)">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between flex-wrap gap-1 mb-1">
                                    <div class="font-bold text-gray-900 text-sm truncate">
                                        ${Prog.util.escapeHtml(p.nombre_punto)} ${badgeFuera} ${badgeOtroDia}
                                    </div>
                                    ${badgeZona}
                                </div>
                                <div class="text-xs text-gray-600 space-y-1">
                                    <div class="truncate"><i class="fas fa-user-tie text-gray-400 w-4"></i> ${Prog.util.escapeHtml(p.nombre_cliente)}</div>
                                    <div class="truncate"><i class="fas fa-location-dot text-red-400 w-4"></i> ${Prog.util.escapeHtml(p.direccion || 'Sin dirección')}</div>
                                    <div class="truncate"><i class="fas fa-microchip text-gray-400 w-4"></i> ${Prog.util.escapeHtml(p.device_id || 'S/N')} (${Prog.util.escapeHtml(p.tipo_maquina || 'N/A')})</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-gray-100 flex justify-between items-center text-[11px] text-gray-500">
                        <span><i class="fas fa-calendar-check text-green-500 mr-1"></i> Último: ${p.fecha_ultima_visita ? p.fecha_ultima_visita.split(' ')[0] : 'Sin registro'}</span>
                        <span class="px-2 py-0.5 bg-orange-50 text-orange-600 rounded-full font-semibold"><i class="fas fa-clock mr-1"></i> ${p.dias_sin_visita || '0'} días</span>
                    </div>
                </div>`;
        });

        html += `</div>`;
        modalContenido.innerHTML = html;
        actualizarContadorModal();
    }

    /**
     * Evento al marcar/desmarcar un punto en el modal
     */
    function onToggleCheckPuntoModal(cb) {
        const card = document.getElementById(`card_punto_${cb.value}`);
        if (card) {
            if (cb.checked) {
                card.classList.add('border-indigo-500', 'ring-2', 'ring-indigo-200', 'bg-indigo-50/30');
                card.classList.remove('border-gray-200', 'bg-white');
            } else {
                card.classList.remove('border-indigo-500', 'ring-2', 'ring-indigo-200', 'bg-indigo-50/30');
                card.classList.add('border-gray-200', 'bg-white');
            }
        }
        actualizarContadorModal();
    }

    /**
     * Filtra las tarjetas de puntos en el modal en tiempo real
     */
    function filtrarPuntosModal() {
        const buscador = document.getElementById('buscadorPuntosModal');
        if (!buscador) return;
        const termino = buscador.value.trim().toLowerCase();
        const cards = Prog.util.qsa('.card-punto-modal');

        cards.forEach(card => {
            const texto = card.dataset.texto || '';
            if (!termino || texto.includes(termino)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    /**
     * Marca o desmarca todos los puntos visibles en el modal
     */
    function marcarTodosPuntosModal(marcar) {
        const cards = Prog.util.qsa('.card-punto-modal');
        cards.forEach(card => {
            if (card.style.display !== 'none') {
                const cb = card.querySelector('.check-punto-modal');
                if (cb) {
                    cb.checked = marcar;
                    onToggleCheckPuntoModal(cb);
                }
            }
        });
        actualizarContadorModal();
    }

    /**
     * Actualiza el contador de puntos marcados en el pie del modal
     */
    function actualizarContadorModal() {
        const checks = Prog.util.qsa('.check-punto-modal:checked');
        const contador = document.getElementById('modalDiaContador');
        if (contador) contador.textContent = checks.length;
    }

    /**
     * Guarda la selección de puntos confirmada en el modal para el día actual
     */
    function guardarPuntosDiaModal() {
        if (!diaActualModal) return;

        const checks = Prog.util.qsa('.check-punto-modal:checked');
        const ids = checks.map(cb => cb.value);

        if (ids.length === 0) {
            const seguro = confirm(`No has seleccionado ningún punto para ${diaActualModal.toUpperCase()}.\n\n¿Deseas dejar este día sin puntos asignados?`);
            if (!seguro) return;
        }

        // Desasignar estos puntos de cualquier otro día para evitar duplicidades
        ids.forEach(id => {
            DIAS.forEach(d => {
                if (d !== diaActualModal && puntosSeleccionadosPorDia[d].includes(id)) {
                    puntosSeleccionadosPorDia[d] = puntosSeleccionadosPorDia[d].filter(x => x !== id);
                    sincronizarDiaUI(d);
                }
            });
        });

        // Guardar en el estado del día actual
        puntosSeleccionadosPorDia[diaActualModal] = ids;
        sincronizarDiaUI(diaActualModal);
        cerrarModalPuntosDia();
    }

    /**
     * Sincroniza la tarjeta del día con los puntos elegidos e inyecta los hidden inputs
     */
    function sincronizarDiaUI(dia) {
        const ids = puntosSeleccionadosPorDia[dia] || [];
        const countSpan = document.getElementById(`count_puntos_${dia}`);
        if (countSpan) countSpan.textContent = ids.length;

        // Inyectar inputs ocultos para enviarse en el formulario
        const hiddenCont = document.getElementById(`hidden_puntos_dia_${dia}`);
        if (hiddenCont) {
            hiddenCont.innerHTML = '';
            ids.forEach(id => {
                hiddenCont.appendChild(Prog.util.el('input', {
                    type: 'hidden',
                    name: `puntos_dia[${dia}][]`,
                    value: id
                }));
            });
        }

        // Actualizar badge visual
        updatePreview(dia);
    }

    /**
     * Cierra el modal de selección de puntos
     */
    function cerrarModalPuntosDia() {
        const modal = document.getElementById('modalSeleccionPuntosDia');
        if (modal) modal.classList.add('hidden');
        diaActualModal = null;
        puntosCargadosEnModal = [];
    }

    /**
     * Valida que al menos un día tenga técnico, zonas y puntos antes de enviar
     */
    function validarYConfirmarEnvio(e) {
        let hayConfiguracionValida = false;
        const errores = [];
        let totalPuntos = 0;

        DIAS.forEach(dia => {
            const selectTecnico = Prog.util.qs(`select[name="tecnico_${dia}"]`);
            const tecnicoVal = selectTecnico ? selectTecnico.value : '';
            const zonas = Prog.util.qsa(`.checkbox-zona-${dia}:checked`);
            const puntos = puntosSeleccionadosPorDia[dia] || [];

            if (tecnicoVal && zonas.length > 0) {
                if (puntos.length > 0) {
                    hayConfiguracionValida = true;
                    totalPuntos += puntos.length;
                } else {
                    const diaNombre = dia.charAt(0).toUpperCase() + dia.slice(1);
                    errores.push(`${diaNombre}: Asignaste técnico y zonas, pero falta pulsar "Elegir Puntos" para seleccionar las máquinas a visitar.`);
                }
            } else if (tecnicoVal && zonas.length === 0) {
                const diaNombre = dia.charAt(0).toUpperCase() + dia.slice(1);
                errores.push(`${diaNombre}: Tiene técnico seleccionado pero ninguna zona marcada.`);
            }
        });

        if (!hayConfiguracionValida) {
            e.preventDefault();
            alert('⚠️ Debes configurar al menos un día con técnico, zonas y puntos seleccionados para continuar.');
            return;
        }

        if (errores.length > 0) {
            e.preventDefault();
            alert('⚠️ Por favor completa los siguientes días antes de continuar:\n\n• ' + errores.join('\n• '));
            return;
        }

        let resumen = 'Se generará la previsualización con la siguiente asignación:\n\n';
        DIAS.forEach(dia => {
            const selectTecnico = Prog.util.qs(`select[name="tecnico_${dia}"]`);
            const zonas = Prog.util.qsa(`.checkbox-zona-${dia}:checked`);
            const puntos = puntosSeleccionadosPorDia[dia] || [];

            if (selectTecnico && selectTecnico.value && puntos.length > 0) {
                const diaNombre = dia.charAt(0).toUpperCase() + dia.slice(1);
                const tecnicoNombre = selectTecnico.options[selectTecnico.selectedIndex]?.text;
                const zonasNombres = zonas.map(cb => cb.parentElement.querySelector('strong')?.textContent.trim()).filter(Boolean).join(' + ');

                resumen += `✓ ${diaNombre}: ${tecnicoNombre} → ${puntos.length} punto(s) en [${zonasNombres}]\n`;
            }
        });

        resumen += `\nTotal de servicios a programar: ${totalPuntos}\n\n¿Deseas continuar a la Previsualización de Rutas?`;

        if (!confirm(resumen)) {
            e.preventDefault();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('formCalendario');
        if (form) form.addEventListener('submit', validarYConfirmarEnvio);
    });

    // Exponer funciones globales para eventos en HTML
    window.toggleZonas = toggleZonas;
    window.updatePreview = updatePreview;
    window.abrirModalPuntosDia = abrirModalPuntosDia;
    window.cerrarModalPuntosDia = cerrarModalPuntosDia;
    window.guardarPuntosDiaModal = guardarPuntosDiaModal;
    window.filtrarPuntosModal = filtrarPuntosModal;
    window.marcarTodosPuntosModal = marcarTodosPuntosModal;
    window.onToggleCheckPuntoModal = onToggleCheckPuntoModal;
})();
