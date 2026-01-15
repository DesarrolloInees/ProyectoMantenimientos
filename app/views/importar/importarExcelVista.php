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
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
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
        let listaNuevosGlobal = []; // <--- 1. AQUÍ ACUMULAREMOS LOS DATOS

        $('#formImportar').on('submit', function(e) {
            e.preventDefault();
            
            if ($('#archivo_excel').get(0).files.length === 0) {
                alert("Selecciona un archivo primero.");
                return;
            }

            $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cargando...');
            $('#seccionProgreso').fadeIn();
            $('#resultadosFinales').hide().html(''); // Limpiar resultados previos
            listaNuevosGlobal = []; // Resetear lista
            
            var formData = new FormData(this);
            var now = new Date();
            fechaInicio = now.getFullYear() + '-' + 
                            String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(now.getDate()).padStart(2, '0') + ' ' + 
                            String(now.getHours()).padStart(2, '0') + ':' + 
                            String(now.getMinutes()).padStart(2, '0') + ':' + 
                            String(now.getSeconds()).padStart(2, '0');

            $.ajax({
                url: 'index.php?pagina=importarExcel&accion=subirArchivo',
                type: 'POST',
                data: formData,
                contentType: false, processData: false, dataType: 'json',
                success: function(resp) {
                    if (resp.exito) {
                        totalFilas = resp.total_filas;
                        $('#textoProgreso').text("Archivo cargado (" + totalFilas + " filas). Iniciando lotes...");
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
            $('#textoProgreso').text("Procesando filas " + inicio + " a " + (inicio + loteTamano) + "...");

            if (inicio > totalFilas) {
                finalizarProceso();
                return;
            }

            $.ajax({
                url: 'index.php?pagina=importarExcel&accion=procesarLote',
                type: 'POST',
                data: { inicio: inicio, cantidad: loteTamano },
                dataType: 'json',
                success: function(resp) {
                    if (resp.exito) {
                        statsGlobales.insertados += resp.stats.insertados;
                        statsGlobales.actualizados += resp.stats.actualizados;
                        statsGlobales.errores += resp.stats.errores;
                        
                        // <--- 2. GUARDAR LOS NUEVOS EN LA MEMORIA JS
                        if (resp.nuevos && resp.nuevos.length > 0) {
                            listaNuevosGlobal.push(...resp.nuevos);
                        }

                        if (resp.detener === true) {
                            $('#barraProgreso').css('width', '100%').text('100%');
                            $('#textoProgreso').text("Final detectado. Generando reporte...");
                            finalizarProceso();
                        } else {
                            procesarSiguienteLote(inicio + loteTamano);
                        }
                        
                    } else {
                        $('#textoProgreso').text("Error en lote " + inicio + ": " + resp.error).addClass('text-danger');
                    }
                },
                error: function() {
                    console.warn("Fallo de red. Reintentando...");
                    setTimeout(() => procesarSiguienteLote(inicio), 3000);
                }
            });
        }

        function finalizarProceso() {
            $('#textoProgreso').text("Limpiando registros inactivos...");
            
            $.ajax({
                url: 'index.php?pagina=importarExcel&accion=finalizarImportacion',
                type: 'POST',
                data: { fecha_inicio: fechaInicio },
                dataType: 'json',
                success: function(resp) {
                    $('#seccionProgreso').hide();
                    
                    // --- 3. CONSTRUIR LA TABLA DE RESULTADOS ---
                    let tablaHTML = '';
                    if (listaNuevosGlobal.length > 0) {
                        tablaHTML = `
                            <div class="card mt-4 border-success shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list-ul mr-2"></i> Detalle de Nuevas Instalaciones (${listaNuevosGlobal.length})
                                    </h5>
                                </div>
                                
                                <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;">
                                    <table class="table table-sm table-striped table-hover mb-0 table-fixed-head">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="pl-3">Device ID</th>
                                                <th>Cliente</th>
                                                <th>Punto</th>
                                                <th>Ciudad</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${listaNuevosGlobal.map(item => `
                                                <tr>
                                                    <td class="pl-3"><strong>${item.device}</strong></td>
                                                    <td>${item.cliente}</td>
                                                    <td>${item.punto}</td>
                                                    <td>${item.ciudad}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                    }

                    let html = `
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box bg-gradient-success elevation-2">
                                    <span class="info-box-icon"><i class="fas fa-plus-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Nuevos Registrados</span>
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
                                        <span class="info-box-number">Máq: ${resp.bajas.maquinas} <small>| Ptos: ${resp.bajas.puntos}</small></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${tablaHTML}

                        <div class="text-center mt-5 mb-3">
                            <div class="alert alert-success shadow-sm">
                                <h4><i class="icon fas fa-check-circle"></i> ¡Proceso Completado Exitosamente!</h4>
                                La base de datos ha sido sincronizada correctamente.
                            </div>
                            <a href="index.php?pagina=importarExcel" class="btn btn-primary btn-lg shadow px-5">
                                <i class="fas fa-file-excel mr-2"></i> Nueva Importación
                            </a>
                        </div>
                    `;
                    
                    $('#resultadosFinales').html(html).fadeIn();
                    $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-play mr-2"></i> INICIAR PROCESO');
                }
            });
        }

        function mostrarError(msg) {
            $('#seccionProgreso').hide();
            $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-play mr-2"></i> REINTENTAR');
            alert(msg);
        }
    });
</script>