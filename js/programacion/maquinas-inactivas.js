/**
 * js/programacion/maquinas-inactivas.js
 * Máquinas fuera de servicio: selección individual, restauración,
 * y disparo del modal de aledaños en UNA o VARIAS zonas a la vez.
 */
(function () {
    let puntosSeleccionadosInactivos = [];

    function toggleMaquinaSeleccion(idPunto) {
        const checkbox = Prog.util.qs(`#maquina_${idPunto} .check-maquina-inactiva`);
        if (checkbox.checked) {
            if (!puntosSeleccionadosInactivos.includes(idPunto)) {
                puntosSeleccionadosInactivos.push(idPunto);
            }
        } else {
            puntosSeleccionadosInactivos = puntosSeleccionadosInactivos.filter(id => id !== idPunto);
        }
        actualizarContadorSeleccion();
        actualizarBarraAccionMultiZona();
    }

    function restaurarMaquinaIndividual(deviceId) {
        if (!confirm(`Vas a restaurar la maquina ${deviceId} a estado Operativo.\n\nEsto significa que NO se programará en esta ocasión. ¿Continuar?`)) {
            return;
        }
        Prog.util.postJSON(`${Prog.config.baseUrl}index.php?pagina=programacionCrear&accion=restaurarMaquinaIndividual`, { device_id: deviceId })
            .then(resp => {
                if (resp.status) {
                    alert('Maquina restaurada a Operativo.');
                    location.reload();
                } else {
                    alert(resp.msg || 'No se pudo restaurar la maquina.');
                }
            })
            .catch(() => alert('Error de conexión al restaurar la maquina.'));
    }

    function actualizarContadorSeleccion() {
        const total = Prog.util.qsa('.check-maquina-inactiva:checked').length;
        let badge = document.getElementById('badgeSeleccionInactivas');
        if (!badge) {
            const titulo = document.querySelector('#seccionMaquinasInactivas h2');
            if (titulo) {
                badge = document.createElement('span');
                badge.id = 'badgeSeleccionInactivas';
                badge.className = 'ml-2 text-sm font-normal';
                titulo.appendChild(badge);
            }
        }
        if (badge) {
            badge.innerHTML = total > 0
                ? `<span class="bg-red-600 text-white px-2 py-1 rounded-full text-xs">${total} seleccionada${total > 1 ? 's' : ''}</span>`
                : '';
        }
    }

    /**
     * Muestra/oculta la barra flotante de acción cuando hay máquinas
     * marcadas en 2 o más zonas distintas: permite abrir el modal
     * de aledaños combinando TODAS esas zonas de una sola vez.
     */
    function actualizarBarraAccionMultiZona() {
        const marcados = Prog.util.qsa('.check-maquina-inactiva:checked');
        let barra = document.getElementById('barraAccionMultiZona');

        if (marcados.length < 2) {
            if (barra) barra.remove();
            return;
        }

        const zonas = [...new Set(marcados.map(cb => cb.dataset.zona).filter(Boolean))];

        if (!barra) {
            barra = Prog.util.el('div', {
                id: 'barraAccionMultiZona',
                class: 'fixed bottom-4 left-1/2 -translate-x-1/2 z-40 bg-gray-900 text-white rounded-full shadow-2xl px-5 py-3 flex items-center gap-4'
            });
            document.body.appendChild(barra);
        }

        barra.innerHTML = `
            <span class="text-sm font-semibold">
                <i class="fas fa-check-circle text-green-400 mr-1"></i>
                ${marcados.length} máquinas · ${zonas.length} zona${zonas.length > 1 ? 's' : ''}
            </span>
            <button type="button" onclick="buscarAledaniosZonasSeleccionadas()"
                class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2 rounded-full">
                <i class="fas fa-search-location mr-1"></i> Ver aledaños de zonas seleccionadas
            </button>`;
    }

    /** Botón por grupo de zona: abre aledaños de UNA sola zona. */
    function buscarAledaniosZona(zona) {
        Prog.modalAledanios.lanzar(0, zona);
    }

    /** Botón de la barra flotante: combina las zonas de todas las máquinas marcadas. */
    function buscarAledaniosZonasSeleccionadas() {
        const marcados = Prog.util.qsa('.check-maquina-inactiva:checked');
        if (marcados.length === 0) {
            alert('Por favor marca al menos una máquina fuera de servicio para tomar sus zonas.');
            return;
        }
        const zonas = [...new Set(marcados.map(cb => cb.dataset.zona).filter(Boolean))];
        if (zonas.length === 0) {
            alert('Las máquinas seleccionadas no tienen zona asignada.');
            return;
        }
        Prog.modalAledanios.lanzar(0, zonas.join(','));
    }

    window.toggleMaquinaSeleccion = toggleMaquinaSeleccion;
    window.restaurarMaquinaIndividual = restaurarMaquinaIndividual;
    window.buscarAledaniosZona = buscarAledaniosZona;
    window.buscarAledaniosZonasSeleccionadas = buscarAledaniosZonasSeleccionadas;
})();
