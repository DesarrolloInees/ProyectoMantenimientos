<div class="row justify-content-center">
    <div class="col-md-10"> 
        <div class="card card-info card-outline shadow-lg"> 
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-map-marked-alt mr-2"></i>
                    Simulación y Actualización de Zonas
                </h3>
            </div>
            
            <div class="card-body">
                <form id="formImportarZonas" enctype="multipart/form-data">
                    <div class="form-group text-center p-4 border-dashed rounded" style="border: 2px dashed #17a2b8; background-color: #f4f8f9;">
                        <label for="archivo_excel_zonas" style="cursor: pointer; width: 100%;">
                            <i class="fas fa-file-excel fa-3x text-info mb-2"></i>
                            <h6 class="text-muted">Cargar Excel para ver Simulación</h6>
                            <span id="nombre_archivo_zonas" class="badge badge-info d-none mt-2 p-2"></span>
                        </label>
                        <input type="file" class="d-none" id="archivo_excel_zonas" name="archivo_excel" accept=".xlsx, .xls" required onchange="mostrarNombreZonas()">
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="button" id="btnGenerarSimulacion" class="btn btn-info font-weight-bold shadow">
                            <i class="fas fa-eye mr-2"></i> CARGAR VISTA PREVIA
                        </button>
                    </div>
                </form>

                <div id="seccionSimulacion" class="mt-4" style="display:none;">
                    <hr>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Modo Simulación:</strong> Aún no se ha guardado nada en la base de datos. Selecciona los puntos que deseas actualizar.
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="m-0 text-secondary">Puntos Encontrados</h5>
                        <button type="button" id="btnProcesarSeleccionados" class="btn btn-success shadow font-weight-bold">
                            <i class="fas fa-save mr-1"></i> ACTUALIZAR SELECCIONADOS
                        </button>
                    </div>

                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover text-center">
                            <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th width="5%">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="checkAll">
                                            <label class="custom-control-label" for="checkAll"></label>
                                        </div>
                                    </th>
                                    <th>Device ID</th>
                                    <th>Punto</th>
                                    <th>Zona Actual</th>
                                    <th>Nueva Zona (Excel)</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaSimulacion">
                                </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function mostrarNombreZonas() {
        let input = document.getElementById('archivo_excel_zonas');
        let label = document.getElementById('nombre_archivo_zonas');
        if (input.files && input.files.length > 0) {
            label.textContent = input.files[0].name;
            label.classList.remove('d-none');
        }
    }

    $(document).ready(function() {
        
        // 1. CARGAR EXCEL Y GENERAR SIMULACIÓN
        $('#btnGenerarSimulacion').on('click', function() {
            if ($('#archivo_excel_zonas').get(0).files.length === 0) {
                alert("Por favor selecciona un archivo de Excel.");
                return;
            }

            let btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Analizando...');
            $('#seccionSimulacion').hide();
            
            var formData = new FormData(document.getElementById('formImportarZonas'));
            formData.append('accion', 'generarSimulacion');

            $.ajax({
                url: 'index.php?pagina=importarMunicipios', // Ajusta el nombre de la página según tu router
                type: 'POST',
                data: formData,
                contentType: false, 
                processData: false, 
                dataType: 'json',
                success: function(resp) {
                    btn.prop('disabled', false).html('<i class="fas fa-eye mr-2"></i> CARGAR VISTA PREVIA');
                    
                    if (resp.exito) {
                        let html = '';
                        if(resp.datos.length === 0) {
                            html = '<tr><td colspan="5">No se encontraron cruces con la base de datos o no hay zonas válidas.</td></tr>';
                        } else {
                            resp.datos.forEach(function(item) {
                                // Resaltar si la zona va a cambiar realmente
                                let claseColor = item.es_diferente ? 'bg-warning-light' : '';
                                let checkAttr = item.es_diferente ? 'checked' : ''; // Autoseleccionar los que sí cambian

                                html += `
                                    <tr class="${claseColor}">
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input chk-actualizar" 
                                                    id="chk_${item.id_punto}" 
                                                    data-id="${item.id_punto}" 
                                                    data-zona="${item.zona_nueva}"
                                                    ${checkAttr}>
                                                <label class="custom-control-label" for="chk_${item.id_punto}"></label>
                                            </div>
                                        </td>
                                        <td>${item.device_id}</td>
                                        <td class="text-left"><small>${item.nombre_punto}</small></td>
                                        <td class="text-danger font-weight-bold">${item.zona_actual}</td>
                                        <td class="text-success font-weight-bold">${item.zona_nueva}</td>
                                    </tr>
                                `;
                            });
                        }
                        
                        $('#cuerpoTablaSimulacion').html(html);
                        $('#seccionSimulacion').fadeIn();
                        
                        // Lógica del "Seleccionar Todos"
                        $('#checkAll').prop('checked', false).off('change').on('change', function(){
                            $('.chk-actualizar').prop('checked', $(this).is(':checked'));
                        });

                    } else {
                        alert("Error: " + resp.error);
                    }
                },
                error: function(xhr, status, error) {
                    btn.prop('disabled', false).html('<i class="fas fa-eye mr-2"></i> CARGAR VISTA PREVIA');
                    alert("Error en la conexión: " + error);
                }
            });
        });

        // 2. EJECUTAR EL UPDATE DE LOS SELECCIONADOS
        $('#btnProcesarSeleccionados').on('click', function() {
            let seleccionados = [];
            
            // Recolectar datos de los checkboxes marcados
            $('.chk-actualizar:checked').each(function() {
                seleccionados.push({
                    id_punto: $(this).data('id'),
                    zona_nueva: $(this).data('zona')
                });
            });

            if (seleccionados.length === 0) {
                alert("No has seleccionado ningún punto para actualizar.");
                return;
            }

            if(!confirm(`¿Estás seguro de actualizar la zona a ${seleccionados.length} puntos?`)) return;

            let btn = $(this);
            let textoOriginal = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

            $.ajax({
                url: 'index.php?pagina=importarMunicipios',
                type: 'POST',
                data: {
                    accion: 'ejecutarActualizacion',
                    datos_seleccionados: JSON.stringify(seleccionados)
                },
                dataType: 'json',
                success: function(resp) {
                    btn.prop('disabled', false).html(textoOriginal);
                    if (resp.exito) {
                        alert("¡Éxito! " + resp.mensaje);
                        // Ocultamos la simulación tras el éxito
                        $('#seccionSimulacion').fadeOut(); 
                        $('#archivo_excel_zonas').val('');
                        $('#nombre_archivo_zonas').addClass('d-none');
                    } else {
                        alert("Error al actualizar: " + resp.error);
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html(textoOriginal);
                    alert("Ocurrió un error al intentar guardar.");
                }
            });
        });

    });
</script>

<style>
    .bg-warning-light { background-color: #fff3cd !important; }
</style>