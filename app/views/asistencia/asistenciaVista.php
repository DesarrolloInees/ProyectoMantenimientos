<style>
    .upload-zone {
        border: 2px dashed #a5b4fc;
        background-color: #eef2ff;
        transition: all 0.3s ease;
        border-radius: 15px;
    }

    .upload-zone:hover {
        background-color: #e0e7ff;
        border-color: #6366f1;
        transform: translateY(-2px);
    }

    .btn-gradient-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        color: white;
        border: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
        color: white;
    }

    .btn-excel {
        background: linear-gradient(135deg, #107c41 0%, #18a558 100%);
        color: white;
        border: none;
    }

    .btn-excel:hover {
        background: linear-gradient(135deg, #0c5e31 0%, #107c41 100%);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(16, 124, 65, 0.4);
    }

    .table-premium {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    }

    .table-premium thead th {
        background-color: #1e293b;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 15px;
        border: none;
    }

    .table-premium tbody tr {
        background-color: white;
        transition: all 0.2s;
    }

    .table-premium tbody tr:hover {
        background-color: #f8fafc;
        transform: scale(1.01);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .table-premium td {
        vertical-align: middle !important;
        padding: 12px 15px;
        border-bottom: 1px solid #f1f5f9;
    }

    .badge-custom {
        padding: 8px 12px;
        font-size: 0.85rem;
        border-radius: 8px;
        font-weight: 600;
    }
</style>

<div class="row justify-content-center mt-4">
    <div class="col-md-11">
        <div class="card shadow-lg border-0 rounded-lg">

            <div class="card-header bg-dark text-white p-4" style="border-radius: 10px 10px 0 0;">
                <h3 class="card-title mb-0 d-flex align-items-center font-weight-bold">
                    <i class="fas fa-fingerprint fa-2x mr-3 text-info"></i>
                    Centro de Procesamiento de Asistencias
                </h3>
            </div>

            <div class="card-body p-5">

                <div class="alert alert-info border-left-info shadow-sm mb-4 p-4 rounded"
                    style="border-left: 5px solid #0dcaf0;">
                    <h5 class="font-weight-bold text-dark"><i class="fas fa-magic text-info mr-2"></i> ¿Cómo funciona?
                    </h5>
                    <p class="mb-0 text-muted">Selecciona el <b>Rango de Fechas</b> que deseas generar y sube el archivo
                        CSV del huellero. El sistema listará a <b>todos los empleados</b> (así no hayan marcado), les
                        asignará todos los días del rango, y si un motorizado tiene servicios en la calle sin marcar
                        huella, calculará su entrada y salida con base en la aplicación.</p>
                </div>

                <form id="formHuellero" enctype="multipart/form-data">
                    <!-- 🔥 NUEVO: SELECCIÓN DE RANGO DE FECHAS 🔥 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="font-weight-bold text-dark"><i class="far fa-calendar-alt mr-2"></i>Fecha de
                                Inicio:</label>
                            <input type="date" class="form-control form-control-lg shadow-sm" name="fecha_inicio"
                                id="fecha_inicio" required>
                        </div>
                        <div class="col-md-6">
                            <label class="font-weight-bold text-dark"><i class="far fa-calendar-check mr-2"></i>Fecha de
                                Fin:</label>
                            <input type="date" class="form-control form-control-lg shadow-sm" name="fecha_fin"
                                id="fecha_fin" required>
                        </div>
                    </div>

                    <div class="form-group text-center p-5 upload-zone mb-4">
                        <label for="archivo_huellero" style="cursor: pointer; width: 100%; margin: 0;">
                            <div class="icon-pulse mb-3">
                                <i class="fas fa-cloud-upload-alt fa-5x text-indigo" style="color: #6366f1;"></i>
                            </div>
                            <h4 class="font-weight-bold text-dark">Haz clic aquí para subir el archivo .CSV</h4>
                            <p class="text-muted">O arrastra el archivo hasta esta zona</p>
                            <span id="nombre_archivo" class="badge badge-success badge-custom mt-3 d-none shadow-sm"
                                style="font-size: 1rem;">
                                <i class="fas fa-file-csv mr-2"></i><span></span>
                            </span>
                        </label>
                        <input type="file" class="d-none" id="archivo_huellero" name="archivo_huellero"
                            accept=".csv, .xlsx, .xls" required onchange="mostrarNombreArchivo()">
                    </div>

                    <div class="text-center mt-4 mb-2">
                        <button type="submit"
                            class="btn btn-gradient-primary btn-lg px-5 py-3 rounded-pill font-weight-bold"
                            id="btnProcesar">
                            <i class="fas fa-cogs mr-2"></i> PROCESAR Y CALCULAR EXTRAS
                        </button>
                    </div>
                </form>

                <div id="resultadosContenedor" class="mt-5" style="display: none;">
                    <hr class="mb-4">

                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded shadow-sm mb-4 border-left-success"
                        style="border-left: 5px solid #198754;">
                        <div>
                            <h4 class="text-success font-weight-bold mb-0"><i
                                    class="fas fa-check-circle mr-2"></i>¡Datos procesados con éxito!</h4>
                            <small class="text-muted">Vista previa de los registros limpios. Descarga el Excel para ver
                                las fórmulas.</small>
                        </div>
                        <a href="index.php?pagina=asistencia&accion=descargarExcel"
                            class="btn btn-excel btn-lg px-4 font-weight-bold rounded-pill">
                            <i class="fas fa-file-excel mr-2"></i> DESCARGAR REPORTE
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-premium text-center">
                            <thead>
                                <tr>
                                    <th class="text-left">Empleado</th>
                                    <th>Cargo</th>
                                    <th>Fecha (Día)</th>
                                    <th>H. Entrada</th>
                                    <th>H. Salida</th>
                                    <th>Servicios</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTabla">
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Inicializar fechas con el mes actual por defecto
    $(document).ready(function () {
        const hoy = new Date();
        const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
        const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0).toISOString().split('T')[0];

        $('#fecha_inicio').val(primerDia);
        $('#fecha_fin').val(ultimoDia);
    });

    function mostrarNombreArchivo() {
        var input = document.getElementById('archivo_huellero');
        var label = document.getElementById('nombre_archivo');
        if (input.files && input.files.length > 0) {
            label.querySelector('span').textContent = input.files[0].name;
            label.classList.remove('d-none');
            label.style.transform = 'scale(1.1)';
            setTimeout(() => label.style.transform = 'scale(1)', 200);
        }
    }

    $(document).ready(function () {
        $('#formHuellero').on('submit', function (e) {
            e.preventDefault();
            let btn = $('#btnProcesar');

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> ARMANDO DÍAS PARA TODOS...');
            $('#resultadosContenedor').slideUp();

            var formData = new FormData(this);

            $.ajax({
                url: 'index.php?pagina=asistencia&accion=procesarArchivo',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function (resp) {
                    btn.prop('disabled', false).html('<i class="fas fa-cogs mr-2"></i> PROCESAR NÓMINA EN RANGO SELECCIONADO');

                    if (resp.exito) {
                        // 🔥 MAGIA VISUAL: ACTUALIZAR LOS INPUTS CON LAS FECHAS DEL CSV 🔥
                        if (resp.fecha_inicio_detectada) {
                            $('#fecha_inicio').val(resp.fecha_inicio_detectada);
                        }
                        if (resp.fecha_fin_detectada) {
                            $('#fecha_fin').val(resp.fecha_fin_detectada);
                        }
                        let html = '';
                        resp.datos.forEach(row => {

                            // Visuales
                            let badgeEntrada = row.entrada.includes('Falta')
                                ? `<span class="badge badge-danger badge-custom"><i class="fas fa-times-circle mr-1"></i> F. Ent</span>`
                                : `<span class="badge badge-success badge-custom" style="background-color: #10b981;"><i class="fas fa-sign-in-alt mr-1"></i> ${row.entrada}</span>`;

                            let badgeSalida = row.salida.includes('Falta')
                                ? `<span class="badge badge-danger badge-custom"><i class="fas fa-times-circle mr-1"></i> F. Sal</span>`
                                : `<span class="badge badge-info badge-custom" style="background-color: #0ea5e9; color: white;"><i class="fas fa-sign-out-alt mr-1"></i> ${row.salida}</span>`;

                            let badgeServicios = row.servicios > 0
                                ? `<span class="badge badge-warning badge-custom text-dark"><i class="fas fa-tools mr-1"></i> ${row.servicios}</span>`
                                : `<span class="text-muted">-</span>`;

                            let textCargo = row.cargo === 'No registrado en BD'
                                ? `<span class="badge badge-warning badge-custom text-dark"><i class="fas fa-user-slash mr-1"></i> No registrado</span>`
                                : `<span class="text-secondary font-weight-bold"><i class="fas fa-user-tie mr-1 text-primary"></i> ${row.cargo}</span>`;

                            html += `<tr>
                                        <td class="text-left"><b class="text-dark" style="font-size: 1.1rem;">${row.nombre}</b></td>
                                        <td>${textCargo}</td>
                                        <td><span class="text-dark font-weight-bold"><i class="far fa-calendar-alt mr-1 text-primary"></i> ${row.fecha_formateada}</span></td>
                                        <td>${badgeEntrada}</td>
                                        <td>${badgeSalida}</td>
                                        <td>${badgeServicios}</td>
                                    </tr>`;
                        });

                        $('#cuerpoTabla').html(html);
                        $('#resultadosContenedor').slideDown(500);
                    } else {
                        alert("Error de Servidor: " + resp.error);
                    }
                },
                error: function (xhr, status, error) {
                    btn.prop('disabled', false).html('<i class="fas fa-cogs mr-2"></i> PROCESAR NÓMINA EN RANGO SELECCIONADO');
                    console.error("Respuesta cruda:", xhr.responseText);
                    alert("Ocurrió un error en la comunicación. Revisa la consola (F12) para más detalles.");
                }
            });
        });
    });
</script>