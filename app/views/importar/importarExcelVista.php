<style>
    /* Esto hace que la cabecera de la tabla se quede fija al hacer scroll */
.table-fixed-head thead th {
    position: sticky;
    top: 0;
    background-color: #e9ecef; /* Color gris claro para tapar el contenido al pasar por debajo */
    z-index: 1;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
}

</style>


<div class="row justify-content-center">
    <div class="col-md-10">
        
        <div class="card card-primary card-outline shadow-lg">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-excel mr-2 text-success"></i>
                    Importación Inteligente C2D
                </h3>
            </div>
            
            <div class="card-body">
                
                <div class="alert alert-info alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <h5><i class="icon fas fa-info"></i> ¿Cómo funciona?</h5>
                    <ul class="mb-0 small">
                        <li>Sube el archivo <b>.xlsx</b>. El sistema procesará en lotes para evitar bloqueos.</li>
                        <li><b>Existentes:</b> Se actualiza su ubicación (traslados automáticos).</li>
                        <li><b>Nuevos:</b> Se crean automáticamente Cliente, Punto y Máquina.</li>
                        <li><b>Fantasmas:</b> Si una máquina/punto NO está en este Excel, se desactiva.</li>
                    </ul>
                </div>

                <form id="formImportar" enctype="multipart/form-data">
                    <div class="form-group text-center p-5 border-dashed rounded" style="border: 2px dashed #ced4da; background-color: #f8f9fa;">
                        <label for="archivo_excel" style="cursor: pointer; width: 100%;">
                            <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                            <h5 class="text-muted">Arrastra tu archivo aquí</h5>
                            <span id="nombre_archivo" class="badge badge-secondary p-2 mt-2 d-none">Ningún archivo seleccionado</span>
                        </label>
                        <input type="file" class="d-none" id="archivo_excel" name="archivo_excel" accept=".xlsx, .xls" required onchange="mostrarNombreArchivo()">
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg px-5 font-weight-bold shadow">
                            <i class="fas fa-play mr-2"></i> INICIAR PROCESO
                        </button>
                    </div>
                </form>

                <div id="seccionProgreso" class="mt-4" style="display:none;">
                    <h5 class="text-center font-weight-bold text-dark">Procesando... Por favor no cierres esta ventana</h5>
                    <div class="progress" style="height: 25px;">
                        <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%; font-weight:bold;">0%</div>
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
            label.classList.replace('badge-secondary', 'badge-primary');
        }
    }

    $(document).ready(function() {
        
        let totalFilas = 0;
        let loteTamano = 200; 
        let fechaInicio = '';
        let statsGlobales = { insertados: 0, actualizados: 0, errores: 0 };
        let listaSimulacionGlobal = []; 
        let modoActual = 'simular'; // Empezamos en simulación

        $('#formImportar').on('submit', function(e) {
            e.preventDefault();
            if ($('#archivo_excel').get(0).files.length === 0) {
                alert("Selecciona un archivo primero.");
                return;
            }

            // Reiniciar UI
            modoActual = 'simular';
            listaSimulacionGlobal = [];
            $('#resultadosFinales').hide().html('');
            $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-search fa-spin"></i> Analizando...');
            $('#seccionProgreso').fadeIn();
            
            var formData = new FormData(this);
            var now = new Date();
            // Formato YYYY-MM-DD HH:MM:SS
            fechaInicio = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0') + ' ' + String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');

            $.ajax({
                url: 'index.php?pagina=importarExcel&accion=subirArchivo',
                type: 'POST',
                data: formData,
                contentType: false, processData: false, dataType: 'json',
                success: function(resp) {
                    if (resp.exito) {
                        totalFilas = resp.total_filas;
                        $('#textoProgreso').text("Archivo cargado. Iniciando simulación...");
                        procesarSiguienteLote(2); 
                    } else {
                        mostrarError(resp.error);
                    }
                },
                error: function(xhr) { mostrarError("Error de conexión al subir: " + xhr.responseText); }
            });
        });

        function procesarSiguienteLote(inicio) {
            let porcentaje = Math.round(((inicio) / totalFilas) * 100);
            if (porcentaje > 100) porcentaje = 100;
            $('#barraProgreso').css('width', porcentaje + '%').text(porcentaje + '%');
            
            let textoFase = modoActual === 'simular' ? "Analizando" : "Importando";
            $('#textoProgreso').text(`${textoFase} filas ${inicio} a ${inicio + loteTamano}...`);

            if (inicio > totalFilas) {
                finalizarProceso();
                return;
            }

            // Recolectar IDs aprobados SI estamos en modo importar
            let idsAprobados = [];
            if (modoActual === 'importar') {
                $('.check-aprobar:checked').each(function() {
                    idsAprobados.push($(this).val());
                });
            }

            $.ajax({
                url: 'index.php?pagina=importarExcel&accion=procesarLote',
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
                        } else if (modoActual === 'importar') {
                            statsGlobales.insertados += resp.stats.insertados;
                            statsGlobales.actualizados += resp.stats.actualizados;
                            statsGlobales.errores += resp.stats.errores;
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
            $('#textoProgreso').text("Finalizando fase actual y buscando bajas...");
            
            // Recolectamos todos los device_id que leímos del Excel para enviarlos al backend
            let devicesLeidos = listaSimulacionGlobal.map(item => item.device);

            $.ajax({
                url: 'index.php?pagina=importarExcel&accion=finalizarImportacion',
                type: 'POST',
                data: { 
                    fecha_inicio: fechaInicio, 
                    modo: modoActual,
                    devices_excel: JSON.stringify(devicesLeidos) // <-- Enviamos la lista
                },
                dataType: 'json',
                success: function(resp) {
                    $('#seccionProgreso').hide();
                    
                    if (modoActual === 'simular') {
                        // Agregamos los fantasmas a nuestra lista global para mostrarlos
                        if (resp.fantasmas && resp.fantasmas.length > 0) {
                            resp.fantasmas.forEach(fantasma => {
                                listaSimulacionGlobal.push({
                                    device: fantasma.device_id,
                                    cliente: fantasma.nombre_cliente,
                                    punto: fantasma.nombre_punto,
                                    accion: "<span class='badge badge-danger'><i class='fas fa-power-off mr-1'></i>DESACTIVAR (BAJA)</span>",
                                    estado: 'BAJA'
                                });
                            });
                        }
                        dibujarTablaSimulacion();
                    } else {
                        dibujarResultadoFinal(resp.bajas);
                    }
                }
            });
        }

        function dibujarTablaSimulacion() {
            $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-play mr-2"></i> REINICIAR');
            
            // Calculamos estadísticas
            let cantNuevos = listaSimulacionGlobal.filter(i => i.estado === 'NUEVO').length;
            let cantActualizados = listaSimulacionGlobal.filter(i => i.estado === 'ACTUALIZAR').length;
            let cantBajas = listaSimulacionGlobal.filter(i => i.estado === 'BAJA').length; // <-- Nuevo contador

            let tablaHTML = `
                <div class="card mt-4 border-info shadow-lg" id="cardSimulacion">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-clipboard-check mr-2"></i> Revisión Previa (${listaSimulacionGlobal.length} registros)</h5>
                    </div>
                    
                    <div class="card-body bg-light pb-2">
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white"><i class="fas fa-search text-info"></i></span>
                                    </div>
                                    <input type="text" id="buscadorSimulacion" class="form-control form-control-lg border-left-0" placeholder="Buscar ID, Cliente..." style="box-shadow: none;">
                                </div>
                            </div>
                            <div class="col-md-8 text-right">
                                <div class="btn-group shadow-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary font-weight-bold btn-filtro active" data-filtro="TODOS">
                                        Todos <span class="badge badge-secondary ml-1">${listaSimulacionGlobal.length}</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-success font-weight-bold btn-filtro" data-filtro="NUEVO">
                                        Nuevos <span class="badge badge-success ml-1">${cantNuevos}</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary font-weight-bold btn-filtro" data-filtro="ACTUALIZAR">
                                        Mover <span class="badge badge-primary ml-1">${cantActualizados}</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger font-weight-bold btn-filtro" data-filtro="BAJA">
                                        Bajas <span class="badge badge-danger ml-1">${cantBajas}</span>
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
                                    <th class="text-center" style="width: 60px;">Subir</th>
                                    <th style="width: 170px;">Acción Sugerida</th>
                                    <th>Device ID</th>
                                    <th>Cliente</th>
                                    <th>Punto Destino</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaSimulacion">
                                ${listaSimulacionGlobal.map(item => {
                                    let htmlCliente = item.cliente;
                                    let htmlPunto = item.punto;
                                    let checkboxHtml = '';

                                    // Lógica visual por estado
                                    if (item.estado === 'ACTUALIZAR') {
                                        checkboxHtml = `<input type="checkbox" class="check-aprobar cursor-pointer" value="${item.device}" checked style="transform: scale(1.5);">`;
                                        if (item.cliente_antiguo && item.cliente_antiguo !== item.cliente) {
                                            htmlCliente = `<del class="text-danger small">${item.cliente_antiguo}</del><br><span class="text-success font-weight-bold"><i class="fas fa-arrow-right mx-1"></i>${item.cliente}</span>`;
                                        } else {
                                            htmlCliente = `<span class="text-muted">${item.cliente} <small>(Sin cambios)</small></span>`;
                                        }

                                        if (item.punto_antiguo && item.punto_antiguo !== item.punto) {
                                            htmlPunto = `<del class="text-danger small">${item.punto_antiguo}</del><br><span class="text-success font-weight-bold"><i class="fas fa-arrow-right mx-1"></i>${item.punto}</span>`;
                                        } else {
                                            htmlPunto = `<span class="text-muted">${item.punto} <small>(Sin cambios)</small></span>`;
                                        }
                                    } else if (item.estado === 'NUEVO') {
                                        checkboxHtml = `<input type="checkbox" class="check-aprobar cursor-pointer" value="${item.device}" checked style="transform: scale(1.5);">`;
                                        htmlCliente = `<span class="text-success font-weight-bold">${item.cliente}</span>`;
                                        htmlPunto = `<span class="text-success font-weight-bold">${item.punto}</span>`;
                                    } else if (item.estado === 'BAJA') {
                                        // Las Bajas NO tienen checkbox porque se hacen solas. Mostramos un ícono.
                                        checkboxHtml = `<i class="fas fa-ban text-danger" title="Se dará de baja automáticamente"></i>`;
                                        htmlCliente = `<span class="text-danger">${item.cliente}</span>`;
                                        htmlPunto = `<span class="text-danger">${item.punto}</span>`;
                                    }

                                    return `
                                    <tr class="fila-simulacion" data-estado="${item.estado}">
                                        <td class="text-center align-middle bg-white">${checkboxHtml}</td>
                                        <td class="align-middle">${item.accion}</td>
                                        <td class="align-middle font-weight-bold text-dark buscador-texto">${item.device}</td>
                                        <td class="align-middle buscador-texto">${htmlCliente}</td>
                                        <td class="align-middle buscador-texto">${htmlPunto}</td>
                                    </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                        
                        <div id="mensajeSinResultados" class="text-center p-4 d-none">
                            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron registros con esa búsqueda.</h5>
                        </div>
                    </div>
                    
                    <div class="card-footer text-center bg-white p-4" style="border-top: 2px dashed #17a2b8;">
                        <h4 class="text-dark mb-3">¿Todo listo, bro?</h4>
                        <button type="button" id="btnConfirmarImportacion" class="btn btn-success btn-lg px-5 font-weight-bold shadow-lg" style="border-radius: 30px;">
                            <i class="fas fa-rocket mr-2"></i> CONFIRMAR E IMPORTAR SELECCIONADOS
                        </button>
                        <p class="text-muted small mt-2">Nota: Las máquinas marcadas como "BAJA" se desactivarán automáticamente.</p>
                    </div>
                </div>
            `;
            
            $('#resultadosFinales').html(tablaHTML).fadeIn();

            // Lógica del buscador y filtros
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
                let msj = `Vas a inyectar ${seleccionados} registros. Las ${cantBajas} máquinas faltantes se desactivarán. ¿Continuar?`;
                
                if (seleccionados === 0 && cantBajas === 0) {
                    alert("No hay nada que actualizar ni desactivar.");
                    return;
                }

                if (confirm(msj)) {
                    $('#cardSimulacion').slideUp();
                    $('#seccionProgreso').fadeIn();
                    $('#barraProgreso').css('width', '0%').text('0%');
                    $('#textoProgreso').text("Iniciando inyección de datos...");
                    
                    modoActual = 'importar'; 
                    statsGlobales = { insertados: 0, actualizados: 0, errores: 0 }; 
                    
                    procesarSiguienteLote(2); 
                }
            });
        }

        function dibujarResultadoFinal(bajas) {
            let html = `
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-box bg-gradient-success elevation-2">
                            <span class="info-box-icon"><i class="fas fa-plus-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Nuevos Creados</span>
                                <span class="info-box-number" style="font-size: 1.5rem;">${statsGlobales.insertados}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-gradient-info elevation-2">
                            <span class="info-box-icon"><i class="fas fa-sync-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Verificados/Movidos</span>
                                <span class="info-box-number" style="font-size: 1.5rem;">${statsGlobales.actualizados}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-gradient-secondary elevation-2">
                            <span class="info-box-icon"><i class="fas fa-power-off"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Desactivados (Bajas)</span>
                                <span class="info-box-number">Máq: ${bajas.maquinas} <small>| Ptos: ${bajas.puntos}</small></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-5 mb-3">
                    <div class="alert alert-success shadow-sm">
                        <h4><i class="icon fas fa-check-circle"></i> ¡Importación Realizada!</h4>
                        Los registros marcados fueron guardados en la base de datos.
                    </div>
                    <a href="index.php?pagina=importarExcel" class="btn btn-primary btn-lg shadow px-5">
                        <i class="fas fa-file-excel mr-2"></i> Subir otro archivo
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