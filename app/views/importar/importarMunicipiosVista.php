<div class="row justify-content-center">
    <div class="col-md-8"> 
        <div class="card card-warning card-outline shadow-lg"> 
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-map-marked-alt mr-2"></i>
                    Importar Catálogo de Zonas/Municipios
                </h3>
            </div>
            
            <div class="card-body">
                <form id="formImportarMuni" enctype="multipart/form-data">
                    <div class="form-group text-center p-4 border-dashed rounded" style="border: 2px dashed #ffc107; background-color: #fffcf5;">
                        <label for="archivo_excel_muni" style="cursor: pointer; width: 100%;">
                            <i class="fas fa-file-csv fa-3x text-warning mb-2"></i>
                            <h6 class="text-muted">Archivo Excel de Zonas</h6>
                            <span id="nombre_archivo_muni" class="badge badge-secondary d-none"></span>
                        </label>
                        <input type="file" class="d-none" id="archivo_excel_muni" name="archivo_excel" accept=".xlsx, .xls" required onchange="mostrarNombreMuni()">
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="button" id="btnIniciarImportacion" class="btn btn-warning font-weight-bold shadow">
                            <i class="fas fa-upload mr-2"></i> CARGAR ZONAS
                        </button>
                    </div>
                </form>

                <div id="seccionProgresoMuni" class="mt-4" style="display:none;">
                    <div class="progress" style="height: 20px;">
                        <div id="barraProgresoMuni" class="progress-bar bg-warning progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
                    </div>
                    <p id="textoProgresoMuni" class="text-center small text-muted mt-1">Iniciando...</p>
                </div>

                <div id="resultadosFinalesMuni" class="mt-4" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function mostrarNombreMuni() {
        var input = document.getElementById('archivo_excel_muni');
        var label = document.getElementById('nombre_archivo_muni');
        if (input.files && input.files.length > 0) {
            label.textContent = input.files[0].name;
            label.classList.remove('d-none');
        }
    }

    $(document).ready(function() {
        let totalFilasMuni = 0;
        let listaMuni = [];

        // CAMBIO: Escuchamos el CLICK del botón, no el submit del form
        $('#btnIniciarImportacion').on('click', function() {
            
            // Validación manual simple
            if ($('#archivo_excel_muni').get(0).files.length === 0) {
                alert("Por favor selecciona un archivo.");
                return;
            }

            listaMuni = [];
            $('#seccionProgresoMuni').fadeIn();
            $('#resultadosFinalesMuni').hide();
            $(this).prop('disabled', true); // Deshabilitar botón para evitar doble click
            
            // Preparamos los datos manualmente
            var formData = new FormData(document.getElementById('formImportarMuni'));
            
            // TRUCO PARA EL INDEX: Agregamos la acción al POST body
            // Tu index.php prioriza $_POST['accion']
            formData.append('accion', 'subirArchivo');

            $.ajax({
                // URL limpia, solo indicamos la página
                url: 'index.php?pagina=importarMunicipios', 
                type: 'POST',
                data: formData,
                contentType: false, 
                processData: false, 
                dataType: 'json',
                success: function(resp) {
                    if (resp.exito) {
                        totalFilasMuni = resp.total_filas;
                        procesarLoteMuni(2);
                    } else {
                        alert(resp.error);
                        $('#btnIniciarImportacion').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    alert("Error en la conexión: " + error);
                    $('#btnIniciarImportacion').prop('disabled', false);
                }
            });
        });

        function procesarLoteMuni(inicio) {
            let avance = Math.round((inicio / totalFilasMuni) * 100);
            if(avance > 100) avance = 100;
            $('#barraProgresoMuni').css('width', avance + '%').text(avance + '%');

            $.ajax({
                url: 'index.php?pagina=importarMunicipios',
                type: 'POST',
                // Enviamos acción también aquí para cumplir con el Router
                data: { 
                    accion: 'procesarLote', 
                    inicio: inicio, 
                    cantidad: 200 
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp.exito) {
                        if (resp.nuevos) listaMuni.push(...resp.nuevos);

                        if (resp.detener || inicio >= totalFilasMuni) {
                            mostrarResultadosMuni();
                        } else {
                            procesarLoteMuni(inicio + 200);
                        }
                    } else {
                         alert("Error procesando lote: " + resp.error);
                    }
                },
                error: function() {
                    // Reintento simple en caso de fallo de red
                    setTimeout(function(){ procesarLoteMuni(inicio); }, 2000);
                }
            });
        }

        function mostrarResultadosMuni() {
            $('#seccionProgresoMuni').hide();
            $('#barraProgresoMuni').css('width', '0%');
            $('#btnIniciarImportacion').prop('disabled', false);
            
            let filasTabla = listaMuni.map(m => 
                `<tr><td>${m.municipio}</td><td>${m.delegacion}</td></tr>`
            ).join('');

            let html = `
                <div class="alert alert-success">
                    <i class="fas fa-check"></i> Proceso terminado. Se procesaron ${listaMuni.length} registros.
                </div>
                <div style="max-height:300px; overflow-y:auto;">
                    <table class="table table-sm table-bordered table-striped">
                        <thead><tr><th>Zona/Municipio</th><th>Delegación</th></tr></thead>
                        <tbody>${filasTabla}</tbody>
                    </table>
                </div>
            `;
            $('#resultadosFinalesMuni').html(html).fadeIn();
        }
    });
</script>