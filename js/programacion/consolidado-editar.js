/**
 * js/programacion/consolidado-editar.js
 * Modal "Editar Servicio Programado" del Consolidado de Programación.
 *
 * La vista debe definir antes de core.js:
 *   window.ProgConsolidadoData, window.tecnicosConsolidado,
 *   window.modalidadesConsolidado, window.BASE_URL y window.ProgConfig.
 */
(function () {
    'use strict';

    var URL_BASE = (typeof window.BASE_URL === 'string' && window.BASE_URL) ? window.BASE_URL : '';

    var estado = {
        idOrden: 0,
        idPuntoOriginal: 0,
        guardando: false,
        cargandoClientes: false
    };

    function qs(id) {
        return document.getElementById(id);
    }

    function urlAccion(accion) {
        return URL_BASE + 'index.php?pagina=programacionConsolidado&accion=' + accion;
    }

    async function postJSON(url, datos) {
        var body = new URLSearchParams();
        Object.entries(datos || {}).forEach(function (par) {
            body.append(par[0], par[1] === null || par[1] === undefined ? '' : par[1]);
        });

        var resp = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        });

        var texto = await resp.text();
        try {
            return JSON.parse(texto);
        } catch (e) {
            console.error('Respuesta no JSON del servidor:', texto);
            throw new Error('El servidor devolvió una respuesta no válida.');
        }
    }

    function agregarOpcion(select, valor, texto, deshabilitada) {
        var opt = document.createElement('option');
        opt.value = valor === null || valor === undefined ? '' : String(valor);
        opt.textContent = texto;
        if (deshabilitada) opt.disabled = true;
        select.appendChild(opt);
        return opt;
    }

    function refrescarSelect2(el) {
        if (!el || !window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;
        var $el = jQuery(el);
        if ($el.data('select2')) $el.trigger('change.select2');
    }

    function inicializarSelect2() {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;
        ['edit_id_cliente', 'edit_id_punto'].forEach(function (id) {
            var el = qs(id);
            if (!el) return;
            var $el = jQuery(el);
            if ($el.data('select2')) return;
            $el.select2({ width: '100%', dropdownParent: jQuery('#modalEditarOrden') });
        });
    }

    function setTexto(id, valor) {
        var el = qs(id);
        if (!el) return;
        el.textContent = (valor === null || valor === undefined || valor === '') ? '—' : String(valor);
    }

    function ocultarInfoPunto() {
        var caja = qs('puntoInfo');
        if (caja) caja.classList.add('hidden');
    }

    function pintarClientes(clientes, idSeleccionado, nombreRespaldo) {
        var select = qs('edit_id_cliente');
        if (!select) return;

        select.innerHTML = '';
        agregarOpcion(select, '', '-- Seleccionar cliente --');

        var ids = {};
        (clientes || []).forEach(function (c) {
            ids[String(c.id_cliente)] = true;
            agregarOpcion(select, c.id_cliente, c.nombre_cliente);
        });

        if (idSeleccionado && !ids[String(idSeleccionado)]) {
            agregarOpcion(select, idSeleccionado, nombreRespaldo || ('Cliente #' + idSeleccionado));
        }

        select.value = idSeleccionado ? String(idSeleccionado) : '';
        refrescarSelect2(select);
    }

    async function cargarClientes(idSeleccionado, nombreRespaldo) {
        var select = qs('edit_id_cliente');
        if (!select) return;

        if (Array.isArray(window.__clientesConsolidado) && window.__clientesConsolidado.length) {
            pintarClientes(window.__clientesConsolidado, idSeleccionado, nombreRespaldo);
            return;
        }

        if (estado.cargandoClientes) return;
        estado.cargandoClientes = true;

        select.innerHTML = '';
        agregarOpcion(select, '', 'Cargando clientes...');

        try {
            var data = await postJSON(urlAccion('listarClientesEditar'), {});
            if (!data || !data.status) {
                throw new Error((data && data.msg) ? data.msg : 'No se pudo cargar la lista de clientes.');
            }
            window.__clientesConsolidado = data.clientes || [];
            pintarClientes(window.__clientesConsolidado, idSeleccionado, nombreRespaldo);
        } catch (error) {
            console.error(error);
            select.innerHTML = '';
            agregarOpcion(select, '', '-- Seleccionar cliente --');
            if (idSeleccionado) {
                agregarOpcion(select, idSeleccionado, nombreRespaldo || ('Cliente #' + idSeleccionado));
            }
            select.value = idSeleccionado ? String(idSeleccionado) : '';
            alert('No se pudo cargar la lista de clientes: ' + error.message);
        } finally {
            estado.cargandoClientes = false;
        }
    }


    function pintarMaquinas(idMaquina, deviceId) {
        var select = qs('edit_id_maquina');
        if (!select) return;

        select.innerHTML = '';
        agregarOpcion(select, '', '-- Sin asignar --');

        if (idMaquina) {
            agregarOpcion(select, idMaquina, 'Device ' + (deviceId || 's/n'));
            select.value = String(idMaquina);
        } else {
            select.value = '';
        }
    }

    function pintarModalidades(idSeleccionada, respaldo) {
        var select = qs('edit_id_modalidad');
        if (!select) return;

        var modalidades = (Array.isArray(window.modalidadesConsolidado) && window.modalidadesConsolidado.length)
            ? window.modalidadesConsolidado
            : (respaldo && respaldo.id_modalidad ? [respaldo] : []);

        select.innerHTML = '';
        agregarOpcion(select, '', '-- Sin asignar --');
        modalidades.forEach(function (m) {
            agregarOpcion(select, m.id_modalidad, m.nombre_modalidad);
        });

        select.value = idSeleccionada ? String(idSeleccionada) : '';
    }

    function actualizarInfoPunto() {
        var select = qs('edit_id_punto');
        var caja = qs('puntoInfo');
        if (!select || !caja) return;

        var op = select.options[select.selectedIndex];
        if (!select.value || !op) {
            ocultarInfoPunto();
            return;
        }

        caja.classList.remove('hidden');
        setTexto('edit_nombre_punto', op.dataset.nombrePunto || op.textContent);
        setTexto('edit_zona_valor', op.dataset.zona);
        setTexto('edit_direccion_valor', op.dataset.direccion);
        setTexto('edit_device_valor', op.dataset.deviceId || 'S/N');

        var idPuntoSeleccionado = parseInt(select.value, 10) || 0;
        var esPuntoOriginal = estado.idPuntoOriginal > 0 && idPuntoSeleccionado === estado.idPuntoOriginal;

        if (esPuntoOriginal) {
            var filaActual = (window.ProgConsolidadoData || []).find(function (f) {
                return String(f.id_orden) === String(estado.idOrden);
            });

            pintarMaquinas(
                filaActual ? filaActual.id_maquina : op.dataset.idMaquina,
                filaActual ? filaActual.device_id : op.dataset.deviceId
            );
            pintarModalidades(filaActual ? filaActual.id_modalidad : op.dataset.idModalidad, null);
        } else {
            pintarMaquinas(op.dataset.idMaquina, op.dataset.deviceId);
            pintarModalidades(op.dataset.idModalidad, {
                id_modalidad: op.dataset.idModalidad,
                nombre_modalidad: op.dataset.nombreModalidad || 'Modalidad del punto'
            });
        }
    }

    function pintarPuntos(puntos, idSeleccionar, idOriginal) {
        var select = qs('edit_id_punto');
        if (!select) return;

        var lista = (puntos || []).slice();

        // Si el punto actual no viene en la lista (p. ej. el id_cliente de la orden
        // no coincide con el dueño del punto), se agrega para no mover la orden sin querer.
        if (idOriginal && !lista.some(function (p) { return String(p.id_punto) === String(idOriginal); })) {
            var filaActual = (window.ProgConsolidadoData || []).find(function (f) {
                return String(f.id_orden) === String(estado.idOrden);
            });

            if (filaActual) {
                lista.unshift({
                    id_punto: filaActual.id_punto,
                    nombre_punto: (filaActual.nombre_punto || 'Punto actual') + ' (actual)',
                    zona: filaActual.zona,
                    direccion: filaActual.direccion,
                    id_maquina: filaActual.id_maquina,
                    device_id: filaActual.device_id,
                    activo_operativo: filaActual.activo_operativo,
                    id_modalidad: filaActual.id_modalidad,
                    nombre_modalidad: '',
                    ya_programado: 0
                });
            }
        }

        select.innerHTML = '';
        agregarOpcion(select, '', '-- Seleccionar punto --');

        var primeraDisponible = '';
        var seleccionExiste = false;

        lista.forEach(function (p) {
            var esOriginal = idOriginal && String(p.id_punto) === String(idOriginal);
            var yaProgramado = parseInt(p.ya_programado, 10) === 1 && !esOriginal;

            var texto = p.nombre_punto + (yaProgramado ? ' — ya programado' : '');
            var op = agregarOpcion(select, p.id_punto, texto, yaProgramado);

            op.dataset.nombrePunto = p.nombre_punto || '';
            op.dataset.zona = p.zona || '';
            op.dataset.direccion = p.direccion || '';
            op.dataset.deviceId = p.device_id || '';
            op.dataset.activoOperativo = p.activo_operativo || '1';
            op.dataset.idMaquina = p.id_maquina || '';
            op.dataset.idModalidad = p.id_modalidad || '';
            op.dataset.nombreModalidad = p.nombre_modalidad || '';

            if (!yaProgramado && !primeraDisponible) primeraDisponible = String(p.id_punto);
            if (idSeleccionar && String(p.id_punto) === String(idSeleccionar) && !yaProgramado) {
                seleccionExiste = true;
            }
        });

        var destino = primeraDisponible;
        if (idSeleccionar && seleccionExiste) {
            destino = String(idSeleccionar);
        } else if (idOriginal) {
            var opOriginal = select.querySelector('option[value="' + idOriginal + '"]');
            if (opOriginal && !opOriginal.disabled) destino = String(idOriginal);
        }

        select.value = destino || '';
        refrescarSelect2(select);
        actualizarInfoPunto();
    }

    async function cargarPuntosCliente(idSeleccionar) {
        var selectCliente = qs('edit_id_cliente');
        var selectPunto = qs('edit_id_punto');
        if (!selectCliente || !selectPunto) return;

        var idCliente = parseInt(selectCliente.value, 10) || 0;

        if (idCliente <= 0) {
            selectPunto.innerHTML = '';
            agregarOpcion(selectPunto, '', '-- Primero seleccione un cliente --');
            selectPunto.disabled = false;
            refrescarSelect2(selectPunto);
            ocultarInfoPunto();
            return;
        }

        selectPunto.disabled = true;
        selectPunto.innerHTML = '';
        agregarOpcion(selectPunto, '', 'Cargando puntos...');

        try {
            var data = await postJSON(urlAccion('obtenerPuntosCliente'), {
                id_cliente: idCliente,
                id_punto_actual: estado.idPuntoOriginal,
                id_orden: estado.idOrden
            });

            if (!data || !data.status) {
                throw new Error((data && data.msg) ? data.msg : 'No se pudieron cargar los puntos.');
            }

            if (Array.isArray(data.modalidades) && data.modalidades.length) {
                window.modalidadesConsolidado = data.modalidades;
            }

            var filaActual = (window.ProgConsolidadoData || []).find(function (f) {
                return String(f.id_orden) === String(estado.idOrden);
            });
            var mismoCliente = filaActual && String(filaActual.id_cliente) === String(idCliente);

            pintarPuntos(data.puntos || [], idSeleccionar, mismoCliente ? estado.idPuntoOriginal : 0);
        } catch (error) {
            console.error(error);
            selectPunto.innerHTML = '';
            agregarOpcion(selectPunto, '', 'Error al cargar los puntos');
            alert('No se pudieron cargar los puntos del cliente: ' + error.message);
        } finally {
            selectPunto.disabled = false;
        }
    }

    function actualizarFilaConsolidado(datos) {
        if (!datos || !datos.id_orden) return;

        var id = String(datos.id_orden);
        var lista = window.ProgConsolidadoData || [];
        var indice = lista.findIndex(function (f) { return String(f.id_orden) === id; });

        if (indice >= 0) {
            lista[indice] = Object.assign({}, lista[indice], datos);
        } else {
            lista.push(datos);
        }
        window.ProgConsolidadoData = lista;

        var fila = document.getElementById('fila_orden_' + id);
        if (!fila) return;

        var celdas = fila.children;

        if (celdas[0]) celdas[0].textContent = datos.fecha_visita || '';

        if (celdas[1]) {
            var spanRuta = celdas[1].querySelector('span');
            if (spanRuta) spanRuta.textContent = datos.codigo_ruta || 'S/R';
            else celdas[1].textContent = datos.codigo_ruta || 'S/R';
        }

        if (celdas[2]) celdas[2].textContent = datos.nombre_tecnico || 'Sin Técnico';
        if (celdas[3]) celdas[3].textContent = datos.zona || '';
        if (celdas[4]) celdas[4].textContent = datos.nombre_cliente || '';
        if (celdas[5]) celdas[5].textContent = datos.nombre_punto || '';
        if (celdas[6]) celdas[6].textContent = datos.device_id || 'S/N';

        if (celdas[7]) {
            if (String(datos.activo_operativo) === '0') {
                celdas[7].innerHTML = '<span class="bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded" '
                    + 'title="La máquina sigue marcada como fuera de servicio; no se ha restaurado">'
                    + '<i class="fas fa-power-off mr-1"></i> Aún Fuera de Servicio</span>';
                fila.classList.add('bg-red-50');
            } else {
                celdas[7].innerHTML = '<span class="text-xs text-gray-400">Operativa</span>';
                fila.classList.remove('bg-red-50');
            }
        }
    }

    async function guardarOrdenEditada() {
        if (estado.guardando) return;

        var btn = document.querySelector('button[onclick="guardarOrdenEditada()"]');
        var htmlBoton = btn ? btn.innerHTML : '';

        var campoOrden = qs('edit_id_orden');
        var campoFecha = qs('edit_fecha_visita');
        var campoTecnico = qs('edit_id_tecnico');
        var campoCliente = qs('edit_id_cliente');
        var campoPunto = qs('edit_id_punto');
        var campoMaquina = qs('edit_id_maquina');
        var campoModalidad = qs('edit_id_modalidad');

        var idOrden = campoOrden ? (parseInt(campoOrden.value, 10) || 0) : 0;
        var fecha = campoFecha ? campoFecha.value : '';
        var idTecnico = campoTecnico ? campoTecnico.value : '';
        var idCliente = campoCliente ? campoCliente.value : '';
        var idPunto = campoPunto ? campoPunto.value : '';
        var idMaquina = campoMaquina ? campoMaquina.value : '';
        var idModalidad = campoModalidad ? campoModalidad.value : '';

        if (!idOrden) return alert('No se identificó la orden que se va a editar.');
        if (!fecha) return alert('Seleccione la fecha de visita.');
        if (!idTecnico) return alert('Seleccione un técnico.');
        if (!idCliente) return alert('Seleccione un cliente.');
        if (!idPunto) return alert('Seleccione un punto.');
        if (campoPunto && campoPunto.disabled) return alert('Espere mientras se cargan los puntos del cliente.');

        estado.guardando = true;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';
        }

        try {
            var data = await postJSON(urlAccion('actualizarOrden'), {
                id_orden: idOrden,
                id_punto: idPunto,
                id_tecnico: idTecnico,
                id_cliente: idCliente,
                id_maquina: idMaquina,
                id_modalidad: idModalidad,
                fecha_visita: fecha
            });

            if (!data || !data.status) {
                alert((data && data.msg) ? data.msg : 'No se pudo actualizar la orden.');
                return;
            }

            if (data.datos) {
                actualizarFilaConsolidado(data.datos);
            } else {
                window.location.reload();
                return;
            }

            var mensaje = data.msg || 'Orden actualizada correctamente.';
            cerrarModalEditarOrden();
            alert(mensaje);
        } catch (error) {
            console.error(error);
            alert('Error al guardar la orden: ' + error.message);
        } finally {
            estado.guardando = false;
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = htmlBoton;
            }
        }
    }

    function abrirModalEditarOrden(idOrden) {
        var modal = qs('modalEditarOrden');
        if (!modal) return;

        var id = parseInt(idOrden, 10) || 0;
        var fila = (window.ProgConsolidadoData || []).find(function (f) {
            return String(f.id_orden) === String(id);
        }) || null;

        estado.idOrden = id;
        estado.idPuntoOriginal = fila ? (parseInt(fila.id_punto, 10) || 0) : 0;

        if (qs('edit_id_orden')) qs('edit_id_orden').value = id ? String(id) : '';
        if (qs('edit_id_punto_original')) {
            qs('edit_id_punto_original').value = estado.idPuntoOriginal ? String(estado.idPuntoOriginal) : '';
        }
        if (qs('edit_fecha_visita')) {
            qs('edit_fecha_visita').value = (fila && fila.fecha_visita) ? String(fila.fecha_visita).slice(0, 10) : '';
        }

        var selectTecnicos = qs('edit_id_tecnico');
        if (selectTecnicos) {
            var tecnicos = window.tecnicosConsolidado || [];
            selectTecnicos.innerHTML = '';
            agregarOpcion(selectTecnicos, '', '-- Seleccionar técnico --');
            tecnicos.forEach(function (t) {
                agregarOpcion(selectTecnicos, t.id_tecnico, t.nombre_tecnico);
            });
            selectTecnicos.value = (fila && fila.id_tecnico) ? String(fila.id_tecnico) : '';
        }

        var selectPunto = qs('edit_id_punto');
        if (selectPunto) {
            selectPunto.innerHTML = '';
            agregarOpcion(selectPunto, '', '-- Primero seleccione un cliente --');
            selectPunto.disabled = false;
        }

        pintarMaquinas(fila ? fila.id_maquina : '', fila ? fila.device_id : '');
        pintarModalidades(fila ? fila.id_modalidad : '', null);

        if (fila) {
            var caja = qs('puntoInfo');
            if (caja) caja.classList.remove('hidden');
            setTexto('edit_nombre_punto', fila.nombre_punto);
            setTexto('edit_zona_valor', fila.zona);
            setTexto('edit_direccion_valor', fila.direccion);
            setTexto('edit_device_valor', fila.device_id);
        } else {
            ocultarInfoPunto();
        }

        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        cargarClientes(fila ? fila.id_cliente : 0, fila ? fila.nombre_cliente : '')
            .then(function () {
                var selectCliente = qs('edit_id_cliente');
                var hayCliente = selectCliente && selectCliente.value;
                return cargarPuntosCliente(hayCliente ? estado.idPuntoOriginal : 0);
            })
            .then(inicializarSelect2)
            .catch(function (error) {
                console.error('Error al preparar el modal de edición:', error);
            });
    }

    function cerrarModalEditarOrden() {
        var modal = qs('modalEditarOrden');
        if (!modal) return;

        modal.classList.add('hidden');
        modal.style.display = '';
        document.body.style.overflow = '';

        var form = qs('formEditarOrden');
        if (form) form.reset();

        ['edit_id_maquina', 'edit_id_modalidad'].forEach(function (id) {
            var el = qs(id);
            if (el) el.innerHTML = '';
        });

        var selectPunto = qs('edit_id_punto');
        if (selectPunto) {
            selectPunto.innerHTML = '';
            agregarOpcion(selectPunto, '', '-- Primero seleccione un cliente --');
            selectPunto.disabled = false;
        }

        var selectCliente = qs('edit_id_cliente');
        if (selectCliente) {
            selectCliente.innerHTML = '';
            agregarOpcion(selectCliente, '', '-- Seleccionar cliente --');
        }

        ocultarInfoPunto();

        if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
            jQuery('#edit_id_cliente, #edit_id_punto').val(null).trigger('change.select2');
        }

        estado.idOrden = 0;
        estado.idPuntoOriginal = 0;
    }

    function inicializarEventosModal() {
        var modal = qs('modalEditarOrden');

        if (modal) {
            modal.addEventListener('click', function (evento) {
                if (evento.target === modal) cerrarModalEditarOrden();
            });
        }

        document.addEventListener('keydown', function (evento) {
            var m = qs('modalEditarOrden');
            if (evento.key === 'Escape' && m && !m.classList.contains('hidden')) {
                cerrarModalEditarOrden();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarEventosModal);
    } else {
        inicializarEventosModal();
    }

    // Expuestos para los atributos onclick/onchange de la vista
    window.abrirModalEditarOrden = abrirModalEditarOrden;
    window.cerrarModalEditarOrden = cerrarModalEditarOrden;
    window.guardarOrdenEditada = guardarOrdenEditada;
    window.cargarPuntosCliente = function () { return cargarPuntosCliente(0); };
})();
