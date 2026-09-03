<style>
.table-fixed-head thead th {
    position: sticky;
    top: 0;
    background-color: #e9ecef;
    z-index: 1;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
}
</style>

<div class="row justify-content-center">
    <div class="col-md-10">
        
        <div class="card card-danger card-outline shadow-lg">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-excel mr-2 text-danger"></i>
                    Importar Estado de Maquinas
                </h3>
            </div>
            
            <div class="card-body">
                
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Como funciona?</h5>
                    <ul class="mb-0 small">
                        <li>Sube un archivo <b>.xlsx</b> con los <b>Device IDs</b> en la <b>columna A</b>.</li>
                        <li>El sistema buscara esas maquinas y las marcara como <b>FUERA DE SERVICIO</b>.</li>
                        <li>Las maquinas marcadas apareceran en la <b>Programacion de Rutas</b> para programar servicios aledaños.</li>
                        <li>Si un punto no se programa, su maquina volvera automaticamente a estado <b>Operativo</b>.</li>
                    </ul>
                </div>

                <form id="formImportarEstado" enctype="multipart/form-data">
                    <div class="form-group text-center p-5 border-dashed rounded" style="border: 2px dashed #dc3545; background-color: #fff5f5;">
                        <label for="archivo_excel" style="cursor: pointer; width: 100%;">
                            <i class="fas fa-cloud-upload-alt fa-4x text-danger mb-3"></i>
                            <h5 class="text-muted">Arrastra tu archivo aqui</h5>
                            <small class="text-muted">Solo columna A con Device IDs</small>
                            <span id="nombre_archivo" class="badge badge-secondary p-2 mt-2 d-none">Ningun archivo seleccionado</span>
                        </label>
                        <input type="file" class="d-none" id="archivo_excel" name="archivo_excel" accept=".xlsx, .xls" required onchange="mostrarNombreArchivo()">
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-danger btn-lg px-5 font-weight-bold shadow">
                            <i class="fas fa-play mr-2"></i> INICIAR PROCESO
                        </button>
                    </div>
                </form>

                <div id="seccionProgreso" class="mt-4" style="display:none;">
                    <h5 class="text-center font-weight-bold text-dark">Procesando... Por favor no cierres esta ventana</h5>
                    <div class="progress" style="height: 25px;">
                        <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%; font-weight:bold;">0%</div>
                    </div>
                    <div class="text-center mt-2">
                        <span id="textoProgreso" class="text-muted">Iniciando carga...</span>
                    </div>
                </div>

                <div id="resultadosFinales" class="mt-4" style="display:none;"></div>

            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    function mostrarNombreArchivo() {
        var input = document.getElementById('archivo_excel');
        var label = document.getElementById('nombre_archivo');
        if (input.files && input.files.length > 0) {
            label.textContent = input.files[0].name;
            label.classList.remove('d-none');
            label.classList.add('d-inline-block');
            label.classList.replace('badge-secondary', 'badge-danger');
        }
    }

    $(document).ready(function() {
        
        let totalFilas = 0;
        let loteTamano = 200;
        let modoActual = 'simular';
        let listaSimulacionGlobal = [];

        $('#formImportarEstado').on('submit', function(e) {
            e.preventDefault();
            if ($('#archivo_excel').get(0).files.length === 0) {
                alert("Selecciona un archivo primero.");
                return;
            }

            modoActual = 'simular';
            listaSimulacionGlobal = [];
            $('#resultadosFinales').hide().html('');
            $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-search fa-spin"></i> Analizando...');
            $('#seccionProgreso').fadeIn();
            
            var formData = new FormData(this);

            $.ajax({
                url: 'index.php?pagina=importarEstadoMaquina&accion=subirArchivo',
                type: 'POST',
                data: formData,
                contentType: false, processData: false, dataType: 'json',
                success: function(resp) {
                    if (resp.exito) {
                        totalFilas = resp.total_filas;
                        $('#textoProgreso').text("Archivo cargado. Iniciando simulacion...");
                        procesarSiguienteLote(2); 
                    } else {
                        mostrarError(resp.error);
                    }
                },
                error: function(xhr) { mostrarError("Error de conexion al subir: " + xhr.responseText); }
            });
        });

        function procesarSiguienteLote(inicio) {
            let porcentaje = Math.round(((inicio) / totalFilas) * 100);
            if (porcentaje > 100) porcentaje = 100;
            $('#barraProgreso').css('width', porcentaje + '%').text(porcentaje + '%');
            
            let textoFase = modoActual === 'simular' ? "Analizando" : "Marcando";
            $('#textoProgreso').text(`${textoFase} filas ${inicio} a ${inicio + loteTamano}...`);

            if (inicio > totalFilas) {
                finalizarProceso();
                return;
            }

            let idsAprobados = [];
            if (modoActual === 'importar') {
                $('.check-aprobar:checked').each(function() {
                    idsAprobados.push($(this).val());
                });
            }

            $.ajax({
                url: 'index.php?pagina=importarEstadoMaquina&accion=procesarLote',
                type: 'POST',
                data: { 
                    inicio: inicio, 
                    cantidad: loteTamano,
                    modo: modoActual,
                    aprobados: JSON.stringify(idsAprobados)
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp.exito) {
                        if (modoActual === 'simular' && resp.detalles) {
                            listaSimulacionGlobal.push(...resp.detalles);
                        }
                        if (resp.detener === true) {
                            $('#barraProgreso').css('width', '100%').text('100%');
                            finalizarProceso();
                        } else {
                            procesarSiguienteLote(inicio + loteTamano);
                        }
                    } else {
                        $('#textoProgreso').text("Error: " + resp.error).addClass('text-danger');
                    }
                },
                error: function() {
                    setTimeout(() => procesarSiguienteLote(inicio), 3000);
                }
            });
        }

        function finalizarProceso() {
            $('#textoProgreso').text("Finalizando...");
            
            $.ajax({
                url: 'index.php?pagina=importarEstadoMaquina&accion=finalizarImportacion',
                type: 'POST',
                data: { modo: modoActual },
                dataType: 'json',
                success: function(resp) {
                    $('#seccionProgreso').hide();
                    
                    if (modoActual === 'simular') {
                        dibujarTablaSimulacion();
                    } else {
                        dibujarResultadoFinal();
                    }
                }
            });
        }

        function dibujarTablaSimulacion() {
            $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-play mr-2"></i> REINICIAR');
            
            let cantMarcar = listaSimulacionGlobal.filter(i => i.estado === 'MARCAR_INACTIVO').length;
            let cantYaInactivas = listaSimulacionGlobal.filter(i => i.estado === 'YA_INACTIVA').length;
            let cantNoEncontradas = listaSimulacionGlobal.filter(i => i.estado === 'NO_ENCONTRADO').length;

            let tablaHTML = `
                <div class="card mt-4 border-danger shadow-lg" id="cardSimulacion">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-clipboard-check mr-2"></i> Revision Previa (${listaSimulacionGlobal.length} registros)</h5>
                    </div>
                    
                    <div class="card-body bg-light pb-2">
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white"><i class="fas fa-search text-danger"></i></span>
                                    </div>
                                    <input type="text" id="buscadorSimulacion" class="form-control form-control-lg border-left-0" placeholder="Buscar Device ID, Cliente..." style="box-shadow: none;">
                                </div>
                            </div>
                            <div class="col-md-8 text-right">
                                <div class="btn-group shadow-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary font-weight-bold btn-filtro active" data-filtro="TODOS">
                                        Todos <span class="badge badge-secondary ml-1">${listaSimulacionGlobal.length}</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger font-weight-bold btn-filtro" data-filtro="MARCAR_INACTIVO">
                                        A Marcar <span class="badge badge-danger ml-1">${cantMarcar}</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary font-weight-bold btn-filtro" data-filtro="YA_INACTIVA">
                                        Ya Inactivas <span class="badge badge-secondary ml-1">${cantYaInactivas}</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning font-weight-bold btn-filtro" data-filtro="NO_ENCONTRADO">
                                        No Encontradas <span class="badge badge-warning ml-1">${cantNoEncontradas}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2 text-right">
                            <button type="button" class="btn btn-sm btn-link text-muted font-weight-bold" id="btnMarcarVisibles"><i class="fas fa-check-square mr-1"></i>Marcar Visibles</button>
                            <button type="button" class="btn btn-sm btn-link text-muted font-weight-bold" id="btnDesmarcarVisibles"><i class="far fa-square mr-1"></i>Desmarcar Visibles</button>
                        </div>
                    </div>

                    <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-sm table-striped table-hover mb-0 table-fixed-head">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-center" style="width: 60px;">Marcar</th>
                                    <th style="width: 180px;">Accion</th>
                                    <th>Device ID</th>
                                    <th>Cliente</th>
                                    <th>Punto</th>
                                    <th>Zona</th>
                                    <th>Tipo Maquina</th>
                                    <th>Estado Actual</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaSimulacion">
                                ${listaSimulacionGlobal.map(item => {
                                    let checkboxHtml = '';
                                    
                                    if (item.estado === 'MARCAR_INACTIVO') {
                                        checkboxHtml = `<input type="checkbox" class="check-aprobar cursor-pointer" value="${item.device}" checked style="transform: scale(1.5);">`;
                                    } else if (item.estado === 'NO_ENCONTRADO') {
                                        checkboxHtml = `<i class="fas fa-exclamation-triangle text-warning" title="Device ID no encontrado en la base de datos"></i>`;
                                    } else {
                                        checkboxHtml = `<i class="fas fa-check-circle text-secondary" title="Ya esta marcada como fuera de servicio"></i>`;
                                    }

                                    return `
                                    <tr class="fila-simulacion" data-estado="${item.estado}">
                                        <td class="text-center align-middle bg-white">${checkboxHtml}</td>
                                        <td class="align-middle">${item.accion}</td>
                                        <td class="align-middle font-weight-bold text-dark buscador-texto">${item.device}</td>
                                        <td class="align-middle buscador-texto">${item.cliente}</td>
                                        <td class="align-middle buscador-texto">${item.punto}</td>
                                        <td class="align-middle"><span class="badge badge-info">${item.zona || 'N/A'}</span></td>
                                        <td class="align-middle buscador-texto">${item.tipo_maquina}</td>
                                        <td class="align-middle">${item.estado_actual === 'Operativo' ? '<span class="badge badge-success">Operativo</span>' : '<span class="badge badge-secondary">Inactivo</span>'}</td>
                                    </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                        
                        <div id="mensajeSinResultados" class="text-center p-4 d-none">
                            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron registros con esa busqueda.</h5>
                        </div>
                    </div>
                    
                    <div class="card-footer text-center bg-white p-4" style="border-top: 2px dashed #dc3545;">
                        <h4 class="text-dark mb-3">Confirmar marcado de maquinas?</h4>
                        <button type="button" id="btnConfirmarImportacion" class="btn btn-danger btn-lg px-5 font-weight-bold shadow-lg" style="border-radius: 30px;">
                            <i class="fas fa-power-off mr-2"></i> CONFIRMAR Y MARCAR SELECCIONADAS
                        </button>
                        <p class="text-muted small mt-2">Las maquinas seleccionadas quedaran como "Fuera de Servicio".</p>
                    </div>
                </div>
            `;
            
            $('#resultadosFinales').html(tablaHTML).fadeIn();

            function aplicarFiltros() {
                let textoBusqueda = $('#buscadorSimulacion').val().toLowerCase();
                let filtroEstado = $('.btn-filtro.active').data('filtro');
                let encontrados = 0;

                $('.fila-simulacion').each(function() {
                    let fila = $(this);
                    let estadoFila = fila.data('estado');
                    let textoFila = fila.find('.buscador-texto').text().toLowerCase();

                    let cumpleBusqueda = textoFila.includes(textoBusqueda);
                    let cumpleEstado = (filtroEstado === 'TODOS' || estadoFila === filtroEstado);

                    if (cumpleBusqueda && cumpleEstado) {
                        fila.show();
                        encontrados++;
                    } else {
                        fila.hide();
                    }
                });

                if (encontrados === 0 && listaSimulacionGlobal.length > 0) {
                    $('#mensajeSinResultados').removeClass('d-none');
                } else {
                    $('#mensajeSinResultados').addClass('d-none');
                }
            }

            $('#buscadorSimulacion').on('keyup', aplicarFiltros);

            $('.btn-filtro').on('click', function() {
                $('.btn-filtro').removeClass('active');
                $(this).addClass('active');
                aplicarFiltros();
            });

            $('#btnMarcarVisibles').click(() => $('.fila-simulacion:visible .check-aprobar').prop('checked', true));
            $('#btnDesmarcarVisibles').click(() => $('.fila-simulacion:visible .check-aprobar').prop('checked', false));

            $('#btnConfirmarImportacion').click(function() {
                let seleccionados = $('.check-aprobar:checked').length;
                
                if (seleccionados === 0) {
                    alert("No hay nada que marcar.");
                    return;
                }

                let msj = `Vas a marcar ${seleccionados} maquina(s) como "Fuera de Servicio".\n\nLas maquinas no seleccionadas o no encontradas no se modificaran.\n\nContinuar?`;

                if (confirm(msj)) {
                    $('#cardSimulacion').slideUp();
                    $('#seccionProgreso').fadeIn();
                    $('#barraProgreso').css('width', '0%').text('0%');
                    $('#textoProgreso').text("Iniciando marcado...");
                    
                    modoActual = 'importar'; 
                    
                    procesarSiguienteLote(2); 
                }
            });
        }

        function dibujarResultadoFinal() {
            let html = `
                <div class="text-center mt-5 mb-3">
                    <div class="alert alert-success shadow-sm">
                        <h4><i class="icon fas fa-check-circle"></i> ¡Marcado Realizado!</h4>
                        Las maquinas seleccionadas fueron marcadas como "Fuera de Servicio".
                        <br>
                        <small class="text-muted">Ahora ve a <b>Programacion de Rutas</b> para ver los puntos afectados.</small>
                    </div>
                    <a href="index.php?pagina=importarEstadoMaquina" class="btn btn-primary btn-lg shadow px-5">
                        <i class="fas fa-file-excel mr-2"></i> Subir otro archivo
                    </a>
                    <a href="index.php?pagina=programacionCrear" class="btn btn-success btn-lg shadow px-5 ml-2">
                        <i class="fas fa-calendar-alt mr-2"></i> Ir a Programacion
                    </a>
                </div>
            `;
            $('#resultadosFinales').html(html).fadeIn();
        }

        function mostrarError(msg) {
            $('#seccionProgreso').hide();
            $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-play mr-2"></i> REINTENTAR');
            alert(msg);
        }
    });
</script>
