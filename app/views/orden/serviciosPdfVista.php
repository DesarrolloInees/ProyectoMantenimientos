<?php
$opciones = $opciones ?? [
    'clientes' => [],
    'tecnicos' => [],
    'delegaciones' => [],
    'tipos' => []
];
?>

<div class="w-full bg-white shadow-xl rounded-lg p-6">
    <div class="flex flex-col gap-2 md:flex-row md:justify-between md:items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">📄 Generación de PDFs por Servicio</h2>
            <p class="text-gray-500 text-sm">Filtra, previsualiza y descarga los servicios en un ZIP organizado por delegación y técnico.</p>
        </div>
        <div class="text-sm text-gray-500">
            Máximo por descarga: <strong>1000 servicios</strong>
        </div>
    </div>

    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha inicio</label>
                <input type="date" id="filtroFechaInicio"
                    class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha fin</label>
                <input type="date" id="filtroFechaFin"
                    class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cliente</label>
                <select id="filtroCliente" class="select-filtro w-full">
                    <option value="">Todos los clientes</option>
                    <?php foreach ($opciones['clientes'] as $cliente): ?>
                        <option value="<?= (int) $cliente['id_cliente'] ?>">
                            <?= htmlspecialchars($cliente['nombre_cliente'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Punto</label>
                <select id="filtroPunto" class="select-filtro w-full">
                    <option value="">Todos los puntos</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Técnico</label>
                <select id="filtroTecnico" class="select-filtro w-full">
                    <option value="">Todos los técnicos</option>
                    <?php foreach ($opciones['tecnicos'] as $tecnico): ?>
                        <option value="<?= (int) $tecnico['id_tecnico'] ?>">
                            <?= htmlspecialchars($tecnico['nombre_tecnico'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Delegación</label>
                <select id="filtroDelegacion" class="select-filtro w-full">
                    <option value="">Todas las delegaciones</option>
                    <?php foreach ($opciones['delegaciones'] as $delegacion): ?>
                        <option value="<?= (int) $delegacion['id_delegacion'] ?>">
                            <?= htmlspecialchars($delegacion['nombre_delegacion'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tipo de mantenimiento</label>
                <select id="filtroTipo" class="select-filtro w-full">
                    <option value="">Todos los tipos</option>
                    <?php foreach ($opciones['tipos'] as $tipo): ?>
                        <option value="<?= (int) $tipo['id_tipo_mantenimiento'] ?>">
                            <?= htmlspecialchars($tipo['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="button" id="btnAplicarFiltros"
                    class="flex-1 bg-blue-600 hover:bg-blue-800 text-white font-bold py-2.5 px-4 rounded shadow">
                    <i class="fas fa-filter mr-1"></i> Aplicar filtros
                </button>
                <button type="button" id="btnLimpiarFiltros"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2.5 px-4 rounded shadow">
                    Limpiar
                </button>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mt-5 pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-600 flex items-center gap-3">
                <span id="textoSeleccion">Sin servicios seleccionados</span>
                <button type="button" id="btnLimpiarSeleccion"
                    class="hidden text-blue-600 hover:text-blue-800 underline font-bold">
                    Limpiar selección
                </button>
            </div>
            <button type="button" id="btnDescargarZip"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow flex items-center justify-center gap-2">
                <i class="fas fa-file-archive"></i>
                <span id="textoBotonZip">Descargar ZIP con filtros</span>
            </button>
        </div>

        <div id="mensajeFiltros" class="hidden mt-3 p-3 rounded text-sm font-bold text-center"></div>
    </div>

    <table id="tablaPdf" class="min-w-full text-sm display stripe hover" style="width:100%">
        <thead class="bg-gray-800 text-white">
            <tr>
                <th class="py-3 px-4 text-center" style="width:40px">
                    <input type="checkbox" id="chkTodos" class="cursor-pointer" title="Seleccionar todos los resultados">
                </th>
                <th class="py-3 px-4 text-center">ID</th>
                <th class="py-3 px-4 text-center">Remisión</th>
                <th class="py-3 px-4 text-center">Fecha Visita</th>
                <th class="py-3 px-4">Cliente / Punto</th>
                <th class="py-3 px-4">Delegación</th>
                <th class="py-3 px-4">Técnico</th>
                <th class="py-3 px-4 text-center">Acciones</th>
            </tr>
        </thead>
    </table>
</div>

<div id="modalProgreso" class="hidden fixed inset-0 bg-black bg-opacity-60 z-[99999] flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                <i class="fas fa-file-pdf"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">Generando ZIP de servicios</h3>
                <p class="text-xs text-gray-500">No cierres esta ventana mientras se generan los PDFs.</p>
            </div>
        </div>

        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden mb-3">
            <div id="barraProgreso" class="bg-blue-600 h-4 rounded-full transition-all duration-200" style="width: 0%"></div>
        </div>

        <div class="flex justify-between text-sm text-gray-600 mb-4">
            <span id="progresoTexto">Preparando descarga...</span>
            <span id="progresoNumero">0 / 0</span>
        </div>

        <div id="progresoDetalle" class="text-xs text-gray-500 truncate mb-4"></div>

        <div class="flex justify-end gap-2">
            <button type="button" id="btnCancelarZip"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded">
                Cancelar
            </button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
    $(document).ready(function () {
        const endpoint = BASE_URL + 'serviciosPdf';
        const seleccionados = new Set();
        let tokenTrabajo = null;
        let procesando = false;
        let cancelado = false;

        function esc(valor) {
            return $('<div>').text(valor === null || valor === undefined ? '' : String(valor)).html();
        }

        function obtenerFiltros() {
            return {
                fecha_inicio: $('#filtroFechaInicio').val(),
                fecha_fin: $('#filtroFechaFin').val(),
                id_cliente: $('#filtroCliente').val(),
                id_punto: $('#filtroPunto').val(),
                id_tecnico: $('#filtroTecnico').val(),
                id_delegacion: $('#filtroDelegacion').val(),
                id_tipo_mantenimiento: $('#filtroTipo').val()
            };
        }

        function mostrarMensaje(texto, tipo) {
            const mensaje = $('#mensajeFiltros');
            mensaje.removeClass('hidden bg-red-100 text-red-700 bg-green-100 text-green-700 bg-yellow-100 text-yellow-800');

            if (!texto) {
                mensaje.addClass('hidden').text('');
                return;
            }

            const clases = tipo === 'ok'
                ? 'bg-green-100 text-green-700'
                : (tipo === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-700');

            mensaje.addClass(clases).text(texto);
        }

        function actualizarContador() {
            const cantidad = seleccionados.size;

            if (cantidad > 0) {
                $('#textoSeleccion').html('<strong>' + cantidad + '</strong> servicios seleccionados manualmente.');
                $('#textoBotonZip').text('Descargar ZIP (' + cantidad + ' seleccionados)');
                $('#btnLimpiarSeleccion').removeClass('hidden');
            } else {
                $('#textoSeleccion').text('Sin servicios seleccionados. Se descargará todo lo que coincida con los filtros.');
                $('#textoBotonZip').text('Descargar ZIP con filtros');
                $('#btnLimpiarSeleccion').addClass('hidden');
            }
        }

        function cargarPuntos(idCliente, puntoSeleccionado) {
            const select = $('#filtroPunto');
            select.empty().append('<option value="">Cargando puntos...</option>');
            select.trigger('change.select2');

            $.ajax({
                url: endpoint,
                method: 'POST',
                dataType: 'json',
                data: {
                    accion: 'ajaxPuntos',
                    id_cliente: idCliente || ''
                }
            }).done(function (resp) {
                select.empty().append('<option value="">Todos los puntos</option>');

                (resp.data || []).forEach(function (punto) {
                    select.append(
                        $('<option>')
                            .attr('value', punto.id_punto)
                            .text(punto.nombre_punto)
                    );
                });

                if (puntoSeleccionado) {
                    select.val(String(puntoSeleccionado));
                }

                select.trigger('change.select2');
            }).fail(function () {
                select.empty().append('<option value="">Todos los puntos</option>');
                select.trigger('change.select2');
            });
        }

        function limpiarSeleccion() {
            seleccionados.clear();
            $('#chkTodos').prop('checked', false);
            actualizarContador();
        }

        $('#filtroCliente').on('change', function () {
            cargarPuntos($(this).val(), null);
        });

        $('.select-filtro').select2({
            width: '100%',
            placeholder: 'Todos',
            allowClear: true
        });

        const tabla = $('#tablaPdf').DataTable({
            responsive: true,
            ajax: {
                url: endpoint,
                type: 'POST',
                data: function (d) {
                    d.accion = 'ajaxListar';
                    Object.assign(d, obtenerFiltros());
                }
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center align-middle',
                    render: function (data, type, row) {
                        return '<input type="checkbox" class="chk-servicio cursor-pointer" data-id="' + row.id_ordenes_servicio + '">';
                    }
                },
                {
                    data: 'id_ordenes_servicio',
                    className: 'text-center font-bold text-gray-600'
                },
                {
                    data: 'numero_remision',
                    className: 'text-center font-bold text-indigo-600',
                    render: function (data, type, row) {
                        return esc(data);
                    }
                },
                {
                    data: 'fecha_visita',
                    className: 'text-center'
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return '<div class="font-bold text-gray-800">' + esc(row.nombre_cliente || 'SIN CLIENTE') + '</div>' +
                            '<div class="text-xs text-gray-500">' + esc(row.nombre_punto || 'SIN PUNTO') + '</div>';
                    }
                },
                {
                    data: 'nombre_delegacion',
                    className: 'text-sm text-gray-700',
                    render: function (data, type, row) {
                        return esc(data);
                    }
                },
                {
                    data: 'nombre_tecnico',
                    className: 'text-sm text-gray-700',
                    render: function (data, type, row) {
                        return esc(data);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center align-middle',
                    render: function (data, type, row) {
                        const urlPdf = BASE_URL + 'index.php?pagina=pdfServicio&accion=generar&id=' + row.id_ordenes_servicio;
                        const urlEditar = BASE_URL + 'index.php?pagina=servicioEditar&accion=index&id=' + row.id_ordenes_servicio;

                        return '<div class="flex flex-col gap-2">' +
                            '<a href="' + urlPdf + '" target="_blank" class="bg-red-600 hover:bg-red-800 text-white font-bold py-1 px-3 rounded shadow text-xs text-center inline-block">' +
                            '<i class="fas fa-file-pdf"></i> PDF</a>' +
                            '<a href="' + urlEditar + '" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-1 px-3 rounded shadow text-xs text-center inline-block">' +
                            '<i class="fas fa-edit"></i> Completar</a>' +
                            '</div>';
                    }
                }
            ],
            order: [[3, 'desc'], [1, 'desc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            drawCallback: function () {
                $('#tablaPdf .chk-servicio').each(function () {
                    const id = String($(this).data('id'));
                    $(this).prop('checked', seleccionados.has(id));
                });

                if (seleccionados.size === 0) {
                    $('#chkTodos').prop('checked', false);
                }
            }
        });

        $('#tablaPdf tbody').on('change', '.chk-servicio', function () {
            const id = String($(this).data('id'));

            if (this.checked) {
                seleccionados.add(id);
            } else {
                seleccionados.delete(id);
            }

            actualizarContador();
        });

        $('#chkTodos').on('change', function () {
            const marcar = this.checked;

            tabla.rows({ search: 'applied' }).every(function () {
                const id = String(this.data().id_ordenes_servicio);

                if (marcar) {
                    seleccionados.add(id);
                } else {
                    seleccionados.delete(id);
                }
            });

            tabla.draw(false);
            actualizarContador();
        });

        $('#btnLimpiarSeleccion').on('click', function () {
            limpiarSeleccion();
            tabla.draw(false);
        });

        $('#btnAplicarFiltros').on('click', function () {
            const inicio = $('#filtroFechaInicio').val();
            const fin = $('#filtroFechaFin').val();

            if (inicio && fin && inicio > fin) {
                mostrarMensaje('La fecha de inicio no puede ser mayor que la fecha fin.', 'error');
                return;
            }

            mostrarMensaje('', 'ok');
            limpiarSeleccion();
            tabla.ajax.reload();
        });

        $('#btnLimpiarFiltros').on('click', function () {
            $('#filtroFechaInicio').val('');
            $('#filtroFechaFin').val('');
            $('.select-filtro').val('').trigger('change.select2');
            cargarPuntos('', null);
            mostrarMensaje('', 'ok');
            limpiarSeleccion();
            tabla.ajax.reload();
        });

        function actualizarProgreso(procesados, total, detalle) {
            const porcentaje = total > 0 ? Math.min(100, Math.round((procesados / total) * 100)) : 0;
            $('#barraProgreso')
                .removeClass('bg-red-600')
                .addClass('bg-blue-600')
                .css('width', porcentaje + '%');
            $('#progresoNumero').text(procesados + ' / ' + total);
            $('#progresoTexto').text(porcentaje + '% completado');
            $('#progresoDetalle').text(detalle || '');
        }

        function abrirModal() {
            $('#modalProgreso').removeClass('hidden');
            $('#btnCancelarZip').text('Cancelar').prop('disabled', false);
            $('#barraProgreso').removeClass('bg-red-600').addClass('bg-blue-600').css('width', '0%');
            $('#progresoTexto').text('Preparando descarga...');
            $('#progresoNumero').text('0 / 0');
            $('#progresoDetalle').text('');
        }

        function cerrarModal() {
            $('#modalProgreso').addClass('hidden');
        }

        function finalizarTrabajo() {
            procesando = false;
            tokenTrabajo = null;
            cerrarModal();
        }

        function fallarTrabajo(mensaje) {
            procesando = false;
            $('#barraProgreso').removeClass('bg-blue-600').addClass('bg-red-600').css('width', '100%');
            $('#progresoTexto').text('No se pudo completar la descarga');
            $('#progresoDetalle').text(mensaje || 'Intenta de nuevo con menos servicios.');
            $('#btnCancelarZip').text('Cerrar');

            if (tokenTrabajo) {
                $.post(endpoint, {
                    accion: 'cancelarTrabajoZip',
                    token: tokenTrabajo
                });
                tokenTrabajo = null;
            }
        }

        function descargarTrabajo() {
            $('#progresoTexto').text('Comprimiendo ZIP...');
            $('#progresoDetalle').text('La descarga comenzará automáticamente.');

            const urlDescarga = endpoint + '?accion=descargarZipTrabajo&token=' + encodeURIComponent(tokenTrabajo);
            window.location.href = urlDescarga;

            setTimeout(function () {
                finalizarTrabajo();
                mostrarMensaje('ZIP generado correctamente. Revisa las descargas de tu navegador.', 'ok');
            }, 4000);
        }

        function procesarLotes() {
            if (cancelado) {
                return;
            }

            $.ajax({
                url: endpoint,
                method: 'POST',
                dataType: 'json',
                timeout: 0,
                data: {
                    accion: 'procesarLoteZip',
                    token: tokenTrabajo,
                    lote: 3
                }
            }).done(function (resp) {
                if (cancelado) {
                    return;
                }

                if (!resp || resp.status !== 'ok') {
                    fallarTrabajo(resp && resp.msg ? resp.msg : 'Error desconocido al generar los PDFs.');
                    return;
                }

                actualizarProgreso(
                    resp.procesados,
                    resp.total,
                    resp.actual ? 'Generando: ' + resp.actual : 'Generando PDFs...'
                );

                if (resp.completado) {
                    descargarTrabajo();
                    return;
                }

                setTimeout(procesarLotes, 80);
            }).fail(function () {
                if (!cancelado) {
                    fallarTrabajo('Se perdió la conexión con el servidor durante la generación.');
                }
            });
        }

        $('#btnDescargarZip').on('click', function () {
            if (procesando) {
                return;
            }

            const inicio = $('#filtroFechaInicio').val();
            const fin = $('#filtroFechaFin').val();

            if (inicio && fin && inicio > fin) {
                mostrarMensaje('La fecha de inicio no puede ser mayor que la fecha fin.', 'error');
                return;
            }

            const datos = obtenerFiltros();
            datos.accion = 'crearTrabajoZip';

            if (seleccionados.size > 0) {
                datos.ids = Array.from(seleccionados);
            }

            procesando = true;
            cancelado = false;
            tokenTrabajo = null;
            mostrarMensaje('', 'ok');
            abrirModal();

            $.ajax({
                url: endpoint,
                method: 'POST',
                dataType: 'json',
                data: datos
            }).done(function (resp) {
                if (!resp || resp.status !== 'ok') {
                    fallarTrabajo(resp && resp.msg ? resp.msg : 'No se pudo iniciar la descarga.');
                    return;
                }

                tokenTrabajo = resp.token;

                if (resp.advertencia) {
                    mostrarMensaje(resp.advertencia, 'warning');
                }

                actualizarProgreso(0, resp.total, 'Iniciando generación de PDFs...');
                procesarLotes();
            }).fail(function () {
                fallarTrabajo('No se pudo iniciar el trabajo de descarga.');
            });
        });

        $('#btnCancelarZip').on('click', function () {
            if (!procesando) {
                cerrarModal();
                return;
            }

            cancelado = true;

            if (tokenTrabajo) {
                $.post(endpoint, {
                    accion: 'cancelarTrabajoZip',
                    token: tokenTrabajo
                });
            }

            finalizarTrabajo();
            mostrarMensaje('Descarga cancelada.', 'warning');
        });

        cargarPuntos('', null);
        actualizarContador();
    });
</script>
