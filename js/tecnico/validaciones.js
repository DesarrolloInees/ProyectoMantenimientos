// ==========================================
// VALIDACIONES Y CAPTURA DE GPS
// ==========================================

// Definir constante para el ID de Fallido (según tu BD es 4)
const ID_TIPO_FALLIDO = 4;

// ==========================================
// FUNCIÓN FINAL DE ENVÍO (Versión Infalible con soporte para Fallido)
// ==========================================
// ==========================================
// VALIDACIÓN Y ENVÍO DEL FORMULARIO (VERSIÓN MEJORADA)
// ==========================================
function validarYEnviar() {
    console.log('=== VALIDANDO FORMULARIO ===');

    // 🔥 PASO 0: Verificar y capturar la firma del canvas
    console.log('🔍 Verificando firma...');

    // Verificar si hay algo dibujado en el canvas
    let hayFirmaEnCanvas = false;
    if (canvas && ctx) {
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;

        for (let i = 0; i < data.length; i += 4) {
            // Si encuentra un píxel que no sea blanco (con margen)
            if (data[i] < 250 || data[i + 1] < 250 || data[i + 2] < 250) {
                hayFirmaEnCanvas = true;
                break;
            }
        }
    }

    console.log('¿Hay firma en canvas?', hayFirmaEnCanvas);

    // Si hay firma en el canvas pero el campo está vacío, actualizarlo
    if (hayFirmaEnCanvas) {
        const dataURL = canvas.toDataURL('image/png');
        $('#firma_base64').val(dataURL);
        console.log('✅ Firma capturada del canvas, longitud:', dataURL.length);
    }

    // Obtener el valor actualizado del campo
    let firmaBase64 = $('#firma_base64').val();
    console.log('Longitud de firma en campo:', firmaBase64 ? firmaBase64.length : 0);

    // 🔥 VALIDACIÓN MEJORADA: Verificar tanto el canvas como el campo
    if (!hayFirmaEnCanvas && !firmaBase64) {
        alert('⚠️ Por favor, realiza la firma del cliente en el cuadro de arriba.');
        return false;
    }

    // Si el canvas tiene firma pero el campo está vacío (por si acaso)
    if (hayFirmaEnCanvas && !firmaBase64) {
        const dataURL = canvas.toDataURL('image/png');
        $('#firma_base64').val(dataURL);
        firmaBase64 = dataURL;
        console.log('✅ Firma capturada forzadamente');
    }

    // 1. Verificar que el tiempo se haya calculado
    let tiempo = $('#tiempo_servicio').val();
    console.log('Tiempo actual:', tiempo);

    if (tiempo === '00:00') {
        // Intentar calcular nuevamente
        tiempo = calcularTiempoServicio();
        console.log('Tiempo recalculado:', tiempo);

        // Si sigue en 00:00, preguntar al usuario
        if (tiempo === '00:00') {
            let horaEntrada = $('#hora_entrada').val();
            let horaSalida = $('#hora_salida').val();

            if (horaEntrada && horaSalida) {
                // Si hay horas pero el cálculo falló, forzar recalculo
                tiempo = calcularTiempoServicio();
            } else {
                alert('⚠️ Por favor, selecciona la hora de entrada y salida antes de guardar.');
                return false;
            }
        }
    }

    // 2. Validar que se haya seleccionado un tipo de mantenimiento
    let tipoManto = $('select[name="id_tipo_mantenimiento"]').val();
    if (!tipoManto) {
        alert('⚠️ Por favor, selecciona el Tipo de Servicio.');
        return false;
    }

    // 3. Validar que se haya seleccionado una remisión
    let remision = $('select[name="numero_remision"]').val();
    if (!remision) {
        alert('⚠️ Por favor, selecciona un número de remisión.');
        return false;
    }

    // 4. Validar que se hayan subido fotos (mínimo 8)
    let totalFotos = parseInt($('#total_fotos_count').text()) || 0;
    if (totalFotos < 8) {
        if (!confirm('⚠️ Solo has subido ' + totalFotos + ' fotos. Se recomiendan mínimo 8 fotos (Antes, Remisión, Después). ¿Deseas continuar?')) {
            return false;
        }
    }

    // 5. Validar que se haya seleccionado estado final
    let estadoFinal = $('select[name="id_estado_maquina"]').val();
    if (!estadoFinal) {
        alert('⚠️ Por favor, selecciona el Estado Final de la máquina.');
        return false;
    }

    // 6. Validar actividades realizadas
    let actividades = $('textarea[name="actividades_realizadas"]').val().trim();
    if (!actividades) {
        alert('⚠️ Por favor, describe las actividades realizadas.');
        return false;
    }

    // 7. Mostrar resumen antes de guardar
    let resumen = '📋 Resumen del Servicio:\n\n';
    resumen += '🕐 Tiempo: ' + $('#tiempo_total_display').text() + '\n';
    resumen += '📸 Fotos: ' + totalFotos + '\n';
    resumen += '🔧 Tipo: ' + $('select[name="id_tipo_mantenimiento"] option:selected').text() + '\n';
    resumen += '📦 Remisión: ' + remision + '\n';
    resumen += '✅ Estado Final: ' + $('select[name="id_estado_maquina"] option:selected').text() + '\n';

    if (!confirm(resumen + '\n¿Guardar este servicio?')) {
        return false;
    }

    // 8. Enviar formulario
    console.log('Enviando formulario...');
    document.getElementById('formReporteMovil').submit();
    return true;
}

// ==========================================
// MOTOR DE UBICACIÓN
// ==========================================
function capturarGPSyEnviar(form) {
    // Mostramos estado de carga
    Swal.fire({
        title: 'Obteniendo ubicación...',
        text: 'Por favor encienda el GPS y conceda los permisos si el navegador se lo pide.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Verificamos si el celular soporta GPS
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            // EXITO: Tenemos las coordenadas
            function (position) {
                // Metemos las coordenadas en los inputs ocultos
                document.getElementById('latitud_fin').value = position.coords.latitude;
                document.getElementById('longitud_fin').value = position.coords.longitude;

                // Borramos autoguardado
                if (typeof limpiarBorradorStorage === 'function') {
                    limpiarBorradorStorage();
                }

                // Cambiamos el mensaje para que sepa que está subiendo fotos
                Swal.fire({
                    title: 'Guardando Reporte...',
                    text: 'Subiendo evidencias y cerrando servicio, no cierre la ventana.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // AHORA SÍ, ENVIAMOS A PHP
                form.submit();
            },
            // ERROR: No dio permiso, tiene el GPS apagado, etc.
            function (error) {
                let msjError = "Error desconocido.";
                if (error.code == 1) msjError = "Denegaste el permiso de ubicación. Debes permitirlo para guardar.";
                if (error.code == 2) msjError = "No se pudo obtener la señal GPS. Intenta salir a un lugar despejado.";
                if (error.code == 3) msjError = "Se agotó el tiempo para obtener la ubicación.";

                if (typeof Notificaciones !== 'undefined' && Notificaciones.error) {
                    Notificaciones.error("GPS Obligatorio", msjError);
                } else {
                    Swal.fire("GPS Obligatorio", msjError, "error");
                }
            },
            // Opciones del GPS (Alta precisión)
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    } else {
        if (typeof Notificaciones !== 'undefined' && Notificaciones.error) {
            Notificaciones.error("Incompatible", "Tu navegador o celular no soporta geolocalización.");
        } else {
            Swal.fire("Incompatible", "Tu navegador o celular no soporta geolocalización.", "error");
        }
    }
}