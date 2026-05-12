<style>
    .btn-gradient-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-gradient-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(217, 119, 6, 0.4);
        color: white;
    }
    
    .resumen-caja {
        background-color: #f8fafc;
        border-radius: 10px;
        padding: 20px;
        border-left: 5px solid #3b82f6;
    }
</style>

<div class="row justify-content-center mt-4">
    <div class="col-md-10">
        <div class="card shadow-lg border-0 rounded-lg">

            <div class="card-header bg-dark text-white p-4" style="border-radius: 10px 10px 0 0;">
                <h3 class="card-title mb-0 d-flex align-items-center font-weight-bold">
                    <i class="fas fa-bell fa-2x mr-3 text-warning"></i>
                    Emisión de Alertas Logísticas
                </h3>
            </div>

            <div class="card-body p-5">
                <div class="alert alert-warning shadow-sm mb-4 p-4 rounded" style="border-left: 5px solid #f59e0b;">
                    <h5 class="font-weight-bold text-dark"><i class="fas fa-info-circle text-warning mr-2"></i> ¿Qué hace este proceso?</h5>
                    <p class="mb-0 text-muted">
                        Al ejecutar este proceso, el sistema analizará la base de datos para detectar:
                        <br>1. Puntos que han sido visitados <b>más de 2 veces</b> en los últimos 7 días.
                        <br>2. Desplazamientos <b>urbanos</b> del día de hoy que hayan tardado <b>más de 40 minutos</b>.
                        <br><br>Si se encuentran coincidencias, se enviará automáticamente un correo a supervisión.
                    </p>
                </div>

                <div class="text-center p-4">
                    <button type="button" class="btn btn-gradient-warning btn-lg px-5 py-3 rounded-pill font-weight-bold" id="btnGenerarAlertas">
                        <i class="fas fa-paper-plane mr-2"></i> GENERAR Y ENVIAR REPORTES AHORA
                    </button>
                </div>

                <!-- Contenedor de Resultados -->
                <div id="resultadosContenedor" class="mt-5" style="display: none;">
                    <hr class="mb-4">
                    
                    <div id="mensajeEstado" class="d-flex justify-content-between align-items-center p-3 bg-light rounded shadow-sm mb-4 border-left-success" style="border-left: 5px solid #198754;">
                        <div>
                            <h4 class="text-success font-weight-bold mb-0" id="tituloEstado"><i class="fas fa-check-circle mr-2"></i>Proceso Finalizado</h4>
                            <small class="text-muted" id="subtituloEstado">Revisa el resumen de los datos encontrados.</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="resumen-caja shadow-sm">
                                <h5 class="font-weight-bold text-dark"><i class="fas fa-map-marker-alt text-danger mr-2"></i> Visitas Recurrentes</h5>
                                <h2 class="text-primary mb-0" id="countVisitas">0</h2>
                                <small class="text-muted">Puntos detectados</small>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="resumen-caja shadow-sm" style="border-left-color: #ef4444;">
                                <h5 class="font-weight-bold text-dark"><i class="fas fa-route text-danger mr-2"></i> Tiempos Excedidos (>40m)</h5>
                                <h2 class="text-danger mb-0" id="countDesplazamientos">0</h2>
                                <small class="text-muted">Viajes urbanos detectados hoy</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-toggle="collapse" data-target="#vistaPreviaCorreo">
                            <i class="fas fa-eye mr-1"></i> Ver vista previa del correo enviado
                        </button>
                        <div class="collapse mt-3" id="vistaPreviaCorreo">
                            <div class="card card-body bg-light" id="contenidoCorreo" style="max-height: 400px; overflow-y: auto;">
                                <!-- Aquí se inyecta el HTML del correo -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Asegúrate de tener cargado Bootstrap JS para el "collapse" -->
<script>
    $(document).ready(function () {
        $('#btnGenerarAlertas').on('click', function () {
            let btn = $(this);
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> ANALIZANDO BASE DE DATOS...');
            $('#resultadosContenedor').slideUp();

            $.ajax({
                url: 'index.php?pagina=notificacionesLogistica&accion=procesarNotificaciones',
                type: 'POST',
                dataType: 'json',
                success: function (resp) {
                    btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i> GENERAR Y ENVIAR REPORTES AHORA');

                    if (resp.exito) {
                        let cantVisitas = resp.datos_visitas ? resp.datos_visitas.length : 0;
                        let cantDesplazamientos = resp.datos_desplazamientos ? resp.datos_desplazamientos.length : 0;

                        $('#countVisitas').text(cantVisitas);
                        $('#countDesplazamientos').text(cantDesplazamientos);

                        if (resp.correo_enviado) {
                            $('#tituloEstado').html('<i class="fas fa-check-circle mr-2"></i>Correos Enviados Exitosamente');
                            $('#subtituloEstado').text('Se enviaron las alertas a los supervisores.');
                        } else if (cantVisitas === 0 && cantDesplazamientos === 0) {
                            $('#tituloEstado').html('<i class="fas fa-info-circle mr-2 text-info"></i>Sin novedades hoy');
                            $('#tituloEstado').removeClass('text-success').addClass('text-info');
                            $('#subtituloEstado').text('No se encontraron registros que cumplan las condiciones. No se envió correo.');
                        } else {
                            $('#tituloEstado').html('<i class="fas fa-exclamation-triangle mr-2 text-danger"></i>Error al enviar correo');
                            $('#tituloEstado').removeClass('text-success').addClass('text-danger');
                            $('#subtituloEstado').text('Se detectaron datos, pero hubo un fallo al intentar enviar el correo (Revisa PHPMailer).');
                        }

                        if(resp.html_generado) {
                            $('#contenidoCorreo').html(resp.html_generado);
                        } else {
                            $('#contenidoCorreo').html('<p class="text-muted">No se generó cuerpo de correo porque no hubo alertas.</p>');
                        }

                        $('#resultadosContenedor').slideDown(500);
                    } else {
                        alert("Error de Servidor: " + resp.error);
                    }
                },
                error: function (xhr, status, error) {
                    btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i> GENERAR Y ENVIAR REPORTES AHORA');
                    console.error("Respuesta cruda:", xhr.responseText);
                    alert("Ocurrió un error en la comunicación. Revisa la consola para más detalles.");
                }
            });
        });
    });
</script>